<?php
/**
 * Stock Movement History - Display all received stock records
 */

// Ensure connection is available
if (!isset($conn)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection not available'
    ]);
    exit;
}

// Get all stock movements for receiving (both PO and Manual)
$stock_movements = [];
$movement_query = $conn->query("SELECT sm.*, il.name as item_name, pol.po_code 
    FROM stock_movement sm
    JOIN item_list il ON sm.item_id = il.id
    LEFT JOIN purchase_order_list pol ON sm.reference_id = pol.id AND sm.reference_type = 'PO'
    WHERE (sm.reference_type = 'PO' OR sm.reference_type = 'MANUAL') AND sm.movement_type = 'IN'
    ORDER BY sm.created_at DESC
    LIMIT 500");

if ($movement_query) {
    while($row = $movement_query->fetch_assoc()) {
        $stock_movements[] = $row;
    }
}

// Group stock movements by transaction (same date + po_id + user)
$grouped_movements = [];
$transaction_groups = [];

foreach($stock_movements as $sm) {
    // Create a transaction key based on created_at date, PO, and user
    $transaction_key = date('Y-m-d H:i:00', strtotime($sm['created_at'])) . '_' . $sm['reference_id'] . '_' . $sm['created_by'];
    
    if (!isset($transaction_groups[$transaction_key])) {
        $transaction_groups[$transaction_key] = [
            'items' => [],
            'total_quantity' => 0,
            'created_at' => $sm['created_at'],
            'po_code' => $sm['po_code'],
            'reference_id' => $sm['reference_id'],
            'reference_type' => $sm['reference_type'],
            'created_by' => $sm['created_by']
        ];
    }
    
    $transaction_groups[$transaction_key]['items'][] = [
        'item_name' => $sm['item_name'],
        'quantity' => $sm['quantity'],
        'remarks' => $sm['remarks']
    ];
    
    $transaction_groups[$transaction_key]['total_quantity'] += $sm['quantity'];
}

// Get summary statistics
$summary = [
    'total_receives' => 0,
    'total_quantity' => 0,
    'unique_pos' => 0,
    'unique_items' => 0
];

$summary_query = $conn->query("SELECT 
    COUNT(DISTINCT sm.id) as total_receives,
    COUNT(DISTINCT sm.reference_id) as unique_pos,
    COUNT(DISTINCT sm.item_id) as unique_items,
    COALESCE(SUM(sm.quantity), 0) as total_quantity
    FROM stock_movement sm
    WHERE (sm.reference_type = 'PO' OR sm.reference_type = 'MANUAL') AND sm.movement_type = 'IN'");

if ($summary_query && $summary_query->num_rows > 0) {
    $summary = array_merge($summary, $summary_query->fetch_assoc());
}
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">📋 Stock Received History</h3>
        <div class="card-tools">
            <a class="btn btn-flat btn-primary" href="<?php echo base_url ?>admin/?page=receiving/manage_receiving">
                <span class="fas fa-plus"></span> Receive Stock
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- STATISTICS CARDS -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-receipt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Receives</span>
                        <span class="info-box-number"><?php echo $summary['total_receives']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Quantity</span>
                        <span class="info-box-number"><?php echo $summary['total_quantity']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-file-invoice"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">POs</span>
                        <span class="info-box-number"><?php echo $summary['unique_pos']; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-cubes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Items</span>
                        <span class="info-box-number"><?php echo $summary['unique_items']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER SECTION -->
        <div class="card card-outline card-secondary mb-3">
            <div class="card-header">
                <h5 class="card-title">🔍 Search & Filter</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" id="search-input" class="form-control" placeholder="Search by PO Code, Item Name...">
                    </div>
                    <div class="col-md-4">
                        <input type="date" id="date-filter" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-sm btn-primary" id="export-btn"><i class="fas fa-download"></i> Export CSV</button>
                        <button type="button" class="btn btn-sm btn-secondary" id="reset-btn"><i class="fas fa-redo"></i> Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STOCK MOVEMENT HISTORY TABLE -->
        <div class="table-responsive">
    <table class="table table-bordered table-striped table-sm" id="history-table">
        <thead style="background-color: rgb(0, 31, 63); color: white; position: sticky; top: 0;">
            <tr>
                <th width="80">PO Code</th>
                <th>Items Received</th>
                <th width="100" class="text-center">Total Qty</th>
                <th>Remarks</th>
                <th width="160" class="text-center">Date & Time</th>
                <th width="50" class="text-center">Action</th>
            </tr>
        </thead>
        <tbody id="table-body">
            <?php if (count($transaction_groups) > 0): ?>
                <?php foreach($transaction_groups as $key => $group): ?>
                    <tr class="movement-row" 
                        data-po="<?php echo $group['po_code'] ?: ''; ?>" 
                        data-date="<?php echo date('Y-m-d', strtotime($group['created_at'])); ?>"
                        data-created-at="<?php echo $group['created_at']; ?>"
                        data-reference-type="<?php echo $group['reference_type']; ?>">
                        <td>
                            <strong>
                                <?php if ($group['po_code']): ?>
                                    <a href="<?php echo base_url; ?>admin/?page=purchase_order/view_po&id=<?php echo $group['reference_id']; ?>" 
                                       class="text-dark" target="_blank">
                                        <?php echo $group['po_code']; ?>
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </strong>
                        </td>
                        <td>
                            <div style="font-size: 13px;">
                                <?php foreach($group['items'] as $idx => $item): ?>
                                    <div style="margin-bottom: 5px;">
                                        <span class="badge badge-info"><?php echo $item['quantity']; ?></span>
                                        <strong><?php echo $item['item_name']; ?></strong>
                                        <?php if ($item['remarks']): ?>
                                            <br><small class="text-muted"><em><?php echo $item['remarks']; ?></em></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <strong style="font-size: 16px; color: #28a745;">
                                <?php echo $group['total_quantity']; ?>
                            </strong>
                        </td>
                        <td>
                            <small class="text-muted">
                                Combined receipt (<?php echo count($group['items']); ?> item<?php echo count($group['items']) > 1 ? 's' : ''; ?>)
                            </small>
                        </td>
                        <td class="text-center">
                            <small><?php echo date('d-m-Y H:i', strtotime($group['created_at'])); ?></small>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger delete-row" data-transaction-key="<?php echo $key; ?>" title="Delete this receipt">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox" style="font-size: 40px; opacity: 0.3;"></i>
                        <p style="margin-top: 10px;">No stock movements recorded yet</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    // Search functionality
    $('#search-input').on('keyup', function() {
        filterTable();
    });
    
    // Date filter
    $('#date-filter').on('change', function() {
        filterTable();
    });
    
    // Filter table
    function filterTable() {
        var searchTerm = $('#search-input').val().toLowerCase();
        var dateFilter = $('#date-filter').val();
        
        $('#table-body tr.movement-row').each(function() {
            var row = $(this);
            var po = row.data('po').toLowerCase();
            var item = row.data('item').toLowerCase();
            var date = row.data('date');
            
            var searchMatch = po.includes(searchTerm) || item.includes(searchTerm);
            var dateMatch = !dateFilter || date === dateFilter;
            
            row.toggle(searchMatch && dateMatch);
        });
    }
    
    // Reset filters
    $('#reset-btn').click(function() {
        $('#search-input').val('');
        $('#date-filter').val('');
        $('#table-body tr.movement-row').show();
    });
    
    // Export CSV
    $('#export-btn').click(function() {
        var csv = [];
        var headers = [];
        
        // Get headers
        $('#history-table thead th').each(function() {
            headers.push($(this).text());
        });
        csv.push(headers.join(','));
        
        // Get visible rows
        $('#table-body tr.movement-row:visible').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                row.push('"' + $(this).text().trim() + '"');
            });
            csv.push(row.join(','));
        });
        
        // Create download link
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', 'stock_received_history_' + new Date().getTime() + '.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Delete row
    $(document).on('click', '.delete-row', function() {
        var btn = $(this);
        var row = btn.closest('tr');
        var poCode = row.find('td:first').text().trim();
        var po = row.data('po');
        var date = row.data('date');
        var createdAt = row.data('created-at');
        var referenceType = row.data('reference-type');
        
        // Validation - check that we have the data we need
        if (!createdAt || !referenceType || !date) {
            alert_toast('Invalid record data', 'error');
            return;
        }
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Delete Receipt?',
            text: 'Are you sure you want to delete this receipt for PO ' + poCode + '? This will remove all items in this transaction.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Disable button and show loader
                btn.prop('disabled', true);
                var originalHtml = btn.html();
                btn.html('<i class="fas fa-spinner fa-spin"></i>');
                
                // Send delete request
                $.ajax({
                    url: '<?php echo base_url ?>classes/Master.php?f=delete_receipt',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        po: po,
                        date: date,
                        created_at: createdAt,
                        reference_type: referenceType
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            // Fade out row smoothly
                            row.fadeOut(300, function() {
                                row.remove();
                                alert_toast('Receipt deleted successfully', 'success');
                            });
                        } else {
                            alert_toast('Error: ' + (response.msg || 'Unknown error'), 'error');
                            btn.prop('disabled', false);
                            btn.html(originalHtml);
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorMsg = 'Failed to delete receipt';
                        
                        // Try to parse error response
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.message || response.msg) {
                                errorMsg = response.message || response.msg;
                            }
                        } catch(e) {
                            errorMsg = 'Network error: ' + (error || 'Unknown error');
                        }
                        
                        alert_toast(errorMsg, 'error');
                        btn.prop('disabled', false);
                        btn.html(originalHtml);
                    }
                });
            }
        });
    });
});
</script>
