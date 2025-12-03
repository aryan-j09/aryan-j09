<?php
// Temporary debug helpers to reveal hidden PHP errors and connection state.
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
global $conn;
$id = intval($_GET['id']);
$qry = $conn->query("SELECT p.*, c.company_name as client_name FROM `project_planner` p LEFT JOIN `clients` c ON p.client_id = c.id WHERE p.id = '{$id}'");
if($qry === false){
    echo "<div class='alert alert-danger'>Error fetching project: " . htmlspecialchars($conn->error) . "</div>";
    return;
}
if($qry->num_rows <= 0){
    echo "<div class='alert alert-warning'>Project not found.</div>";
    return;
}
$project = $qry->fetch_assoc();

// Fetch assigned Supplier POs
$supplier_po_qry = $conn->query("SELECT pol.po_code FROM `project_po_list` ppl
                                 JOIN `purchase_order_list` pol ON ppl.po_id = pol.id
                                 WHERE ppl.project_id = '{$id}'
                                 ORDER BY pol.po_code ASC");
if($supplier_po_qry === false){
    echo "<div class='alert alert-danger'>Error fetching assigned POs: " . htmlspecialchars($conn->error) . "</div>";
    $supplier_pos = [];
} else {
    $supplier_pos = $supplier_po_qry->fetch_all(MYSQLI_ASSOC);
}

// Fetch project items
$items_qry = $conn->query("
    SELECT 
        pi.*, 
        pia.activity_name as latest_activity
    FROM `project_items` pi
    LEFT JOIN (
        SELECT 
            project_item_id, activity_name,
            ROW_NUMBER() OVER(PARTITION BY project_item_id ORDER BY activity_date DESC, id DESC) as rn
        FROM `project_item_activities`
    ) pia ON pi.id = pia.project_item_id AND pia.rn = 1
    WHERE pi.project_id = '{$id}' ORDER BY pi.item_name ASC");
if($items_qry === false){
    echo "<div class='alert alert-danger'>Error fetching project items: " . htmlspecialchars($conn->error) . "</div>";
    $items = [];
} else {
    $items = $items_qry->fetch_all(MYSQLI_ASSOC);
}

// Fetch all available items for the dropdown
$all_items_qry = $conn->query("SELECT name FROM `item_list` ORDER BY name ASC");
if($all_items_qry === false){
    echo "<div class='alert alert-danger'>Error fetching items: " . htmlspecialchars($conn->error) . "</div>";
    $all_items = [];
} else {
    $all_items = $all_items_qry->fetch_all(MYSQLI_ASSOC);
}

// Fetch available PO items that are not already added to the project
$available_po_items = [];

// Supplier PO Items - get all items from assigned POs
$supplier_items_qry = $conn->query("SELECT pi.id, pol.po_code, il.name as item_name
                                         FROM `po_items` pi 
                                    JOIN `purchase_order_list` pol ON pi.po_id = pol.id
                                    JOIN `item_list` il ON pi.item_id = il.id
                                    WHERE pol.id IN (SELECT po_id FROM project_po_list WHERE project_id = '{$id}')
                                    ORDER BY pol.po_code ASC, il.name ASC");
if($supplier_items_qry !== false){
    while($row = $supplier_items_qry->fetch_assoc()){
        // Show all PO items from assigned POs
        $available_po_items[] = [
            'id' => $row['id'], 
            'text' => '['.$row['po_code'].'] ' . $row['item_name']
        ];
    }
} else {
    echo "<div class='alert alert-warning'>No PO items found or error: " . htmlspecialchars($conn->error) . "</div>";
}

// Fetch general project activities for the timeline
$project_activities_qry = $conn->query("SELECT a.*, u.firstname, u.lastname 
                                        FROM project_activities a 
                                        LEFT JOIN users u ON u.id = a.created_by 
                                        WHERE project_id = '{$id}' 
                                        ORDER BY a.created_at DESC");
if (!$project_activities_qry) {
    die("Error fetching project activities: " . $conn->error);
}

// Fetch project tasks
$tasks_qry = $conn->query("SELECT t.*, CONCAT(u.firstname, ' ', u.lastname) as assigned_to_name 
                           FROM `tasks` t 
                           LEFT JOIN `users` u ON t.assigned_to = u.id 
                           WHERE t.project_id = '{$id}' 
                           ORDER BY t.due_date ASC, t.priority DESC");
if (!$tasks_qry) {
    die("Error fetching project tasks: " . $conn->error);
}

// Fetch users for task assignment dropdown
$users_qry = $conn->query("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM `users` ORDER BY name ASC");
$users = $users_qry->fetch_all(MYSQLI_ASSOC);
?>

<div class="row">
    <div class="col-md-8">
                <!-- Project Items Card -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Project Items</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-flat btn-primary" id="add_item_btn"><i class="fa fa-plus"></i> Add Item</button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Specifications</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($item['specifications'])); ?></td>
                                <td>
                                    <?php echo !empty($item['latest_activity']) ? '<span class="badge badge-primary">' . htmlspecialchars($item['latest_activity']) . '</span>' : '<span class="badge badge-secondary">Pending</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-info btn-flat log_activity" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['item_name']); ?>"><i class="fa fa-tasks"></i></button>
                                    <button class="btn btn-xs btn-primary btn-flat edit_item" data-id="<?php echo $item['id']; ?>" data-item='<?php echo json_encode($item, JSON_HEX_QUOT | JSON_HEX_APOS); ?>'><i class="fa fa-edit"></i></button>
                                    <button class="btn btn-xs btn-danger btn-flat delete_item" data-id="<?php echo $item['id']; ?>"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($items)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No items added yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- General Activity Log Card -->
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Project Activity Log</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-flat btn-primary" id="log_project_activity_btn"><i class="fa fa-plus"></i> Log Activity</button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php if($project_activities_qry->num_rows > 0): ?>
                        <?php while($row = $project_activities_qry->fetch_assoc()): ?>
                        <div>
                            <i class="fas fa-info-circle bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date("M d, Y h:i A", strtotime($row['created_at'])) ?>
                                </span>
                                <h3 class="timeline-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php echo '<strong>' . ucfirst($row['activity_type']) . '</strong> by ' . $row['firstname'].' '.$row['lastname'] ?>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-xs btn-primary edit_project_activity" 
                                                data-id="<?php echo $row['id'] ?>"
                                                data-activity='<?php echo json_encode($row, JSON_HEX_QUOT | JSON_HEX_APOS); ?>'
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-danger delete_project_activity" data-id="<?php echo $row['id'] ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </h3>
                                <div class="timeline-body">
                                    <?php echo nl2br(htmlspecialchars($row['description'])) ?>
                                    
                                    <?php if(!empty($row['time_from']) && !empty($row['time_to'])): ?>
                                    <div class="mt-2">
                                        <span class="badge badge-info">
                                            <i class="fas fa-clock"></i> 
                                            Time: <?php echo date("h:i A", strtotime($row['time_from'])) ?> - <?php echo date("h:i A", strtotime($row['time_to'])) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($row['next_followup'])): ?>
                                    <div class="mt-2">
                                        <span class="badge badge-warning">
                                            <i class="fas fa-calendar-alt"></i> 
                                            Next Follow-up: <?php echo date("d M, Y h:i A", strtotime($row['next_followup'])) ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted">No general project activities logged yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Project Details</h3>
                <div class="card-tools">
                    <a class="btn btn-sm btn-flat btn-primary" href="<?php echo base_url ?>admin/?page=project_planner/manage_project&id=<?php echo $project['id'] ?>"><i class="fa fa-edit"></i> Edit</a>
                    <button class="btn btn-sm btn-flat btn-danger delete_project" data-id="<?php echo $project['id']; ?>"><i class="fa fa-trash"></i> Delete</button>
                    <a class="btn btn-sm btn-flat btn-secondary" href="<?php echo base_url ?>admin/?page=project_planner">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <dl>
                    <dt>Project Name</dt>
                    <dd><?php echo htmlspecialchars($project['name']) ?></dd>
                    <dt>Client</dt>
                    <dd><?php echo htmlspecialchars($project['client_name']) ?></dd>
                    <dt>Date Created</dt>
                    <dd><?php echo date("d M, Y", strtotime($project['created_at'])) ?></dd>
                    <dt>Description</dt>
                    <dd><?php echo !empty($project['description']) ? $project['description'] : 'N/A' ?></dd>
                    <dt class="mt-3">Assigned Purchase Orders</dt>
                    <dd>
                        <?php
                        $has_pos = false;
                        if (!empty($supplier_pos)) {
                            $has_pos = true;
                            echo "<h6><span class='badge badge-secondary'>Supplier POs</span></h6>";
                            foreach ($supplier_pos as $spo) {
                                echo '<span class="badge badge-info mr-1">' . htmlspecialchars($spo['po_code']) . '</span>';
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

        <!-- Project Tasks Card -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Project Tasks</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-flat btn-success" id="add_task_btn"><i class="fa fa-plus"></i> Add Task</button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-hover table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Status</th>
                            <th>Task</th>
                            <th>Assigned To</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Rewind query pointer to loop again since it was used before
                        $tasks_qry->data_seek(0); 
                        if($tasks_qry->num_rows > 0): 
                            while($task = $tasks_qry->fetch_assoc()): 
                        ?>
                                <tr>
                                    <td class="text-center"><span class="badge <?php echo get_task_status_badge($task['status']); ?>"><?php echo ucfirst($task['status']); ?></span></td>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo htmlspecialchars($task['assigned_to_name']); ?></td>
                                    <td><?php echo date("d M, Y", strtotime($task['due_date'])); ?></td>
                                    <td><?php echo ucfirst($task['priority']); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-xs btn-primary btn-flat edit_task" data-task='<?php echo json_encode($task, JSON_HEX_QUOT | JSON_HEX_APOS); ?>'><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-xs btn-danger btn-flat delete_task" data-id="<?php echo $task['id']; ?>"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No tasks planned for this project yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Task Modal -->
<div class="modal fade" id="task_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Project Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="task-form">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="project_id" value="<?php echo $id; ?>">
                    <div class="form-group">
                        <label for="title" class="control-label">Task Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description" class="control-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="assigned_to" class="control-label">Assigned To</label>
                            <select name="assigned_to" id="assigned_to" class="form-control" required>
                                <option value="" disabled selected>Select a user</option>
                                <?php foreach($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="due_date" class="control-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="priority" class="control-label">Priority</label>
                            <select name="priority" id="priority" class="form-control" required>
                                <option value="Low">Low</option>
                                <option value="Normal">Normal</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="status" class="control-label">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="In-Progress">In-Progress</option>
                                <option value="Testing">Testing</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" form="task-form">Save Task</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Item Modal -->
<div class="modal fade" id="item_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Project Item</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="item-form">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="po_item_id" value="">
                    <?php if(!empty($available_po_items)): ?>
                    <div class="form-group">
                        <label for="po_item_select" class="control-label">Import from Assigned PO</label>
                        <select id="po_item_select" class="form-control">
                            <option value="" disabled selected>Select a PO item to auto-fill</option>
                            <?php foreach($available_po_items as $po_item): ?>
                                <option value="<?php echo $po_item['id']; ?>"><?php echo htmlspecialchars($po_item['text']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <?php endif; ?>
                    <input type="hidden" name="project_id" value="<?php echo $id; ?>">
                    <div class="form-group">
                        <label for="item_name" class="control-label">Item Name</label>
                        <select name="item_name" id="item_name" class="form-control" required>
                            <option value="" disabled selected>Select an item</option>
                            <?php foreach($all_items as $item): ?>
                                <option value="<?php echo htmlspecialchars($item['name']); ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class="control-label">Quantity</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label for="specifications" class="control-label">Specifications</label>
                        <textarea name="specifications" id="specifications" class="form-control" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" form="item-form">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- General Project Activity Modal -->
<div class="modal fade" id="project_activity_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Project Activity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="project-activity-form">
                    <input type="hidden" name="project_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="activity_id" value="">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="project_activity_type" class="control-label">Activity Type</label>
                            <select name="activity_type" id="project_activity_type" class="form-control" required>
                                <option>Note</option>
                                <option>Email</option>
                                <option>Call</option>
                                <option>Meeting</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="project_activity_created_at" class="control-label">Activity Date & Time</label>
                            <input type="datetime-local" name="created_at" id="project_activity_created_at" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="project_activity_next_followup" class="control-label">Next Follow-up</label>
                            <input type="datetime-local" name="next_followup" id="project_activity_next_followup" class="form-control">
                        </div>
                    </div>
                    <div class="row time-range" style="display:none;">
                        <div class="col-md-3 form-group">
                            <label class="small">From</label>
                            <input type="time" name="time_from" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small">To</label>
                            <input type="time" name="time_to" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="project_activity_description" class="control-label">Description</label>
                        <textarea name="description" id="project_activity_description" class="form-control" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" form="project-activity-form">Save</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Item Activity Modal -->
<div class="modal fade" id="activity_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Activity Log for <span id="activity_item_name"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="activity-form">
                    <input type="hidden" name="project_item_id" value="">
                    <div class="form-group">
                        <label for="activity_name" class="control-label">Activity</label>
                        <select name="activity_name" id="activity_name" class="form-control" required>
                            <option>Order Placed</option>
                            <option>FAT</option>
                            <option>Dispatched</option>
                            <option>Received</option>
                            <option>Installed</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity_date" class="control-label">Date</label>
                        <input type="date" name="activity_date" id="activity_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="remarks" class="control-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Activity</button>
                </form>
                <hr>
                <div id="activity-log-container"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Initialize select2 for the item dropdown
    $('#item_name').select2({
        placeholder: "Select an item",
        dropdownParent: $('#item_modal') // Important for search to work inside a modal
    });
    $('#po_item_select').select2({
        placeholder: "Select a PO item to auto-fill",
        dropdownParent: $('#item_modal')
    });
    $('#assigned_to').select2({
        placeholder: "Select a user",
        dropdownParent: $('#task_modal')
    });

    // Show Add Item Modal
    $('#add_item_btn').click(function(){
        $('#item-form')[0].reset();
        $('#item-form input[name="id"]').val('');
        // Reset select2 fields
        $('#item_name').val(null).trigger('change');
        $('#po_item_select').val(null).trigger('change');
        $('#item_modal').modal('show');
    });

    // Show Edit Item Modal
    $('.edit_item').click(function(){
        var data = $(this).data('item');
        var form = $('#item-form');
        form[0].reset();
        form.find('input[name="id"]').val(data.id);
        form.find('select[name="item_name"]').val(data.item_name).trigger('change'); // Set select2 value
        form.find('input[name="quantity"]').val(data.quantity);
        form.find('textarea[name="specifications"]').val(data.specifications);
        $('#item_modal').modal('show');
    });

    // Handle PO Item Selection
    $('#po_item_select').on('change', function(){
        var po_item_id = $(this).val();
        if(!po_item_id) return;

        // Store the po_item_id in the hidden field
        $('#item-form input[name="po_item_id"]').val(po_item_id);

        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=get_po_item_for_project",
            method: 'POST',
            data: { po_item_id: po_item_id },
            dataType: 'json',
            error: function(err){
                console.log('AJAX Error:', err);
                alert_toast("An error occurred while fetching PO item details.", 'error');
                end_loader();
            },
            success: function(resp){
                console.log('AJAX Response:', resp);
                if(resp.status == 'success'){
                    $('#item_name').val(resp.data.item_name).trigger('change');
                    $('#quantity').val(resp.data.quantity);
                    $('#specifications').val(resp.data.specifications);
                    alert_toast("PO item details loaded successfully.", 'success');
                } else {
                    alert_toast(resp.msg || "Failed to load PO item details.", 'error');
                }
                end_loader();
            }
        });
    });

    // Handle Form Submission
    $('#item-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_project_item",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            error: function(err){
                console.log(err);
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast(resp.msg || "An error occurred.", 'error');
                }
                end_loader();
            }
        });
    });

    // Handle Delete Item
    $('.delete_item').click(function(){
        _conf("Are you sure you want to delete this item?", "delete_project_item", [$(this).data('id')]);
    });

    // --- Project Task Management ---

    // Show Add Task Modal
    $('#add_task_btn').click(function(){
        $('#task-form')[0].reset();
        $('#task-form input[name="id"]').val('');
        $('#assigned_to').val(null).trigger('change');
        $('#task_modal .modal-title').text('Add New Project Task');
        $('#task_modal').modal('show');
    });

    // Show Edit Task Modal
    $('.edit_task').click(function(){
        var task = $(this).data('task');
        var form = $('#task-form');
        form[0].reset();

        form.find('[name="id"]').val(task.id);
        form.find('[name="title"]').val(task.title);
        form.find('[name="description"]').val(task.description);

        // Helper: set select value case-insensitively (matches option value or text)
        function setSelectCaseInsensitive($select, value){
            if(value === null || value === undefined) return;
            var valStr = String(value);
            // Try direct match first
            if($select.find('option[value="'+valStr+'"]').length){
                $select.val(valStr).trigger('change');
                return;
            }
            var found = null;
            $select.find('option').each(function(){
                var optVal = $(this).attr('value') || '';
                var optText = $(this).text() || '';
                if(optVal.toLowerCase() === valStr.toLowerCase() || optText.toLowerCase() === valStr.toLowerCase()){
                    found = optVal;
                    return false;
                }
            });
            if(found !== null){
                $select.val(found).trigger('change');
                return;
            }
            // fallback: partial match on value
            $select.find('option').each(function(){
                var optVal = ($(this).attr('value') || '').toLowerCase();
                if(optVal.indexOf(valStr.toLowerCase()) !== -1){
                    found = $(this).attr('value');
                    return false;
                }
            });
            if(found !== null) $select.val(found).trigger('change');
        }

        // Correctly set dropdown values (case-insensitive to tolerate different DB casing)
        setSelectCaseInsensitive(form.find('#assigned_to'), task.assigned_to);
        setSelectCaseInsensitive(form.find('#priority'), task.priority);
        setSelectCaseInsensitive(form.find('#status'), task.status);

        // Format and set the due date robustly
        if(task.due_date && task.due_date.indexOf('0000-00-00') === -1){
            // Accept formats like "YYYY-MM-DD" or "YYYY-MM-DD HH:MM:SS"
            var datePart = task.due_date.split(' ')[0];
            form.find('[name="due_date"]').val(datePart);
        } else {
            form.find('[name="due_date"]').val('');
        }

        $('#task_modal .modal-title').text('Edit Project Task');
        $('#task_modal').modal('show');
    });

    // Handle Task Form Submission
    $('#task-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_task",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            error: function(err){
                console.log(err);
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast(resp.msg || "An error occurred.", 'error');
                }
                end_loader();
            }
        });
    });

    // Handle Delete Task
    $('.delete_task').click(function(){
        _conf("Are you sure you want to delete this task?", "delete_task", [$(this).data('id')]);
    });

    // --- Item Activity Log ---

    var current_item_id;

    // Show Activity Modal
    $('.log_activity').click(function(){
        current_item_id = $(this).data('id');
        var item_name = $(this).data('name');
        
        $('#activity_item_name').text(item_name);
        $('#activity-form input[name="project_item_id"]').val(current_item_id);
        $('#activity-form')[0].reset();
        $('#activity_date').val(new Date().toISOString().slice(0, 10)); // Set today's date

        load_activities();
        $('#activity_modal').modal('show');
    });

    function load_activities(){
        $('#activity-log-container').html('<div class="text-center">Loading...</div>');
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=get_item_activities",
            method: 'POST',
            data: { item_id: current_item_id },
            dataType: 'json',
            success: function(resp){
                if(resp.status == 'success'){
                    $('#activity-log-container').html(resp.html);
                }
            }
        });
    }

    // Handle Activity Form Submission
    $('#activity-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_item_activity",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp){
                if(resp.status == 'success'){
                    alert_toast("Activity logged successfully.", 'success');
                    // Reload page to update status in the main table
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert_toast("An error occurred.", 'error');
                }
                end_loader();
            }
        });
    });

    // Handle Activity Deletion (event delegation for dynamic elements)
    $(document).on('click', '.delete_activity', function(){
        var activity_id = $(this).data('id');
        _conf("Are you sure you want to delete this activity log?", "delete_item_activity_confirmed", [activity_id]);
    });

    // --- General Project Activity Log ---

    // Show Add Modal
    $('#log_project_activity_btn').click(function(){
        var form = $('#project-activity-form');
        form[0].reset();
        form.find('[name="activity_id"]').val('');
        form.find('[name="created_at"]').val(new Date().toISOString().slice(0, 16));
        $('#project_activity_modal .modal-title').text('Log Project Activity');
        $('#project_activity_modal').modal('show');
    });

    // Show Edit Modal
    $('.edit_project_activity').click(function(){
        var data = $(this).data('activity');
        var form = $('#project-activity-form');
        form[0].reset();

        form.find('[name="activity_id"]').val(data.id);
        form.find('[name="activity_type"]').val(data.activity_type).trigger('change');
        form.find('[name="description"]').val(data.description);
        
        // Format dates for datetime-local input
        if(data.created_at) form.find('[name="created_at"]').val(data.created_at.replace(' ', 'T').slice(0, 16));
        if(data.next_followup) form.find('[name="next_followup"]').val(data.next_followup.replace(' ', 'T').slice(0, 16));
        if(data.time_from) form.find('[name="time_from"]').val(data.time_from);
        if(data.time_to) form.find('[name="time_to"]').val(data.time_to);

        $('#project_activity_modal .modal-title').text('Edit Project Activity');
        $('#project_activity_modal').modal('show');
    });

    // Show time range for call/meeting
    $('#project_activity_type').change(function(){
        var type = $(this).val();
        if(type === 'Call' || type === 'Meeting'){
            $('#project_activity_modal .time-range').show();
        } else {
            $('#project_activity_modal .time-range').hide();
        }
    }).trigger('change');

    // Handle General Activity Form Submission
    $('#project-activity-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_project_activity",
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            error: function(err){
                console.log(err);
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast(resp.err || "An error occurred.", 'error');
                }
                end_loader();
            }
        });
    });

    // Handle General Activity Deletion
    $('.delete_project_activity').click(function(){
        _conf("Are you sure you want to delete this activity log?", "delete_project_activity_confirmed", [$(this).data('id')]);
    });

    // Handle Project Deletion
    $('.delete_project').click(function(){
        _conf("Are you sure you want to delete this entire project and all its data? This action cannot be undone.", "delete_project", [$(this).data('id')]);
    });
});

function delete_project_activity_confirmed($id){
    start_loader();
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=delete_project_activity",
        method: 'POST',
        data: {id: $id},
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                location.reload();
            } else {
                alert_toast("An error occurred.", 'error');
                end_loader();
            }
        }
    });
}

function delete_item_activity_confirmed($id){
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=delete_item_activity",
        method: 'POST',
        data: {id: $id},
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                alert_toast("Activity deleted.", 'success');
                setTimeout(() => location.reload(), 1000);
            }
        }
    });
}

function delete_project_item($id){
    start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_project_item",
		method:"POST",
		data:{id: $id},
		dataType:"json",
		error:err=>{
			console.log(err)
			alert_toast("An error occured.",'error');
			end_loader();
		},
		success:function(resp){
			if(typeof resp== 'object' && resp.status == 'success'){
				location.reload();
			}
		}
	})
}

function delete_project($id){
    start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_project",
		method:"POST",
		data:{id: $id},
		dataType:"json",
		error:err=>{
			console.log(err)
			alert_toast("An error occured.",'error');
			end_loader();
		},
		success:function(resp){
			if(resp.status == 'success'){
                location.href = '<?php echo base_url ?>admin/?page=project_planner';
			} else {
                alert_toast(resp.error || "An error occurred.", 'error');
			}
		}
	})
}

function delete_task($id){
    start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_task",
		method:"POST",
		data:{id: $id},
		dataType:"json",
		error:err=>{
			console.log(err)
			alert_toast("An error occured.",'error');
			end_loader();
		},
		success:function(resp){
			if(resp.status == 'success'){
				location.reload();
			}else{
                alert_toast(resp.error || "An error occurred.", 'error');
				end_loader();
			}
		}
	})
}

<?php
// Helper function to determine badge color for task status
function get_task_status_badge($status){
    switch(strtolower($status)){
        case 'pending':
            return 'badge-secondary';
        case 'in-progress':
            return 'badge-primary';
        case 'testing':
            return 'badge-info';
        case 'completed':
            return 'badge-success';
        default:
            return 'badge-light';
    }
}
?>
</script>
