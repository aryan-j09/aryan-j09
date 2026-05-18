<?php
/**
 * Chemical Inventory - Outgoing Utilization
 */

if (!isset($conn)) {
    die('Database connection not available');
}

$batch_exists = false;
$chk_batch = $conn->query("SHOW TABLES LIKE 'chemical_inventory_batches'");
if ($chk_batch && $chk_batch->num_rows > 0) {
    $batch_exists = true;
}

$logs_exists = false;
$chk_logs = $conn->query("SHOW TABLES LIKE 'chemical_stock_logs'");
if ($chk_logs && $chk_logs->num_rows > 0) {
    $logs_exists = true;
}

$batch_has_unit = false;
if ($batch_exists) {
    $col_q = $conn->query("SHOW COLUMNS FROM chemical_inventory_batches LIKE 'unit'");
    if ($col_q && $col_q->num_rows > 0) {
        $batch_has_unit = true;
    }
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

// no debug counter

$available_chemicals = [];
if ($batch_exists) {
    if ($batch_has_unit) {
        $q = $conn->query("SELECT c.id AS chemical_id, c.name, c.brand,
            SUM(b.available_qty) AS available_qty,
            COALESCE(MAX(NULLIF(TRIM(b.unit), '')), '') AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.available_qty > 0
            GROUP BY c.id, c.name, c.brand
            ORDER BY c.name ASC
            LIMIT 300");
    } else {
        $q = $conn->query("SELECT c.id AS chemical_id, c.name, c.brand,
            SUM(b.available_qty) AS available_qty,
            COALESCE(c.unit, '') AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.available_qty > 0
            GROUP BY c.id, c.name, c.brand, c.unit
            ORDER BY c.name ASC
            LIMIT 300");
    }

    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $available_chemicals[] = $r;
        }
    }
}

$recent_outgoing = [];
if ($logs_exists) {
    $log_has_project_name = false;
    $log_project_col = $conn->query("SHOW COLUMNS FROM chemical_stock_logs LIKE 'project_name'");
    if ($log_project_col && $log_project_col->num_rows > 0) {
        $log_has_project_name = true;
    }

    $out_unit_select = $batch_has_unit ? "b.unit AS unit" : "c.unit AS unit";
    $out_project_select = $log_has_project_name ? "l.project_name AS project_name" : "'' AS project_name";
    $batch_has_short_code = false;
    $short_code_col = $conn->query("SHOW COLUMNS FROM chemical_inventory_batches LIKE 'short_code'");
    if ($short_code_col && $short_code_col->num_rows > 0) {
        $batch_has_short_code = true;
    }

    $out_short_code_select = $batch_has_short_code ? "COALESCE(b.short_code, '') AS short_code" : "'' AS short_code";

    $r_q = $conn->query("SELECT l.id, l.batch_id, l.quantity, l.reference_no,
        l.created_at, l.remarks, c.name, c.brand, {$out_project_select}, {$out_unit_select}, {$out_short_code_select}
        FROM chemical_stock_logs l
        INNER JOIN chemical_master_list c ON c.id = l.chemical_id
        LEFT JOIN chemical_inventory_batches b ON b.id = l.batch_id
        WHERE l.movement_type = 'OUT'
        ORDER BY l.id DESC
        LIMIT 150");
    if ($r_q) {
        while ($r = $r_q->fetch_assoc()) {
            $recent_outgoing[] = $r;
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Chemical Utilization</h3>
            <div style="margin-left:auto; display:flex; gap:8px;">
                <button type="button" class="btn btn-sm btn-danger" id="open-outgoing-modal" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                    <i class="fas fa-plus"></i> Log Utilization
                </button>
                <a href="<?php echo base_url ?>admin/?page=chemical_inventory" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Inventory Home
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if(!$batch_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_inventory_batches</code>. Please run schema SQL first.
                </div>
            <?php endif; ?>
            <?php if(!$logs_exists): ?>
                <div class="alert alert-warning">
                    <strong>Missing table:</strong> <code>chemical_stock_logs</code>. Please create this table first.
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead style="background-color: rgb(0, 31, 63); color: white;">
                        <tr>
                            <th width="45">#</th>
                            <th>Chemical</th>
                            <th>Project</th>
                            <th width="100" class="text-center">Qty Used</th>
                            <th width="140">Reference</th>
                            <th width="130">Used At</th>
                            <th>Remarks</th>
                            <th width="140" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($recent_outgoing) === 0): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No utilization records found</td>
                            </tr>
                        <?php else: ?>
                            <?php $i = 1; foreach($recent_outgoing as $r): ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($r['name'] . (!empty($r['brand']) ? ' - ' . $r['brand'] : '')); ?></td>
                                    <td><?php echo htmlspecialchars($r['project_name'] ?? ''); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($r['quantity'] . ' ' . ($r['unit'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($r['reference_no'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(!empty($r['created_at']) ? date('d-m-Y H:i', strtotime($r['created_at'])) : ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['remarks'] ?? ''); ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-primary btn-sm print-barcode" data-short-code="<?php echo htmlspecialchars($r['short_code'] ?? ''); ?>" data-batch-id="<?php echo (int)($r['batch_id'] ?? 0); ?>" data-chemical-name="<?php echo htmlspecialchars($r['name']); ?>" title="Print Barcode">
                                            <i class="fas fa-barcode"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-outgoing" data-id="<?php echo (int)$r['id']; ?>" title="Delete Outgoing">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                    <div class="col-md-12">
                        <label>Chemical <span class="text-danger">*</span></label>
                        <select id="out_chemical_id" class="form-control" style="width:100%;" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                            <option value="">Select chemical</option>
                            <?php foreach($available_chemicals as $c): ?>
                                <option
                                    value="<?php echo (int)$c['chemical_id']; ?>"
                                    data-available="<?php echo htmlspecialchars($c['available_qty']); ?>"
                                    data-unit="<?php echo htmlspecialchars($c['unit'] ?? ''); ?>"
                                >
                                    <?php echo htmlspecialchars(
                                        $c['name'] .
                                        (!empty($c['brand']) ? ' - ' . $c['brand'] : '') .
                                        ' | Available: ' . $c['available_qty'] . ' ' . ($c['unit'] ?? '')
                                    ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <label>Project <span class="text-danger">*</span></label>
                        <!-- project helper removed -->
                        <select id="out_project_id" class="form-control" style="width:100%;" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
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

<script>
function delete_outgoing(id){
    $.ajax({
        url: '<?php echo base_url ?>classes/Master.php?f=delete_chemical_outgoing',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(resp){
            if (resp.status === 'success') {
                alert_toast('Outgoing record deleted', 'success');
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

function openBarcodeWindow(shortCode, chemicalName, batchId) {
    var params = new URLSearchParams({
        short_code: shortCode,
        chemical: chemicalName,
        barcode: 'BATCH-' + batchId,
        count: 1
    });
    window.open(
        '<?php echo base_url ?>admin/chemical_inventory/print_qr.php?' + params.toString(),
        'QRPrint',
        'height=600,width=800,left=50,top=50'
    );
}

$(function(){
    $('#open-outgoing-modal').on('click', function(){
        $('#outgoing-modal').modal('show');
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

    $(document).on('click', '.print-barcode', function(e){
        e.preventDefault();
        e.stopPropagation();

        var btn = $(this);
        var shortCode = btn.data('short-code');
        var batchId = btn.data('batch-id');
        var chemicalName = btn.data('chemical-name');

        if (!batchId) {
            alert_toast('No linked batch found for this utilization record', 'warning');
            return;
        }

        if (!shortCode) {
            btn.prop('disabled', true);
            $.ajax({
                url: '<?php echo base_url ?>classes/Master.php?f=generate_batch_short_code',
                type: 'POST',
                data: { batch_id: batchId },
                dataType: 'json',
                success: function(resp){
                    btn.prop('disabled', false);
                    if (resp.status === 'success' && resp.short_code) {
                        shortCode = resp.short_code;
                        btn.data('short-code', shortCode);
                        openBarcodeWindow(shortCode, chemicalName, batchId);
                    } else {
                        alert_toast(resp.msg || 'Failed to generate barcode code', 'error');
                    }
                },
                error: function(){
                    btn.prop('disabled', false);
                    alert_toast('Error generating barcode', 'error');
                }
            });
            return;
        }

        openBarcodeWindow(shortCode, chemicalName, batchId);
    });

    $('#save_outgoing').on('click', function(){
        var projectId = $('#out_project_id').val();
        var newProjectName = '';
        if (projectId && projectId.indexOf('__new__') === 0) {
            newProjectName = $('#out_project_id').find('option:selected').text().replace(/\s*\(new\)\s*$/, '').trim();
        }

        var payload = {
            chemical_id: $('#out_chemical_id').val(),
            project_id: projectId,
            quantity: $('#out_quantity').val(),
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
                    window.location.href = '<?php echo base_url ?>admin/?page=chemical_inventory/outgoing';
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

    var successMsg = sessionStorage.getItem('success_message');
    if (successMsg) {
        alert_toast(successMsg, 'success');
        sessionStorage.removeItem('success_message');
    }

    $(document).on('click', '.delete-outgoing', function(){
        var id = $(this).data('id');
        _conf('Delete this utilization record? This will restore quantity back to stock.', 'delete_outgoing', [id]);
    });
});
</script>
