<?php
/**
 * SerialNumberGenerator.php
 * Generates and manages serial numbers for inventory tracking
 */

class SerialNumberGenerator {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Generate serial numbers for a PO item
     * Format: PO{po_id}-ITEM{item_id}-{sequence}
     * Example: PO20-ITEM12-001, PO20-ITEM12-002, etc.
     */
    public function generateSerials($po_id, $po_item_id, $item_id, $quantity, $po_code = null, $item_name = null) {
        try {
            $serials = [];

            // Get or create sequence
            $seq = $this->getOrCreateSequence($po_id, $po_item_id, $item_id, $quantity, $po_code, $item_name);
            $current_number = intval($seq['current_number']) + 1;

            // Generate serial numbers
            for ($i = 1; $i <= $quantity; $i++) {
                $seq_number = $current_number + $i - 1;
                $serial = $this->formatSerial($po_id, $item_id, $seq_number);
                $serials[] = [
                    'serial_number' => $serial,
                    'sequence_number' => $seq_number
                ];
            }

            // Save to database
            $this->saveSerials($po_id, $po_item_id, $item_id, $serials);

            // Update sequence counter
            $this->updateSequenceCounter($po_id, $po_item_id, $current_number + $quantity - 1);

            return [
                'success' => true,
                'serials' => $serials,
                'count' => count($serials)
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format serial number string
     */
    private function formatSerial($po_id, $item_id, $sequence_number) {
        $seq_padded = str_pad($sequence_number, 4, '0', STR_PAD_LEFT);
        return "PO{$po_id}-ITEM{$item_id}-{$seq_padded}";
    }

    /**
     * Get existing sequence or create new one
     */
    private function getOrCreateSequence($po_id, $po_item_id, $item_id, $quantity, $po_code, $item_name) {
        $stmt = $this->conn->prepare(
            "SELECT id, current_number FROM serial_number_sequences 
             WHERE po_id = ? AND po_item_id = ? AND item_id = ?"
        );
        $stmt->bind_param("iii", $po_id, $po_item_id, $item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        // Create new sequence
        $prefix = "PO{$po_id}-ITEM{$item_id}";
        $stmt = $this->conn->prepare(
            "INSERT INTO serial_number_sequences 
             (po_id, po_item_id, item_id, po_code, item_name, sequence_prefix, total_quantity_ordered, current_number) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
        );
        $stmt->bind_param("iiisssi", $po_id, $po_item_id, $item_id, $po_code, $item_name, $prefix, $quantity);
        $stmt->execute();

        return ['id' => $this->conn->insert_id, 'current_number' => 0];
    }

    /**
     * Save generated serials to database
     */
    private function saveSerials($po_id, $po_item_id, $item_id, $serials) {
        $stmt = $this->conn->prepare(
            "INSERT INTO item_serial_numbers 
             (serial_number, po_id, po_item_id, item_id, status) 
             VALUES (?, ?, ?, ?, 'ordered')"
        );

        foreach ($serials as $serial_data) {
            $serial = $serial_data['serial_number'];
            $stmt->bind_param("siii", $serial, $po_id, $po_item_id, $item_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert serial: " . $stmt->error);
            }
        }
    }

    /**
     * Update sequence counter
     */
    private function updateSequenceCounter($po_id, $po_item_id, $current_number) {
        $stmt = $this->conn->prepare(
            "UPDATE serial_number_sequences 
             SET current_number = ?, updated_at = NOW() 
             WHERE po_id = ? AND po_item_id = ?"
        );
        $stmt->bind_param("iii", $current_number, $po_id, $po_item_id);
        $stmt->execute();
    }

    /**
     * Get serials for a PO item
     */
    public function getSerials($po_id, $po_item_id = null, $status = null) {
        $sql = "SELECT * FROM item_serial_numbers WHERE po_id = ?";
        $params = [$po_id];
        $types = "i";

        if ($po_item_id) {
            $sql .= " AND po_item_id = ?";
            $params[] = $po_item_id;
            $types .= "i";
        }

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update serial status
     */
    public function updateSerialStatus($serial_number, $new_status, $remarks = null) {
        $stmt = $this->conn->prepare(
            "UPDATE item_serial_numbers 
             SET status = ?, remarks = ?, updated_at = NOW() 
             WHERE serial_number = ?"
        );
        $stmt->bind_param("sss", $new_status, $remarks, $serial_number);
        return $stmt->execute();
    }

    /**
     * Get serial by number
     */
    public function getSerialByNumber($serial_number) {
        $stmt = $this->conn->prepare(
            "SELECT s.*, i.name as item_name, p.po_code 
             FROM item_serial_numbers s 
             JOIN item_list i ON s.item_id = i.id 
             JOIN purchase_order_list p ON s.po_id = p.id 
             WHERE s.serial_number = ?"
        );
        $stmt->bind_param("s", $serial_number);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }
}
?>
