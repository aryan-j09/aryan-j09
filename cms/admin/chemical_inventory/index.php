<?php
/**
 * Chemical Inventory - Chemicals List
 */

if (!isset($conn)) {
    die('Database connection not available');
}

if (!function_exists('ci_format_qty')) {
    function ci_format_qty($value) {
        $n = (float)$value;
        if (abs($n - round($n)) < 0.000001) {
            return number_format($n, 0, '.', '');
        }
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}

$open_incoming_modal = isset($_GET['open_modal']) && $_GET['open_modal'] === 'incoming';
$open_outgoing_modal = isset($_GET['open_modal']) && $_GET['open_modal'] === 'outgoing';

$batch_exists = false;
$logs_exists = false;
$batch_has_unit = false;
$chemicals = [];
$suppliers = [];
$available_chemicals = [];
$chemical_stats = [];
$rows = [];

$chk_batch = $conn->query("SHOW TABLES LIKE 'chemical_inventory_batches'");
if ($chk_batch && $chk_batch->num_rows > 0) {
    $batch_exists = true;
    $unit_chk = $conn->query("SHOW COLUMNS FROM chemical_inventory_batches LIKE 'unit'");
    if ($unit_chk && $unit_chk->num_rows > 0) {
        $batch_has_unit = true;
    }
}

$chk_logs = $conn->query("SHOW TABLES LIKE 'chemical_stock_logs'");
if ($chk_logs && $chk_logs->num_rows > 0) {
    $logs_exists = true;
}

$projects_exists = false;
$project_options = [];
$chk_projects = $conn->query("SHOW TABLES LIKE 'projects'");
if ($chk_projects && $chk_projects->num_rows > 0) {
    $projects_exists = true;
    $projects_q = $conn->query("SELECT id, name FROM projects ORDER BY name ASC");
    if ($projects_q) {
        while ($p = $projects_q->fetch_assoc()) {
            $project_options[] = $p;
        }
    }
}

$chem_q = $conn->query("SELECT id, name, brand, remarks FROM chemical_master_list ORDER BY name ASC, id ASC");
if ($chem_q) {
    while ($c = $chem_q->fetch_assoc()) {
        $chemicals[] = $c;
    }
}

$sup_q = $conn->query("SHOW TABLES LIKE 'supplier_list'");
if ($sup_q && $sup_q->num_rows > 0) {
    $supplier_q = $conn->query("SELECT id, name FROM supplier_list ORDER BY name ASC");
    if ($supplier_q) {
        while ($s = $supplier_q->fetch_assoc()) {
            $suppliers[] = $s;
        }
    }
}

if ($batch_exists) {
    $stats_sql = "SELECT c.id AS chemical_id,
        COUNT(b.id) AS batch_count,
        COALESCE(SUM(b.received_qty),0) AS total_received,
        COALESCE(SUM(b.available_qty),0) AS total_available,
        SUM(CASE WHEN b.expiry_date IS NOT NULL AND b.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_batches,
        SUM(CASE WHEN b.expiry_date IS NOT NULL AND b.expiry_date >= CURDATE() AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS near_expiry_batches,
        MAX(b.received_date) AS last_received_date
        FROM chemical_master_list c
        LEFT JOIN chemical_inventory_batches b ON b.chemical_id = c.id
        GROUP BY c.id";
    $stats_q = $conn->query($stats_sql);
    if ($stats_q) {
        while ($row = $stats_q->fetch_assoc()) {
            $chemical_stats[(int)$row['chemical_id']] = $row;
        }
    }

    $chem_avail_sql = $batch_has_unit
        ? "SELECT c.id AS chemical_id, c.name, c.brand,
            COALESCE(SUM(CASE
                WHEN LOWER(TRIM(b.unit)) = 'kg' THEN b.available_qty
                WHEN LOWER(TRIM(b.unit)) = 'g' THEN b.available_qty / 1000
                WHEN LOWER(TRIM(b.unit)) = 'l' THEN b.available_qty
                WHEN LOWER(TRIM(b.unit)) = 'ml' THEN b.available_qty / 1000
                ELSE b.available_qty
            END),0) AS available_qty,
            CASE
                WHEN SUM(CASE WHEN LOWER(TRIM(b.unit)) IN ('kg','g') THEN 1 ELSE 0 END) > 0 THEN 'kg'
                WHEN SUM(CASE WHEN LOWER(TRIM(b.unit)) IN ('l','ml') THEN 1 ELSE 0 END) > 0 THEN 'L'
                WHEN SUM(CASE WHEN LOWER(TRIM(b.unit)) = 'pcs' THEN 1 ELSE 0 END) > 0 THEN 'pcs'
                ELSE COALESCE(MAX(NULLIF(TRIM(b.unit), '')), '')
            END AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.available_qty > 0
            GROUP BY c.id, c.name, c.brand
            ORDER BY c.name ASC"
        : "SELECT c.id AS chemical_id, c.name, c.brand,
            SUM(b.available_qty) AS available_qty,
            COALESCE(c.unit, '') AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.available_qty > 0
            GROUP BY c.id, c.name, c.brand, c.unit
            ORDER BY c.name ASC";

    $chem_avail_q = $conn->query($chem_avail_sql);
    if ($chem_avail_q) {
        while ($row = $chem_avail_q->fetch_assoc()) {
            $available_chemicals[] = $row;
        }
    }
}

foreach ($chemicals as $chem) {
    $stats = $chemical_stats[(int)$chem['id']] ?? [];
    $rows[] = [
        'id' => $chem['id'],
        'name' => $chem['name'],
        'brand' => $chem['brand'] ?? '',
        'remarks' => $chem['remarks'] ?? '',
        'batch_count' => (int)($stats['batch_count'] ?? 0),
        'total_received' => (float)($stats['total_received'] ?? 0),
        'total_available' => (float)($stats['total_available'] ?? 0),
        'expired_batches' => (int)($stats['expired_batches'] ?? 0),
        'near_expiry_batches' => (int)($stats['near_expiry_batches'] ?? 0),
        'last_received_date' => $stats['last_received_date'] ?? ''
    ];
}
?>

<div class="card card-outline card-primary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Chemical Inventory</h3>
        <div class="card-tools" style="margin-left:auto; display:flex; gap:8px;">
            <button type="button" id="open-incoming-modal" class="btn btn-sm btn-success" <?php echo !$batch_exists ? 'disabled' : ''; ?>>
                <i class="fas fa-arrow-down"></i> Receive
            </button>
            <button type="button" id="open-outgoing-modal" class="btn btn-sm btn-danger" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                <i class="fas fa-arrow-up"></i> Utilize
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <?php if(!$batch_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_inventory_batches</code>. Please run the schema SQL first.
                </div>
            <?php endif; ?>
            <?php if(!$logs_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_stock_logs</code>. Outgoing records will not be available until this table exists.
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-4 ml-auto">
                    <label class="mb-1">Search</label>
                    <input type="text" id="chemical-search" class="form-control" placeholder="Search chemicals...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="chemical-inventory-table">
                    <colgroup>
                        <col width="5%">
                        <col width="26%">
                        <col width="16%">
                        <col width="13%">
                        <col width="14%">
                        <col width="26%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Sr.</th>
                            <th>Chemical</th>
                            <th>Remarks</th>
                            <th>Batches</th>
                            <th>Available</th>
                            <th>Last Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rows) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No chemicals found</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($rows as $r):
                                $expired_batches = (int)$r['expired_batches'];
                                $near_expiry_batches = (int)$r['near_expiry_batches'];
                                $badge_class = 'badge-secondary';
                                $status_text = 'No stock';
                                if ($r['batch_count'] > 0) {
                                    if ($expired_batches > 0) {
                                        $badge_class = 'badge-danger';
                                        $status_text = 'Expired stock present';
                                    } elseif ($near_expiry_batches > 0) {
                                        $badge_class = 'badge-warning';
                                        $status_text = 'Expiring soon';
                                    } else {
                                        $badge_class = 'badge-success';
                                        $status_text = 'Stock healthy';
                                    }
                                }
                            ?>
                                <tr class="chemical-row" data-id="<?php echo (int)$r['id']; ?>" style="cursor:pointer;">
                                    <td class="text-center"><?php echo $i++; ?>.</td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($r['name']); ?></strong>
                                        <?php if(!empty($r['brand'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($r['brand']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(mb_strimwidth((string)$r['remarks'], 0, 60, '...')); ?></td>
                                    <td class="text-center"><?php echo (int)$r['batch_count']; ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($r['total_available'])); ?></td>
                                    <td>
                                        <?php echo !empty($r['last_received_date']) ? date('d-M-Y', strtotime($r['last_received_date'])) : '-'; ?>
                                        <br><span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="incoming-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Incoming Batch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label>Chemical <span class="text-danger">*</span></label>
                        <div class="input-group mb-1">
                            <input type="text" id="in_scan_barcode" class="form-control" placeholder="Scan batch barcode to select chemical" aria-label="Scan barcode">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="in_scan_clear">Clear</button>
                            </div>
                        </div>
                        <select id="in_chemical_id" class="form-control" style="width:100%;">
                            <option value="">Select chemical</option>
                            <?php foreach($chemicals as $chem): ?>
                                <option value="<?php echo (int)$chem['id']; ?>">
                                    <?php echo htmlspecialchars($chem['name'] . (!empty($chem['brand']) ? ' - ' . $chem['brand'] : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Supplier</label>
                        <select id="in_supplier" class="form-control" style="width:100%;">
                            <option value="">Select supplier</option>
                            <?php foreach($suppliers as $supplier): ?>
                                <option value="<?php echo htmlspecialchars($supplier['name']); ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <label>Batch No</label>
                        <input type="text" id="in_batch_no" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label>Storage Location <span class="text-danger">*</span></label>
                        <input type="text" id="in_storage_location" class="form-control" placeholder="Rack/Shelf/Bin">
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-3">
                        <label>Received Qty <span class="text-danger">*</span></label>
                        <input type="number" id="in_received_qty" class="form-control" min="0.0001" step="0.0001" placeholder="0.0000">
                    </div>
                    <div class="col-md-3">
                        <label>Unit <span class="text-danger">*</span></label>
                        <select id="in_unit" class="form-control" style="width:100%;">
                            <option value="">Select unit</option>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="L">L</option>
                            <option value="ml">ml</option>
                            <option value="pcs">pcs</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Unit Cost</label>
                        <input type="number" id="in_unit_cost" class="form-control" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label>Total Cost</label>
                        <input type="text" id="in_total_cost" class="form-control" value="0.00" readonly>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6">
                        <label>Received Date <span class="text-danger">*</span></label>
                        <input type="date" id="in_received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Expiry Date</label>
                        <input type="date" id="in_expiry_date" class="form-control">
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Remarks</label>
                        <input type="text" id="in_remarks" class="form-control" placeholder="Optional">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="save_incoming" class="btn btn-primary" <?php echo !$batch_exists ? 'disabled' : ''; ?>><i class="fas fa-save"></i> Save Incoming</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="new-chemical-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Chemical</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" id="new_chem_name" class="form-control" placeholder="Chemical name">
                </div>
                <div class="form-group mb-2">
                    <label>Make/Brand</label>
                    <input type="text" id="new_chem_brand" class="form-control" placeholder="Brand">
                </div>
                <div class="form-group mb-0">
                    <label>Remarks</label>
                    <input type="text" id="new_chem_remarks" class="form-control" placeholder="Optional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="save_new_chemical_inline" class="btn btn-primary"><i class="fas fa-save"></i> Save Chemical</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="outgoing-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Utilization</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <label>Chemical <span class="text-danger">*</span></label>
                        <div class="input-group mb-1">
                            <input type="text" id="out_scan_barcode" class="form-control" placeholder="Scan batch barcode to select chemical" aria-label="Scan barcode">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="out_scan_clear">Clear</button>
                            </div>
                        </div>
                        <input type="hidden" id="out_scanned_batch_id" value="">
                        <select id="out_chemical_id" class="form-control" style="width:100%;" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                            <option value="">Select chemical</option>
                            <?php foreach($available_chemicals as $c): ?>
                                <option value="<?php echo (int)$c['chemical_id']; ?>" data-available="<?php echo htmlspecialchars(ci_format_qty($c['available_qty'])); ?>" data-unit="<?php echo htmlspecialchars($c['unit'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($c['name'] . (!empty($c['brand']) ? ' - ' . $c['brand'] : '') . ' | Available: ' . ci_format_qty($c['available_qty']) . ' ' . ($c['unit'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Project <span class="text-danger">*</span></label>
                        <select id="out_project_id" class="form-control" style="width:100%;" <?php echo (!$batch_exists || !$logs_exists || !$projects_exists) ? 'disabled' : ''; ?>>
                            <option value="">Select or type project name</option>
                            <?php foreach($project_options as $proj): ?>
                                <option value="<?php echo (int)$proj['id']; ?>"><?php echo htmlspecialchars($proj['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-4">
                        <label>Available</label>
                        <input type="text" id="out_available_info" class="form-control" value="-" readonly>
                    </div>
                    <div class="col-md-4">
                        <label>Quantity Used <span class="text-danger">*</span></label>
                        <input type="number" id="out_quantity" class="form-control" min="0.0001" step="0.0001" placeholder="0.0000" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                    </div>
                    <div class="col-md-4">
                        <label>Used At</label>
                        <input type="datetime-local" id="out_utilized_at" class="form-control" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Remarks</label>
                        <input type="text" id="out_remarks" class="form-control" placeholder="Optional notes" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" id="save_outgoing" class="btn btn-danger" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>><i class="fas fa-save"></i> Save Usage</button>
            </div>
        </div>
    </div>
</div>

<style>
    .chemical-row:hover {
        background-color: rgba(0, 123, 255, 0.14) !important;
    }

    #chemical-inventory-table tbody tr.chemical-row td {
        transition: background-color 0.15s ease-in-out;
    }

    .select2-results__option .select2-add-new-option {
        color: #007bff;
        font-weight: 600;
    }

    .select2-results__option--highlighted .select2-add-new-option {
        color: #ffffff !important;
    }
</style>

<script>
function delete_incoming(id){
    $.ajax({
        url: '<?php echo base_url ?>classes/Master.php?f=delete_chemical_incoming',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(resp){
            if (resp.status === 'success') {
                sessionStorage.setItem('success_message', resp.msg || 'Incoming record deleted');
                setTimeout(function(){ location.reload(); }, 300);
            } else {
                alert_toast(resp.msg || 'Delete failed', 'error');
            }
        },
        error: function(){
            alert_toast('Delete request failed', 'error');
        }
    });
}

function delete_outgoing(id){
    $.ajax({
        url: '<?php echo base_url ?>classes/Master.php?f=delete_chemical_outgoing',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(resp){
            if (resp.status === 'success') {
                sessionStorage.setItem('success_message', resp.msg || 'Outgoing record deleted');
                setTimeout(function(){ location.reload(); }, 300);
            } else {
                alert_toast(resp.msg || 'Delete failed', 'error');
            }
        },
        error: function(){
            alert_toast('Delete request failed', 'error');
        }
    });
}

$(function(){
    var addNewChemicalTerm = '';

    var successMsg = sessionStorage.getItem('success_message');
    if (successMsg) {
        alert_toast(successMsg, 'success');
        sessionStorage.removeItem('success_message');
    }

    $('#chemical-inventory-table').DataTable({
        dom: 'rtip'
    });

    $('#chemical-search').on('keyup', function(){
        $('#chemical-inventory-table').DataTable().search($(this).val()).draw();
    });

    $('#open-incoming-modal').on('click', function(){
        $('#incoming-modal').modal('show');
    });

    $('#open-outgoing-modal').on('click', function(){
        $('#outgoing-modal').modal('show');
    });

    function updateIncomingTotalCost(){
        var qty = parseFloat($('#in_received_qty').val());
        var cost = parseFloat($('#in_unit_cost').val());
        if (isNaN(qty) || isNaN(cost)) {
            $('#in_total_cost').val('0.00');
            return;
        }
        $('#in_total_cost').val((qty * cost).toFixed(2));
    }

    $('#in_received_qty, #in_unit_cost').on('input change', updateIncomingTotalCost);

    $('#incoming-modal').on('shown.bs.modal', function(){
        $('#in_supplier, #in_unit').select2({
            dropdownParent: $('#incoming-modal'),
            width: '100%'
        });

        if (!$('#in_chemical_id').hasClass('select2-hidden-accessible')) {
            $('#in_chemical_id').select2({
                dropdownParent: $('#incoming-modal'),
                width: '100%',
                tags: true,
                createTag: function(params){
                    var term = $.trim(params.term || '');
                    if (term === '') return null;
                    addNewChemicalTerm = term;
                    return {
                        id: '__add_new__',
                        text: '+ Add New Chemical: "' + term + '"',
                        isAddNew: true,
                        term: term
                    };
                },
                insertTag: function(data, tag){
                    data.push(tag);
                },
                templateResult: function(data){
                    if (data.isAddNew) {
                        return $('<span class="select2-add-new-option"></span>').text(data.text);
                    }
                    return data.text;
                },
                templateSelection: function(data){
                    if (data && data.id === '__add_new__') {
                        return '';
                    }
                    return data.text || '';
                },
                escapeMarkup: function(markup){ return markup; }
            });
        }

        $('#in_chemical_id').off('select2:selecting.chemadd').on('select2:selecting.chemadd', function(e){
            var selected = e.params && e.params.args ? e.params.args.data : null;
            if (selected && selected.id === '__add_new__') {
                e.preventDefault();
                $('#in_chemical_id').val(null).trigger('change.select2');
                $('#in_chemical_id').select2('close');
                $('#new_chem_name').val(selected.term || addNewChemicalTerm);
                $('#new-chemical-modal').modal('show');
            }
        });

        updateIncomingTotalCost();
    });

    $('#outgoing-modal').on('shown.bs.modal', function(){
        if (!$('#out_chemical_id').hasClass('select2-hidden-accessible')) {
            $('#out_chemical_id').select2({
                dropdownParent: $('#outgoing-modal'),
                width: '100%'
            });
        }

        if (!$('#out_project_id').hasClass('select2-hidden-accessible')) {
            $('#out_project_id').select2({
                dropdownParent: $('#outgoing-modal'),
                width: '100%',
                tags: true,
                tokenSeparators: [','],
                createTag: function(params) {
                    var term = $.trim(params.term);
                    if (term === '') return null;
                    return {
                        id: '__new__' + Date.now(),
                        text: term,
                        newProject: true
                    };
                },
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    if (data.newProject) {
                        return '<strong>' + String(data.text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;').replace(/'/g, '&#039;') + '</strong> <small style="color:#999;">(new)</small>';
                    }
                    return data.text;
                },
                templateSelection: function(data){
                    return data && data.text ? data.text : '';
                },
                escapeMarkup: function(markup){ return markup; }
            });
        }

        if (!$('#out_utilized_at').val()) {
            var now = new Date();
            var local = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0,16);
            $('#out_utilized_at').val(local);
        }
    });

    $('#out_chemical_id').on('change', function(){
        var opt = $(this).find('option:selected');
        var available = opt.data('available');
        var unit = opt.data('unit') || '';
        if (available === undefined || available === '') {
            $('#out_available_info').val('-');
        } else {
            $('#out_available_info').val(available + ' ' + unit);
        }
    });

    $('#save_new_chemical_inline').on('click', function(){
        var payload = {
            name: $('#new_chem_name').val().trim(),
            brand: $('#new_chem_brand').val().trim(),
            remarks: $('#new_chem_remarks').val().trim()
        };

        if (!payload.name) {
            alert_toast('Name is required', 'warning');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=save_chemical_master',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(resp){
                if (resp.status === 'success') {
                    var id = parseInt(resp.chemical_id || 0, 10);
                    var name = (resp.chemical_name || payload.name).trim();
                    var brand = (resp.chemical_brand || payload.brand).trim();
                    var label = brand ? (name + ' - ' + brand) : name;

                    if (id > 0) {
                        if ($('#in_chemical_id option[value="' + id + '"]').length === 0) {
                            $('#in_chemical_id').append(new Option(label, id, false, false));
                        }
                        $('#in_chemical_id').val(String(id)).trigger('change');
                    }

                    $('#new_chem_name').val('');
                    $('#new_chem_brand').val('');
                    $('#new_chem_remarks').val('');
                    $('#new-chemical-modal').modal('hide');
                    alert_toast('Chemical added. Continue incoming entry.', 'success');
                } else {
                    alert_toast(resp.msg || 'Save failed', 'error');
                }
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Chemical');
            },
            error: function(){
                alert_toast('Request failed', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Chemical');
            }
        });
    });

    function showChemicalQRPrintDialog(shortCode, chemicalName, barcodeCode) {
        var params = new URLSearchParams({
            short_code: shortCode || '',
            chemical: chemicalName,
            barcode: barcodeCode,
            count: 1
        });

        window.open(
            '<?php echo base_url ?>admin/chemical_inventory/print_qr.php?' + params.toString(),
            'QRPrint',
            'height=600,width=800,left=50,top=50'
        );
    }

    $('#save_incoming').on('click', function(){
        var payload = {
            chemical_id: $('#in_chemical_id').val(),
            batch_no: $('#in_batch_no').val().trim(),
            unit: ($('#in_unit').val() || '').trim(),
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
        if (!payload.unit) {
            alert_toast('Unit is required', 'warning');
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
                    var shortCode = resp.short_code || '';
                    var chemName = $('#in_chemical_id option:selected').text() || 'Chemical';
                    var barcodeCode = 'BATCH-' + resp.batch_id;
                    
                    if (shortCode) {
                        if (confirm('Print barcode label for this batch?')) {
                            showChemicalQRPrintDialog(shortCode, chemName, barcodeCode);
                        }
                    }
                    
                    sessionStorage.setItem('success_message', 'Incoming saved successfully.');
                    setTimeout(function(){
                        window.location.href = '<?php echo base_url ?>admin/?page=chemical_inventory';
                    }, 500);
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

    $('#save_outgoing').on('click', function(){
        var projectId = $('#out_project_id').val();
        var newProjectName = '';
        if (projectId && projectId.indexOf('__new__') === 0) {
            newProjectName = $('#out_project_id').find('option:selected').text().replace(/\s*\(new\)\s*$/, '').trim();
        }

        var qtyVal = parseFloat($('#out_quantity').val());
        var payload = {
            chemical_id: $('#out_chemical_id').val(),
            project_id: projectId,
            quantity: isNaN(qtyVal) ? '' : qtyVal,
            utilized_at: $('#out_utilized_at').val(),
            remarks: $('#out_remarks').val().trim()
        };

        if (newProjectName) {
            payload.new_project_name = newProjectName;
        }

        if (!payload.chemical_id) {
            alert_toast('Chemical is required', 'warning');
            return;
        }
        if (!payload.project_id) {
            alert_toast('Project is required', 'warning');
            return;
        }
        if (!payload.quantity || Number(payload.quantity) <= 0) {
            alert_toast('Quantity must be greater than 0', 'warning');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=save_chemical_outgoing',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function(resp){
                if (resp.status === 'success') {
                    sessionStorage.setItem('success_message', resp.msg || 'Usage saved.');
                    window.location.href = '<?php echo base_url ?>admin/?page=chemical_inventory';
                } else {
                    alert_toast(resp.msg || 'Save failed', 'error');
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Usage');
                }
            },
            error: function(){
                alert_toast('Request failed', 'error');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Usage');
            }
        });
    });

    <?php if($open_incoming_modal): ?>
    $('#incoming-modal').modal('show');
    <?php elseif($open_outgoing_modal): ?>
    $('#outgoing-modal').modal('show');
    <?php endif; ?>

    $(document).on('click', '.delete-incoming', function(e){
        e.preventDefault();
        e.stopPropagation();
        var id = parseInt($(this).data('id'), 10) || 0;
        if (!id) {
            alert_toast('Invalid incoming record ID', 'error');
            return;
        }
        if (!confirm('Delete this incoming record? This is allowed only when the batch is not consumed.')) {
            return;
        }
        delete_incoming(id);
    });

    $(document).on('click', '.delete-outgoing', function(e){
        e.preventDefault();
        e.stopPropagation();
        var id = parseInt($(this).data('id'), 10) || 0;
        if (!id) {
            alert_toast('Invalid outgoing record ID', 'error');
            return;
        }
        if (!confirm('Delete this outgoing record? This will restore quantity back to stock.')) {
            return;
        }
        delete_outgoing(id);
    });

    $(document).on('click', '.chemical-row', function(){
        var id = $(this).data('id');
        if (id) {
            window.location.href = '<?php echo base_url ?>admin/?page=chemical_inventory/view_chemical&id=' + id;
        }
    });

    function lookupBatchByShortCode(code, cb) {
        if (!code) {
            cb && cb({status:'error', msg:'Empty code'});
            return;
        }
        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=get_batch_by_short_code',
            type: 'POST',
            data: {short_code: code},
            dataType: 'json',
            success: function(resp){ cb && cb(resp); },
            error: function(){ cb && cb({status:'error', msg:'Request failed'}); }
        });
    }

    // Incoming scan handler
    $(document).on('keydown', '#in_scan_barcode', function(e){
        if (e.key === 'Enter') {
            var code = $(this).val().trim();
            if (!code) return;
            lookupBatchByShortCode(code, function(resp){
                if (resp.status === 'success') {
                    var data = resp.data || {};
                    if (data.chemical_id) {
                        if ($('#in_chemical_id option[value="' + data.chemical_id + '"]').length === 0) {
                            $('#in_chemical_id').append(new Option(data.chemical_name || 'Chemical', data.chemical_id, false, false));
                        }
                        $('#in_chemical_id').val(String(data.chemical_id)).trigger('change');
                    }
                    if (data.batch_no) $('#in_batch_no').val(data.batch_no);
                    if (data.unit) $('#in_unit').val(data.unit).trigger('change');
                    alert_toast('Batch found: ' + (data.chemical_name || ''), 'success');
                } else {
                    alert_toast(resp.msg || 'Batch not found', 'warning');
                }
            });
        }
    });
    $(document).on('click', '#in_scan_clear', function(){ $('#in_scan_barcode').val(''); });

    // Outgoing scan handler
    $(document).on('keydown', '#out_scan_barcode', function(e){
        if (e.key === 'Enter') {
            var code = $(this).val().trim();
            if (!code) return;
            lookupBatchByShortCode(code, function(resp){
                if (resp.status === 'success') {
                    var data = resp.data || {};
                    if (data.chemical_id) {
                        if ($('#out_chemical_id option[value="' + data.chemical_id + '"]').length === 0) {
                            // create a simple label
                            var label = (data.chemical_name || 'Chemical') + (data.unit ? ' | Available: ' + data.available_qty + ' ' + data.unit : '');
                            $('#out_chemical_id').append(new Option(label, data.chemical_id, false, false));
                        }
                        $('#out_chemical_id').val(String(data.chemical_id)).trigger('change');
                    }
                    $('#out_available_info').val((data.available_qty || '-') + ' ' + (data.unit || ''));
                    if (data.batch_id) $('#out_scanned_batch_id').val(data.batch_id);
                    alert_toast('Batch selected: ' + (data.batch_no || ''), 'success');
                    $('#out_quantity').focus();
                } else {
                    alert_toast(resp.msg || 'Batch not found', 'warning');
                }
            });
        }
    });
    $(document).on('click', '#out_scan_clear', function(){ $('#out_scan_barcode').val(''); $('#out_scanned_batch_id').val(''); });
});
</script>
