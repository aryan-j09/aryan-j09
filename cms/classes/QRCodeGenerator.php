<?php
/**
 * QRCodeGenerator.php
 * Generates QR codes for serial numbers using phpqrcode library
 */

class QRCodeGenerator {
    private $conn;
    private $qr_dir = 'uploads/qr_codes/';
    private $qr_plugin_path = 'plugins/phpqrcode/qrlib.php';

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Generate QR code for a serial number
     */
    public function generateQR($serial_number_id, $serial_number, $data, $generated_by) {
        try {
            // Ensure QR directory exists
            if (!$this->ensureQRDirectory()) {
                throw new Exception("Failed to create QR codes directory");
            }

            // Load phpqrcode library
            if (!file_exists($this->qr_plugin_path)) {
                throw new Exception("QR code library not found at: " . $this->qr_plugin_path);
            }
            require_once($this->qr_plugin_path);

            // Create filename
            $timestamp = time();
            $filename = $serial_number . '_' . $timestamp . '_' . $generated_by . '.png';
            $filepath = $this->qr_dir . $filename;

            // Generate QR code
            \QRcode::png(
                json_encode($data),
                $filepath,
                'L',  // Error correction level: L=7%, M=15%, Q=25%, H=30%
                4,    // Size
                2,    // Margin
                false,
                2
            );

            // Verify file was created
            if (!file_exists($filepath)) {
                throw new Exception("Failed to generate QR code file");
            }

            // Save to database
            $this->logQRGeneration($serial_number_id, $filepath, json_encode($data), $generated_by);

            // Update serial to mark QR as generated
            $stmt = $this->conn->prepare(
                "UPDATE item_serial_numbers 
                 SET qr_generated = 1, qr_image_path = ?, updated_at = NOW() 
                 WHERE id = ?"
            );
            $stmt->bind_param("si", $filepath, $serial_number_id);
            $stmt->execute();

            return [
                'success' => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'url' => base_url . $filepath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate QR codes in bulk
     */
    public function generateBulkQR($serial_ids, $generated_by) {
        $results = [];
        $success_count = 0;
        $error_count = 0;

        foreach ($serial_ids as $serial_id) {
            // Get serial details
            $stmt = $this->conn->prepare(
                "SELECT isn.*, il.name as item_name, pol.po_code 
                 FROM item_serial_numbers isn 
                 JOIN item_list il ON isn.item_id = il.id 
                 JOIN purchase_order_list pol ON isn.po_id = pol.id 
                 WHERE isn.id = ?"
            );
            $stmt->bind_param("i", $serial_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $serial_data = $result->fetch_assoc();

                // Prepare QR data
                $qr_data = [
                    'sn' => $serial_data['serial_number'],
                    'po_code' => $serial_data['po_code'],
                    'po_id' => $serial_data['po_id'],
                    'item_id' => $serial_data['item_id'],
                    'item_name' => $serial_data['item_name'],
                    'qty' => $serial_data['quantity'],
                    'generated_at' => date('Y-m-d H:i:s'),
                ];

                // Generate QR
                $qr_result = $this->generateQR($serial_id, $serial_data['serial_number'], $qr_data, $generated_by);

                if ($qr_result['success']) {
                    $success_count++;
                    $results[] = [
                        'serial' => $serial_data['serial_number'],
                        'status' => 'success',
                        'filepath' => $qr_result['filepath']
                    ];
                } else {
                    $error_count++;
                    $results[] = [
                        'serial' => $serial_data['serial_number'],
                        'status' => 'error',
                        'error' => $qr_result['error']
                    ];
                }
            }
        }

        return [
            'success' => $error_count === 0,
            'total' => count($serial_ids),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results
        ];
    }

    /**
     * Log QR generation in database
     */
    private function logQRGeneration($serial_number_id, $filepath, $qr_data_json, $generated_by) {
        $stmt = $this->conn->prepare(
            "INSERT INTO qr_code_logs 
             (serial_number_id, qr_image_path, qr_data_json, generated_by, print_count) 
             VALUES (?, ?, ?, ?, 0)"
        );
        $stmt->bind_param("issi", $serial_number_id, $filepath, $qr_data_json, $generated_by);
        return $stmt->execute();
    }

    /**
     * Get QR code for serial
     */
    public function getQRBySerial($serial_number) {
        $stmt = $this->conn->prepare(
            "SELECT qr.* 
             FROM qr_code_logs qr 
             JOIN item_serial_numbers isn ON qr.serial_number_id = isn.id 
             WHERE isn.serial_number = ? 
             ORDER BY qr.generated_at DESC 
             LIMIT 1"
        );
        $stmt->bind_param("s", $serial_number);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    /**
     * Log QR code print
     */
    public function logPrint($qr_log_id, $printed_by) {
        $stmt = $this->conn->prepare(
            "UPDATE qr_code_logs 
             SET printed_by = ?, printed_at = NOW(), print_count = print_count + 1 
             WHERE id = ?"
        );
        $stmt->bind_param("ii", $printed_by, $qr_log_id);
        return $stmt->execute();
    }

    /**
     * Ensure QR directory exists
     */
    private function ensureQRDirectory() {
        if (!is_dir($this->qr_dir)) {
            return @mkdir($this->qr_dir, 0777, true);
        }
        return true;
    }

    /**
     * Delete QR code file and log
     */
    public function deleteQR($qr_log_id) {
        $stmt = $this->conn->prepare("SELECT qr_image_path FROM qr_code_logs WHERE id = ?");
        $stmt->bind_param("i", $qr_log_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            if (file_exists($data['qr_image_path'])) {
                @unlink($data['qr_image_path']);
            }

            $stmt = $this->conn->prepare("DELETE FROM qr_code_logs WHERE id = ?");
            $stmt->bind_param("i", $qr_log_id);
            return $stmt->execute();
        }

        return false;
    }
}
?>
