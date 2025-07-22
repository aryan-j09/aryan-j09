<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Quotations</h3>
        <div class="card-tools">
            <a href="./?page=quotations/manage_quote" class="btn btn-flat btn-primary">
                <span class="fas fa-plus"></span> Create New
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="table table-bordered table-stripped" id="quotation-list">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Quotation Code</th>
                            <th>Date Created</th>
                            <th>Client/Lead</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $qry = $conn->query("
                            SELECT q.*, u.firstname, u.lastname, l.id as lead_id, l.company_name 
                            FROM quotations q 
                            LEFT JOIN users u ON q.created_by = u.id 
                            LEFT JOIN leads l ON q.lead_id = l.id
                            ORDER BY q.created_at DESC
                        ");
                        while($row = $qry->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $row['quotation_code']; ?></td>
                            <td><?php echo date("F j, Y", strtotime($row['created_at'])); ?></td>
                            <td><a href="./?page=leads/view_lead&id=<?php echo $row['lead_id'] ?>"><?php echo $row['company_name'] ?></a></td>
                            <td><?php echo $row['firstname'].' '.$row['lastname']; ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="./?page=quotations/view_quote&id=<?php echo $row['id'] ?>" class="btn btn-primary btn-flat btn-sm">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="./?page=quotations/manage_quote&id=<?php echo $row['id'] ?>" class="btn btn-info btn-flat btn-sm">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-flat btn-sm delete_quotation" data-id="<?php echo $row['id'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function(){
    $('#quotation-list').dataTable({
        columnDefs: [
            { orderable: false, targets: [-1] }
        ]
    });

    $('.delete_quotation').click(function(){
        _conf("Are you sure to delete this quotation?", "delete_quotation", [$(this).data('id')])
    });
});

function delete_quotation($id){
    start_loader();
    $.ajax({
        url: '../classes/Master.php?f=delete_quotation',
        method: 'POST',
        data: {id: $id},
        dataType: 'json',
        success: function(resp){
            if(resp.status == 'success'){
                location.reload();
            }else{
                alert(resp.msg || 'An error occurred');
            }
            end_loader();
        }
    });
}
</script>