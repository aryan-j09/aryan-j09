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
    function save_project(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id', 'supplier_po_ids', 'supplier_po_detail_id'))){
                $v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		
		$check = $this->conn->query("SELECT * FROM `project_planner` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Project Name already exists.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `project_planner` set {$data} ";
		}else{
			$sql = "UPDATE `project_planner` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$project_id = empty($id) ? $this->conn->insert_id : $id;
			$resp['status'] = 'success';
			$resp['project_id'] = $project_id;

			// Clear old PO associations for this project
			$this->conn->query("DELETE FROM `project_po_list` WHERE project_id = '{$project_id}'");

			// Clear old PO Detail associations for this project
			$this->conn->query("DELETE FROM `project_po_detail_list` WHERE project_id = '{$project_id}'");

			// Save supplier POs
			if(isset($supplier_po_ids) && is_array($supplier_po_ids)){
				$po_data = "";
				foreach($supplier_po_ids as $po_id){
					if(!empty($po_data)) $po_data .= ", ";
					$po_data .= "('{$project_id}', '{$po_id}')";
				}
				if(!empty($po_data)){
					$this->conn->query("INSERT INTO `project_po_list` (project_id, po_id) VALUES {$po_data}");
				}
			}

			// Save supplier PO Detail (single value)
			if(isset($supplier_po_detail_id) && !empty($supplier_po_detail_id)){
				$po_detail_id = intval($supplier_po_detail_id);
				$this->conn->query("INSERT INTO `project_po_detail_list` (project_id, po_detail_id) VALUES ('{$project_id}', '{$po_detail_id}')");
			}

			if(empty($id)){
				$this->settings->set_flashdata('success',"New Project successfully saved.");
			} else {
				$this->settings->set_flashdata('success',"Project successfully updated.");
			}
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}

	function get_po_items(){
		extract($_POST);
		$resp = ['status' => 'failed', 'html' => ''];
		$html = "";

		try {
			// Supplier PO Items
			if(isset($supplier_po_ids) && is_array($supplier_po_ids) && count($supplier_po_ids) > 0){
				$supplier_ids = implode(',', array_map('intval', $supplier_po_ids));
				$sql = "SELECT poi.*, il.name as item_name, pol.po_code 
						FROM `po_items` poi 
						JOIN `item_list` il ON poi.item_id = il.id 
						JOIN `purchase_order_list` pol ON poi.po_id = pol.id 
						WHERE poi.po_id IN ({$supplier_ids}) 
						ORDER BY pol.po_code, il.name";
				$qry = $this->conn->query($sql);

				if($qry->num_rows > 0){
					$html .= '<h5 class="mt-3">Supplier PO Items</h5>';
					$html .= '<table class="table table-sm table-bordered table-striped">';
					$html .= '<thead class="thead-dark"><tr><th>PO Code</th><th>Item</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr></thead><tbody>';
					while($row = $qry->fetch_assoc()){
						$html .= "<tr>
									<td>" . htmlspecialchars($row['po_code']) . "</td>
									<td>" . htmlspecialchars($row['item_name']) . "</td>
									<td>" . htmlspecialchars($row['quantity']) . "</td>
									<td>" . number_format($row['amount'], 2) . "</td>
									<td>" . number_format($row['total_amount'], 2) . "</td>
								  </tr>";
					}
					$html .= '</tbody></table>';
				}
			}

			// PO Details Items
			if(isset($supplier_po_detail_ids) && is_array($supplier_po_detail_ids) && count($supplier_po_detail_ids) > 0){
				$po_detail_ids = implode(',', array_map('intval', $supplier_po_detail_ids));
				$sql = "SELECT po.id, po.po_code, pil.client_id, c.company_name as client_name
						FROM `purchase_orders` po 
						LEFT JOIN `proforma_invoice_list` pil ON po.po_code = pil.po_code
						LEFT JOIN `clients` c ON pil.client_id = c.id
						WHERE po.id IN ({$po_detail_ids}) 
						ORDER BY po.po_code";
				$qry = $this->conn->query($sql);

				if($qry->num_rows > 0){
					$html .= '<h5 class="mt-3">PO Factory Details</h5>';
					$html .= '<table class="table table-sm table-bordered table-striped">';
					$html .= '<thead class="thead-dark"><tr><th>PO Code</th><th>Client</th></tr></thead><tbody>';
					while($row = $qry->fetch_assoc()){
						$html .= "<tr>
									<td>" . htmlspecialchars($row['po_code']) . "</td>
									<td>" . htmlspecialchars($row['client_name']) . "</td>
								  </tr>";
					}
					$html .= '</tbody></table>';
				}
			}

			$resp['status'] = 'success';
			$resp['html'] = $html;
		} catch (Exception $e) {
			$resp['msg'] = $e->getMessage();
		}
		return json_encode($resp);
	}

	function save_project_item(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id', 'po_item_id'))){
				$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}

		if(empty($id)){
			$sql = "INSERT INTO `project_items` set {$data} ";
		}else{
			$sql = "UPDATE `project_items` set {$data} where id = '{$id}' ";
		}

		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New project item added.");
			else
				$this->settings->set_flashdata('success',"Project item updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
    
	function get_item_activities(){
		extract($_POST);
		$resp = ['status' => 'failed', 'html' => ''];
		$item_id = intval($item_id);

		$qry = $this->conn->query("SELECT * FROM `project_item_activities` WHERE project_item_id = '{$item_id}' ORDER BY activity_date DESC, id DESC");
		
		$html = '<ul class="list-group">';
		if($qry->num_rows > 0){
			while($row = $qry->fetch_assoc()){
				$html .= '<li class="list-group-item">';
				$html .= '<strong>' . htmlspecialchars($row['activity_name']) . '</strong> - <small>' . date("d M, Y", strtotime($row['activity_date'])) . '</small>';
				$html .= '<button class="btn btn-xs btn-danger float-right delete_activity" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></button>';
				if(!empty($row['remarks'])){
					$html .= '<p class="mb-0 mt-1 text-muted">' . nl2br(htmlspecialchars($row['remarks'])) . '</p>';
				}
				$html .= '</li>';
			}
		} else {
			$html .= '<li class="list-group-item text-center text-muted">No activities logged yet.</li>';
		}
		$html .= '</ul>';

		$resp['status'] = 'success';
		$resp['html'] = $html;
		return json_encode($resp);
	}

	function save_item_activity(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id'))){
				$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$sql = "INSERT INTO `project_item_activities` set {$data} ";
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}

	function delete_item_activity(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `project_item_activities` where id = '{$id}'");
		$resp['status'] = $del ? 'success' : 'failed';
		return json_encode($resp);
	}

	function save_project_activity(){
        header('Content-Type: application/json');
        try{
            extract($_POST);
            $sets = [];
            // Unset fields that are not part of the table
            unset($_POST['id']);

            foreach($_POST as $k => $v){
                // Exclude datetime fields we'll handle separately
                if(!in_array($k, array('activity_id', 'created_at', 'next_followup', 'time_from', 'time_to'))){ 
                    // Skip arrays to avoid warnings
                    if(is_array($v)) continue;
                    $v = $this->conn->real_escape_string($v);
                    $sets[] = "`{$k}`='{$v}'";
                }
            }

            // Manually handle and format the created_at field
            if(isset($created_at) && !empty($created_at)){
                // Normalize possible 'T' in datetime-local values
                if(strpos($created_at,'T') !== false){
                    $created_at = str_replace('T',' ',$created_at);
                    if(strlen($created_at) == 16) $created_at .= ':00';
                }
                $sets[] = "`created_at`='" . date("Y-m-d H:i:s", strtotime($created_at)) . "'";
            }

            // Handle datetime fields - set NULL if empty, otherwise escape and set value
            $next_followup = isset($next_followup) ? $next_followup : '';
            if(empty($next_followup)){
                $sets[] = "`next_followup`=NULL";
            } else {
                // Normalize possible 'T' in datetime-local values
                if(strpos($next_followup,'T') !== false){
                    $next_followup = str_replace('T',' ',$next_followup);
                    if(strlen($next_followup) == 16) $next_followup .= ':00';
                }
                $next_followup = $this->conn->real_escape_string($next_followup);
                $sets[] = "`next_followup`='{$next_followup}'";
            }
            
            $time_from = isset($time_from) ? $time_from : '';
            if(empty($time_from)){
                $sets[] = "`time_from`=NULL";
            } else {
                $time_from = $this->conn->real_escape_string($time_from);
                $sets[] = "`time_from`='{$time_from}'";
            }
            
            $time_to = isset($time_to) ? $time_to : '';
            if(empty($time_to)){
                $sets[] = "`time_to`=NULL";
            } else {
                $time_to = $this->conn->real_escape_string($time_to);
                $sets[] = "`time_to`='{$time_to}'";
            }

            if(empty($activity_id)){
                // Insert new activity
                $created_by = isset($_SESSION['userdata']['id']) ? $_SESSION['userdata']['id'] : 0;
                $sets[] = "`created_by`='{$created_by}'";
                $sql = "INSERT INTO `project_activities` SET " . implode(", ", $sets);
            }else{
                // Update existing activity
                $sql = "UPDATE `project_activities` SET " . implode(", ", $sets) . " WHERE id = '{$activity_id}' ";
            }

            $save = $this->conn->query($sql);
            if($save){
                $resp['status'] = 'success';
                if(empty($activity_id))
                    $this->settings->set_flashdata('success',"New project activity logged.");
                else
                    $this->settings->set_flashdata('success',"Project activity updated.");
            }else{
                // Log SQL and input for debugging
                error_log("save_project_activity failed: " . $this->conn->error . " SQL: " . $sql);
                error_log("POST: " . print_r($_POST, true));
                $resp['status'] = 'failed';
                $resp['err'] = $this->conn->error."[{$sql}]";
            }
            return json_encode($resp);
        } catch (Exception $e){
            error_log("Exception in save_project_activity: " . $e->getMessage());
            return json_encode(['status'=>'failed','err'=>$e->getMessage()]);
        }
	}

	function delete_project_activity(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `project_activities` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Project activity successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function delete_project_item(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `project_items` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Project item successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function delete_project(){
		extract($_POST);
		$this->conn->begin_transaction();
		try {
			// Get all item IDs for the project
			$item_ids_qry = $this->conn->query("SELECT id FROM `project_items` WHERE project_id = '{$id}'");
			$item_ids = [];
			while($row = $item_ids_qry->fetch_assoc()){
				$item_ids[] = $row['id'];
			}

			if(!empty($item_ids)){
				$item_ids_str = implode(',', $item_ids);
				// Delete activities for those items
				$this->conn->query("DELETE FROM `project_item_activities` WHERE project_item_id IN ({$item_ids_str})");
			}

			// Delete project items
			$this->conn->query("DELETE FROM `project_items` WHERE project_id = '{$id}'");
			// Delete general project activities
			$this->conn->query("DELETE FROM `project_activities` WHERE project_id = '{$id}'");
			// Delete PO associations
			$this->conn->query("DELETE FROM `project_po_list` WHERE project_id = '{$id}'");
			// Delete PO Detail associations
			$this->conn->query("DELETE FROM `project_po_detail_list` WHERE project_id = '{$id}'");
			// Delete associated tasks
			$this->conn->query("DELETE FROM `tasks` WHERE project_id = '{$id}'");
            // Delete associated planner sheets
            $this->conn->query("DELETE FROM `project_sheets` WHERE project_id = '{$id}'");
			// Finally, delete the project itself
			$del = $this->conn->query("DELETE FROM `project_planner` WHERE id = '{$id}'");

			$this->conn->commit();
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Project and all its data successfully deleted.");
		} catch (Exception $e) {
			$this->conn->rollback();
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function save_project_phase(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k => $v){
			if(!in_array($k, array('id'))){
				$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";

                // Handle empty dates by setting them to NULL
                if(in_array($k, ['start_date', 'end_date']) && empty($v)){
                    $data .= " `{$k}`=NULL ";
                } else {
				    $data .= " `{$k}`='{$v}' ";
                }
			}
		}

		if(empty($id)){
			$sql = "INSERT INTO `project_phases` set {$data} ";
		}else{
			$sql = "UPDATE `project_phases` set {$data} where id = '{$id}' ";
		}

		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New project phase added.");
			else
				$this->settings->set_flashdata('success',"Project phase updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}

	function delete_project_phase(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `project_phases` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = $this->conn->error;
		}
		return json_encode($resp);
	}

    function save_phase_activity() {
        extract($_POST);
        
        // Ensure item_name is NULL if not provided or if log type is general
        if(!isset($_POST['item_name']) || empty($_POST['item_name'])){
            $_POST['item_name'] = NULL;
        }

        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k, array('id'))){ // 'id' is the activity id
                $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";

                if($k == 'item_name' && $v === NULL) {
                    $data .= " `{$k}`=NULL ";
                } else {
                    $data .= " `{$k}`='{$v}' ";
                }
            }
        }

        if(empty($id)){
            $data .= ", `created_by` = '{$_SESSION['userdata']['id']}'";
            $sql = "INSERT INTO `phase_activities` set {$data} ";
        } else {
            $sql = "UPDATE `phase_activities` set {$data} where id = '{$id}'";
        }

        $save = $this->conn->query($sql);
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"New phase activity added.");
            else
                $this->settings->set_flashdata('success',"Phase activity updated.");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    
	function delete_phase_activity(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `phase_activities` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function get_project_items_for_activity(){
        extract($_POST);
        $project_id = isset($project_id) ? intval($project_id) : 0;
        if($project_id <= 0){
            return json_encode([]);
        }
        
        $search = isset($_POST['q']) ? $_POST['q'] : '';
        $items = [];

        // Fetch all item names from the global item list (ignore supplier/project restrictions)
        // This ensures users see all items and can select any; frontend tagging will allow adding temporary names.
        $sql = "SELECT DISTINCT name FROM `item_list` WHERE name LIKE ? ORDER BY name ASC";

        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            return json_encode([]);
        }
        $searchTerm = "%{$search}%";
        if(!$stmt->bind_param('s', $searchTerm)){
            $stmt->close();
            return json_encode([]);
        }
        if(!$stmt->execute()){
            $stmt->close();
            return json_encode([]);
        }

        $name = null;
        $stmt->bind_result($name);
        while($stmt->fetch()){
            $items[] = ['id' => $name, 'text' => $name];
        }
        $stmt->close();
        return json_encode($items);
	}




	function get_po_item_for_project(){
		extract($_POST);
		$resp = ['status' => 'failed', 'msg' => 'An error occurred.'];
		
		$item_id = intval($po_item_id);

		$sql = "SELECT il.name as item_name, poi.quantity, '' as specifications 
				FROM `po_items` poi
				JOIN `item_list` il ON poi.item_id = il.id
				WHERE poi.id = ?";
		$stmt = $this->conn->prepare($sql);
		
		if(!$stmt){
			$resp['msg'] = 'Prepare failed: ' . $this->conn->error;
			return json_encode($resp);
		}
		
		$stmt->bind_param('i', $item_id);

		if(!$stmt->execute()){
			$resp['msg'] = 'Execute failed: ' . $stmt->error;
			return json_encode($resp);
		}
		
		// Use bind_result instead of get_result for better compatibility
		$item_name = '';
		$quantity = '';
		$specifications = '';
		
		if(!$stmt->bind_result($item_name, $quantity, $specifications)){
			$resp['msg'] = 'Bind result failed: ' . $stmt->error;
			$stmt->close();
			return json_encode($resp);
		}
		
		if($stmt->fetch()){
			$resp['status'] = 'success';
			$resp['data'] = [
				'item_name' => $item_name,
				'quantity' => $quantity,
				'specifications' => $specifications
			];
		} else {
			$resp['msg'] = 'No PO item found with ID: ' . $item_id;
		}
		$stmt->close();
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

    function delete_receipt(){
        $po = isset($_POST['po']) ? $_POST['po'] : '';
        $date = isset($_POST['date']) ? $_POST['date'] : '';
        $created_at = isset($_POST['created_at']) ? $_POST['created_at'] : '';
        $reference_type = isset($_POST['reference_type']) ? $_POST['reference_type'] : '';
        $resp = ['status' => 'failed', 'msg' => 'Invalid parameters'];

        if(empty($date) || empty($created_at) || empty($reference_type)){
            return json_encode($resp);
        }
        
        // Delete stock movements based on reference type
        if($reference_type === 'PO' && !empty($po)) {
            // Delete PO receipts: match by po_code and date
            $delete_sql = "DELETE sm FROM stock_movement sm
                          JOIN purchase_order_list pol ON sm.reference_id = pol.id
                          WHERE pol.po_code = ? 
                          AND DATE(sm.created_at) = ?
                          AND sm.reference_type = 'PO'
                          AND sm.movement_type = 'IN'";
            
            $stmt = $this->conn->prepare($delete_sql);
            if (!$stmt) {
                $resp['msg'] = "Prepare failed: " . $this->conn->error;
                return json_encode($resp);
            }
            
            $stmt->bind_param('ss', $po, $date);
        } else if($reference_type === 'MANUAL') {
            // Delete manual receipts: match by created_at timestamp (to the minute)
            $delete_sql = "DELETE FROM stock_movement 
                          WHERE DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') = DATE_FORMAT(?, '%Y-%m-%d %H:%i')
                          AND reference_type = 'MANUAL'
                          AND movement_type = 'IN'";
            
            $stmt = $this->conn->prepare($delete_sql);
            if (!$stmt) {
                $resp['msg'] = "Prepare failed: " . $this->conn->error;
                return json_encode($resp);
            }
            
            $stmt->bind_param('s', $created_at);
        } else {
            $resp['msg'] = 'Unknown receipt type';
            return json_encode($resp);
        }
        
        if ($stmt->execute()) {
            $resp['status'] = 'success';
            $resp['msg'] = 'Receipt deleted successfully';
            $this->settings->set_flashdata('success', "Receipt deleted successfully.");
        } else {
            $resp['msg'] = "Delete failed: " . $stmt->error;
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
        header('Content-Type: application/json');
        $resp = array('status'=>'failed', 'msg'=>'');
        try {
            // Accept alternative parameter names for robustness
            $lead_id = isset($_POST['lead_id']) ? $_POST['lead_id'] : (isset($_POST['id']) ? $_POST['id'] : (isset($_POST['lead']) ? $_POST['lead'] : null));
            $activity_type = isset($_POST['activity_type']) ? $_POST['activity_type'] : null;
            $description = isset($_POST['description']) ? $_POST['description'] : null;

            if(empty($lead_id) || empty($activity_type) || empty($description)) {
                throw new Exception("Lead ID, activity type and description are required");
            }

            // Normalize datetimes: convert 'YYYY-MM-DDTHH:MM' to 'YYYY-MM-DD HH:MM:SS' if needed
            if(isset($_POST['created_at']) && strpos($_POST['created_at'], 'T') !== false){
                $_POST['created_at'] = str_replace('T', ' ', $_POST['created_at']);
                if(strlen($_POST['created_at']) == 16) $_POST['created_at'] .= ':00';
            }
            if(isset($_POST['next_followup']) && strpos($_POST['next_followup'], 'T') !== false){
                $_POST['next_followup'] = str_replace('T', ' ', $_POST['next_followup']);
                if(strlen($_POST['next_followup']) == 16) $_POST['next_followup'] .= ':00';
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
                    $activity_type,
                    $description,
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
                $created_at = isset($_POST['created_at']) && !empty($_POST['created_at']) ? $_POST['created_at'] : date('Y-m-d H:i:s');
                $next_followup = !empty($_POST['next_followup']) ? $_POST['next_followup'] : null;
                $time_from = !empty($_POST['time_from']) ? $_POST['time_from'] : null;
                $time_to = !empty($_POST['time_to']) ? $_POST['time_to'] : null;
                $created_by = isset($_SESSION['userdata']['id']) ? $_SESSION['userdata']['id'] : null;
                $stmt->bind_param("isssssss", 
                    $lead_id,
                    $activity_type,
                    $description,
                    $next_followup,
                    $created_by,
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
                $upload_path = dirname(__FILE__) . '/../uploads/lead_documents/';
                // Create directory if it doesn't exist
                if(!is_dir($upload_path)) {
                    if(!mkdir($upload_path, 0777, true)) {
                        throw new Exception("Failed to create upload directory");
                    }
                }

                for($i = 0; $i < count($_FILES['documents']['name']); $i++) {
                    if($_FILES['documents']['error'][$i] == 0) {
                        $file_ext = pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION);
                        $file_name = 'doc_' . time() . '_' . $i . '.' . $file_ext;
                        $file_path = 'uploads/lead_documents/' . $file_name;
                        $full_path = $upload_path . $file_name;

                        if(move_uploaded_file($_FILES['documents']['tmp_name'][$i], $full_path)) {
                            $doc_type = isset($_POST['document_type'][$i]) ? $_POST['document_type'][$i] : '';
                            $doc_desc = isset($_POST['document_description'][$i]) ? $_POST['document_description'][$i] : '';
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
                                throw new Exception("Failed to save document record: " . $stmt->error);
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

    function get_users() {
        header('Content-Type: application/json');
        $resp = ['status' => 'failed', 'users' => []];

        try {
            // Get all users - remove status filter to see all users
            $qry = $this->conn->query("SELECT id, username as name FROM users ORDER BY username ASC");
            
            if (!$qry) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $users = [];
            if ($qry->num_rows > 0) {
                while ($row = $qry->fetch_assoc()) {
                    $users[] = [
                        'id' => (int)$row['id'],
                        'name' => $row['name']
                    ];
                }
            }

            $resp['status'] = 'success';
            $resp['users'] = $users;
            $resp['count'] = count($users); // Debug info

        } catch (Exception $e) {
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }

        return json_encode($resp);
    }

    function save_task()
    {
        try {
            extract($_POST);

            // Validate required fields
            if (empty($title) || empty($assigned_to) || empty($due_date) || empty($status) || empty($priority)) {
                throw new Exception("Please fill in all required fields");
            }

            // Standardize casing for status and priority
            if(isset($_POST['status'])){
                $_POST['status'] = ucwords(str_replace('-', ' ', $_POST['status']));
            }
            if(isset($_POST['priority'])){
                $_POST['priority'] = ucfirst(strtolower($_POST['priority']));
            }

            // Build data string
            $data = "";
            foreach ($_POST as $k => $v) {
                if (!in_array($k, array('id', 'assigned_by', 'project_id'))) { // Exclude project_id from initial loop
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
                // Add project_id if it exists
                if (isset($project_id) && !empty($project_id)) {
                    if (!empty($data)) $data .= ",";
                    $data .= " `project_id`='{$project_id}' ";
                }

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

    function create_tasks_from_sheet() {
        header('Content-Type: application/json');
        $resp = ['status' => 'failed', 'msg' => 'An error occurred.', 'created_count' => 0];

        try {
            extract($_POST);

            // Validate required fields
            if (empty($project_id) || empty($rows)) {
                throw new Exception("Project ID and rows data are required.");
            }

            $project_id = intval($project_id);
            if ($project_id <= 0) {
                throw new Exception("Invalid project ID.");
            }

            // Verify project exists
            $proj_check = $this->conn->query("SELECT id FROM project_planner WHERE id = '{$project_id}'");
            if (!$proj_check || $proj_check->num_rows == 0) {
                throw new Exception("Project not found.");
            }

            // Decode rows array
            $rows = json_decode($rows, true);
            if (!is_array($rows) || empty($rows)) {
                throw new Exception("No rows provided.");
            }

            // Get current user ID for assigned_by
            $assigned_by = $_SESSION['userdata']['id'];

            // Default values for new tasks
            $default_status = 'Pending';
            $default_priority = 'medium';
            $default_assigned_to = !empty($assigned_to) ? intval($assigned_to) : $assigned_by;

            $created_count = 0;
            $this->conn->begin_transaction();

            foreach ($rows as $row) {
                // Extract activity (as description) and end_date (as due_date)
                $activity = isset($row['activity']) ? trim($row['activity']) : '';
                $end_date = isset($row['end_date']) ? trim($row['end_date']) : '';

                // Skip empty activities
                if (empty($activity)) {
                    continue;
                }

                // Parse flexible date format for due_date
                $due_date = null;
                if (!empty($end_date)) {
                    $parsed_date = $this->parseFlexibleDate($end_date);
                    if ($parsed_date) {
                        $due_date = $parsed_date->format('Y-m-d');
                    }
                }

                // Use today's date as fallback for due_date
                if (!$due_date) {
                    $due_date = date('Y-m-d');
                }

                // Escape activity for SQL
                $title = $this->conn->real_escape_string($activity);

                // Build INSERT query
                $sql = "INSERT INTO `tasks` (
                    `title`,
                    `description`,
                    `assigned_to`,
                    `assigned_by`,
                    `due_date`,
                    `status`,
                    `priority`,
                    `project_id`
                ) VALUES (
                    '{$title}',
                    '{$title}',
                    '{$default_assigned_to}',
                    '{$assigned_by}',
                    '{$due_date}',
                    '{$default_status}',
                    '{$default_priority}',
                    '{$project_id}'
                )";

                if (!$this->conn->query($sql)) {
                    throw new Exception("Failed to create task: " . $this->conn->error);
                }

                $created_count++;
            }

            $this->conn->commit();

            if ($created_count > 0) {
                $resp['status'] = 'success';
                $resp['created_count'] = $created_count;
                $resp['msg'] = "{$created_count} task(s) successfully created from spreadsheet.";
                $this->settings->set_flashdata('success', $resp['msg']);
            } else {
                throw new Exception("No valid rows to create tasks from.");
            }

        } catch (Exception $e) {
            if ($this->conn->connect_errno == 0) {
                $this->conn->rollback();
            }
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }

        return json_encode($resp);
    }

    function get_project_tasks() {
        header('Content-Type: application/json');
        $resp = ['status' => 'failed', 'msg' => 'An error occurred.', 'tasks' => []];

        try {
            extract($_GET);

            if (empty($project_id)) {
                throw new Exception("Project ID is required.");
            }

            $project_id = intval($project_id);
            if ($project_id <= 0) {
                throw new Exception("Invalid project ID.");
            }

            // Verify project exists
            $proj_check = $this->conn->query("SELECT id FROM project_planner WHERE id = '{$project_id}'");
            if (!$proj_check || $proj_check->num_rows == 0) {
                throw new Exception("Project not found.");
            }

            // Fetch tasks for this project
            $sql = "SELECT t.id, t.title, t.description, t.assigned_to, t.due_date, t.status, t.priority,
                           u.name as assigned_to_name
                    FROM `tasks` t
                    LEFT JOIN `users` u ON t.assigned_to = u.id
                    WHERE t.project_id = '{$project_id}'
                    ORDER BY t.due_date ASC, t.priority DESC, t.created_at DESC";

            $qry = $this->conn->query($sql);
            if (!$qry) {
                throw new Exception("Database error: " . $this->conn->error);
            }

            $tasks = [];
            if ($qry->num_rows > 0) {
                while ($row = $qry->fetch_assoc()) {
                    $tasks[] = $row;
                }
            }

            $resp['status'] = 'success';
            $resp['tasks'] = $tasks;
            $resp['msg'] = 'Tasks retrieved successfully.';

        } catch (Exception $e) {
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }

        return json_encode($resp);
    }

    function parseFlexibleDate($dateStr) {
        if (!$dateStr) return null;
        $dateStr = trim($dateStr);

        // Try dd-mm-yyyy or dd/mm/yyyy
        $parts = preg_split('/[-\/]/', $dateStr);
        if (count($parts) === 3) {
            if (is_numeric($parts[0]) && is_numeric($parts[1]) && is_numeric($parts[2])) {
                // Could be dd-mm-yyyy or mm-dd-yyyy or yyyy-mm-dd
                // Assume first two are dd-mm if first part is > 12
                if ($parts[0] > 12) {
                    // dd-mm-yyyy
                    return DateTime::createFromFormat('d-m-Y', $dateStr) ?: DateTime::createFromFormat('d/m/Y', $dateStr);
                } else {
                    // Ambiguous, try mm-dd-yyyy first, then dd-mm-yyyy
                    $d = DateTime::createFromFormat('m-d-Y', $dateStr) ?: DateTime::createFromFormat('m/d/Y', $dateStr);
                    if ($d) return $d;
                    return DateTime::createFromFormat('d-m-Y', $dateStr) ?: DateTime::createFromFormat('d/m/Y', $dateStr);
                }
            }
        }

        // Try standard yyyy-mm-dd
        $d = DateTime::createFromFormat('Y-m-d', $dateStr);
        if ($d) return $d;

        // Try other common formats
        $formats = ['Y/m/d', 'd-M-Y', 'd/M/Y', 'M d, Y', 'm/d/Y'];
        foreach ($formats as $fmt) {
            $d = DateTime::createFromFormat($fmt, $dateStr);
            if ($d) return $d;
        }

        return null;
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

    /*
     * Project Sheets: save and get
     * Uses `project_sheets` table created by migration.
     */
    function save_project_sheet(){
        header('Content-Type: application/json');
        
        if(!isset($_SESSION['userdata']['id'])){
            echo json_encode(['status'=>'error','msg'=>'Not authenticated']);
            exit;
        }
        
        $project_id = intval($_POST['project_id'] ?? 0);
        $sheet_name = isset($_POST['sheet_name']) ? trim($_POST['sheet_name']) : 'Sheet1';
        $sheet_json = isset($_POST['sheet_json']) ? $_POST['sheet_json'] : '';
        
        if($project_id <= 0){
            echo json_encode(['status'=>'error','msg'=>'Invalid project id']);
            exit;
        }
        
        if(empty($sheet_json)){
            echo json_encode(['status'=>'error','msg'=>'Sheet data is empty']);
            exit;
        }
        
        // Ensure table exists
        $checkTable = $this->conn->query("SHOW TABLES LIKE 'project_sheets'");
        if($checkTable && $checkTable->num_rows == 0){
            $sql = "CREATE TABLE `project_sheets` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `project_id` INT NOT NULL,
                `name` VARCHAR(120) NOT NULL DEFAULT 'Sheet1',
                `data` LONGTEXT NOT NULL,
                `created_by` INT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `project_sheet_unique` (`project_id`, `name`),
                KEY `project_id_idx` (`project_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if(!$this->conn->query($sql)){
                echo json_encode(['status'=>'error','msg'=>'Failed to create table: '.$this->conn->error]);
                exit;
            }
        }
        
        // Check if sheet exists for this project
        $check = $this->conn->query("SELECT id FROM project_sheets WHERE project_id = $project_id AND name = '$sheet_name' LIMIT 1");
        if($check && $check->num_rows > 0){
            // Update existing
            $row = $check->fetch_assoc();
            $id = $row['id'];
            $sheet_json_escaped = $this->conn->real_escape_string($sheet_json);
            $update = $this->conn->query("UPDATE project_sheets SET data = '$sheet_json_escaped', updated_at = NOW() WHERE id = $id");
            if($update){
                echo json_encode(['status'=>'success','sheet_id'=>$id,'action'=>'updated']);
            } else {
                echo json_encode(['status'=>'error','msg'=>'Update failed: '.$this->conn->error]);
            }
        } else {
            // Insert new
            $created_by = intval($_SESSION['userdata']['id']);
            $sheet_name_escaped = $this->conn->real_escape_string($sheet_name);
            $sheet_json_escaped = $this->conn->real_escape_string($sheet_json);
            $insert = $this->conn->query("INSERT INTO project_sheets (project_id, name, data, created_by) VALUES ($project_id, '$sheet_name_escaped', '$sheet_json_escaped', $created_by)");
            if($insert){
                $id = $this->conn->insert_id;
                echo json_encode(['status'=>'success','sheet_id'=>$id,'action'=>'created']);
            } else {
                echo json_encode(['status'=>'error','msg'=>'Insert failed: '.$this->conn->error]);
            }
        }
        exit;
    }

    function get_project_sheet(){
        header('Content-Type: application/json');
        // Support sheet_id or project_id+sheet_name
        if(isset($_GET['sheet_id'])){
            $sid = intval($_GET['sheet_id']);
            $qry = $this->conn->query("SELECT * FROM project_sheets WHERE id = '{$sid}' LIMIT 1");
        } else {
            $project_id = intval($_GET['project_id'] ?? 0);
            $sheet_name = $this->conn->real_escape_string($_GET['sheet_name'] ?? 'Sheet1');
            $qry = $this->conn->query("SELECT * FROM project_sheets WHERE project_id = '{$project_id}' AND name = '{$sheet_name}' LIMIT 1");
        }
        if(!$qry){
            echo json_encode(['status'=>'error','msg'=>$this->conn->error]);
            exit;
        }
        if($qry->num_rows <= 0){
            echo json_encode(['status'=>'none','data'=>null]);
            exit;
        }
        $row = $qry->fetch_assoc();
        $data = json_decode($row['data'], true);
        echo json_encode(['status'=>'success','data'=>$data,'meta'=>['id'=>$row['id'],'updated_at'=>$row['updated_at']]]);
        exit;
    }

    function get_project_sheets(){
        header('Content-Type: application/json');
        $project_id = intval($_GET['project_id'] ?? 0);
        if($project_id <= 0){
            echo json_encode(['status'=>'error','msg'=>'Invalid project_id']);
            exit;
        }
        $qry = $this->conn->query("SELECT id, name, created_at, updated_at FROM project_sheets WHERE project_id = '{$project_id}' ORDER BY created_at ASC");
        if(!$qry){
            echo json_encode(['status'=>'error','msg'=>$this->conn->error]);
            exit;
        }
        $sheets = [];
        while($row = $qry->fetch_assoc()){
            $sheets[] = $row;
        }
        // If there are no sheets yet, surface a default placeholder
        if(count($sheets) === 0){
            $sheets[] = ['id'=>0, 'name'=>'Sheet1', 'created_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s')];
        }
        echo json_encode(['status'=>'success','sheets'=>$sheets]);
        exit;
    }

    function delete_project_sheet(){
        header('Content-Type: application/json');
        $project_id = intval($_POST['project_id'] ?? 0);
        $sheet_name = $this->conn->real_escape_string($_POST['sheet_name'] ?? '');
        
        if($project_id <= 0 || empty($sheet_name) || $sheet_name === 'Sheet1'){
            echo json_encode(['status'=>'error','msg'=>'Invalid request or Sheet1 cannot be deleted']);
            exit;
        }
        
        $del = $this->conn->query("DELETE FROM project_sheets WHERE project_id = '{$project_id}' AND name = '{$sheet_name}'");
        if($del){
            echo json_encode(['status'=>'success','msg'=>'Sheet deleted']);
        } else {
            echo json_encode(['status'=>'error','msg'=>$this->conn->error]);
        }
        exit;
    }

    function rename_project_sheet(){
        header('Content-Type: application/json');
        $project_id = intval($_POST['project_id'] ?? 0);
        $old_name = $this->conn->real_escape_string($_POST['old_name'] ?? '');
        $new_name = $this->conn->real_escape_string($_POST['new_name'] ?? '');
        
        if($project_id <= 0 || empty($old_name) || empty($new_name)){
            echo json_encode(['status'=>'error','msg'=>'Invalid request']);
            exit;
        }
        
        // Check if new name already exists
        $exists = $this->conn->query("SELECT id FROM project_sheets WHERE project_id = '{$project_id}' AND name = '{$new_name}' LIMIT 1");
        if($exists && $exists->num_rows > 0){
            echo json_encode(['status'=>'error','msg'=>'Sheet name already exists']);
            exit;
        }
        
        $update = $this->conn->query("UPDATE project_sheets SET name = '{$new_name}' WHERE project_id = '{$project_id}' AND name = '{$old_name}'");
        if($update){
            echo json_encode(['status'=>'success','msg'=>'Sheet renamed']);
        } else {
            echo json_encode(['status'=>'error','msg'=>$this->conn->error]);
        }
        exit;
    }

    function get_po_detail_specs(){
        extract($_POST);
        $resp = ['status' => 'failed', 'html' => ''];
        
        if(!isset($po_detail_ids) || !is_array($po_detail_ids) || empty($po_detail_ids)){
            return json_encode($resp);
        }
        
        $ids = array_map('intval', $po_detail_ids);
        $ids_str = implode(',', $ids);
        
        $query = "SELECT po.po_code, po.requirement, po.specification, c.company_name 
                  FROM `purchase_orders` po
                  LEFT JOIN `proforma_invoice_list` pil ON po.po_code = pil.po_code
                  LEFT JOIN `clients` c ON pil.client_id = c.id
                  WHERE po.id IN ({$ids_str})
                  ORDER BY po.po_code ASC";
        
        $result = $this->conn->query($query);
        
        if($result && $result->num_rows > 0){
            $html = '';
            while($row = $result->fetch_assoc()){
                $po_code = htmlspecialchars($row['po_code']);
                $client = htmlspecialchars($row['company_name'] ?? 'N/A');
                $requirement = $row['requirement'] ?? '';
                $specification = $row['specification'] ?? '';
                
                // Combine requirement and specification if both exist
                if(!empty($requirement) || !empty($specification)){
                    if($result->num_rows > 1){
                        // Multiple POs - add headers
                        $html .= "<h4>PO: {$po_code} - {$client}</h4>";
                    }
                    if(!empty($requirement)){
                        $html .= $requirement;
                    }
                    if(!empty($specification)){
                        if(!empty($requirement)) $html .= '<br><br>';
                        $html .= $specification;
                    }
                    if($result->num_rows > 1){
                        $html .= '<hr>';
                    }
                }
            }
            
            $resp['status'] = 'success';
            $resp['html'] = $html;
        }
        
        return json_encode($resp);
    }

    function receive_stock_batch(){
        header('Content-Type: application/json');
        $resp = array('status' => 'failed', 'msg' => '', 'barcodes' => []);
    
        try {
            extract($_POST);
            $po_id = (int)$_POST['po_id'];
        
            // Get items data
            $item_ids = isset($_POST['item_id']) ? (array)$_POST['item_id'] : [];
            $received_quantities = isset($_POST['received_qty']) ? (array)$_POST['received_qty'] : [];
            $remarks_array = isset($_POST['remarks']) ? (array)$_POST['remarks'] : [];
        
            if (empty($item_ids)) {
                throw new Exception('Please select at least one item to receive');
            }
        
            // Check if stock_movement table exists, create if not
            $check_table = $this->conn->query("SHOW TABLES LIKE 'stock_movement'");
            if ($check_table->num_rows == 0) {
                $this->conn->query("CREATE TABLE stock_movement (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    item_id INT NOT NULL,
                    movement_type ENUM('IN', 'OUT') NOT NULL,
                    quantity INT NOT NULL,
                    reference_type VARCHAR(50),
                    reference_id INT,
                    barcode_id INT,
                    serial_number_id INT,
                    balance_before INT,
                    balance_after INT,
                    remarks TEXT,
                    created_by INT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX(item_id), INDEX(reference_type), INDEX(barcode_id)
                )");
            }
            
            $count = 0;
            $generated_barcodes = [];
            
            // Process each item
            foreach($item_ids as $idx => $item_id) {
                $item_id = (int)$item_id;
                $received_qty = (int)($received_quantities[$idx] ?? 0);
                $remark = trim($remarks_array[$idx] ?? '');
            
                if ($received_qty <= 0) {
                    continue; // Skip if no quantity
                }
            
                // Verify PO item exists
                $po_item_result = $this->conn->query("SELECT * FROM po_items WHERE po_id = $po_id AND item_id = $item_id");
                if (!$po_item_result || $po_item_result->num_rows == 0) {
                    continue; // Skip invalid items
                }
            
                // Insert into stock_movement for logging
                $ms = $this->conn->prepare("INSERT INTO stock_movement 
                    (item_id, movement_type, quantity, reference_type, reference_id, remarks, created_by)
                    VALUES (?, 'IN', ?, 'PO', ?, ?, ?)");
            
                if (!$ms) {
                    throw new Exception("Prepare failed: " . $this->conn->error);
                }
            
                $ms->bind_param('iiisi', $item_id, $received_qty, $po_id, $remark, $_SESSION['userdata']['id']);
            
                if ($ms->execute()) {
                    $count++;
                } else {
                    throw new Exception("Failed to log item $item_id: " . $ms->error);
                }
            }
        
            if ($count == 0) {
                throw new Exception('No valid quantities to receive');
            }
        
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Successfully received $count item(s) against PO #$po_id!");
        
        } catch (Exception $e) {
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
    
        return json_encode($resp);
    }

    function receive_stock_manual(){
        header('Content-Type: application/json');
        $resp = array('status' => 'failed', 'msg' => '', 'barcodes' => []);
        try{
            // Ensure items present
            $item_ids = isset($_POST['item_id']) ? (array)$_POST['item_id'] : [];
            $received_quantities = isset($_POST['received_qty']) ? (array)$_POST['received_qty'] : [];
            $remarks_array = isset($_POST['remarks']) ? (array)$_POST['remarks'] : [];
            if (empty($item_ids)) {
                throw new Exception('Please add at least one item');
            }

            // Ensure stock_movement exists
            $check_table = $this->conn->query("SHOW TABLES LIKE 'stock_movement'");
            if ($check_table->num_rows == 0) {
                $this->conn->query("CREATE TABLE stock_movement (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    item_id INT NOT NULL,
                    movement_type ENUM('IN', 'OUT') NOT NULL,
                    quantity INT NOT NULL,
                    reference_type VARCHAR(50),
                    reference_id INT,
                    barcode_id INT,
                    serial_number_id INT,
                    balance_before INT,
                    balance_after INT,
                    remarks TEXT,
                    created_by INT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX(item_id), INDEX(reference_type), INDEX(barcode_id)
                )");
            }
            
            $count = 0;
            $generated_barcodes = [];
            
            foreach($item_ids as $idx => $raw_item_id){
                $item_id = (int)$raw_item_id;
                $received_qty = (int)($received_quantities[$idx] ?? 0);
                $remark = trim($remarks_array[$idx] ?? '');
                if($received_qty <= 0){
                    continue;
                }

                // Verify item exists
                $item_chk = $this->conn->query("SELECT id FROM item_list WHERE id = {$item_id}");
                if(!$item_chk || $item_chk->num_rows == 0){
                    continue;
                }

                $ms = $this->conn->prepare("INSERT INTO stock_movement (item_id, movement_type, quantity, reference_type, reference_id, remarks, created_by) VALUES (?, 'IN', ?, 'MANUAL', NULL, ?, ?)");
                if(!$ms){
                    throw new Exception('Prepare failed: ' . $this->conn->error);
                }
                $ms->bind_param('iisi', $item_id, $received_qty, $remark, $_SESSION['userdata']['id']);
                if($ms->execute()){
                    $count++;
                }else{
                    throw new Exception('Failed to log item ' . $item_id . ': ' . $ms->error);
                }
            }

            if($count == 0){
                throw new Exception('No valid quantities to receive');
            }

            $resp['status'] = 'success';
            $this->settings->set_flashdata('success', "Successfully received $count item(s) (Manual Receipt)!");
        }catch(Exception $e){
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        }
        return json_encode($resp);
    }

    function save_received_barcode(){
        header('Content-Type: application/json');
        $resp = ['status' => 'failed', 'msg' => 'An error occurred'];
        
        try {
            // Validate input
            if(empty($_POST['barcode_code']) || empty($_POST['item_id']) || empty($_POST['quantity'])) {
                $resp['msg'] = 'Missing required fields';
                return json_encode($resp);
            }
            
            $barcode_code = $this->conn->real_escape_string($_POST['barcode_code']);
            $item_id = intval($_POST['item_id']);
            $quantity = intval($_POST['quantity']);
            $reference_type = $this->conn->real_escape_string($_POST['reference_type']);
            $po_id = !empty($_POST['po_id']) ? intval($_POST['po_id']) : NULL;
            $remarks = !empty($_POST['remarks']) ? $this->conn->real_escape_string($_POST['remarks']) : '';
            $created_by = $_SESSION['userdata']['id'];
            
            // Insert barcode record
            $sql = "INSERT INTO item_barcodes (barcode_code, item_id, quantity, reference_type, po_id, created_by, created_at) 
                    VALUES ('$barcode_code', $item_id, $quantity, '$reference_type', " . ($po_id ? $po_id : 'NULL') . ", $created_by, NOW())";
            
            if(!$this->conn->query($sql)) {
                $resp['msg'] = 'Failed to save barcode: ' . $this->conn->error;
                return json_encode($resp);
            }
            
            $barcode_id = $this->conn->insert_id;
            
            // Insert stock movement record
            $movement_sql = "INSERT INTO stock_movement (item_id, quantity, reference_type, reference_id, barcode_id, movement_type, remarks, created_by, created_at) 
                            VALUES ($item_id, $quantity, '$reference_type', " . ($po_id ? $po_id : 'NULL') . ", $barcode_id, 'IN', '$remarks', $created_by, NOW())";
            
            if(!$this->conn->query($movement_sql)) {
                $resp['msg'] = 'Failed to record stock movement: ' . $this->conn->error;
                return json_encode($resp);
            }
            
            $resp['status'] = 'success';
            $resp['barcode_id'] = $barcode_id;
            $resp['barcode_code'] = $barcode_code;
            
        } catch (Exception $e) {
            $resp['msg'] = 'Exception: ' . $e->getMessage();
            error_log('save_received_barcode error: ' . $e->getMessage());
        }
        
        return json_encode($resp);
    }
}
$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_project':
		echo $Master->save_project();
	break;
	case 'delete_project':
		echo $Master->delete_project();
	break;
	case 'save_project_phase':
		echo $Master->save_project_phase();
	break;
	case 'delete_project_phase':
		echo $Master->delete_project_phase();
	break;
    case 'save_phase_activity':
        echo $Master->save_phase_activity();
    break;
	case 'delete_phase_activity':
		echo $Master->delete_phase_activity();
	break;
	case 'get_po_items':
		echo $Master->get_po_items();
	break;
	case 'save_project_item':
		echo $Master->save_project_item();
	break;
	case 'delete_project_item':
		echo $Master->delete_project_item();
	break;
	case 'get_item_activities':
		echo $Master->get_item_activities();
	break;
	case 'save_item_activity':
		echo $Master->save_item_activity();
	break;
	case 'delete_item_activity':
		echo $Master->delete_item_activity();
	break;
	case 'save_project_activity':
		echo $Master->save_project_activity();
	break;
	case 'delete_project_activity':
		echo $Master->delete_project_activity();
	break;
	case 'get_project_items_for_activity':
		echo $Master->get_project_items_for_activity();
	break;
	case 'get_po_item_for_project':
		echo $Master->get_po_item_for_project();
	break;
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
    case 'get_users':
        echo $Master->get_users();
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
    case 'create_tasks_from_sheet':
        echo $Master->create_tasks_from_sheet();
    break;
    case 'get_project_tasks':
        echo $Master->get_project_tasks();
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
    case 'save_project_sheet':
        echo $Master->save_project_sheet();
    break;
    case 'get_project_sheet':
        echo $Master->get_project_sheet();
    break;
    case 'get_project_sheets':
        echo $Master->get_project_sheets();
    break;
    case 'delete_project_sheet':
        echo $Master->delete_project_sheet();
    break;
    case 'rename_project_sheet':
        echo $Master->rename_project_sheet();
    break;
    case 'get_po_detail_specs':
        echo $Master->get_po_detail_specs();
    break;
    case 'receive_stock_batch':
        echo $Master->receive_stock_batch();
    break;
    case 'receive_stock_manual':
        echo $Master->receive_stock_manual();
    break;
    case 'save_received_barcode':
        echo $Master->save_received_barcode();
    break;
    case 'delete_receipt':
        echo $Master->delete_receipt();
    break;
	default:
		// echo $sysset->index();
	break;
}