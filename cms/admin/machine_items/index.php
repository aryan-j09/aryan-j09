<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">List of Item</h3>
            <div class="card-tools">
                <button class="btn btn-primary btn-sm" id="create_new"><i class="fa fa-plus"></i> Create New Item</button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <colgroup>
                    <col width="5%">
                    <col width="30%">
                    <col width="45%">
                    <col width="10%">
                    <col width="10%">                    
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM `machine_list` order by `name` asc");
                    while($row = $qry->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++ ?>.</td>
                        <td><?php echo $row['name'] ?></td>
                        <td><?php echo $row['description'] ?></td>
                        <td class="text-center">
                            <?php if($row['status'] == 1): ?>
                                <span class="badge badge-success rounded-pill">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger rounded-pill">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Actions
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>                                    
                                </div>
                            </div>
                        </td>
                    </tr>	
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Structure -->
<div class="modal fade" id="uni_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>            
        </div>
    </div>
</div>

<script>
function start_load() {
    $('body').prepend('<div id="preloader2"></div>');
}

function end_load() {
    $('#preloader2').fadeOut('fast', function() {
        $(this).remove();
    });
}

$(document).ready(function(){
    $('#create_new').click(function(){
        uni_modal("<i class='fa fa-plus'></i> Add New Machine Item","machine_items/manage_item.php","mid-large")
    })
    $('.edit_data').click(function(){
        uni_modal("<i class='fa fa-edit'></i> Edit Machine Item Details","machine_items/manage_item.php?id="+$(this).attr('data-id'),"mid-large")
    })
    $('.view_data').click(function(){
        uni_modal("<i class='fa fa-eye'></i> View Machine Item Details","machine_items/view_item.php?id="+$(this).attr('data-id'),"mid-large")
    })
    $('.delete_data').click(function(){
        _conf("Are you sure to delete this Machine Item permanently?","delete_machine_item",[$(this).attr('data-id')])
    })
    $('.table td,.table th').addClass('py-1 px-2 align-middle')
    $('.table').dataTable();
})

function delete_machine_item($id){
    start_load();
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=delete_machine_item",
        method: 'POST',
        data: {id: $id},
        success: function(resp){
            end_load();
            console.log(resp); // Debugging statement
            if(resp && resp.status == 1){                
                setTimeout(function(){
                    location.reload();
                }, 0);
            } else {
                alert_toast("An error occurred", 'error');
            }
        },
        error: function(err){
            end_load();
            console.log(err); // Debugging statement
            alert_toast("An error occurred", 'error');
        }
    });
}

function uni_modal(title, url, size = ""){
    start_load();
    $.ajax({
        url: url,
        error: err => {
            console.log(err);
            alert("An error occurred");
        },
        success: function(resp){
            if(resp){
                $('#uni_modal .modal-title').html(title);
                $('#uni_modal .modal-body').html(resp);
                if(size != ''){
                    $('#uni_modal .modal-dialog').addClass(size);
                } else {
                    $('#uni_modal .modal-dialog').removeAttr("class").addClass("modal-dialog modal-md");
                }
                $('#uni_modal').modal('show');
                end_load();
            }
        }
    });
}
</script>