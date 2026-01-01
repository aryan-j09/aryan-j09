<?php
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT t.*, 
        a.username as assigned_by_name,
        b.username as assigned_to_name,
        p.name as project_name,
        p.id as project_id
        FROM `tasks` t 
        LEFT JOIN users a on a.id = t.assigned_by 
        LEFT JOIN users b on b.id = t.assigned_to 
        LEFT JOIN project_planner p on p.id = t.project_id
        where t.id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Task Details</h3>
        <div class="card-tools">
            <button class="btn btn-flat btn-primary edit_task" data-id="<?php echo isset($id) ? $id : '' ?>">Edit</button>
            <a class="btn btn-flat btn-default" href="?page=tasks">Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label text-muted">Title</label>
                    <div><?php echo isset($title) ? $title : '' ?></div>
                </div>
                <div class="col-md-6">
                    <label class="control-label text-muted">Status</label>
                    <div>
                        <?php if($status == 'pending'): ?>
                            <span class="badge badge-secondary">Pending</span>
                        <?php elseif($status == 'in_progress'): ?>
                            <span class="badge badge-primary">In Progress</span>
                        <?php elseif($status == 'completed'): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Cancelled</span>
                        <?php endif; ?>
                        <?php if(isset($status_updated_at)): ?>
                            <small class="text-muted ml-2">
                                (Updated: <?php echo date("F j, Y h:i A", strtotime($status_updated_at)) ?>)
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label text-muted">Assigned To</label>
                    <div><?php echo isset($assigned_to_name) ? $assigned_to_name : '' ?></div>
                </div>
                <div class="col-md-6">
                    <label class="control-label text-muted">Assigned By</label>
                    <div><?php echo isset($assigned_by_name) ? $assigned_by_name : '' ?></div>
                </div>
            </div>
            <?php if(isset($project_id) && !empty($project_id)): ?>
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label text-muted">Related Project</label>
                    <div>
                        <a href="?page=project_planner2/view_project&id=<?php echo $project_id ?>" class="btn btn-sm btn-info">
                            <i class="fa fa-link"></i> <?php echo isset($project_name) ? $project_name : 'View Project' ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label text-muted">Created On</label>
                    <div><?php echo isset($created_at) ? date("F j, Y h:i A", strtotime($created_at)) : '' ?></div>
                </div>
                <div class="col-md-6">
                    <label class="control-label text-muted">Due Date</label>
                    <div><?php echo isset($due_date) ? date("F j, Y h:i A", strtotime($due_date)) : '' ?></div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label class="control-label text-muted">Priority</label>
                    <div>
                        <?php if($priority == 'high'): ?>
                            <span class="badge badge-danger">High</span>
                        <?php elseif($priority == 'medium'): ?>
                            <span class="badge badge-warning">Medium</span>
                        <?php else: ?>
                            <span class="badge badge-info">Low</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <label class="control-label text-muted">Description</label>
                    <div><?php echo isset($description) ? nl2br($description) : '' ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    // Handle edit button click
    $('.edit_task').click(function(){
        uni_modal("<i class='fa fa-edit'></i> Edit Task", "tasks/manage_task.php?id="+$(this).attr('data-id'), "large")
    })
})
</script>