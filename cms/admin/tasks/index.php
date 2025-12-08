<?php
// --- DAILY TASKS LOGIC ---
$user_id = $_SESSION['userdata']['id'];
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Add new daily task
if (isset($_POST['add_daily_task']) && !empty($_POST['task'])) {
    $task = $conn->real_escape_string($_POST['task']);
    $conn->query("INSERT INTO daily_tasks (user_id, task, task_date) VALUES ('{$user_id}', '{$task}', '{$today}')");
    echo "<meta http-equiv='refresh' content='0;url=?page=tasks#daily-tasks'>";
    exit;
}

// Manual delete daily task
if (isset($_POST['delete_daily_task_id'])) {
    $id = intval($_POST['delete_daily_task_id']);
    $conn->query("DELETE FROM daily_tasks WHERE id = '{$id}' AND user_id = '{$user_id}'");
    exit('ok');
}

// Mark daily task as completed (AJAX)
if (isset($_POST['complete_daily_task_id'])) {
    $id = intval($_POST['complete_daily_task_id']);
    $conn->query("UPDATE daily_tasks SET completed = 1, completed_at = NOW() WHERE id = '{$id}' AND user_id = '{$user_id}'");
    exit('ok');
}

// Fetch today's daily tasks
$daily_tasks = $conn->query("SELECT * FROM daily_tasks WHERE user_id = '{$user_id}' AND task_date = '{$today}' ORDER BY id ASC");

// Fetch today's follow-ups for this user
$today_followups = $conn->query("SELECT la.*, l.company_name 
    FROM lead_activities la 
    LEFT JOIN leads l ON la.lead_id = l.id 
    WHERE DATE(la.next_followup) <= '{$today}' 
    AND la.created_by = '{$user_id}' 
    AND (la.handled IS NULL OR la.handled = 0)
    ORDER BY la.next_followup DESC");

// For viewing a single task (existing logic)
if (isset($_GET['id']) && $_GET['id'] > 0) {
    $qry = $conn->query("SELECT t.*, 
        a.username as assigned_by_name,
        b.username as assigned_to_name 
        FROM `tasks` t 
        LEFT JOIN users a on a.id = t.assigned_by 
        LEFT JOIN users b on b.id = t.assigned_to 
        where t.id = '{$_GET['id']}'");
    if ($qry->num_rows > 0) {
        foreach ($qry->fetch_assoc() as $k => $v) {
            $$k = $v;
        }
    }
}
?>

<style>
    .custom-control-input:checked~.custom-control-label::before {
        background-color: #007bff;
        border-color: #007bff;
    }

    .bulk-actions {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        margin-bottom: 15px;
        display: none;
    }

    .custom-checkbox .custom-control-label {
        cursor: pointer;
    }

    .task-checkbox:disabled+.custom-control-label {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Tasks</h3>
        <div class="card-tools">
            <button id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Create New</button>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <!-- Add tabs for better organization -->
            <ul class="nav nav-tabs mb-3" id="taskTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" id="assigned-to-me-tab" data-toggle="tab" href="#assigned-to-me" role="tab">Assigned To Me</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="assigned-by-me-tab" data-toggle="tab" href="#assigned-by-me" role="tab">Assigned By Me</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" id="daily-tasks-tab" data-toggle="tab" href="#daily-tasks" role="tab">My Daily Tasks</a>
                </li>
            </ul>
            <div class="tab-content" id="taskTabContent">
                <!-- Tasks assigned to me -->
                <div class="tab-pane fade" id="assigned-to-me" role="tabpanel">
                    <table class="table table-bordered table-stripped" id="task-list-assigned-to">
                        <colgroup>
                            <col width="5%">
                            <col width="27%">
                            <col width="10%">
                            <col width="15%">
                            <col width="15%">
                            <col width="10%">
                            <col width="8%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Assigned By</th>
                                <th>Created on</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            $qry = $conn->query("SELECT t.*, u.username as assigned_by_name 
                                FROM `tasks` t 
                                INNER JOIN users u ON u.id = t.assigned_by 
                                WHERE t.assigned_to = '{$user_id}' 
                                ORDER BY 
                                    CASE t.priority 
                                        WHEN 'high' THEN 1 
                                        WHEN 'medium' THEN 2 
                                        WHEN 'low' THEN 3 
                                    END,
                                    t.due_date ASC");
                            while ($row = $qry->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo $row['title'] ?></td>
                                    <td><?php echo $row['assigned_by_name'] ?></td>
                                    <td><?php echo date("d-M-y h:i A", strtotime($row['date_created'])) ?></td>
                                    <td><?php echo date("d-M-y h:i A", strtotime($row['due_date'])) ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge badge-secondary">Pending</span>
                                        <?php elseif ($row['status'] == 'in_progress'): ?>
                                            <span class="badge badge-primary">In Progress</span>
                                        <?php elseif ($row['status'] == 'completed'): ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['priority'] == 'high'): ?>
                                            <span class="badge badge-danger">High</span>
                                        <?php elseif ($row['priority'] == 'medium'): ?>
                                            <span class="badge badge-warning">Medium</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td align="center">
                                        <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                            Action
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu" role="menu">
                                            <a class="dropdown-item" href="?page=tasks/view_task&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Tasks assigned by me -->
                <div class="tab-pane fade" id="assigned-by-me" role="tabpanel">
                    <table class="table table-bordered table-stripped" id="task-list-assigned-by">
                        <colgroup>
                            <col width="5%">
                            <col width="27%">
                            <col width="10%">
                            <col width="15%">
                            <col width="15%">
                            <col width="10%">
                            <col width="8%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Created on</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            $qry = $conn->query("SELECT t.*, u.username as assigned_to_name 
                                FROM `tasks` t 
                                INNER JOIN users u ON u.id = t.assigned_to 
                                WHERE t.assigned_by = '{$user_id}' 
                                ORDER BY 
                                    CASE t.priority 
                                        WHEN 'high' THEN 1 
                                        WHEN 'medium' THEN 2 
                                        WHEN 'low' THEN 3 
                                    END,
                                    t.due_date ASC");
                            while ($row = $qry->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td><?php echo $row['title'] ?></td>
                                    <td><?php echo $row['assigned_to_name'] ?></td>
                                    <td><?php echo date("d-M-y h:i A", strtotime($row['date_created'])) ?></td>
                                    <td><?php echo date("d-M-y h:i A", strtotime($row['due_date'])) ?></td>
                                    <td class="text-center">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <span class="badge badge-secondary">Pending</span>
                                        <?php elseif ($row['status'] == 'in_progress'): ?>
                                            <span class="badge badge-primary">In Progress</span>
                                        <?php elseif ($row['status'] == 'completed'): ?>
                                            <span class="badge badge-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['priority'] == 'high'): ?>
                                            <span class="badge badge-danger">High</span>
                                        <?php elseif ($row['priority'] == 'medium'): ?>
                                            <span class="badge badge-warning">Medium</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td align="center">
                                        <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                            Action
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu" role="menu">
                                            <a class="dropdown-item" href="?page=tasks/view_task&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <!-- My Daily Tasks -->
                <div class="tab-pane fade show active" id="daily-tasks" role="tabpanel">
                    <!-- Add task form -->
                    <form method="POST" class="mb-3 d-flex">
                        <input type="text" name="task" class="form-control mr-2" placeholder="Add new daily task..." required>
                        <button class="btn btn-primary" name="add_daily_task" value="1">Add</button>
                    </form>

                    <div class="row">
                        <div class="col-md-7">
                            <!-- Replace the task list section with this updated code -->
                            <ul class="list-group" id="daily-task-list">
                                <?php
                                $all_daily_tasks = $conn->query("SELECT dt.*, 
                                    CASE WHEN EXISTS (
                                        SELECT 1 FROM tasks t 
                                        WHERE t.daily_task_id = dt.id
                                    ) THEN 1 ELSE 0 END as is_assigned
                                    FROM daily_tasks dt 
                                    WHERE dt.user_id = '{$user_id}' 
                                    ORDER BY dt.completed ASC, dt.task_date DESC, dt.id DESC");

                                $has_completed = false;
                                while ($row = $all_daily_tasks->fetch_assoc()):
                                    if (!$has_completed && $row['completed']) {
                                ?>
                                        <!-- Replace the Completed Tasks header section -->
                                        <li class="list-group-item bg-light d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <span class="font-weight-bold mr-3">Completed Tasks</span>
                                                <button class="btn btn-danger btn-sm" id="bulk-delete" style="display:none;">
                                                    <i class="fa fa-trash"></i> Delete Selected (<span id="selected-count">0</span>)
                                                </button>
                                            </div>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAll">
                                                <label class="custom-control-label" for="selectAll">Select All</label>
                                            </div>
                                        </li>
                                    <?php
                                        $has_completed = true;
                                    }

                                    $task_time = strtotime($row['task_date']);
                                    $today_time = strtotime(date('Y-m-d'));
                                    $week_ago_time = strtotime('-7 days', $today_time);

                                    if ($task_time == $today_time) {
                                        $border_color = '#007bff';
                                        $border_title = 'Today\'s Task';
                                    } elseif ($task_time < $today_time && $task_time >= $week_ago_time) {
                                        $border_color = '#ffc107';
                                        $border_title = 'This Week\'s Task';
                                    } elseif ($task_time < $week_ago_time) {
                                        $border_color = '#dc3545';
                                        $border_title = 'Older Task';
                                    } else {
                                        $border_color = '#dee2e6';
                                        $border_title = '';
                                    }
                                    ?>
                                    <li class="list-group-item d-flex align-items-center"
                                        style="border-left: 4px solid <?php echo $border_color; ?>;"
                                        title="<?php echo $border_title; ?>">

                                        <?php if ($row['completed']): ?>
                                            <!-- Only show checkbox for completed tasks -->
                                            <div class="custom-control custom-checkbox mr-2">
                                                <input type="checkbox"
                                                    class="custom-control-input task-checkbox"
                                                    id="task-<?php echo $row['id']; ?>"
                                                    data-id="<?php echo $row['id']; ?>">
                                                <label class="custom-control-label" for="task-<?php echo $row['id']; ?>"></label>
                                            </div>
                                        <?php else: ?>
                                            <!-- Show complete task checkbox only for incomplete tasks -->
                                            <input type="checkbox"
                                                class="mr-2 complete-daily-task"
                                                data-id="<?php echo $row['id']; ?>">
                                        <?php endif; ?>

                                        <span style="<?php echo $row['completed'] ? 'color:gray;' : ''; ?>">
                                            <?php echo htmlspecialchars($row['task']); ?>
                                        </span>
                                        <small class="ml-2 text-dark">
                                            <?php echo date('d M Y', strtotime($row['task_date'])); ?>
                                        </small>

                                        <?php if ($row['is_assigned']): ?>
                                            <button class="btn btn-sm btn-success ml-auto" title="Task Assigned" disabled>
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-primary ml-auto manage_task"
                                                href="javascript:void(0)"
                                                data-title="<?php echo htmlspecialchars($row['task'], ENT_QUOTES) ?>"
                                                data-daily-id="<?php echo $row['id']; ?>"
                                                title="Assign Task">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger ml-1 delete_daily_task"
                                            data-id="<?php echo $row['id']; ?>" title="Delete Task">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div class="col-md-5">
                            <div class="card">
                                <div class="card-header py-2 px-3">
                                    <h6 class="mb-0">Today's Follow-ups</h6>
                                </div>
                                <ul class="list-group list-group-flush">
                                    <?php if ($today_followups->num_rows > 0): ?>
                                        <?php while ($fu = $today_followups->fetch_assoc()): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <i class="fas fa-phone mr-2 text-primary"></i>
                                                <span class="d-block">
                                                    <strong><?php echo htmlspecialchars($fu['company_name']); ?></strong>
                                                    <div class="text-muted small mt-1">
                                                        <?php echo date('d M Y', strtotime($fu['next_followup'])); ?>
                                                        <?php echo date('h:i A', strtotime($fu['next_followup'])); ?>
                                                    </div>
                                                </span>
                                                <button class="btn btn-sm btn-outline-info ml-auto view-lead-btn"
                                                    data-url="<?php echo base_url ?>admin/?page=leads/view_lead&id=<?php echo $fu['lead_id'] ?>">
                                                    View Lead
                                                </button>
                                                <a class="btn btn-sm btn-success ml-2 log-activity-btn"
                                                    href="<?php echo base_url ?>admin/?page=leads/view_lead&id=<?php echo $fu['lead_id'] ?>&log_activity=1&activity_id=<?php echo $fu['id']; ?>">
                                                    Log Activity
                                                </a>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item text-muted">No follow-ups for today.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#create_new').click(function() {
            uni_modal("<i class='fa fa-plus'></i> New Task", "tasks/manage_task.php", "large")
        })
        $('.edit_data').click(function() {
            uni_modal("<i class='fa fa-edit'></i> Edit Task", "tasks/manage_task.php?id=" + $(this).attr('data-id'), "large")
        })
        $('.delete_data').click(function() {
            var id = $(this).attr('data-id');
            _conf_sweet("Are you sure to delete this task permanently?", function(result) {
                if (result)
                    delete_task(id);
            });
        })
        // Initialize both datatables
        $('#task-list-assigned-to').dataTable();
        $('#task-list-assigned-by').dataTable();

        // Daily task completion
        $('#daily-task-list').on('change', '.complete-daily-task', function() {
            var id = $(this).data('id');
            $.post('', {
                complete_daily_task_id: id
            }, function() {
                location.reload();
            });
        });

        // Manual delete daily task
        $('#daily-task-list').on('click', '.delete_daily_task', function() {
            var id = $(this).data('id');
            _conf_sweet("Are you sure you want to delete this daily task?", function(result) {
                if (result) {
                    delete_daily_task(id);
                }
            });
        });

        $('#daily-task-list').on('click', '.manage_task', function() {
            var title = $(this).data('title');
            var dailyId = $(this).data('daily-id');
            window.dailyTaskTitle = title;
            window.dailyTaskId = dailyId;
            uni_modal("<i class='fa fa-plus'></i> New Task", "tasks/manage_task.php", "large")
        })

        // Activate correct tab if hash present
        if (window.location.hash) {
            $('.nav-link[href="' + window.location.hash + '"]').tab('show');
        }

        // Select All functionality
        $('#selectAll').change(function() {
            // Get the checked state of the "Select All" checkbox
            const isChecked = $(this).prop('checked');

            // Update all task checkboxes to match
            $('.task-checkbox').prop('checked', isChecked);

            // Update bulk actions visibility
            updateBulkActionsVisibility();
        });

        // Individual checkbox handling
        $('.task-checkbox').change(function() {
            // Check if all task checkboxes are checked
            const allChecked = $('.task-checkbox').length === $('.task-checkbox:checked').length;

            // Update the "Select All" checkbox accordingly
            $('#selectAll').prop('checked', allChecked);

            // Update bulk actions visibility
            updateBulkActionsVisibility();
        });

        // Update bulk actions toolbar visibility
        function updateBulkActionsVisibility() {
            const selectedCount = $('.task-checkbox:checked').length;
            $('#selected-count').text(selectedCount);
            $('#bulk-delete').toggle(selectedCount > 0);
        }

        // Handle bulk delete button click
        $('#bulk-delete').click(function() {
            const selectedIds = [];
            $('.task-checkbox:checked').each(function() {
                selectedIds.push($(this).data('id'));
            });

            if (selectedIds.length > 0) {
                _conf_sweet("Are you sure to delete these tasks?", function(yes) {
                    if (yes) {
                        start_loader();
                        $.ajax({
                            url: _base_url_ + "classes/Master.php?f=bulk_delete_tasks",
                            method: "POST",
                            data: {
                                task_ids: selectedIds
                            },
                            dataType: "json",
                            error: err => {
                                console.log(err);
                                alert_toast("An error occurred.", 'error');
                                end_loader();
                            },
                            success: function(resp) {
                                if (resp.status == 'success') {
                                    location.reload();
                                } else {
                                    alert_toast("An error occurred: " + resp.msg, 'error');
                                    end_loader();
                                }
                            }
                        });
                    }
                });
            } else {
                alert_toast("Please select tasks to delete", 'warning');
            }
        });

        // Helper function for confirmation dialog
        function _conf_sweet(msg, callback) {
            Swal.fire({
                title: 'Are you sure?',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33', // Corrected property name
                confirmButtonText: 'Yes, do it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    callback(true);
                }
            })
        }
    });

    function delete_task($id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_task",
            method: "POST",
            data: {
                id: $id
            },
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp) {
                if (typeof resp == 'object' && resp.status == 'success') {
                    location.reload();
                } else {
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                }
            }
        })
    }

    function delete_daily_task(id) {
        start_loader();
        $.ajax({
            url: '',
            method: 'POST',
            data: {
                delete_daily_task_id: id
            },
            dataType: 'text',
            error: function(err) {
                console.log(err);
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp) {
                window.location.hash = "#daily-tasks";
                window.location.reload();
            }
        });
    }

    // View Lead button for follow-ups (reloads location)
    $('.tab-pane#daily-tasks').on('click', '.view-lead-btn', function() {
        window.location = $(this).data('url');
    });
</script>