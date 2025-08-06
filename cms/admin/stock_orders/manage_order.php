<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // Only include config if $conn is not available (AJAX request)
    if (!isset($conn)) {
        require_once('../../config.php');
    }
    
    // Set content type for JSON response
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'get_purchase_orders' && isset($_POST['supplier_id'])) {
        $supplier_id = $_POST['supplier_id'];
        $current_order_id = isset($_POST['current_order_id']) ? $_POST['current_order_id'] : null;
        
        // Debug: Log the incoming request
        error_log("=== PO REQUEST DEBUG ===");
        error_log("Action: " . $_POST['action']);
        error_log("Supplier ID: " . $supplier_id);
        error_log("Current Order ID: " . $current_order_id);
        
        // Get purchase orders for the selected supplier, excluding those already used in stock orders
        $query = "SELECT pol.id, pol.po_code 
                  FROM purchase_order_list pol 
                  WHERE pol.supplier_id = ? 
                  AND pol.po_code NOT IN (
                      SELECT so.po_code 
                      FROM stock_orders so 
                      WHERE so.po_code IS NOT NULL
                      " . ($current_order_id ? " AND so.id != ?" : "") . "
                  )
                  ORDER BY pol.po_code";
        
        error_log("SQL Query: " . $query);
        
        $stmt = $conn->prepare($query);
        if ($current_order_id) {
            $stmt->bind_param("ii", $supplier_id, $current_order_id);
        } else {
            $stmt->bind_param("i", $supplier_id);
        }
        $stmt->execute();
        
        // Use bind_result instead of get_result for compatibility
        $stmt->bind_result($id, $po_code);
        
        $pos = [];
        while ($stmt->fetch()) {
            $pos[] = [
                'id' => $id,
                'po_code' => $po_code
            ];
        }
        $stmt->close();
        
        // Debug logging
        error_log("Found POs: " . count($pos));
        error_log("PO Data: " . json_encode($pos));
        error_log("=== END PO REQUEST DEBUG ===");
        
        echo json_encode($pos);
        exit;
    }
    
    if ($_POST['action'] === 'get_po_items' && isset($_POST['po_code'])) {
        $po_code = $_POST['po_code'];
        
        // Get PO items for the selected purchase order
        $query = "SELECT poi.item_id, i.name as item_name, poi.quantity, poi.amount, poi.total_amount 
                  FROM po_items poi 
                  INNER JOIN item_list i ON poi.item_id = i.id 
                  INNER JOIN purchase_order_list pol ON poi.po_id = pol.id 
                  WHERE pol.po_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $po_code);
        $stmt->execute();
        
        // Use bind_result instead of get_result for compatibility
        $stmt->bind_result($item_id, $item_name, $quantity, $amount, $total_amount);
        
        $items = [];
        while ($stmt->fetch()) {
            // Calculate total price (quantity × unit price)
            $calculated_total = $quantity * $amount;
            
            $items[] = [
                'item_id' => $item_id,
                'item_name' => $item_name,
                'quantity' => $quantity,
                'unit' => 'PCS', // Default unit since po_items doesn't have unit column
                'unit_price' => $amount,
                'total_price' => $calculated_total, // Calculated total (quantity × unit price)
                'negotiated_total' => $total_amount, // PO's total_amount (with discounts) as negotiated price
                'remarks' => '' // No remarks column in po_items
            ];
        }
        $stmt->close();
        
        // Debug logging
        error_log("PO Items Request - PO Code: $po_code, Found Items: " . count($items));
        
        echo json_encode($items);
        exit;
    }
    
         if ($_POST['action'] === 'get_supplier_items' && isset($_POST['supplier_id'])) {
         $supplier_id = $_POST['supplier_id'];
         
         // Get items for the selected supplier
         $query = "SELECT id, name FROM item_list WHERE supplier_id = ? AND status = 1 ORDER BY name";
         $stmt = $conn->prepare($query);
         $stmt->bind_param("i", $supplier_id);
         $stmt->execute();
         
         // Use bind_result instead of get_result for compatibility
         $stmt->bind_result($item_id, $item_name);
         
         $items = [];
         while ($stmt->fetch()) {
             $items[] = [
                 'id' => $item_id,
                 'name' => $item_name
             ];
         }
         $stmt->close();
         
         // Debug logging
         error_log("Supplier Items Request - Supplier ID: $supplier_id, Found Items: " . count($items));
         
         echo json_encode($items);
         exit;
     }
     
     if ($_POST['action'] === 'get_next_order_number' && isset($_POST['channel_prefix'])) {
         $channel_prefix = $_POST['channel_prefix'];
         
         // Get the next order number for this channel prefix
         $query = "SELECT order_code FROM stock_orders 
                   WHERE order_code LIKE ? 
                   ORDER BY CAST(SUBSTRING(order_code, 4) AS UNSIGNED) DESC 
                   LIMIT 1";
         $stmt = $conn->prepare($query);
         $pattern = $channel_prefix . '-%';
         $stmt->bind_param("s", $pattern);
         $stmt->execute();
         
         // Use bind_result instead of get_result for compatibility
         $stmt->bind_result($order_code_result);
         
         $nextNumber = 1;
         if ($stmt->fetch()) {
             // Extract the number part after the prefix and dash
             $numericPart = intval(substr($order_code_result, strlen($channel_prefix) + 1));
             $nextNumber = $numericPart + 1;
         }
         $stmt->close();
         
         // Format the number with leading zeros (3 digits)
         $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
         
         echo json_encode(['next_number' => $formattedNumber]);
         exit;
     }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $order_code = $_POST['order_code'];
    $channel = $_POST['channel'];
    $order_date = $_POST['order_date'];
    $supplier_id = $_POST['supplier_id'];
    $work_order_numbers = isset($_POST['work_order_numbers']) ? explode(', ', $_POST['work_order_numbers']) : [];
    $order_type = $_POST['order_type'];
    $remarks = $_POST['remarks'];
    $po_code = null; // Initialize po_code variable
    
    // Handle PO number for purchase order channel
    if ($channel === 'purchase_order' && isset($_POST['po_code'])) {
        // For purchase order channel, we still need to generate a serial order code
        // The PO number will be stored in po_code column for proper linking
        if (empty($id)) {
            $channel_prefix = 'PO';
            
            // Get the next order number for this channel prefix
            $query = "SELECT order_code FROM stock_orders 
                      WHERE order_code LIKE ? 
                      ORDER BY CAST(SUBSTRING(order_code, 4) AS UNSIGNED) DESC 
                      LIMIT 1";
            $stmt = $conn->prepare($query);
            $pattern = $channel_prefix . '-%';
            $stmt->bind_param("s", $pattern);
            $stmt->execute();
            
            // Use bind_result instead of get_result for compatibility
            $stmt->bind_result($order_code_result);
            
            $nextNumber = 1;
            if ($stmt->fetch()) {
                // Extract the number part after the prefix and dash
                $numericPart = intval(substr($order_code_result, strlen($channel_prefix) + 1));
                $nextNumber = $numericPart + 1;
            }
            $stmt->close();
            
            // Format the number with leading zeros (3 digits)
            $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $order_code = "{$channel_prefix}-{$formattedNumber}";
            
            // Store the PO number in po_code column for proper linking
            $po_code = $_POST['po_code'];
        } else {
            // For existing records, just update the po_code
            $po_code = $_POST['po_code'];
        }
    } else {
                 // Auto-generate order code for new records based on channel
         if (empty($id)) {
             // Get channel prefix
             $channel_prefix = '';
             switch($channel) {
                 case 'purchase_order':
                     $channel_prefix = 'PO';
                     break;
                 case 'whatsapp':
                     $channel_prefix = 'WA';
                     break;
                 case 'phone_call':
                     $channel_prefix = 'PC';
                     break;
                 case 'email':
                     $channel_prefix = 'EM';
                     break;
                 case 'other':
                     $channel_prefix = 'OT';
                     break;
                 default:
                     $channel_prefix = 'SO';
             }
             
             // Get the next order number for this channel prefix
             $query = "SELECT order_code FROM stock_orders 
                       WHERE order_code LIKE ? 
                       ORDER BY CAST(SUBSTRING(order_code, 4) AS UNSIGNED) DESC 
                       LIMIT 1";
             $stmt = $conn->prepare($query);
             $pattern = $channel_prefix . '-%';
             $stmt->bind_param("s", $pattern);
                         $stmt->execute();
            
            // Use bind_result instead of get_result for compatibility
            $stmt->bind_result($order_code_result);
            
            $nextNumber = 1;
            if ($stmt->fetch()) {
                // Extract the number part after the prefix and dash
                $numericPart = intval(substr($order_code_result, strlen($channel_prefix) + 1));
                $nextNumber = $numericPart + 1;
            }
            $stmt->close();
             
             // Format the number with leading zeros (3 digits)
             $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
             $order_code = "{$channel_prefix}-{$formattedNumber}";
         }
    }

    if (empty($id)) {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO stock_orders (
            order_code, po_code, channel, order_date, supplier_id, work_order_number, 
            order_type, total_amount, negotiated_amount, remarks, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $created_by = $_SESSION['userdata']['id'];
        $total_amount = 0; // Will be calculated from items
        $negotiated_amount = 0; // Will be calculated from items
        $work_order_number = implode(', ', $work_order_numbers); // Convert array to comma-separated string

        $stmt->bind_param(
            "ssssisssdsi",
            $order_code, $po_code, $channel, $order_date, $supplier_id, $work_order_number,
            $order_type, $total_amount, $negotiated_amount, $remarks, $created_by
        );
    } else {
        // Update existing record
        $stmt = $conn->prepare("UPDATE stock_orders SET 
            order_code = ?, po_code = ?, channel = ?, order_date = ?, supplier_id = ?, 
            work_order_number = ?, order_type = ?, remarks = ? 
            WHERE id = ?");

        $work_order_number = implode(', ', $work_order_numbers); // Convert array to comma-separated string
        
        $stmt->bind_param(
            "ssssisssi",
            $order_code, $po_code, $channel, $order_date, $supplier_id, 
            $work_order_number, $order_type, $remarks, $id
        );
    }

    if ($stmt->execute()) {
        if (empty($id)) {
            $id = $conn->insert_id;
        }

        // Handle items
        $conn->query("DELETE FROM stock_order_items WHERE order_id = '$id'");
        
        $total_amount = 0;
        $negotiated_amount = 0;
        
        if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
            foreach ($_POST['item_id'] as $index => $item_id) {
                $quantity = $_POST['quantity'][$index];
                $unit = $_POST['unit'][$index];
                $unit_price = $_POST['unit_price'][$index];
                $total_price = $_POST['total_price'][$index];
                $negotiated_total = $_POST['negotiated_total'][$index];
                $item_remarks = $_POST['item_remarks'][$index];
                
                $stmt = $conn->prepare("INSERT INTO stock_order_items (
                    order_id, item_id, quantity, unit, unit_price, total_price, 
                    negotiated_total, remarks
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param(
                    "iissddds",
                    $id, $item_id, $quantity, $unit, $unit_price, $total_price,
                    $negotiated_total, $item_remarks
                );
                $stmt->execute();
                
                $total_amount += $total_price;
                $negotiated_amount += $negotiated_total;
            }
        }
        
        // Update total amounts
        $conn->query("UPDATE stock_orders SET total_amount = $total_amount, negotiated_amount = $negotiated_amount WHERE id = $id");

        $success_message = 'Stock order saved successfully.';
        echo '<script>
                sessionStorage.setItem("success_message", "Stock order saved successfully.");
                window.location.href = "' . base_url . 'admin/?page=stock_orders/view_order&id=' . $id . '";
              </script>';
        exit;
    } else {
        $success_message = 'Error: ' . $stmt->error;
    }
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $qry = $conn->query("SELECT * FROM stock_orders WHERE id = $id");
    if ($qry->num_rows > 0) {
        $result = $qry->fetch_assoc();
        $order_code = $result['order_code'];
        $channel = $result['channel'];
        $order_date = $result['order_date'];
        $supplier_id = $result['supplier_id'];
        $work_order_number = $result['work_order_number'];
        $order_type = $result['order_type'];
        $remarks = $result['remarks'];
    }

    // Fetch items
    $item_query = $conn->query("SELECT soi.*, i.name as item_name 
                                FROM stock_order_items soi 
                                INNER JOIN item_list i ON soi.item_id = i.id 
                                WHERE soi.order_id = $id");
    $item_list = [];
    while($row = $item_query->fetch_assoc()) {
        $item_list[] = $row;
    }
}

// Fetch suppliers
$suppliers = $conn->query("SELECT id, name FROM supplier_list WHERE status = 1");

// Fetch work orders (proforma invoices with work order numbers)
$work_orders = $conn->query("SELECT work_order_number FROM proforma_invoice_list WHERE work_order_number IS NOT NULL");

// Fetch items (will be filtered by supplier via AJAX)
$items = $conn->query("SELECT id, name FROM item_list WHERE status = 1");
?>

<style>
.item-table {
    margin-top: 20px;
}
.item-row {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 10px;
}
.remove-item {
    color: #dc3545;
    cursor: pointer;
    display: block !important;
    opacity: 1 !important;
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}
.remove-item:hover {
    background-color: #c82333 !important;
    border-color: #bd2130 !important;
}
.remove-item i {
    color: white !important;
    display: inline-block !important;
    opacity: 1 !important;
}
.calculate-total {
    font-weight: bold;
    color: #28a745;
}
.work-order-dropdown {
    position: relative;
}
.work-order-dropdown .dropdown-menu {
    max-height: 300px;
    overflow-y: auto;
}
.work-order-dropdown .dropdown-item {
    padding: 8px 15px;
    border-bottom: 1px solid #eee;
}
.work-order-dropdown .dropdown-item:last-child {
    border-bottom: none;
}
.work-order-dropdown .form-check {
    margin: 0;
}
.work-order-dropdown .form-check-label {
    cursor: pointer;
    width: 100%;
    margin: 0;
}
.work-order-dropdown .form-check-input {
    margin-right: 8px;
}
</style>

<form id="manage-stock-order" action="<?php echo base_url ?>classes/Master.php?f=save_stock_order" method="POST">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="order_code" id="order_code_label">Order Code</label>
                <input type="text" name="order_code" id="order_code" class="form-control" value="<?php echo isset($order_code) ? $order_code : ''; ?>" readonly>
                <div id="po_dropdown_container" style="display: none;">
                    <select name="po_code" id="po_number" class="form-control">
                        <option value="">Select Purchase Order</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="channel">Channel <span class="text-danger">*</span></label>
                <select name="channel" id="channel" class="form-control" required>
                    <option value="">Select Channel</option>
                    <option value="purchase_order" <?php echo (isset($channel) && $channel == 'purchase_order') ? 'selected' : ''; ?>>Purchase Order</option>
                    <option value="whatsapp" <?php echo (isset($channel) && $channel == 'whatsapp') ? 'selected' : ''; ?>>WhatsApp</option>
                    <option value="phone_call" <?php echo (isset($channel) && $channel == 'phone_call') ? 'selected' : ''; ?>>Phone Call</option>
                    <option value="email" <?php echo (isset($channel) && $channel == 'email') ? 'selected' : ''; ?>>Email</option>
                    <option value="other" <?php echo (isset($channel) && $channel == 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="order_date">Order Date <span class="text-danger">*</span></label>
                <input type="date" name="order_date" id="order_date" class="form-control" value="<?php echo isset($order_date) ? $order_date : date('Y-m-d'); ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="supplier_id">Supplier <span class="text-danger">*</span></label>
                <select name="supplier_id" id="supplier_id" class="custom-select select2" required>
                    <option value="">Select Supplier</option>
                    <?php while($supplier = $suppliers->fetch_assoc()): ?>
                        <option value="<?php echo $supplier['id']; ?>" <?php echo (isset($supplier_id) && $supplier_id == $supplier['id']) ? 'selected' : ''; ?>>
                            <?php echo $supplier['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="work_order_numbers">Work Order Numbers</label>
                                 <div class="work-order-dropdown">
                     <button type="button" class="btn btn-outline-secondary w-100 text-left" id="workOrderDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                         <span id="selectedWorkOrdersText">Select Work Orders</span>
                         <span class="float-right"><i class="fas fa-chevron-down"></i></span>
                     </button>
                     <div class="dropdown-menu w-100" id="workOrderDropdownMenu">
                         <?php 
                         $selected_work_orders = isset($work_order_number) ? explode(', ', $work_order_number) : [];
                         $work_orders->data_seek(0);
                         while($wo = $work_orders->fetch_assoc()): 
                             // Get corresponding PO code
                             $po_query = $conn->query("SELECT po_code FROM proforma_invoice_list WHERE work_order_number = '{$wo['work_order_number']}' LIMIT 1");
                             $po_code = $po_query->num_rows > 0 ? $po_query->fetch_assoc()['po_code'] : 'N/A';
                         ?>
                             <div class="dropdown-item">
                                 <div class="form-check">
                                     <input class="form-check-input work-order-checkbox" type="checkbox" 
                                            value="<?php echo $wo['work_order_number']; ?>" 
                                            id="wo_<?php echo $wo['work_order_number']; ?>"
                                            <?php echo in_array($wo['work_order_number'], $selected_work_orders) ? 'checked' : ''; ?>>
                                     <label class="form-check-label" for="wo_<?php echo $wo['work_order_number']; ?>">
                                         <strong><?php echo $wo['work_order_number']; ?></strong>
                                         <span class="text-muted ml-2">(PO: <?php echo $po_code; ?>)</span>
                                     </label>
                                 </div>
                             </div>
                         <?php endwhile; ?>
                     </div>
                 </div>
                <input type="hidden" name="work_order_numbers" id="work_order_numbers_input" value="<?php echo isset($work_order_number) ? $work_order_number : ''; ?>">
                <small class="form-text text-muted">Click to select multiple work orders</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="order_type">Order Type <span class="text-danger">*</span></label>
                <select name="order_type" id="order_type" class="form-control" required>
                    <option value="">Select Order Type</option>
                    <option value="work_order_specific" <?php echo (isset($order_type) && $order_type == 'work_order_specific') ? 'selected' : ''; ?>>Work Order Specific</option>
                    <option value="bulk_order" <?php echo (isset($order_type) && $order_type == 'bulk_order') ? 'selected' : ''; ?>>Bulk Order</option>
                    <option value="stocking" <?php echo (isset($order_type) && $order_type == 'stocking') ? 'selected' : ''; ?>>Stocking</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3"><?php echo isset($remarks) ? $remarks : ''; ?></textarea>
            </div>
        </div>
    </div>
    
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Order Items</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-primary" id="add-item">Add Item</button>
            </div>
        </div>
        <div class="card-body">
            <div id="items-container">
                <?php if (isset($item_list) && count($item_list) > 0): ?>
                    <?php foreach ($item_list as $index => $item): ?>
                        <div class="item-row" data-index="<?php echo $index; ?>">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Item</label>
                                    <select name="item_id[]" class="form-control item-select" required>
                                        <option value="">Select Item</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Quantity</label>
                                    <input type="number" name="quantity[]" class="form-control quantity" value="<?php echo $item['quantity']; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit</label>
                                    <input type="text" name="unit[]" class="form-control" value="<?php echo $item['unit']; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price</label>
                                    <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" value="<?php echo $item['unit_price']; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total Price</label>
                                    <input type="number" step="0.01" name="total_price[]" class="form-control total-price" value="<?php echo $item['total_price']; ?>" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-sm btn-danger remove-item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label>Negotiated Total</label>
                                    <input type="number" step="0.01" name="negotiated_total[]" class="form-control negotiated-total" value="<?php echo $item['negotiated_total']; ?>" required>
                                </div>
                                <div class="col-md-9">
                                    <label>Remarks</label>
                                    <input type="text" name="item_remarks[]" class="form-control" value="<?php echo $item['remarks']; ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="item-row" data-index="0">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Item</label>
                                <select name="item_id[]" class="form-control item-select" required>
                                    <option value="">Select Item</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Quantity</label>
                                <input type="number" name="quantity[]" class="form-control quantity" required>
                            </div>
                            <div class="col-md-2">
                                <label>Unit</label>
                                <input type="text" name="unit[]" class="form-control" value="PCS" required>
                            </div>
                            <div class="col-md-2">
                                <label>Unit Price</label>
                                <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" required>
                            </div>
                            <div class="col-md-2">
                                <label>Total Price</label>
                                <input type="number" step="0.01" name="total_price[]" class="form-control total-price" readonly>
                            </div>
                            <div class="col-md-1">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-danger remove-item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <label>Negotiated Total</label>
                                <input type="number" step="0.01" name="negotiated_total[]" class="form-control negotiated-total" required>
                            </div>
                            <div class="col-md-9">
                                <label>Remarks</label>
                                <input type="text" name="item_remarks[]" class="form-control">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-primary">Save Order</button>
            <a href="<?php echo base_url ?>admin/?page=stock_orders" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
<script>
$(document).ready(function(){
    // Initialize select2 for supplier dropdown
    $('.select2').select2({placeholder:"Please Select here",width:"relative"});
    
    let itemIndex = <?php echo isset($item_list) ? count($item_list) : 1; ?>;
    
    // Generate order code when channel changes
    $('#channel').change(function(){
        const channel = $(this).val();
        
        // Clear existing values
        $('#order_code').val('');
        $('#po_number').empty().append('<option value="">Select Purchase Order</option>');
        $('#items-container').empty();
        
        if(channel === 'purchase_order') {
            // Show PO dropdown and hide order code input
            $('#order_code').hide();
            $('#po_dropdown_container').show();
            $('#order_code_label').text('Purchase Order Number');
            
            // Generate order code for purchase order channel too
            if(channel) {
                getNextOrderNumber('PO', function(nextNumber) {
                    $('#order_code').val(`PO-${nextNumber}`);
                });
            }
        } else {
            // Show regular order code input and hide PO dropdown
            $('#order_code').show();
            $('#po_dropdown_container').hide();
            $('#order_code_label').text('Order Code');
            
            if(channel) {
                // Generate order code based on channel
                const channelPrefix = {
                    'whatsapp': 'WA',
                    'phone_call': 'PC',
                    'email': 'EM',
                    'other': 'OT'
                }[channel] || 'SO';
                
                // Get the next serial number for this channel
                getNextOrderNumber(channelPrefix, function(nextNumber) {
                    $('#order_code').val(`${channelPrefix}-${nextNumber}`);
                });
            }
        }
        
        // Reset supplier selection and items
        $('#supplier_id').val('');
        $('.item-select').prop('disabled', true);
        
        // Add default empty item row
        if($('#items-container').is(':empty')) {
            addDefaultItemRow();
        }
    });
    
    // Filter items by supplier
    $('#supplier_id').change(function(){
        const supplierId = $(this).val();
        const channel = $('#channel').val();
        
        if(supplierId) {
            // Enable item selection
            $('.item-select').prop('disabled', false);
            
            // If purchase order channel, load PO numbers for selected supplier
            if(channel === 'purchase_order') {
                loadPurchaseOrders(supplierId);
            } else {
                // For other channels, load items for the selected supplier
                loadSupplierItems(supplierId);
            }
        } else {
            // Disable item selection if no supplier selected
            $('.item-select').prop('disabled', true);
            
            // Clear PO dropdown if purchase order channel
            if(channel === 'purchase_order') {
                $('#po_number').empty().append('<option value="">Select Purchase Order</option>');
            }
            
            // Clear items container
            $('#items-container').empty();
            addDefaultItemRow();
        }
    });
    
    // Load items for selected supplier
    function loadSupplierItems(supplierId) {
        $.ajax({
            url: '<?php echo base_url ?>admin/stock_orders/manage_order.php',
            method: 'POST',
            data: {
                action: 'get_supplier_items',
                supplier_id: supplierId
            },
            success: function(items){
                $('.item-select').each(function(){
                    const select = $(this);
                    const currentValue = select.val();
                    select.empty().append('<option value="">Select Item</option>');
                    
                    if(items && items.length > 0) {
                        items.forEach(function(item){
                            const selected = (item.id == currentValue) ? 'selected' : '';
                            select.append(`<option value="${item.id}" ${selected}>${item.name}</option>`);
                        });
                    }
                });
                
                // Enable item selection after loading items
                $('.item-select').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading supplier items:', error, xhr.responseText);
            }
        });
    }
    
         // Load purchase orders for selected supplier
     function loadPurchaseOrders(supplierId) {
         $.ajax({
             url: '<?php echo base_url ?>admin/stock_orders/manage_order.php',
             method: 'POST',
             data: {
                 action: 'get_purchase_orders',
                 supplier_id: supplierId,
                 current_order_id: '<?php echo isset($id) ? $id : ""; ?>'
             },
             success: function(pos){
                 $('#po_number').empty().append('<option value="">Select Purchase Order</option>');
                  if(pos && pos.length > 0) {
                     pos.forEach(function(po){
                         $('#po_number').append(`<option value="${po.po_code}">${po.po_code}</option>`);
                     });
                 }
             },
             error: function(xhr, status, error) {
                 console.error('AJAX Error:', error, xhr.responseText);
             }
         });
     }
     
     // Load purchase orders for edit mode (preserves existing selection)
     function loadPurchaseOrdersForEdit(supplierId) {
         const currentOrderCode = $('#order_code').val();
         $.ajax({
             url: '<?php echo base_url ?>admin/stock_orders/manage_order.php',
             method: 'POST',
             data: {
                 action: 'get_purchase_orders',
                 supplier_id: supplierId,
                 current_order_id: '<?php echo isset($id) ? $id : ""; ?>'
             },
             success: function(pos){
                 $('#po_number').empty().append('<option value="">Select Purchase Order</option>');
                 if(pos && pos.length > 0) {
                     pos.forEach(function(po){
                         const selected = (po.po_code === currentOrderCode) ? 'selected' : '';
                         $('#po_number').append(`<option value="${po.po_code}" ${selected}>${po.po_code}</option>`);
                     });
                 }
                 
                 // If we have a current order code and it's in the list, load the items
                 if(currentOrderCode && pos && pos.some(po => po.po_code === currentOrderCode)) {
                     loadPOItemsForEdit(currentOrderCode);
                 }
             },
             error: function(xhr, status, error) {
                 console.error('AJAX Error:', error, xhr.responseText);
             }
         });
     }
    
    // Initialize: disable item selection if no supplier is selected
    if(!$('#supplier_id').val()) {
        $('.item-select').prop('disabled', true);
    } else {
        $('.item-select').prop('disabled', false);
    }
    
    // Check if we're in edit mode
    const isEditMode = <?php echo isset($id) ? 'true' : 'false'; ?>;
    
    if (isEditMode) {
        // In edit mode, don't trigger channel change which clears the form
        // Instead, manually set up the UI based on the current channel
        const channel = $('#channel').val();
        if(channel === 'purchase_order') {
            $('#order_code').hide();
            $('#po_dropdown_container').show();
            $('#order_code_label').text('Purchase Order Number');
            
            // Load PO dropdown if supplier is selected
            const supplierId = $('#supplier_id').val();
            if(supplierId) {
                loadPurchaseOrdersForEdit(supplierId);
            }
        } else {
            $('#order_code').show();
            $('#po_dropdown_container').hide();
            $('#order_code_label').text('Order Code');
        }
        
        // Load supplier items if supplier is selected
        const supplierId = $('#supplier_id').val();
        if(supplierId && channel !== 'purchase_order') {
            loadSupplierItems(supplierId);
        }
    } else {
        // Initialize channel change on page load only for new records
        $('#channel').trigger('change');
    }
    
    // Add default item row
    function addDefaultItemRow() {
        const newRow = `
            <div class="item-row" data-index="0">
                <div class="row">
                    <div class="col-md-3">
                        <label>Item</label>
                        <select name="item_id[]" class="form-control item-select" required disabled>
                            <option value="">Select Item</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Quantity</label>
                        <input type="number" name="quantity[]" class="form-control quantity" required>
                    </div>
                    <div class="col-md-2">
                        <label>Unit</label>
                        <input type="text" name="unit[]" class="form-control" value="PCS" required>
                    </div>
                    <div class="col-md-2">
                        <label>Unit Price</label>
                        <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" required>
                    </div>
                    <div class="col-md-2">
                        <label>Total Price</label>
                        <input type="number" step="0.01" name="total_price[]" class="form-control total-price" readonly>
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label>Negotiated Total</label>
                        <input type="number" step="0.01" name="negotiated_total[]" class="form-control negotiated-total" required>
                    </div>
                    <div class="col-md-9">
                        <label>Remarks</label>
                        <input type="text" name="item_remarks[]" class="form-control">
                    </div>
                </div>
            </div>
        `;
        $('#items-container').append(newRow);
    }
    
    // Add new item
    $('#add-item').click(function(){
        const supplierId = $('#supplier_id').val();
        if(!supplierId) {
            alert('Please select a supplier first');
            return;
        }
        
        const newRow = `
            <div class="item-row" data-index="${itemIndex}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Item</label>
                        <select name="item_id[]" class="form-control item-select" required>
                            <option value="">Select Item</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Quantity</label>
                        <input type="number" name="quantity[]" class="form-control quantity" required>
                    </div>
                    <div class="col-md-2">
                        <label>Unit</label>
                        <input type="text" name="unit[]" class="form-control" value="PCS" required>
                    </div>
                    <div class="col-md-2">
                        <label>Unit Price</label>
                        <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" required>
                    </div>
                    <div class="col-md-2">
                        <label>Total Price</label>
                        <input type="number" step="0.01" name="total_price[]" class="form-control total-price" readonly>
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-danger remove-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label>Negotiated Total</label>
                        <input type="number" step="0.01" name="negotiated_total[]" class="form-control negotiated-total" required>
                    </div>
                    <div class="col-md-9">
                        <label>Remarks</label>
                        <input type="text" name="item_remarks[]" class="form-control">
                    </div>
                </div>
            </div>
        `;
        $('#items-container').append(newRow);
        
        // Populate the new row with supplier items
        const newSelect = $('#items-container .item-row:last-child .item-select');
        $.ajax({
            url: '<?php echo base_url ?>admin/stock_orders/manage_order.php',
            method: 'POST',
            data: {
                action: 'get_supplier_items',
                supplier_id: supplierId
            },
            success: function(items){
                newSelect.empty().append('<option value="">Select Item</option>');
                if(items && items.length > 0) {
                    items.forEach(function(item){
                        newSelect.append(`<option value="${item.id}">${item.name}</option>`);
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading supplier items for new row:', error, xhr.responseText);
            }
        });
        
        itemIndex++;
    });
    
    // Handle PO selection for purchase order channel
    $(document).on('change', '#po_number', function(){
        const poCode = $(this).val();
        if(poCode) {
            loadPOItems(poCode);
        }
    });
    
         // Load PO items when PO is selected
     function loadPOItems(poCode) {
         $.ajax({
             url: '<?php echo base_url ?>admin/stock_orders/manage_order.php',
             method: 'POST',
             data: {
                 action: 'get_po_items',
                 po_code: poCode
             },
             success: function(items){
                 // Clear existing items
                 $('#items-container').empty();
                 
                 if (items && items.length > 0) {
                     // Add items from PO
                     items.forEach(function(item, index){
                         const newRow = `
                             <div class="item-row" data-index="${index}">
                                 <div class="row">
                                     <div class="col-md-3">
                                         <label>Item</label>
                                         <select name="item_id[]" class="form-control item-select" required>
                                             <option value="${item.item_id}" selected>${item.item_name}</option>
                                         </select>
                                     </div>
                                     <div class="col-md-2">
                                         <label>Quantity</label>
                                         <input type="number" name="quantity[]" class="form-control quantity" value="${item.quantity}" required>
                                     </div>
                                     <div class="col-md-2">
                                         <label>Unit</label>
                                         <input type="text" name="unit[]" class="form-control" value="${item.unit}" required>
                                     </div>
                                     <div class="col-md-2">
                                         <label>Unit Price</label>
                                         <input type="number" step="0.01" name="unit_price[]" class="form-control unit-price" value="${item.unit_price}" required>
                                     </div>
                                     <div class="col-md-2">
                                         <label>Total Price</label>
                                         <input type="number" step="0.01" name="total_price[]" class="form-control total-price" value="${item.total_price}" readonly>
                                     </div>
                                     <div class="col-md-1">
                                         <label>&nbsp;</label>
                                         <button type="button" class="btn btn-sm btn-danger remove-item">
                                             <i class="fas fa-trash"></i>
                                         </button>
                                     </div>
                                 </div>
                                                                  <div class="row mt-2">
                                     <div class="col-md-3">
                                         <label>Negotiated Total</label>
                                         <input type="number" step="0.01" name="negotiated_total[]" class="form-control negotiated-total" value="${item.negotiated_total}" required>
                                     </div>
                                     <div class="col-md-9">
                                         <label>Remarks</label>
                                         <input type="text" name="item_remarks[]" class="form-control" value="${item.remarks || ''}">
                                     </div>
                                 </div>
                             </div>
                         `;
                         $('#items-container').append(newRow);
                     });
                     itemIndex = items.length;
                 } else {
                     addDefaultItemRow();
                     itemIndex = 1;
                 }
             },
             error: function(xhr, status, error) {
                 console.error('AJAX Error loading PO items:', error, xhr.responseText);
             }
         });
     }
     
     // Load PO items for edit mode (doesn't clear existing items if they exist)
     function loadPOItemsForEdit(poCode) {
         // Only load PO items if the items container is empty (no existing items)
         if($('#items-container').is(':empty') || $('#items-container .item-row').length === 0) {
             loadPOItems(poCode);
         }
     }
    
    // Remove item
    $(document).on('click', '.remove-item', function(){
        if($('.item-row').length > 1) {
            $(this).closest('.item-row').remove();
        }
    });
    
    // Calculate totals
    $(document).on('input', '.quantity, .unit-price', function(){
        const row = $(this).closest('.item-row');
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        const totalPrice = quantity * unitPrice;
        row.find('.total-price').val(totalPrice.toFixed(2));
    });
    
    // Work Order Dropdown functionality
    $(document).on('change', '.work-order-checkbox', function(){
        updateSelectedWorkOrders();
    });
    
    // Prevent dropdown from closing when clicking on checkboxes
    $(document).on('click', '.work-order-dropdown .dropdown-item', function(e){
        e.stopPropagation();
    });
    
    // Update selected work orders text and hidden input
    function updateSelectedWorkOrders() {
        const selectedWorkOrders = [];
        $('.work-order-checkbox:checked').each(function(){
            selectedWorkOrders.push($(this).val());
        });
        
        if(selectedWorkOrders.length > 0) {
            $('#selectedWorkOrdersText').text(selectedWorkOrders.join(', '));
        } else {
            $('#selectedWorkOrdersText').text('Select Work Orders');
        }
        
        $('#work_order_numbers_input').val(selectedWorkOrders.join(', '));
    }
    
         // Initialize work order selection
     updateSelectedWorkOrders();
     
     // In edit mode, ensure work order checkboxes are properly checked
     if (isEditMode) {
         const selectedWorkOrders = '<?php echo isset($work_order_number) ? $work_order_number : ""; ?>';
         if (selectedWorkOrders) {
             const workOrderArray = selectedWorkOrders.split(', ');
             workOrderArray.forEach(function(wo) {
                 $(`input[value="${wo.trim()}"]`).prop('checked', true);
             });
             updateSelectedWorkOrders();
         }
     }
     
     // Function to get next order number for a channel
     function getNextOrderNumber(channelPrefix, callback) {
         $.ajax({
             url: '<?php echo base_url ?>admin/stock_orders/manage_order.php',
             method: 'POST',
             data: {
                 action: 'get_next_order_number',
                 channel_prefix: channelPrefix
             },
             success: function(response){
                 if(response && response.next_number) {
                     callback(response.next_number);
                 } else {
                     callback('001'); // Default if no response
                 }
             },
             error: function(xhr, status, error) {
                 console.error('AJAX Error getting next order number:', error, xhr.responseText);
                 callback('001'); // Default on error
             }
         });
     }
     
     // Form submission handling
     $('#manage-stock-order').submit(function(e){
         e.preventDefault();
         var _this = $(this);
         $('.err-msg').remove();
         start_loader();
         $.ajax({
             url: _this.attr('action'),
             data: new FormData($(this)[0]),
             cache: false,
             contentType: false,
             processData: false,
             method: 'POST',
             type: 'POST',
             dataType: 'json',
             error: err => {
                 console.log(err);
                 alert_toast("An error occurred", 'error');
                 end_loader();
             },
            success: function(resp){
                if (typeof resp == 'object' && resp.status == 'success') {
                    // Use the redirect URL from the response
                    location.href = resp.redirect;
                } else if (resp.status == 'failed' && !!resp.msg) {
                    var el = $('<div>');
                    el.addClass("alert alert-danger err-msg").text(resp.msg);
                    _this.prepend(el);
                    el.show('slow');
                    $("html, body").animate({ scrollTop: _this.closest('.card').offset().top }, "fast");
                    end_loader();
                } else {
                    alert_toast("An error occurred", 'error');
                    end_loader();
                }
            }
         });
     });
    
});
</script>