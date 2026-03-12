 <?php
/**
 * Combined Stock In/Out Management
 * Shows history tables with action buttons
 */

if (!isset($conn)) {
    die('Database connection not available');
}

// Get receiving summary
$receive_summary = $conn->query("SELECT 
    COUNT(DISTINCT sm.id) as total_receives,
    COALESCE(SUM(sm.quantity), 0) as total_quantity
    FROM stock_movement sm
    WHERE sm.movement_type = 'IN'")->fetch_assoc();

// Get utilization summary
$utilize_summary = $conn->query("SELECT 
    COUNT(DISTINCT uh.id) as total_utilizations,
    COALESCE(SUM(uh.quantity_used), 0) as total_quantity_used
    FROM utilization_history uh")->fetch_assoc();

// Get recent receiving records
$recent_receives = [];
$receive_query = $conn->query("SELECT sm.*, il.name as item_name, pol.po_code 
    FROM stock_movement sm
    JOIN item_list il ON sm.item_id = il.id
    LEFT JOIN purchase_order_list pol ON sm.reference_id = pol.id AND sm.reference_type = 'PO'
    WHERE sm.movement_type = 'IN'
    ORDER BY sm.created_at DESC
    LIMIT 50");
if ($receive_query) {
    while($row = $receive_query->fetch_assoc()) {
        $recent_receives[] = $row;
    }
}

// Get recent utilization records
$recent_utilizes = [];
$utilize_query = $conn->query("SELECT uh.*, il.name as item_name, 
    CONCAT(u.firstname, ' ', u.lastname) as utilized_by_name
    FROM utilization_history uh
    JOIN item_list il ON uh.item_id = il.id
    LEFT JOIN users u ON uh.utilized_by = u.id
    ORDER BY uh.utilized_at DESC
    LIMIT 50");
if ($utilize_query) {
    while($row = $utilize_query->fetch_assoc()) {
        $recent_utilizes[] = $row;
    }
}

// Get inventory levels
$inventory = [];
$inventory_query = $conn->query("SELECT 
    il.id,
    il.name,
    COALESCE(SUM(sm.quantity), 0) as qty_received,
    COALESCE((SELECT SUM(uh.quantity_used) FROM utilization_history uh WHERE uh.item_id = il.id), 0) as qty_used,
    COALESCE(SUM(sm.quantity), 0) - COALESCE((SELECT SUM(uh.quantity_used) FROM utilization_history uh WHERE uh.item_id = il.id), 0) as available_qty
    FROM item_list il
    LEFT JOIN stock_movement sm ON il.id = sm.item_id AND sm.movement_type = 'IN'
    GROUP BY il.id, il.name
    HAVING qty_received > 0
    ORDER BY il.name ASC");
if ($inventory_query) {
    while($row = $inventory_query->fetch_assoc()) {
        $inventory[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">📦 Stock In/Out Management</h3>
        </div>
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Stock In</span>
                            <span class="info-box-number" id="stock-in-total"><?php echo $receive_summary['total_quantity']; ?> items</span>
                            <span class="info-box-text" id="stock-in-transactions"><?php echo $receive_summary['total_receives']; ?> transactions</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box bg-danger">
                        <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Stock Out</span>
                            <span class="info-box-number" id="stock-out-total"><?php echo $utilize_summary['total_quantity_used']; ?> items</span>
                            <span class="info-box-text" id="stock-out-transactions"><?php echo $utilize_summary['total_utilizations']; ?> transactions</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="stock-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="receive-tab" data-toggle="tab" href="#receive" role="tab">
                        <i class="fas fa-download"></i> Stock In (Receiving)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="utilize-tab" data-toggle="tab" href="#utilize" role="tab">
                        <i class="fas fa-upload"></i> Stock Out (Utilization)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="inventory-tab" data-toggle="tab" href="#inventory" role="tab">
                        <i class="fas fa-boxes"></i> Current Inventory
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="stock-tabs-content" style="border: 1px solid #dee2e6; border-top: none; padding: 20px;">
                <!-- RECEIVE TAB -->
                <div class="tab-pane fade show active" id="receive" role="tabpanel">
                    <div class="mb-3">
                        <a class="btn btn-primary" href="<?php echo base_url ?>admin/?page=stock/manage_receiving">
                            <i class="fas fa-plus"></i> Receive Stock
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead style="background-color: rgb(0, 31, 63); color: white;">
                                <tr>
                                    <th width="120">Date</th>
                                    <th width="100">PO Code</th>
                                    <th>Item</th>
                                    <th width="80" class="text-center">Qty</th>
                                    <th>Remarks</th>
                                    <th width="60" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_receives as $rec): ?>
                                <tr>
                                    <td><small><?php echo date('d-m-Y H:i', strtotime($rec['created_at'])); ?></small></td>
                                    <td><?php echo $rec['po_code'] ?: 'Manual'; ?></td>
                                    <td><strong><?php echo htmlspecialchars($rec['item_name']); ?></strong></td>
                                    <td class="text-center"><span class="badge badge-success"><?php echo $rec['quantity']; ?></span></td>
                                    <td><small><?php echo htmlspecialchars($rec['remarks']); ?></small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-danger delete-receive" 
                                            data-id="<?php echo $rec['id']; ?>"
                                            data-qty="<?php echo $rec['quantity']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- UTILIZE TAB -->
                <div class="tab-pane fade" id="utilize" role="tabpanel">
                    <div class="mb-3">
                        <a class="btn btn-success" href="<?php echo base_url ?>admin/?page=stock/manage_utilization">
                            <i class="fas fa-plus"></i> Utilize Stock
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead style="background-color: rgb(0, 31, 63); color: white;">
                                <tr>
                                    <th width="120">Date</th>
                                    <th>Item</th>
                                    <th width="80" class="text-center">Qty Used</th>
                                    <th width="150">Purpose</th>
                                    <th width="120">Utilized By</th>
                                    <th>Remarks</th>
                                    <th width="60" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_utilizes as $util): ?>
                                <tr>
                                    <td><small><?php echo date('d-m-Y H:i', strtotime($util['utilized_at'])); ?></small></td>
                                    <td><strong><?php echo htmlspecialchars($util['item_name']); ?></strong></td>
                                    <td class="text-center"><span class="badge badge-danger"><?php echo $util['quantity_used']; ?></span></td>
                                    <td><small><?php echo htmlspecialchars($util['purpose']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($util['utilized_by_name']); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($util['remarks']); ?></small></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-danger delete-utilize" data-id="<?php echo $util['id']; ?>" data-qty="<?php echo $util['quantity_used']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- INVENTORY TAB -->
                <div class="tab-pane fade" id="inventory" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead style="background-color: rgb(0, 31, 63); color: white;">
                                <tr>
                                    <th>Item Name</th>
                                    <th width="100" class="text-center">Qty Received</th>
                                    <th width="100" class="text-center">Qty Used</th>
                                    <th width="120" class="text-center">Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($inventory) > 0): ?>
                                    <?php foreach($inventory as $item): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td class="text-center"><span class="badge badge-info"><?php echo $item['qty_received']; ?></span></td>
                                        <td class="text-center"><span class="badge badge-warning"><?php echo $item['qty_used']; ?></span></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo ($item['available_qty'] > 0) ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $item['available_qty']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No items in inventory</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function parseCount(text) {
        var value = parseFloat(String(text).replace(/[^0-9.\-]/g, ''));
        return isNaN(value) ? 0 : value;
    }

    function formatCount(value, label) {
        return Math.max(0, value) + ' ' + label;
    }

    function updateSummary(type, qty) {
        var totalSelector = type === 'in' ? '#stock-in-total' : '#stock-out-total';
        var transactionSelector = type === 'in' ? '#stock-in-transactions' : '#stock-out-transactions';
        var currentTotal = parseCount($(totalSelector).text());
        var currentTransactions = parseCount($(transactionSelector).text());
        var quantity = parseCount(qty);

        $(totalSelector).text(formatCount(currentTotal - quantity, 'items'));
        $(transactionSelector).text(formatCount(currentTransactions - 1, 'transactions'));
    }

    function confirmDelete(message, callback) {
        Swal.fire({
            title: 'Delete record?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c82333',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                callback();
            }
        });
    }

    function parseDeleteResponse(xhr) {
        if (xhr.responseJSON) {
            return xhr.responseJSON;
        }

        if (!xhr.responseText) {
            return null;
        }

        var responseText = $.trim(xhr.responseText);

        try {
            return JSON.parse(responseText);
        } catch (e) {
            var jsonStart = responseText.indexOf('{');
            var jsonEnd = responseText.lastIndexOf('}');

            if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                try {
                    return JSON.parse(responseText.substring(jsonStart, jsonEnd + 1));
                } catch (innerError) {
                    return null;
                }
            }
        }

        return null;
    }

    function handleDeleteSuccess(btn, message, type, qty) {
        updateSummary(type, qty);
        alert_toast(message, 'success');
        btn.closest('tr').fadeOut(function() { $(this).remove(); });
    }

    function handleDeleteError(xhr, fallbackMessage) {
        var response = parseDeleteResponse(xhr);

        if (response && response.status === 'success') {
            return response;
        }

        alert_toast(response && response.msg ? 'Error: ' + response.msg : fallbackMessage, 'error');
        return null;
    }

    // Delete receiving record
    $('.delete-receive').click(function() {
        var id = $(this).data('id');
        var qty = $(this).data('qty');
        var btn = $(this);

        confirmDelete('This stock-in entry will be removed permanently.', function() {
            $.ajax({
                url: '<?php echo base_url ?>classes/Master.php?f=delete_receipt',
                type: 'POST',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        handleDeleteSuccess(btn, 'Receiving record deleted', 'in', qty);
                    } else {
                        alert_toast('Error: ' + response.msg, 'error');
                    }
                },
                error: function(xhr) {
                    var response = handleDeleteError(xhr, 'Error deleting record');
                    if (response && response.status === 'success') {
                        handleDeleteSuccess(btn, 'Receiving record deleted', 'in', qty);
                    }
                }
            });
        });
    });

    // Delete utilization record
    $('.delete-utilize').click(function() {
        var id = $(this).data('id');
        var qty = $(this).data('qty');
        var btn = $(this);

        confirmDelete('This stock-out entry will be removed permanently.', function() {
            $.ajax({
                url: '<?php echo base_url ?>classes/Master.php?f=delete_utilization',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.status === 'success') {
                        handleDeleteSuccess(btn, 'Utilization record deleted', 'out', qty);
                    } else {
                        alert_toast('Error: ' + response.msg, 'error');
                    }
                },
                error: function(xhr) {
                    var response = handleDeleteError(xhr, 'Error deleting record');
                    if (response && response.status === 'success') {
                        handleDeleteSuccess(btn, 'Utilization record deleted', 'out', qty);
                    }
                }
            });
        });
    });
});
</script>
