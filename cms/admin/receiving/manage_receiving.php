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

function showQRPrintDialog(barcodeCode, itemName, qty, qrData) {
    var printWindow = window.open('', 'QRPrint', 'height=900,width=900,left=50,top=50');
    printWindow.document.write('<html><head><title>Loading QR Codes...</title></head><body><h3>Generating QR codes, please wait...</h3></body></html>');
    
    var htmlContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print QR - ${itemName}</title>
            <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"><\/script>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: Arial, sans-serif; 
                    background: white;
                    padding: 10px;
                }
                .qr-container { 
                    display: grid;
                    grid-template-columns: repeat(5, 1fr);
                    gap: 10px;
                }
                .qr-label {
                    border: 2px solid #000;
                    padding: 8px;
                    text-align: center;
                    page-break-inside: avoid;
                    background: white;
                    min-width: 110px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                }
                .item-name { 
                    font-size: 8px; 
                    font-weight: bold; 
                    margin-bottom: 2px;
                    word-wrap: break-word;
                }
                .serial-num { 
                    font-size: 10px; 
                    font-weight: bold; 
                    color: #d9534f; 
                    margin: 2px 0;
                }
                .qr-code {
                    width: 80px;
                    height: 80px;
                    margin: 3px auto;
                }
                .barcode-code {
                    font-size: 6px;
                    font-family: 'Courier New', monospace;
                    margin-top: 2px;
                    word-break: break-all;
                }
                .date {
                    font-size: 6px;
                    color: #666;
                    margin-top: 1px;
                }
                @media print {
                    @page { size: A4; margin: 5mm; }
                }
            </style>
        </head>
        <body>
            <div class="qr-container" id="qrContainer"></div>
            <script>
                const barcodeCode = "${barcodeCode}";
                const itemName = "${itemName}";
                const qty = ${qty};
                const qrData = ${JSON.stringify(qrData)};
                
                let currentIndex = 1;
                const container = document.getElementById('qrContainer');
                
                function generateNext() {
                    if (currentIndex > qty) {
                        setTimeout(() => window.print(), 500);
                        return;
                    }
                    
                    const serialCode = barcodeCode + '-' + currentIndex;
                    const qrContent = JSON.stringify({
                        barcode: serialCode,
                        serial: currentIndex + '/' + qty,
                        item_id: qrData.item_id,
                        item_name: qrData.item_name,
                        po_id: qrData.po_id,
                        timestamp: new Date().toLocaleString()
                    });
                    
                    const labelDiv = document.createElement('div');
                    labelDiv.className = 'qr-label';
                    labelDiv.innerHTML = '<div class="item-name">' + itemName + '</div>' +
                                       '<div class="serial-num">S/N: ' + currentIndex + '/' + qty + '</div>' +
                                       '<div class="qr-code" id="qr' + currentIndex + '"></div>' +
                                       '<div class="barcode-code">' + serialCode + '</div>' +
                                       '<div class="date">' + new Date().toLocaleDateString() + '</div>';
                    
                    container.appendChild(labelDiv);
                    
                    new QRCode(document.getElementById('qr' + currentIndex), {
                        text: qrContent,
                        width: 80,
                        height: 80
                    });
                    
                    currentIndex++;
                    setTimeout(generateNext, 50);
                }
                
                setTimeout(generateNext, 100);
            <\/script>
        </body>
        </html>
    `;
    
    printWindow.document.open();
    printWindow.document.write(htmlContent);
    printWindow.document.close();
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
            var row = `
                <tr>
                    <td style="width: 300px;"><strong>${item.name}</strong></td>
                    <td class="text-center">${item.quantity}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm received-qty" 
                               min="0" max="${item.quantity}" placeholder="Qty" 
                               data-item-id="${item.item_id}" data-item-name="${item.name}" data-po-id="${po_id}">
                    </td>
                    <td style="min-width: 120px;">
                        <input type="text" class="form-control form-control-sm item-remarks" 
                               placeholder="Notes...">
                    </td>
                    <td style="width: 160px;">
                        <button type="button" class="btn btn-sm btn-info generate-qr-btn" 
                                data-item-id="${item.item_id}" data-item-name="${item.name}" 
                                style="display: none; width: 100%;">
                            <i class="fas fa-qrcode"></i> Gen & Print
                        </button>
                        <span class="qr-status text-success" style="display: none; font-size: 11px;">✓ Done</span>
                    </td>
                </tr>
            `;
            itemsTable.append(row);
        });
        
        itemsSection.show();
    });
    
    // Show Generate QR button when quantity is entered
    $(document).on('input', '#po-items-table .received-qty', function() {
        var qty = parseInt($(this).val()) || 0;
        var row = $(this).closest('tr');
        var btn = row.find('.generate-qr-btn');
        
        if (qty > 0) {
            btn.show();
        } else {
            btn.hide();
        }
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
        
        if (qty <= 0) {
            alert_toast('Please enter a quantity first', 'warning');
            return;
        }
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        var timestamp = new Date().toISOString().replace(/[:\-T.]/g, '').substring(0, 14);
        var random = Math.random().toString(36).substring(2, 8).toUpperCase();
        var barcodeCode = 'PO-' + poId + '-IT-' + itemId + '-' + timestamp + '-' + random;
        
        var qrData = {
            barcode: barcodeCode,
            item_id: itemId,
            item_name: itemName,
            quantity: qty,
            po_id: poId,
            received_at: new Date().toLocaleString(),
            remarks: remarks
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
                qr_data: JSON.stringify(qrData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showQRPrintDialog(barcodeCode, itemName, qty, qrData);
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
            window.location.href = '<?php echo base_url ?>admin/?page=receiving';
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
                <td style="width: 300px;"><strong>${itemName}</strong></td>
                <td>
                    <input type="number" class="form-control form-control-sm manual-received-qty" min="1" placeholder="Qty" value="1" 
                           data-item-id="${itemId}" data-item-name="${itemName}">
                </td>
                <td style="min-width: 120px;">
                    <input type="text" class="form-control form-control-sm manual-item-remarks" placeholder="Notes...">
                </td>
                <td style="width: 160px;">
                    <button type="button" class="btn btn-sm btn-info generate-qr-btn-manual" 
                            data-item-id="${itemId}" data-item-name="${itemName}" 
                            style="width: 100%;">
                        <i class="fas fa-qrcode"></i> Gen & Print
                    </button>
                    <span class="qr-status text-success" style="display: none; font-size: 11px;">✓ Done</span>
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
        
        if (qty <= 0) {
            alert_toast('Please enter a quantity first', 'warning');
            return;
        }
        
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        var timestamp = new Date().toISOString().replace(/[:\-T.]/g, '').substring(0, 14);
        var random = Math.random().toString(36).substring(2, 8).toUpperCase();
        var barcodeCode = 'MANUAL-IT-' + itemId + '-' + timestamp + '-' + random;
        
        var qrData = {
            barcode: barcodeCode,
            item_id: itemId,
            item_name: itemName,
            quantity: qty,
            received_at: new Date().toLocaleString(),
            remarks: remarks
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
                qr_data: JSON.stringify(qrData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showQRPrintDialog(barcodeCode, itemName, qty, qrData);
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
            window.location.href = '<?php echo base_url ?>admin/?page=receiving';
        }, 1500);
    });
});
</script>
