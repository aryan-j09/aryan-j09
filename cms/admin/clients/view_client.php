<?php 
$qry = $conn->query("SELECT * FROM clients where id = '{$_GET['id']}'");
if($qry->num_rows > 0){
    foreach($qry->fetch_array() as $k => $v){
        $$k = $v;
    }
}
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h4 class="card-title">Client Details - <?php echo $company_name ?></h4>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-striped">
                <tr>
                    <td width="20%"><b>Company Name:</b></td>
                    <td width="30%"><?php echo isset($company_name) ? $company_name : '' ?></td>
                    <td width="20%"><b>Email:</b></td>
                    <td width="30%"><?php echo isset($email) ? $email : '' ?></td>
                </tr>
                <tr>
                    <td><b>GST Number:</b></td>
                    <td colspan="3"><?php echo isset($gst_number) ? $gst_number : '' ?></td>
                </tr>
                <tr>
                    <td><b>Billing Address:</b></td>
                    <td><?php echo isset($billing_address) ? $billing_address : '' ?></td>
                    <td><b>Shipping Address:</b></td>
                    <td><?php echo isset($shipping_address) ? $shipping_address : '' ?></td>
                </tr>
                <?php if(!empty($contact_person) || !empty($contact_no)): ?>
                <tr>
                    <td colspan="4" class="bg-light"><b>End User Contact Details</b></td>
                </tr>
                <tr>
                    <td><b>Contact Person:</b></td>
                    <td><?php echo !empty($contact_person) ? $contact_person : '-' ?></td>
                    <td><b>Contact No:</b></td>
                    <td><?php echo !empty($contact_no) ? $contact_no : '-' ?></td>
                </tr>
                <?php endif; ?>
                <?php if(!empty($cperson_acc) || !empty($cperson_no_acc)): ?>
                <tr>
                    <td colspan="4" class="bg-light"><b>Accounts Contact Details</b></td>
                </tr>
                <tr>
                    <td><b>Contact Person:</b></td>
                    <td><?php echo !empty($cperson_acc) ? $cperson_acc : '-' ?></td>
                    <td><b>Contact No:</b></td>
                    <td><?php echo !empty($cperson_no_acc) ? $cperson_no_acc : '-' ?></td>
                </tr>
                <?php endif; ?>
                <?php if(!empty($cperson_pur) || !empty($cperson_no_pur)): ?>
                <tr>
                    <td colspan="4" class="bg-light"><b>Purchase Contact Details</b></td>
                </tr>
                <tr>
                    <td><b>Contact Person:</b></td>
                    <td><?php echo !empty($cperson_pur) ? $cperson_pur : '-' ?></td>
                    <td><b>Contact No:</b></td>
                    <td><?php echo !empty($cperson_no_pur) ? $cperson_no_pur : '-' ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <a class="btn btn-flat btn-primary" href="<?php echo base_url.'/admin?page=clients/manage_client&id='.(isset($id) ? $id : '') ?>">Edit</a>
        <a class="btn btn-flat btn-default" href="<?php echo base_url.'/admin?page=clients' ?>">Back To List</a>
    </div>
</div>