<?php
if(!isset($_GET['id'])) {
    $_SESSION['error'] = "Quotation ID is required";
    header("Location: ./?page=quotations");
    exit;
}

$quotation_id = $_GET['id'];

// Get quotation details and lead info
$qry = $conn->query("SELECT q.*, u.firstname, u.lastname, l.company_name, l.contact_person, l.city, l.email, l.phone, l.address
    FROM quotations q 
    LEFT JOIN users u ON q.created_by = u.id
    LEFT JOIN leads l ON q.lead_id = l.id
    WHERE q.id = '{$quotation_id}'");

if($qry->num_rows <= 0) {
    $_SESSION['error'] = "Quotation not found";
    header("Location: ./?page=quotations");
    exit;
}

$quotation = $qry->fetch_assoc();

// Get only selected machines for this quotation
$machines_qry = $conn->query("
    SELECT qi.*, q.id as quotation_item_id
    FROM quotation_items q 
    INNER JOIN quote_items qi ON q.quote_item_id = qi.id
    WHERE q.quotation_id = '{$quotation_id}'
");
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
    gap: 20px;
}
.address-block {
    width: 45%;
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
    margin-top: -20px;
    margin-bottom: 20px;
}
.button-container .btn {
    margin: 0 5px;
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
    width: 100%;
}
.address-container .right-address {
    width: 100%;        
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
    .avoid-break {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
    table {
        page-break-inside: auto !important;
        break-inside: auto !important;
    }
    .page-break {
        display: none !important; /* Hide manual breaks */
    }
    .machine-section {
        page-break-before: always !important;
    }
    .card {
        border-left: none !important;
        border-right: none !important;
        border-bottom: none !important;
        box-shadow: none !important;
    }
    .card-header {
        padding: 0 !important;
        background-color: transparent !important;
    }
    .card-body {
        padding: 0 1.5rem !important; /* Add horizontal padding */
    }
    .machine-section .card-body {
        padding-top: 1.5rem !important; /* Add top padding for item sections */
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }  
    .signature-block {
        position: absolute;
        bottom: 40px;
        left: 0;
        right: 0;
        width: 100%;
        padding-left: 0;
        padding-right: 0;
    }
    .quotation-cover {
        position: relative;
        min-height: 900px;
    }
}
</style>

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
        Tel: 9820537200 Email: sales@sbpanchal.com<br>
        <strong>Works:</strong> Plot No TS 20, MIDC Phase 2, Sagaon,<br>
        Manpada Road, Dombivli (E) 421203</p>
        GSTIN:27AACCH1711N1ZM
    </div>
</div>
<div class="text-right md-1 no-print">
    <a href="./?page=quotations" class="btn btn-secondary">Back to List</a>
    <button id="printButton" class="btn btn-success">Print</button>
</div>
<div class="card card-outline card-primary">
    <div class="card-body" id="print_out">                

        <!-- Quotation Cover Page (First Page) -->
        <div class="quotation-cover mb-4" style="position:relative; min-height:900px;">
            <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                <div>
                    <strong>Our Ref No: <?php echo htmlspecialchars($quotation['quotation_code'] ?? $quotation['id']); ?></strong>
                </div>
                <div>
                    <strong><?php echo date("d-m-Y", strtotime($quotation['created_at'])); ?></strong>  
                </div>
            </div>
            <div style="margin-top:18px; margin-bottom:8px; font-size:1.1em;"><strong>To,</strong></div>
            <table class="table table-bordered w-100 mb-3">
                <?php if(!empty($quotation['contact_person'])): ?>
                <tr>
                    <th style="width: 20%;">Contact</th>
                    <td>
                        <?php echo htmlspecialchars($quotation['contact_person']); ?>
                        <?php if(!empty($quotation['designation'])): ?>
                            (<?php echo htmlspecialchars($quotation['designation']); ?>)
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if(!empty($quotation['company_name']) || !empty($quotation['address'])): ?>
                <tr>
                    <th style="width: 20%;">Business Address</th>
                    <td>
                        <?php 
                        $address_parts = [];
                        if(!empty($quotation['company_name'])) {
                            $address_parts[] = '<strong>' . htmlspecialchars($quotation['company_name']) . '</strong>';
                        }
                        if(!empty($quotation['address'])) {
                            $address_parts[] = nl2br(htmlspecialchars($quotation['address']));
                        }
                        echo implode('<br>', $address_parts);
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php if(!empty($quotation['phone'])): ?>
                <tr>
                    <th style="width: 20%;">Business Phone</th>
                    <td><?php echo htmlspecialchars($quotation['phone']); ?></td>
                </tr>
                <?php endif; ?>

                <?php if(!empty($quotation['email'])): ?>
                <tr>
                    <th style="width: 20%;">E-mail</th>
                    <td><?php echo htmlspecialchars($quotation['email']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <p class="mt-3" style="font-style: italic;">
                We are glad to quote for the following R&D formulation line of equipment manufactured by us.
            </p>
            <ol style="font-size: 1.1em;">
                <?php
                $machines_qry->data_seek(0);
                while($machine = $machines_qry->fetch_assoc()): ?>
                    <li><strong><?php echo htmlspecialchars($machine['name']); ?></strong></li>
                <?php endwhile; ?>
            </ol>
            <div class="signature-block mt-4" style="position:absolute; bottom:40px; left:0; right:0;">
                Sincerely,<br><br>
                <strong>For Hugopharm Technologies Pvt. Ltd.,</strong><br>
                <strong><?php echo htmlspecialchars($quotation['firstname'] . ' ' . $quotation['lastname']); ?></strong><br>
                Tel: 022-24226882 / 022-24222815
            </div>
        </div>

        <div class="machine-container">
        <?php
        // Reset pointer again for details loop
        $machines_qry->data_seek(0);
        $item_number = 1;
        while($machine = $machines_qry->fetch_assoc()): 
        ?>
        <div class="machine-section mt-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h4><?php echo $item_number . '. ' . htmlspecialchars($machine['name']); ?></h4>
                </div>
                <div class="card-body">
                    <!-- Machine Description -->
                    <div class="mb-3">
                        <?php echo nl2br($machine['description']); ?>
                    </div>

                    <!-- Technical Specifications -->
                    <?php 
                    $specs = $conn->query("SELECT * FROM quote_item_attributes WHERE quote_item_id = '{$machine['id']}'");
                    if($specs->num_rows > 0): ?>
                    <div class="avoid-break">
                        <h5>Technical Specifications</h5>
                        <table class="table table-bordered table-striped">
                            <colgroup>
                                <col width= "10%;">
                                <col width= "30%;">
                                <col width= "60%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Attribute</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while($spec = $specs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?>.</td>
                                    <td><strong><?php echo $spec['attribute_name']; ?></strong></td>
                                    <td><?php echo nl2br($spec['attribute_value']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Selected Prices -->
                    <?php 
                    $prices = $conn->query("
                        SELECT p.price, p.description
                        FROM quotation_item_prices qp
                        INNER JOIN quote_item_prices p ON qp.price_id = p.id
                        WHERE qp.quotation_item_id = '{$machine['quotation_item_id']}'
                    ");
                    if($prices->num_rows > 0): ?>
                    <div class="avoid-break">
                        <h5>Prices: <?php echo $machine['name']; ?></h5>
                        <table class="table table-bordered table-striped">
                            <colgroup>
                                <col width= "10%;">
                                <col width= "20%;">
                                <col width= "70%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while($price = $prices->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?>.</td>
                                    <td><strong>₹<?php echo number_format($price['price'], 2); ?></strong></td>
                                    <td><?php echo html_entity_decode($price['description']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Selected Accessories -->
                    <?php 
                    $accessories = $conn->query("
                        SELECT a.name, a.price
                        FROM quotation_item_accessories qa
                        INNER JOIN quote_item_accessories a ON qa.accessory_id = a.id
                        WHERE qa.quotation_item_id = '{$machine['quotation_item_id']}'
                    ");
                    if($accessories->num_rows > 0): ?>
                    <div class="avoid-break">
                        <h5>Options For Extra Accessories</h5>
                        <table class="table table-bordered table-striped">
                            <colgroup>
                                <col width= "10%;">
                                <col width= "20%;">
                                <col width= "70%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Sr. No</th>
                                    <th>Price</th>
                                    <th>Accessory</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while($acc = $accessories->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?>.</td>
                                    <td><strong>₹<?php echo number_format($acc['price'], 2); ?></strong></td>
                                    <td><?php echo nl2br($acc['name']); ?></td>                                        
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php 
        $item_number++;
        endwhile; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('printButton').onclick = function() {
    window.print();
};
</script>