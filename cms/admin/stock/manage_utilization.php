<?php
/**
 * Utilize Stock - Scan QR code and input quantity being used
 * Tracks stock utilization and depletion from received inventory
 */

if (!isset($conn)) {
    die('Database connection not available');
}

// Get projects for dropdown (optional field)
$projects = [];
$project_query = $conn->query("SELECT id, name FROM project_planner ORDER BY name ASC");
if ($project_query) {
    while($row = $project_query->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Get recent barcodes for quick reference
$recent_barcodes = [];
$barcode_query = $conn->query("SELECT DISTINCT sm.barcode_id, il.name as item_name, 
    SUM(CASE WHEN sm.movement_type = 'IN' THEN sm.quantity ELSE 0 END) - 
    COALESCE(SUM(CASE WHEN uh.id IS NOT NULL THEN uh.quantity_used ELSE 0 END), 0) as available_qty
    FROM stock_movement sm
    JOIN item_list il ON sm.item_id = il.id
    LEFT JOIN utilization_history uh ON sm.barcode_id = uh.barcode_id
    WHERE sm.movement_type = 'IN' AND sm.barcode_id IS NOT NULL AND sm.barcode_id != ''
    GROUP BY sm.barcode_id
    HAVING available_qty > 0
    ORDER BY sm.created_at DESC
    LIMIT 50");

if ($barcode_query) {
    while($row = $barcode_query->fetch_assoc()) {
        $recent_barcodes[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">📦 Utilize Stock / Issue Items</h3>
        </div>
        <div class="card-body">
            <!-- BARCODE SCAN SECTION -->
            <div class="form-section" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #dee2e6;">
                <h6 style="margin-bottom: 15px; color: #333;">Step 1: Scan Barcode or Select Item</h6>
                <div class="form-row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="barcode_scan">Scan Barcode:</label>
                            <input type="text" id="barcode_scan" class="form-control form-control-lg" 
                                   placeholder="Scan barcode here (will auto-focus)..." 
                                   style="font-size: 16px; padding: 12px; border: 2px solid #007bff;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="barcode_select">Or Select from Recent:</label>
                            <select id="barcode_select" class="form-control select2" data-placeholder="Select barcode..." style="width: 100%;">
                                <option value=""></option>
                                <?php foreach($recent_barcodes as $bc): ?>
                                    <option value="<?php echo htmlspecialchars($bc['barcode_id']); ?>" 
                                            data-item="<?php echo htmlspecialchars($bc['item_name']); ?>"
                                            data-qty="<?php echo $bc['available_qty']; ?>">
                                        <?php echo htmlspecialchars($bc['barcode_id']); ?> - <?php echo htmlspecialchars($bc['item_name']); ?> (<?php echo $bc['available_qty']; ?> avail)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UTILIZATION BATCH -->
            <div id="utilization-section" style="display: none;">
                <div class="form-section" style="background: #fff; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #dee2e6;">
                    <h6 style="margin-bottom: 15px; color: #333;">Step 2: Batch Utilization</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="project_select">Project (Optional):</label>
                                <select id="project_select" class="form-control select2" style="width: 100%;">
                                    <option value="">-- No Project --</option>
                                    <?php foreach($projects as $proj): ?>
                                        <option value="<?php echo $proj['id']; ?>"><?php echo htmlspecialchars($proj['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Project will apply to all items in this batch.</small>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="utilization-table" style="min-width: 900px;">
                            <thead style="background-color: rgb(0, 31, 63); color: white; font-size: 12px;">
                                <tr>
                                    <th style="width: 240px;">Item</th>
                                    <th style="width: 220px;">Barcode</th>
                                    <th style="width: 90px;" class="text-center">Available</th>
                                    <th style="width: 120px;" class="text-center">Qty Used</th>
                                    <th style="width: 180px;">Purpose</th>
                                    <th style="width: 200px;">Remarks</th>
                                    <th style="width: 80px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="utilization-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" id="clear-btn">Clear List</button>
                    <button type="button" class="btn btn-success" id="submit-btn"><i class="fas fa-check"></i> Record Utilization</button>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div id="empty-state" style="text-align: center; padding: 60px 20px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 80px; opacity: 0.3; margin-bottom: 20px; display: block;"></i>
                <h5>Ready to utilize stock</h5>
                <p>Scan a barcode or select an item from the list above to get started</p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var scannedMap = {};

    // Auto-focus on barcode scan field
    $('#barcode_scan').focus();

    // Enhance dropdowns with Select2
    $('#barcode_select, #project_select').select2({
        allowClear: true,
        width: 'resolve'
    });

    // Handle barcode scan input (Enter key)
    $('#barcode_scan').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            var scannedData = $(this).val().trim();
            if (scannedData) {
                // Barcodes are now simple 6-digit codes, use directly
                loadBarcodeInfo(scannedData);
            }
            return false;
        }
    });

    // Handle barcode dropdown selection
    $('#barcode_select').change(function() {
        var barcode = $(this).val();
        if (barcode) {
            loadBarcodeInfo(barcode);
        }
    });

    // Load barcode information
    function loadBarcodeInfo(barcode) {
        console.log('Loading barcode:', barcode);
        
        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=get_barcode_info',
            type: 'POST',
            data: { barcode_id: barcode },
            dataType: 'json',
            success: function(response) {
                console.log('Response:', response);
                
                if (response.status === 'success') {
                    addOrUpdateRow(response, barcode);

                    $('#empty-state').hide();
                    $('#utilization-section').show();

                    $('#barcode_scan').val('');
                    $('#barcode_scan').focus();
                    $('#barcode_select').val('').trigger('change');
                } else {
                    alert_toast('Barcode not found or no stock available: ' + (response.msg || ''), 'error');
                    $('#barcode_scan').val('');
                    $('#barcode_scan').focus();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                alert_toast('Error loading barcode information: ' + error, 'error');
            }
        });
    }

    // Add or update scanned item row
    function addOrUpdateRow(response, scannedBarcode) {
        var barcodeId = response.barcode_id || scannedBarcode;
        var barcodeLabel = response.barcode_code || scannedBarcode;
        var available = parseInt(response.available_qty, 10) || 0;

        if (scannedMap[barcodeId]) {
            var row = $('#utilization-tbody').find('tr[data-barcode-id="' + barcodeId + '"]');
            var qtyInput = row.find('.qty-used');
            var currentQty = parseInt(qtyInput.val(), 10) || 0;
            if (currentQty < available) {
                qtyInput.val(currentQty + 1).trigger('input');
            }
            return;
        }

        scannedMap[barcodeId] = true;

        var rowHtml = `
            <tr data-barcode-id="${barcodeId}" data-item-id="${response.item_id}" data-available="${available}">
                <td><strong>${response.item_name}</strong></td>
                <td>${barcodeLabel}</td>
                <td class="text-center">${available}</td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm qty-used" min="1" max="${available}" value="1" style="max-width: 110px; margin: 0 auto;">
                </td>
                <td><input type="text" class="form-control form-control-sm purpose" placeholder="Purpose"></td>
                <td><input type="text" class="form-control form-control-sm remarks" placeholder="Remarks"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;

        $('#utilization-tbody').append(rowHtml);
    }

    // Validate quantity per row
    $(document).on('input', '.qty-used', function() {
        var qty = parseInt($(this).val(), 10) || 0;
        var max = parseInt($(this).attr('max'), 10) || 0;
        if (qty > max) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        var row = $(this).closest('tr');
        var barcodeId = row.data('barcode-id');
        delete scannedMap[barcodeId];
        row.remove();

        if ($('#utilization-tbody tr').length === 0) {
            $('#utilization-section').hide();
            $('#empty-state').show();
        }
    });

    // Clear list
    $('#clear-btn').click(function() {
        scannedMap = {};
        $('#utilization-tbody').empty();
        $('#barcode_scan').val('').focus();
        $('#barcode_select').val('').trigger('change');
        $('#utilization-section').hide();
        $('#empty-state').show();
    });

    // Submit utilization
    $('#submit-btn').click(function() {
        var rows = $('#utilization-tbody tr');
        if (rows.length === 0) {
            alert_toast('Please scan at least one barcode', 'warning');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Recording...');

        var projectId = $('#project_select').val() || null;
        var rowData = [];

        rows.each(function() {
            var row = $(this);
            var qty = parseInt(row.find('.qty-used').val(), 10) || 0;
            var max = parseInt(row.attr('data-available'), 10) || 0;
            row.find('.qty-used').removeClass('is-invalid');

            if (qty <= 0 || qty > max) {
                row.find('.qty-used').addClass('is-invalid');
            } else {
                rowData.push({
                    barcode_id: row.data('barcode-id'),
                    item_id: row.data('item-id'),
                    quantity_used: qty,
                    purpose: row.find('.purpose').val(),
                    remarks: row.find('.remarks').val()
                });
            }
        });

        if (rowData.length !== rows.length) {
            alert_toast('Fix highlighted quantities before saving', 'warning');
            btn.prop('disabled', false);
            btn.html('<i class="fas fa-check"></i> Record Utilization');
            return;
        }

        var index = 0;
        function saveNext() {
            if (index >= rowData.length) {
                location.href = '<?php echo base_url ?>admin/?page=stock';
                return;
            }

            var payload = rowData[index];
            payload.project_id = projectId;

            $.ajax({
                url: '<?php echo base_url ?>classes/Master.php?f=save_utilization',
                type: 'POST',
                data: payload,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        index++;
                        saveNext();
                    } else {
                        alert_toast('Error: ' + response.msg, 'error');
                        btn.prop('disabled', false);
                        btn.html('<i class="fas fa-check"></i> Record Utilization');
                    }
                },
                error: function() {
                    alert_toast('Error saving utilization record', 'error');
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-check"></i> Record Utilization');
                }
            });
        }

        saveNext();
    });
});
</script>
