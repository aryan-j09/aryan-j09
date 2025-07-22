<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $delete = $conn->query("DELETE FROM `proforma_invoice_list` WHERE id = '{$id}'");
    if ($delete) {
        $resp['status'] = 'success';
        $_SESSION['flashdata']['type'] = 'success';
        $_SESSION['flashdata']['message'] = 'Proforma Invoice successfully deleted.';
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred. Error: ' . $conn->error;
    }
    echo json_encode($resp);
    exit;
}

$query = "SELECT pi.*, email, c.company_name as client FROM clients c JOIN proforma_invoice_list pi ON c.id= pi.client_id";
$result = $conn->query($query);
?>

<style>
    .action-buttons {
        display: flex;
        gap: 2px;
    }
    /* From Uiverse.io by vinodjangid07 */ 
    .delete_data {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgb(255, 255, 255);
        border: none;
        font-weight: 600;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition-duration: 0.3s;
        overflow: hidden;
        position: relative;
        gap: 1px;
    }

    .svgIcon {
        width: 12px;
        transition-duration: 0.3s;
        color: black;
    }

    .svgIcon path {
        fill: black;
    }

    .delete_data:hover {
        width: 110px;
        border-radius: 50px;
        transition-duration: 0.3s;
        background-color: rgb(255, 69, 69);
        align-items: center;
        gap: 0;
    }

    .delete_data:hover .bin-bottom {
        width: 50px;
        transition-duration: 0.3s;
        transform: translateY(60%);
    }

    .bin-top {
        transform-origin: bottom right;
    }

    .delete_data:hover .bin-top {
        width: 40px;
        transition-duration: 0.3s;
        transform: translateY(60%) rotate(160deg);
    }

    .delete_data::before {
        position: absolute;
        top: -27px;
        content: "Delete";
        color: black;
        transition-duration: 0.3s;
        font-size: 2px;
    }

    .delete_data:hover::before {
        font-size: 13px;
        opacity: 1;
        transform: translateY(35px);
        transition-duration: 0.3s;
    }

    .edit_data {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgb(255, 255, 255);
        border: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition-duration: 0.3s;
        overflow: hidden;
        position: relative;
        text-decoration: none !important;
    }

    .edit-svgIcon {
        width: 17px;
        transition-duration: 0.3s;
        color: black;
    }

    .edit-svgIcon path {
        fill: black;
    }

    .edit_data:hover {
        width: 100px;
        border-radius: 50px;
        transition-duration: 0.3s;
        background-color: rgb(53, 208, 255);
        align-items: center;
    }

    .edit_data:hover .edit-svgIcon {
        width: 20px;
        transition-duration: 0.3s;
        transform: translateY(60%);
        -webkit-transform: rotate(360deg);
        -moz-transform: rotate(360deg);
        -o-transform: rotate(360deg);
        -ms-transform: rotate(360deg);
        transform: rotate(360deg);
    }

    .edit_data::before {
        color: black;
        content: "Edit";
        transition-duration: 0.3s;
        font-size: 0px;
    }

    .edit_data:hover::before {
        display: block;
        padding-right: 10px;
        font-size: 13px;
        opacity: 1;
        transform: translateY(0px);
        transition-duration: 0.3s;
    }

    .view_data {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgb(255, 255, 255);
        border: none;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0px 0px 20px rgb(0, 0, 0, 0.1);
        cursor: pointer;
        transition-duration: 0.3s;
        overflow: hidden;
        position: relative;
        text-decoration: none !important;
    }

    .view-svgIcon {
        width: 20px;
        transition-duration: 0.3s;
        color: black;
    }

    .view-svgIcon path {
        fill: black;
    }

    .view_data:hover {
        width: 110px;
        border-radius: 50px;
        transition-duration: 0.3s;
        background-color: rgb(46, 204, 113);
        align-items: center;
        gap: 5px;
        padding: 0 5px; /* Add horizontal padding */
        flex-direction: row; /* Display icon and text in a row */
    }

    .view_data::before {
        content: "View";
        color: black;
        transition-duration: 0.3s;
        font-size: 0px; /* Initially hide the text */
        white-space: nowrap; /* Prevent text from wrapping */
        order: 1; /* Place text on the left */
    }

    .view_data:hover::before {
        font-size: 15px;
        opacity: 1;
        transition-duration: 0.3s;
    }

    .view_data:hover .view-svgIcon {
        order: 2; /* Place icon on the right */
    }    
    
    .create_btn {
        background: #fff;
        border: none;
        padding: 10px 20px;
        display: inline-block;
        font-size: 15px;
        font-weight: 600;
        width: 120px;
        text-transform: uppercase;
        cursor: pointer;
        transform: skew(-21deg);
        color: black;
    }    

    .create_btn::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        right: 100%;
        left: 0;
        background: rgb(20, 20, 20);
        opacity: 0;
        z-index: -1;
        transition: all 0.5s;
    }

    .create_btn:hover {
        color: #fff;
    }

    .create_btn:hover::before {
        left: 0;
        right: 0;
        opacity: 1;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Proforma Invoices</h3>
        <div class="card-tools">
            <a href="<?php echo base_url ?>admin/?page=proforma_invoice/manage_pi" class="create_btn"><span class="fas fa-plus"> New</span> </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <?php if (isset($_SESSION['flashdata'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flashdata']['type']; ?>">
                    <?php echo $_SESSION['flashdata']['message']; ?>
                </div>
                <?php unset($_SESSION['flashdata']); ?>
            <?php endif; ?>
            <table class="table table-bordered table-striped" id="proforma_invoice_table">
                <colgroup>
                    <col width="6%">                    
                    <col width="29%">
                    <col width="26%">
                    <col width="14%">
                    <col width="14%">
                    <col width="11%">                        
                </colgroup>
                <thead>
                    <tr>
                        <th>Sr.</th>                        
                        <th>PO Code</th>
                        <th>Client Name</th>                        
                        <th>PO Date</th>
                        <th>Total Amount</th>                        
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    while($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?>.</td>                            
                            <td><?php echo $row['po_code'] ?></td>
                            <td><?php echo $row['client'] ?></td>
                            <td><?php echo date("d-M-Y",strtotime($row['po_date_created'])) ?></td>
                            <td><?php echo number_format($row['total_amount'], 2) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a class="view_data" href="<?php echo base_url.'admin?page=proforma_invoice/view_pi&id='.$row['id'] ?>" data-id="<?php echo $row['id'] ?>">
                                        <i class="fa fa-eye view-svgIcon"></i>
                                    </a>
                                    <a class="edit_data" href="<?php echo base_url.'admin?page=proforma_invoice/manage_pi&id='.$row['id'] ?>" data-id="<?php echo $row['id'] ?>">
                                        <svg class="edit-svgIcon" viewBox="0 0 512 512">
                                            <path d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.7c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L387.7 253.7 410.3 231zM160 399.4l-9.1 22.7c-4 3.1-8.5 5.4-13.3 6.9L59.4 452l23-78.1c1.4-4.9 3.8-9.4 6.9-13.3l22.7-9.1v32c0 8.8 7.2 16 16 16h32zM362.7 18.7L348.3 33.2 325.7 55.8 314.3 67.1l33.9 33.9 62.1 62.1 33.9 33.9 11.3-11.3 22.6-22.6 14.5-14.5c25-25 25-65.5 0-90.5L453.3 18.7c-25-25-65.5-25-90.5 0zm-47.4 168l-144 144c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l144-144c6.2-6.2 16.4-6.2 22.6 0s6.2 16.4 0 22.6z"/>
                                        </svg>
                                    </a>
                                    <a class="delete_data" data-id="<?php echo $row['id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 69 14" class="svgIcon bin-top">
                                            <g clip-path="url(#clip0_35_24)">
                                                <path fill="black" d="M20.8232 2.62734L19.9948 4.21304C19.8224 4.54309 19.4808 4.75 19.1085 4.75H4.92857C2.20246 4.75 0 6.87266 0 9.5C0 12.1273 2.20246 14.25 4.92857 14.25H64.0714C66.7975 14.25 69 12.1273 69 9.5C69 6.87266 66.7975 4.75 64.0714 4.75H49.8915C49.5192 4.75 49.1776 4.54309 49.0052 4.21305L48.1768 2.62734C47.3451 1.00938 45.6355 0 43.7719 0H25.2281C23.3645 0 21.6549 1.00938 20.8232 2.62734ZM64.0023 20.0648C64.0397 19.4882 63.5822 19 63.0044 19H5.99556C5.4178 19 4.96025 19.4882 4.99766 20.0648L8.19375 69.3203C8.44018 73.0758 11.6746 76 15.5712 76H53.4288C57.3254 76 60.5598 73.0758 60.8062 69.3203L64.0023 20.0648Z"></path>
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_35_24">
                                                    <rect fill="white" height="14" width="69"/>
                                                </clipPath>
                                            </defs>
                                        </svg>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 69 57" class="svgIcon bin-bottom">
                                            <g clip-path="url(#clip0_35_22)">
                                                <path fill="black" d="M20.8232 -16.3727L19.9948 -14.787C19.8224 -14.4569 19.4808 -14.25 19.1085 -14.25H4.92857C2.20246 -14.25 0 -12.1273 0 -9.5C0 -6.8727 2.20246 -4.75 4.92857 -4.75H64.0714C66.7975 -4.75 69 -6.8727 69 -9.5C69 -12.1273 66.7975 -14.25 64.0714 -14.25H49.8915C49.5192 -14.25 49.1776 -14.4569 49.0052 -14.787L48.1768 -16.3727C47.3451 -17.9906 45.6355 -19 43.7719 -19H25.2281C23.3645 -19 21.6549 -17.9906 20.8232 -16.3727ZM64.0023 1.0648C64.0397 0.4882 63.5822 0 63.0044 0H5.99556C5.4178 0 4.96025 0.4882 4.99766 1.0648L8.19375 50.3203C8.44018 54.0758 11.6746 57 15.5712 57H53.4288C57.3254 57 60.5598 54.0758 60.8062 50.3203L64.0023 1.0648Z"></path>
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_35_22">
                                                    <rect fill="white" height="57" width="69"/>
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                            <!-- <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    Action
                                    <span class="sr-only">Toggle Dropdown</span>                                    
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="<?php echo base_url.'admin?page=proforma_invoice/view_pi&id='.$row['id'] ?>" data-id="<?php echo $row['id'] ?>"><span class="fa fa-eye text-dark"></span> View</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="<?php echo base_url.'admin?page=proforma_invoice/manage_pi&id='.$row['id'] ?>" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span></a>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span></a>
                                </div>
                            </td> -->
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>  
    </div>
</div>
<script>
    $(document).ready(function(){
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this Proforma Invoice permanently?","delete_pi",[$(this).attr('data-id')])
        })
        $('.table td,.table th').addClass('py-1 px-2 align-middle')
        $('#proforma_invoice_table').dataTable();
    })
    function delete_pi($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_pi",
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
                }else{
                    alert_toast("An error occured.",'error');
                    end_loader();
                }
            }
        })
    }
</script>