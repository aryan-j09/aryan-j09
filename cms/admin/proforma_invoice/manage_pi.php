<?php 

define('_base_url_', 'https://sbpanchal.com/cms/');

function get_work_order_prefix($company){
    $company = trim((string)$company);
    if($company === 'Hugopharm'){
        return 'HUGO/';
    }
    if($company === 'S.B. Panchal'){
        return 'SBP/';
    }
    return 'WO';
}

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST Data: " . print_r($_POST, true));    
    
    // Verify HSN codes are present
    if (isset($_POST['hsn_code'])) {
        error_log("HSN Codes: " . print_r($_POST['hsn_code'], true));
    } else {
        error_log("No HSN codes in POST data");
    }
    
    // Validate required fields
    if (empty($_POST['company'])) {
        $success_message = 'Error: Company is required';
        exit;
    }
    
    // Extract and sanitize form data
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $po_code = $_POST['po_code'];
    $po_date_created = $_POST['po_date_created'];
    $client_id = $_POST['client_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $packing_forwarding = isset($_POST['packing_forwarding']) ? $_POST['packing_forwarding'] : 0;
    $tax = isset($_POST['tax']) ? $_POST['tax'] : 0;
    $cgst = isset($_POST['cgst']) ? $_POST['cgst'] : 0;
    $sgst = isset($_POST['sgst']) ? $_POST['sgst'] : 0;
    $advance_payment = isset($_POST['advance_payment']) ? $_POST['advance_payment'] : 0;
    $inspection_payment = isset($_POST['inspection_payment']) ? $_POST['inspection_payment'] : 0;
    $installation_payment = isset($_POST['installation_payment']) ? $_POST['installation_payment'] : 0;
    $inspection_payment_type = isset($_POST['inspection_payment_type']) ? $_POST['inspection_payment_type'] : 'inspection';

    // If advance is 100%, store the entire amount (including taxes, packing/forwarding and freight)
    // in advance_payment_amount and zero other payment amounts so server stores correctly.
    if (floatval($advance_payment) === 100.0) {
        $advance_payment_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : $advance_payment_amount;
        $inspection_payment_amount = 0;
        $installation_payment_amount = 0;
        $credit_payment_amount = 0;
    } elseif (floatval($inspection_payment) === 100.0) {
        $advance_payment_amount = 0;
        $inspection_payment_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : $inspection_payment_amount;
        $installation_payment_amount = 0;
        $credit_payment_amount = 0;
    } elseif (floatval($installation_payment) === 100.0) {
        $advance_payment_amount = 0;
        $inspection_payment_amount = 0;
        $installation_payment_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : $installation_payment_amount;
        $credit_payment_amount = 0;
    }
    $company = $_POST['company'];
    $freight = isset($_POST['freight']) ? $_POST['freight'] : 0;
    $credit_payment_days = isset($_POST['credit_payment_days']) ? $_POST['credit_payment_days'] : 0;
    $credit_payment_amount = isset($_POST['credit_payment_amount']) ? $_POST['credit_payment_amount'] : 0;
    $freight_note = isset($_POST['freight_note']) ? $_POST['freight_note'] : '';
    $authorized_signatory = isset($_POST['authorized_signatory']) ? $_POST['authorized_signatory'] : '';
    $currency = isset($_POST['currency']) ? $_POST['currency'] : 'INR';

    // Use the values directly from the form POST data
    $sub_total = !empty($_POST['sub_total']) ? $_POST['sub_total'] : 0;
    $packing_forwarding_amount = !empty($_POST['packing_forwarding_amount']) ? $_POST['packing_forwarding_amount'] : 0;
    $freight = $_POST['freight'];
    $tax_amount = !empty($_POST['tax_amount']) ? $_POST['tax_amount'] : 0;
    $cgst_amount = !empty($_POST['cgst_amount']) ? $_POST['cgst_amount'] : 0;
    $sgst_amount = !empty($_POST['sgst_amount']) ? $_POST['sgst_amount'] : 0;
    $total_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : 0;
    $advance_payment_amount = !empty($_POST['advance_payment_amount']) ? $_POST['advance_payment_amount'] : 0;
    $inspection_payment_amount = !empty($_POST['inspection_payment_amount']) ? $_POST['inspection_payment_amount'] : 0;
    $installation_payment_amount = !empty($_POST['installation_payment_amount']) ? $_POST['installation_payment_amount'] : 0;

    // Add these to your list of extracted POST variables
    $abg_required = isset($_POST['abg_required']) ? 1 : 0;
    $pbg_required = isset($_POST['pbg_required']) ? 1 : 0;

    // If advance is 100%, store the entire amount (including taxes, packing/forwarding and freight)
    // in advance_payment_amount and zero other payment amounts so server stores correctly.
    if (floatval($advance_payment) === 100.0) {
        $advance_payment_amount = !empty($_POST['total_amount']) ? $_POST['total_amount'] : $advance_payment_amount;
        $inspection_payment_amount = 0;
        $installation_payment_amount = 0;
        $credit_payment_amount = 0;
    }

    $prefix = get_work_order_prefix($company);

    // Generate a fresh work order number for the selected company on every save
    $query = "SELECT work_order_number FROM proforma_invoice_list 
              WHERE work_order_number LIKE '{$prefix}%' 
              ORDER BY CAST(SUBSTRING(work_order_number, " . (strlen($prefix) + 1) . ") AS UNSIGNED) DESC 
              LIMIT 1";
    
    $result = $conn->query($query);
    $nextNumber = 1;

    if ($result && $result->num_rows > 0) {
        $lastNumber = $result->fetch_assoc()['work_order_number'];
        $numericPart = intval(substr($lastNumber, strlen($prefix)));
        $nextNumber = $numericPart + 1;
    }
    
    $work_order_number = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    error_log("Generated Work Order Number for save: " . $work_order_number); // Debugging line


    // Update the form fields with calculated values
    echo "<script>
        document.getElementById('advance_payment_amount').value = $advance_payment_amount.toFixed(2);
        document.getElementById('hidden_advance_payment_amount').value = $advance_payment_amount.toFixed(2);
        document.getElementById('inspection_payment_amount').value = $inspection_payment_amount.toFixed(2);
        document.getElementById('hidden_inspection_payment_amount').value = $inspection_payment_amount.toFixed(2);
        document.getElementById('installation_payment_amount').value = $installation_payment_amount.toFixed(2);
        document.getElementById('hidden_installation_payment_amount').value = $installation_payment_amount.toFixed(2);
    </script>";

    if (empty($id)) {
    // Insert new record
    $stmt = $conn->prepare("INSERT INTO proforma_invoice_list (
        po_code,
        work_order_number,
        po_date_created,
        client_id,
        total_amount,
        packing_forwarding,
        freight,
        tax,
        cgst,
        sgst,
        advance_payment,
        advance_payment_amount,
        inspection_payment,
        inspection_payment_amount,
        installation_payment,
        installation_payment_amount,
        sub_total,
        sgst_amount,
        cgst_amount,
        tax_amount,
        packing_forwarding_amount,
        company,
        credit_payment_days,
        credit_payment_amount,
        freight_note,
        authorized_signatory,
        inspection_payment_type,
        abg_required,
        pbg_required,
        currency
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "sssidddddddddddddddddsddsssiis", // Corrected 30-character string
        $po_code,
        $work_order_number,
        $po_date_created,
        $client_id,
        $total_amount,
        $packing_forwarding,
        $freight,
        $tax,
        $cgst,
        $sgst,
        $advance_payment,
        $advance_payment_amount,
        $inspection_payment,
        $inspection_payment_amount,
        $installation_payment,
        $installation_payment_amount,
        $sub_total,
        $sgst_amount,
        $cgst_amount,
        $tax_amount,
        $packing_forwarding_amount,
        $company,
        $credit_payment_days,
        $credit_payment_amount,
        $freight_note,
        $authorized_signatory,
        $inspection_payment_type,
        $abg_required,
        $pbg_required,
        $currency
    );

} else {
    // Update existing record - work_order_number is not updated
    $stmt = $conn->prepare("UPDATE proforma_invoice_list SET 
        po_code = ?,
        po_date_created = ?,
        client_id = ?,
        work_order_number = ?,
        total_amount = ?,
        packing_forwarding = ?,
        freight = ?,
        tax = ?,
        cgst = ?,
        sgst = ?,
        advance_payment = ?,
        advance_payment_amount = ?,
        inspection_payment = ?,
        inspection_payment_amount = ?,
        installation_payment = ?,
        installation_payment_amount = ?,
        sub_total = ?,
        sgst_amount = ?,
        cgst_amount = ?,
        tax_amount = ?,
        packing_forwarding_amount = ?,
        company = ?,
        credit_payment_days = ?,
        credit_payment_amount = ?,
        freight_note = ?,
        authorized_signatory = ?,
        inspection_payment_type = ?,
        abg_required = ?,
        pbg_required = ?,
        currency = ?
        WHERE id = ?");

    $stmt->bind_param(
        "ssissddddddddddddddddsddsssiisi", // 30 parameters: 3 strings, 1 int, 18 decimals, 1 string, 2 decimals, 3 strings, 3 ints
        $po_code,
        $po_date_created,
        $client_id,
        $work_order_number,
        $total_amount,
        $packing_forwarding,
        $freight,
        $tax,
        $cgst,
        $sgst,
        $advance_payment,
        $advance_payment_amount,
        $inspection_payment,
        $inspection_payment_amount,
        $installation_payment,
        $installation_payment_amount,
        $sub_total,
        $sgst_amount,
        $cgst_amount,
        $tax_amount,
        $packing_forwarding_amount,
        $company,
        $credit_payment_days,
        $credit_payment_amount,
        $freight_note,
        $authorized_signatory,
        $inspection_payment_type,
        $abg_required,
        $pbg_required,
        $currency,
        $id
    );
}    

    if ($stmt->execute()) {
        // Get the last inserted id if it's a new record
        if (empty($id)) {
            $id = $conn->insert_id;
        }

        // Handle items
        $conn->query("DELETE FROM proforma_invoice_items WHERE proforma_invoice_id = '$id'");
        foreach ($description as $index => $desc) {
            $amt = $amount[$index];
            $hsn = $_POST['hsn_code'][$index];
            $stmt = $conn->prepare("INSERT INTO proforma_invoice_items (proforma_invoice_id, description, hsn_code, amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issd", $id, $desc, $hsn, $amt);
            $stmt->execute();
        }

        // Set success message and redirect based on the selected company
        $redirect_url = _base_url_ . 'admin/?page=proforma_invoice/view_pi&id=' . $id . '&success=true';
        if ($company == 'S.B. Panchal') {
            $redirect_url = _base_url_ . 'admin/?page=proforma_invoice/sbp_pi&id=' . $id . '&success=true';
        }

        echo '<script>
                sessionStorage.setItem("success_message", "Proforma Invoice saved successfully.");
                window.location.href = "' . $redirect_url . '";
              </script>';
        exit;
    } else {
        $success_message = 'Error: ' . $stmt->error;
    }
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $qry = $conn->query("SELECT * FROM proforma_invoice_list WHERE id = $id");
    if ($qry->num_rows > 0) {
        $result = $qry->fetch_assoc();
        $po_code = $result['po_code'];
        $work_order_number = $result['work_order_number'];
        $po_date_created = $result['po_date_created'];
        $client_id = $result['client_id'];
        $company = $result['company']; // Add this line
        $total_amount = $result['total_amount'];
        $packing_forwarding = $result['packing_forwarding'];
        $freight = $result['freight'];  // Add this line
        $tax = $result['tax'];
        $cgst = $result['cgst'];
        $sgst = $result['sgst'];
        $advance_payment = $result['advance_payment'];
        $advance_payment_amount = $result['advance_payment_amount'];
        $inspection_payment = $result['inspection_payment'];
        $inspection_payment_amount = $result['inspection_payment_amount'];
        $installation_payment = $result['installation_payment'];
        $installation_payment_amount = $result['installation_payment_amount'];
        $sub_total = $result['sub_total'];
        $sgst_amount = $result['sgst_amount'];
        $cgst_amount = $result['cgst_amount'];
        $tax_amount = $result['tax_amount'];
        $packing_forwarding_amount = $result['packing_forwarding_amount'];
        $credit_payment_days = $result['credit_payment_days'];
        $credit_payment_amount = $result['credit_payment_amount'];
        $freight_note = $result['freight_note'];
        $authorized_signatory = $result['authorized_signatory'];
        $inspection_payment_type = $result['inspection_payment_type'];
        $is_direct_value = $result['packing_forwarding'] == 0 && $result['packing_forwarding_amount'] > 0;
        $abg_required = $result['abg_required'];
        $pbg_required = $result['pbg_required'];
        $currency = $result['currency'];
    }

    // Fetch items from proforma_invoice_items table
    $item_query = $conn->query("SELECT * FROM proforma_invoice_items WHERE proforma_invoice_id = $id");
    $item_list = [];
    while($row = $item_query->fetch_assoc()) {
        $item_list[] = $row;
    }
}

// Fetch clients
$clients = $conn->query("SELECT id, company_name FROM clients");

// Fetch purchase orders
$purchase_orders = $conn->query("SELECT id, po_code FROM purchase_order_list");

// Modify the item query to include description
$item_query = $conn->query("SELECT id, name, description FROM machine_list ORDER BY name ASC");
$items = [];
while($row = $item_query->fetch_assoc()) {
    $items[] = $row;
}
?>


<style>
.button-container {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: lightgrey;
    padding: 10px;
    width: 100%;
    position: relative;
    top: -20px; /* Adjust this value to move the buttons slightly above */
}

.button-container .btn {
    margin: 0 10px;
}

.select2-container--default .select2-selection--single {
    height: 38px; /* Match the height of other form fields */
    border: 1px solid #ced4da; /* Match the border of other form fields */
    border-radius: 4px; /* Match the border-radius of other form fields */
    padding: 6px 12px; /* Match the padding of other form fields */
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 30px; /* Adjust line-height to vertically center the text */
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px; /* Match the height of the selection */
}

.custom-dropdown {
    max-width: 100%;
    box-sizing: border-box;
}

.totals-section {
    margin-top: 20px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 15px;
}

.totals-section h4 {
    background-color: rgb(0, 31, 63);
    color: white;
    padding: 10px;
    margin: -15px -15px 15px -15px;
    border-radius: 5px 5px 0 0;
}

.table-divider {
    border-left: 2px solid #dee2e6;
    padding-left: 15px;
}

.form-row {
    margin-bottom: 10px;
}

.readonly-field {
    background-color: #f8f9fa;
}

.amount-red {
    font-weight: bold;
    color:rgb(255, 0, 0);
}

.amount-gr {
    font-weight: bold;
    color: #28a745;
}

.custom-checkbox {
    margin-left: 8px;
}

.d-flex.align-items-center {
    gap: 5px;
}
</style>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<form id="manage-pi" action="" method="POST">
    <input type="hidden" name="id" value="<?php echo isset($id) ? $id : ''; ?>">   
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="po_code">PO Code <span class="text-danger">*</span></label>
                <input type="text" name="po_code" id="po_code" class="form-control" placeholder="PO Code" value="<?php echo isset($po_code) ? $po_code : ''; ?>" required>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label for="po_date_created">PO Date <span class="text-danger">*</span></label>
                <input type="date" name="po_date_created" id="po_date_created" class="form-control" value="<?php echo isset($po_date_created) ? date('Y-m-d', strtotime($po_date_created)) : ''; ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="client_id">Client <span class="text-danger">*</span></label>
                <select name="client_id" id="client_id" class="form-control custom-dropdown" required>
                    <option value="" disabled selected>Select a client</option>
                    <?php while($client = $clients->fetch_assoc()): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo isset($client_id) && $client_id == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo $client['company_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="company">Company <span class="text-danger">*</span></label>
                <select name="company" id="company" class="form-control custom-dropdown" required>
                    <option value="" disabled selected>Select a company</option>
                    <option value="Hugopharm" <?php echo (isset($company) && $company == 'Hugopharm') ? 'selected' : ''; ?>>Hugopharm</option>
                    <option value="S.B. Panchal" <?php echo (isset($company) && $company == 'S.B. Panchal') ? 'selected' : ''; ?>>S.B. Panchal</option>
                </select>
            </div>
        </div>
    </div>
    <table id="item-table" class="table table-bordered table-striped">
        <colgroup>
            <col width="7%">
            <col width="40%">
            <col width="18%">  
            <col width="20%">  
            <col width="15%">
        </colgroup>
        <thead>
            <tr style="background-color: rgb(0, 31, 63); color: white;">
                <th>Sr. No.</th>
                <th>Description</th>
                <th>HSN Code</th>
                <th>Amount 
                    <select name="currency" id="currency" class="form-control-sm">
                        <option value="INR" <?php echo (isset($currency) && $currency == 'INR') ? 'selected' : ''; ?>>INR</option>
                        <option value="USD" <?php echo (isset($currency) && $currency == 'USD') ? 'selected' : ''; ?>>USD</option>
                        <option value="EUR" <?php echo (isset($currency) && $currency == 'EUR') ? 'selected' : ''; ?>>EUR</option>
                    </select>
                </th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($item_list) && count($item_list) > 0): ?>
            <?php foreach ($item_list as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td>
                        <select name="item_id" id="item_id" class="form-control item-dropdown" onchange="updateDescription(this)">
                            <option value="">Select an item</option>
                            <?php foreach ($items as $item_option): ?>
                                <option value="<?php echo $item_option['name']; ?>" data-description="<?php echo htmlspecialchars($item_option['description']); ?>"><?php echo $item_option['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="description[]" class="form-control item-input" rows="2" required><?php echo $item['description']; ?></textarea>
                    </td>
                    <td>
                        <select name="hsn_code[]" class="form-control hsn-dropdown" required>
                            <option value="">Select HSN Code</option>
                            <option value="84798970" <?php echo $item['hsn_code'] == '84798970' ? 'selected' : ''; ?>>84798970 - MACHINERY</option>
                            <option value="84799040" <?php echo $item['hsn_code'] == '84799040' ? 'selected' : ''; ?>>84799040 - SPARES</option>
                            <option value="998346" <?php echo $item['hsn_code'] == '998346' ? 'selected' : ''; ?>>998346 - TRIAL SAC</option>
                            <option value="998141" <?php echo $item['hsn_code'] == '998141' ? 'selected' : ''; ?>>998141 - R & D PHARMACEUTICALS</option>
                            <option value="998142" <?php echo $item['hsn_code'] == '998142' ? 'selected' : ''; ?>>998142 - R & D AGRICULTURE</option>
                            <option value="998143" <?php echo $item['hsn_code'] == '998143' ? 'selected' : ''; ?>>998143 - R & D BIOTECHNOLOGY</option>
                            <option value="998144" <?php echo $item['hsn_code'] == '998144' ? 'selected' : ''; ?>>998144 - R & D COMPUTER SCIENCES</option>
                            <option value="998145" <?php echo $item['hsn_code'] == '998145' ? 'selected' : ''; ?>>998145 - R & D OTHER FIELDS</option>
                            <option value="998717" <?php echo $item['hsn_code'] == '998717' ? 'selected' : ''; ?>>998717 - MAINTENANCE & REPAIR</option>
                            <option value="998732" <?php echo $item['hsn_code'] == '998732' ? 'selected' : ''; ?>>998732 - INSTALLATION SERVICES</option>
                            <option value="90318000" <?php echo $item['hsn_code'] == '90318000' ? 'selected' : ''; ?>>90318000 - IR/LIGHT/PROXIMITY SENSOR</option>
                        </select>
                    </td>
                    <td><input type="number" name="amount[]" class="form-control amount" value="<?php echo $item['amount']; ?>" required></td>
                    <td>
                        <?php if ($index == 0): ?>
                        <button type="button" id="add-item" class="btn btn-primary add-item-btn">Add Item</button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php else: ?>
                <!-- Template for new item row -->
                <tr>
                    <td>1</td>
                    <td>
                        <select name="item_id" id="item_id" class="form-control item-dropdown" onchange="updateDescription(this)">
                            <option value="">Select an item</option>
                            <?php foreach ($items as $item_option): ?>
                                <option value="<?php echo $item_option['name']; ?>" data-description="<?php echo htmlspecialchars($item_option['description']); ?>"><?php echo $item_option['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="description[]" class="form-control item-input" rows="2" required></textarea>
                    </td>
                    <td>
                        <select name="hsn_code[]" class="form-control hsn-dropdown" required>
                            <option value="">Select HSN Code</option>
                            <option value="84798970">84798970 - MACHINERY</option>
                            <option value="84799040">84799040 - SPARES</option>
                            <option value="998346">998346 - TRIAL SAC</option>
                            <option value="998141">998141 - R & D PHARMACEUTICALS</option>
                            <option value="998142">998142 - R & D AGRICULTURE</option>
                            <option value="998143">998143 - R & D BIOTECHNOLOGY</option>
                            <option value="998144">998144 - R & D COMPUTER SCIENCES</option>
                            <option value="998145">998145 - R & D OTHER FIELDS</option>
                            <option value="998717">998717 - MAINTENANCE & REPAIR</option>
                            <option value="998732">998732 - INSTALLATION SERVICES</option>
                            <option value="90318000">90318000 - IR/LIGHT/PROXIMITY SENSOR</option>
                        </select>
                    </td>
                    <td><input type="number" name="amount[]" class="form-control amount" required></td>
                    <td>
                        <button type="button" id="add-item" class="btn btn-primary add-item-btn">Add Item</button>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
            <td colspan="5">
                <div class="row">
                    
                <!-- Left Column - Calculations -->
                <div class="col-md-4">
                    <h5 class="text-center"><strong>P/F & Freight</strong></h5>
                    <div class="form-row">
                        <div class="col-6 pt-1">Sub Total:</div>
                        <div class="col-6">
                            <input type="number" name="sub_total" id="sub_total" class="form-control readonly-field amount-red" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <div class="d-flex align-items-center mb-2">
                                <span style="display: inline-block; margin-right: 10px;">Packing & Forwarding:</span>
                                <div class="custom-control custom-switch" style="display: inline-block;">
                                    <input type="checkbox" class="custom-control-input" id="pf_type_toggle">
                                    <label class="custom-control-label font-weight-normal" for="pf_type_toggle">Value</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div id="pf_percentage_input">
                                        <div class="input-group">
                                            <input type="number" step="0.1" name="packing_forwarding" id="packing_forwarding" class="form-control" value="<?php echo isset($packing_forwarding) ? $packing_forwarding : '0' ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="pf_direct_input" style="display: none;">
                                        <input type="number" step="0.01" name="packing_forwarding_direct" id="packing_forwarding_direct" class="form-control" value="<?php echo isset($is_direct_value) && $is_direct_value ? $packing_forwarding_amount : '0'; ?>">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Amount:</span>
                                        </div>
                                        <input type="text" id="packing_forwarding_amount" class="form-control readonly-field" readonly>
                                        <input type="hidden" name="packing_forwarding_amount" id="hidden_packing_forwarding_amount">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-6 pt-1">Freight Charges:</div>
                        <div class="col-6">
                            <input type="number" step="0.01" name="freight" id="freight" class="form-control" value="<?php echo isset($freight) ? $freight : '0'; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-6 pt-1"><strong>Total Amount:</strong></div>
                        <div class="col-6">
                            <input type="number" name="total_amount" id="total_amount" class="form-control readonly-field amount-gr" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="freight_note" class="control-label">Note:</label>
                        <input type="text" name="freight_note" id="freight_note" class="form-control" value="<?php echo isset($freight_note) ? $freight_note : ''; ?>">
                    </div>                    
                </div>
                <!-- Middle Column - Taxes -->
                <div class="col-md-4 table-divider">
                    <h5 class="text-center"><strong>Taxes</strong></h5>
                    <div class="form-row">
                    <div class="col-6">
                        IGST(%):
                        <input type="number" step="0.1" name="tax" id="tax" class="form-control" value="<?php echo isset($tax) ? $tax : '0'; ?>">
                    </div>
                    <div class="col-6">
                        Amount:
                        <input type="text" id="tax_amount_display" class="form-control readonly-field" readonly>
                        <input type="hidden" name="tax_amount" id="tax_amount">
                    </div>
                    </div>
                    <div class="form-row">
                    <div class="col-6">
                        CGST(%):
                        <input type="number" step="0.1" name="cgst" id="cgst" class="form-control" value="<?php echo isset($cgst) ? $cgst : '0'; ?>">
                    </div>
                    <div class="col-6">
                        Amount:
                        <input type="text" id="cgst_amount_display" class="form-control readonly-field" readonly>
                        <input type="hidden" name="cgst_amount" id="cgst_amount">
                    </div>
                    </div>
                    <div class="form-row">
                    <div class="col-6">
                        SGST(%):
                        <input type="number" step="0.1" name="sgst" id="sgst" class="form-control" value="<?php echo isset($sgst) ? $sgst : '0'; ?>">
                    </div>
                    <div class="col-6">
                        Amount:
                        <input type="text" id="sgst_amount_display" class="form-control readonly-field" readonly>
                        <input type="hidden" name="sgst_amount" id="sgst_amount">
                    </div>
                    </div>
                    <div class="form-group">
                        <label for="authorized_signatory" class="control-label pt-2">Authorized Signatory:</label>
                        <input type="text" name="authorized_signatory" id="authorized_signatory" class="form-control" value="<?php echo isset($authorized_signatory) ? $authorized_signatory : ''; ?>">
                    </div>
                </div>
                <!-- Right Column - Payment Terms -->
                <div class="col-md-4 table-divider">
                    <h5 class="text-center"><strong>Payment Terms</strong></h5>
                    <div class="form-row">
                        <div class="col-6">
                            Advance Payment(%):
                            <div class="d-flex align-items-center">
                                <input type="number" name="advance_payment" id="advance_payment" class="form-control" value="<?php echo isset($advance_payment) ? $advance_payment : '0'; ?>">
                                <div class="custom-control custom-checkbox ml-2">
                                    <input type="checkbox" name="abg_required" id="abg_checkbox" class="custom-control-input" <?php echo isset($abg_required) && $abg_required ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="abg_checkbox">ABG</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            Amount:
                            <input type="text" id="advance_payment_amount" class="form-control readonly-field" readonly>
                            <input type="hidden" name="advance_payment_amount" id="hidden_advance_payment_amount">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-6">
                            <div class="d-flex align-items-center mb-2">
                                <span id="payment_type_label">Against FAT</span>
                                <button type="button" id="inspection_type_toggle" class="btn btn-link btn-sm" style="padding: 0 5px; margin-left: 5px;" title="Cycle through payment types">
                                    <i class="fas fa-sort"></i>
                                </button>
                                <input type="hidden" name="inspection_payment_type" id="inspection_payment_type_hidden" value="<?php echo isset($inspection_payment_type) ? $inspection_payment_type : 'inspection'; ?>">
                            </div>
                            <label class="mt-2">Inspection Payment(%):</label>
                            <input type="number" name="inspection_payment" id="inspection_payment" class="form-control" value="<?php echo isset($inspection_payment) ? $inspection_payment : '0'; ?>">
                        </div>
                        <div class="col-6 pt-2">
                            Amount:
                            <input type="text" id="inspection_payment_amount" class="form-control readonly-field" readonly>
                            <input type="hidden" name="inspection_payment_amount" id="hidden_inspection_payment_amount">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-6">
                            Installation Payment(%):
                            <div class="d-flex align-items-center">
                                <input type="number" name="installation_payment" id="installation_payment" class="form-control" value="<?php echo isset($installation_payment) ? $installation_payment : '0'; ?>">
                                <div class="custom-control custom-checkbox ml-2">
                                    <input type="checkbox" name="pbg_required" id="pbg_checkbox" class="custom-control-input" <?php echo isset($pbg_required) && $pbg_required ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="pbg_checkbox">PBG</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            Amount:
                            <input type="text" id="installation_payment_amount" class="form-control readonly-field" readonly>
                            <input type="hidden" name="installation_payment_amount" id="hidden_installation_payment_amount">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-6">
                            Credit Payment (Days):
                            <div class="input-group">
                                <input type="number" name="credit_payment_days" id="credit_payment_days" class="form-control" value="<?php echo isset($credit_payment_days) ? $credit_payment_days : '0'; ?>">
                                <div class="input-group-append">
                                    <span class="input-group-text">Days</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            Amount:
                            <input type="text" id="credit_payment_amount" class="form-control readonly-field" readonly>
                            <input type="hidden" name="credit_payment_amount" id="hidden_credit_payment_amount">
                        </div>
                    </div>                    
                </div>
                </div>
            </td>
            </tr>
        </tfoot>
    </table>
    <div class="button-container">    
    <button type="submit" id="save-btn" class="btn btn-primary">Save</button>
    <button type="button" id="cancel-btn" class="btn btn-secondary">Cancel</button>
    </div>
</form>



<script>

    
function updateDescription(selectElement) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var itemName = selectedOption.text;
    var description = selectedOption.getAttribute('data-description');
    var textarea = selectElement.closest('td').querySelector('textarea');
    
    // Combine name and description as "name (description)"
    textarea.value = description ? `${itemName} (${description})` : itemName;
}

$(document).ready(function(){
    function calculateTotal() {
        var subTotal = 0;

        // Calculate sub-total from item amounts
        $('#item-table tbody tr').each(function() {
            var amount = parseFloat($(this).find('.amount').val()) || 0;
            subTotal += amount;
        });

        $('#sub_total').val(subTotal.toFixed(2));

        // Get other charges
        var packing_forwarding = parseFloat($('#packing_forwarding').val()) || 0;
        var packing_forwarding_direct = parseFloat($('#packing_forwarding_direct').val()) || 0;
        var freight = parseFloat($('#freight').val()) || 0; // Freight as a direct amount
        var tax = parseFloat($('#tax').val()) || 0;
        var cgst = parseFloat($('#cgst').val()) || 0;
        var sgst = parseFloat($('#sgst').val()) || 0;

        // Calculate amounts
        var packingForwardingAmount = $('#pf_type_toggle').is(':checked') ? packing_forwarding_direct : (subTotal * (packing_forwarding / 100)).toFixed(2);
        var totalWithPacking = subTotal + parseFloat(packingForwardingAmount) + parseFloat(freight); // Add Freight here
        var taxAmount = (totalWithPacking * (tax / 100)).toFixed(2);
        var cgstAmount = (totalWithPacking * (cgst / 100)).toFixed(2);
        var sgstAmount = (totalWithPacking * (sgst / 100)).toFixed(2);
        var totalWithTax = totalWithPacking + parseFloat(taxAmount) + parseFloat(cgstAmount) + parseFloat(sgstAmount);

        // Update calculated fields
        $('#packing_forwarding_amount').val(packingForwardingAmount);
        $('#tax_amount').val(taxAmount);
        $('#cgst_amount').val(cgstAmount);
        $('#sgst_amount').val(sgstAmount);
        $('#total_amount').val(totalWithTax.toFixed(2));

        // Call payment terms calculation
        calculatePaymentTerms(subTotal, totalWithPacking, totalWithTax, taxAmount, cgstAmount, sgstAmount, packingForwardingAmount, freight);
    }

    function calculatePaymentTerms(subTotal, totalWithPacking, totalWithTax, taxAmount, cgstAmount, sgstAmount, packingForwardingAmount, freight) {
    const advance_payment = parseFloat($('#advance_payment').val()) || 0;
    const inspection_payment = parseFloat($('#inspection_payment').val()) || 0;
    const installation_payment = parseFloat($('#installation_payment').val()) || 0;

    let advancePaymentAmount = 0;
    let inspectionPaymentAmount = 0;
    let installationPaymentAmount = 0;
    let creditPaymentAmount = 0;

    // All extra charges
    const extraCharges = parseFloat(packingForwardingAmount) + 
                         parseFloat(freight) + 
                         parseFloat(taxAmount) + 
                         parseFloat(cgstAmount) + 
                         parseFloat(sgstAmount);

    // 100% single payments
    if (advance_payment === 100) {
        // Advance should include everything (subtotal + packing/forwarding + freight + taxes)
        advancePaymentAmount = totalWithTax;
        inspectionPaymentAmount = 0;
        installationPaymentAmount = 0;
    } else if (inspection_payment === 100) {
        inspectionPaymentAmount = subTotal + extraCharges;
        advancePaymentAmount = 0;
        installationPaymentAmount = 0;
    } else if (installation_payment === 100) {
        // Installation should also include everything (subtotal + packing/forwarding + freight + taxes)
        installationPaymentAmount = totalWithTax;
        advancePaymentAmount = 0;
        inspectionPaymentAmount = 0;
    }
    // Split payments (e.g. 40/50/10, 90/10, etc.)
    else if ((advance_payment > 0 || inspection_payment > 0 || installation_payment > 0) && (advance_payment + inspection_payment + installation_payment === 100)) {
        // Advance on subtotal
        advancePaymentAmount = (subTotal * (advance_payment / 100));
        // Inspection on subtotal + all extra charges
        inspectionPaymentAmount = (subTotal * (inspection_payment / 100)) + extraCharges;
        // Installation on subtotal
        installationPaymentAmount = (subTotal * (installation_payment / 100));
    }
    // Handle other cases (partial payments)
    else {
        if (advance_payment > 0) {
            advancePaymentAmount = (subTotal * (advance_payment / 100));
        }
        if (inspection_payment > 0) {
            inspectionPaymentAmount = (subTotal * (inspection_payment / 100)) + extraCharges;
        }
        if (installation_payment > 0) {
            installationPaymentAmount = (subTotal * (installation_payment / 100));
        }
    }

    // Calculate credit payment as remaining amount
    const totalPayments = advancePaymentAmount + inspectionPaymentAmount + installationPaymentAmount;
    creditPaymentAmount = Math.max(0, (subTotal + extraCharges) - totalPayments);

    // Update form fields
    $('#advance_payment_amount').val(advancePaymentAmount.toFixed(2));
    $('#hidden_advance_payment_amount').val(advancePaymentAmount.toFixed(2));
    
    $('#inspection_payment_amount').val(inspectionPaymentAmount.toFixed(2));
    $('#hidden_inspection_payment_amount').val(inspectionPaymentAmount.toFixed(2));
    
    $('#installation_payment_amount').val(installationPaymentAmount.toFixed(2));
    $('#hidden_installation_payment_amount').val(installationPaymentAmount.toFixed(2));
    
    $('#credit_payment_amount').val(creditPaymentAmount.toFixed(2));
    $('#hidden_credit_payment_amount').val(creditPaymentAmount.toFixed(2));
}

    // Trigger calculations on input changes
    $(document).on('input', 
        '.amount, #packing_forwarding, #packing_forwarding_direct, #freight, ' + 
        '#tax, #cgst, #sgst, #advance_payment, #inspection_payment, ' +
        '#installation_payment, #credit_payment_days', 
        function() {
            calculateTotal();
    });

    // Initial calculation on page load
    $(document).ready(function() {
        calculateTotal();
    });

    $('#add-item').click(function() {
        var rowCount = $('#item-table tbody tr').length + 1;
        var newRow = `<tr>
            <td>${rowCount}</td>
            <td>
                <select class="form-control item-dropdown" onchange="updateDescription(this)">
                    <option value="">Select an item</option>
                    <?php foreach($items as $item_option): ?>
                        <option value="<?php echo $item_option['name']; ?>" data-description="<?php echo htmlspecialchars($item_option['description']); ?>"><?php echo $item_option['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="description[]" class="form-control item-input" rows="2" required></textarea>
            </td>
            <td>
                <select name="hsn_code[]" class="form-control hsn-dropdown" required>
                    <option value="">Select HSN Code</option>
                    <option value="84798970">84798970 - MACHINERY</option>
                    <option value="84799040">84799040 - SPARES</option>
                    <option value="998346">998346 - TRIAL SAC</option>
                    <option value="998141">998141 - R & D PHARMACEUTICALS</option>
                    <option value="998142">998142 - R & D AGRICULTURE</option>
                    <option value="998143">998143 - R & D BIOTECHNOLOGY</option>
                    <option value="998144">998144 - R & D COMPUTER SCIENCES</option>
                    <option value="998145">998145 - R & D OTHER FIELDS</option>
                    <option value="998717">998717 - MAINTENANCE & REPAIR</option>
                    <option value="998732">998732 - INSTALLATION SERVICES</option>
                    <option value="90318000">90318000 - IR/LIGHT/PROXIMITY SENSOR</option>
                </select>
            </td>
            <td><input type="number" name="amount[]" class="form-control amount" required></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-item">Remove</button></td>
        </tr>`;
        $('#item-table tbody').append(newRow);
        updateSrNo();
    });

    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateSrNo();
        calculateTotal();
    });

    $(document).on('input', '.amount, #packing_forwarding, #packing_forwarding_direct, #tax, #cgst, #sgst, #advance_payment, #inspection_payment, #installation_payment', function() {
        calculateTotal();
    });

    function updateSrNo() {
        $('#item-table tbody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    calculateTotal();
    updateSrNo();

    // Recalculate total on input change
    $('#packing_forwarding, #packing_forwarding_direct, #tax, #cgst, #sgst, #advance_payment, #inspection_payment, #installation_payment').on('input', calculateTotal);

    // Toggle packing forwarding input type
    $('#pf_type_toggle').change(function() {
        if ($(this).is(':checked')) {
            $('#pf_percentage_input').hide();
            $('#pf_direct_input').show();
        } else {
            $('#pf_percentage_input').show();
            $('#pf_direct_input').hide();
        }
        calculateTotal();
    });

    // Set initial state of packing forwarding toggle and values
    <?php if (isset($is_direct_value) && $is_direct_value): ?>
    $('#pf_type_toggle').prop('checked', true);
    $('#pf_percentage_input').hide();
    $('#pf_direct_input').show();
    <?php endif; ?>
});

$('#cancel-btn').click(function() {
        window.location.href = _base_url_ + "admin/?page=proforma_invoice";
    });

function calculateAll() {
    // Calculate subtotal
    let subTotal = 0;
    $('#item-table tbody tr').each(function() {
        const amount = parseFloat($(this).find('.amount').val()) || 0;
        subTotal += amount;
    });

    // Get all rates with default 0 for empty values
    const packing_forwarding = parseFloat($('#packing_forwarding').val()) || 0;
    const packing_forwarding_direct = parseFloat($('#packing_forwarding_direct').val()) || 0;
    const freight = parseFloat($('#freight').val()) || 0;
    const tax = parseFloat($('#tax').val()) || 0;
    const cgst = parseFloat($('#cgst').val()) || 0;
    const sgst = parseFloat($('#sgst').val()) || 0;

    // Calculate amounts in order
    const packingForwardingAmount = $('#pf_type_toggle').is(':checked') ? packing_forwarding_direct : (subTotal * (packing_forwarding / 100)) || 0;
    const baseForTax = subTotal + packingForwardingAmount + freight;
    const taxAmount = (baseForTax * (tax / 100)) || 0;
    const cgstAmount = (baseForTax * (cgst / 100)) || 0;
    const sgstAmount = (baseForTax * (sgst / 100)) || 0;
    const totalAmount = baseForTax + taxAmount + cgstAmount + sgstAmount;

    // Update form fields with default 0 for empty values
    $('#sub_total').val(subTotal.toFixed(2));
    $('#packing_forwarding_amount').val(packingForwardingAmount.toFixed(2));
    $('#hidden_packing_forwarding_amount').val(packingForwardingAmount.toFixed(2));
    $('#tax_amount_display').val(taxAmount.toFixed(2));
    $('#tax_amount').val(taxAmount.toFixed(2));
    $('#cgst_amount_display').val(cgstAmount.toFixed(2));
    $('#cgst_amount').val(cgstAmount.toFixed(2));
    $('#sgst_amount_display').val(sgstAmount.toFixed(2));
    $('#sgst_amount').val(sgstAmount.toFixed(2));
    $('#total_amount').val(totalAmount.toFixed(2));

    // Payment terms calculations with default 0
    const advance_payment = parseFloat($('#advance_payment').val()) || 0;
    const inspection_payment = parseFloat($('#inspection_payment').val()) || 0;
    const installation_payment = parseFloat($('#installation_payment').val()) || 0;

    const advancePaymentAmount = advance_payment === 100 ? totalAmount : ((subTotal * (advance_payment / 100)) || 0);
    const inspectionPaymentAmount = inspection_payment === 100 ? totalAmount : 
        ((subTotal * (inspection_payment / 100)) + packingForwardingAmount + freight + taxAmount + cgstAmount + sgstAmount) || 0;
    const installationPaymentAmount = installation_payment === 100 ? totalAmount : ((subTotal * (installation_payment / 100)) || 0);

    // Calculate credit payment amount (remaining amount)
    const totalPayments = advancePaymentAmount + inspectionPaymentAmount + installationPaymentAmount;
    const creditPaymentAmount = totalAmount - totalPayments;

    $('#advance_payment_amount').val(parseFloat(advancePaymentAmount).toFixed(2));
    $('#hidden_advance_payment_amount').val(parseFloat(advancePaymentAmount).toFixed(2));
    $('#inspection_payment_amount').val(parseFloat(inspectionPaymentAmount).toFixed(2));
    $('#hidden_inspection_payment_amount').val(parseFloat(inspectionPaymentAmount).toFixed(2));
    $('#installation_payment_amount').val(parseFloat(installationPaymentAmount).toFixed(2));
    $('#hidden_installation_payment_amount').val(parseFloat(installationPaymentAmount).toFixed(2));
    $('#credit_payment_amount').val(parseFloat(creditPaymentAmount).toFixed(2));
    $('#hidden_credit_payment_amount').val(parseFloat(creditPaymentAmount).toFixed(2));
}

// Update event handlers
$(document).ready(function() {
    // Remove old calculation functions
    $('.amount, #packing_forwarding, #packing_forwarding_direct, #freight, #tax, #cgst, #sgst, #advance_payment, #inspection_payment, #installation_payment')
        .off('input')
        .on('input', calculateAll);

    // Initial calculation
    calculateAll();
});

$(document).ready(function() {
    // ...existing code...
    
    // Toggle between percentage and direct value for packing forwarding
    $('#pf_type_toggle').change(function() {
        const isDirectValue = $(this).is(':checked');
        $('#pf_percentage_input').toggle(!isDirectValue);
        $('#pf_direct_input').toggle(isDirectValue);
        
        // Reset values when switching
        if(isDirectValue) {
            $('#packing_forwarding').val(0);
        } else {
            $('#packing_forwarding_direct').val(0);
        }
        calculateAll();
    });

    // Modify calculateAll function to handle both percentage and direct value
    function calculateAll() {
        let subTotal = 0;
        $('#item-table tbody tr').each(function() {
            const amount = parseFloat($(this).find('.amount').val()) || 0;
            subTotal += amount;
        });

        // Handle packing forwarding based on type
        const isDirectValue = $('#pf_type_toggle').is(':checked');
        let packingForwardingAmount = 0;
        if (isDirectValue) {
            packingForwardingAmount = parseFloat($('#packing_forwarding_direct').val()) || 0;
        } else {
            const packing_forwarding = parseFloat($('#packing_forwarding').val()) || 0;
            packingForwardingAmount = (subTotal * (packing_forwarding / 100)) || 0;
        }

        const freight = parseFloat($('#freight').val()) || 0;
        const tax = parseFloat($('#tax').val()) || 0;
        const cgst = parseFloat($('#cgst').val()) || 0;
        const sgst = parseFloat($('#sgst').val()) || 0;

        // Rest of your existing calculation code...
        const baseForTax = subTotal + packingForwardingAmount + freight;
        const taxAmount = (baseForTax * (tax / 100)) || 0;
        const cgstAmount = (baseForTax * (cgst / 100)) || 0;
        const sgstAmount = (baseForTax * (sgst / 100)) || 0;
        const totalAmount = baseForTax + taxAmount + cgstAmount + sgstAmount;

        // Update form fields
        $('#sub_total').val(subTotal.toFixed(2));
        $('#packing_forwarding_amount').val(packingForwardingAmount.toFixed(2));
        $('#hidden_packing_forwarding_amount').val(packingForwardingAmount.toFixed(2));
        // ...rest of your existing update code...
    }

    // Add event listener for direct value input
    $('#packing_forwarding_direct').on('input', calculateAll);
});

$(document).ready(function() {
    // Inspection payment type toggle cycle handler
    const typeLabels = {
        'inspection': 'Against FAT',
        'prior_dispatch': 'Prior to Dispatch',
        'delivery': 'Against Delivery'
    };
    
    const typeOrder = ['inspection', 'prior_dispatch', 'delivery'];
    
    // Set initial label on page load
    const initialValue = $('#inspection_payment_type_hidden').val();
    $('#payment_type_label').text(typeLabels[initialValue]);
    
    $('#inspection_type_toggle').click(function(e) {
        e.preventDefault();
        const currentValue = $('#inspection_payment_type_hidden').val();
        const currentIndex = typeOrder.indexOf(currentValue);
        const nextIndex = (currentIndex + 1) % typeOrder.length;
        const nextValue = typeOrder[nextIndex];
        
        $('#inspection_payment_type_hidden').val(nextValue);
        $('#payment_type_label').text(typeLabels[nextValue]);
    });
});

// Add this to your existing JavaScript code
$(document).ready(function() {
    // When form is submitted, ensure checkbox values are included
    $('#manage-pi').on('submit', function() {
        var abgRequired = $('#abg_checkbox').is(':checked') ? 1 : 0;
        var pbgRequired = $('#pbg_checkbox').is(':checked') ? 1 : 0;

        // Add hidden fields to ensure values are sent even if checkboxes are unchecked
        $(this).append(`
            <input type="hidden" name="abg_required" value="${abgRequired}">
            <input type="hidden" name="pbg_required" value="${pbgRequired}">
        `);
    });
});

// Replace the existing checkbox handling JavaScript with this:

$(document).ready(function() {
    // When form is submitted
    $('#manage-pi').on('submit', function() {
        // Remove any previously added hidden fields
        $('input[name="abg_required"]').not('#abg_checkbox').remove();
        $('input[name="pbg_required"]').not('#pbg_checkbox').remove();

        // Get current checkbox states
        var abgRequired = $('#abg_checkbox').is(':checked');
        var pbgRequired = $('#pbg_checkbox').is(':checked');

        // Update or add hidden fields with current values
        if (!$('#abg_checkbox').length) {
            $(this).append(`<input type="hidden" name="abg_required" value="${abgRequired ? 1 : 0}">`);
        }
        if (!$('#pbg_checkbox').length) {
            $(this).append(`<input type="hidden" name="pbg_required" value="${pbgRequired ? 1 : 0}">`);
        }
    });

    // Add change event listeners to checkboxes
    $('#abg_checkbox, #pbg_checkbox').on('change', function() {
        var isChecked = $(this).is(':checked');
        $(this).val(isChecked ? 1 : 0);
    });
});

</script>
<script>
$(document).ready(function() {
    $('#client_id').select2({
        placeholder: "Select a client",
        allowClear: true,
    });
    $('#item_id').select2({
        placeholder: "Select an item",
        allowClear: true
    });
});
</script>
<script>
// ...existing code...

$(document).ready(function() {
    // Add form validation
    $('#manage-pi').on('submit', function(e) {
        var company = $('#company').val();
        if (!company) {
            e.preventDefault();
            alert('Please select a company');
            $('#company').focus();
            return false;
        }
    });
});
</script>
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $client_id = $_POST['client_id'];
    $po_code = $_POST['po_code'];
    $total_amount = $_POST['total_amount'];
    $packing_forwarding = $_POST['packing_forwarding'];
    $tax = $_POST['tax'];
    $cgst = $_POST['cgst'];
    $sgst = $_POST['sgst'];
    $advance_payment = $_POST['advance_payment'];
    $advance_payment_amount = $_POST['advance_payment_amount'];
    $inspection_payment = $_POST['inspection_payment'];
    $inspection_payment_amount = $_POST['inspection_payment_amount'];
    $installation_payment = $_POST['installation_payment'];
    $installation_payment_amount = $_POST['installation_payment_amount'];
    $credit_payment_days = isset($_POST['credit_payment_days']) ? $_POST['credit_payment_days'] : 0;
    $credit_payment_amount = isset($_POST['credit_payment_amount']) ? $_POST['credit_payment_amount'] : 0;
    $employee_approved_by = '';
    $admin_approved_by = '';
    $freight_note = isset($_POST['freight_note']) ? $_POST['freight_note'] : '';
    $authorized_signatory = isset($_POST['authorized_signatory']) ? $_POST['authorized_signatory'] : '';
    $inspection_payment_type = isset($_POST['inspection_payment_type']) ? $_POST['inspection_payment_type'] : 'inspection';

    // Validate employee passcode
    if (!empty($_POST['employee_passcode'])) {
        $employee_passcode = $_POST['employee_passcode'];
        $qry = $conn->query("SELECT name FROM approvers WHERE role = 'employee' AND passcode = '{$employee_passcode}'");
        if ($qry->num_rows > 0) {
            $employee_approved_by = $qry->fetch_assoc()['name'];
        }
    }

    // Validate admin passcode
    if (!empty($_POST['admin_passcode'])) {
        $admin_passcode = $_POST['admin_passcode'];
        $qry = $conn->query("SELECT name FROM approvers WHERE role = 'admin' AND passcode = '{$admin_passcode}'");
        if ($qry->num_rows > 0) {
            $admin_approved_by = $qry->fetch_assoc()['name'];
        }
    }

    

    // Update the INSERT query with all fields including freight
$query = "INSERT INTO proforma_invoice_list (
    po_code,
    work_order_number,
    po_date_created,
    client_id,
    total_amount,
    packing_forwarding,
    freight,
    tax,
    cgst,
    sgst,
    advance_payment,
    advance_payment_amount,
    inspection_payment,
    inspection_payment_amount,
    installation_payment,
    installation_payment_amount,
    sub_total,
    sgst_amount,
    cgst_amount,
    tax_amount,
    packing_forwarding_amount,
    company,
    credit_payment_days,
    credit_payment_amount,
    freight_note,
    authorized_signatory,
    inspection_payment_type,
    abg_required,
    pbg_required,
    work_order_number
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    $response = array('status' => 'error', 'message' => 'Prepare failed: ' . htmlspecialchars($conn->error));
    echo json_encode($response);
    exit;
}

// Update bind_param with correct number of parameters and types
$stmt->bind_param(
    "sssidddddddddddddddddsddsssiis",  // 28 parameters: 3 strings, 1 integer, 16 decimals, 1 string, 2 decimals, 3 strings, 2 integers
    $po_code,
    $work_order_number,
    $po_date_created,
    $client_id,
    $total_amount,
    $packing_forwarding,
    $freight,
    $tax,
    $cgst,
    $sgst,
    $advance_payment,
    $advance_payment_amount,
    $inspection_payment,
    $inspection_payment_amount,
    $installation_payment,
    $installation_payment_amount,
    $sub_total,
    $sgst_amount,
    $cgst_amount,
    $tax_amount,
    $packing_forwarding_amount,
    $company,
    $credit_payment_days,
    $credit_payment_amount,
    $freight_note,
    $authorized_signatory,
    $inspection_payment_type,
    $abg_required,
    $pbg_required,
    $work_order_number
);

    $stmt->execute();

    // Check if the query was successful
    if ($stmt->affected_rows > 0) {
        // Save items
        $stmt->close();

        // Get the last inserted id if it's a new record
        if (empty($id)) {
            $id = $conn->insert_id;
        }

        $conn->query("DELETE FROM proforma_invoice_items WHERE proforma_invoice_id = '{$id}'");
        $stmt = $conn->prepare("INSERT INTO proforma_invoice_items (proforma_invoice_id, description, amount) VALUES (?, ?, ?)");
        foreach ($_POST['description'] as $key => $description) {
            $amount = $_POST['amount'][$key];
            $stmt->bind_param("isd", $id, $description, $amount);
            $stmt->execute();
        }

        // Set default values if not provided
        $packing_forwarding = isset($packing_forwarding) ? $packing_forwarding : 0;
        $tax = isset($tax) ? $tax : 0;
        $cgst = isset($cgst) ? $cgst : 0;
        $sgst = isset($sgst) ? $sgst : 0;
        $advance_payment = isset($advance_payment) ? $advance_payment : 0;
        $inspection_payment = isset($inspection_payment) ? $inspection_payment : 0;
        $installation_payment = isset($installation_payment) ? $installation_payment : 0;

        $response = array('status' => 'success', 'po_code' => $po_code);
    } else {
        $response = array('status' => 'error', 'message' => 'Execute failed: ' . htmlspecialchars($stmt->error));
    }

    echo json_encode($response);
}
?>
