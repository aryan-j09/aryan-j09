<?php
require_once('../../../initialize.php');
require_once('../../../classes/DBConnection.php');
require_once('../../../classes/Login.php');
require_once('../../../classes/SerialNumberGenerator.php');
require_once('../../../classes/QRCodeGenerator.php');

// Check authentication
if (!isset($_SESSION['login_id'])) {
    header('Location: ' . base_url . 'admin/?page=login');
    exit;
}

$db = new DBConnection();
$conn = $db->conn;
$user_id = $_SESSION['login_id'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'get_pos') {
        $sql = "SELECT pol.id, pol.po_code, COUNT(DISTINCT poi.item_id) as total_items, COALESCE(SUM(poi.quantity), 0) as total_quantity
                FROM purchase_order_list pol 
                LEFT JOIN po_items poi ON pol.id = poi.po_id
                WHERE (pol.status = 'active' OR pol.status IS NULL)
                GROUP BY pol.id, pol.po_code
                ORDER BY pol.po_code DESC";
        
        $result = $conn->query($sql);
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
            exit;
        }
        
        $pos = [];
        while ($row = $result->fetch_assoc()) {
            $pos[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $pos]);
        exit;
    }

    elseif ($action === 'get_items') {
        $po_id = intval($_POST['po_id']);
        $sql = "SELECT poi.id as po_item_id, poi.quantity as ordered_qty, il.id as item_id, il.name as item_name,
                       COALESCE(SUM(CASE WHEN sm.movement_type = 'IN' THEN sm.quantity ELSE 0 END), 0) as received_qty
                FROM po_items poi
                JOIN item_list il ON poi.item_id = il.id
                LEFT JOIN stock_movement sm ON sm.item_id = il.id AND sm.reference_type = 'PO' AND sm.reference_id = ?
                WHERE poi.po_id = ?
                GROUP BY poi.id, il.id
                ORDER BY il.name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $po_id, $po_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $items]);
        exit;
    }

    elseif ($action === 'generate_serials') {
        $po_id = intval($_POST['po_id']);
        $po_code = $_POST['po_code'];
        $items = json_decode($_POST['items'], true);
        
        $serial_gen = new SerialNumberGenerator($conn);
        $generated_serials = [];
        
        foreach ($items as $item) {
            $result = $serial_gen->generateSerials(
                $po_id, 
                intval($item['po_item_id']), 
                intval($item['item_id']), 
                intval($item['quantity']), 
                $po_code, 
                $item['item_name']
            );
            
            if ($result['success']) {
                $serials = $serial_gen->getSerials($po_id, intval($item['po_item_id']));
                foreach ($serials as $serial) {
                    $generated_serials[] = [
                        'id' => $serial['id'],
                        'serial_number' => $serial['serial_number'],
                        'item_name' => $item['item_name']
                    ];
                }
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Generated ' . count($generated_serials) . ' serial numbers',
            'serials' => $generated_serials
        ]);
        exit;
    }

    elseif ($action === 'generate_qr') {
        $serial_ids = json_decode($_POST['serial_ids'], true);
        
        if (empty($serial_ids)) {
            echo json_encode(['success' => false, 'error' => 'No serials selected']);
            exit;
        }
        
        $qr_gen = new QRCodeGenerator($conn);
        $result = $qr_gen->generateBulkQR($serial_ids, $user_id);
        
        echo json_encode($result);
        exit;
    }

    elseif ($action === 'confirm_receipt') {
        $po_id = intval($_POST['po_id']);
        $serial_ids = json_decode($_POST['serial_ids'], true);
        $remarks = $_POST['remarks'] ?? '';
        
        $conn->begin_transaction();
        
        try {
            $placeholders = implode(',', array_fill(0, count($serial_ids), '?'));
            $sql = "UPDATE item_serial_numbers SET status = 'received', received_date = NOW(), received_by = ?, remarks = ? WHERE id IN ($placeholders)";
            
            $stmt = $conn->prepare($sql);
            $types = "si" . str_repeat("i", count($serial_ids));
            $bind_params = [$user_id, $remarks];
            foreach ($serial_ids as $id) {
                $bind_params[] = intval($id);
            }
            
            $stmt->bind_param($types, ...$bind_params);
            $stmt->execute();
            
            // Log to stock_movement
            $stmt = $conn->prepare("SELECT * FROM item_serial_numbers WHERE id IN ($placeholders)");
            $serial_ids_int = array_map('intval', $serial_ids);
            $stmt->bind_param(str_repeat("i", count($serial_ids_int)), ...$serial_ids_int);
            $stmt->execute();
            $serials_result = $stmt->get_result();
            
            $stmt_log = $conn->prepare(
                "INSERT INTO stock_movement (item_id, movement_type, quantity, reference_type, reference_id, serial_number_id, remarks, created_by, created_at)
                 VALUES (?, 'IN', ?, 'PO', ?, ?, ?, ?, NOW())"
            );
            
            while ($serial = $serials_result->fetch_assoc()) {
                $qty = intval($serial['quantity']);
                $stmt_log->bind_param("iiissi", 
                    $serial['item_id'],
                    $qty,
                    $serial['po_id'],
                    $serial['id'],
                    $remarks,
                    $user_id
                );
                $stmt_log->execute();
            }
            
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => count($serial_ids) . ' items received and tracked']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Display the page (not a modal)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receive Stock with Serial Numbers</title>
    <link rel="stylesheet" href="<?php echo base_url; ?>plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo base_url; ?>plugins/fontawesome-free/css/all.min.css">
    <style>
        body { background-color: #f5f5f5; padding: 20px; }
        .page-container { max-width: 1000px; margin: 0 auto; }
        .card { border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        code { background-color: #f5f5f5; padding: 4px 8px; border-radius: 3px; font-size: 0.85em; }
        .section-hidden { display: none; }
        .btn-group-justified { display: flex; gap: 10px; }
    </style>
</head>
<body>
<div class="page-container">
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="m-0"><i class="fa fa-barcode"></i> Receive Stock with Serial Numbers</h3>
            <small>Track individual items & generate QR codes</small>
        </div>
        <div class="card-body">
            <a href="<?php echo base_url; ?>admin/?page=receiving" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left"></i> Back to Receiving
            </a>
        </div>
    </div>

    <form id="receiving_form">
        <!-- Step 1: PO Selection -->
        <div class="card card-primary mb-3">
            <div class="card-header">
                <h5 class="card-title m-0">Step 1: Select Purchase Order</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="po_select"><strong>PO Code *</strong></label>
                    <select class="form-control form-control-lg" id="po_select" required>
                        <option value="">-- Loading POs --</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Step 2: Items Section -->
        <div id="items_section" class="section-hidden card card-success mb-3">
            <div class="card-header">
                <h5 class="card-title m-0">Step 2: Select Items & Quantities</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Item Name</th>
                                <th width="80">Ordered</th>
                                <th width="80">Received</th>
                                <th width="120">Qty to Receive</th>
                            </tr>
                        </thead>
                        <tbody id="items_tbody"></tbody>
                    </table>
                </div>

                <div class="form-group">
                    <label for="remarks">Remarks (Optional)</label>
                    <textarea class="form-control" id="remarks" placeholder="Add any notes..." rows="2"></textarea>
                </div>

                <div class="btn-group-justified">
                    <button type="button" class="btn btn-success" id="generate_serials_btn">
                        <i class="fa fa-cogs"></i> Generate Serial Numbers
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 3: Serials Section -->
        <div id="serials_section" class="section-hidden card card-info mb-3">
            <div class="card-header">
                <h5 class="card-title m-0">Step 3: Review Generated Serials (<span id="serial_count">0</span>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select_all" title="Select all">
                                </th>
                                <th>Serial Number</th>
                                <th>Item Name</th>
                            </tr>
                        </thead>
                        <tbody id="serials_tbody"></tbody>
                    </table>
                </div>

                <div class="btn-group-justified">
                    <button type="button" class="btn btn-secondary" id="back_items_btn">
                        <i class="fa fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-info" id="generate_qr_btn">
                        <i class="fa fa-qrcode"></i> Generate QR Codes
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: QR Section -->
        <div id="qr_section" class="section-hidden card card-warning mb-3">
            <div class="card-header">
                <h5 class="card-title m-0">Step 4: QR Codes Generated - Ready to Confirm</h5>
            </div>
            <div class="card-body">
                <div id="qr_preview" class="row mb-3"></div>

                <div class="btn-group-justified">
                    <button type="button" class="btn btn-secondary" id="back_serials_btn">
                        <i class="fa fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-success btn-lg" id="confirm_receipt_btn">
                        <i class="fa fa-check"></i> Confirm Receipt
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="<?php echo base_url; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url; ?>libs/js/sweetalert.min.js"></script>

<script>
$(document).ready(function() {
    let current_po = null;
    let serials = [];
    let selected_ids = [];
    let base_url = '<?php echo base_url; ?>';
    let ajax_url = base_url + 'admin/?page=receiving/serial_receive';

    // Load POs on page load
    loadPOs();

    function loadPOs() {
        $.post(ajax_url, {action: 'get_pos'}, function(r) {
            console.log('PO Response:', r);
            if (r.status === 'success') {
                $('#po_select').empty().append('<option value="">-- Select PO --</option>');
                if (r.data && r.data.length > 0) {
                    r.data.forEach(po => {
                        $('#po_select').append(`<option value="${po.id}" data-code="${po.po_code}">${po.po_code} (${po.total_items} items)</option>`);
                    });
                } else {
                    alert_toast('No active POs found', 'warning');
                }
            } else {
                alert_toast('Error: ' + (r.message || 'Could not load POs'), 'error');
            }
        }, 'json').fail(function(jqXHR, status, error) {
            console.error('AJAX Error:', status, error);
            alert_toast('Connection error: ' + error, 'error');
        });
    }

    // PO Selection
    $('#po_select').change(function() {
        if (this.value) {
            current_po = {id: this.value, code: $(this.options[this.selectedIndex]).data('code')};
            loadItems(current_po.id);
            $('#items_section').show();
        } else {
            $('#items_section').hide();
        }
    });

    function loadItems(po_id) {
        $.post(ajax_url, {action: 'get_items', po_id: po_id}, function(r) {
            if (r.status === 'success') {
                let tbody = $('#items_tbody').empty();
                if (r.data.length === 0) {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted">No items in this PO</td></tr>');
                    return;
                }
                r.data.forEach(item => {
                    let remain = item.ordered_qty - item.received_qty;
                    tbody.append(`<tr>
                        <td>${item.item_name}</td>
                        <td><strong>${item.ordered_qty}</strong></td>
                        <td><strong>${item.received_qty}</strong></td>
                        <td><input type="number" class="form-control form-control-sm qty" 
                            data-id="${item.item_id}" data-poid="${item.po_item_id}" 
                            data-name="${item.item_name}" min="0" max="${remain}" value="0"></td>
                    </tr>`);
                });
            }
        }, 'json');
    }

    // Generate Serials
    $('#generate_serials_btn').click(function() {
        let items = [];
        $('.qty').each(function() {
            let qty = parseInt(this.value) || 0;
            if (qty > 0) {
                items.push({
                    po_item_id: $(this).data('poid'),
                    item_id: $(this).data('id'),
                    item_name: $(this).data('name'),
                    quantity: qty
                });
            }
        });

        if (!items.length) {
            alert_toast('Enter quantity for at least one item', 'warning');
            return;
        }

        $.post(ajax_url, {
            action: 'generate_serials',
            po_id: current_po.id,
            po_code: current_po.code,
            items: JSON.stringify(items)
        }, function(r) {
            if (r.status === 'success') {
                serials = r.serials;
                showSerials();
                alert_toast(r.message, 'success');
            } else {
                alert_toast(r.message, 'error');
            }
        }, 'json');
    });

    function showSerials() {
        $('#serial_count').text(serials.length);
        let tbody = $('#serials_tbody').empty();
        serials.forEach(s => {
            tbody.append(`<tr>
                <td><input type="checkbox" class="scheck" value="${s.id}" checked></td>
                <td><code>${s.serial_number}</code></td>
                <td>${s.item_name}</td>
            </tr>`);
        });
        $('#items_section').hide();
        $('#serials_section').show();
    }

    // Select All
    $('#select_all').change(function() {
        $('.scheck').prop('checked', this.checked);
    });

    // Back buttons
    $('#back_items_btn').click(() => {
        $('#serials_section').hide();
        $('#items_section').show();
    });

    $('#back_serials_btn').click(() => {
        $('#qr_section').hide();
        $('#serials_section').show();
    });

    // Generate QR
    $('#generate_qr_btn').click(function() {
        selected_ids = [];
        $('.scheck:checked').each(function() {
            selected_ids.push($(this).val());
        });

        if (!selected_ids.length) {
            alert_toast('Select at least one serial', 'warning');
            return;
        }

        $.post(ajax_url, {
            action: 'generate_qr',
            serial_ids: JSON.stringify(selected_ids)
        }, function(r) {
            if (r.success) {
                let preview = $('#qr_preview').empty();
                r.results.forEach(res => {
                    if (res.status === 'success') {
                        preview.append(`<div class="col-md-3 text-center mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <small>${res.serial}</small><br>
                                    <img src="<?php echo base_url; ?>${res.filepath}" class="img-fluid" style="max-width:150px">
                                </div>
                            </div>
                        </div>`);
                    }
                });
                $('#serials_section').hide();
                $('#qr_section').show();
                alert_toast(r.success_count + ' QR codes generated', 'success');
            }
        }, 'json');
    });

    // Confirm Receipt
    $('#confirm_receipt_btn').click(function() {
        $.post(ajax_url, {
            action: 'confirm_receipt',
            po_id: current_po.id,
            serial_ids: JSON.stringify(selected_ids),
            remarks: $('#remarks').val()
        }, function(r) {
            if (r.status === 'success') {
                alert_toast(r.message, 'success');
                setTimeout(() => {
                    window.location.href = base_url + 'admin/?page=receiving';
                }, 1500);
            } else {
                alert_toast(r.message, 'error');
            }
        }, 'json');
    });
});
</script>
</body>
</html>
