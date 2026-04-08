<?php
/**
 * Chemical Inventory Dashboard
 */

if (!isset($conn)) {
    die('Database connection not available');
}

$open_incoming_modal = isset($_GET['open_modal']) && $_GET['open_modal'] === 'incoming';

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

if (!function_exists('ci_format_qty')) {
    function ci_format_qty($value) {
        $n = (float)$value;
        if (abs($n - round($n)) < 0.000001) {
            return number_format($n, 0, '.', '');
        }
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
    }
}

$total_batches = 0;
$total_available = 0;
$expired_count = 0;
$expiring_30_count = 0;
$incoming_rows = [];
$outgoing_rows = [];
$inventory_totals = [];
$chemicals = [];
$available_chemicals = [];
$suppliers = [];
$batch_has_unit = false;

$chem_q = $conn->query("SELECT id, name, brand FROM chemical_master_list ORDER BY name ASC");
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
    $col_q = $conn->query("SHOW COLUMNS FROM chemical_inventory_batches LIKE 'unit'");
    if ($col_q && $col_q->num_rows > 0) {
        $batch_has_unit = true;
    }
}

if ($batch_exists) {
    $sum_q = $conn->query("SELECT COUNT(*) AS total_batches, COALESCE(SUM(available_qty),0) AS total_available FROM chemical_inventory_batches");
    if ($sum_q) {
        $sum = $sum_q->fetch_assoc();
        $total_batches = (int)$sum['total_batches'];
        $total_available = (float)$sum['total_available'];
    }

    $exp_q = $conn->query("SELECT
        SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_count,
        SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_30_count
        FROM chemical_inventory_batches
        WHERE available_qty > 0");
    if ($exp_q) {
        $exp = $exp_q->fetch_assoc();
        $expired_count = (int)$exp['expired_count'];
        $expiring_30_count = (int)$exp['expiring_30_count'];
    }

    $unit_select = $batch_has_unit ? "b.unit AS unit" : "c.unit AS unit";
    $in_q = $conn->query("SELECT b.id, b.batch_no, b.supplier, b.storage_location, b.received_qty, b.available_qty,
        b.expiry_date, b.received_date, b.remarks, c.name, c.brand, {$unit_select}
        FROM chemical_inventory_batches b
        INNER JOIN chemical_master_list c ON c.id = b.chemical_id
        ORDER BY b.id DESC
        LIMIT 100");
    if ($in_q) {
        while ($r = $in_q->fetch_assoc()) {
            $incoming_rows[] = $r;
        }
    }

    if ($batch_has_unit) {
        $tot_q = $conn->query("SELECT c.id AS chemical_id, c.name, c.brand,
            COALESCE(SUM(CASE
                WHEN LOWER(TRIM(b.unit)) = 'kg' THEN b.received_qty
                WHEN LOWER(TRIM(b.unit)) = 'g' THEN b.received_qty / 1000
                WHEN LOWER(TRIM(b.unit)) = 'l' THEN b.received_qty
                WHEN LOWER(TRIM(b.unit)) = 'ml' THEN b.received_qty / 1000
                ELSE b.received_qty
            END),0) AS total_received,
            COALESCE(SUM(CASE
                WHEN LOWER(TRIM(b.unit)) = 'kg' THEN b.available_qty
                WHEN LOWER(TRIM(b.unit)) = 'g' THEN b.available_qty / 1000
                WHEN LOWER(TRIM(b.unit)) = 'l' THEN b.available_qty
                WHEN LOWER(TRIM(b.unit)) = 'ml' THEN b.available_qty / 1000
                ELSE b.available_qty
            END),0) AS total_available,
            COUNT(b.id) AS batch_count,
            SUM(CASE WHEN b.expiry_date IS NOT NULL AND b.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_batches,
            SUM(CASE WHEN b.expiry_date IS NOT NULL AND b.expiry_date >= CURDATE() AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS near_expiry_batches,
                        (SELECT bb.batch_no
                         FROM chemical_inventory_batches bb
                         WHERE bb.chemical_id = c.id
                             AND bb.available_qty > 0
                             AND bb.expiry_date IS NOT NULL
                         ORDER BY bb.expiry_date ASC, bb.id ASC
                         LIMIT 1) AS nearest_batch_no,
                        (SELECT bb.expiry_date
                         FROM chemical_inventory_batches bb
                         WHERE bb.chemical_id = c.id
                             AND bb.available_qty > 0
                             AND bb.expiry_date IS NOT NULL
                         ORDER BY bb.expiry_date ASC, bb.id ASC
                         LIMIT 1) AS nearest_expiry,
            CASE
                WHEN SUM(CASE WHEN LOWER(TRIM(b.unit)) IN ('kg','g') THEN 1 ELSE 0 END) > 0 THEN 'kg'
                WHEN SUM(CASE WHEN LOWER(TRIM(b.unit)) IN ('l','ml') THEN 1 ELSE 0 END) > 0 THEN 'L'
                WHEN SUM(CASE WHEN LOWER(TRIM(b.unit)) = 'pcs' THEN 1 ELSE 0 END) > 0 THEN 'pcs'
                ELSE COALESCE(MAX(NULLIF(TRIM(b.unit), '')), '')
            END AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            GROUP BY c.id, c.name, c.brand
            ORDER BY c.name ASC");
    } else {
        $tot_q = $conn->query("SELECT c.id AS chemical_id, c.name, c.brand,
            COALESCE(SUM(b.received_qty),0) AS total_received,
            COALESCE(SUM(b.available_qty),0) AS total_available,
            COUNT(b.id) AS batch_count,
            SUM(CASE WHEN b.expiry_date IS NOT NULL AND b.expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired_batches,
            SUM(CASE WHEN b.expiry_date IS NOT NULL AND b.expiry_date >= CURDATE() AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS near_expiry_batches,
                        (SELECT bb.batch_no
                         FROM chemical_inventory_batches bb
                         WHERE bb.chemical_id = c.id
                             AND bb.available_qty > 0
                             AND bb.expiry_date IS NOT NULL
                         ORDER BY bb.expiry_date ASC, bb.id ASC
                         LIMIT 1) AS nearest_batch_no,
                        (SELECT bb.expiry_date
                         FROM chemical_inventory_batches bb
                         WHERE bb.chemical_id = c.id
                             AND bb.available_qty > 0
                             AND bb.expiry_date IS NOT NULL
                         ORDER BY bb.expiry_date ASC, bb.id ASC
                         LIMIT 1) AS nearest_expiry,
            COALESCE(c.unit, '') AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            GROUP BY c.id, c.name, c.brand, c.unit
            ORDER BY c.name ASC");
    }
    if ($tot_q) {
        while ($row = $tot_q->fetch_assoc()) {
            $inventory_totals[] = $row;
        }
    }

    if ($batch_has_unit) {
        $chem_avail_q = $conn->query("SELECT c.id AS chemical_id, c.name, c.brand,
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
            ORDER BY c.name ASC
            LIMIT 300");
    } else {
        $chem_avail_q = $conn->query("SELECT c.id AS chemical_id, c.name, c.brand,
            SUM(b.available_qty) AS available_qty,
            COALESCE(c.unit, '') AS unit
            FROM chemical_inventory_batches b
            INNER JOIN chemical_master_list c ON c.id = b.chemical_id
            WHERE b.available_qty > 0
            GROUP BY c.id, c.name, c.brand, c.unit
            ORDER BY c.name ASC
            LIMIT 300");
    }
    if ($chem_avail_q) {
        while ($row = $chem_avail_q->fetch_assoc()) {
            $available_chemicals[] = $row;
        }
    }
}

if ($logs_exists) {
    $out_unit_select = $batch_has_unit ? "b.unit AS unit" : "c.unit AS unit";
    $out_q = $conn->query("SELECT l.id, l.batch_id, l.quantity, l.reference_type, l.reference_no, l.movement_date,
        COALESCE(NULLIF(l.created_at, ''), CONCAT(l.movement_date, ' 00:00:00')) AS usage_at,
        l.remarks,
        c.name, c.brand, {$out_unit_select}, b.batch_no
        FROM chemical_stock_logs l
        INNER JOIN chemical_master_list c ON c.id = l.chemical_id
        LEFT JOIN chemical_inventory_batches b ON b.id = l.batch_id
        WHERE l.movement_type = 'OUT'
        ORDER BY l.id DESC
        LIMIT 100");
    if ($out_q) {
        while ($r = $out_q->fetch_assoc()) {
            $outgoing_rows[] = $r;
        }
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Chemical Inventory</h3>
            <div style="margin-left:auto; display:flex; gap:8px;">
                <button type="button" id="open-incoming-modal" class="btn btn-sm btn-success" <?php echo !$batch_exists ? 'disabled' : ''; ?>>
                    <i class="fas fa-arrow-down"></i> Incoming
                </button>
                <button type="button" id="open-outgoing-modal" class="btn btn-sm btn-danger" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                    <i class="fas fa-arrow-up"></i> Outgoing
                </button>
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
                    <strong>Missing table:</strong> <code>chemical_stock_logs</code>. Outgoing records tab will be empty until this table exists.
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-layer-group"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Open Batches</span>
                            <span class="info-box-number"><?php echo $total_batches; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-boxes"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Available Qty</span>
                            <span class="info-box-number"><?php echo number_format($total_available, 0); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Expired</span>
                            <span class="info-box-number"><?php echo $expired_count; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Expiring in 30d</span>
                            <span class="info-box-number"><?php echo $expiring_30_count; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs" id="chemical-inventory-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="total-inventory-tab" data-toggle="tab" href="#total-inventory" role="tab" aria-controls="total-inventory" aria-selected="true">
                        <i class="fas fa-chart-pie text-primary"></i> Total Inventory
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="incoming-tab" data-toggle="tab" href="#incoming-records" role="tab" aria-controls="incoming-records" aria-selected="false">
                        <i class="fas fa-arrow-down text-success"></i> Incoming Records
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="outgoing-tab" data-toggle="tab" href="#outgoing-records" role="tab" aria-controls="outgoing-records" aria-selected="false">
                        <i class="fas fa-arrow-up text-danger"></i> Outgoing Records
                    </a>
                </li>                
            </ul>

            <div class="tab-content border border-top-0 p-3" id="chemical-inventory-tab-content">
                <div class="tab-pane fade" id="incoming-records" role="tabpanel" aria-labelledby="incoming-tab">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead style="background-color: rgb(0, 31, 63); color: white;">
                                <tr>
                                    <th width="45">#</th>
                                    <th>Chemical</th>
                                    <th width="100">Batch</th>
                                    <th width="200">Storage</th>
                                    <th width="90" class="text-center">Received Qty</th>
                                    <th width="110" class="text-center">Received Date</th>
                                    <th width="100" class="text-center">Expiry</th>
                                    <th width="70" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($incoming_rows) === 0): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No incoming records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $i = 1; foreach($incoming_rows as $r): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $i++; ?></td>
                                            <td><?php echo htmlspecialchars($r['name'] . (!empty($r['brand']) ? ' - ' . $r['brand'] : '')); ?></td>
                                            <td><?php echo htmlspecialchars($r['batch_no'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['storage_location'] ?? ''); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($r['received_qty']) . ' ' . ($r['unit'] ?? '')); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($r['received_date'] ?? ''); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($r['expiry_date'] ?? ''); ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm delete-incoming" data-id="<?php echo (int)$r['id']; ?>" title="Delete Incoming">
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

                <div class="tab-pane fade" id="outgoing-records" role="tabpanel" aria-labelledby="outgoing-tab">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead style="background-color: rgb(0, 31, 63); color: white;">
                                <tr>
                                    <th width="45">#</th>
                                    <th>Chemical</th>
                                    <th width="90" class="text-center">Qty Out</th>
                                    <th width="150" class="text-center">Usage Date/Time</th>
                                    <th>Remarks</th>
                                    <th width="70" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($outgoing_rows) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No outgoing records found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $j = 1; foreach($outgoing_rows as $r): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $j++; ?></td>
                                            <td><?php echo htmlspecialchars($r['name'] . (!empty($r['brand']) ? ' - ' . $r['brand'] : '')); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($r['quantity']) . ' ' . ($r['unit'] ?? '')); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars(!empty($r['usage_at']) ? date('d-m-Y H:i', strtotime($r['usage_at'])) : ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['remarks'] ?? ''); ?></td>
                                            <td class="text-center">
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

                <div class="tab-pane fade show active" id="total-inventory" role="tabpanel" aria-labelledby="total-inventory-tab">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead style="background-color: rgb(0, 31, 63); color: white;">
                                <tr>
                                    <th width="45">#</th>
                                    <th>Chemical</th>
                                    <th width="110" class="text-center">Batches</th>
                                    <th width="130" class="text-center">Total Received</th>
                                    <th width="130" class="text-center">Total Available</th>
                                    <th width="150" class="text-center">Expiry Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($inventory_totals) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No inventory summary available</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $k = 1; foreach($inventory_totals as $t): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $k++; ?></td>
                                            <td><?php echo htmlspecialchars($t['name'] . (!empty($t['brand']) ? ' - ' . $t['brand'] : '')); ?></td>
                                            <td class="text-center"><?php echo (int)$t['batch_count']; ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($t['total_received']) . ' ' . ($t['unit'] ?? '')); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars(ci_format_qty($t['total_available']) . ' ' . ($t['unit'] ?? '')); ?></td>
                                            <td class="text-center">
                                                <?php
                                                    $expired_batches = (int)($t['expired_batches'] ?? 0);
                                                    $near_expiry_batches = (int)($t['near_expiry_batches'] ?? 0);
                                                    $nearest_expiry = !empty($t['nearest_expiry']) ? date('d-m-Y', strtotime($t['nearest_expiry'])) : '';
                                                    $nearest_batch_no = trim((string)($t['nearest_batch_no'] ?? ''));
                                                    if ($nearest_batch_no !== '' || $nearest_expiry !== '') {
                                                        $line = 'Batch ' . ($nearest_batch_no !== '' ? $nearest_batch_no : '-') . ' - ' . ($nearest_expiry !== '' ? $nearest_expiry : '-');
                                                        $badge_class = 'badge-secondary';
                                                        if ($expired_batches > 0) {
                                                            $badge_class = 'badge-danger';
                                                        } elseif ($near_expiry_batches > 0) {
                                                            $badge_class = 'badge-warning';
                                                        } else {
                                                            $badge_class = 'badge-success';
                                                        }
                                                        echo '<span class="badge ' . $badge_class . '" style="font-size: 0.9rem; padding: 0.45rem 0.6rem;">' . htmlspecialchars($line) . '</span>';
                                                    } else {
                                                        echo '<span class="badge badge-secondary">No expiry set</span>';
                                                    }
                                                ?>
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
                    <div class="col-md-12">
                        <label>Chemical <span class="text-danger">*</span></label>
                        <select id="out_chemical_id" class="form-control" style="width:100%;" <?php echo (!$batch_exists || !$logs_exists) ? 'disabled' : ''; ?>>
                            <option value="">Select chemical</option>
                            <?php foreach($available_chemicals as $c): ?>
                                <option
                                    value="<?php echo (int)$c['chemical_id']; ?>"
                                    data-available="<?php echo htmlspecialchars(ci_format_qty($c['available_qty'])); ?>"
                                    data-unit="<?php echo htmlspecialchars($c['unit'] ?? ''); ?>"
                                >
                                    <?php echo htmlspecialchars(
                                        $c['name'] .
                                        (!empty($c['brand']) ? ' - ' . $c['brand'] : '') .
                                        ' | Available: ' . ci_format_qty($c['available_qty']) . ' ' . ($c['unit'] ?? '')
                                    ); ?>
                                </option>
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

    function openNewChemicalModal(prefillName){
        $('#new_chem_name').val(prefillName || '');
        $('#new_chem_brand').val('');
        $('#new_chem_remarks').val('');
        $('#new-chemical-modal').modal('show');
    }

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
                openNewChemicalModal(selected.term || addNewChemicalTerm);
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
                    sessionStorage.setItem('success_message', 'Incoming saved successfully.');
                    window.location.href = '<?php echo base_url ?>admin/?page=chemical_inventory';
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
        var qtyVal = parseFloat($('#out_quantity').val());
        var payload = {
            chemical_id: $('#out_chemical_id').val(),
            quantity: isNaN(qtyVal) ? '' : qtyVal,
            utilized_at: $('#out_utilized_at').val(),
            remarks: $('#out_remarks').val().trim()
        };

        if (!payload.chemical_id) {
            alert_toast('Chemical is required', 'warning');
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
    <?php endif; ?>

    $(document).on('click', '.delete-incoming', function(){
        var id = $(this).data('id');
        _conf('Delete this incoming record? This is allowed only when the batch is not consumed.', 'delete_incoming', [id]);
    });

    $(document).on('click', '.delete-outgoing', function(){
        var id = $(this).data('id');
        _conf('Delete this outgoing record? This will restore quantity back to stock.', 'delete_outgoing', [id]);
    });

});
</script>
