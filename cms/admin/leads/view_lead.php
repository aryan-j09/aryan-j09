<?php
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM leads where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    }
}
?>

<?php
if(isset($_SESSION['success_msg'])){
    echo "<script>
        $(document).ready(function(){
            alert_toast('".$_SESSION['success_msg']."','success');
        });
    </script>";
    unset($_SESSION['success_msg']);
}
?>

<div class="row">
    <!-- Activity Log Section (Center) -->
    <div class="col-md-8">
        <!-- Activity Timeline with Documents -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Activity Log</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#activityModal">
                        <i class="fas fa-plus"></i> Log Activity
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php 
                    $activities = $conn->query("SELECT a.*, u.firstname, u.lastname 
                                            FROM lead_activities a 
                                            LEFT JOIN users u ON u.id = a.created_by 
                                            WHERE a.lead_id = '{$id}' 
                                            ORDER BY a.created_at DESC");
                    while($row = $activities->fetch_assoc()):
                        // Get documents for this activity
                        $docs = $conn->query("SELECT * FROM lead_documents WHERE activity_id = '{$row['id']}'");
                    ?>
                    <div>
                        <i class="fas fa-<?php echo getActivityIcon($row['activity_type']) ?> bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time">
                                <i class="fas fa-clock"></i> 
                                <?php echo date("M d, Y h:i A", strtotime($row['created_at'])) ?>
                            </span>
                            <h3 class="timeline-header d-flex justify-content-between align-items-center">
                                <div>
                                    <?php echo ucfirst($row['activity_type']) ?> 
                                    by <?php echo $row['firstname'].' '.$row['lastname'] ?>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-xs btn-info edit_activity" 
                                            data-id="<?php echo $row['id'] ?>"
                                            data-activity_type="<?php echo $row['activity_type'] ?>"
                                            data-description="<?php echo htmlspecialchars($row['description']) ?>"
                                            data-next_followup="<?php echo $row['next_followup'] ?>"
                                            data-created_at="<?php echo $row['created_at'] ?>"
                                            data-time_from="<?php echo $row['time_from'] ?>"
                                            data-time_to="<?php echo $row['time_to'] ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-danger delete_activity" data-id="<?php echo $row['id'] ?>" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-warning assign-task-btn"
                                        data-description="<?php echo htmlspecialchars($row['description'], ENT_QUOTES) ?>"
                                        title="Assign Task">
                                        <i class="fas fa-tasks"></i>
                                    </button>
                                </div>
                            </h3>
                            <div class="timeline-body">
                                <?php echo nl2br($row['description']) ?>
                                
                                <?php if(!empty($row['time_from']) && !empty($row['time_to'])): ?>
                                <div class="mt-2">
                                    <span class="badge badge-info">
                                        <i class="fas fa-clock"></i> 
                                        Time: <?php echo date("h:i A", strtotime($row['time_from'])) ?> - 
                                              <?php echo date("h:i A", strtotime($row['time_to'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($row['next_followup'])): ?>
                                <div class="mt-2">
                                    <span class="badge badge-info">
                                        <i class="fas fa-calendar"></i> 
                                        Next Follow-up: <?php echo date("F d, Y h:i A", strtotime($row['next_followup'])) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if($docs->num_rows > 0): ?>
                                    <div class="mt-2">
                                        <p class="mb-1"><strong>Attached Documents:</strong></p>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Document Type</th>
                                                        <th>Description</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($doc = $docs->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo ucfirst($doc['document_type']) ?></td>
                                                        <td><?php echo $doc['document_description'] ?></td>
                                                        <td>
                                                            <a href="<?php echo base_url.$doc['file_path'] ?>" 
                                                               class="btn btn-xs btn-info" 
                                                               target="_blank">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                            <a href="<?php echo base_url.$doc['file_path'] ?>" 
                                                               class="btn btn-xs btn-success" 
                                                               download="<?php echo $doc['file_name'] ?>">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Details Section (Right) -->
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Lead Details</h3>
                <div class="card-tools">
                    <a href="<?php echo base_url ?>admin/?page=leads" class="btn btn-tool" title="Return to CRM">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="./?page=leads/manage_lead&id=<?php echo $id ?>" class="btn btn-tool" title="Edit Details">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn btn-tool delete_lead" data-id="<?php echo $id ?>" title="Delete Lead">
                        <i class="fas fa-trash text-danger"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <tr>
                        <th class="px-3 py-2">Company Name</th>
                        <td class="px-3 py-2"><?php echo $company_name ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Contact Person</th>
                        <td class="px-3 py-2"><?php echo $contact_person ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Email</th>
                        <td class="px-3 py-2"><?php echo $email ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Phone</th>
                        <td class="px-3 py-2"><?php echo $phone ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Status</th>
                        <td class="px-3 py-2">
                            <span class="badge badge-<?php echo getStatusColor($status) ?>">
                                <?php echo ucfirst($status) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Source</th>
                        <td class="px-3 py-2"><?php echo $source ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Latest Follow-up</th>
                        <td class="px-3 py-2">
                            <?php 
                            $latest_followup = $conn->query("SELECT next_followup 
                                                           FROM lead_activities 
                                                           WHERE lead_id = '{$id}' 
                                                           AND next_followup IS NOT NULL 
                                                           ORDER BY next_followup DESC 
                                                           LIMIT 1")->fetch_assoc();
                            echo isset($latest_followup['next_followup']) 
                                 ? date("F d, Y h:i A", strtotime($latest_followup['next_followup'])) 
                                 : 'N/A';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Created On</th>
                        <td class="px-3 py-2"><?php echo date("F d, Y h:i A", strtotime($created_at)) ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">City</th>
                        <td class="px-3 py-2"><?php echo $city ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Address</th>
                        <td class="px-3 py-2"><?php echo $address ?></td>
                    </tr>
                    <tr>
                        <th class="px-3 py-2">Notes</th>
                        <td class="px-3 py-2"><?php echo nl2br($notes) ?></td>
                    </tr>
                </table>

                <?php if($status != 'converted' && $status != 'closed'): ?>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-block convert-to-client" data-id="<?php echo $id ?>">
                                    <i class="fas fa-user-plus"></i> Convert to Client
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-secondary btn-block mark-as-closed" data-id="<?php echo $id ?>">
                                    <i class="fas fa-times-circle"></i> Mark as Closed
                                </button>
                            </div>
                        </div>
                        <!--<div class="row mt-3">
                            <div class="col-8 mx-auto">
                                <a href="<?php echo base_url ?>admin/?page=quotations/manage_quote&lead_id=<?php echo $id; ?>"
                                   class="btn btn-success btn-block"
                                   title="Generate Quotation">
                                    <i class="fas fa-file-invoice"></i> Generate Quotation
                                </a>
                            </div>
                        </div>-->
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Activity Modal -->
<div class="modal fade" id="activityModal" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Log Activity</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="activity-form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="lead_id" value="<?php echo $id ?>">
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-4">
                                <label>Activity Date & Time</label>
                                <input type="datetime-local" name="created_at" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                                <small class="text-muted">You can select past dates for logging previous activities</small>
                            </div>
                            <div class="col-md-4">
                                <label>Activity Type</label>
                                <select name="activity_type" id="activity_type" class="form-control" required>
                                    <option value="note">Note</option>
                                    <option value="email">Email</option>
                                    <option value="call">Call</option>
                                    <option value="meeting">Meeting</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Next Follow-up Date</label>
                                <input type="datetime-local" 
                                       name="next_followup" 
                                       class="form-control"
                                       min="<?php echo date('Y-m-d\TH:i'); ?>">
                                <small class="text-muted">Leave empty if no follow-up needed</small>
                            </div>                            
                        </div>
                    </div>
                    <div class="form-group time-range" style="display:none;">
                        <label>Time Range</label>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="small">From</label>
                                <input type="time" name="time_from" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="small">To</label>
                                <input type="time" name="time_to" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Attachments</label>
                        <div id="document-container">
                            <div class="document-row mb-2">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <select name="document_type[]" class="form-control form-control-sm">
                                            <option value="quotation">Quotation</option>
                                            <option value="email">Email</option>
                                            <option value="specification">Specification</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="document_description[]" 
                                               class="form-control form-control-sm" 
                                               placeholder="Document description">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="file" name="documents[]" class="form-control-file">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-danger remove-document">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-info mt-2" id="add-document">
                            <i class="fas fa-plus"></i> Add Another Document
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lead Modal -->
<div class="modal fade" id="leadModal" tabindex="-1" role="dialog" aria-labelledby="leadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadModalLabel">Manage Lead</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

<?php
function getStatusColor($status) {
    switch($status) {
        case 'new': return 'primary';
        case 'contacted': return 'info';
        case 'negotiation': return 'purple';
        case 'converted': return 'success';
        case 'lost': return 'danger';
        default: return 'secondary';
    }
}

function getActivityIcon($type) {
    switch($type) {
        case 'note': return 'sticky-note';
        case 'email': return 'envelope';
        case 'call': return 'phone';
        case 'meeting': return 'users';
        case 'status_change': return 'exchange-alt';
        default: return 'circle';
    }
}
?>

<script>
$(document).ready(function(){
    // Activity type change handler
    $('#activity_type').change(function(){
        var type = $(this).val();
        if(type === 'call' || type === 'meeting'){
            $('.time-range').show();
            $('.time-range input').prop('required', true);
        } else {
            $('.time-range').hide();
            $('.time-range input').prop('required', false);
        }
    });
    
    // Document handling
    $('#add-document').click(function(){
        var newRow = $('.document-row:first').clone();
        newRow.find('input').val('');
        newRow.find('select').prop('selectedIndex', 0);
        $('#document-container').append(newRow);
    });

    $(document).on('click', '.remove-document', function(){
        if($('.document-row').length > 1) {
            $(this).closest('.document-row').remove();
        } else {
            alert_toast("You must have at least one document row", 'warning');
        }
    });

    $('.convert-to-client').click(function(){
        var id = $(this).data('id');
        _conf("Are you sure to convert this lead to client?", "convertToClient", [id]);
    });

    $('.mark-as-closed').click(function(){
        _conf("Are you sure to mark this lead as closed?", "markAsClosed", [$(this).data('id')]);
    });

    $('.delete_lead').click(function(){
        _conf("Are you sure you want to delete this lead?", "delete_lead", [$(this).data('id')]);
    });

    // Single form submission handler
    $('#activity-form').submit(function(e){
        e.preventDefault();
        start_loader();
        
        var formData = new FormData(this);
        
        // Convert datetime-local to MySQL format with timezone handling
        var createdAt = formData.get('created_at');
        if(createdAt) {
            // Create date object and adjust for local timezone
            var date = new Date(createdAt);
            
            // Format date in YYYY-MM-DD HH:mm:ss format
            var year = date.getFullYear();
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var day = date.getDate().toString().padStart(2, '0');
            var hours = date.getHours().toString().padStart(2, '0');
            var minutes = date.getMinutes().toString().padStart(2, '0');
            var seconds = date.getSeconds().toString().padStart(2, '0');
            
            var formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            formData.set('created_at', formattedDate);
        }
        
        // Always use log_activity endpoint
        var url = _base_url_ + "classes/Master.php?f=log_activity";

        $.ajax({
            url: url,
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error: err=>{
                console.log(err);
                alert_toast("An error occurred",'error');
                end_loader();
            },
            success:function(resp){
                if(resp.status == 'success'){
                    $('#activityModal').modal('hide');
                    location.reload();
                } else {
                    alert_toast(resp.msg,'error');
                    end_loader();
                }
            }
        });
    });

    // Delete activity handler
    $('.delete_activity').click(function(){
        _conf("Are you sure to delete this activity?", "delete_activity", [$(this).attr('data-id')])
    })

    // Edit activity handler
    $('.edit_activity').click(function(){
        var id = $(this).data('id');
        var activity_type = $(this).data('activity_type');
        var description = $(this).data('description');
        var next_followup = $(this).data('next_followup');
        var created_at = $(this).data('created_at');
        var time_from = $(this).data('time_from');
        var time_to = $(this).data('time_to');

        // Convert created_at to datetime-local format with timezone handling
        if(created_at) {
            var date = new Date(created_at + ' UTC'); // Treat the date as UTC
            var localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
            var createdAtFormatted = localDate.toISOString().slice(0, 16);
            $('input[name="created_at"]').val(createdAtFormatted);
        }

        // Convert next_followup with timezone handling
        if(next_followup) {
            var date = new Date(next_followup + ' UTC');
            var localDate = new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
            var nextFollowupFormatted = localDate.toISOString().slice(0, 16);
            $('input[name="next_followup"]').val(nextFollowupFormatted);
        }

        // Update form title
        $('.modal-title').text('Edit Activity');
        
        // Add hidden activity_id field and mark form as edit
        $('#activity-form').append('<input type="hidden" name="activity_id" value="' + id + '">');
        $('#activity-form').attr('data-edit', 'true');
        
        // Remove min attribute from next_followup input when editing
        $('input[name="next_followup"]').removeAttr('min');
        
        // Populate the form
        $('#activity_type').val(activity_type).trigger('change');
        $('textarea[name="description"]').val(description);
        $('input[name="activity_datetime"]').val(createdAtFormatted);
        $('input[name="next_followup"]').val(nextFollowupFormatted);
        
        // Set time range if exists
        if(time_from) $('input[name="time_from"]').val(time_from);
        if(time_to) $('input[name="time_to"]').val(time_to);
        
        // Show modal
        $('#activityModal').modal('show');
    });

    // Add modal hidden event handler to reset form
    $('#activityModal').on('hidden.bs.modal', function () {
        $('.modal-title').text('Log Activity');
        $('#activity-form')[0].reset();
        $('#activity-form').removeAttr('data-edit');
        $('input[name="activity_id"]').remove();
        $('.document-row:not(:first)').remove();
        $('.document-row:first input').val('');
        
        // Restore min attribute for next_followup when adding new activity
        $('input[name="next_followup"]').attr('min', '<?php echo date('Y-m-d\TH:i'); ?>');
    });
});

function delete_lead(id){
    start_loader();
    $.ajax({
        url:_base_url_+"classes/Master.php?f=delete_lead",
        method:"POST",
        data:{id: id},
        dataType:"json",
        error:err=>{
            console.log(err);
            alert_toast("An error occurred.", 'error');
            end_loader();
        },
        success:function(resp){
            if(resp.status == 'success'){
                location.href = './?page=leads';
            }else{
                alert_toast(resp.msg || "An error occurred.", 'error');
            }
            end_loader();
        }
    });
}

function convertToClient(id){
    start_loader();
    $.ajax({
        url: _base_url_+"classes/Master.php?f=save_lead",
        method: "POST",
        data: {
            id: id,
            status: 'converted',
            update_status_only: true,
            company_name: '<?php echo $company_name ?>',
            contact_person: '<?php echo $contact_person ?>'
        },
        dataType: "json",
        error: err=>{
            console.log(err);
            alert_toast("An error occurred.", 'error');
            end_loader();
        },
        success: function(resp){
            if(resp.status == 'success'){
                location.href = '<?php echo base_url ?>admin/?page=clients/manage_client&convert_from_lead=' + id;
            } else {
                alert_toast(resp.msg || "An error occurred.", 'error');
                end_loader();
            }
        }
    });
}

function markAsClosed(id){
    start_loader();
    $.ajax({
        url: _base_url_+"classes/Master.php?f=save_lead",
        method: "POST",
        data: {
            id: id,
            status: 'closed',
            update_status_only: true,  // Add this flag
            company_name: '<?php echo $company_name ?>', // Add existing values
            contact_person: '<?php echo $contact_person ?>'
        },
        dataType: "json",
        error: err=>{
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
}

function delete_activity($id){
    start_loader();
    $.ajax({
        url: _base_url_+"classes/Master.php?f=delete_activity",
        method: "POST",
        data: {id: $id},
        dataType: "json",
        error: err=>{
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
}

function manage_lead(id = ''){
    start_loader();
    $.ajax({
        url: "./?page=leads/manage_lead",
        method: "POST",
        data: {id: id},
        dataType: 'html',
        success:function(resp){
            if(resp){
                // Clear previous content first
                $('#leadModal .modal-body').empty();
                // Add new content
                $('#leadModal .modal-body').html(resp);
                // Remove any duplicate modals and backdrops
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                // Show modal
                $('#leadModal').modal('show');
            }
        },
        complete:function(){
            end_loader();
        }
    });
}

$(document).on('click', '.assign-task-btn', function(){
    var desc = $(this).data('description');
    // Encode description for URL
    var descParam = encodeURIComponent(desc);
    // Open manage_task in new tab with description pre-filled
    window.location.href = '<?php echo base_url ?>admin/?page=tasks/manage_task&description=' + descParam;
});
</script>

<style>
.modal-body {
    padding: 20px;
}
.document-row {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}
.document-row:last-child {
    border-bottom: none;
}
.modal-lg {
    max-width: 80%; /* Makes modal even wider */
}
</style>