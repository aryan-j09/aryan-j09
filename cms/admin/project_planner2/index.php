<style>
    .table tbody tr { cursor: pointer; transition: background-color 0.3s ease; user-select: none; }
    .table tbody tr:hover { background-color: rgba(0,123,255,0.1); }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Projects (Planner 2)</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=project_planner2/manage_project" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  Create New</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-hover table-striped">
                <colgroup>
                    <col width="5%">
                    <col width="20%">
                    <col width="35%">
                    <col width="40%">
                </colgroup>
                <thead>
                    <tr class="bg-navy disabled">
                        <th>#</th>
                        <th>Dates Created</th>
                        <th>Project Name</th>
                        <th>Client Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $i = 1;
                        $sql = "SELECT pp.*, c.company_name as client_name FROM `project_planner` pp LEFT JOIN `clients` c on pp.client_id = c.id ORDER BY pp.created_at DESC";
                        $qry = $conn->query($sql);
                        if($qry === false){
                            echo "<tr><td colspan='4' class='text-center'>Query failed: " . htmlspecialchars($conn->error) . "</td></tr>";
                        } elseif($qry->num_rows > 0){
                            while($row = $qry->fetch_assoc()):
                    ?>
                            <tr onclick="window.location.href = '<?php echo base_url ?>admin/?page=project_planner2/view_project&id=<?php echo $row['id'] ?>';">
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td><?php echo date("d-M-y", strtotime($row['created_at'])) ?></td>
                                <td><?php echo htmlspecialchars($row['name']) ?></td>
                                <td><?php echo htmlspecialchars($row['client_name']) ?></td>
                            </tr>
                        <?php endwhile; } else { echo "<tr><td colspan='4' class='text-center'>No Records Found</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('.table td, .table th').addClass('py-1 px-2 align-middle');
        $('.table').dataTable({
            order: [[1, 'desc']]
        });
    });
</script>
