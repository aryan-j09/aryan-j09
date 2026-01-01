<?php
// Follow pattern used in other admin pages: do not re-require config here.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
global $conn;
$project_id = intval($_GET['id'] ?? 0);
if($project_id <= 0){
    echo "<div class='alert alert-danger'>Invalid project id.</div>";
    return;
}

// Fetch project details
$proj_qry = $conn->query("SELECT pp.*, c.company_name FROM project_planner pp LEFT JOIN clients c ON pp.client_id = c.id WHERE pp.id = $project_id LIMIT 1");
$project = null;
if($proj_qry && $proj_qry->num_rows > 0){
    $project = $proj_qry->fetch_assoc();
}

// Fetch assigned Supplier POs
$supplier_pos = [];
$po_qry = $conn->query("SELECT pol.id, pol.po_code FROM `project_po_list` ppl
                         JOIN `purchase_order_list` pol ON ppl.po_id = pol.id
                         WHERE ppl.project_id = '{$project_id}'
                         ORDER BY pol.po_code ASC");
if($po_qry && $po_qry->num_rows > 0){
    $supplier_pos = $po_qry->fetch_all(MYSQLI_ASSOC);
}

// Fetch assigned PO Factory Details
$po_details = [];
$po_detail_qry = $conn->query("SELECT po.id, po.po_code FROM `project_po_detail_list` ppdl
                               JOIN `purchase_orders` po ON ppdl.po_detail_id = po.id
                               WHERE ppdl.project_id = '{$project_id}'
                               ORDER BY po.po_code ASC");
if($po_detail_qry && $po_detail_qry->num_rows > 0){
    $po_details = $po_detail_qry->fetch_all(MYSQLI_ASSOC);
}

// Note: project_sheets table is created once during initial setup via database migration.
// No need to check/create it on every page load for performance.
?>
<div class="row">
    <div class="col-md-12">
        <?php if($project): ?>
        <div class="card card-outline card-primary collapsed-card">
            <div class="card-header ms-card-header-toggle" role="button" aria-expanded="false" aria-label="Toggle Project Details">
                <h3 class="card-title">Project Details</h3>
                <div class="card-tools d-flex align-items-center" style="gap:6px;">
                    <a href="<?php echo base_url; ?>admin/?page=project_planner2/manage_project&id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo base_url; ?>admin/?page=project_planner2" class="btn btn-sm btn-secondary">Back</a>
                    <button type="button" id="btn_delete_project" class="btn btn-sm btn-danger">Delete</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Project Name:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($project['project_name'] ?? $project['name'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-4">Client:</dt>
                            <dd class="col-sm-8"><?php echo $project['company_name'] ?? 'N/A'; ?></dd>
                            
                            <dt class="col-sm-4">PO Details:</dt>
                            <dd class="col-sm-8">
                                <?php 
                                    if (!empty($po_details)) {
                                        foreach ($po_details as $pod) {
                                            $pod_id = (int)($pod['id'] ?? 0);
                                            $pod_code = htmlspecialchars($pod['po_code'] ?? '');
                                            if($pod_id > 0 && $pod_code !== ''){
                                                echo '<a href="'.base_url.'admin/?page=po_details/view_po_details&id='.$pod_id.'" target="_blank" class="badge badge-success mr-1">'.$pod_code.'</a>';
                                            } else {
                                                echo '<span class="badge badge-success mr-1">'.$pod_code.'</span>';
                                            }
                                        }
                                    } else {
                                        echo '<span class="text-muted">N/A</span>';
                                    }
                                ?>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <?php 
                                $status = $project['status'] ?? 1;
                                // Handle numeric status (1 = active, 0 = inactive)
                                $status_text = ($status == 1 || $status == 'active') ? 'Active' : 'Inactive';
                                $badge = ($status == 1 || $status == 'active') ? 'success' : 'warning';
                                echo "<span class='badge badge-$badge'>$status_text</span>";
                                ?>
                            </dd>
                            
                            <dt class="col-sm-4">Created:</dt>
                            <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($project['date_added'] ?? 'now')); ?></dd>
                            
                            <dt class="col-sm-4">POs:</dt>
                            <dd class="col-sm-8">
                                <?php 
                                    $has_pos = false;
                                    if (!empty($supplier_pos)) {
                                        $has_pos = true;
                                        foreach ($supplier_pos as $spo) {
                                            $po_id = (int)($spo['id'] ?? 0);
                                            $po_code = htmlspecialchars($spo['po_code'] ?? '');
                                            if($po_id > 0 && $po_code !== ''){
                                                echo '<a href="'.base_url.'admin/?page=purchase_order/view_po&id='.$po_id.'" target="_blank" class="badge badge-info mr-1">'.$po_code.'</a>';
                                            } else {
                                                echo '<span class="badge badge-info mr-1">'.$po_code.'</span>';
                                            }
                                        }
                                    }
                                    if (!$has_pos) {
                                        echo '<span class="text-muted">N/A</span>';
                                    }
                                ?>
                            </dd>
                        </dl>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <dl class="row">
                            <dt class="col-sm-2">Description:</dt>
                            <dd class="col-sm-10 project-description"><?php 
                                $desc = $project['description'] ?? 'N/A';
                                if($desc !== 'N/A'){
                                    // Collapse excessive whitespace (3+ newlines to 2)
                                    $desc = preg_replace('/\n{3,}/', "\n\n", $desc);
                                    // Trim each line to remove leading/trailing spaces
                                    $desc = implode("\n", array_map('trim', explode("\n", $desc)));
                                    // Only apply nl2br if no HTML tags detected
                                    if(strip_tags($desc) === $desc){
                                        echo nl2br($desc);
                                    } else {
                                        echo $desc;
                                    }
                                } else {
                                    echo 'N/A';
                                }
                            ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">Project not found.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header">
        <div class="ms-header d-flex justify-content-between w-100">
            <div class="card-tools ms-toolbar">
                <select id="ms_sheet_selector" class="form-control form-control-sm ms-sheet-selector">
                    <option value="">Loading sheets...</option>
                </select>
                <button id="ms_new_sheet" class="btn btn-sm btn-info" title="New Sheet">➕ New</button>
                <button id="ms_rename_sheet" class="btn btn-sm btn-primary" title="Rename Sheet">✏️ Rename</button>
                <button id="ms_delete_sheet" class="btn btn-sm btn-warning" title="Delete Sheet">🗑️ Delete</button>
                <div id="ms_format_toolbar" class="ms-format-toolbar">
                    <button id="fmt_bold" class="btn btn-sm btn-light" title="Bold"><strong>B</strong></button>
                    <button id="fmt_italic" class="btn btn-sm btn-light" title="Italic"><em>I</em></button>
                    <button id="fmt_underline" class="btn btn-sm btn-light" title="Underline"><u>U</u></button>
                </div>
                <input type="text" id="ms_search" class="form-control form-control-sm ms-search" placeholder="Search...">
                <button id="ms_undo" class="btn btn-sm btn-warning" title="Undo (Ctrl+Z)">↶ Undo</button>
                <button id="ms_redo" class="btn btn-sm btn-warning" title="Redo (Ctrl+Y)">↷ Redo</button>
                <button id="ms_add_row" class="btn btn-sm btn-success">Add Row</button>
                <button id="ms_add_col" class="btn btn-sm btn-success">Add Col</button>
                <button id="ms_create_tasks" class="btn btn-sm btn-info">Create Tasks</button>
                <button id="ms_export_csv" class="btn btn-sm btn-secondary">Export CSV</button>
                <button id="ms_save_sheet" class="btn btn-sm btn-primary">Save</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto; position: relative;">
            <table id="mini_sheet" class="table table-bordered table-sm" style="min-width:800px;">
                <thead></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for adding column -->
<div class="modal fade" id="addColModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Column</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Column Name:</label>
                    <input type="text" id="colName" class="form-control" placeholder="e.g., Deliverables">
                </div>
                <div class="form-group">
                    <label>Column Type:</label>
                    <select id="colType" class="form-control">
                        <option value="text">📝 Text/Number</option>
                        <option value="date">📅 Date</option>
                        <option value="timeline">⏱️ Timeline (days)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAddCol">Add Column</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for creating tasks from spreadsheet -->
<div class="modal fade" id="createTasksModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Tasks from Spreadsheet</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Rows to Create Tasks:</label>
                    <div id="taskRowsList" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                        <!-- Populated dynamically -->
                    </div>
                </div>
                <div class="form-group">
                    <label for="taskAssignTo">Assign To:</label>
                    <select id="taskAssignTo" class="form-control">
                        <option value="">-- Select User --</option>
                    </select>
                </div>
                <div class="alert alert-info">
                    <strong>ℹ️ Mapping:</strong>
                    <ul class="mb-0" style="margin-left: 20px; margin-top: 8px;">
                        <li><strong>Activity</strong> column → Task Title & Description</li>
                        <li><strong>End Date</strong> column → Task Due Date</li>
                        <li>All tasks will be created with Status: <code>Pending</code> and Priority: <code>Medium</code></li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmCreateTasks">Create Tasks</button>
            </div>
        </div>
    </div>
</div>

<style>
    .row-color-yellow { background: #fff3cd; }
    .row-color-green { background: #d4edda; }
    .row-color-orange { background: #ffe5b4; }
    .cell-editable { min-width:120px; }
    
    th[draggable="true"] { cursor: move; }
    tr[draggable="true"] { cursor: move; }
    .drag-over { opacity: 0.5; background-color: #e3f2fd !important; }
    .dragging { opacity: 0.3; }
    
    /* Frozen column styling */
    #mini_sheet thead th:nth-child(1),
    #mini_sheet tbody td:nth-child(1) {
        position: sticky;
        left: 0;
        background-color: #f8f9fa;
        z-index: 10;
        border-right: 2px solid #dee2e6;
    }
    
    #mini_sheet thead th:nth-child(1) {
        z-index: 11;
    }
    
    /* Column resize handle */
    .col-resize-handle {
        position: absolute;
        right: -5px;
        top: 0;
        width: 10px;
        height: 100%;
        cursor: col-resize;
        user-select: none;
    }
    
    th { position: relative; }
    
    /* Hidden rows for search */
    tr.search-hidden { display: none !important; }
    
    /* Unsaved indicator */
    .unsaved-indicator {
        display: inline-block;
        margin-left: 10px;
        color: #dc3545;
        font-weight: bold;
    }

    /* Toolbar spacing */
    .ms-header { gap: 8px; }
    .ms-toolbar {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-start;
        gap: 4px;
        width: 100%;
        float: none !important;
        overflow-x: auto;
    }
    .ms-sheet-selector { min-width: 130px; max-width: 150px; }
    .ms-format-toolbar { display: flex; align-items: center; gap: 4px; margin-right: 2px; }
    .ms-search { min-width: 130px; max-width: 150px; }
    .ms-toolbar .btn-sm { min-height: 30px; padding: 4px 8px; }
    .ms-card-header-toggle {
        cursor: pointer;
        user-select: none;
        -webkit-user-select: none;
        -ms-user-select: none;
    }
    .ms-card-header-toggle .card-title { margin-bottom: 0; }
    
    /* Description formatting */
    .project-description {
        white-space: pre-line;
        line-height: 1.6;
        max-height: 400px;
        overflow-y: auto;
    }
</style>

<script>
;(function(){
    var base = typeof _base_url_ !== 'undefined' ? _base_url_ : '<?php echo base_url ?>';
    var projectId = <?php echo $project_id; ?>;
    var sheetName = 'Sheet1';
    var activeEditable = null; // currently focused cell
    
    // History for undo/redo
    var history = [];
    var historyIndex = -1;
    var hasUnsavedChanges = false;
    
    function saveToHistory(){
        // Remove any history after current index (when user makes new change after undo)
        history = history.slice(0, historyIndex + 1);
        // Save current state
        history.push(JSON.stringify(getSheetFromDOM()));
        historyIndex++;
        // Limit history to 50 items
        if(history.length > 50){
            history.shift();
            historyIndex--;
        }
        updateUndoRedoButtons();
        setUnsavedChanges(true);
    }
    
    function updateUndoRedoButtons(){
        $('#ms_undo').prop('disabled', historyIndex <= 0);
        $('#ms_redo').prop('disabled', historyIndex >= history.length - 1);
    }
    
    function setUnsavedChanges(unsaved){
        hasUnsavedChanges = unsaved;
        if(unsaved){
            if(!$('.unsaved-indicator').length){
                $('.card-title').after('<span class="unsaved-indicator">● Unsaved</span>');
            }
        } else {
            $('.unsaved-indicator').remove();
        }
    }

    function loadSheetsList(){
        $.getJSON(base + 'classes/Master.php?f=get_project_sheets&project_id='+projectId, function(resp){
            if(resp.status === 'success' && resp.sheets){
                var selector = $('#ms_sheet_selector');
                selector.empty();
                resp.sheets.forEach(function(s){
                    selector.append('<option value="'+s.name+'">'+s.name+'</option>');
                });
                // Set to first sheet or Sheet1
                var firstSheet = resp.sheets.length > 0 ? resp.sheets[0].name : 'Sheet1';
                selector.val(firstSheet);
                sheetName = firstSheet;
                loadSheet();
            }
        });
    }

    function buildEmptySheet(){
        return {
            cols: [
                {id:'activity', title:'Activity'},
                {id:'timeline', title:'Timeline (days)'},
                {id:'start_date', title:'Start date', type:'date'},
                {id:'end_date', title:'End date', type:'date'},
                {id:'status', title:'Status / Responsibility'}
            ],
            rows: []
        };
    }

    function renderSheet(sheet){
        var thead = $('#mini_sheet thead');
        var tbody = $('#mini_sheet tbody');
        thead.empty(); tbody.empty();
        var tr = $('<tr/>');
        tr.append('<th style="width:30px"></th>');
        sheet.cols.forEach(function(c, ci){
            var colType = c.type || 'text';
            var th = $('<th draggable="true" data-col-type="'+colType+'" style="min-width:120px;"><span contenteditable class="col-title">'+c.title+'</span> <button class="btn btn-xs btn-danger ms_del_col" data-col-index="'+ci+'" style="padding:0 4px; font-size:10px;">×</button></th>');
            tr.append(th);
        });
        thead.append(tr);
        addResizeHandle();

        sheet.rows.forEach(function(r, ri){
            var row = $('<tr draggable="true" data-row-index="'+ri+'"></tr>');
            var ctrl = $('<td style="padding: 5px; display: flex; gap: 5px; align-items: center;"><button class="btn btn-xs btn-success ms_add_row_inline" style="margin: 0;" title="Add row">+</button><button class="btn btn-xs btn-danger ms_del_row_inline" style="margin: 0;" title="Delete row">🗑️</button><div style="position: relative; width: 28px; height: 28px;"><input type="color" class="row-color-picker" value="#ffffff" style="opacity: 0; width: 100%; height: 100%; cursor: pointer; position: absolute; top: 0; left: 0;"><div class="color-wheel-bg" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ddd; background-color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; pointer-events: none;">🎨</div></div></td>');
            row.append(ctrl);
            // Set background color from saved data
            if(r.rowColor){
                row.css('background-color', r.rowColor);
                ctrl.find('.row-color-picker').val(r.rowColor);
                ctrl.find('.color-wheel-bg').css('background-color', r.rowColor);
            }
            r.cells.forEach(function(cell, ci){
                var colType = sheet.cols[ci].type || 'text';
                var td = $('<td contenteditable class="cell-editable" data-col-type="'+colType+'"></td>');
                // Check if cell is an object with content and style
                if(cell && typeof cell === 'object' && cell.content !== undefined){
                    td.html(cell.content);
                    if(cell.style) td.attr('style', cell.style);
                } else if(cell && /<\w+|<span|<b>|<i>|<u>/.test(cell)){
                    td.html(cell);
                } else {
                    td.text(cell);
                }
                row.append(td);
            });
            tbody.append(row);
        });
        // If no rows, show one empty row
        if(sheet.rows.length === 0){
            addRow();
        }
    }

    function addRow(data){
        var cols = $('#mini_sheet thead th').length - 1;
        var ri = $('#mini_sheet tbody tr').length;
        var row = $('<tr draggable="true" data-row-index="'+ri+'"></tr>');
        var ctrl = $('<td style="padding: 5px; display: flex; gap: 5px; align-items: center;"><button class="btn btn-xs btn-success ms_add_row_inline" style="margin: 0;" title="Add row">+</button><button class="btn btn-xs btn-danger ms_del_row_inline" style="margin: 0;" title="Delete row">🗑️</button><div style="position: relative; width: 28px; height: 28px;"><input type="color" class="row-color-picker" value="#ffffff" style="opacity: 0; width: 100%; height: 100%; cursor: pointer; position: absolute; top: 0; left: 0;"><div class="color-wheel-bg" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ddd; background-color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; pointer-events: none;">🎨</div></div></td>');
        row.append(ctrl);
        for(var i=0;i<cols;i++){
            var colType = $('#mini_sheet thead th').eq(i+1).attr('data-col-type') || 'text';
            var td = $('<td contenteditable class="cell-editable" data-col-type="'+colType+'"></td>');
            td.text((data && data[i])? data[i] : '');
            row.append(td);
        }
        $('#mini_sheet tbody').append(row);
    }

    function addCol(){
        // Show modal instead of prompt
        $('#colName').val('');
        $('#colType').val('text');
        $('#addColModal').modal('show');
        $('#colName').focus();
    }
    
    function addColFromModal(){
        var colName = $('#colName').val().trim();
        var colType = $('#colType').val();
        
        if(!colName){
            Swal.fire({
                icon: 'warning',
                title: 'Required',
                text: 'Please enter a column name'
            });
            return;
        }
        
        // add header cell with editable title
        var newColIndex = $('#mini_sheet thead th').length - 1;
        var th = $('<th data-col-type="'+colType+'"><span contenteditable class="col-title">'+colName+'</span> <button class="btn btn-xs btn-danger ms_del_col" data-col-index="'+newColIndex+'" style="padding:0 4px; font-size:10px;">×</button></th>');
        $('#mini_sheet thead tr').append(th);
        addResizeHandle();
        
        // add cell to each row with proper type
        $('#mini_sheet tbody tr').each(function(){
            var td = $('<td contenteditable class="cell-editable" data-col-type="'+colType+'"></td>');
            $(this).append(td);
        });
        
        $('#addColModal').modal('hide');
        saveToHistory();
    }

    function exportCSV(){
        var headers = [];
        $('#mini_sheet thead th').each(function(i, el){
            if(i > 0){
                headers.push($(el).find('.col-title').text().trim());
            }
        });

        var lines = [];
        lines.push(headers.join(','));

        $('#mini_sheet tbody tr').each(function(){
            var vals = [];
            $(this).find('td').each(function(i, td){
                if(i === 0) return; // skip control column
                var cellText = $(td).text().replace(/\r?\n|\r/g, ' ').trim();
                vals.push('"' + cellText.replace(/"/g, '""') + '"');
            });
            lines.push(vals.join(','));
        });

        var csv = '\uFEFF' + lines.join('\r\n'); // BOM + CRLF for Excel compatibility
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'sheet_' + projectId + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function getSheetFromDOM(){
        var cols = [];
        $('#mini_sheet thead th').each(function(i,el){ 
            if(i>0) {
                // Extract only the column title from the span
                var titleText = $(el).find('.col-title').text().trim();
                var colType = $(el).attr('data-col-type') || 'text';
                cols.push({id:'c'+i, title:titleText, type:colType});
            }
        });
        var rows = [];
        $('#mini_sheet tbody tr').each(function(){
            var r = { cells: [], rowColor: '' };
            // Save inner HTML AND inline styles so formatting is persisted
            $(this).find('td.cell-editable').each(function(i,td){ 
                var $td = $(td);
                var styleAttr = $td.attr('style');
                if(styleAttr){
                    // Save as object with content and style
                    r.cells.push({content: $td.html(), style: styleAttr});
                } else {
                    // Save just the HTML
                    r.cells.push($td.html());
                }
            });
            // Get color from the color picker
            var colorVal = $(this).find('.row-color-picker').val();
            if(colorVal && colorVal !== '#ffffff'){
                r.rowColor = colorVal;
            }
            rows.push(r);
        });
        return { cols: cols, rows: rows };
    }

    function saveSheet(){
        var payload = getSheetFromDOM();
        $.ajax({
            url: base + 'classes/Master.php?f=save_project_sheet',
            method: 'POST',
            data: { project_id: projectId, sheet_name: sheetName, sheet_json: JSON.stringify(payload) },
            dataType: 'json',
            success: function(resp){
                if(resp.status == 'success'){
                    setUnsavedChanges(false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: 'Sheet saved successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }else{
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: resp.msg||resp.error||'Unknown error occurred'
                    });
                }
            },
            error: function(xhr, status, err){ 
                Swal.fire({
                    icon: 'error',
                    title: 'Save Error',
                    text: status + ' - ' + err
                });
                console.log(xhr.responseText); 
            }
        });
    }

    function loadSheet(){
        $.getJSON(base + 'classes/Master.php?f=get_project_sheet&project_id='+projectId+'&sheet_name='+sheetName, function(resp){
            if(resp.status == 'success' && resp.data){
                var sheet = resp.data;
                // ensure shape
                if(!sheet.cols) sheet = buildEmptySheet();
                renderSheet(sheet);
                // Initialize history after first load
                history = [JSON.stringify(getSheetFromDOM())];
                historyIndex = 0;
                updateUndoRedoButtons();
                setUnsavedChanges(false);
            } else {
                renderSheet(buildEmptySheet());
            }
        }).fail(function(){ renderSheet(buildEmptySheet()); });
    }
    
    function addResizeHandle(){
        $('#mini_sheet thead th[draggable="true"]').each(function(){
            if(!$(this).find('.col-resize-handle').length){
                $(this).append('<div class="col-resize-handle"></div>');
            }
        });
    }
    
    function filterRows(){
        var searchText = $('#ms_search').val().toLowerCase();
        $('#mini_sheet tbody tr').each(function(){
            var rowText = $(this).text().toLowerCase();
            if(searchText === '' || rowText.includes(searchText)){
                $(this).removeClass('search-hidden');
            } else {
                $(this).addClass('search-hidden');
            }
        });
    }
    
    function undo(){
        if(historyIndex > 0){
            historyIndex--;
            renderSheet(JSON.parse(history[historyIndex]));
            updateUndoRedoButtons();
        }
    }
    
    function redo(){
        if(historyIndex < history.length - 1){
            historyIndex++;
            renderSheet(JSON.parse(history[historyIndex]));
            updateUndoRedoButtons();
        }
    }

    function showCreateTasksModal(){
        // Get all column headers to identify activity and end_date columns
        var headers = [];
        var activityIdx = -1, endDateIdx = -1;
        $('#mini_sheet thead th').each(function(i){
            if(i > 0){ // skip first column (row control)
                var title = $(this).find('.col-title').text() || $(this).text();
                headers.push(title);
                if(title.toLowerCase().includes('activity') || title.toLowerCase().includes('description')){
                    activityIdx = i - 1;
                }
                if(title.toLowerCase().includes('end') || title.toLowerCase().includes('date')){
                    endDateIdx = i - 1;
                }
            }
        });

        if(activityIdx < 0){
            Swal.fire({
                icon: 'warning',
                title: 'Column Not Found',
                text: 'Cannot find "Activity" or "Description" column in the spreadsheet.'
            });
            return;
        }

        // Build rows list with checkboxes
        var rowsList = '';
        $('#mini_sheet tbody tr').each(function(rowIdx){
            var cells = $(this).find('td.cell-editable');
            if(cells.length > activityIdx){
                var activity = cells.eq(activityIdx).text().trim();
                if(activity){
                    var endDate = endDateIdx >= 0 && cells.length > endDateIdx ? cells.eq(endDateIdx).text().trim() : '';
                    rowsList += '<div class="form-check" style="margin-bottom: 8px;">';
                    rowsList += '<input type="checkbox" class="form-check-input task-row-check" data-row="'+rowIdx+'" data-activity="'+activity.replace(/"/g, '&quot;')+'" data-end-date="'+endDate.replace(/"/g, '&quot;')+'" id="taskRow'+rowIdx+'">';
                    rowsList += '<label class="form-check-label" for="taskRow'+rowIdx+'" style="cursor: pointer; margin-left: 5px;">';
                    rowsList += activity;
                    if(endDate) rowsList += ' <small style="color: #999;">('+endDate+')</small>';
                    rowsList += '</label></div>';
                }
            }
        });

        if(!rowsList){
            Swal.fire({
                icon: 'warning',
                title: 'No Data',
                text: 'No rows with activity data found in the spreadsheet.'
            });
            return;
        }

        $('#taskRowsList').html(rowsList);

        // Load users for assignment dropdown
        $.get(base + 'classes/Master.php?f=get_users', function(resp){
            if(resp && resp.status === 'success' && resp.users){
                var usersHtml = '<option value="">-- Select User --</option>';
                resp.users.forEach(function(user){
                    usersHtml += '<option value="'+user.id+'">'+user.name+'</option>';
                });
                $('#taskAssignTo').html(usersHtml);
            }
        }, 'json').fail(function(){
            console.log('Could not load users list');
        });

        $('#createTasksModal').modal('show');
    }

    function submitCreateTasks(){
        var selectedRows = [];
        $('.task-row-check:checked').each(function(){
            selectedRows.push({
                activity: $(this).data('activity'),
                end_date: $(this).data('end-date')
            });
        });

        if(selectedRows.length === 0){
            Swal.fire({
                icon: 'warning',
                title: 'No Rows Selected',
                text: 'Please select at least one row to create tasks from.'
            });
            return;
        }

        var assignedTo = $('#taskAssignTo').val();
        if(!assignedTo){
            Swal.fire({
                icon: 'warning',
                title: 'User Required',
                text: 'Please select a user to assign the tasks to.'
            });
            return;
        }

        // Disable button and show progress
        var $btn = $('#confirmCreateTasks');
        $btn.prop('disabled', true).html('Creating...');

        $.ajax({
            url: base + 'classes/Master.php?f=create_tasks_from_sheet',
            type: 'POST',
            dataType: 'json',
            data: {
                project_id: projectId,
                rows: JSON.stringify(selectedRows),
                assigned_to: assignedTo
            },
            success: function(resp){
                $btn.prop('disabled', false).html('Create Tasks');

                if(resp.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Tasks Created',
                        text: resp.msg || 'Tasks have been created successfully.'
                    }).then(function(){
                        $('#createTasksModal').modal('hide');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: resp.msg || 'Failed to create tasks.'
                    });
                }
            },
            error: function(xhr, status, err){
                $btn.prop('disabled', false).html('Create Tasks');
                Swal.fire({
                    icon: 'error',
                    title: 'Request Error',
                    text: status + ' - ' + err
                });
                console.log(xhr.responseText);
            }
        });
    }

    // Parse flexible date formats: dd-mm-yyyy, dd/mm/yyyy, yyyy-mm-dd
    function parseFlexibleDate(dateStr){
        if(!dateStr) return null;
        dateStr = dateStr.trim();
        
        // Try dd-mm-yyyy or dd/mm/yyyy
        var parts = dateStr.split(/[-\/]/);
        if(parts.length === 3){
            if(parts[0].length <= 2 && parts[1].length <= 2){
                // dd-mm-yyyy format
                var year = parseInt(parts[2]);
                // Convert 2-digit years to 4-digit (00-99 becomes 2000-2099)
                if(parts[2].length === 2){
                    year += 2000;
                }
                var d = new Date(year, parts[1]-1, parts[0]);
                if(!isNaN(d.getTime())) return d;
            }
        }
        
        // Try standard yyyy-mm-dd
        var d = new Date(dateStr);
        if(!isNaN(d.getTime())) return d;
        
        return null;
    }
    
    // Format date as dd-mm-yyyy
    function formatDate(date){
        var d = date.getDate().toString().padStart(2, '0');
        var m = (date.getMonth() + 1).toString().padStart(2, '0');
        var y = date.getFullYear();
        return d + '-' + m + '-' + y;
    }

    // Auto-calculate dates/timeline when cells are edited
    function autoCalculateDates(row){
        var cells = row.find('td.cell-editable');
        var timeline = null, startDate = null, endDate = null;
        var timelineIdx = -1, startIdx = -1, endIdx = -1;
        
        // Find Timeline, Start date, End date columns by type
        cells.each(function(i){
            var type = $(this).attr('data-col-type');
            var title = $('#mini_sheet thead th').eq(i+1).find('.col-title').text().trim().toLowerCase();
            
            if(type === 'timeline' || title.includes('timeline') || title.includes('days')){
                timelineIdx = i;
                timeline = $(this).text().trim();
            }
            if(type === 'date'){
                if(!startIdx || startIdx < 0 || title.includes('start')){
                    if(title.includes('start')){
                        startIdx = i;
                        startDate = $(this).text().trim();
                    } else if(startIdx < 0) {
                        startIdx = i;
                        startDate = $(this).text().trim();
                    }
                }
                if(title.includes('end') || (!title.includes('start') && startIdx >= 0 && i > startIdx)){
                    endIdx = i;
                    endDate = $(this).text().trim();
                }
            }
        });
        
        // Calculate End date from Timeline + Start date
        if(timeline && startDate && timelineIdx >= 0 && startIdx >= 0 && endIdx >= 0){
            var days = parseInt(timeline);
            if(!isNaN(days) && days > 0){
                var start = parseFlexibleDate(startDate);
                if(start){
                    var end = new Date(start);
                    end.setDate(end.getDate() + days);
                    cells.eq(endIdx).text(formatDate(end));
                    return;
                }
            }
        }
        
        // Calculate Timeline from Start date + End date
        if(startDate && endDate && timelineIdx >= 0 && startIdx >= 0 && endIdx >= 0){
            var start = parseFlexibleDate(startDate);
            var end = parseFlexibleDate(endDate);
            if(start && end){
                var diffDays = Math.floor((end - start) / (1000 * 60 * 60 * 24));
                cells.eq(timelineIdx).text(diffDays);
            }
        }
    }
    
    // events
    // Track active editable cell for formatting
    $(document).on('focus', '.cell-editable', function(){ activeEditable = this; });
    $(document).on('blur', '.cell-editable', function(){ activeEditable = this; autoCalculateDates($(this).closest('tr')); saveToHistory(); });
    
    // Formatting actions
    function applyCmd(cmd, value){
        if(!activeEditable) return;
        var sel = window.getSelection();
        var hasRange = sel && sel.rangeCount > 0 && !sel.getRangeAt(0).collapsed;
        // Focus to restore selection context
        activeEditable.focus();
        if(cmd === 'foreColor'){
            if(hasRange){
                document.execCommand('foreColor', false, value);
            } else {
                // Apply color to entire cell when no selection
                $(activeEditable).css('color', value);
            }
        } else if(cmd === 'bold'){
            if(hasRange){
                document.execCommand('bold', false, null);
            } else {
                // Toggle bold for entire cell
                var $cell = $(activeEditable);
                var current = $cell.css('font-weight');
                var isBold = parseInt(current,10) >= 600 || current === 'bold';
                $cell.css('font-weight', isBold ? 'normal' : 'bold');
            }
        } else if(cmd === 'italic'){
            if(hasRange){
                document.execCommand('italic', false, null);
            } else {
                var $cell = $(activeEditable);
                var current = $cell.css('font-style');
                $cell.css('font-style', current === 'italic' ? 'normal' : 'italic');
            }
        } else if(cmd === 'underline'){
            if(hasRange){
                document.execCommand('underline', false, null);
            } else {
                var $cell = $(activeEditable);
                var current = $cell.css('text-decoration-line') || $cell.css('text-decoration');
                var isUnderline = (current && current.indexOf('underline') !== -1);
                $cell.css('text-decoration', isUnderline ? 'none' : 'underline');
            }
        }
        saveToHistory();
    }
    $(document).on('click', '#fmt_bold', function(){ applyCmd('bold'); });
    $(document).on('click', '#fmt_italic', function(){ applyCmd('italic'); });
    $(document).on('click', '#fmt_underline', function(){ applyCmd('underline'); });
    $(document).on('change', '#fmt_color', function(){ applyCmd('foreColor', $(this).val()); });
    
    // Existing events
    $(document).on('click', '#ms_add_row', function(){ addRow(); saveToHistory(); });
    $(document).on('click', '#ms_add_col', function(){ addCol(); });
    $(document).on('click', '#confirmAddCol', function(){ addColFromModal(); });
    $(document).on('keypress', '#colName', function(e){ if(e.which === 13) addColFromModal(); });
    $(document).on('click', '#ms_export_csv', function(){ exportCSV(); });
    $(document).on('click', '#ms_save_sheet', function(){ saveSheet(); });
    $(document).on('click', '#ms_undo', function(){ undo(); });
    $(document).on('click', '#ms_redo', function(){ redo(); });
    $(document).on('input', '#ms_search', function(){ filterRows(); });
    $(document).on('click', '#ms_create_tasks', function(){ showCreateTasksModal(); });
    $(document).on('click', '#confirmCreateTasks', function(){ submitCreateTasks(); });
    
    $(document).on('click', '.ms_add_row_inline', function(){ 
        var currentRow = $(this).closest('tr');
        var cols = $('#mini_sheet thead th').length - 1;
        var newRow = $('<tr draggable="true"></tr>');
        var ctrl = $('<td style="padding: 5px; display: flex; gap: 5px; align-items: center;"><button class="btn btn-xs btn-success ms_add_row_inline" style="margin: 0;" title="Add row">+</button><button class="btn btn-xs btn-danger ms_del_row_inline" style="margin: 0;" title="Delete row">🗑️</button><div style="position: relative; width: 28px; height: 28px;"><input type="color" class="row-color-picker" value="#ffffff" style="opacity: 0; width: 100%; height: 100%; cursor: pointer; position: absolute; top: 0; left: 0;"><div class="color-wheel-bg" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ddd; background-color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; pointer-events: none;">🎨</div></div></td>');
        newRow.append(ctrl);
        for(var i=0;i<cols;i++){
            var colType = $('#mini_sheet thead th').eq(i+1).attr('data-col-type') || 'text';
            var td = $('<td contenteditable class="cell-editable" data-col-type="'+colType+'"></td>');
            newRow.append(td);
        }
        newRow.insertAfter(currentRow);
        saveToHistory();
    });
    $(document).on('click', '.ms_del_row_inline', function(){
        var row = $(this).closest('tr');
        Swal.fire({
            icon: 'warning',
            title: 'Delete this row?',
            text: 'This cannot be undone (use Undo if needed).',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result){
            if(result.isConfirmed){
                row.remove();
                saveToHistory();
            }
        });
    });
    $(document).on('click', '.ms_del_col', function(){ 
        var colIndex = parseInt($(this).data('col-index'));
        // Delete header
        $('#mini_sheet thead th').eq(colIndex + 1).remove();
        // Delete cells from all rows
        $('#mini_sheet tbody tr').each(function(){
            $(this).find('td').eq(colIndex + 1).remove();
        });
        saveToHistory();
    });
    $(document).on('change', '.row-color-picker', function(){
        var color = $(this).val();
        var tr = $(this).closest('tr');
        tr.css('background-color', color);
        // Update the color wheel circle
        $(this).siblings('.color-wheel-bg').css('background-color', color);
        saveToHistory();
    });

    // Column resize functionality
    var resizingCol = null;
    var resizeStartX = 0;
    var resizeStartWidth = 0;
    
    $(document).on('mousedown', '.col-resize-handle', function(e){
        resizingCol = $(this).closest('th');
        resizeStartX = e.pageX;
        resizeStartWidth = resizingCol.width();
        e.preventDefault();
    });
    
    $(document).on('mousemove', function(e){
        if(resizingCol){
            var newWidth = resizeStartWidth + (e.pageX - resizeStartX);
            if(newWidth > 50) resizingCol.width(newWidth);
        }
    });
    
    $(document).on('mouseup', function(){
        if(resizingCol){
            resizingCol = null;
            saveToHistory();
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e){
        if(e.ctrlKey || e.metaKey){
            if(e.which === 90){ // Ctrl+Z
                e.preventDefault();
                undo();
            } else if(e.which === 89){ // Ctrl+Y
                e.preventDefault();
                redo();
            } else if(e.which === 83){ // Ctrl+S
                e.preventDefault();
                saveSheet();
            }
        }
    });

    // Delete project action
    $(document).on('click', '#btn_delete_project', function(){
        Swal.fire({
            icon: 'warning',
            title: 'Delete this project?',
            text: 'This will permanently remove the project, its items, activities, PO links and tasks.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then(function(result){
            if(result.isConfirmed){
                $.ajax({
                    url: base + 'classes/Master.php?f=delete_project',
                    method: 'POST',
                    data: { id: projectId },
                    dataType: 'json',
                    success: function(resp){
                        if(resp.status === 'success'){
                            Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false });
                            setTimeout(function(){ window.location.href = base + 'admin/?page=project_planner2'; }, 1200);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Delete failed', text: (resp.msg||resp.error||'Unknown error') });
                        }
                    },
                    error: function(xhr, status, err){
                        Swal.fire({ icon: 'error', title: 'Delete Error', text: status + ' - ' + err });
                        console.log(xhr.responseText);
                    }
                });
            }
        });
    });
    
    // New sheet
    $(document).on('click', '#ms_new_sheet', function(){
        Swal.fire({
            icon: 'question',
            title: 'New Sheet',
            input: 'text',
            inputPlaceholder: 'e.g., Sheet2, Q1 Plan',
            showCancelButton: true,
            confirmButtonText: 'Create',
            inputValidator: function(val){ if(!val.trim()) return 'Sheet name is required'; }
        }).then(function(result){
            if(result.isConfirmed){
                var newName = result.value.trim();
                $.ajax({
                    url: base + 'classes/Master.php?f=save_project_sheet',
                    method: 'POST',
                    data: { project_id: projectId, sheet_name: newName, sheet_json: JSON.stringify(buildEmptySheet()) },
                    dataType: 'json',
                    success: function(resp){
                        if(resp.status === 'success'){
                            sheetName = newName;
                            hasUnsavedChanges = false;
                            loadSheetsList();
                            Swal.fire({ icon: 'success', title: 'Created', timer: 1000, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Create failed', text: resp.msg||'Error creating sheet' });
                        }
                    },
                    error: function(xhr, status, err){
                        Swal.fire({ icon: 'error', title: 'Error', text: status + ' - ' + err });
                    }
                });
            }
        });
    });
    
    // Delete sheet
    $(document).on('click', '#ms_delete_sheet', function(){
        if(!sheetName || sheetName === 'Sheet1'){
            Swal.fire({ icon: 'warning', title: 'Cannot delete', text: 'Sheet1 cannot be deleted.' });
            return;
        }
        Swal.fire({
            icon: 'warning',
            title: 'Delete sheet?',
            text: 'This will permanently remove "'+sheetName+'" and all its data.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then(function(result){
            if(result.isConfirmed){
                $.ajax({
                    url: base + 'classes/Master.php?f=delete_project_sheet',
                    method: 'POST',
                    data: { project_id: projectId, sheet_name: sheetName },
                    dataType: 'json',
                    success: function(resp){
                        if(resp.status === 'success'){
                            hasUnsavedChanges = false;
                            loadSheetsList();
                            Swal.fire({ icon: 'success', title: 'Deleted', timer: 1000, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Delete failed', text: resp.msg||'Error deleting sheet' });
                        }
                    },
                    error: function(xhr, status, err){
                        Swal.fire({ icon: 'error', title: 'Error', text: status + ' - ' + err });
                    }
                });
            }
        });
    });
    
    // Rename sheet
    $(document).on('click', '#ms_rename_sheet', function(){
        if(!sheetName){
            Swal.fire({ icon: 'warning', title: 'Select a sheet first', text: 'Choose a sheet to rename.' });
            return;
        }
        Swal.fire({
            icon: 'question',
            title: 'Rename Sheet',
            input: 'text',
            inputValue: sheetName,
            inputPlaceholder: 'Enter new sheet name',
            showCancelButton: true,
            confirmButtonText: 'Rename',
            inputValidator: function(val){ 
                if(!val.trim()) return 'Sheet name is required';
            }
        }).then(function(result){
            if(result.isConfirmed){
                var newName = result.value.trim();
                if(newName === sheetName) return;
                $.ajax({
                    url: base + 'classes/Master.php?f=rename_project_sheet',
                    method: 'POST',
                    data: { project_id: projectId, old_name: sheetName, new_name: newName },
                    dataType: 'json',
                    success: function(resp){
                        if(resp.status === 'success'){
                            sheetName = newName;
                            hasUnsavedChanges = false;
                            loadSheetsList();
                            Swal.fire({ icon: 'success', title: 'Renamed', timer: 1000, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Rename failed', text: resp.msg||'Error renaming sheet' });
                        }
                    },
                    error: function(xhr, status, err){
                        Swal.fire({ icon: 'error', title: 'Error', text: status + ' - ' + err });
                    }
                });
            }
        });
    });
    
    // Sheet selector change
    $(document).on('change', '#ms_sheet_selector', function(){
        if(hasUnsavedChanges){
            Swal.fire({
                icon: 'warning',
                title: 'Unsaved changes',
                text: 'Save before switching sheets?',
                showCancelButton: true,
                confirmButtonText: 'Save',
                cancelButtonText: 'Discard'
            }).then(function(result){
                if(result.isConfirmed){
                    saveSheet();
                    setTimeout(function(){
                        sheetName = $('#ms_sheet_selector').val();
                        hasUnsavedChanges = false;
                        loadSheet();
                    }, 500);
                } else {
                    sheetName = $('#ms_sheet_selector').val();
                    hasUnsavedChanges = false;
                    loadSheet();
                }
            });
        } else {
            sheetName = $('#ms_sheet_selector').val();
            loadSheet();
        }
    });
    
    // Warn on unsaved changes
    $(window).on('beforeunload', function(e){
        if(hasUnsavedChanges){
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
    var draggedCol = null;
    $(document).on('dragstart', 'th[draggable="true"]', function(e){
        draggedCol = $(this);
        e.originalEvent.dataTransfer.effectAllowed = 'move';
    });
    
    $(document).on('dragover', 'th[draggable="true"]', function(e){
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        if(draggedCol && draggedCol[0] !== this){
            $(this).addClass('drag-over');
        }
    });
    
    $(document).on('dragleave', 'th[draggable="true"]', function(e){
        $(this).removeClass('drag-over');
    });
    
    $(document).on('drop', 'th[draggable="true"]', function(e){
        e.preventDefault();
        e.stopPropagation();
        if(draggedCol && draggedCol[0] !== this){
            // Get the indices
            var fromIdx = draggedCol.index();
            var toIdx = $(this).index();
            
            // Move all cells at fromIdx to position toIdx
            var allThs = $('#mini_sheet thead th');
            var draggedTh = allThs.eq(fromIdx);
            
            if(toIdx < fromIdx){
                draggedTh.insertBefore(allThs.eq(toIdx));
            } else {
                draggedTh.insertAfter(allThs.eq(toIdx));
            }
            
            // Move corresponding TD cells in all rows
            $('#mini_sheet tbody tr').each(function(){
                var allTds = $(this).find('td');
                var draggedTd = allTds.eq(fromIdx);
                
                if(toIdx < fromIdx){
                    draggedTd.insertBefore(allTds.eq(toIdx));
                } else {
                    draggedTd.insertAfter(allTds.eq(toIdx));
                }
            });
        }
        $('.drag-over').removeClass('drag-over');
        draggedCol = null;
    });

    // Drag and drop for rows
    var draggedRow = null;
    var draggedRowIndex = null;
    $(document).on('dragstart', 'tbody tr[draggable="true"]', function(e){
        draggedRow = $(this);
        draggedRowIndex = draggedRow.index();
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        $(this).addClass('dragging');
    });
    
    $(document).on('dragover', 'tbody tr[draggable="true"]', function(e){
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        if(draggedRow && draggedRow[0] !== this){
            $(this).addClass('drag-over');
        }
    });
    
    $(document).on('dragleave', 'tbody tr[draggable="true"]', function(e){
        $(this).removeClass('drag-over');
    });
    
    $(document).on('drop', 'tbody tr[draggable="true"]', function(e){
        e.preventDefault();
        e.stopPropagation();
        if(draggedRow && draggedRow[0] !== this){
            var targetIdx = $(this).index();
            var allRows = $('#mini_sheet tbody tr');
            var rowToMove = allRows.eq(draggedRowIndex);
            
            if(draggedRowIndex < targetIdx){
                // Moving down
                rowToMove.insertAfter(allRows.eq(targetIdx));
            } else {
                // Moving up
                rowToMove.insertBefore(allRows.eq(targetIdx));
            }
        }
        $('.drag-over').removeClass('drag-over');
        draggedRow = null;
        draggedRowIndex = null;
    });
    
    $(document).on('dragend', 'tbody tr[draggable="true"]', function(e){
        $('.dragging').removeClass('dragging');
        $('.drag-over').removeClass('drag-over');
        draggedRow = null;
        draggedRowIndex = null;
    });

    // Card header click to toggle collapse (ignore tool buttons)
    $(document).on('click', '.ms-card-header-toggle', function(e){
        if($(e.target).closest('.card-tools').length) return;
        var card = $(this).closest('.card');
        if(card.length && typeof card.CardWidget === 'function'){
            card.CardWidget('toggle');
        }
    });

    // Lazy-load sheets when card becomes visible
    var sheetsLoaded = false;
    var observer = new IntersectionObserver(function(entries){
        entries.forEach(function(entry){
            if(entry.isIntersecting && !sheetsLoaded){
                sheetsLoaded = true;
                loadSheetsList();
                observer.disconnect();
            }
        });
    }, { threshold: 0.1 });
    
    $(function(){
        var card = document.querySelector('.card.card-outline.card-info');
        if(card){
            observer.observe(card);
        } else {
            // Fallback: load immediately if card not found
            loadSheetsList();
        }
    });
})();
</script>
