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
            <table class="table table-bordered table-hover table-sm" id="po-items-table" style="min-width: 700px;">
                <thead style="background-color: rgb(0, 31, 63); color: white; font-size: 12px;">
                    <tr>
                        <th style="width: 50px;">Select</th>
                        <th style="min-width: 250px;">Item Name</th>
                        <th style="width: 80px;" class="text-center">Ordered</th>
                        <th style="width: 100px;" class="text-center">Receive Qty</th>
                        <th style="min-width: 150px;">Remarks</th>
                    </tr>
                </thead>
                <tbody id="items-tbody">
                </tbody>
            </table>
        </div>
        
        <div class="form-group" style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn btn-sm btn-success" id="submit-btn"><i class="fas fa-check"></i> Submit</button>
            <button type="button" class="btn btn-sm btn-secondary" id="close-btn" data-dismiss="modal">Close</button>
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
            <table class="table table-bordered table-hover table-sm" id="manual-items-table" style="min-width: 700px;">
                <thead style="background-color: rgb(0, 31, 63); color: white; font-size: 12px;">
                    <tr>
                        <th style="width: 50px;">Select</th>
                        <th style="min-width: 250px;">Item Name</th>
                        <th style="width: 100px;" class="text-center">Receive Qty</th>
                        <th style="min-width: 150px;">Remarks</th>
                    </tr>
                </thead>
                <tbody id="manual-items-tbody"></tbody>
            </table>
        </div>
        <div class="form-group" style="margin-top: 20px; text-align: right;">
            <button type="button" class="btn btn-sm btn-success" id="submit-manual-btn"><i class="fas fa-check"></i> Submit</button>
            <button type="button" class="btn btn-sm btn-secondary" id="close-btn2" data-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<script>
<?php 
// Create JavaScript object with PO items
$po_items = [];
foreach($pos as $po) {
    $items = $conn->query("SELECT poi.id, poi.item_id, il.name, poi.quantity 
        FROM po_items poi
        JOIN item_list il ON poi.item_id = il.id
        WHERE poi.po_id = {$po['id']}")->fetch_all(MYSQLI_ASSOC);
    $po_items[$po['id']] = $items;
}
?>
var poItems = <?php echo json_encode($po_items); ?>;

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
            var row = `
                <tr>
                    <td>
                        <input type="checkbox" class="item-checkbox" 
                               value="${item.item_id}" 
                               data-item-name="${item.name}"
                               data-quantity="${item.quantity}">
                    </td>
                    <td><strong>${item.name}</strong></td>
                    <td class="text-center">${item.quantity}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm received-qty" 
                               min="0" max="${item.quantity}" placeholder="Qty">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-remarks" 
                               placeholder="Notes...">
                    </td>
                </tr>
            `;
            itemsTable.append(row);
        });
        
        itemsSection.show();
    });
    
    // Handle submit (PO mode) - AJAX request
    $('#submit-btn').click(function() {
        var po_id = $('#po_select').val();
        
        if (!po_id) {
            alert_toast('Please select a PO', 'warning');
            return;
        }
        
        // Collect selected items
        var selectedItems = [];
        $('#po-items-table tbody').find('input[type="checkbox"]:checked').each(function() {
            var checkbox = $(this);
            var row = checkbox.closest('tr');
            var receivedQty = parseInt(row.find('.received-qty').val()) || 0;
            var remarks = row.find('.item-remarks').val() || '';
            
            if (receivedQty > 0) {
                selectedItems.push({
                    item_id: checkbox.val(),
                    received_qty: receivedQty,
                    remarks: remarks
                });
            }
        });
        
        if (selectedItems.length === 0) {
            alert_toast('Please select at least one item and enter received quantity', 'warning');
            return;
        }
        
        // Disable submit button during submission
        var submitBtn = $(this);
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'receive_stock_batch');
        formData.append('po_id', po_id);
        
        $.each(selectedItems, function(index, item) {
            formData.append('item_id[]', item.item_id);
            formData.append('received_qty[]', item.received_qty);
            formData.append('remarks[]', item.remarks);
        });
        
        // Submit via AJAX
        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=receive_stock_batch',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                    if (response.status === 'success') {
                    // Redirect immediately to main receiving page
                    window.location.href = '<?php echo base_url ?>admin/?page=receiving';
                } else {
                        alert_toast('Error: ' + (response.msg || 'Unknown error'), 'error');
                    
                    // Re-enable submit button on error
                    submitBtn.prop('disabled', false);
                    submitBtn.html('<i class="fas fa-check"></i> Submit');
                }
            },
            error: function(xhr, status, error) {
                alert_toast('Error: ' + (xhr.responseText || error), 'error');
                
                // Re-enable submit button
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="fas fa-check"></i> Submit');
            }
        });
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
            var val = $(this).find('.manual-item-checkbox').val();
            if(val == itemId){ exists = true; return false; }
        });
        if(exists){
            alert_toast('Item already added', 'info');
            return;
        }
        var row = `
            <tr>
                <td>
                    <input type="checkbox" class="manual-item-checkbox" value="${itemId}" checked>
                </td>
                <td><strong>${itemName}</strong></td>
                <td>
                    <input type="number" class="form-control form-control-sm manual-received-qty" min="1" placeholder="Qty" value="1">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm manual-item-remarks" placeholder="Notes...">
                </td>
            </tr>
        `;
        $('#manual-items-tbody').append(row);
        // Show table and clear selection
        $('#manual-table-wrapper').show();
        $('#manual_item_select').val(null).trigger('change');
    });

    // Handle submit (Manual mode) - AJAX request
    $('#submit-manual-btn').click(function(){
        // Collect selected items from manual table
        var selectedItems = [];
        $('#manual-items-table tbody').find('input[type="checkbox"]:checked').each(function(){
            var checkbox = $(this);
            var row = checkbox.closest('tr');
            var receivedQty = parseInt(row.find('.manual-received-qty').val()) || 0;
            var remarks = row.find('.manual-item-remarks').val() || '';
            if(receivedQty > 0){
                selectedItems.push({
                    item_id: checkbox.val(),
                    received_qty: receivedQty,
                    remarks: remarks
                });
            }
        });
        if(selectedItems.length === 0){
            alert_toast('Add and select at least one item with quantity', 'warning');
            return;
        }
        var submitBtn = $(this);
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        var formData = new FormData();
        formData.append('action', 'receive_stock_manual');
        $.each(selectedItems, function(index, item){
            formData.append('item_id[]', item.item_id);
            formData.append('received_qty[]', item.received_qty);
            formData.append('remarks[]', item.remarks);
        });
        $.ajax({
            url: '<?php echo base_url ?>classes/Master.php?f=receive_stock_manual',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response){
                if(response.status === 'success'){
                    window.location.href = '<?php echo base_url ?>admin/?page=receiving';
                }else{
                    alert_toast('Error: ' + (response.msg || 'Unknown error'), 'error');
                    submitBtn.prop('disabled', false);
                    submitBtn.html('<i class="fas fa-check"></i> Submit');
                }
            },
            error: function(xhr, status, error){
                alert_toast('Error: ' + (xhr.responseText || error), 'error');
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="fas fa-check"></i> Submit');
            }
        });
    });
    
    // Toggle received qty input when checkbox changes
    $(document).on('change', '.item-checkbox', function() {
        var row = $(this).closest('tr');
        var receivedQtyInput = row.find('.received-qty');
        
        if ($(this).is(':checked')) {
            receivedQtyInput.focus();
            var maxQty = $(this).data('quantity');
            receivedQtyInput.val(maxQty); // Auto-fill with ordered quantity
        } else {
            receivedQtyInput.val('');
        }
    });
});
</script>
