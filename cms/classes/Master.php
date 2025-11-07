<?php
require_once('../config.php');

date_default_timezone_set('Asia/Kolkata');
Class Master extends DBConnection {
    private $settings;
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }
    public function __destruct(){
        parent::__destruct();
    }
    function capture_err(){
        if(!$this->conn->error)
            return false;
        else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
            return json_encode($resp);
            exit;
        }
    }
    function save_supplier(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                if(!empty($data)) $data .= ", ";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM `supplier_list` where `name` ='{$name}' ".(!empty($id) ? " and id != {$id} " : ""))->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Supplier already exists.";
            return json_encode($resp);
        }
        if(empty($id)){
            $sql = "INSERT INTO `supplier_list` set {$data} ";
        }else{
            $sql = "UPDATE `supplier_list` set {$data} where id = '{$id}' ";
        }
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"New Supplier successfully saved.");
            else
                $this->settings->set_flashdata('success',"Supplier successfully updated.");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    
    function delete_supplier(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `supplier_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Supplier successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
	function save_item(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k,array('id', 'attributes', 'values'))){
            $v = $this->conn->real_escape_string($v);
            if(!empty($data)) $data .=",";
            $data .= " `{$k}`='{$v}' ";
        }
    }

    $check = $this->conn->query("SELECT * FROM `item_list` where `name` = '{$name}' and `supplier_id` = '{$supplier_id}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
    if($this->capture_err())
        return $this->capture_err();
    if($check > 0){
        $resp['status'] = 'failed';
        $resp['msg'] = "Item already exists under selected supplier.";
        return json_encode($resp);
        exit;
    }
    if(empty($id)){
        $sql = "INSERT INTO `item_list` set {$data} ";
        $save = $this->conn->query($sql);
        $item_id = $this->conn->insert_id;
    }else{
        $sql = "UPDATE `item_list` set {$data} where id = '{$id}' ";
        $save = $this->conn->query($sql);
        $item_id = $id;
    }
    if($save){
        $resp['status'] = 'success';
        if(empty($id))
            $this->settings->set_flashdata('success',"New Item successfully saved.");
        else
            $this->settings->set_flashdata('success',"Item successfully updated.");
            
        // Save attributes
        $this->conn->query("DELETE FROM `item_attributes` where `item_id` = '{$item_id}'");
        if(isset($attributes) && isset($values)){
            foreach($attributes as $key => $attr){
                if(empty($attr)) continue;
                $value = $values[$key];
                $this->conn->query("INSERT INTO `item_attributes` (`item_id`, `attribute`, `value`) VALUES ('{$item_id}', '{$attr}', '{$value}')");
            }
        }
    }else{
        $resp['status'] = 'failed';
        $resp['err'] = $this->conn->error."[{$sql}]";
    }
    return json_encode($resp);
}

    function delete_item(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `item_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Item successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function save_machine_item(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=", ";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM `machine_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : ""))->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "Machine item already exists.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)){
            $sql = "INSERT INTO `machine_list` set {$data} ";
        }else{
            $sql = "UPDATE `machine_list` set {$data} where id = '{$id}' ";
        }
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"New Item successfully saved.");
            else
                $this->settings->set_flashdata('success',"Item successfully updated.");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function delete_machine_item(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `machine_list` where id = '{$id}'");
        if($del){
            $resp['status'] = '1';
            $this->settings->set_flashdata('success',"Item successfully deleted.");
        }else{
            $resp['status'] = '0';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
	function save_po(){
        extract($_POST);
        $spec_sheet = isset($_POST['spec_sheet']) ? $this->conn->real_escape_string($_POST['spec_sheet']) : '';
        $data = "po_code = '{$po_code}', internal_ref_no = '{$internal_ref_no}', supplier_id = '{$supplier_id}', remarks = '{$remarks}', spec_sheet = '{$spec_sheet}', status = 'pending'";
        if(empty($id)){
            $sql = "INSERT INTO `purchase_order_list` set {$data}";
        }else{
            $sql = "UPDATE `purchase_order_list` set {$data} where id = '{$id}'";
        }
        $save = $this->conn->query($sql);
        if($save){
            $po_id = empty($id) ? $this->conn->insert_id : $id;
            $resp['status'] = 'success';
            $resp['id'] = $po_id;
            $data = "";
            foreach($item_id as $k =>$v){
                if(!empty($data)) $data .=", ";
                $data .= "('{$po_id}','{$v}','{$qty[$k]}','{$price[$k]}','{$unit[$k]}','{$total[$k]}')";
            }
            if(!empty($data)){
                $this->conn->query("DELETE FROM `po_items` where po_id = '{$po_id}'");
                $this->conn->query("INSERT INTO `po_items` (`po_id`,`item_id`,`quantity`,`price`,`unit`,`total`) VALUES {$data}");
            }
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = 'An error occurred. Error: '.$this->conn->error;
        }
        return json_encode($resp);
    }

    function delete_po(){
        extract($_POST);

        // Delete related po_items
        $this->conn->query("DELETE FROM `po_items` WHERE po_id = '{$id}'");

        // Delete the purchase order
        $delete = $this->conn->query("DELETE FROM `purchase_order_list` WHERE id = '{$id}'");
        if($delete){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Purchase Order was Successfully deleted.");
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function save_client() {
        
        try {
            extract($_POST);
            $this->conn->begin_transaction();
    
            // Build data for client insert/update
            $data = "";
            foreach($_POST as $k => $v) {
                if(!in_array($k, array('id', 'converted_from_lead'))) {
                    $v = $this->conn->real_escape_string($v);
                    if(!empty($data)) $data .= ", ";
                    $data .= "`{$k}`='{$v}'";
                }
            }
    
            // Save client
            if(empty($id)) {
                $sql = "INSERT INTO clients SET {$data}";
            } else {
                $sql = "UPDATE clients SET {$data} WHERE id = '{$id}'";
            }
    
            $save = $this->conn->query($sql);
            if(!$save) {
                throw new Exception("Failed to save client");
            }
    
            // Update lead status if this is a conversion
            if(isset($converted_from_lead) && !empty($converted_from_lead)) {
                // First log the activity
                $created_by = $_SESSION['userdata']['id'];
                $activity_sql = "INSERT INTO lead_activities (lead_id, activity_type, description, created_by) 
                               VALUES ('{$converted_from_lead}', 'status_change', 'Lead converted to client', '{$created_by}')";
                
                if(!$this->conn->query($activity_sql)) {
                    throw new Exception("Failed to log conversion activity");
                }
    
                // Then update the lead status
                $update_sql = "UPDATE leads SET status = 'converted' WHERE id = '{$converted_from_lead}'";
                if(!$this->conn->query($update_sql)) {
                    throw new Exception("Failed to update lead status");
                }
            }
    
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', empty($id) ? 
                "New client successfully saved." : "Client successfully updated.");
    
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Save client error: " . $e->getMessage());
        }
    
        return json_encode($resp);
    }

    function delete_client(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `clients` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Client successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function save_pi() {
        try {
            extract($_POST);
            
            // Generate work order number for new invoices
            if (empty($id)) {
                $query = "SELECT work_order_number FROM proforma_invoice_list 
                          WHERE work_order_number LIKE 'WO%' 
                          ORDER BY CAST(SUBSTRING(work_order_number, 3) AS UNSIGNED) DESC 
                          LIMIT 1";
                
                $result = $this->conn->query($query);
                $nextNumber = 1;
    
                if ($result && $result->num_rows > 0) {
                    $lastNumber = $result->fetch_assoc()['work_order_number'];
                    $numericPart = intval(substr($lastNumber, 2));
                    $nextNumber = $numericPart + 1;
                }
                
                $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                // Add the generated number directly to the $_POST array
                $_POST['work_order_number'] = "WO{$formattedNumber}";
                // Add a specific debug log to confirm generation
                error_log("Generated Work Order Number: " . $_POST['work_order_number']);
            }
            
            // Add ABG/PBG handling
            $_POST['abg_required'] = isset($abg_required) ? 1 : 0;
            $_POST['pbg_required'] = isset($pbg_required) ? 1 : 0;
            
            // Build the data string for the SQL query
            $data = "";
            foreach ($_POST as $k => $v) {
                if (!in_array($k, array('id', 'description', 'amount', 'employee_passcode', 'admin_passcode')) && !is_array($v)) {
                    $v = $this->conn->real_escape_string($v);
                    if (!empty($data)) $data .= ", ";
                    $data .= " `{$k}` = '{$v}' ";
                }
            }
    
            // Set default values if not provided
            $packing_forwarding = isset($packing_forwarding) ? $packing_forwarding : 0;
            $tax = isset($tax) ? $tax : 0;
            $cgst = isset($cgst) ? $cgst : 0;
            $sgst = isset($sgst) ? $sgst : 0;
            $advance_payment = isset($advance_payment) ? $advance_payment : 0;
            $inspection_payment = isset($inspection_payment) ? $inspection_payment : 0;
            $installation_payment = isset($installation_payment) ? $installation_payment : 0;
        
    
                // Generate pi_code if not provided
                if (empty($pi_code)) {
                    $result = $this->conn->query("SELECT MAX(id) AS max_id FROM proforma_invoice_list");
                    $row = $result->fetch_assoc();
                    $next_id = $row['max_id'] + 1;
                    $pi_code = 'PI-' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
                    if (!empty($data)) $data .= ", ";
                    $data .= " `pi_code` = '{$pi_code}'";
                }
    
                // Validate employee passcode
                $employee_result = $this->conn->query("SELECT * FROM approvers WHERE role = 'employee' AND passcode = '{$employee_passcode}'");
                if ($employee_result->num_rows > 0) {
                    $employee_approved = 1;
                    $employee_approved_by = $employee_result->fetch_assoc()['name'];
                } else {
                    $employee_approved = 0;
                    $employee_approved_by = null;
                }
    
                // Validate admin passcode
                $admin_result = $this->conn->query("SELECT * FROM approvers WHERE role = 'admin' AND passcode = '{$admin_passcode}'");
                if ($admin_result->num_rows > 0) {
                    $admin_approved = 1;
                    $admin_approved_by = $admin_result->fetch_assoc()['name'];
                } else {
                    $admin_approved = 0;
                    $admin_proved_by = null;
                }
    
                // Add approval passcodes to the data
                if (!empty($data)) $data .= ", ";
                $data .= " `employee_approved` = '{$employee_approved}', `admin_approved` = '{$admin_approved}', `employee_approved_by` = '{$employee_approved_by}', `admin_approved_by` = '{$admin_approved_by}'";
    
                if (empty($id)) {
                    $sql = "INSERT INTO `proforma_invoice_list` SET {$data}";
                } else {
                    $sql = "UPDATE `proforma_invoice_list` SET {$data} WHERE id = '{$id}'";
                }
                
                // Add a debug log for the final query
                error_log("Final SQL Query: " . $sql);
                
                $save = $this->conn->query($sql);
                
                if ($save) {
                    $resp['status'] = 'success';
                    $pi_id = empty($id) ? $this->conn->insert_id : $id;
                    $resp['id'] = $pi_id;
    
                    if (empty($id)) {
                        $resp['msg'] = "New Proforma Invoice successfully created.";
                    } else {
                        $resp['msg'] = "Proforma Invoice successfully updated.";
                    }
    
                    // Save items
                    if(!empty($_POST['description'])) {
                        $stmt = $this->conn->prepare("DELETE FROM proforma_invoice_items WHERE proforma_invoice_id = ?");
                        $stmt->bind_param("i", $pi_id);
                        $stmt->execute();
    
                        $stmt = $this->conn->prepare("INSERT INTO proforma_invoice_items (proforma_invoice_id, description, hsn_code, amount) VALUES (?, ?, ?, ?)");
                        
                        foreach ($_POST['description'] as $key => $description) {
                            $amount = $_POST['amount'][$key];
                            $hsn_code = $_POST['hsn_code'][$key];
                            
                            $stmt->bind_param("issd", $pi_id, $description, $hsn_code, $amount);
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to save item details: " . $stmt->error);
                            }
                        }
                    }
                    
                    $resp['pi_code'] = $pi_code ?? null;
                    
                } else {
                    $resp['status'] = 'failed';
                    $resp['msg'] = "Error: " . $this->conn->error;
                    error_log("SQL Error: " . $this->conn->error);
                }
    
            } catch (Exception $e) {
                $resp['status'] = 'failed';
                $resp['msg'] = "Exception: " . $e->getMessage();
                error_log("Exception: " . $e->getMessage());
            }
    
            return json_encode($resp);
        }

    function delete_pi(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `proforma_invoice_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success'," Proforma Invoice was successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    function save_po_details() {
    try {
        extract($_POST);

        $tds_amount = isset($_POST['tds_amount']) ? floatval($_POST['tds_amount']) : 0;

        // Handle file upload
        $uploadedFile = '';
        if(isset($_FILES['po_file']) && $_FILES['po_file']['error'] == 0) {
            // Fix upload path - use absolute path
            $upload_path = dirname(dirname(__FILE__)) . '/uploads/po_files/';
            
            // Create directory with proper permissions
            if(!file_exists($upload_path)) {
                if(!mkdir($upload_path, 0777, true)) {
                    error_log("Failed to create directory: " . $upload_path);
                    throw new Exception("Failed to create upload directory");
                }
            }

            // Sanitize filename using PO code
            $ext = pathinfo($_FILES['po_file']['name'], PATHINFO_EXTENSION);
            $po_code_safe = preg_replace('/[^A-Za-z0-9\-]/', '_', $po_code);
            $fname = "po_{$po_code_safe}_" . time() . "." . $ext;
            $upload_file_path = $upload_path . $fname;
            
            error_log("Trying to move PO file to: " . $upload_file_path);
            if(!move_uploaded_file($_FILES['po_file']['tmp_name'], $upload_file_path)) {
                throw new Exception("Failed to save PO file. Path: " . $upload_file_path);
            }
            
            $uploadedFile = $fname;
        }

        // Convert payment values to float
        $adv_received = isset($advance_received) ? floatval($advance_received) : 0;
        $insp_received = isset($inspection_received) ? floatval($inspection_received) : 0;
        $inst_received = isset($installation_received) ? floatval($installation_received) : 0;
        $cred_received = isset($credit_received) ? floatval($credit_received) : 0; // Add this line

        // Inside the save_po_details function, after handling the PO file upload
        // Add this code for e-way bill upload
        $uploadedEwayFile = '';
        if(isset($_FILES['eway_file']) && $_FILES['eway_file']['error'] == 0) {
            // Fix upload path - use absolute path
            $eway_upload_path = dirname(dirname(__FILE__)) . '/uploads/eway_file/';
            
            // Create directory with proper permissions if it doesn't exist
            if(!file_exists($eway_upload_path)) {
                if(!mkdir($eway_upload_path, 0777, true)) {
                    error_log("Failed to create e-way bill directory: " . $eway_upload_path);
                    throw new Exception("Failed to create e-way bill upload directory");
                }
            }

            // Sanitize filename using PO code
            $ext = pathinfo($_FILES['eway_file']['name'], PATHINFO_EXTENSION);
            $po_code_safe = preg_replace('/[^A-Za-z0-9\-]/', '_', $po_code);
            $eway_fname = "eway_{$po_code_safe}_" . time() . "." . $ext;
            $upload_eway_path = $eway_upload_path . $eway_fname;
            
            error_log("Trying to move e-way bill file to: " . $upload_eway_path);
            if(!move_uploaded_file($_FILES['eway_file']['tmp_name'], $upload_eway_path)) {
                throw new Exception("Failed to save e-way bill file. Path: " . $upload_eway_path);
            }
            
            $uploadedEwayFile = $eway_fname;
        }

        // Inside the save_po_details function, after handling the eway_file upload:

        // Handle LR Copy upload
        $uploadedLrFile = '';
        if(isset($_FILES['lr_file']) && $_FILES['lr_file']['error'] == 0) {
            $lr_upload_path = dirname(dirname(__FILE__)) . '/uploads/lr_file/';
            
            if(!file_exists($lr_upload_path)) {
                if(!mkdir($lr_upload_path, 0777, true)) {
                    error_log("Failed to create LR file directory: " . $lr_upload_path);
                    throw new Exception("Failed to create LR file upload directory");
                }
            }

            $ext = pathinfo($_FILES['lr_file']['name'], PATHINFO_EXTENSION);
            $po_code_safe = preg_replace('/[^A-Za-z0-9\-]/', '_', $po_code);
            $lr_fname = "lr_{$po_code_safe}_" . time() . "." . $ext;
            $upload_lr_path = $lr_upload_path . $lr_fname;
            
            if(!move_uploaded_file($_FILES['lr_file']['tmp_name'], $upload_lr_path)) {
                throw new Exception("Failed to save LR file");
            }
            
            $uploadedLrFile = $lr_fname;
        }

        // Handle Quotation file upload
        $uploadedQuotationFile = '';
        if(isset($_FILES['quotation_file']) && $_FILES['quotation_file']['error'] == 0) {
            $quotation_upload_path = dirname(dirname(__FILE__)) . '/uploads/quotation_files/';
            
            if(!file_exists($quotation_upload_path)) {
                if(!mkdir($quotation_upload_path, 0777, true)) {
                    error_log("Failed to create quotation directory: " . $quotation_upload_path);
                    throw new Exception("Failed to create quotation upload directory");
                }
            }

            $ext = pathinfo($_FILES['quotation_file']['name'], PATHINFO_EXTENSION);
            $po_code_safe = preg_replace('/[^A-Za-z0-9\-]/', '_', $po_code);
            $quotation_fname = "quotation_{$po_code_safe}_" . time() . "." . $ext;
            $upload_quotation_path = $quotation_upload_path . $quotation_fname;
            
            if(!move_uploaded_file($_FILES['quotation_file']['tmp_name'], $upload_quotation_path)) {
                throw new Exception("Failed to save quotation file");
            }
            
            $uploadedQuotationFile = $quotation_fname;
        }

        // Modify the INSERT query to include the new fields
        if(empty($id)) {
            $sql = "INSERT INTO purchase_orders (
                    client_id, po_code, requirement, specification, 
                    expected_delivery, remarks, advance_received,
                    inspection_received, installation_received,
                    credit_received, tds_amount, po_file, eway_file, lr_file, quotation_file
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isssssdddddssss", 
                $client_id,
                $po_code,
                $requirement,
                $specification,
                $expected_delivery,
                $remarks,
                $adv_received,
                $insp_received,
                $inst_received,
                $cred_received,
                $tds_amount,
                $uploadedFile,
                $uploadedEwayFile,
                $uploadedLrFile,
                $uploadedQuotationFile
            );
        } else {
            // Build UPDATE query dynamically based on which files were uploaded
            $updateFields = [
                "requirement = ?",
                "specification = ?",
                "expected_delivery = ?",
                "remarks = ?",
                "advance_received = ?",
                "inspection_received = ?",
                "installation_received = ?",
                "credit_received = ?",
                "tds_amount = ?"
            ];
            $params = [
                $requirement,
                $specification,
                $expected_delivery,
                $remarks,
                $adv_received,
                $insp_received,
                $inst_received,
                $cred_received,
                $tds_amount
            ];
            $types = "ssssddddd";

            if($uploadedFile) {
                $updateFields[] = "po_file = ?";
                $params[] = $uploadedFile;
                $types .= "s";
            }
            if($uploadedEwayFile) {
                $updateFields[] = "eway_file = ?";
                $params[] = $uploadedEwayFile;
                $types .= "s";
            }
            if($uploadedLrFile) {
                $updateFields[] = "lr_file = ?";
                $params[] = $uploadedLrFile;
                $types .= "s";
            }
            if($uploadedQuotationFile) {
                $updateFields[] = "quotation_file = ?";
                $params[] = $uploadedQuotationFile;
                $types .= "s";
            }

            $params[] = $id;
            $types .= "i";

            $sql = "UPDATE purchase_orders SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        }

        $save = $stmt->execute();
        
        if($save) {
            $resp['status'] = 'success';
            if(empty($id)) {
                $resp['id'] = $this->conn->insert_id;
            } else {
                $resp['id'] = $id;
            }
            
            if(empty($id))
                $this->settings->set_flashdata('success', "Purchase Order successfully created.");
            else 
                $this->settings->set_flashdata('success', "Purchase Order successfully updated.");
        } else {
            throw new Exception($stmt->error);
        }

    } catch (Exception $e) {
        $resp['status'] = 'error';
        $resp['msg'] = 'Error: ' . $e->getMessage();
        error_log($e->getMessage());
    }

    echo json_encode($resp);
    exit;
}

    function delete_po_details() {
    try {
        extract($_POST);
        
        // Begin transaction
        $this->conn->begin_transaction();

        // Get all file paths before deleting
        $stmt = $this->conn->prepare("SELECT po_file, eway_file, lr_file, quotation_file FROM purchase_orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if(!$stmt->execute()) {
            throw new Exception("Failed to fetch file information");
        }
        $stmt->bind_result($po_file, $eway_file, $lr_file, $quotation_file);
        if($stmt->fetch()) {
            $row = [
                'po_file' => $po_file,
                'eway_file' => $eway_file,
                'lr_file' => $lr_file,
                'quotation_file' => $quotation_file
            ];
        } else {
            $row = false;
        }
        $stmt->close();
        
        // Delete PO file if exists
        if(!empty($row['po_file'])) {
            $po_file_path = dirname(dirname(__FILE__)) . '/uploads/po_files/' . $row['po_file'];
            if(file_exists($po_file_path)) {
                unlink($po_file_path);
            }
        }

        // Delete e-way bill file
        if(!empty($row['eway_file'])) {
            $eway_file_path = dirname(dirname(__FILE__)) . '/uploads/eway_file/' . $row['eway_file'];
            if(file_exists($eway_file_path)) {
                unlink($eway_file_path);
            }
        }

        // Delete LR file
        if(!empty($row['lr_file'])) {
            $lr_file_path = dirname(dirname(__FILE__)) . '/uploads/lr_file/' . $row['lr_file'];
            if(file_exists($lr_file_path)) {
                unlink($lr_file_path);
            }
        }

        // Delete quotation file
        if(!empty($row['quotation_file'])) {
            $quotation_file_path = dirname(dirname(__FILE__)) . '/uploads/quotation_files/' . $row['quotation_file'];
            if(file_exists($quotation_file_path)) {
                unlink($quotation_file_path);
            }
        }

        // Delete the purchase order
        $sql = "DELETE FROM purchase_orders WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $delete = $stmt->execute();

        if ($delete) {
            $this->conn->commit();
            $_SESSION['success_msg'] = "Purchase Order deleted successfully";
            $resp['status'] = 'success';
        } else {
            throw new Exception("Failed to delete Purchase Order");
        }

    } catch (Exception $e) {
        if ($this->conn->connect_errno) {
            $this->conn->rollback();
        }
        $resp['status'] = 'error';
        $resp['msg'] = 'Error: ' . $e->getMessage();
        error_log($e->getMessage());
    }

    echo json_encode($resp);
    exit;
}

    function complete_project() {
        $resp = ['status' => 'failed', 'msg' => ''];
        
        try {
            error_log("POST: " . print_r($_POST, true));
            error_log("FILES: " . print_r($_FILES, true));

            if(empty($_POST['po_id']) || empty($_POST['delivery_date']) || empty($_POST['po_code'])) {
                throw new Exception("Required fields are missing");
            }

            // Fix upload path - use absolute path
            $upload_path = dirname(dirname(__FILE__)) . '/uploads/invoices/';
            error_log("Upload path: " . $upload_path);
            
            // Create directory with proper permissions
            if(!file_exists($upload_path)) {
                if(!mkdir($upload_path, 0777, true)) {
                    error_log("Failed to create directory: " . $upload_path);
                    throw new Exception("Failed to create upload directory");
                }
            }

            $po_id = $_POST['po_id'];
            $delivery_date = $_POST['delivery_date'];
            $po_code = preg_replace('/[^A-Za-z0-9\-]/', '_', $_POST['po_code']); // Sanitize filename

            // Handle bill file
            if(isset($_FILES['bill_file']) && $_FILES['bill_file']['error'] == 0) {
                $ext = pathinfo($_FILES['bill_file']['name'], PATHINFO_EXTENSION);
                $bill_fname = "bill_{$po_code}_" . time() . "." . $ext;
                $bill_path = $upload_path . $bill_fname;
                
                error_log("Trying to move bill file to: " . $bill_path);
                if(!move_uploaded_file($_FILES['bill_file']['tmp_name'], $bill_path)) {
                    throw new Exception("Failed to save bill file. Path: " . $bill_path);
                }
            } else {
                throw new Exception("Bill file is required");
            }

            // Handle challan file
            if(isset($_FILES['challan_file']) && $_FILES['challan_file']['error'] == 0) {
                $ext = pathinfo($_FILES['challan_file']['name'], PATHINFO_EXTENSION);
                $challan_fname = "challan_{$po_code}_" . time() . "." . $ext;
                $challan_path = $upload_path . $challan_fname;
                
                error_log("Trying to move challan file to: " . $challan_path);
                if(!move_uploaded_file($_FILES['challan_file']['tmp_name'], $challan_path)) {
                    throw new Exception("Failed to save challan file. Path: " . $challan_path);
                }
            } else {
                throw new Exception("Challan file is required");
            }

            // Update database
            $sql = "UPDATE purchase_orders SET 
                    status = 'completed', 
                    actual_delivery_date = ?, 
                    bill_file = ?, 
                    challan_file = ? 
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            if(!$stmt) {
                throw new Exception("Database prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("sssi", $delivery_date, $bill_fname, $challan_fname, $po_id);
            
            if(!$stmt->execute()) {
                throw new Exception("Database update failed: " . $stmt->error);
            }

            if($stmt->affected_rows <= 0) {
                throw new Exception("No records were updated. PO ID: " . $po_id);
            }

            $resp['status'] = 'success';
            $resp['msg'] = 'Project completed successfully';
            
            // Add this line to set session message
            $this->settings->set_flashdata('success', "Project has been completed successfully.");

        } catch(Exception $e) {
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Complete project error: " . $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode($resp);
        exit;
    }

    function verify_requirements() {
    extract($_POST);
    
    if(!isset($po_id)) {
        return json_encode([
            'status' => 'failed',
            'msg' => 'PO ID is required'
        ]);
    }

    $verified_by = $_SESSION['userdata']['id'];
    $verified_at = date('Y-m-d H:i:s');
    
    // Calculate hash of current content
    $query = $this->conn->prepare("SELECT requirement, specification FROM purchase_orders WHERE id = ?");
    $query->bind_param("i", $po_id);
    $query->execute();
    $query->bind_result($requirement, $specification);
    if($query->fetch()) {
        $row = [
            'requirement' => $requirement,
            'specification' => $specification
        ];
    } else {
        $row = false;
    }
    $query->close();
    
    // Create hash of combined content
    $content_hash = hash('sha256', $row['requirement'] . $row['specification']);

    $sql = "UPDATE purchase_orders SET 
            requirements_verified = 1,
            verified_by = ?,
            verified_at = ?,
            requirements_hash = ?
            WHERE id = ?";

    $stmt = $this->conn->prepare($sql);
    $stmt->bind_param("isss", $verified_by, $verified_at, $content_hash, $po_id);

    if($stmt->execute()) {
        return json_encode([
            'status' => 'success'
        ]);
    } else {
        return json_encode([
            'status' => 'failed',
            'msg' => 'Failed to update verification status: ' . $this->conn->error
        ]);
    }
}

    function log_activity() {
        $resp = array('status'=>'failed', 'msg'=>'');
        try {
            if(empty($_POST['lead_id']) || empty($_POST['activity_type']) || empty($_POST['description'])) {
                throw new Exception("Lead ID, activity type and description are required");
            }

            $this->conn->begin_transaction();

            // Check if this is an edit operation
            $activity_id = isset($_POST['activity_id']) ? $_POST['activity_id'] : null;
            $prev_activity_id = isset($_POST['prev_activity_id']) ? intval($_POST['prev_activity_id']) : null;

            if($activity_id) {
                // Update existing activity (edit mode)
                $sql = "UPDATE lead_activities SET 
                        activity_type = ?, 
                        description = ?, 
                        next_followup = ?, 
                        created_at = ?,
                        time_from = ?,
                        time_to = ?
                        WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d H:i:s');
                $next_followup = !empty($_POST['next_followup']) ? $_POST['next_followup'] : null;
                $time_from = !empty($_POST['time_from']) ? $_POST['time_from'] : null;
                $time_to = !empty($_POST['time_to']) ? $_POST['time_to'] : null;
                $stmt->bind_param("ssssssi", 
                    $_POST['activity_type'],
                    $_POST['description'],
                    $next_followup,
                    $created_at,
                    $time_from,
                    $time_to,
                    $activity_id
                );
            } else {
                // Insert new activity (always use current date/time for created_at)
                $sql = "INSERT INTO lead_activities (
                    lead_id, 
                    activity_type, 
                    description, 
                    next_followup, 
                    created_by, 
                    created_at,
                    time_from,
                    time_to
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                // Use provided created_at if sent by client (allows logging past activities),
                // otherwise fall back to current server time.
                $created_at = isset($_POST['created_at']) && !empty($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d H:i:s');
                $next_followup = !empty($_POST['next_followup']) ? $_POST['next_followup'] : null;
                $time_from = !empty($_POST['time_from']) ? $_POST['time_from'] : null;
                $time_to = !empty($_POST['time_to']) ? $_POST['time_to'] : null;
                $stmt->bind_param("isssssss", 
                    $_POST['lead_id'],
                    $_POST['activity_type'],
                    $_POST['description'],
                    $next_followup,
                    $_SESSION['userdata']['id'],
                    $created_at,
                    $time_from,
                    $time_to
                );
                // Mark the previous activity as handled if prev_activity_id is present
                if ($prev_activity_id) {
                    $this->conn->query("UPDATE lead_activities SET handled = 1 WHERE id = '{$prev_activity_id}'");
                }
            }
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to save activity: " . $stmt->error);
            }

            $new_activity_id = $activity_id ?: $this->conn->insert_id;

            // Handle document uploads if any
            if(isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
                $upload_path = '../uploads/lead_documents/';
                
                // Create directory if it doesn't exist
                if(!is_dir($upload_path)) {
                    if(!mkdir($upload_path, 0777, true)) {
                        throw new Exception("Failed to create upload directory");
                    }
                }
    
                // Process each uploaded document
                for($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                    if($_FILES['documents']['error'][$i] == 0) {
                        $file_ext = pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION);
                        $file_name = 'doc_' . time() . '_' . $i . '.' . $file_ext;
                        $file_path = 'uploads/lead_documents/' . $file_name;
                        $full_path = $upload_path . $file_name;
    
                        // Move uploaded file
                        if(move_uploaded_file($_FILES['documents']['tmp_name'][$i], $full_path)) {
                            // Insert document record
                            $doc_type = $_POST['document_type'][$i];
                            $doc_desc = $_POST['document_description'][$i];
                            
                            $sql = "INSERT INTO lead_documents (
                                activity_id,
                                document_type,
                                document_description,
                                file_name,
                                file_path
                            ) VALUES (?, ?, ?, ?, ?)";
                            
                            $stmt = $this->conn->prepare($sql);
                            $stmt->bind_param("issss", 
                                $new_activity_id,
                                $doc_type,
                                $doc_desc,
                                $_FILES['documents']['name'][$i],
                                $file_path
                            );
                            
                            if(!$stmt->execute()) {
                                throw new Exception("Failed to save document record");
                            }
                        } else {
                            throw new Exception("Failed to upload file: " . $_FILES['documents']['name'][$i]);
                        }
                    }
                }
            }
    
            $this->conn->commit();
            $_SESSION['success_msg'] = $new_activity_id ? "Activity updated successfully" : "Activity logged successfully";
            $resp['status'] = 'success';
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Activity log error: " . $e->getMessage());
        }
        
        echo json_encode($resp);
        exit;
    }

    function save_lead() {
        $resp = array('status'=>'failed', 'msg'=>'', 'id'=>'');  
        
        if(isset($_POST['update_status_only']) && $_POST['update_status_only']) {
            $sql = "UPDATE leads SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('si', $_POST['status'], $_POST['id']);
            
            if($stmt->execute()) {
                $resp['status'] = 'success';
            } else {
                $resp['status'] = 'failed';
                $resp['msg'] = 'An error occurred while updating the lead status';
            }
            return json_encode($resp);
        }
        try {
            if(empty($_POST['company_name']) || empty($_POST['contact_person'])) {
                throw new Exception("Company name and contact person are required");
            }
    
            $data = array();
            foreach($_POST as $k => $v) {
                if(!in_array($k, array('id'))) {
                    $data[$k] = $this->conn->real_escape_string($v);
                }
            }
    
            if(empty($_POST['id'])) {
                $sql = "INSERT INTO leads SET " . implode(", ", array_map(function($k, $v) {
                    return "`$k`='$v'";
                }, array_keys($data), $data));
            } else {
                $sql = "UPDATE leads SET " . implode(", ", array_map(function($k, $v) {
                    return "`$k`='$v'";
                }, array_keys($data), $data)) . " WHERE id='".$_POST['id']."'";
            }
    
            $save = $this->conn->query($sql);
    
            if($save) {
                $resp['status'] = 'success';
                $resp['id'] = empty($_POST['id']) ? $this->conn->insert_id : $_POST['id'];
                $this->settings->set_flashdata('success', empty($_POST['id']) ? 
                    "New Lead successfully saved." : "Lead successfully updated.");
            } else {
                throw new Exception($this->conn->error);
            }
    
        } catch (Exception $e) {
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
    
        echo json_encode($resp);
        exit;
    }

    function delete_lead(){
    try {
        extract($_POST);
        
        if(!isset($id)) {
            throw new Exception("Lead ID is required");
        }
        
        $this->conn->begin_transaction();
        
        // Delete associated documents first
        $docs = $this->conn->query("SELECT file_path FROM lead_documents 
                                   WHERE activity_id IN (SELECT id FROM lead_activities WHERE lead_id = '{$id}')");
        
        while($row = $docs->fetch_assoc()){
            if(file_exists($row['file_path'])){
                unlink($row['file_path']);
            }
        }
        
        // Delete activities and their documents
        $this->conn->query("DELETE FROM lead_documents 
                           WHERE activity_id IN (SELECT id FROM lead_activities WHERE lead_id = '{$id}')");
        $this->conn->query("DELETE FROM lead_activities WHERE lead_id = '{$id}'");
        
        // Finally delete the lead
        $delete = $this->conn->query("DELETE FROM leads WHERE id = '{$id}'");
        
        if($delete){
            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = "Lead successfully deleted.";
            $this->settings->set_flashdata('success', "Lead successfully deleted.");
        } else {
            throw new Exception("Failed to delete lead");
        }
        
    } catch (Exception $e) {
        $this->conn->rollback();
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: '.$e->getMessage();
        error_log("Delete lead error: " . $e->getMessage());
    }
    
    return json_encode($resp);
}
function delete_activity(){
    try {
        extract($_POST);
        
        if(!isset($id)) {
            throw new Exception("Activity ID is required");
        }
        
        $this->conn->begin_transaction();
        
        // First get and delete any associated documents
        $docs = $this->conn->query("SELECT file_path FROM lead_documents WHERE activity_id = '{$id}'");
        while($row = $docs->fetch_assoc()){
            if(is_file('../' . $row['file_path'])){
                unlink('../' . $row['file_path']);
            }
        }
        
        // Delete document records
        $this->conn->query("DELETE FROM lead_documents WHERE activity_id = '{$id}'");
        
        // Delete the activity
        $delete = $this->conn->query("DELETE FROM lead_activities WHERE id = '{$id}'");
        
        if($delete){
            $this->conn->commit();
            $this->settings->set_flashdata('success', "Activity successfully deleted.");
            $resp['status'] = 'success';
        } else {
            throw new Exception("Failed to delete activity");
        }
        
    } catch (Exception $e) {
        $this->conn->rollback();
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: '.$e->getMessage();
        error_log("Delete activity error: " . $e->getMessage());
    }
    
    return json_encode($resp);
    }
    function repeat_po(){
        extract($_POST);
        
        $this->conn->begin_transaction();
        
        try {
            $po_qry = $this->conn->query("SELECT * FROM purchase_order_list WHERE id = '$id'");
            if(!$po_qry) {
                throw new Exception("Error getting PO data");
            }
            
            $po_data = $po_qry->fetch_assoc();
            
            // Generate new PO code
            $old_code = $po_data['po_code'];
            $numeric_part = preg_replace('/[^0-9]/', '', $old_code);
            $text_part = preg_replace('/[0-9]/', '', $old_code);
            $new_numeric = intval($numeric_part) + 1;
            $new_po_code = $text_part . str_pad($new_numeric, strlen($numeric_part), '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO purchase_order_list (po_code, internal_ref_no, supplier_id, remarks, 
                    tax, cgst, sgst, sub_total, grand_total, tax_amount, cgst_amount, sgst_amount,
                    final_discounted_price, company, material_delivery, payment_terms, delivery_period, 
                    authorized_signatory) 
                    SELECT '$new_po_code', internal_ref_no, supplier_id, remarks, tax, cgst, sgst, 
                    sub_total, grand_total, tax_amount, cgst_amount, sgst_amount, final_discounted_price,
                    company, material_delivery, payment_terms, delivery_period, authorized_signatory 
                    FROM purchase_order_list WHERE id = '$id'";

            if(!$this->conn->query($sql)) {
                throw new Exception("Error creating new PO");
            }
            
            $new_po_id = $this->conn->insert_id;
            
            // Copy PO items
            $sql = "INSERT INTO po_items (po_id, item_id, amount, quantity, discount, total_amount)
                    SELECT '$new_po_id', item_id, amount, quantity, discount, total_amount 
                    FROM po_items WHERE po_id = '$id'";
                    
            if(!$this->conn->query($sql)) {
                throw new Exception("Error copying PO items");
            }
            
            $this->conn->commit();
            
            $resp['status'] = 'success';
            $resp['new_id'] = $new_po_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        
        return json_encode($resp);
    }

    function save_po_timeline() {
        try {
            extract($_POST);
            $this->conn->begin_transaction();

            // Check required fields 
            if(empty($po_id) || empty($step_name) || empty($step_date)) {
                throw new Exception("Required fields are missing");
            }

            // Get step category and order for ABG/PBG steps
            $step_category = null;
            $step_order = null;
            $bg_type = null;

            if(strpos($step_name, 'abg_') === 0) {
                $step_category = 'abg';
                $bg_type = 'abg';
                $step_order = match($step_name) {
                    'abg_format_approved' => 1,
                    'abg_sent' => 2,
                    'abg_payment' => 3,
                    default => null
                };
            } elseif(strpos($step_name, 'pbg_') === 0) {
                $step_category = 'pbg';
                $bg_type = 'pbg';
                $step_order = match($step_name) {
                    'pbg_format_approved' => 1,
                    'pbg_sent' => 2,
                    'pbg_payment' => 3,
                    default => null
                };
            }

            // Validate step order for ABG/PBG
            if($step_order > 1) {
                $prev_order = $step_order - 1;
                $check = $this->conn->query("SELECT id FROM po_timeline 
                    WHERE po_id = '{$po_id}' 
                    AND step_category = '{$step_category}' 
                    AND step_order = '{$prev_order}'");
                    
                if($check->num_rows == 0) {
                    throw new Exception("Please complete previous steps first");
                }
            }

            // Handle payment updates if payment amount is provided
            if(isset($payment_type) && isset($payment_amount) && $payment_amount > 0) {
                $column = "{$payment_type}_received";
                $current = $this->conn->query("SELECT {$column} FROM purchase_orders WHERE id = '{$po_id}'")->fetch_assoc()[$column] ?? 0;
                $new_amount = $current + $payment_amount;
                $update = $this->conn->query("UPDATE purchase_orders SET {$column} = '{$new_amount}' WHERE id = '{$po_id}'");
                if(!$update) {
                    throw new Exception("Failed to update payment record");
                }
                $payment_note = "Amount Received: ₹" . number_format($payment_amount, 2) . "\n\n";
                $remarks = $payment_note . ($remarks ?? '');
            }

            // Handle file uploads
            $uploaded_files = [];
            if(isset($_FILES['documents'])) {
                $upload_path = '../uploads/timeline_files/';
                if(!is_dir($upload_path)) mkdir($upload_path, 0777, true);

                foreach($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                    if($_FILES['documents']['error'][$key] == 0) {
                        $ext = pathinfo($_FILES['documents']['name'][$key], PATHINFO_EXTENSION);
                        $fname = strtotime(date('y-m-d H:i')).'_'.rand(0,999).'.'.$ext;
                        if(move_uploaded_file($tmp_name, $upload_path.$fname)) {
                            $uploaded_files[] = [
                                'path' => 'uploads/timeline_files/'.$fname,
                                'description' => $_POST['document_description'][$key] ?? ''
                            ];
                        }
                    }
                }
            }

            // Handle custom step name
            if(isset($custom_step_name) && !empty($custom_step_name)) {
                $step_name = $custom_step_name;
            }

            // UPDATE if id is set, otherwise INSERT
            if(isset($id) && !empty($id)) {
                $sql = "UPDATE po_timeline SET 
                        step_name = ?, 
                        step_category = ?, 
                        step_order = ?, 
                        bg_type = ?,
                        step_date = ?, 
                        remarks = ?
                        WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param('ssisssi', 
                    $step_name, 
                    $step_category, 
                    $step_order, 
                    $bg_type,
                    $step_date, 
                    $remarks, 
                    $id
                );
            } else {
                $sql = "INSERT INTO po_timeline (
                        po_id, step_name, step_category, step_order, bg_type,
                        step_date, remarks, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $created_by = $_SESSION['userdata']['id'];
                $stmt->bind_param('isssssss', 
                    $po_id, 
                    $step_name, 
                    $step_category, 
                    $step_order, 
                    $bg_type,
                    $step_date, 
                    $remarks, 
                    $created_by
                );
            }

            if(!$stmt->execute()) {
                throw new Exception("Failed to save timeline entry");
            }

            $timeline_id = isset($id) ? $id : $this->conn->insert_id;

            // Save uploaded files
            if(!empty($uploaded_files)) {
                $sql = "INSERT INTO po_timeline_files (timeline_id, file_path, description) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                foreach($uploaded_files as $file) {
                    $stmt->bind_param('iss', $timeline_id, $file['path'], $file['description']);
                    if(!$stmt->execute()) {
                        throw new Exception("Failed to save file record");
                    }
                }
            }

            $this->conn->commit();
            return json_encode(['status' => 'success']);

        } catch (Exception $e) {
            $this->conn->rollback();
            // Clean up any uploaded files
            if(isset($uploaded_files)){
                foreach($uploaded_files as $file) {
                    if(file_exists('../'.$file['path'])) {
                        unlink('../'.$file['path']);
                    }
                }
            }
            return json_encode([
                'status' => 'failed',
                'msg' => $e->getMessage()
            ]);
        }
    }
    
    function delete_po_timeline() {
        try {
            extract($_POST);
            $this->conn->begin_transaction();
            
            if(!isset($id)) {
                throw new Exception("Timeline ID is required");
            }
    
            // Get all files associated with this timeline entry before deletion
            $files_query = $this->conn->query("SELECT file_path FROM po_timeline_files WHERE timeline_id = '{$id}'");
            
            // Store file paths for deletion
            $files_to_delete = [];
            while($file = $files_query->fetch_assoc()) {
                if(!empty($file['file_path'])) {
                    $files_to_delete[] = $file['file_path'];
                }
            }
    
            // Delete files from po_timeline_files table first
            $delete_files = $this->conn->query("DELETE FROM po_timeline_files WHERE timeline_id = '{$id}'");
            
            // Delete the timeline entry
            $delete_timeline = $this->conn->query("DELETE FROM po_timeline WHERE id = '{$id}'");
            
            if($delete_timeline) {
                // Delete physical files from directory
                foreach($files_to_delete as $file_path) {
                    $full_path = '../' . $file_path;
                    if(file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
                
                $this->conn->commit();
                $this->settings->set_flashdata('success', "Timeline entry deleted successfully.");
                return json_encode(['status' => 'success']);
            }
    
            throw new Exception('Failed to delete timeline entry');
    
        } catch (Exception $e) {
            $this->conn->rollback();
            return json_encode([
                'status' => 'failed',
                'msg' => $e->getMessage()
            ]);
        }
    }

    function save_task()
    {
        try {
            extract($_POST);

            // Validate required fields
            if (empty($title) || empty($assigned_to) || empty($due_date) || empty($status) || empty($priority)) {
                throw new Exception("Please fill in all required fields");
            }

            // Build data string
            $data = "";
            foreach ($_POST as $k => $v) {
                if (!in_array($k, array('id', 'assigned_by'))) {
                    if (!empty($data)) $data .= ",";
                    $v = $this->conn->real_escape_string($v);
                    $data .= " `{$k}`='{$v}' ";
                }
            }

            // Check if this is an update and status has changed
            if (!empty($id)) {
                $old_status = $this->conn->query("SELECT status FROM tasks WHERE id = '{$id}'")->fetch_assoc()['status'];
                if ($old_status != $status) {
                    if (!empty($data)) $data .= ",";
                    $data .= " status_updated_at = CURRENT_TIMESTAMP ";
                }
            }

            // Add assigned_by for new tasks
            if (empty($id)) {
                $data .= ", `assigned_by`='{$_SESSION['userdata']['id']}'";
                $sql = "INSERT INTO `tasks` set {$data}";
            } else {
                $sql = "UPDATE `tasks` set {$data} where id = '{$id}'";
            }

            error_log("Task SQL Query: " . $sql); // Debug log

            $save = $this->conn->query($sql);
            if ($save) {
                // Get updated task count
                $user_id = $assigned_to;
                $count_query = $this->conn->query("SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = '{$user_id}' AND status != 'completed'");
                $count = $count_query->fetch_assoc()['count'];

                $resp['status'] = 'success';
                $resp['task_count'] = $count;
                if (empty($id))
                    $this->settings->set_flashdata('success', "New Task successfully saved.");
                else
                    $this->settings->set_flashdata('success', "Task successfully updated.");
            } else {
                throw new Exception("Database Error: " . $this->conn->error);
            }
        } catch (Exception $e) {
            error_log("Task Save Error: " . $e->getMessage());
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }

        return json_encode($resp);
    }
    
    function delete_task(){
        extract($_POST);
        
        // Get assigned_to before deleting
        $assigned_to = $this->conn->query("SELECT assigned_to FROM tasks WHERE id = '{$id}'")->fetch_assoc()['assigned_to'];
        
        $del = $this->conn->query("DELETE FROM `tasks` where id = '{$id}'");
        if($del){
            // Get updated count
            $count_query = $this->conn->query("SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = '{$assigned_to}' AND status != 'completed'");
            $count = $count_query->fetch_assoc()['count'];
            
            $resp['status'] = 'success';
            $resp['task_count'] = $count;
            $this->settings->set_flashdata('success',"Task successfully deleted.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    function get_task_count() {
        $user_id = $_SESSION['userdata']['id'];
        $qry = $this->conn->query("SELECT COUNT(*) as count FROM tasks 
            WHERE assigned_to = '{$user_id}' AND status != 'completed'");
        $row = $qry->fetch_assoc();
        return json_encode(['status' => 'success', 'count' => $row['count']]);
    }

    function save_stock_order(){
    // Set JSON header
    header('Content-Type: application/json');
    $resp = array('status' => 'failed', 'msg' => '');
    
    try {
        // Extract POST data
        extract($_POST);
        
        // Basic validation
        if(empty($channel) || empty($order_date) || empty($supplier_id) || empty($order_type)) {
            throw new Exception("Please fill all required fields");
        }

        // Set po_code to NULL for non-purchase_order channels
        $po_code = ($channel === 'purchase_order' && !empty($_POST['po_code'])) ? $_POST['po_code'] : null;

        // Start transaction
        $this->conn->begin_transaction();

        // Set timezone to India
        date_default_timezone_set('Asia/Kolkata');
        $current_time = date('Y-m-d H:i:s');

        if(empty($id)) {
            // Insert new stock order
            $stmt = $this->conn->prepare("INSERT INTO stock_orders (
                order_code, po_code, channel, order_date, supplier_id, 
                work_order_number, order_type, total_amount, negotiated_amount, 
                remarks, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if(!$stmt) {
                throw new Exception("Failed to prepare INSERT statement: " . $this->conn->error);
            }
            
            $created_by = $_SESSION['userdata']['id'];
            $total_amount = 0;
            $negotiated_amount = 0;
            
            $stmt->bind_param(
                "ssssissddsis",
                $order_code,      // s - string
                $po_code,         // s - string (can be null)
                $channel,         // s - string
                $order_date,      // s - string
                $supplier_id,     // i - integer
                $work_order_numbers, // s - string
                $order_type,      // s - string
                $total_amount,    // d - double
                $negotiated_amount, // d - double
                $remarks,         // s - string
                $created_by,      // i - integer
                $current_time     // s - string (current timestamp)
            );

        } else {
            // Update existing stock order
            $stmt = $this->conn->prepare("UPDATE stock_orders SET 
                order_code = ?, 
                po_code = ?,
                channel = ?, 
                order_date = ?, 
                supplier_id = ?, 
                work_order_number = ?,
                order_type = ?,
                total_amount = ?,
                negotiated_amount = ?,
                remarks = ?,
                updated_at = ?
                WHERE id = ?");
            
            if(!$stmt) {
                throw new Exception("Failed to prepare UPDATE statement: " . $this->conn->error);
            }
            
            $total_amount = 0;
            $negotiated_amount = 0;
            
            $stmt->bind_param(
                "ssssissddsssi",
                $order_code,      // s - string
                $po_code,         // s - string (can be null)
                $channel,         // s - string
                $order_date,      // s - string
                $supplier_id,     // i - integer
                $work_order_numbers, // s - string
                $order_type,      // s - string
                $total_amount,    // d - double
                $negotiated_amount, // d - double
                $remarks,         // s - string
                $current_time,    // s - string (current timestamp)
                $id              // i - integer
            );
        }

        if(!$stmt->execute()) {
            throw new Exception("Failed to save stock order: " . $stmt->error);
        }
        
        $order_id = empty($id) ? $this->conn->insert_id : $id;
        
        // Delete existing items if updating
        if(!empty($id)) {
            $this->conn->query("DELETE FROM stock_order_items WHERE order_id = '$order_id'");
        }
        
        // Save stock order items
        if(isset($_POST['item_id']) && is_array($_POST['item_id'])) {
            $total_amount = 0;
            $negotiated_amount = 0;
            
            foreach($_POST['item_id'] as $index => $item_id) {
                $quantity = $_POST['quantity'][$index];
                $unit = $_POST['unit'][$index];
                $unit_price = $_POST['unit_price'][$index];
                $total_price = $_POST['total_price'][$index];
                $negotiated_total = $_POST['negotiated_total'][$index];
                $item_remarks = $_POST['item_remarks'][$index];
                
                $stmt = $this->conn->prepare("INSERT INTO stock_order_items (
                    order_id, item_id, quantity, unit, unit_price, 
                    total_price, negotiated_total, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param(
                    "iissddds",
                    $order_id,        // i - integer
                    $item_id,         // i - integer
                    $quantity,        // s - string
                    $unit,           // s - string
                    $unit_price,     // d - double
                    $total_price,    // d - double
                    $negotiated_total, // d - double
                    $item_remarks    // s - string
                );
                
                if(!$stmt->execute()) {
                    throw new Exception("Failed to save stock order item: " . $stmt->error);
                }
                
                $total_amount += $total_price;
                $negotiated_amount += $negotiated_total;
            }
            
            // Update order totals
            $stmt = $this->conn->prepare("UPDATE stock_orders SET 
                total_amount = ?, negotiated_amount = ? 
                WHERE id = ?");
            
            $stmt->bind_param("ddi", $total_amount, $negotiated_amount, $order_id);
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to update order totals: " . $stmt->error);
            }
        }

        $this->conn->commit();
        
        // Set success message in session
        $this->settings->set_flashdata('success', empty($id) ? 'Stock order successfully created.' : 'Stock order successfully updated.');

        // Set success response
        $resp['status'] = 'success';
        $resp['msg'] = empty($id) ? 'Stock order successfully created.' : 'Stock order successfully updated.';
        $resp['id'] = $order_id;
        $resp['redirect'] = base_url . 'admin/?page=stock_orders/view_order&id=' . $order_id;

    } catch (Exception $e) {
        $this->conn->rollback();
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
        error_log("Stock order save error: " . $e->getMessage());
    }
    
    return json_encode($resp);
}
function delete_stock_order(){
    extract($_POST);
    $delete = $this->conn->query("DELETE FROM `stock_orders` where id = '{$id}'");
    if($delete){
        $this->capture_err();
        $resp['status'] = 'success';
        $resp['msg'] = 'Stock order successfully deleted.';
        $this->settings->set_flashdata('success', 'Stock order successfully deleted.');
    }else{
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred. Error: '.$this->conn->error;
    }
    return json_encode($resp);
}

    function save_purchase_order_timeline()
    {
        try {
            extract($_POST);
            $this->conn->begin_transaction();

            // Validate required fields
            if (empty($po_id) || empty($step_name) || empty($step_date)) {
                throw new Exception("Required fields are missing");
            }

            // Handle file uploads
            $uploaded_files = [];
            if (isset($_FILES['documents'])) {
                $upload_path = '../uploads/purchase_order_timeline/';
                if (!is_dir($upload_path)) {
                    if (!mkdir($upload_path, 0777, true)) {
                        throw new Exception("Failed to create upload directory");
                    }
                }
                foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['documents']['error'][$key] == 0) {
                        $file_ext = pathinfo($_FILES['documents']['name'][$key], PATHINFO_EXTENSION);
                        $file_name = 'purchase_timeline_' . time() . '_' . $key . '.' . $file_ext;
                        $file_path = 'uploads/purchase_order_timeline/' . $file_name;
                        $full_path = $upload_path . $file_name;
                        if (move_uploaded_file($tmp_name, $full_path)) {
                            $doc_desc = isset($_POST['document_description'][$key]) ? $_POST['document_description'][$key] : '';
                            $uploaded_files[] = [
                                'path' => $file_path,
                                'description' => $doc_desc
                            ];
                        } else {
                            throw new Exception("Failed to upload file: " . $_FILES['documents']['name'][$key]);
                        }
                    }
                }
            }

            // UPDATE if timeline_id is set, otherwise INSERT
            if (isset($timeline_id) && !empty($timeline_id)) {
                $sql = "UPDATE purchase_order_timeline SET 
                        step_name = ?, 
                        step_date = ?, 
                        remarks = ?
                    WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param(
                    'sssi',
                    $step_name,
                    $step_date,
                    $remarks,
                    $timeline_id
                );
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update timeline entry");
                }
                $current_timeline_id = $timeline_id;
            } else {
                $sql = "INSERT INTO purchase_order_timeline (
                        po_id, step_name, step_date, remarks, created_by
                    ) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $created_by = $_SESSION['userdata']['id'];
                $stmt->bind_param(
                    'isssi',
                    $po_id,
                    $step_name,
                    $step_date,
                    $remarks,
                    $created_by
                );
                if (!$stmt->execute()) {
                    throw new Exception("Failed to save timeline entry");
                }
                $current_timeline_id = $this->conn->insert_id;
            }

            // Save uploaded files
            if (!empty($uploaded_files)) {
                $sql = "INSERT INTO purchase_order_timeline_files (timeline_id, file_path, description) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                foreach ($uploaded_files as $file) {
                    $stmt->bind_param('iss', $current_timeline_id, $file['path'], $file['description']);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to save file record");
                    }
                }
            }

            $this->conn->commit();
            $this->settings->set_flashdata('success', "Entry saved successfully.");
            return json_encode(['status' => 'success']);
        } catch (Exception $e) {
            error_log("Purchase Order Timeline Error: " . $e->getMessage());
            $this->conn->rollback();
            // Clean up any uploaded files
            if (isset($uploaded_files)) {
                foreach ($uploaded_files as $file) {
                    if (file_exists('../' . $file['path'])) {
                        unlink('../' . $file['path']);
                    }
                }
            }
            return json_encode([
                'status' => 'failed',
                'msg' => $e->getMessage()
            ]);
        }
    }

    function delete_purchase_order_timeline()
    {
        try {
            extract($_POST);
            $this->conn->begin_transaction();

            if (!isset($id)) {
                throw new Exception("Timeline ID is required");
            }

            // Get all files associated with this timeline entry before deletion
            $files_query = $this->conn->query("SELECT file_path FROM purchase_order_timeline_files WHERE timeline_id = '{$id}'");
            $files_to_delete = [];
            while ($file = $files_query->fetch_assoc()) {
                if (!empty($file['file_path'])) {
                    $files_to_delete[] = $file['file_path'];
                }
            }

            // Delete files from DB
            $this->conn->query("DELETE FROM purchase_order_timeline_files WHERE timeline_id = '{$id}'");

            // Delete the timeline entry
            $delete_timeline = $this->conn->query("DELETE FROM purchase_order_timeline WHERE id = '{$id}'");

            if ($delete_timeline) {
                // Delete physical files from directory
                foreach ($files_to_delete as $file_path) {
                    $full_path = '../' . $file_path;
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
                $this->conn->commit();
                $this->settings->set_flashdata('success', "Timeline entry deleted successfully.");
                return json_encode(['status' => 'success']);
            }

            throw new Exception('Failed to delete timeline entry');
        } catch (Exception $e) {
            $this->conn->rollback();
            return json_encode([
                'status' => 'failed',
                'msg' => $e->getMessage()
            ]);
        }
    }

    function save_quotation()
    {
        extract($_POST);
        $resp = array();

        try {
            $this->conn->begin_transaction();

            // Determine if this is an insert or update
            if (empty($id)) {
                // New Quotation
                if (!empty($_POST['quotation_no'])) {
                    $quotation_code = $this->conn->real_escape_string($_POST['quotation_no']);
                } else {
                    $prefix = "QT";
                    $code = sprintf("%'.04d", 1);
                    while (true) {
                        $check_code = $this->conn->query("SELECT id FROM quotations WHERE quotation_code = '{$prefix}-{$code}'")->num_rows;
                        if ($check_code > 0) {
                            $code = sprintf("%'.04d", intval($code) + 1);
                        } else {
                            break;
                        }
                    }
                    $quotation_code = "{$prefix}-{$code}";
                }

                $sql = "INSERT INTO quotations (lead_id, quotation_code, created_by, created_at) VALUES (?, ?, ?, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("isi", $lead_id, $quotation_code, $_SESSION['userdata']['id']);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create quotation: " . $stmt->error);
                }
                $quotation_id = $this->conn->insert_id;
            } else {
                // Update Existing Quotation
                $quotation_id = $id;
                $quotation_code = $this->conn->real_escape_string($quotation_no);
                $sql = "UPDATE quotations SET quotation_code = ?, lead_id = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("sii", $quotation_code, $lead_id, $quotation_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update quotation: " . $stmt->error);
                }

                // Clear old items for this quotation
                $this->conn->query("DELETE FROM quotation_item_prices WHERE quotation_item_id IN (SELECT id FROM quotation_items WHERE quotation_id = '{$quotation_id}')");
                $this->conn->query("DELETE FROM quotation_item_accessories WHERE quotation_item_id IN (SELECT id FROM quotation_items WHERE quotation_id = '{$quotation_id}')");
                $this->conn->query("DELETE FROM quotation_items WHERE quotation_id = '{$quotation_id}'");
            }

            // Save selected machines and their details
            if (isset($selected_machines) && is_array($selected_machines)) {
                foreach ($selected_machines as $machine_id) {
                    $sql = "INSERT INTO quotation_items (quotation_id, quote_item_id) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param("ii", $quotation_id, $machine_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to save machine selection: " . $stmt->error);
                    }
                    $quotation_item_id = $this->conn->insert_id;

                    if (isset($prices) && isset($prices[$machine_id]) && is_array($prices[$machine_id])) {
                        foreach ($prices[$machine_id] as $price_id) {
                            $sql = "INSERT INTO quotation_item_prices (quotation_item_id, price_id) VALUES (?, ?)";
                            $stmt = $this->conn->prepare($sql);
                            $stmt->bind_param("ii", $quotation_item_id, $price_id);
                            if (!$stmt->execute()) throw new Exception("Failed to save price: " . $stmt->error);
                        }
                    }

                    if (isset($accessories) && isset($accessories[$machine_id]) && is_array($accessories[$machine_id])) {
                        foreach ($accessories[$machine_id] as $accessory_id) {
                            $sql = "INSERT INTO quotation_item_accessories (quotation_item_id, accessory_id) VALUES (?, ?)";
                            $stmt = $this->conn->prepare($sql);
                            $stmt->bind_param("ii", $quotation_item_id, $accessory_id);
                            if (!$stmt->execute()) throw new Exception("Failed to save accessory: " . $stmt->error);
                        }
                    }
                }
            }

            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['quotation_id'] = $quotation_id;
            $flash_msg = empty($id) ? "Quotation generated successfully." : "Quotation updated successfully.";
            $this->settings->set_flashdata('success', $flash_msg);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Quotation Error: " . $e->getMessage());
        }

        return json_encode($resp);
    }

    function delete_quotation()
    {
        extract($_POST);

        try {
            $this->conn->begin_transaction();

            // Delete related records first
            $this->conn->query("DELETE FROM quotation_item_prices WHERE quotation_item_id IN (SELECT id FROM quotation_items WHERE quotation_id = '{$id}')");
            $this->conn->query("DELETE FROM quotation_item_accessories WHERE quotation_item_id IN (SELECT id FROM quotation_items WHERE quotation_id = '{$id}')");
            $this->conn->query("DELETE FROM quotation_items WHERE quotation_id = '{$id}'");
            $this->conn->query("DELETE FROM quotations WHERE id = '{$id}'");

            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Quotation deleted successfully.");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }

        return json_encode($resp);
    }

    function save_quote_item()
    {
        try {
            extract($_POST);
            $this->conn->begin_transaction();

            // Basic item data
            $data = "name = '" . $this->conn->real_escape_string($name) . "', 
                 description = '" . $this->conn->real_escape_string($description) . "'";

            // Insert or Update main item data
            if (empty($id)) {
                $sql = "INSERT INTO quote_items SET {$data}";
                $save = $this->conn->query($sql);
                $item_id = $this->conn->insert_id;
            } else {
                $sql = "UPDATE quote_items SET {$data} WHERE id = '{$id}'";
                $save = $this->conn->query($sql);
                $item_id = $id;
            }

            if (!$save) throw new Exception("Failed to save item data");

            // Handle image uploads with descriptions
            if (isset($_FILES['item_images'])) {
                $upload_path = '../uploads/quote_items/';
                if (!is_dir($upload_path)) mkdir($upload_path, 0777, true);

                foreach ($_FILES['item_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['item_images']['error'][$key] == 0) {
                        $ext = pathinfo($_FILES['item_images']['name'][$key], PATHINFO_EXTENSION);
                        $fname = 'item_' . time() . '_' . $key . '.' . $ext;
                        $image_path = 'uploads/quote_items/' . $fname;

                        if (move_uploaded_file($tmp_name, $upload_path . $fname)) {
                            $description = isset($_POST['image_descriptions'][$key]) ?
                                $this->conn->real_escape_string($_POST['image_descriptions'][$key]) : '';

                            $img_sql = "INSERT INTO quote_item_images (quote_item_id, image_path, description) 
                                  VALUES ('{$item_id}', '{$image_path}', '{$description}')";
                            if (!$this->conn->query($img_sql)) {
                                throw new Exception("Failed to save image record");
                            }
                        }
                    }
                }
            }

            // Save Technical Specifications
            if (isset($_POST['attr_name'])) {
                if (!empty($id)) {
                    $this->conn->query("DELETE FROM quote_item_attributes WHERE quote_item_id = $item_id");
                }
                $attr_stmt = $this->conn->prepare("INSERT INTO quote_item_attributes (quote_item_id, attribute_name, attribute_value) VALUES (?, ?, ?)");
                foreach ($_POST['attr_name'] as $key => $attr_name) {
                    if (empty($attr_name)) continue;
                    $attr_value = $_POST['attr_value'][$key];
                    $attr_stmt->bind_param("iss", $item_id, $attr_name, $attr_value);
                    $attr_stmt->execute();
                }
            }

            // Save Pricing Details
            if (isset($_POST['price_desc'])) {
                if (!empty($id)) {
                    $this->conn->query("DELETE FROM quote_item_prices WHERE quote_item_id = $item_id");
                }
                $price_stmt = $this->conn->prepare("INSERT INTO quote_item_prices (quote_item_id, description, price) VALUES (?, ?, ?)");
                foreach ($_POST['price_desc'] as $key => $price_desc) {
                    if (empty($price_desc)) continue;
                    $price = floatval($_POST['price_amount'][$key]);
                    $price_stmt->bind_param("isd", $item_id, $price_desc, $price);
                    $price_stmt->execute();
                }
            }

            // Save Accessories
            if (isset($_POST['acc_name'])) {
                if (!empty($id)) {
                    $this->conn->query("DELETE FROM quote_item_accessories WHERE quote_item_id = $item_id");
                }
                $acc_stmt = $this->conn->prepare("INSERT INTO quote_item_accessories (quote_item_id, name, price) VALUES (?, ?, ?)");
                foreach ($_POST['acc_name'] as $key => $acc_name) {
                    if (empty($acc_name)) continue;
                    $acc_price = floatval($_POST['acc_price'][$key]);
                    $acc_stmt->bind_param("isd", $item_id, $acc_name, $acc_price);
                    $acc_stmt->execute();
                }
            }

            $this->conn->commit();
            $resp['status'] = 'success';
            $resp['msg'] = empty($id) ? "Quote item added successfully!" : "Quote item updated successfully!";
            $resp['redirect'] = './?page=quote_items';
            $this->settings->set_flashdata('success', $resp['msg']);
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Save quote item error: " . $e->getMessage());
        }

        return json_encode($resp);
    }
    function delete_quote_item()
    {
        try {
            extract($_POST);
            $this->conn->begin_transaction();

            // Fetch all image paths for this item
            $images = $this->conn->query("SELECT image_path FROM quote_item_images WHERE quote_item_id = '{$id}'");
            while ($img = $images->fetch_assoc()) {
                $file_path = '../' . $img['image_path'];
                if (file_exists($file_path)) {
                    @unlink($file_path);
                }
            }

            // Delete all associated records
            $tables = [
                'quote_item_images',
                'quote_item_attributes',
                'quote_item_prices',
                'quote_item_accessories'
            ];
            foreach ($tables as $table) {
                $this->conn->query("DELETE FROM {$table} WHERE quote_item_id = '{$id}'");
            }

            // Delete the main item
            $this->conn->query("DELETE FROM quote_items WHERE id = '{$id}'");

            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Item deleted successfully.");
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['error'] = $e->getMessage();
            error_log("Delete quote item error: " . $e->getMessage());
        }

        return json_encode($resp);
    }

    function delete_quote_image()
    {
        try {
            extract($_POST);
            $img = $this->conn->query("SELECT * FROM quote_item_images WHERE id = '{$id}'")->fetch_assoc();
            if (!$img) throw new Exception('Image not found');
            $file_path = '../' . $img['image_path'];
            if (file_exists($file_path)) @unlink($file_path);
            $delete = $this->conn->query("DELETE FROM quote_item_images WHERE id = '{$id}'");
            if (!$delete) throw new Exception("Failed to delete image record");
            $resp['status'] = 'success';
            $resp['msg'] = 'Image deleted successfully';
        } catch (Exception $e) {
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

function save_utility_supplier(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k,array('id'))){
            if(!empty($data)) $data .= ",";
            $data .= " `{$k}`='{$v}' ";
        }
    }
    if(empty($id)){
        $sql = "INSERT INTO `utility_supplier_list` set {$data}";
    }else{
        $sql = "UPDATE `utility_supplier_list` set {$data} where id = '{$id}'";
    }
    $save = $this->conn->query($sql);
    if($save){
        $resp['status'] = 'success';
        if(empty($id))
            $this->settings->set_flashdata('success',"New Utility Supplier successfully saved.");
        else
            $this->settings->set_flashdata('success',"Utility Supplier successfully updated.");
    }else{
        $resp['status'] = 'failed';
        $resp['err'] = $this->conn->error."[{$sql}]";
    }
    return json_encode($resp);
}

function delete_utility_supplier(){
    extract($_POST);
    $del = $this->conn->query("DELETE FROM `utility_supplier_list` where id = '{$id}'");
    if($del){
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success',"Utility Supplier successfully deleted.");
    }else{
        $resp['status'] = 'failed';
        $resp['error'] = $this->conn->error;
    }
    return json_encode($resp);
}

    function bulk_delete_tasks()
    {
        try {
            extract($_POST);

            if (!isset($task_ids) || !is_array($task_ids)) {
                throw new Exception("No tasks selected");
            }

            $this->conn->begin_transaction();

            // Sanitize task IDs
            $task_ids = array_map('intval', $task_ids);
            $ids = implode(',', $task_ids);

            // Only delete completed tasks
            $sql = "DELETE FROM daily_tasks 
                WHERE id IN ($ids) 
                AND completed = 1 
                AND user_id = '{$_SESSION['userdata']['id']}'";

            $delete = $this->conn->query($sql);

            if ($delete) {
                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = "Selected tasks successfully deleted";
                $this->settings->set_flashdata('success', "Selected tasks successfully deleted.");
            } else {
                throw new Exception($this->conn->error);
            }
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
            error_log("Bulk delete tasks error: " . $e->getMessage());
        }

        return json_encode($resp);
    }
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_supplier':
		echo $Master->save_supplier();
	break;
	case 'delete_supplier':
		echo $Master->delete_supplier();
	break;
	case 'save_item':
		echo $Master->save_item();
	break;
	case 'delete_item':
		echo $Master->delete_item();
	break;
	case 'save_machine_item':
		echo $Master->save_machine_item();
	break;
	case 'delete_machine_item':
		echo $Master->delete_machine_item();
	break;
	case 'save_po':
		echo $Master->save_po();
	break;
	case 'delete_po':
		echo $Master->delete_po();
	break;
    case 'save_client':
        echo $Master->save_client();
    break;
    case 'delete_client':
        echo $Master->delete_client();
    break;
	case 'save_pi':
        echo $Master->save_pi();
    break;
	case 'delete_pi':
        echo $Master->delete_pi();
    break;
	case 'save_po_details':
    echo $Master->save_po_details();
    break;
    case 'delete_po_details':
    echo $Master->delete_po_details();
    break;    
    case 'complete_project':
        echo $Master->complete_project();
    break;
    case 'verify_requirements':
        echo $Master->verify_requirements();
    break;
    case 'save_lead':
            echo $Master->save_lead();
    break;
    case 'delete_lead':
        echo $Master->delete_lead();
    break;
    case 'log_activity':
        echo $Master->log_activity();
    break;
    case 'delete_activity':
        echo $Master->delete_activity();
    break;
    case 'repeat_po':
        echo $Master->repeat_po();
    break;
    case 'save_po_timeline':
        echo $Master->save_po_timeline();
    break;
    case 'delete_po_timeline':
        echo $Master->delete_po_timeline();
    break;
    case 'save_task':
        echo $Master->save_task();
    break;
    case 'delete_task':
        echo $Master->delete_task();
    break;
    case 'get_task_count':
        echo $Master->get_task_count();
    break;
    case 'save_quote_item':
        echo $Master->save_quote_item();
    break;
    case 'delete_quote_item':
        echo $Master->delete_quote_item();
    break;
    case 'delete_quote_image':
        echo $Master->delete_quote_image();
    break;
    case 'save_quotation':
        echo $Master->save_quotation();
    break;
    case 'delete_quotation':
        echo $Master->delete_quotation();
    break;
    case 'save_purchase_order_timeline':
        echo $Master->save_purchase_order_timeline();
    break;
    case 'delete_purchase_order_timeline':
        echo $Master->delete_purchase_order_timeline();
    break;
    case 'save_stock_order':
        echo $Master->save_stock_order();
    break;
    case 'delete_stock_order':
        echo $Master->delete_stock_order();
    break;
    case 'save_utility_supplier':   
        echo $Master->save_utility_supplier();
    break;
    case 'delete_utility_supplier':
        echo $Master->delete_utility_supplier();
    break;
    case 'bulk_delete_tasks':
        echo $Master->bulk_delete_tasks();
    break;
	default:
		// echo $sysset->index();
	break;
}