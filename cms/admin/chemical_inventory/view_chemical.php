<?php
/**
 * Chemical Inventory - Chemical Detail View
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

$chemical_id = intval($_GET['id'] ?? 0);
if ($chemical_id <= 0) {
    echo '<div class="alert alert-danger">Invalid chemical ID.</div>';
    return;
}

$chem_q = $conn->query("SELECT * FROM chemical_master_list WHERE id = {$chemical_id} LIMIT 1");
$chemical = ($chem_q && $chem_q->num_rows > 0) ? $chem_q->fetch_assoc() : null;
if (!$chemical) {
    echo '<div class="alert alert-danger">Chemical not found.</div>';
    return;
}

$batch_exists = false;
$logs_exists = false;
$batch_has_unit = false;

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

$incoming_rows = [];
$outgoing_rows = [];
$batch_count = 0;
$total_received = 0;
$total_available = 0;
$total_outgoing = 0;
$summary_unit = '';

if ($batch_exists) {
    $stats_sql = $batch_has_unit
        ? "SELECT COUNT(*) AS batch_count,
            COALESCE(SUM(CASE
                WHEN LOWER(TRIM(unit)) = 'kg' THEN received_qty
                WHEN LOWER(TRIM(unit)) = 'g' THEN received_qty / 1000
                WHEN LOWER(TRIM(unit)) = 'l' THEN received_qty
                WHEN LOWER(TRIM(unit)) = 'ml' THEN received_qty / 1000
                ELSE received_qty
            END),0) AS total_received,
            COALESCE(SUM(CASE
                WHEN LOWER(TRIM(unit)) = 'kg' THEN available_qty
                WHEN LOWER(TRIM(unit)) = 'g' THEN available_qty / 1000
                WHEN LOWER(TRIM(unit)) = 'l' THEN available_qty
                WHEN LOWER(TRIM(unit)) = 'ml' THEN available_qty / 1000
                ELSE available_qty
            END),0) AS total_available
            FROM chemical_inventory_batches
            WHERE chemical_id = {$chemical_id}"
        : "SELECT COUNT(*) AS batch_count,
            COALESCE(SUM(received_qty),0) AS total_received,
            COALESCE(SUM(available_qty),0) AS total_available
            FROM chemical_inventory_batches
            WHERE chemical_id = {$chemical_id}";

    $stats_q = $conn->query($stats_sql);
    if ($stats_q) {
        $stats = $stats_q->fetch_assoc();
        $batch_count = (int)($stats['batch_count'] ?? 0);
        $total_received = (float)($stats['total_received'] ?? 0);
        $total_available = (float)($stats['total_available'] ?? 0);
    }

    if ($batch_has_unit) {
        $unit_q = $conn->query("SELECT
            SUM(CASE WHEN LOWER(TRIM(unit)) IN ('kg', 'g') THEN 1 ELSE 0 END) AS mass_units,
            SUM(CASE WHEN LOWER(TRIM(unit)) IN ('l', 'ml') THEN 1 ELSE 0 END) AS volume_units,
            SUM(CASE WHEN LOWER(TRIM(unit)) = 'pcs' THEN 1 ELSE 0 END) AS pcs_units,
            COALESCE(MAX(NULLIF(TRIM(unit), '')), '') AS raw_unit
            FROM chemical_inventory_batches
            WHERE chemical_id = {$chemical_id}");
        if ($unit_q) {
            $unit_row = $unit_q->fetch_assoc();
            if ((int)($unit_row['mass_units'] ?? 0) > 0) {
                $summary_unit = 'kg';
            } elseif ((int)($unit_row['volume_units'] ?? 0) > 0) {
                $summary_unit = 'L';
            } elseif ((int)($unit_row['pcs_units'] ?? 0) > 0) {
                $summary_unit = 'pcs';
            } else {
                $summary_unit = trim((string)($unit_row['raw_unit'] ?? ''));
            }
        }
    } else {
        $summary_unit = trim((string)($chemical['unit'] ?? ''));
    }

    $incoming_sql = $batch_has_unit
        ? "SELECT b.*, c.name, c.brand, b.unit AS unit,
            (SELECT COALESCE(SUM(l.quantity),0) FROM chemical_stock_logs l WHERE l.batch_id = b.id AND UPPER(l.movement_type) = 'OUT') AS batch_outgoing
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.chemical_id = {$chemical_id}
            ORDER BY b.received_date DESC, b.id DESC"
        : "SELECT b.*, c.name, c.brand, COALESCE(c.unit,'') AS unit,
            (SELECT COALESCE(SUM(l.quantity),0) FROM chemical_stock_logs l WHERE l.batch_id = b.id AND UPPER(l.movement_type) = 'OUT') AS batch_outgoing
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.chemical_id = {$chemical_id}
            ORDER BY b.received_date DESC, b.id DESC";

    $incoming_q = $conn->query($incoming_sql);
    if ($incoming_q) {
        while ($row = $incoming_q->fetch_assoc()) {
            $incoming_rows[] = $row;
        }
    }
}

if ($logs_exists) {
    $outgoing_sql = $batch_has_unit
        ? "SELECT l.*, c.name, c.brand, b.batch_no, b.unit AS unit
            FROM chemical_stock_logs l
            INNER JOIN chemical_master_list c ON c.id = l.chemical_id
            LEFT JOIN chemical_inventory_batches b ON b.id = l.batch_id
            WHERE l.chemical_id = {$chemical_id} AND UPPER(l.movement_type) = 'OUT'
            ORDER BY l.created_at DESC, l.id DESC"
        : "SELECT l.*, c.name, c.brand, b.batch_no, COALESCE(b.unit, c.unit, '') AS unit
            FROM chemical_stock_logs l
            INNER JOIN chemical_master_list c ON c.id = l.chemical_id
            LEFT JOIN chemical_inventory_batches b ON b.id = l.batch_id
            WHERE l.chemical_id = {$chemical_id} AND UPPER(l.movement_type) = 'OUT'
            ORDER BY l.created_at DESC, l.id DESC";

    $outgoing_q = $conn->query($outgoing_sql);
    if ($outgoing_q) {
        while ($row = $outgoing_q->fetch_assoc()) {
            $outgoing_rows[] = $row;
            $total_outgoing += (float)($row['quantity'] ?? 0);
        }
    }
}
?>

<div class="no-print mb-3">
    <a href="<?php echo base_url ?>admin/?page=chemical_inventory" class="btn btn-sm btn-secondary">Back</a>
    <a href="<?php echo base_url ?>admin/?page=chemical_inventory&open_modal=incoming" class="btn btn-sm btn-success"><i class="fas fa-arrow-down"></i> Receive</a>
    <a href="<?php echo base_url ?>admin/?page=chemical_inventory&open_modal=outgoing" class="btn btn-sm btn-danger"><i class="fas fa-arrow-up"></i> Utilize</a>
    <button type="button" onclick="window.print()" class="btn btn-sm btn-info"><i class="fas fa-print"></i> Print</button>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <?php echo htmlspecialchars($chemical['name']); ?>
            <?php if(!empty($chemical['brand'])): ?>
                <small class="text-muted">- <?php echo htmlspecialchars($chemical['brand']); ?></small>
            <?php endif; ?>
        </h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-layer-group"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Batches</span>
                        <span class="info-box-number"><?php echo $batch_count; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Received</span>
                        <span class="info-box-number"><?php echo htmlspecialchars(ci_format_qty($total_received) . (!empty($summary_unit) ? ' ' . $summary_unit : '')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-hand-holding"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Utilized</span>
                        <span class="info-box-number"><?php echo htmlspecialchars(ci_format_qty($total_outgoing) . (!empty($summary_unit) ? ' ' . $summary_unit : '')); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-warehouse"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Available</span>
                        <span class="info-box-number"><?php echo htmlspecialchars(ci_format_qty($total_available) . (!empty($summary_unit) ? ' ' . $summary_unit : '')); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if(!empty($chemical['remarks'])): ?>
            <div class="alert alert-light border"><?php echo htmlspecialchars($chemical['remarks']); ?></div>
        <?php endif; ?>

        <h5 class="mb-2">Incoming Records</h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped" id="incoming-records-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>Batch No</th>
                        <th>Supplier</th>
                        <th>Storage</th>
                        <th class="text-center">Received Qty</th>
                        <th class="text-center">Available</th>
                        <th class="text-center">Received Date</th>
                        <th class="text-center">Expiry Date</th>
                        <th>Remarks</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($incoming_rows) === 0): ?>
                        <tr><td colspan="10" class="text-center text-muted">No incoming records found</td></tr>
                    <?php else: ?>
                        <?php $i = 1; foreach($incoming_rows as $row): ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?>.</td>
                                <td><?php echo htmlspecialchars($row['batch_no'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['storage_location'] ?? ''); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($row['received_qty']) . ' ' . ($row['unit'] ?? '')); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($row['available_qty']) . ' ' . ($row['unit'] ?? '')); ?></td>
                                <td class="text-center"><?php echo !empty($row['received_date']) ? date('d-M-Y', strtotime($row['received_date'])) : '-'; ?></td>
                                <td class="text-center"><?php echo !empty($row['expiry_date']) ? date('d-M-Y', strtotime($row['expiry_date'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                                <td class="text-center">
                                    <?php if(!empty($row['short_code'])): ?>
                                        <button type="button" class="btn btn-primary btn-sm print-barcode" data-short-code="<?php echo htmlspecialchars($row['short_code']); ?>" data-batch-id="<?php echo (int)$row['id']; ?>" data-chemical-name="<?php echo htmlspecialchars($chemical['name']); ?>" title="Print barcode"><i class="fas fa-barcode"></i></button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-danger btn-sm delete-incoming" data-id="<?php echo (int)$row['id']; ?>" onclick="event.preventDefault();event.stopPropagation();confirm_delete_incoming(<?php echo (int)$row['id']; ?>);"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h5 class="mb-2">Utilized Records</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="outgoing-records-table">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>Batch No</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Movement Date</th>
                        <th>Reference</th>
                        <th>Remarks</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($outgoing_rows) === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted">No outgoing records found</td></tr>
                    <?php else: ?>
                        <?php $j = 1; foreach($outgoing_rows as $row): ?>
                            <tr>
                                <td class="text-center"><?php echo $j++; ?>.</td>
                                <td><?php echo htmlspecialchars($row['batch_no'] ?? ''); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($row['quantity']) . ' ' . ($row['unit'] ?? '')); ?></td>
                                <td class="text-center"><?php echo !empty($row['created_at']) ? date('d-M-Y H:i', strtotime($row['created_at'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($row['reference_no'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm delete-outgoing" data-id="<?php echo (int)$row['id']; ?>" onclick="event.preventDefault();event.stopPropagation();confirm_delete_outgoing(<?php echo (int)$row['id']; ?>);"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

function confirm_delete_incoming(id){
    var incomingId = parseInt(id, 10) || 0;
    if (!incomingId) {
        alert_toast('Invalid incoming record ID', 'error');
        return;
    }
    if (!confirm('Delete this incoming record? This is allowed only when the batch is not consumed.')) {
        return;
    }
    delete_incoming(incomingId);
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

function confirm_delete_outgoing(id){
    var outgoingId = parseInt(id, 10) || 0;
    if (!outgoingId) {
        alert_toast('Invalid outgoing record ID', 'error');
        return;
    }
    if (!confirm('Delete this outgoing record? This will restore quantity back to stock.')) {
        return;
    }
    delete_outgoing(outgoingId);
}

$(function(){
    var successMsg = sessionStorage.getItem('success_message');
    if (successMsg) {
        alert_toast(successMsg, 'success');
        sessionStorage.removeItem('success_message');
    }

    $('#incoming-records-table').DataTable();
    $('#outgoing-records-table').DataTable();

    $(document).on('click', '.delete-incoming', function(e){
        e.preventDefault();
        e.stopPropagation();
        confirm_delete_incoming($(this).data('id'));
    });

    $(document).on('click', '.delete-outgoing', function(e){
        e.preventDefault();
        e.stopPropagation();
        confirm_delete_outgoing($(this).data('id'));
    });

    $(document).on('click', '.print-barcode', function(e){
        e.preventDefault();
        e.stopPropagation();
        var shortCode = $(this).data('short-code');
        var batchId = $(this).data('batch-id');
        var chemicalName = $(this).data('chemical-name');
        if (!shortCode || !batchId) {
            alert_toast('Missing barcode information', 'error');
            return;
        }
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
    });
});
</script>
