<?php
/**
 * Manage Stock In - Receiving goods against Purchase Orders
 * PO Items are listed with quantity ordered vs received
 */

// Ensure connection is available
if (!isset($conn)) {
    die('Database connection not available');
}

// Get all purchase orders with items
$pos = [];
$po_query = $conn->query("SELECT DISTINCT pol.id, pol.po_code, s.name as supplier_name 
    FROM purchase_order_list pol
    LEFT JOIN supplier_list s ON pol.supplier_id = s.id
    WHERE pol.po_code IS NOT NULL AND pol.po_code != ''
    ORDER BY pol.id DESC");

if ($po_query) {
    while($row = $po_query->fetch_assoc()) {
        // Check if all items for this PO have been received
        $po_id = $row['id'];
        
        // Get total ordered quantity for this PO
        $ordered_result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total_ordered FROM po_items WHERE po_id = $po_id");
        $ordered_data = $ordered_result->fetch_assoc();
        $total_ordered = (int)$ordered_data['total_ordered'];
        
        // Get total received quantity for this PO from stock_movement
        $received_result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total_received 
            FROM stock_movement 
            WHERE reference_id = $po_id AND reference_type = 'PO' AND movement_type = 'IN'");
        $received_data = $received_result->fetch_assoc();
        $total_received = (int)$received_data['total_received'];
        
        // Only include PO if not all items are received
        if ($total_ordered > $total_received) {
            $pos[] = $row;
        }
    }
}
?>

<div class="container-fluid">
    <!-- MODE TOGGLE -->
    <div class="form-section" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #dee2e6;">
        <h6 style="margin-bottom: 10px; color: #333;">Step 1: Receive Mode</h6>
        <div class="form-group d-flex align-items-center" style="gap: 20px;">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="receive_mode" id="mode_po" value="po" checked>
                <label class="form-check-label" for="mode_po">Against Purchase Order</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="receive_mode" id="mode_manual" value="manual">
                <label class="form-check-label" for="mode_manual">Manual (No PO)</label>
            </div>
        </div>
    </div>

    <!-- SELECT PO (shown when mode = po) -->
    <div id="po-section" class="form-section" style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #dee2e6;">
        <h6 style="margin-bottom: 15px; color: #333;">Step 2: Select Purchase Order</h6>
        <div class="form-group">
            <select id="po_select" class="form-control select2" data-placeholder="Search or select a PO" style="width: 100%;">
                <option value=""></option>
                <?php foreach($pos as $po): ?>
                    <option value="<?php echo $po['id']; ?>" data-supplier="<?php echo $po['supplier_name'] ?: 'N/A'; ?>">
                        <?php echo $po['po_code'] . ' - ' . ($po['supplier_name'] ?: 'N/A'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <!-- ITEMS TABLE (shown after PO selection) -->
    <div id="items-section" style="display: none;">
        <h6 style="margin-bottom: 15px; color: #333;">Step 3: Select Items to Receive</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm" id="po-items-table" style="min-width: 900px;">
                <colgroup>
                    <col width="20%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="10%">
                    <col width="20%">
                </colgroup>
                <thead style="background-color: rgb(0, 31, 63); color: white; font-size: 12px;">
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Ordered</th>
                        <th class="text-center">Remaining</th>
                        <th class="text-center">Receive Qty</th>
                        <th class="text-center">QR Mode</th>
                        <th class="text-center">Box Size</th>
                        <th class="text-center">Action</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="items-tbody">
                </tbody>
            </table>
        </div>
        
        <div class="form-group" style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn btn-sm btn-success" id="submit-btn"><i class="fas fa-check"></i> Submit</button>
            <button type="button" class="btn btn-sm btn-secondary" id="close-btn">Cancel</button>
        </div>
    </div>

    <!-- MANUAL RECEIVE SECTION (shown when mode = manual) -->
    <div id="manual-section" style="display: none;">
        <h6 style="margin-bottom: 15px; color: #333;">Step 2: Add Items</h6>
        <div class="form-row" style="margin-bottom:10px;">
            <div class="col-md-8">
                <select id="manual_item_select" class="form-control select2" data-placeholder="Search and select item" style="width: 100%;">
                    <option value=""></option>
                    <?php 
                    $item_list = $conn->query("SELECT id, name FROM item_list ORDER BY name ASC");
                    while($it = $item_list->fetch_assoc()): ?>
                        <option value="<?php echo $it['id']; ?>"><?php echo htmlspecialchars($it['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-sm btn-primary" id="add-manual-item"><i class="fas fa-plus"></i> Add Item</button>
            </div>
        </div>
        <div class="table-responsive" id="manual-table-wrapper" style="display: none;">
            <table class="table table-bordered table-hover table-sm" id="manual-items-table" style="min-width: 900px;">
                <colgroup>
                    <col style="width: 260px;">
                    <col style="width: 110px;">
                    <col style="width: 110px;">
                    <col style="width: 90px;">
                    <col style="width: 160px;">
                    <col style="width: 110px;">
                </colgroup>
                <thead style="background-color: rgb(0, 31, 63); color: white; font-size: 12px;">
                    <tr>
                        <th>Item Name</th>
                        <th class="text-center">Receive Qty</th>
                        <th class="text-center">QR Mode</th>
                        <th class="text-center">Box Size</th>
                        <th class="text-center">Action</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="manual-items-tbody"></tbody>
            </table>
        </div>
        <div class="form-group" style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn btn-sm btn-success" id="submit-manual-btn"><i class="fas fa-check"></i> Submit</button>
            <button type="button" class="btn btn-sm btn-secondary" id="close-btn2">Cancel</button>
        </div>
    </div>
</div>

<script>
<?php 
// Create JavaScript object with PO items
$po_items = [];
foreach($pos as $po) {
    $items = $conn->query("SELECT poi.id, poi.item_id, il.name,
        COALESCE(poi.quantity, 0) as quantity,
        COALESCE((
            SELECT SUM(sm.quantity)
            FROM stock_movement sm
            WHERE sm.reference_type = 'PO'
              AND sm.reference_id = poi.po_id
              AND sm.movement_type = 'IN'
              AND sm.item_id = poi.item_id
        ), 0) as received_qty
        FROM po_items poi
        JOIN item_list il ON poi.item_id = il.id
        WHERE poi.po_id = {$po['id']}")->fetch_all(MYSQLI_ASSOC);

    foreach($items as &$it){
        $it['remaining_qty'] = max(0, (int)$it['quantity'] - (int)$it['received_qty']);
    }
    unset($it);

    $po_items[$po['id']] = $items;
}
?>
var poItems = <?php echo json_encode($po_items); ?>;

function showQRPrintDialog(barcodeCode, itemName, qrCount, qrData, mode, totalQty, boxSize, shortCode) {
    var params = new URLSearchParams({
        barcode: barcodeCode,
        item: itemName,
        count: qrCount,
        mode: mode,
        qty: totalQty,
        box: boxSize,
        po: qrData.po_id || '',
        short_code: shortCode || ''
    });

    window.open(
        '<?php echo base_url ?>admin/receiving/print_qr.php?' + params.toString(),
        'QRPrint',
        'height=900,width=900,left=50,top=50'
    );
}

$(document).ready(function() {
    // Enhance PO dropdown with search
    $('#po_select').select2({
        placeholder: 'Search or select a PO',
        allowClear: true,
        width: 'resolve'
    });

    // Enhance manual item select
    $('#manual_item_select').select2({
        placeholder: 'Search and select item',
        allowClear: true,
        width: 'resolve'
    });

    // Mode toggle
    $('input[name="receive_mode"]').change(function(){
        var mode = $(this).val();
        if(mode === 'po'){
            $('#po-section').show();
            $('#items-section').hide();
            $('#manual-section').hide();
        }else{
            $('#po-section').hide();
            $('#items-section').hide();
            $('#manual-section').show();
        }
    });

    // When PO is selected, show items table
    $('#po_select').change(function() {
        var po_id = $(this).val();
        var itemsSection = $('#items-section');
        var itemsTable = $('#po-items-table tbody');
        
        itemsTable.html('');
        
        if (!po_id || !poItems[po_id] || poItems[po_id].length === 0) {
            itemsSection.hide();
            return;
        }
        
        // Populate table with items
        $.each(poItems[po_id], function(index, item) {
            var orderedQty = parseInt(item.quantity || 0, 10);
            var remainingQty = parseInt(item.remaining_qty || 0, 10);

            if (remainingQty <= 0) {
                return;
            }

            var row = `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td class="text-center">${orderedQty}</td>
                    <td class="text-center"><span class="badge badge-warning remaining-qty">${remainingQty}</span></td>
                    <td>
                        <input type="number" class="form-control form-control-sm received-qty" 
                               min="0" max="${remainingQty}" placeholder="Qty" 
                               data-item-id="${item.item_id}" data-item-name="${item.name}" data-po-id="${po_id}" data-remaining="${remainingQty}">
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group" style="width: 100%;">
                            <button type="button" class="btn btn-secondary qr-mode-btn active" data-mode="qty" title="One QR per item">Per Qty</button>
                            <button type="button" class="btn btn-outline-secondary qr-mode-btn" data-mode="box" title="One QR per box">Per Box</button>
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm box-size" 
                               min="1" placeholder="Items/Box" style="display: none;">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info generate-qr-btn" 
                                data-item-id="${item.item_id}" data-item-name="${item.name}" 
                                style="display: none; width: 100%;">
                            <i class="fas fa-qrcode"></i> Gen & Print
                        </button>
                        <span class="qr-status text-success" style="display: none; font-size: 11px;">✓ Done</span>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-remarks" 
                               placeholder="Notes...">
                    </td>
                </tr>
            `;
            itemsTable.append(row);
        });

        if(itemsTable.children().length === 0){
            itemsSection.hide();
            alert_toast('All items for this PO are already fully received.', 'info');
            return;
        }
        
        itemsSection.show();
    });
    
    // Show Generate QR button when quantity is entered
    $(document).on('input', '#po-items-table .received-qty', function() {
        var qty = parseInt($(this).val()) || 0;
        var row = $(this).closest('tr');
        var btn = row.find('.generate-qr-btn');
        var mode = row.find('.qr-mode-btn.active').data('mode');
        var boxSize = parseInt(row.find('.box-size').val()) || 0;
        var remaining = parseInt($(this).data('remaining')) || 0;

        if (qty > remaining) {
            qty = remaining;
            $(this).val(remaining);
            alert_toast('Receive quantity cannot exceed remaining quantity (' + remaining + ')', 'warning');
        }
        
        var canShow = false;
        if (mode === 'qty') {
            canShow = qty > 0;
        } else if (mode === 'box') {
            canShow = qty > 0 && boxSize > 0;
        }
        
        if (canShow) {
            btn.show();
        } else {
            btn.hide();
        }
    });

    // Handle QR mode toggle
    $(document).on('click', '#po-items-table .qr-mode-btn', function() {
        var btn = $(this);
        var row = btn.closest('tr');
        var mode = btn.data('mode');
        
        // Update active state
        row.find('.qr-mode-btn').removeClass('active btn-secondary').addClass('btn-outline-secondary');
        btn.addClass('active btn-secondary').removeClass('btn-outline-secondary');
        
        // Show/hide box size field
        if (mode === 'box') {
            row.find('.box-size').show();
        } else {
            row.find('.box-size').hide().val('');
        }
        
        // Check if button should be shown
        row.find('.received-qty').trigger('input');
    });

    // Update button visibility when box size changes
    $(document).on('input', '#po-items-table .box-size', function() {
        $(this).closest('tr').find('.received-qty').trigger('input');
    });

    // Generate and print QR code
    $(document).on('click', '.generate-qr-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var row = btn.closest('tr');
        var itemId = btn.data('item-id');
        var itemName = btn.data('item-name');
        var qty = parseInt(row.find('.received-qty').val()) || 0;
        var remarks = row.find('.item-remarks').val() || '';
        var poId = $('#po_select').val();
        var mode = row.find('.qr-mode-btn.active').data('mode');
        var boxSize = parseInt(row.find('.box-size').val()) || 1;
        var remaining = parseInt(row.find('.received-qty').data('remaining')) || 0;
        
        if (qty <= 0) {
            alert_toast('Please enter a quantity first', 'warning');
            return;
        }

        if (qty > remaining) {
            alert_toast('Receive quantity cannot exceed remaining quantity (' + remaining + ')', 'warning');
            return;
        }
        
        if (mode === 'box' && boxSize <= 0) {
            alert_toast('Please enter box size', 'warning');
            return;
        }
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        var timestamp = new Date().toISOString().replace(/[:\-T.]/g, '').substring(0, 14);
        var random = Math.random().toString(36).substring(2, 8).toUpperCase();
        var barcodeCode = 'PO-' + poId + '-IT-' + itemId + '-' + timestamp + '-' + random;
        
        // Calculate number of QR codes to generate
        var qrCount = qty;
        if (mode === 'box') {
            qrCount = Math.ceil(qty / boxSize);
        }
        
        var qrData = {
            barcode: barcodeCode,
            item_id: itemId,
            item_name: itemName,
            quantity: qty,
            po_id: poId,
            received_at: new Date().toLocaleString(),
            remarks: remarks,
            qr_mode: mode,
            box_size: boxSize
        };
        
        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=save_received_barcode',
            type: 'POST',
            data: {
                barcode_code: barcodeCode,
                item_id: itemId,
                quantity: qty,
                reference_type: 'PO',
                po_id: poId,
                remarks: remarks,
                qr_mode: mode,
                box_size: boxSize,
                qr_data: JSON.stringify(qrData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showQRPrintDialog(barcodeCode, itemName, qrCount, qrData, mode, qty, boxSize, response.short_code);
                    row.find('.qr-status').show();
                    btn.hide();
                } else {
                    alert_toast('Error: ' + response.msg, 'error');
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-qrcode"></i> Gen & Print');
                }
            },
            error: function(xhr) {
                alert_toast('Error saving barcode', 'error');
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-qrcode"></i> Gen & Print');
            }
        });
    });

    // Handle submit (only collects already-printed items)
    $('#submit-btn').click(function() {
        var po_id = $('#po_select').val();
        var doneCount = $('#po-items-table').find('.qr-status:visible').length;
        
        if (doneCount === 0) {
            alert_toast('Please generate and print QR codes for items first', 'warning');
            return;
        }
        
        alert_toast('All items have been recorded with barcodes!', 'success');
        setTimeout(function() {
            window.location.href = '<?php echo base_url ?>admin/?page=stock';
        }, 1500);
    });

    // Add manual item row
    $('#add-manual-item').click(function(){
        var itemId = $('#manual_item_select').val();
        var itemName = $('#manual_item_select option:selected').text();
        if(!itemId){
            alert_toast('Select an item to add', 'warning');
            return;
        }
        // Prevent duplicate rows for same item
        var exists = false;
        $('#manual-items-tbody tr').each(function(){
            var val = $(this).find('[data-item-id]').data('item-id');
            if(val == itemId){ exists = true; return false; }
        });
        if(exists){
            alert_toast('Item already added', 'info');
            return;
        }
        var row = `
            <tr>
                <td><strong>${itemName}</strong></td>
                <td>
                    <input type="number" class="form-control form-control-sm manual-received-qty" min="1" placeholder="Qty" value="1" 
                           data-item-id="${itemId}" data-item-name="${itemName}">
                </td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group" style="width: 100%;">
                        <button type="button" class="btn btn-secondary qr-mode-btn-manual active" data-mode="qty" title="One QR per item">Per Qty</button>
                        <button type="button" class="btn btn-outline-secondary qr-mode-btn-manual" data-mode="box" title="One QR per box">Per Box</button>
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm box-size-manual" 
                           min="1" placeholder="Items/Box" style="display: none;">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info generate-qr-btn-manual" 
                            data-item-id="${itemId}" data-item-name="${itemName}" 
                            style="width: 100%;">
                        <i class="fas fa-qrcode"></i> Gen & Print
                    </button>
                    <span class="qr-status text-success" style="display: none; font-size: 11px;">✓ Done</span>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm manual-item-remarks" placeholder="Notes...">
                </td>
            </tr>
        `;
        $('#manual-items-tbody').append(row);
        $('#manual-table-wrapper').show();
        $('#manual_item_select').val(null).trigger('change');
    });

    // Manual QR generation
    $(document).on('click', '.generate-qr-btn-manual', function(e) {
        e.preventDefault();
        var btn = $(this);
        var row = btn.closest('tr');
        var itemId = btn.data('item-id');
        var itemName = btn.data('item-name');
        var qty = parseInt(row.find('.manual-received-qty').val()) || 0;
        var remarks = row.find('.manual-item-remarks').val() || '';
        var mode = row.find('.qr-mode-btn-manual.active').data('mode');
        var boxSize = parseInt(row.find('.box-size-manual').val()) || 1;
        
        if (qty <= 0) {
            alert_toast('Please enter a quantity first', 'warning');
            return;
        }
        
        if (mode === 'box' && boxSize <= 0) {
            alert_toast('Please enter box size', 'warning');
            return;
        }
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        var timestamp = new Date().toISOString().replace(/[:\-T.]/g, '').substring(0, 14);
        var random = Math.random().toString(36).substring(2, 8).toUpperCase();
        var barcodeCode = 'MANUAL-IT-' + itemId + '-' + timestamp + '-' + random;
        
        // Calculate number of QR codes to generate
        var qrCount = qty;
        if (mode === 'box') {
            qrCount = Math.ceil(qty / boxSize);
        }
        
        var qrData = {
            barcode: barcodeCode,
            item_id: itemId,
            item_name: itemName,
            quantity: qty,
            received_at: new Date().toLocaleString(),
            remarks: remarks,
            qr_mode: mode,
            box_size: boxSize
        };
        
        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=save_received_barcode',
            type: 'POST',
            data: {
                barcode_code: barcodeCode,
                item_id: itemId,
                quantity: qty,
                reference_type: 'MANUAL',
                po_id: null,
                remarks: remarks,
                qr_mode: mode,
                box_size: boxSize,
                qr_data: JSON.stringify(qrData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showQRPrintDialog(barcodeCode, itemName, qrCount, qrData, mode, qty, boxSize, response.short_code);
                    row.find('.qr-status').show();
                    btn.hide();
                } else {
                    alert_toast('Error: ' + response.msg, 'error');
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-qrcode"></i> Gen & Print');
                }
            },
            error: function(xhr) {
                alert_toast('Error saving barcode', 'error');
                btn.prop('disabled', false);
                btn.html('<i class="fas fa-qrcode"></i> Gen & Print');
            }
        });
    });

    // Handle submit (Manual mode)
    $('#submit-manual-btn').click(function(){
        var doneCount = $('#manual-items-table').find('.qr-status:visible').length;
        
        if (doneCount === 0) {
            alert_toast('Please generate and print QR codes for items first', 'warning');
            return;
        }
        
        alert_toast('All items have been recorded with barcodes!', 'success');
        setTimeout(function() {
            window.location.href = '<?php echo base_url ?>admin/?page=stock';
        }, 1500);
    });

    // Cancel buttons should navigate back to the stock dashboard.
    $('#close-btn, #close-btn2').click(function() {
        window.location.href = '<?php echo base_url ?>admin/?page=stock';
    });

    // Handle manual QR mode toggle
    $(document).on('click', '#manual-items-table .qr-mode-btn-manual', function() {
        var btn = $(this);
        var row = btn.closest('tr');
        var mode = btn.data('mode');
        
        // Update active state
        row.find('.qr-mode-btn-manual').removeClass('active btn-secondary').addClass('btn-outline-secondary');
        btn.addClass('active btn-secondary').removeClass('btn-outline-secondary');
        
        // Show/hide box size field
        if (mode === 'box') {
            row.find('.box-size-manual').show();
        } else {
            row.find('.box-size-manual').hide().val('');
        }
    });

    // Update button visibility when manual box size changes
    $(document).on('input', '#manual-items-table .box-size-manual', function() {
        // Optional: can add validation logic here if needed
    });
});
</script>
