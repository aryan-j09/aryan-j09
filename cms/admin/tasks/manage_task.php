<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * FROM tasks where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    }
}
?>
<div class="container-fluid">
    <form action="" id="task-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-control" value="<?php echo isset($title) ? $title : '' ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" cols="30" rows="5" class="form-control"><?php echo isset($description) ? $description : '' ?></textarea>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="assigned_to">Assign To</label>
                    <select name="assigned_to" id="assigned_to" class="form-control select2" required>
                        <option value="">Select User</option>
                        <?php 
                        // Modified query to get ALL users
                        $users = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
                        while($row = $users->fetch_assoc()):
                        ?>
                        <option value="<?php echo $row['id'] ?>" <?php echo isset($assigned_to) && $assigned_to == $row['id'] ? 'selected' : '' ?>><?php echo $row['username'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="project_id">Project (Optional)</label>
                    <select name="project_id" id="project_id" class="form-control select2">
                        <option value="">-- No Project --</option>
                        <?php 
                        $projects = $conn->query("SELECT id, name FROM project_planner ORDER BY name ASC");
                        while($row = $projects->fetch_assoc()):
                        ?>
                        <option value="<?php echo $row['id'] ?>" <?php echo isset($project_id) && $project_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group row">
                    <div class="col-6">
                    <label for="due_date">Due Date</label>
                    <input type="datetime-local" name="due_date" id="due_date" class="form-control" value="<?php echo isset($due_date) ? date('Y-m-d\TH:i', strtotime($due_date)) : '' ?>" required>   
                    </div>   
                    <div class="col-6">                                      <label for="status">Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="pending" <?php echo isset($status) && $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?php echo isset($status) && $status == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?php echo isset($status) && $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?php echo isset($status) && $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority" class="form-control" required>
                        <option value="low" <?php echo isset($priority) && $priority == 'low' ? 'selected' : '' ?>>
                            Low
                        </option>
                        <option value="medium" <?php echo isset($priority) && $priority == 'medium' ? 'selected' : '' ?>>
                            Medium
                        </option>
                        <option value="high" <?php echo isset($priority) && $priority == 'high' ? 'selected' : '' ?>>
                            High
                        </option>
                    </select>
                </div>
            </div>
        </div>
        <?php if(!isset($id)): ?>
            <input type="hidden" name="assigned_by" value="<?php echo $_SESSION['userdata']['id'] ?>">
        <?php endif; ?>
    </form>
</div>
<script>
$(function(){
    $('#task-form').submit(function(e){
        e.preventDefault();
        var _this = $(this)
        $('.err-msg').remove();
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=save_task",
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            dataType: 'json',
            error:err=>{
                console.log(err)
                alert_toast("An error occurred",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp =='object' && resp.status == 'success'){
                    // Let SSE handle the badge update
                    location.href = "./?page=tasks";
                }else if(resp.status == 'failed' && !!resp.msg){
                    var el = $('<div>')
                        el.addClass("alert alert-danger err-msg").text(resp.msg)
                        _this.prepend(el)
                        el.show('slow')
                        $("html, body").animate({ scrollTop: _this.closest('.card').offset().top }, "fast");
                }else{
                    alert_toast("An error occurred",'error');
                }
                end_loader()
            }
        })
    })
})

$(document).ready(function(){
    // Only prefill if not editing (no id in URL or PHP variable)
    var isEdit = <?php echo isset($id) ? 'true' : 'false'; ?>;
    console.log('Is edit mode:', isEdit); // Debug log
    if(!isEdit){
        // Check for global variable first (for daily task assignment)
        if(typeof window.dailyTaskTitle !== 'undefined' && window.dailyTaskTitle){
            console.log('Global daily task title found:', window.dailyTaskTitle); // Debug log
            $('#title').val(window.dailyTaskTitle);
            console.log('Title field value after setting from global:', $('#title').val()); // Debug log
            // Clear the global variable after using it
            window.dailyTaskTitle = null;
        } else {
            // Fallback to URL parameters
            const params = new URLSearchParams(window.location.search);
            console.log('URL params:', window.location.search); // Debug log
            if(params.has('description')){
                // Decode and set the description
                $('#description').val(decodeURIComponent(params.get('description')));
            }
            if(params.has('title')){
                console.log('Title param found:', params.get('title')); // Debug log
                var titleValue = decodeURIComponent(params.get('title'));
                console.log('Decoded title:', titleValue); // Debug log
                $('#title').val(titleValue);
                console.log('Title field value after setting:', $('#title').val()); // Debug log
            }
        }
        if(typeof window.dailyTaskId !== 'undefined' && window.dailyTaskId){
            if($('#daily_task_id').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'daily_task_id',
                    name: 'daily_task_id',
                    value: window.dailyTaskId
                }).appendTo('#task-form');
            }
            window.dailyTaskId = null;
        }
    }
});
</script>