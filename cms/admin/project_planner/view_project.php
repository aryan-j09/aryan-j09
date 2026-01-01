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

// Fetch Project Phases (will be empty until backend is implemented)
$phases_qry = $conn->query("SELECT * FROM `project_phases` WHERE project_id = '{$id}' ORDER BY display_order ASC, created_at ASC");
if (!$phases_qry) {
    // Don't die, just show an error and continue rendering the page
    echo "<div class='alert alert-danger'>Error preparing for phases: " . htmlspecialchars($conn->error) . "</div>";
}

// Fetch all activities for all phases of this project at once for efficiency
$phase_activities = [];
if ($phases_qry && $phases_qry->num_rows > 0) {
    $phase_ids = array_map(function($p) { return $p['id']; }, $phases_qry->fetch_all(MYSQLI_ASSOC));
    $phases_qry->data_seek(0); // Reset pointer after fetch_all

    $activities_qry = $conn->query("SELECT pa.*, CONCAT(u.firstname, ' ', u.lastname) as author FROM `phase_activities` pa LEFT JOIN `users` u ON pa.created_by = u.id WHERE pa.phase_id IN (" . implode(',', $phase_ids) . ") ORDER BY pa.created_at DESC");
    if ($activities_qry) {
        while ($activity = $activities_qry->fetch_assoc()) {
            $phase_activities[$activity['phase_id']][] = $activity;
        }
    }
}
?>
<style>
    .clickable-header {
        cursor: pointer;
        -webkit-user-select: none; /* Safari */
        -ms-user-select: none; /* IE 10+ and Edge */
        user-select: none; /* Standard syntax */
    }

    /* Item Card Retractable Styles */
    .item-card {
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .item-card.collapsed {
        max-height: 60px;
        overflow: hidden;
    }

    .item-card.expanded {
        max-height: none;
    }

    .item-card-header {
        padding: 12px 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
        cursor: pointer;
    }

    .item-card-header .expand-icon {
        transition: transform 0.3s ease;
        display: inline-block;
        margin-left: 8px;
    }

    .item-card.expanded .expand-icon {
        transform: rotate(180deg);
    }

    .item-card-body {
        padding: 0 15px 15px 15px;
        transition: opacity 0.3s ease;
    }

    .item-card.collapsed .item-card-body {
        display: none;
    }
</style>

<div class="row">
    <div class="col-md-8">

        <!-- Project Phases Section -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Project Phases</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-flat btn-success" id="add_phase_btn"><i class="fa fa-plus"></i> Add Phase</button>
                </div>
            </div>
            <div class="card-body">
                <div id="phases_container">
                    <?php if($phases_qry && $phases_qry->num_rows > 0): ?>
                        <?php while($phase = $phases_qry->fetch_assoc()): ?>
                            <div class="card card-outline card-secondary mb-3 collapsed-card">
                                <div class="card-header clickable-header">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($phase['name']); ?>
                                        <?php if(!empty($phase['start_date']) && !empty($phase['end_date'])): ?>
                                            <small class="text-muted ml-2">(<?php echo date("d M, Y", strtotime($phase['start_date'])) . ' - ' . date("d M, Y", strtotime($phase['end_date'])); ?>)</small>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="card-tools">
                                        <span class="badge <?php echo get_phase_status_badge($phase['status']); ?>"><?php echo htmlspecialchars($phase['status']); ?></span>
                                        <button class="btn btn-xs btn-primary edit_phase" data-phase='<?php echo json_encode($phase, JSON_HEX_QUOT | JSON_HEX_APOS); ?>'><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-xs btn-danger delete_phase" data-id="<?php echo $phase['id']; ?>"><i class="fa fa-trash"></i></button>
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p><?php echo nl2br(htmlspecialchars($phase['description'])); ?></p>
                                    <div class="mt-2">
                                        <button class="btn btn-xs btn-info add_phase_activity" data-phase-id="<?php echo $phase['id']; ?>"><i class="fa fa-plus"></i> Add Activity</button>
                                    </div>

                                    <!-- Activity Items Grouped Display -->
                                    <div class="items-container mt-3">
                                        <?php 
                                        if(isset($phase_activities[$phase['id']]) && count($phase_activities[$phase['id']]) > 0):
                                            // Group activities by item_name
                                            $grouped_by_item = [];
                                            foreach($phase_activities[$phase['id']] as $activity):
                                                $item_key = !empty($activity['item_name']) ? $activity['item_name'] : '__general__';
                                                if(!isset($grouped_by_item[$item_key])):
                                                    $grouped_by_item[$item_key] = [];
                                                endif;
                                                $grouped_by_item[$item_key][] = $activity;
                                            endforeach;

                                            foreach($grouped_by_item as $item_name => $item_activities):
                                                $is_general = ($item_name === '__general__');
                                        ?>
                                            <div class="card card-outline card-info mb-3 item-card collapsed" data-item-name="<?php echo htmlspecialchars($item_name); ?>">
                                                <div class="card-header item-card-header">
                                                    <h6 class="card-title mb-0">
                                                        <?php if(!$is_general): ?>
                                                            <strong><?php echo htmlspecialchars($item_name); ?></strong>
                                                            <span class="badge badge-primary ml-2"><?php echo count($item_activities); ?> activities</span>
                                                        <?php else: ?>
                                                            <strong>General Activities</strong>
                                                            <span class="badge badge-secondary ml-2"><?php echo count($item_activities); ?> activities</span>
                                                        <?php endif; ?>
                                                        <i class="fas fa-chevron-down expand-icon"></i>
                                                    </h6>
                                                </div>
                                                <div class="card-body item-card-body">
                                                    <div class="timeline timeline-inverse">
                                                        <?php foreach($item_activities as $activity): ?>
                                                            <div>
                                                                <i class="fas fa-circle bg-info"></i>
                                                                <div class="timeline-item">
                                                                    <span class="time">
                                                                        <i class="fas fa-clock"></i> 
                                                                        <?php echo date("M d, Y h:i A", strtotime($activity['created_at'])) ?>
                                                                    </span>
                                                                    <h5 class="timeline-header d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <strong><?php echo htmlspecialchars($activity['activity_type']); ?></strong>
                                                                            <small class="text-muted"> by <?php echo htmlspecialchars($activity['author'] ?? 'System'); ?></small>
                                                                        </div>
                                                                        <div>
                                                                            <button class="btn btn-xs btn-primary edit_phase_activity" data-activity='<?php echo json_encode($activity, JSON_HEX_QUOT | JSON_HEX_APOS); ?>'><i class="fa fa-edit"></i></button>
                                                                            <button class="btn btn-xs btn-danger delete_phase_activity" data-id="<?php echo $activity['id']; ?>"><i class="fa fa-trash"></i></button>
                                                                        </div>
                                                                    </h5>
                                                                    <div class="timeline-body">
                                                                        <?php echo nl2br(htmlspecialchars($activity['activity_description'])); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php 
                                            endforeach;
                                        else: 
                                        ?>
                                            <div class="text-center text-muted small">No activities logged for this phase yet.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">No phases have been added to this project yet.</p>
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

    </div>
</div>

<!-- Phase Modal -->
<div class="modal fade" id="phase_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Project Phase</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="phase-form">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="project_id" value="<?php echo $id; ?>">
                    <div class="form-group">
                        <label for="name" class="control-label">Phase Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="description" class="control-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="start_date" class="control-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="end_date" class="control-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="no_of_days" class="control-label">No. of Days</label>
                            <input type="number" id="no_of_days" class="form-control" min="1">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="status" class="control-label">Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option>Pending</option>
                                <option>In Progress</option>
                                <option>Completed</option>
                                <option>On Hold</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

                <button type="submit" class="btn btn-primary" form="phase-form">Save Phase</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Phase Activity Modal -->
<div class="modal fade" id="phase_activity_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Phase Activity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="phase-activity-form">
                    <input type="hidden" name="id" value="">
                    <input type="hidden" name="phase_id" value="">
                    <div class="form-group">
                        <label for="log_type" class="control-label">Log Type</label>
                        <select id="log_type" class="form-control">
                            <option value="general">General Activity</option>
                            <option value="item">Item-Specific Activity</option>
                        </select>
                    </div>
                    <div class="form-group" id="item_name_group" style="display: none;">
                        <label for="item_name" class="control-label">Item Name</label>
                        <!-- Changed to a select to allow searching and adding new tags -->
                        <select name="item_name" id="item_name" class="form-control"></select>
                    </div>
                    <div class="form-group">
                        <label for="activity_type" class="control-label">Activity</label>
                        <select name="activity_type" id="activity_type" class="form-control select2" required>
                            <optgroup label="General Activities">
                                <option>Note</option>
                                <option>Email</option>
                                <option>Call</option>
                                <option>Meeting</option>
                            </optgroup>
                            <optgroup label="Item Activities">
                                <option>Order Placed</option>
                                <option>Dispatched</option>
                                <option>Received</option>
                                <option>Installed</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="activity_description" class="control-label">Activity Description</label>
                        <textarea name="activity_description" id="activity_description" class="form-control" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">

                <button type="submit" class="btn btn-primary" form="phase-activity-form">Save Activity</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>



<script>
$(document).ready(function(){
    // Initialize select2 for activity type
    $('#activity_type').select2({
        dropdownParent: $('#phase_activity_modal')
    });

    // Initialize select2 for item name with tagging and AJAX
    $('#item_name').select2({
        placeholder: 'Select or type an item name',
        dropdownParent: $('#phase_activity_modal'),
        tags: true, // Allow creating new tags
        ajax: {
            url: _base_url_ + "classes/Master.php?f=get_project_items_for_activity",
            method: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    project_id: "<?php echo $id; ?>",
                    q: params.term // Pass search term
                };
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    });
    // --- Project Phase Management (Layout Only) ---

    // Make entire phase header clickable for collapse/expand
    $('#phases_container').on('click', '.card-header', function(e) {
        // Prevent collapse when clicking on action buttons
        if ($(e.target).closest('.btn').length === 0) {
            $(this).find('[data-card-widget="collapse"]').click();
        }
    });

    // Show Add Phase Modal
    $('#add_phase_btn').click(function(){
        var form = $('#phase-form');
        form[0].reset();

        form.find('[name="id"]').val('');
        $('#phase_modal .modal-title').text('Add New Project Phase');
        $('#phase_modal').modal('show');
    });

    // Show Edit Phase Modal (event delegation for future dynamic content)
    $(document).on('click', '.edit_phase', function(){
        var phase = $(this).data('phase');
        var form = $('#phase-form');
        form[0].reset();


        // Populate the form with the phase data
        form.find('[name="id"]').val(phase.id);
        form.find('[name="name"]').val(phase.name);
        form.find('[name="description"]').val(phase.description);
        form.find('[name="start_date"]').val(phase.start_date);
        form.find('[name="end_date"]').val(phase.end_date);
        form.find('[name="status"]').val(phase.status);
        calculateDays(); // Calculate days when opening the modal

        $('#phase_modal .modal-title').text('Edit Project Phase');
        $('#phase_modal').modal('show');
    });

    // Handle Phase Form Submission
    $('#phase-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_project_phase",
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

    // Handle Phase Deletion
    $(document).on('click', '.delete_phase', function(){
        _conf("Are you sure you want to delete this phase?", "delete_project_phase", [$(this).data('id')]);
    });

    // --- Dynamic Date Calculation for Phase Modal ---
    var startDateInput = $('#phase_modal #start_date');
    var endDateInput = $('#phase_modal #end_date');
    var daysInput = $('#phase_modal #no_of_days');

    function calculateDays() {
        var start = startDateInput.val();
        var end = endDateInput.val();
        if (start && end) {
            var startDate = new Date(start);
            var endDate = new Date(end);
            if (endDate < startDate) {
                daysInput.val('');
                return;
            }
            var timeDiff = endDate.getTime() - startDate.getTime();
            var dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Inclusive of start and end day
            daysInput.val(dayDiff);
        }
    }

    function calculateEndDate() {
        var start = startDateInput.val();
        var days = parseInt(daysInput.val());
        if (start && days > 0) {
            var startDate = new Date(start);
            startDate.setDate(startDate.getDate() + days - 1); // Subtract 1 to be inclusive
            endDateInput.val(startDate.toISOString().split('T')[0]);
        }
    }

    function calculateStartDate() {
        var end = endDateInput.val();
        var days = parseInt(daysInput.val());
        if (end && days > 0) {
            var endDate = new Date(end);
            endDate.setDate(endDate.getDate() - (days - 1)); // Subtract 1 to be inclusive
            startDateInput.val(endDate.toISOString().split('T')[0]);
        }
    }

    startDateInput.on('change', calculateDays);
    endDateInput.on('change', calculateDays);

    daysInput.on('input', function() {
        // Prioritize calculating end date if start date is present
        if (startDateInput.val()) calculateEndDate();
        else if (endDateInput.val()) calculateStartDate();
    });

    // --- Item Card Click Toggle Behavior ---
    
    // Click to toggle expand/collapse
    $(document).on('click', '.item-card-header', function(e) {
        // Prevent event from bubbling if clicking on buttons
        if ($(e.target).closest('.btn').length > 0) {
            return;
        }
        
        var itemCard = $(this).closest('.item-card');
        itemCard.toggleClass('collapsed expanded');
    });

    // --- Item Card Click Toggle Behavior ---
    $(document).on('click', '.add_phase_activity', function() {
        var form = $('#phase-activity-form');
        form[0].reset();
        form.find('[name="id"]').val('');
        $('#item_name').val(null).trigger('change');
        $('#log_type').val('general').trigger('change');

        var phaseId = $(this).data('phase-id');
        form.find('[name="phase_id"]').val(phaseId);
        $('#phase_activity_modal .modal-title').text('Add Phase Activity');
        $('#phase_activity_modal').modal('show');
    });

    // Show Edit Phase Activity Modal
    $(document).on('click', '.edit_phase_activity', function() {
        var activity = $(this).data('activity');
        var form = $('#phase-activity-form');
        form[0].reset();
        $('#item_name').val(null).trigger('change');

        form.find('[name="id"]').val(activity.id);
        form.find('[name="phase_id"]').val(activity.phase_id);
        form.find('[name="activity_type"]').val(activity.activity_type).trigger('change');
        form.find('[name="activity_description"]').val(activity.activity_description);

        if (activity.item_name) {
            $('#log_type').val('item').trigger('change');
            // Check if the option exists, if not, create it
            if ($('#item_name').find("option[value='" + activity.item_name + "']").length) {
                $('#item_name').val(activity.item_name).trigger('change');
            } else { 
                var newOption = new Option(activity.item_name, activity.item_name, true, true);
                $('#item_name').append(newOption).trigger('change');
            }
        } else {
            $('#log_type').val('general').trigger('change');
        }

        $('#phase_activity_modal .modal-title').text('Edit Phase Activity');
        $('#phase_activity_modal').modal('show');
    });

    $('#log_type').on('change', function() {
        $('#item_name_group').toggle($(this).val() === 'item');
        if ($(this).val() !== 'item') {
            // Clear item selection when switching to general
            $('#item_name').val(null).trigger('change');
        }
    });

    // Handle Phase Activity Form Submission
    $('#phase-activity-form').submit(function(e){
        e.preventDefault();
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=save_phase_activity",
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

    // Handle Phase Activity Deletion
    $(document).on('click', '.delete_phase_activity', function(){
        _conf("Are you sure you want to delete this activity?", "delete_phase_activity", [$(this).data('id')]);
    });






    // Handle Project Deletion
    $('.delete_project').click(function(){
        _conf("Are you sure you want to delete this entire project and all its data? This action cannot be undone.", "delete_project", [$(this).data('id')]);
    });
});

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

function delete_project_phase($id){
    start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_project_phase",
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
                alert_toast(resp.msg || "An error occurred.", 'error');
				end_loader();
			}
		}
	})
}

function delete_phase_activity($id){
    start_loader();
	$.ajax({
		url:_base_url_+"classes/Master.php?f=delete_phase_activity",
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
                alert_toast(resp.msg || "An error occurred.", 'error');
				end_loader();
			}
		}
	})
}


<?php
// Helper function to determine badge color for phase status
function get_phase_status_badge($status){
    switch(strtolower($status)){
        case 'pending':
            return 'badge-secondary';
        case 'in progress':
            return 'badge-primary';
        case 'completed':
            return 'badge-success';
        case 'on hold':
            return 'badge-warning';
        default:
            return 'badge-light';
    }
}
?>
</script>
