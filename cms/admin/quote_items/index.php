<?php
$items = $conn->query("
SELECT qi.*, (
    SELECT image_path 
    FROM quote_item_images 
    WHERE quote_item_id = qi.id 
    ORDER BY id ASC LIMIT 1
) as first_image
FROM quote_items qi
ORDER BY qi.id DESC
");
?>

<div class="card card-outline card-primary">
<div class="card-header">
    <h3 class="card-title">Quote Items</h3>
    <div class="card-tools">
        <a href="?page=quote_items/manage_item" class="btn btn-flat btn-primary">
            <span class="fas fa-plus"></span> Create New
        </a>
    </div>
</div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="table table-bordered table-stripped">
                    <colgroup>
                        <col width="5%">
                        <col width="15%">
                        <col width="25%">
                        <col width="35%">
                        <col width="20%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        while($row = $items->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class="text-center">
                                <?php if(!empty($row['first_image'])): ?>
                                    <img src="<?php echo base_url . $row['first_image']; ?>" 
                                         alt="Item Image" 
                                         class="img-thumbnail" 
                                         style="height: 50px; width: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <span class="fa fa-image text-muted"></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['name'] ?></td>
                            <td><p class="truncate-3"><?php echo $row['description'] ?></p></td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="?page=quote_items/view_item&id=<?php echo $row['id'] ?>">
                                        <span class="fa fa-eye text-dark"></span> View
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=quote_items/manage_item&id=<?php echo $row['id'] ?>">
                                        <span class="fa fa-edit text-primary"></span> Edit
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-trash text-danger"></span> Delete
                                    </a>
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
function delete_quote_item(id) {
    start_loader();
    $.ajax({
        url: _base_url_ + 'classes/Master.php?f=delete_quote_item',
        method: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(resp) {
            if(resp.status == 'success') {
                location.reload();
            } else {
                alert_toast("An error occurred: " + resp.error, 'error');
            }
            end_loader();
        },
        error: function(xhr, status, error) {
            alert_toast("An error occurred", 'error');
            end_loader();
            console.error(xhr.responseText);
        }
    });
}

$(document).ready(function() {
    // Delete confirmation
    $('.delete_data').click(function() {
        _conf("Are you sure to delete this quote item?", "delete_quote_item", [$(this).data('id')]);
    });
});
</script>

<style>
.dropdown-menu .dropdown-item {
    padding: 0.25rem 1rem;
    cursor: pointer;
}

.dropdown-menu .dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-menu .dropdown-item .fa {
    margin-right: 0.5rem;
}
</style>