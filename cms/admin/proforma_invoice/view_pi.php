<?php 
if(isset($_GET['id'])){
    $qry = $conn->query("SELECT pi.*, c.company_name as client, c.billing_address as client_address, 
        c.shipping_address, c.contact_person, c.contact_no, c.cperson_acc, cperson_no_acc, c.cperson_pur, cperson_no_pur, c.gst_number, c.email 
        FROM clients c 
        JOIN proforma_invoice_list pi ON c.id = pi.client_id 
        WHERE pi.id = '{$_GET['id']}'");
    
    if($qry->num_rows > 0){
        $invoice = $qry->fetch_assoc();
    } else {
        echo "No invoice found with the given ID.";
        exit;
    }

    // Fetch the items
    $items = $conn->query("SELECT * FROM proforma_invoice_items WHERE proforma_invoice_id = '{$_GET['id']}'");
} else {
    echo "No invoice ID provided.";
    exit;
}

function format_indian_number($number)
{
    $decimal = (string)($number - floor($number));
    $decimal = substr($decimal, 1);
    $number = floor($number);

    $len = strlen($number);
    $m = '';
    $number = strrev($number);
    for ($i = 0; $i < $len; $i++) {
        if (($i == 3 || ($i > 3 && ($i - 1) % 2 == 0)) && $i != $len) {
            $m .= ',';
        }
        $m .= $number[$i];
    }
    $result = strrev($m);
    return $result . $decimal;
}
?>

<style>
    .print-header {
        color: rgb(47, 84, 150);
        text-align: center;
        margin-bottom: 20px;
    }
    .print-header h1 {
        font: 70px 'Raleway SemiBold';                
        margin: 0;
    }
    .print-header p {
        font: 27px 'Garamond';        
        margin: 0;
        padding: 0;
    }
    .d-flex {
        display: flex;
    }
    .justify-content-start {
        justify-content: flex-start;
    }
    .address-container {
        gap: 20px; /* Add some space between the address blocks */
    }
    .address-block {
        width: 45%; /* Adjust the width as needed */
    }
    .print-address {
        display: block;
    }
    .header-container .date {
        text-align: right;
    }
    .inline-container {
        display: flex;
        gap: 90px;
    }    
    .center-text {
        text-align: center;
    }
    .center-text h3 {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
        top: 40%;
        transform: translate(-50%, -50%);          
    }
    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-left img {
        max-width: 130px;
        max-height: 1300px;
    }
    .header-middle {
        text-align: center;        
    }
    .header-right {        
        text-align: left;
        position: relative;
        padding-left: 20px;
    }
    .header-right::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 0;
        bottom: 0;
        width: 1px;
        border-left: 2px dotted rgb(47, 84, 150);
    }
    .box {
        border: 1px solid #000;
        padding: 3px;
        padding-bottom: 0;        
        display: inline-block;
        margin-right: 10px;
        font-style: Arial;
    }
    .header-container .date {
        text-align: right;
    }        
    .form-control-sm {
        height: calc(1.5em + .5rem + 2px);
        padding: .25rem .5rem;
        font-size: .875rem;
        line-height: 1.5;
        border-radius: .2rem;
    }
    .button-container {
        display: flex;
        justify-content: center;
        margin-top: -20px; /* Adjust this value to position the buttons slightly above the container */
        margin-bottom: 20px; /* Add some space below the buttons */
    }
    .button-container .btn {
        margin: 0 5px; /* Add some space between the buttons */
    }   
    .print-background {
        background-color: rgb(0, 31, 63) !important;
        color: white !important;            
    }
    .footer-container .col-4.text-center {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .text-right {
        margin-left: auto;
    }
    .address-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
    }
    .address-container .left-address {
        width: 100%; /* Adjust the width as needed */
    }
    .address-container .right-address {
        width: 100%; /* Adjust the width as needed */        
    }
    .footer-container {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .print-background {
            background-color: rgb(0, 31, 63) !important;
            color: white !important;            
        }
        .print-background th {
            color: black !important;
        }
        .inline-container {
            display: flex;
            gap: 700px;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05) !important;
        }
        .table-striped tbody tr:nth-of-type(even) {
            background-color: rgba(0, 0, 0, 0.15) !important;
        }      
        .footer-container {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        
        table {
            page-break-inside: auto;
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }  
    }    
</style>
<body>
    <div class="header-container d-flex justify-content-between align-items-center print-header">
        <div class="header-left">
            <img src="<?php echo base_url; ?>uploads/HUGO.png" alt="Company Logo" class="company-logo">
        </div>
        <div class="header-middle text-center flex-grow-1">
            <h1 style="font-size:70px">HUGOPHARM</h1>
            <p style="font-size:30px; font-style: italic;">Systems Engineered with Mind and Spirit</p>
        </div>
        <div class="header-right text-left">
            <p style="font-size: 18px;"><strong>Regd Office:</strong> 8, Jogani Industrial Estate,<br> 
            541 Senapati Bapat Marg, Dadar (W), Mumbai 400028<br>
            Mob: 9869415083 Email: sales@sbpanchal.com<br>
            <strong>Works:</strong> Plot No TS 20, MIDC Phase 2, Sagaon,<br>
            Manpada Road, Dombivli (E) 421203</p>
            GSTIN:27AACCH1711N1ZM
        </div>
    </div>

    <div class="card card-outline card-primary">    
        <div class="card-header d-flex justify-content-between align-items-center">        
            <div class="box">
                <label class="control-label text-info">PO No: </label> <?php echo isset($invoice['po_code']) ? $invoice['po_code'] : 'N/A'; ?>
                <label class="control-label text-info date">Date: </label> <?php echo date('d-m-Y', strtotime($invoice['po_date_created'])); ?>              
            </div>                   
            <div class="center-text">
                <h3><strong><u>Proforma Invoice</strong></u></h3>
            </div>
            <div class="text-right">
                <label class="date control-label">Date: <?php echo date('d-m-Y'); ?></label>
            </div>
        </div> 
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">                
                    <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>Client:</strong></td>
                                    <td><?php echo $invoice['client']; ?></td>                                        
                                </tr>
                                <tr>
                                    <td><strong>GST No:</strong></td>
                                    <td><?php echo $invoice['gst_number']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Billing Address:</strong></td>
                                    <td><?php echo $invoice['client_address']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Shipping Address:</strong></td>
                                    <td><?php echo $invoice['shipping_address']; ?></td>
                                </tr>
                                <?php if(!empty($invoice['contact_person'])): ?>
                                <tr>
                                <td><strong>Cont. End User:</strong></td>
                                <td>
                                    <?= $invoice['contact_person'] ?>
                                    <?= !empty($invoice['contact_no']) ? ' - ' . $invoice['contact_no'] : '' ?>
                                </td>
                                </tr>
                                <?php endif; ?>                                
                                <?php if(!empty($invoice['cperson_pur'])): ?>
                                <tr>
                                    <td><strong>Cont. Purchase:</strong></td>
                                    <td>
                                        <?= $invoice['cperson_pur'] ?>
                                        <?= !empty($invoice['cperson_no_pur']) ? ' - ' . $invoice['cperson_no_pur'] : '' ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo $invoice['email']; ?></td>
                                </tr>                                                                  
                            </tbody>
                        </table>
                    <!-- <h5 class="no-print">Employee Approval: 
                        <?php 
                        if (!empty($invoice['employee_approved_by'])) {
                            echo '<span class="badge badge-success rounded-pill">Approved by ' . $invoice['employee_approved_by'] . '</span>';
                        } else {
                            echo '<span class="badge badge-danger rounded-pill">Pending</span>';
                        }
                        ?>
                    Admin Approval: 
                        <?php 
                        if (!empty($invoice['admin_approved_by'])) {
                            echo '<span class="badge badge-success rounded-pill">Approved by ' . $invoice['admin_approved_by'] . '</span>';
                        } else {
                            echo '<span class="badge badge-danger rounded-pill">Pending</span>';
                        }
                        ?>
                    </h5> -->
                </div>            
            </div>
            <fieldset>            
                <table class="table table-bordered table-striped">
                    <colgroup>
                        <col width="10%">
                        <col width="60%">
                        <col width="10%">
                        <col width="20%">                    
                    </colgroup>
                    <thead>
                        <tr class="center-text print-background">
                            <th>Sr. No.</th>
                            <th>Description</th>
                            <th>HSN/SAC</th>
                            <th>Amount (<?php echo isset($invoice['currency']) ? $invoice['currency'] : 'INR'; ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        $totalAmount = 0;
                        while($item = $items->fetch_assoc()): 
                            $totalAmount += $item['amount'];
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?>)</td>
                                <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                                <td class="text-center"><?php echo $item['hsn_code']; ?></td>
                                <td style="text-align: right"><?php echo format_indian_number($item['amount']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-right" colspan="3">Sub Total:</th>
                            <td class="text-right"><?php echo format_indian_number($totalAmount); ?></td>
                        </tr>
                        <tr>
                            <th class="text-right" colspan="3">Packing Forwarding:
                                <?php if ($invoice['packing_forwarding'] > 0): ?>
                                    (<?php echo $invoice['packing_forwarding']; ?>%)
                                <?php endif; ?>
                            </th>
                            <td class="text-right">
                                <?php if ($invoice['packing_forwarding_amount'] > 0) {
                                    echo format_indian_number($invoice['packing_forwarding_amount']);
                                } else {
                                    echo "Included";
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <?php if ($invoice['freight'] > 0): ?>
                                <th class="text-right" colspan="3">Freight Charges:</th>
                                <td class="text-right"><?php echo format_indian_number($invoice['freight']); ?></td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php if ($invoice['tax'] > 0): ?>
                                <th class="text-right" colspan="3">IGST (<?php echo $invoice['tax']; ?>%):</th>
                                <td class="text-right"><?php echo format_indian_number($invoice['tax_amount']);?></td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php if ($invoice['cgst'] > 0): ?>
                                <th class="text-right" colspan="3">CGST (<?php echo $invoice['cgst']; ?>%)</th>
                                <td class="text-right"><?php echo format_indian_number($invoice['cgst_amount']);?></td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php if ($invoice['sgst'] > 0): ?>
                                <th class="text-right" colspan="3">SGST (<?php echo $invoice['sgst']; ?>%):</th>
                                <td class="text-right"><?php echo format_indian_number($invoice['sgst_amount']); ?></td>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <th class="text-right" style="color: red;" colspan="3"><u>Grand Total:</u></th>
                            <th class="text-right"><?php echo format_indian_number($invoice['total_amount']); ?></th>
                        </tr>
                        <tr>
                            <td colspan="4" style="text-align: center; font-size: 1.25em;"><strong><u>Payment Terms</u></strong></td>
                        </tr>
                        <tr>
                            <?php if ($invoice['advance_payment'] > 0): ?>
                                <th colspan="3" class="text-right">                                    
                                    <?php if ($invoice['abg_required']): ?>
                                        <span style="color: red;">[Against ABG]</span>
                                    <?php endif; ?>
                                    Advance Payment(<?php echo $invoice['advance_payment']; ?>%):
                                </th>
                                <th class="text-right"><?php echo format_indian_number($invoice['advance_payment_amount']);?></th>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php if ($invoice['inspection_payment'] > 0): ?>
                                <th colspan="3" class="text-right">
                                    <?php echo ($invoice['inspection_payment_type'] == 'delivery' ? 'Against Delivery' : 'Against Inspection Prior to Dispatch'); ?>
                                    (<?php echo $invoice['inspection_payment']; ?>%):
                                </th>
                                <th class="text-right"><?php echo format_indian_number($invoice['inspection_payment_amount']);?></th>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php if ($invoice['installation_payment'] > 0): ?>
                                <th colspan="3" class="text-right">                                    
                                    <?php if ($invoice['pbg_required']): ?>
                                        <span style="color: red;">[Against PBG]</span>
                                    <?php endif; ?>
                                    Within 15 Days from Date of Installation(<?php echo $invoice['installation_payment']; ?>%):
                                </th>
                                <th class="text-right"><?php echo format_indian_number($invoice['installation_payment_amount']);?></th>
                            <?php endif; ?>
                        </tr>
                        <tr>
                            <?php if($invoice['credit_payment_amount'] > 0): ?>
                                <th class="text-right" colspan="3"><?php echo($invoice ['credit_payment_days']) ?> days from Date of Invoice:</th>
                                <th class="text-right"><?php echo format_indian_number($invoice['credit_payment_amount']); ?></th>
                            <?php endif; ?>
                        </tr>
                    </tfoot>
                </table>
            </fieldset>
            <div class="footer-container">
                <div class="row" style="margin: 0;">
                    <div class="col-8 text-left" style="font-size: 15px; padding: 0;">
                        <p><strong>
                        <u style="font-size:20px; color:red;">Freight: <?php echo $invoice['freight_note']; ?></u>
                        <br>
                        NEFT DETAILS:<br>
                        BANK NAME: CENTRAL BANK OF INDIA<br>
                        BRANCH: DADAR (W) MUMBAI – 400028<br>
                        A/C NO: 3013760932<br>
                        IFSC CODE: CBIN0280600<br></strong>
                        </p>
                    </div>                
                    <div class="col-4 text-center">
                        <h4 style="font-size: 20px;">E. & O.E.</h4>
                        <h4 style="font-size: 20px;"><strong>For Hugopharm Technologies Pvt. Ltd</strong></h4>
                        <h4><?php echo $invoice['authorized_signatory']; ?></h4>
                        <p>Authorised Signatory</p>
                    </div>                
                </div>
            </div>
            <p class="text-center">This is a computer generated document and does not require a signature.</p>
        </div>
    </div>

    <div class="button-container mt-3">
        <a href="<?php echo base_url ?>admin/?page=proforma_invoice/manage_pi&id=<?php echo $invoice['id']; ?>" class="btn btn-primary no-print">Edit</a>
        <a href="<?php echo base_url ?>admin/?page=proforma_invoice" class="btn btn-secondary no-print">Back to List</a>
        <button id="printButton" class="btn btn-success no-print">Print</button>
    </div>

    <script>    
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            if (success === 'true') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Proforma Invoice saved successfully!',
                    showConfirmButton: false,
                    timer: 2000,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
            }
        });

        document.getElementById('printButton').onclick = function() {
            window.print();
        };
    </script>
</body>
