<?php 

// Set timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

define('_base_url_', 'https://sbpanchal.com/cms/');

$success_message = '';
$duplicate_po_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'check_po_code') {
        $po_code = isset($_POST['po_code']) ? trim($_POST['po_code']) : '';
        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;

        $resp = ['status' => 'success', 'exists' => false];

        if ($po_code !== '') {
            if ($id > 0) {
                $check_stmt = $conn->prepare("SELECT id FROM purchase_order_list WHERE po_code = ? AND id != ? LIMIT 1");
                $check_stmt->bind_param("si", $po_code, $id);
            } else {
                $check_stmt = $conn->prepare("SELECT id FROM purchase_order_list WHERE po_code = ? LIMIT 1");
                $check_stmt->bind_param("s", $po_code);
            }

            if ($check_stmt->execute()) {
                $check_stmt->store_result();
                $resp['exists'] = $check_stmt->num_rows > 0;
            }
        }

        header('Content-Type: application/json');
        echo json_encode($resp);
        exit;
    }

    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $po_code = $_POST['po_code'];
    $internal_ref_no = $_POST['internal_ref_no'];
    $supplier_id = $_POST['supplier_id'];
    $remarks = $_POST['remarks'];
    $spec_sheet = isset($_POST['spec_sheet']) ? $_POST['spec_sheet'] : '';
    $final_discounted_price = isset($_POST['final_discounted_price']) && $_POST['final_discounted_price'] !== '' ? (float)$_POST['final_discounted_price'] : 0;
    $tax = isset($_POST['tax']) && $_POST['tax'] !== '' ? (float)$_POST['tax'] : 0;
    $cgst = isset($_POST['cgst']) && $_POST['cgst'] !== '' ? (float)$_POST['cgst'] : 0;
    $sgst = isset($_POST['sgst']) && $_POST['sgst'] !== '' ? (float)$_POST['sgst'] : 0;
    $sub_total = isset($_POST['sub_total']) && $_POST['sub_total'] !== '' ? (float)$_POST['sub_total'] : 0;
    $grand_total = isset($_POST['total_amount']) && $_POST['total_amount'] !== '' ? (float)$_POST['total_amount'] : 0;

    $company = $_POST['company'];
    $material_delivery = $_POST['material_delivery'];
    $payment_terms = $_POST['payment_terms'];
    $delivery_period = $_POST['delivery_period'];
    $authorized_signatory = $_POST['authorized_signatory'];
    $freight = $_POST['freight'];
    $packing_forwarding = isset($_POST['packing_forwarding']) ? $_POST['packing_forwarding'] : '';

    $tax_amount = isset($_POST['tax_amount']) && $_POST['tax_amount'] !== '' ? (float)$_POST['tax_amount'] : (($sub_total * $tax) / 100);
    $cgst_amount = isset($_POST['cgst_amount']) && $_POST['cgst_amount'] !== '' ? (float)$_POST['cgst_amount'] : (($sub_total * $cgst) / 100);
    $sgst_amount = isset($_POST['sgst_amount']) && $_POST['sgst_amount'] !== '' ? (float)$_POST['sgst_amount'] : (($sub_total * $sgst) / 100);

    // Block save when PO code already exists (excluding current record on edit).
    if (!empty($id)) {
        $dup_stmt = $conn->prepare("SELECT id FROM purchase_order_list WHERE po_code = ? AND id != ? LIMIT 1");
        $dup_stmt->bind_param("si", $po_code, $id);
    } else {
        $dup_stmt = $conn->prepare("SELECT id FROM purchase_order_list WHERE po_code = ? LIMIT 1");
        $dup_stmt->bind_param("s", $po_code);
    }

    $dup_stmt->execute();
    $dup_stmt->store_result();
    if ($dup_stmt->num_rows > 0) {
        $duplicate_po_message = 'This PO code already exists.';
    }

    if (empty($duplicate_po_message) && empty($id)) {
        // Get current date/time in the correct timezone
        $created_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO purchase_order_list (
            po_code, internal_ref_no, supplier_id, remarks, spec_sheet, tax, cgst, sgst, sub_total, 
            grand_total, tax_amount, cgst_amount, sgst_amount, final_discounted_price, company,
            material_delivery, payment_terms, delivery_period, authorized_signatory, freight, packing_forwarding, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 

        $stmt->bind_param(
            "ssissdddddddddssssssss",
            $po_code,
            $internal_ref_no,
            $supplier_id,
            $remarks,
            $spec_sheet,
            $tax,
            $cgst,
            $sgst,
            $sub_total,
            $grand_total,
            $tax_amount,
            $cgst_amount,
            $sgst_amount,
            $final_discounted_price,
            $company,
            $material_delivery,
            $payment_terms,
            $delivery_period,
            $authorized_signatory,
            $freight,
            $packing_forwarding,
            $created_at
        );
    } else if (empty($duplicate_po_message)) {
        $stmt = $conn->prepare("UPDATE purchase_order_list SET 
            po_code = ?, internal_ref_no = ?, supplier_id = ?, remarks = ?, spec_sheet = ?,
            final_discounted_price = ?, tax = ?, cgst = ?, sgst = ?, sub_total = ?, 
            grand_total = ?, tax_amount = ?, cgst_amount = ?, sgst_amount = ?, 
            company = ?, material_delivery = ?, payment_terms = ?, delivery_period = ?,
            authorized_signatory = ?, freight = ?, packing_forwarding = ? WHERE id = ?");

        $stmt->bind_param(
            "ssissdddddsdddsssssssi",
            $po_code,
            $internal_ref_no,
            $supplier_id,
            $remarks,
            $spec_sheet,
            $final_discounted_price,
            $tax,
            $cgst,
            $sgst,
            $sub_total,
            $grand_total,
            $tax_amount,
            $cgst_amount,
            $sgst_amount,
            $company,
            $material_delivery,
            $payment_terms,
            $delivery_period,
            $authorized_signatory,
            $freight,
            $packing_forwarding,
            $id
        );
    }

    // Execute the statement
    if (empty($duplicate_po_message) && $stmt->execute()) {
        if (empty($id)) {
            $id = $conn->insert_id;
        }

        $conn->query("DELETE FROM po_items WHERE po_id = '{$id}'");
        $stmt = $conn->prepare("INSERT INTO po_items (po_id, item_id, amount, quantity, unit, discount, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($_POST['item_id'] as $key => $item_id) {
            $item_amount = $_POST['item_amount'][$key];
            $item_quantity = $_POST['item_quantity'][$key];
            $item_unit = $_POST['unit'][$key];
            $item_discount = $_POST['item_discount'][$key];
            $item_total_amount = $_POST['item_total_amount'][$key];
            
            // Use proper types: id (i), item_id (i), amount (d), quantity (d), unit (s), discount (d), total_amount (d)
            $stmt->bind_param("iiddsdd", 
                $id, 
                $item_id, 
                $item_amount, 
                $item_quantity, 
                $item_unit,
                $item_discount, 
                $item_total_amount
            );
            $stmt->execute();
        }

        $redirect_url = _base_url_ . 'admin/?page=purchase_order/view_po&id=' . $id . '&company=' . urlencode($company) . '&success=true';

        echo '<script>
                sessionStorage.setItem("success_message", "Purchase order saved successfully.");
                window.location.href = "' . $redirect_url . '";
              </script>';
        exit;
    } else if (empty($duplicate_po_message)) {
        $success_message = 'Error: ' . $stmt->error;
    }
}

if (isset($_GET['repeat_id'])) {
    $original_id = $_GET['repeat_id'];
    $qry = $conn->query("SELECT * FROM purchase_order_list WHERE id = $original_id");
    if ($qry->num_rows > 0) {
        $result = $qry->fetch_assoc();
        $supplier_id = $result['supplier_id'];
        $remarks = $result['remarks'];
        $spec_sheet = $result['spec_sheet'];
        $final_discounted_price = $result['final_discounted_price'];
        $tax = $result['tax'];
        $cgst = $result['cgst'];
        $sgst = $result['sgst'];
        $sub_total = $result['sub_total'];
        $grand_total = $result['grand_total'];
        $company = $result['company'];
        $material_delivery = $result['material_delivery'];
        $payment_terms = $result['payment_terms'];
        $delivery_period = $result['delivery_period'];
        $authorized_signatory = $result['authorized_signatory'];
        $freight = $result['freight'];
        $packing_forwarding = $result['packing_forwarding'];

        // Get items from original PO
        $item_query = $conn->query("SELECT po_items.*, item_list.name
                                  FROM po_items 
                                  JOIN item_list ON po_items.item_id = item_list.id 
                                  WHERE po_items.po_id = $original_id");
        $items = [];
        while($row = $item_query->fetch_assoc()) {
            $items[] = [
                'id' => $row['id'],
                'item_id' => $row['item_id'],
                'name' => $row['name'],
                'amount' => $row['amount'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'discount' => $row['discount'],
                'total_amount' => $row['total_amount']
            ];
        }

        echo "<script>
            var originalItems = " . json_encode($items) . ";
            $(document).ready(function(){
                setTimeout(function() {
                    var neededRows = originalItems.length - 1;
                    for(var i = 0; i < neededRows; i++) {
                        $('#add-item').click();
                    }
                    $('#item-table tbody tr').each(function(index) {
                        if(originalItems[index]) {
                            var row = $(this);
                            var item = originalItems[index];
                            row.find('.item-select')
                               .val(item.item_id)
                               .trigger('change');
                            row.find('.amount').val(item.amount);
                            row.find('.quantity').val(item.quantity);
                            row.find('.unit').val(item.unit);
                            row.find('.discount').val(item.discount);
                            row.find('.total_amount').val(item.total_amount);
                        }
                    });
                    calculateTotal();
                    alert_toast('Please review and update the details before saving.', 'info');
                }, 1000);
            });
        </script>";
    }
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $qry = $conn->query("SELECT * FROM purchase_order_list WHERE id = $id");
    if ($qry->num_rows > 0) {
        $result = $qry->fetch_assoc();
        $po_code = $result['po_code'];
        $internal_ref_no = $result['internal_ref_no'];
        $supplier_id = $result['supplier_id'];
        $remarks = $result['remarks'];
        $spec_sheet = $result['spec_sheet'];
        $final_discounted_price = $result['final_discounted_price'];
        $tax = $result['tax'];
        $cgst = $result['cgst'];
        $sgst = $result['sgst'];
        $sub_total = $result['sub_total'];
        $grand_total = $result['grand_total'];
        $company = $result['company'];
        $material_delivery = $result['material_delivery'];
        $payment_terms = $result['payment_terms'];
        $delivery_period = $result['delivery_period'];
        $authorized_signatory = $result['authorized_signatory'];
        $freight = $result['freight'];
        $packing_forwarding = $result['packing_forwarding'];
    }

    $item_query = $conn->query("SELECT po_items.*, item_list.name FROM po_items JOIN item_list ON po_items.item_id = item_list.id WHERE po_items.po_id = {$id}");
    $items = [];
    while($row = $item_query->fetch_assoc()) {
        $items[] = $row;
    }
} else {
    $items = [];
}

$item_arr = [];
$item_query = $conn->query("SELECT * FROM item_list ORDER BY id DESC");
while($row = $item_query->fetch_assoc()) {
    $item_arr[$row['supplier_id']][] = $row;
}
?>

<style>
    select[readonly].select2-hidden-accessible + .select2-container {
        pointer-events: none;
        touch-action: none;
        background: #eee;
        box-shadow: none;
    }
    select[readonly].select2-hidden-accessible + .select2-container .select2-selection {
        background: #eee;
        box-shadow: none;
    }
    .center-text { text-align: center; }
    .header-container { display: flex; justify-content: space-between; align-items: center; }
    .header-container .date { text-align: right; }
    .table-sm td, .table-sm th { padding: .3rem; }
    .form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .875rem; line-height: 1.5; border-radius: .2rem; }
    .readonly-field { background-color: #f8f9fa; }
    .amount-red { font-weight: bold; color: rgb(255, 0, 0); }
    .amount-gr { font-weight: bold; color: #28a745; }
    .table-divider { border-left: 2px solid #dee2e6; padding-left: 15px; }
    .form-row { margin-bottom: 10px; }
</style>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($duplicate_po_message): ?>
    <div class="alert alert-danger">
        <?php echo $duplicate_po_message; ?>
    </div>
<?php endif; ?>

<form id="manage-po" action="" method="POST">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">
    <div class="row">
        <div class="col-md-6">  
            <div class="form-group">
                <label for="po_code">PO Code</label>
                <input type="text" name="po_code" id="po_code" class="form-control" placeholder="PO Code" value="<?php echo isset($po_code) ? $po_code : ''; ?>" required>
                <small id="po-code-error" class="text-danger" style="display:none;">This PO code already exists.</small>
            </div>
            <div class="form-group">
                <label for="internal_ref_no">Internal Ref No</label>
                <input type="text" name="internal_ref_no" id="internal_ref_no" class="form-control" placeholder="Internal Ref No" value="<?php echo isset($internal_ref_no) ? $internal_ref_no : ''; ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="custom-select select2" required>
                    <option value="">Select a supplier</option>
                    <?php 
                    $supplier = $conn->query("SELECT * FROM supplier_list WHERE status = 1 ORDER BY name ASC");
                    while($row = $supplier->fetch_assoc()):
                    ?>
                    <option value="<?php echo $row['id'] ?>" <?php echo isset($supplier_id) && $supplier_id == $row['id'] ? "selected" : "" ?>><?php echo $row['name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="company">Company <span class="text-danger">*</span></label>
                <select name="company" id="company" class="form-control" required>
                    <option value="" disabled selected>Select a company</option>
                    <option value="Hugopharm" <?php echo (isset($company) && $company == 'Hugopharm') ? 'selected' : ''; ?>>Hugopharm</option>
                    <option value="S.B. Panchal" <?php echo (isset($company) && $company == 'S.B. Panchal') ? 'selected' : ''; ?>>S.B. Panchal</option>
                </select>
            </div>
        </div>
    </div>
    <div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="material_delivery">Material Delivery Location</label>
            <select name="material_delivery" id="material_delivery" class="form-control" required>
                <option value="">Select location</option>
                <option value="DOM" <?php echo (isset($material_delivery) && $material_delivery == 'DOM') ? 'selected' : ''; ?>>Dombivli</option>
                <option value="DDR" <?php echo (isset($material_delivery) && $material_delivery == 'DDR') ? 'selected' : ''; ?>>Dadar</option>
                <option value="SELF" <?php echo (isset($material_delivery) && $material_delivery == 'SELF') ? 'selected' : ''; ?>>Self Pickup</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="payment_terms">Payment Terms</label>
            <select name="payment_terms" id="payment_terms" class="form-control" required>
                <option value="Against Delivery" <?php echo (isset($payment_terms) && $payment_terms == 'Against Delivery') ? 'selected' : ''; ?>>Against Delivery</option>
                <option value="Net 30 days" <?php echo (isset($payment_terms) && $payment_terms == 'Net 30 days') ? 'selected' : ''; ?>>Net 30days</option>
                <option value="Net 45 days" <?php echo (isset($payment_terms) && $payment_terms == 'Net 45 days') ? 'selected' : ''; ?>>Net 45days</option>
                <option value="Net 60 days" <?php echo (isset($payment_terms) && $payment_terms == 'Net 60 days') ? 'selected' : ''; ?>>Net 60days</option>
                <option value="Net 90 days" <?php echo (isset($payment_terms) && $payment_terms == 'Net 90 days') ? 'selected' : ''; ?>>Net 90days</option>
                <option value="30% advance 70% against delivery" <?php echo (isset($payment_terms) && $payment_terms == '30% advance 70% against delivery') ? 'selected' : ''; ?>>30% advance 70% against delivery</option>
                <option value="35% advance 65% against Proforma Invoice" <?php echo (isset($payment_terms) && $payment_terms == '35% advance 65% against Proforma Invoice') ? 'selected' : ''; ?>>35% advance 65% against Proforma Invoice</option>
                <option value="40% advance 60% against delivery" <?php echo (isset($payment_terms) && $payment_terms == '40% advance 60% against delivery') ? 'selected' : ''; ?>>40% advance 60% against delivery</option>
                <option value="50% advance 50% against delivery" <?php echo (isset($payment_terms) && $payment_terms == '50% advance 50% against delivery') ? 'selected' : ''; ?>>50% advance 50% against delivery</option>
                <option value="40% Adv 50% Delivery & 10% Inst at factory" <?php echo (isset($payment_terms) && $payment_terms == '40% Adv 50% Delivery & 10% Inst at factory') ? 'selected' : ''; ?>>40% Advance 50% against Delivery & 10% against Installation at factory</option>
                <option value="100% Advance against PI" <?php echo (isset($payment_terms) && $payment_terms == '100% Advance against PI') ? 'selected' : ''; ?>>100% Advance against PI</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="delivery_period">Delivery Period</label>
            <select name="delivery_period" id="delivery_period" class="form-control" required>
                <option value="2-4 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '2-4 Weeks') ? 'selected' : ''; ?>>2-4 Weeks</option>
                <option value="4-6 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '4-6 Weeks') ? 'selected' : ''; ?>>4-6 Weeks</option>
                <option value="6-8 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '6-8 Weeks') ? 'selected' : ''; ?>>6-8 Weeks</option>
                <option value="8-10 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '8-10 Weeks') ? 'selected' : ''; ?>>8-10 Weeks</option>
                <option value="10-15 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '10-15 Weeks') ? 'selected' : ''; ?>>10-15 Weeks</option>
                <option value="15-20 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '15-20 Weeks') ? 'selected' : ''; ?>>15-20 Weeks</option>
                <option value="20-25 Weeks" <?php echo (isset($delivery_period) && $delivery_period == '20-25 Weeks') ? 'selected' : ''; ?>>20-25 Weeks</option>
                <option value="Immediate" <?php echo (isset($delivery_period) && $delivery_period == 'Immediate') ? 'selected' : ''; ?>>Immediate</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="authorized_signatory">Authorized Signatory</label>
            <input type="text" name="authorized_signatory" id="authorized_signatory" class="form-control" 
                   value="<?php echo isset($authorized_signatory) ? $authorized_signatory : ''; ?>" required>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="freight">Freight</label>
            <select name="freight" id="freight" class="form-control" required>
                <option value="Included" <?php echo (isset($freight) && $freight == 'Included') ? 'selected' : ''; ?>>Included</option>
                <option value="To Pay Basis" <?php echo (isset($freight) && $freight == 'To Pay Basis') ? 'selected' : ''; ?>>To Pay Basis</option>
                <option value="Clients Scope" <?php echo (isset($freight) && $freight == 'Clients Scope') ? 'selected' : ''; ?>>Clients Scope</option>
                <option value="EXTRA AS APPLICABLE" <?php echo (isset($freight) && $freight == 'EXTRA AS APPLICABLE') ? 'selected' : ''; ?>>EXTRA AS APPLICABLE</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="packing_forwarding">Packing Forwarding</label>
            <input type="text" name="packing_forwarding" id="packing_forwarding" class="form-control" required value="<?php echo isset($packing_forwarding) ? $packing_forwarding : ''; ?>">
        </div>
    </div>
</div>

<div class="form-group">
    <label for="spec_sheet">Spec Sheet</label>
    <textarea name="spec_sheet" id="spec_sheet" rows="3"><?php echo isset($spec_sheet) ? $spec_sheet : ''; ?></textarea>
</div>

    <div class="form-group">
        <label for="items">Items</label>
        <table class="table table-bordered table-striped" id="item-table">
            <colgroup>
                <col width="3%">
                <col width="28%">
                <col width="12%">
                <col width="8%">
                <col width="8%">
                <col width="8%">
                <col width="13%">
                <col width="15%">
            </colgroup>
            <thead>                            
                <tr style="background-color: rgb(0, 31, 63); color: white;">
                    <th>Sr.</th>
                    <th>Description</th>
                    <th>Cost</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Discount (%)</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($items) && count($items) > 0): ?>
                    <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <select name="item_id[]" class="form-control item-select" required data-item-id="<?php echo $item['item_id']; ?>">
                                    <option value="">Select an item</option>
                                    <?php 
                                    if (isset($item_arr[$supplier_id])) {
                                        foreach ($item_arr[$supplier_id] as $row) {
                                            $selected = ($row['id'] == $item['item_id']) ? 'selected' : '';
                                            echo "<option value='{$row['id']}' {$selected}>{$row['name']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="number" name="item_amount[]" class="form-control amount" value="<?php echo $item['amount']; ?>" required step="0.01"></td>
                            <td><input type="number" name="item_quantity[]" class="form-control quantity" value="<?php echo $item['quantity']; ?>" required></td>
                            <td>
                                <select name="unit[]" class="form-control unit">
                                    <option value="nos" <?php echo ($item['unit'] == 'nos') ? 'selected' : ''; ?>>nos</option>
                                    <option value="packet" <?php echo ($item['unit'] == 'packet') ? 'selected' : ''; ?>>packet</option>
                                    <option value="box" <?php echo ($item['unit'] == 'box') ? 'selected' : ''; ?>>box</option>
                                    <option value="kg" <?php echo ($item['unit'] == 'kg') ? 'selected' : ''; ?>>kg</option>
                                    <option value="mtr" <?php echo ($item['unit'] == 'mtr') ? 'selected' : ''; ?>>mtr</option>
                                    <option value="days" <?php echo ($item['unit'] == 'days') ? 'selected' : ''; ?>>days</option>
                                </select>
                            </td>
                            <td><input type="number" name="item_discount[]" class="form-control discount" value="<?php echo $item['discount']; ?>" required step="0.01"></td>
                            <td><input type="number" name="item_total_amount[]" class="form-control total_amount" value="<?php echo $item['total_amount']; ?>" readonly></td>
                            <td>
                                <?php if ($index == 0): ?>  
                                    <button type="button" id="add-item" class="btn btn-primary add-item-btn">Add Item</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>  
                    <tr>
                        <td>1</td>
                        <td>
                            <select name="item_id[]" class="form-control item-select" required data-item-id="">
                                <option value="">Select an item</option>
                                <?php 
                                if (isset($item_arr[$supplier_id])) {
                                    foreach ($item_arr[$supplier_id] as $row) {
                                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </td>
                        <td><input type="number" name="item_amount[]" class="form-control amount" value="0" required step="0.01"></td>
                        <td><input type="number" name="item_quantity[]" class="form-control quantity" value="1" required></td>
                        <td>
                            <select name="unit[]" class="form-control unit">
                                <option value="nos" selected>nos</option>
                                <option value="packet">packet</option>
                                <option value="box">box</option>
                                <option value="kg">kg</option>
                                <option value="mtr">mtr</option>
                                <option value="days">days</option>
                            </select>
                        </td>
                        <td><input type="number" name="item_discount[]" class="form-control discount" value="0" required step="0.01"></td>
                        <td><input type="number" name="item_total_amount[]" class="form-control total_amount" value="0" readonly></td>
                        <td>
                            <button type="button" id="add-item" class="btn btn-primary add-item-btn">Add Item</button>
                            <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                        </td>
                    </tr>
                <?php endif; ?>
            <tfoot>
                <tr>
                    <td colspan="8">
                        <div class="row">
                            <!-- Left Column - Calculations -->
                            <div class="col-md-4">
                                <h5 class="text-center"><strong>Calculations</strong></h5>
                                <div class="form-row">
                                    <div class="col-6 pt-1">Sub Total:</div>
                                    <div class="col-6">
                                        <input type="number" name="sub_total" id="sub_total" class="form-control readonly-field amount-red" readonly step="0.01" value="<?php echo isset($sub_total) ? $sub_total : 0; ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-6 pt-1"><strong>Total Amount:</strong></div>
                                    <div class="col-6">
                                        <input type="number" name="total_amount" id="total_amount" class="form-control readonly-field amount-gr" readonly step="0.01" value="<?php echo isset($grand_total) ? $grand_total : 0; ?>">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-6 pt-1">
                                        <label for="final_discounted_price">Discounted Amount:</label>
                                    </div>
                                    <div class="col-6">
                                        <input type="number" name="final_discounted_price" id="final_discounted_price" class="form-control" step="0.01" value="<?php echo isset($final_discounted_price) ? $final_discounted_price : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Middle Column - Taxes -->
                            <div class="col-md-4 table-divider">
                                <h5 class="text-center"><strong>Taxes</strong></h5>
                                <div class="form-row">
                                    <div class="col-6">
                                        <label for="tax">IGST(%):</label>
                                        <input type="number" step="0.1" name="tax" id="tax" class="form-control" value="<?php echo isset($tax) ? $tax : 0; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label>Amount:</label>
                                        <input type="text" id="tax_amount" class="form-control readonly-field" readonly>
                                        <input type="hidden" name="tax_amount" id="hidden_tax_amount" value="0.00">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-6">
                                        <label for="cgst">CGST(%):</label>
                                        <input type="number" step="0.1" name="cgst" id="cgst" class="form-control" value="<?php echo isset($cgst) ? $cgst : 0; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label>Amount:</label>
                                        <input type="text" id="cgst_amount" class="form-control readonly-field" readonly>
                                        <input type="hidden" name="cgst_amount" id="hidden_cgst_amount" value="0.00">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-6">
                                        <label for="sgst">SGST(%):</label>
                                        <input type="number" step="0.1" name="sgst" id="sgst" class="form-control" value="<?php echo isset($sgst) ? $sgst : 0; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label>Amount:</label>
                                        <input type="text" id="sgst_amount" class="form-control readonly-field" readonly>
                                        <input type="hidden" name="sgst_amount" id="hidden_sgst_amount" value="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Empty for balance -->
                            <div class="col-md-4 table-divider">
                                <h5 class="text-center"><strong>Remarks</strong></h5>
                                <div class="form-row">
                                    <label for="remarks">Comment</label>
                                    <textarea name="remarks" id="remarks" class="form-control" rows="6"><?php echo isset($remarks) ? $remarks : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="button-container text-center" style="padding: 20px;">
        <button type="submit" id="save-btn" class="btn btn-primary">Save</button>
        <button type="button" id="cancel-btn" class="btn btn-secondary">Cancel</button>
    </div>
</form>

<script>
var items = <?php echo json_encode($item_arr); ?>;

$(document).ready(function() {
    var poCodeExists = false;

    function togglePoCodeError(show) {
        poCodeExists = !!show;
        if (poCodeExists) {
            $('#po_code').addClass('is-invalid');
            $('#po-code-error').show();
        } else {
            $('#po_code').removeClass('is-invalid');
            $('#po-code-error').hide();
        }
    }

    function checkPoCodeExists(callback) {
        var poCode = $.trim($('#po_code').val());
        var currentId = $('input[name="id"]').val();

        if (poCode === '') {
            poCodeExists = false;
            if (typeof callback === 'function') callback(false);
            return;
        }

        $.ajax({
            url: '',
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'check_po_code',
                po_code: poCode,
                id: currentId
            },
            success: function(resp) {
                togglePoCodeError(!!(resp && resp.exists));
                if (typeof callback === 'function') callback(poCodeExists);
            },
            error: function() {
                togglePoCodeError(false);
                if (typeof callback === 'function') callback(false);
            }
        });
    }

    $('.select2').select2({
        placeholder: "Please select here",
        width: 'resolve',
    });

    $('#spec_sheet').summernote({
        height: 400,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    $('#cancel-btn').click(function() {
        location.href = _base_url_ + "admin/?page=purchase_order";
    });

    $('#po_code').on('blur change', function() {
        checkPoCodeExists();
    });

    $('#manage-po').on('submit', function(e) {
        e.preventDefault();
        var form = this;
        checkPoCodeExists(function(exists) {
            if (exists || poCodeExists) {
                togglePoCodeError(true);
                $('#po_code').focus();
                return;
            }
            form.submit();
        });
    });

    <?php if ($duplicate_po_message): ?>
    togglePoCodeError(true);
    $('#po_code').focus();
    <?php endif; ?>

    function updateItemSelects() {
        // Store current selections
        var currentSelections = [];
        $('.item-select').each(function() {
            currentSelections.push($(this).val());
        });

        // Update item selects (your existing logic here)
        var supplier_id = $('#supplier_id').val();
        $('.item-select').each(function() {
            var select = $(this);
            var selectedItemId = select.data('item-id'); // Get the stored item ID

            select.html('<option value="">Select an item</option>');
            if (items[supplier_id]) {
                $.each(items[supplier_id], function(index, item) {
                    var option = $('<option>', {
                        value: item.id,
                        text: item.name
                    });

                    if (selectedItemId == item.id) { // Use the stored item ID for comparison
                        option.prop('selected', true);
                    }

                    select.append(option);
                });
            }
        });

        // Reapply stored selections
        $('.item-select').each(function(index) {
            $(this).val(currentSelections[index]);
        });
    }

    $('.item-select').data('initialized', false);
    updateItemSelects();

    $('#supplier_id').change(function() {
        $('.item-select').data('initialized', false);
        updateItemSelects();
    });

    $('#add-item').click(function() {
        var rowCount = $('#item-table tbody tr').length + 1;
        var newRow = `<tr>
                        <td>${rowCount}</td>
                        <td>
                            <select name="item_id[]" class="form-control item-select" required>
                                <option value="">Select an item</option>
                            </select>
                        </td>
                        <td><input type="number" name="item_amount[]" class="form-control amount" value="0" required step="0.01"></td>
                        <td><input type="number" name="item_quantity[]" class="form-control quantity" value="1" required></td>
                        <td>
                            <select name="unit[]" class="form-control unit">
                                <option value="nos" selected>nos</option>
                                <option value="packet">packet</option>
                                <option value="box">box</option>
                                <option value="kg">kg</option>
                            </select>
                        </td>
                        <td><input type="number" name="item_discount[]" class="form-control discount" value="0" required step="0.01"></td>
                        <td><input type="number" name="item_total_amount[]" class="form-control total_amount" value="0" readonly></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-item">Remove</button></td>
                    </tr>`;
        $('#item-table tbody').append(newRow);
        updateSrNo();
        updateItemSelects();
    });

    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateSrNo();
        calculateTotal();
    });

    $(document).on('input', '.amount, .quantity, .discount, #tax, #cgst, #sgst', function() {
        var row = $(this).closest('tr');
        var amount = parseFloat(row.find('.amount').val()) || 0;
        var quantity = parseFloat(row.find('.quantity').val()) || 1;
        var discount = parseFloat(row.find('.discount').val()) || 0;
        var total_amount = (amount * quantity) - ((amount * quantity) * (discount / 100));
        row.find('.total_amount').val(total_amount.toFixed(2));
        calculateTotal();
    });

    function updateSrNo() {
        $('#item-table tbody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    function calculateTotal() {
        var subTotal = 0;
        $('#item-table tbody tr').each(function() {
            var amount = parseFloat($(this).find('.total_amount').val()) || 0;
            subTotal += amount;
        });

        $('#sub_total').val(subTotal.toFixed(2));

        var tax = parseFloat($('#tax').val()) || 0;
        var cgst = parseFloat($('#cgst').val()) || 0;
        var sgst = parseFloat($('#sgst').val()) || 0;

        var taxAmount = (subTotal * (tax / 100)).toFixed(2);
        var cgstAmount = (subTotal * (cgst / 100)).toFixed(2);
        var sgstAmount = (subTotal * (sgst / 100)).toFixed(2);

        $('#tax_amount').val(taxAmount);
        $('#cgst_amount').val(cgstAmount);
        $('#sgst_amount').val(sgstAmount);
        $('#hidden_tax_amount').val(taxAmount);
        $('#hidden_cgst_amount').val(cgstAmount);
        $('#hidden_sgst_amount').val(sgstAmount);

        var grandTotal = subTotal + parseFloat(taxAmount) + parseFloat(cgstAmount) + parseFloat(sgstAmount);
        $('#total_amount').val(grandTotal.toFixed(2));
    }

    calculateTotal();
});

</script>