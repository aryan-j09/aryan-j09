<?php
/**
 * Chemicals Incoming (Batch-wise inventory with expiry)
 */

if (!isset($conn)) {
    die('Database connection not available');
}

$current_page = isset($_GET['page']) ? trim((string)$_GET['page']) : '';
$inventory_base_page = (strpos($current_page, 'chemical_inventory') === 0) ? 'chemical_inventory' : 'chemicals';

$master_exists = false;
$batch_exists = false;
$chk_master = $conn->query("SHOW TABLES LIKE 'chemical_master_list'");
if ($chk_master && $chk_master->num_rows > 0) {
    $master_exists = true;
}
$chk_batch = $conn->query("SHOW TABLES LIKE 'chemical_inventory_batches'");
if ($chk_batch && $chk_batch->num_rows > 0) {
    $batch_exists = true;
}

$chemicals = [];
if ($master_exists) {
    $chem_q = $conn->query("SELECT id, name, brand, unit FROM chemical_master_list ORDER BY name ASC");
    if ($chem_q) {
        while ($c = $chem_q->fetch_assoc()) {
            $chemicals[] = $c;
        }
    }
}

$suppliers = [];
$sup_q = $conn->query("SHOW TABLES LIKE 'supplier_list'");
if ($sup_q && $sup_q->num_rows > 0) {
    $supplier_q = $conn->query("SELECT id, name FROM supplier_list ORDER BY name ASC");
    if ($supplier_q) {
        while ($s = $supplier_q->fetch_assoc()) {
            $suppliers[] = $s;
        }
    }
}

$recent_rows = [];
if ($master_exists && $batch_exists) {
    $recent_q = $conn->query("SELECT b.id, b.batch_no, b.supplier, b.storage_location, b.received_qty, b.available_qty,
        b.unit_cost, b.expiry_date, b.received_date, b.remarks, c.name, c.brand, c.unit
        FROM chemical_inventory_batches b
        INNER JOIN chemical_master_list c ON c.id = b.chemical_id
        ORDER BY b.id DESC
        LIMIT 100");
    if ($recent_q) {
        while ($r = $recent_q->fetch_assoc()) {
            $recent_rows[] = $r;
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Chemical Incoming</h3>
            <div style="margin-left:auto; display:flex; gap:8px;">
                <a href="<?php echo base_url ?>admin/?page=chemical_inventory" class="btn btn-sm btn-info">
                    <i class="fas fa-flask"></i> Inventory Home
                </a>
                <a href="<?php echo base_url ?>admin/?page=chemicals" class="btn btn-sm btn-secondary">
                    <i class="fas fa-list"></i> Master List
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if(!$master_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_master_list</code>. Please create it first.
                </div>
            <?php endif; ?>
            <?php if(!$batch_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_inventory_batches</code>. Run incoming schema SQL first.
                </div>
            <?php endif; ?>

            <div class="form-section" style="background:#f8f9fa;padding:15px;border:1px solid #dee2e6;border-radius:6px;margin-bottom:18px;">
                <h6 style="margin-bottom:12px;">Add Incoming Batch</h6>
                <div class="row">
                    <div class="col-md-4">
                        <label>Chemical <span class="text-danger">*</span></label>
                        <select id="in_chemical_id" class="form-control select2" style="width:100%;">
                            <option value="">Select chemical</option>
                            <?php foreach($chemicals as $chem): ?>
                                <option value="<?php echo (int)$chem['id']; ?>">
                                    <?php echo htmlspecialchars($chem['name'] . (!empty($chem['brand']) ? ' - ' . $chem['brand'] : '') . ' (' . $chem['unit'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Batch No</label>
                        <input type="text" id="in_batch_no" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-3">
                        <label>Supplier</label>
                        <select id="in_supplier" class="form-control select2" style="width:100%;">
                            <option value="">Select supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier['name']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Storage Location <span class="text-danger">*</span></label>
                        <input type="text" id="in_storage_location" class="form-control" placeholder="Rack/Shelf/Bin">
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-2">
                        <label>Received Qty <span class="text-danger">*</span></label>
                        <input type="number" id="in_received_qty" class="form-control" min="0.0001" step="0.0001" placeholder="0.0000">
                    </div>
                    <div class="col-md-2">
                        <label>Unit Cost</label>
                        <input type="number" id="in_unit_cost" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label>Received Date <span class="text-danger">*</span></label>
                        <input type="date" id="in_received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Expiry Date</label>
                        <input type="date" id="in_expiry_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Remarks</label>
                        <input type="text" id="in_remarks" class="form-control" placeholder="Optional">
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-9"></div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" id="save_incoming" class="btn btn-primary btn-block" <?php echo (!$master_exists || !$batch_exists) ? 'disabled' : ''; ?>><i class="fas fa-save"></i> Save Incoming</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead style="background-color: rgb(0, 31, 63); color: white;">
                        <tr>
                            <th width="45">#</th>
                            <th>Chemical</th>
                            <th width="100">Batch</th>
                            <th width="160">Supplier</th>
                            <th width="150">Storage</th>
                            <th width="85" class="text-center">Received</th>
                            <th width="85" class="text-center">Available</th>
                            <th width="95" class="text-center">Received On</th>
                            <th width="95" class="text-center">Expiry</th>
                            <th width="105" class="text-center">Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($recent_rows) === 0): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted">No incoming batches found</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($recent_rows as $r): ?>
                                <?php
                                    $status = 'No Expiry';
                                    $badge = 'badge-secondary';
                                    if (!empty($r['expiry_date'])) {
                                        $today = strtotime(date('Y-m-d'));
                                        $exp = strtotime($r['expiry_date']);
                                        $days_left = (int)floor(($exp - $today) / 86400);
                                        if ($days_left < 0) {
                                            $status = 'Expired';
                                            $badge = 'badge-danger';
                                        } elseif ($days_left <= 30) {
                                            $status = 'Expiring Soon';
                                            $badge = 'badge-warning';
                                        } else {
                                            $status = 'Safe';
                                            $badge = 'badge-success';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($r['name'] . (!empty($r['brand']) ? ' - ' . $r['brand'] : '') . ' (' . $r['unit'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($r['batch_no'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['supplier'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['storage_location'] ?? ''); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($r['received_qty']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($r['available_qty']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($r['received_date']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($r['expiry_date'] ?? ''); ?></td>
                                    <td class="text-center"><span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
                                    <td><?php echo htmlspecialchars($r['remarks'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    var successMsg = sessionStorage.getItem('success_message');
    if (successMsg) {
        alert_toast(successMsg, 'success');
        sessionStorage.removeItem('success_message');
    }

    $('#in_chemical_id, #in_supplier').select2({
        width: 'resolve'
    });

    $('#save_incoming').on('click', function(){
        var payload = {
            chemical_id: $('#in_chemical_id').val(),
            batch_no: $('#in_batch_no').val().trim(),
            supplier: ($('#in_supplier').val() || '').trim(),
            storage_location: $('#in_storage_location').val().trim(),
            received_qty: $('#in_received_qty').val(),
            unit_cost: $('#in_unit_cost').val(),
            received_date: $('#in_received_date').val(),
            expiry_date: $('#in_expiry_date').val(),
            remarks: $('#in_remarks').val().trim()
        };

        if (!payload.chemical_id) {
            alert_toast('Chemical is required', 'warning');
            return;
        }
        if (!payload.storage_location) {
            alert_toast('Storage location is required', 'warning');
            return;
        }
        if (!payload.received_qty || Number(payload.received_qty) <= 0) {
            alert_toast('Received quantity must be greater than 0', 'warning');
            return;
        }
        if (!payload.received_date) {
            alert_toast('Received date is required', 'warning');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=save_chemical_incoming',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(resp){
                if (resp.status === 'success') {
                    sessionStorage.setItem('success_message', 'Incoming saved successfully.');
                    window.location.href = '<?php echo base_url ?>admin/?page=<?php echo $inventory_base_page; ?>/incoming';
                } else {
                    alert_toast(resp.msg || 'Save failed', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Incoming');
                }
            },
            error: function(){
                alert_toast('Request failed', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Incoming');
            }
        });
    });
});
</script>
