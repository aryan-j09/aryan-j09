<?php
header('Content-Type: application/json');

require_once('../config.php');
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

    function save_receiving(){
    extract($_POST);
    
    // Begin transaction
    $this->conn->begin_transaction();
    
    try {
        $data = " po_id='{$po_id}', received_by='{$received_by}' ";
        
        // Insert into receiving_list
        if(empty($id)){
            $sql = "INSERT INTO receiving_list set {$data}";
        }else{
            $sql = "UPDATE receiving_list set {$data} where id = '{$id}'";
        }
        
        $save = $this->conn->query($sql);
        if(!$save) throw new Exception($this->conn->error);
        
        $receiving_id = empty($id) ? $this->conn->insert_id : $id;
        
        // Process received items
        if(isset($_POST['received_qty'])){
            foreach($_POST['received_qty'] as $item_id => $qty){
                if($qty > 0){
                    // Insert into receiving_list_items
                    $sql = "INSERT INTO receiving_list_items 
                           (receiving_id, item_id, quantity) 
                           VALUES ('{$receiving_id}', '{$item_id}', '{$qty}')";
                    
                    if(!$this->conn->query($sql)){
                        throw new Exception($this->conn->error);
                    }
                    
                    // Update po_items received quantity
                    $sql = "UPDATE po_items SET 
                            received_qty = COALESCE(received_qty, 0) + {$qty}
                            WHERE po_id = '{$po_id}' 
                            AND item_id = '{$item_id}'";
                            
                    if(!$this->conn->query($sql)){
                        throw new Exception($this->conn->error);
                    }
                }
            }
        }
        
        $this->conn->commit();
        return json_encode(['status' => 'success', 'msg' => 'Receiving record successfully saved']);
        
    } catch (Exception $e) {
        $this->conn->rollback();
        return json_encode(['status' => 'failed', 'msg' => $e->getMessage()]);
    }
}

    function delete_receiving(){
        extract($_POST);
        
        if(!isset($receiving_id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Missing receiving ID";
            return json_encode($resp);
        }
        
        try {
            $this->conn->begin_transaction();

            // Delete receiving items first
            $this->conn->query("DELETE FROM receiving_list_items WHERE receiving_id = '{$receiving_id}'");

            // Delete the receiving record
            $delete = $this->conn->query("DELETE FROM receiving_list WHERE id = '{$receiving_id}'");
            
            if($delete && isset($po_id)) {
                // Recalculate PO items received quantities
                $update_qty = $this->conn->query("
                    UPDATE po_items pi 
                    LEFT JOIN (
                        SELECT item_id, SUM(quantity) as total_received 
                        FROM receiving_list_items rli
                        JOIN receiving_list rl ON rli.receiving_id = rl.id
                        WHERE rl.po_id = '{$po_id}'
                        GROUP BY item_id
                    ) r ON pi.item_id = r.item_id 
                    SET pi.received_qty = COALESCE(r.total_received, 0)
                    WHERE pi.po_id = '{$po_id}'"
                );

                // Update PO status
                $status_query = $this->conn->query("SELECT 
                    (CASE 
                        WHEN SUM(COALESCE(received_qty, 0)) = 0 THEN 'pending'
                        WHEN SUM(COALESCE(received_qty, 0)) = SUM(quantity) THEN 'received'
                        ELSE 'partially_received'
                    END) as new_status
                    FROM po_items 
                    WHERE po_id = '{$po_id}'");
                
                if($status_row = $status_query->fetch_assoc()) {
                    $this->conn->query("UPDATE purchase_order_list 
                                      SET status = '{$status_row['new_status']}' 
                                      WHERE id = '{$po_id}'");
                }
                
                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = "Receiving record successfully deleted";
            } else {
                throw new Exception("Failed to delete receiving record");
            }
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        
        return json_encode($resp);
    }
	function delete_bo(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `back_order_list` where id = '{$id}'");
        if($del){
            $this->conn->query("DELETE FROM `bo_items` where bo_id = '{$id}'");
            $resp['status'] = 'success';
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function save_return(){
        if(empty($_POST['id'])){
            $prefix = "R";
            $code = sprintf("%'.04d",1);
            while(true){
                $check_code = $this->conn->query("SELECT * FROM `return_list` where return_code ='".$prefix.'-'.$code."' ")->num_rows;
                if($check_code > 0){
                    $code = sprintf("%'.04d",$code+1);
                }else{
                    break;
                }
            }
            $_POST['return_code'] = $prefix."-".$code;
        }
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id')) && !is_array($_POST[$k])){
                if(!is_numeric($v))
                $v= $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=", ";
                $data .=" `{$k}` = '{$v}' ";
            }
        }
        if(empty($id)){
            $sql = "INSERT INTO `return_list` set {$data}";
        }else{
            $sql = "UPDATE `return_list` set {$data} where id = '{$id}'";
        }
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
            $return_id = $this->conn->insert_id;
            else
            $return_id = $id;
            $resp['id'] = $return_id;
            $data = "";
            $sids = array();
            $get = $this->conn->query("SELECT * FROM `return_list` where id = '{$return_id}'");
            if($get->num_rows > 0){
                $res = $get->fetch_array();
                if(!empty($res['stock_ids'])){
                    $this->conn->query("DELETE FROM `stock_list` where id in ({$res['stock_ids']}) ");
                }
            }
            foreach($item_id as $k =>$v){
                $sql = "INSERT INTO `stock_list` set item_id='{$v}', `quantity` = '{$qty[$k]}', `unit` = '{$unit[$k]}', `price` = '{$price[$k]}', `total` = '{$total[$k]}', `type` = 2 ";
                $save = $this->conn->query($sql);
                if($save){
                    $sids[] = $this->conn->insert_id;
                }
            }
            $sids = implode(',',$sids);
            $this->conn->query("UPDATE `return_list` set stock_ids = '{$sids}' where id = '{$return_id}'");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = 'An error occured. Error: '.$this->conn->error;
        }
        if($resp['status'] == 'success'){
            if(empty($id)){
                $this->settings->set_flashdata('success'," New Returned Item Record was Successfully created.");
            }else{
                $this->settings->set_flashdata('success'," Returned Item Record's Successfully updated.");
            }
        }

        return json_encode($resp);
    }

    function delete_return(){
        extract($_POST);
        $get = $this->conn->query("SELECT * FROM return_list where id = '{$id}'");
        if($get->num_rows > 0){
            $res = $get->fetch_array();
        }
        $del = $this->conn->query("DELETE FROM `return_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Returned Item Record's Successfully deleted.");
            if(isset($res)){
                $this->conn->query("DELETE FROM `stock_list` where id in ({$res['stock_ids']})");
            }
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
	function save_sale(){
        if(empty($_POST['id'])){
            $prefix = "SALE";
            $code = sprintf("%'.04d",1);
            while(true){
                $check_code = $this->conn->query("SELECT * FROM `sales_list` where sales_code ='".$prefix.'-'.$code."' ")->num_rows;
                if($check_code > 0){
                    $code = sprintf("%'.04d",$code+1);
                }else{
                    break;
                }
            }
            $_POST['sales_code'] = $prefix."-".$code;
        }
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id')) && !is_array($_POST[$k])){
                if(!is_numeric($v))
                $v= $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=", ";
                $data .=" `{$k}` = '{$v}' ";
            }
        }
        if(empty($id)){
            $sql = "INSERT INTO `sales_list` set {$data}";
        }else{
            $sql = "UPDATE `sales_list` set {$data} where id = '{$id}'";
        }
        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
            $sale_id = $this->conn->insert_id;
            else
            $sale_id = $id;
            $resp['id'] = $sale_id;
            $data = "";
            $sids = array();
            $get = $this->conn->query("SELECT * FROM `sales_list` where id = '{$sale_id}'");
            if($get->num_rows > 0){
                $res = $get->fetch_array();
                if(!empty($res['stock_ids'])){
                    $this->conn->query("DELETE FROM `stock_list` where id in ({$res['stock_ids']}) ");
                }
            }
            foreach($item_id as $k =>$v){
                $sql = "INSERT INTO `stock_list` set item_id='{$v}', `quantity` = '{$qty[$k]}', `unit` = '{$unit[$k]}', `price` = '{$price[$k]}', `total` = '{$total[$k]}', `type` = 2 ";
                $save = $this->conn->query($sql);
                if($save){
                    $sids[] = $this->conn->insert_id;
                }
            }
            $sids = implode(',',$sids);
            $this->conn->query("UPDATE `sales_list` set stock_ids = '{$sids}' where id = '{$sale_id}'");
        }else{
            $resp['status'] = 'failed';
            $resp['msg'] = 'An error occured. Error: '.$this->conn->error;
        }
        if($resp['status'] == 'success'){
            if(empty($id)){
                $this->settings->set_flashdata('success'," New Sales Record was Successfully created.");
            }else{
                $this->settings->set_flashdata('success'," Sales Record's Successfully updated.");
            }
        }

        return json_encode($resp);
    }

    function delete_sale(){
        extract($_POST);
        $get = $this->conn->query("SELECT * FROM sales_list where id = '{$id}'");
        if($get->num_rows > 0){
            $res = $get->fetch_array();
        }
        $del = $this->conn->query("DELETE FROM `sales_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Sales Record's Successfully deleted.");
            if(isset($res)){
                $this->conn->query("DELETE FROM `stock_list` where id in ({$res['stock_ids']})");
            }
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

	function save_client(){
    extract($_POST);
    $data = "";

    // Only company_name, gst_number and billing_address are required
    $required = array(
        'company_name',        
        'billing_address'
    );

    foreach($required as $field){
        if(!isset($_POST[$field]) || empty($_POST[$field])){
            $resp['status'] = 'failed';
            $resp['msg'] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            return json_encode($resp);
        }
    }

    // Phone number validation only if provided
    $phone_fields = array('contact_no', 'cperson_no_acc', 'cperson_no_pur');
    foreach($phone_fields as $field){
        if(!empty($_POST[$field]) && !preg_match('/^\d{10}$/', $_POST[$field])){
            $resp['status'] = 'failed';
            $resp['msg'] = 'Please enter valid 10-digit phone numbers.';
            return json_encode($resp);
        }
    }

    foreach($_POST as $k => $v){
        if(!in_array($k, array('id'))){
            $v = $this->conn->real_escape_string($v);
            if(!empty($data)) $data .= ", ";
            $data .= "`{$k}`='{$v}'";
        }
    }

    if(empty($id)){
        $sql = "INSERT INTO clients SET {$data}";
    }else{
        $sql = "UPDATE clients SET {$data} WHERE id = '{$id}'";
    }

    $save = $this->conn->query($sql);
    if($save){
        // Handle lead conversion if applicable
        if(isset($_POST['converted_from_lead'])) {
            $lead_id = $this->conn->real_escape_string($_POST['converted_from_lead']);
            
            // Update lead status to converted
            $this->conn->query("UPDATE leads SET status = 'converted' WHERE id = '{$lead_id}'");
            
            // Log the conversion activity
            $activity_sql = "INSERT INTO lead_activities SET 
                            lead_id = '{$lead_id}',
                            activity_type = 'status_change',
                            description = 'Lead converted to client',
                            created_by = '{$_SESSION['userdata']['id']}'";
            $this->conn->query($activity_sql);
        }
        
        $resp['status'] = 'success';
        if(empty($id)){
            $resp['msg'] = "New client successfully saved.";
        } else {
            $resp['msg'] = "Client successfully updated.";
        }
    }else{
        throw new Exception($this->conn->error);
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
        
        // Add ABG/PBG handling here
        $abg_required = isset($_POST['abg_required']) ? 1 : 0;
        $pbg_required = isset($_POST['pbg_required']) ? 1 : 0;
        
        // Debug
        error_log("POST data: " . print_r($_POST, true));
        
        // ...rest of existing save_pi code...
        
        // Make sure these are included in your database query
        $data .= ", `abg_required` = '{$abg_required}'";
        $data .= ", `pbg_required` = '{$pbg_required}'";
        
        // ...rest of existing save_pi code...
        
        // Debug
        error_log("POST data: " . print_r($_POST, true));
        
        foreach ($_POST as $k => $v) {
            if (!in_array($k, array('id', 'description', 'amount', 'employee_passcode', 'admin_passcode')) && !is_array($_POST[$k])) {
                if (!is_numeric($v)) $v = $this->conn->real_escape_string($v);
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
                $data .= ", `pi_code` = '{$pi_code}'";
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
            $data .= ", `employee_approved` = '{$employee_approved}', `admin_approved` = '{$admin_approved}', `employee_approved_by` = '{$employee_approved_by}', `admin_approved_by` = '{$admin_approved_by}'";

            // Debug
            error_log("SQL data: " . $data);

            if (empty($id)) {
                $sql = "INSERT INTO `proforma_invoice_list` SET {$data}";
            } else {
                $sql = "UPDATE `proforma_invoice_list` SET {$data} WHERE id = '{$id}'";
            }

            // Debug
            error_log("SQL query: " . $sql);
            
            $save = $this->conn->query($sql);
            
            if ($save) {
                $resp['status'] = 'success';
                if (empty($id)) {
                    $resp['msg'] = "New Proforma Invoice successfully created.";
                } else {
                    $resp['msg'] = "Proforma Invoice successfully updated.";
                }

                // Save items
                if(!empty($_POST['description'])) {
                    $stmt = $this->conn->prepare("DELETE FROM proforma_invoice_items WHERE proforma_invoice_id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();

                    $stmt = $this->conn->prepare("INSERT INTO proforma_invoice_items (proforma_invoice_id, description, hsn_code, amount) VALUES (?, ?, ?, ?)");
                    
                    foreach ($_POST['description'] as $key => $description) {
                        $amount = $_POST['amount'][$key];
                        $hsn_code = $_POST['hsn_code'][$key];
                        
                        // Debug log
                        error_log("Saving item - Description: $description, HSN: $hsn_code, Amount: $amount");
                        
                        $stmt->bind_param("issd", $id, $description, $hsn_code, $amount);
                        if (!$stmt->execute()) {
                            error_log("Error saving item: " . $stmt->error);
                            throw new Exception("Failed to save item details: " . $stmt->error);
                        }
                    }
                }
                
                $resp['msg'] = "Proforma Invoice saved successfully.";
                $resp['id'] = $id;
                $resp['pi_code'] = $pi_code;
                
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
    function manage_stock(){
    extract($_POST);
    $data = "item_id = '{$item_id}', quantity = '{$quantity}', unit = '{$unit}', price = '{$price}', total = '{$total}', type = '{$type}'";
    if(empty($id)){
        $sql = "INSERT INTO `stock_list` set {$data}";
    }else{
        $sql = "UPDATE `stock_list` set {$data} where id = '{$id}'";
    }
    $save = $this->conn->query($sql);
    if($save){
        $resp['status'] = 'success';
        if(empty($id))
            $this->settings->set_flashdata('success',"New Stock Record successfully saved.");
        else
            $this->settings->set_flashdata('success',"Stock Record successfully updated.");
    }else{
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred. Error: '.$this->conn->error;
    }
    return json_encode($resp);
    }
    function log_stock_usage(){
    extract($_POST);
    
    $success = true;
    $this->conn->begin_transaction();

    try {
        // Loop through each item
        foreach($item_id as $key => $value){
            // First check if we have enough stock
            $stock_check = $this->conn->query("SELECT SUM(quantity) as total FROM `stock_list` WHERE item_id = '{$value}'");
            $available = $stock_check->fetch_assoc()['total'];
            
            if($available < $quantity[$key]){
                $success = false;
                $resp['status'] = 'failed';
                $resp['msg'] = 'Insufficient stock for selected item(s)';
                break;
            }

            // Insert usage record
            $data = "item_id = '{$value}', used_by = '{$used_by}', quantity = '{$quantity[$key]}'";
            $sql = "INSERT INTO `usage_history` SET {$data}";
            $save = $this->conn->query($sql);
            
            if(!$save){
                $success = false;
                break;
            }

            // Update stock quantity
            $remaining_quantity = $quantity[$key];
            $stock_entries = $this->conn->query("SELECT * FROM `stock_list` WHERE item_id = '{$value}' AND quantity > 0 ORDER BY date_created ASC");
            while($row = $stock_entries->fetch_assoc()){
                if($remaining_quantity <= 0) break;
                $reduce_quantity = min($row['quantity'], $remaining_quantity);
                $this->conn->query("UPDATE `stock_list` SET quantity = quantity - {$reduce_quantity} WHERE id = '{$row['id']}'");
                $remaining_quantity -= $reduce_quantity;
            }
        }

        if($success){
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Stock usage logged successfully.");
        }else{
            if(!isset($resp['msg'])){
                $resp['msg'] = 'An error occurred while saving the stock usage.';
            }
            $this->conn->rollback();
            $resp['status'] = 'failed';
        }
    } catch (Exception $e) {
        $this->conn->rollback();
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred: ' . $e->getMessage();
    }

    return json_encode($resp);
    }
    
    function save_po_details() {
    try {
        extract($_POST);

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
                    credit_received, po_file, eway_file, lr_file, quotation_file
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("isssssddddssss", 
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
                "credit_received = ?"
            ];
            $params = [
                $requirement,
                $specification,
                $expected_delivery,
                $remarks,
                $adv_received,
                $insp_received,
                $inst_received,
                $cred_received
            ];
            $types = "ssssdddd";

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

            if($activity_id) {
                // Update existing activity
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
                // Insert new activity
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
                
                $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d H:i:s');
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
            }
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to save activity: " . $stmt->error);
            }

            $activity_id = $activity_id ?: $this->conn->insert_id;
    
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
                                $activity_id,
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
            $_SESSION['success_msg'] = $activity_id ? "Activity updated successfully" : "Activity logged successfully";
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
                $stmt->bind_param('ississsi', 
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

    function save_task(){
        extract($_POST);
        $data = "";
        
        // Check if this is an update and status has changed
        if(!empty($id)){
            $old_status = $this->conn->query("SELECT status FROM tasks WHERE id = '{$id}'")->fetch_assoc()['status'];
            if($old_status != $status){
                if(!empty($data)) $data .= ",";
                $data .= " status_updated_at = CURRENT_TIMESTAMP ";
            }
        }
        
        foreach($_POST as $k =>$v){
            if(!in_array($k, array('id', 'assigned_by'))){
                if(!empty($data)) $data .=",";
                $v = $this->conn->real_escape_string($v);
                $data .= " `{$k}`='{$v}' ";
            }
        }
        
        if(empty($id)){
            $data .= ", `assigned_by`='{$_SESSION['userdata']['id']}'";
            $sql = "INSERT INTO `tasks` set {$data}";
        }else{
            $sql = "UPDATE `tasks` set {$data} where id = '{$id}'";
        }
        
        $save = $this->conn->query($sql);
        if($save){
            // Get updated task count
            $user_id = $assigned_to;
            $count_query = $this->conn->query("SELECT COUNT(*) as count FROM tasks 
                WHERE assigned_to = '{$user_id}' AND status != 'completed'");
            $count = $count_query->fetch_assoc()['count'];
            
            $resp['status'] = 'success';
            $resp['task_count'] = $count;
            if(empty($id))
                $this->settings->set_flashdata('success',"New Task successfully saved.");
            else
                $this->settings->set_flashdata('success',"Task successfully updated.");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error;
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

    function save_quote_item() {
    try {
        extract($_POST);
        $this->conn->begin_transaction();

        // Basic item data
        $data = "name = '" . $this->conn->real_escape_string($name) . "', 
                 description = '" . $this->conn->real_escape_string($description) . "'";

        // Insert or Update main item data
        if(empty($id)) {
            $sql = "INSERT INTO quote_items SET {$data}";
            $save = $this->conn->query($sql);
            $item_id = $this->conn->insert_id;
        } else {
            $sql = "UPDATE quote_items SET {$data} WHERE id = '{$id}'";
            $save = $this->conn->query($sql);
            $item_id = $id;
        }

        if(!$save) throw new Exception("Failed to save item data");

        // Handle image uploads with descriptions
        if(isset($_FILES['item_images'])) {
            $upload_path = '../uploads/quote_items/';
            if(!is_dir($upload_path)) mkdir($upload_path, 0777, true);

            foreach($_FILES['item_images']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['item_images']['error'][$key] == 0) {
                    $ext = pathinfo($_FILES['item_images']['name'][$key], PATHINFO_EXTENSION);
                    $fname = 'item_' . time() . '_' . $key . '.' . $ext;
                    $image_path = 'uploads/quote_items/' . $fname;
                    
                    if(move_uploaded_file($tmp_name, $upload_path . $fname)) {
                        $description = isset($_POST['image_descriptions'][$key]) ? 
                            $this->conn->real_escape_string($_POST['image_descriptions'][$key]) : '';

                        $img_sql = "INSERT INTO quote_item_images (quote_item_id, image_path, description) 
                                  VALUES ('{$item_id}', '{$image_path}', '{$description}')";
                        if(!$this->conn->query($img_sql)) {
                            throw new Exception("Failed to save image record");
                        }
                    }
                }
            }
        }

        // Save Technical Specifications
        if(isset($_POST['attr_name'])) {
            if(!empty($id)) {
                $this->conn->query("DELETE FROM quote_item_attributes WHERE quote_item_id = $item_id");
            }
            $attr_stmt = $this->conn->prepare("INSERT INTO quote_item_attributes (quote_item_id, attribute_name, attribute_value) VALUES (?, ?, ?)");
            foreach($_POST['attr_name'] as $key => $attr_name) {
                if(empty($attr_name)) continue;
                $attr_value = $_POST['attr_value'][$key];
                $attr_stmt->bind_param("iss", $item_id, $attr_name, $attr_value);
                $attr_stmt->execute();
            }
        }

        // Save Pricing Details
        if(isset($_POST['price_desc'])) {
            if(!empty($id)) {
                $this->conn->query("DELETE FROM quote_item_prices WHERE quote_item_id = $item_id");
            }
            $price_stmt = $this->conn->prepare("INSERT INTO quote_item_prices (quote_item_id, description, price) VALUES (?, ?, ?)");
            foreach($_POST['price_desc'] as $key => $price_desc) {
                if(empty($price_desc)) continue;
                $price = floatval($_POST['price_amount'][$key]);
                $price_stmt->bind_param("isd", $item_id, $price_desc, $price);
                $price_stmt->execute();
            }
        }

        // Save Accessories
        if(isset($_POST['acc_name'])) {
            if(!empty($id)) {
                $this->conn->query("DELETE FROM quote_item_accessories WHERE quote_item_id = $item_id");
            }
            $acc_stmt = $this->conn->prepare("INSERT INTO quote_item_accessories (quote_item_id, name, price) VALUES (?, ?, ?)");
            foreach($_POST['acc_name'] as $key => $acc_name) {
                if(empty($acc_name)) continue;
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
function delete_quote_item() {
    try {
        extract($_POST);
        $this->conn->begin_transaction();

        // Fetch all image paths for this item
        $images = $this->conn->query("SELECT image_path FROM quote_item_images WHERE quote_item_id = '{$id}'");
        while($img = $images->fetch_assoc()) {
            $file_path = '../' . $img['image_path'];
            if(file_exists($file_path)) {
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
        foreach($tables as $table) {
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

function delete_quote_image() {
    try {
        extract($_POST);
        $img = $this->conn->query("SELECT * FROM quote_item_images WHERE id = '{$id}'")->fetch_assoc();
        if(!$img) throw new Exception('Image not found');
        $file_path = '../' . $img['image_path'];
        if(file_exists($file_path)) @unlink($file_path);
        $delete = $this->conn->query("DELETE FROM quote_item_images WHERE id = '{$id}'");
        if(!$delete) throw new Exception("Failed to delete image record");
        $resp['status'] = 'success';
        $resp['msg'] = 'Image deleted successfully';
    } catch (Exception $e) {
        $resp['status'] = 'failed';
        $resp['msg'] = $e->getMessage();
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
	case 'save_receiving':
		echo $Master->save_receiving();
	break;
	case 'delete_receiving':
		echo $Master->delete_receiving();
	break;
	case 'delete_bo':
        echo $Master->delete_bo();
    break;
	case 'save_return':
		echo $Master->save_return();
	break;
	case 'delete_return':
		echo $Master->delete_return();
	break;
	case 'save_sale':
		echo $Master->save_sale();
	break;
	case 'delete_sale':
		echo $Master->delete_sale();
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
    case 'manage_stock':
        echo $Master->manage_stock();
    break;
    case 'log_stock_usage':
        echo $Master->log_stock_usage();
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
    case 'save_purchase_order_timeline':
        echo $Master->save_purchase_order_timeline();
    break;
    case 'delete_purchase_order_timeline':
        echo $Master->delete_purchase_order_timeline();
    break;
	default:
		// echo $sysset->index();
	break;
}