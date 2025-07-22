<!DOCTYPE html>
<html lang="en">

<head>
    
    <style>
        body {
            background-color: #f4f4f4;            
            color: #333;
            user-select: none; /* Prevent text selection */
            -webkit-user-select: none; /* For Safari */
            -moz-user-select: none; /* For Firefox */
            -ms-user-select: none; /* For Internet Explorer/Edge */
        }

        .container {
            margin-top: 50px;
        }

        .info-box {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            min-height: 150px;
            /* Make the entire box clickable without <a> tag issues */
            cursor: pointer; /* Indicate it's clickable */
            
        }

        .info-box:hover {
            transform: translateY(-10px);
        }

        .info-box-icon {
            font-size: 40px;
            color: #007bff;
            margin-bottom: 15px;
        }

        .info-box-text {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: rgb(0, 0, 0);
        }

        .info-box-number {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Styles for the number display (if needed) */
        .info-box-number {
            margin-top: 10px; /* Adjust spacing as needed */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-center mb-4">Welcome to <?php echo $_settings->info('name') ?></h1>
        <div class="row">

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=purchase_order'">
                    <i class="fas fa-th-list info-box-icon"></i>
                    <span class="info-box-text">Purchase Orders:
                        <?php echo $conn->query("SELECT * FROM `purchase_order_list`")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=proforma_invoice'">
                    <i class="fas fa-file-invoice-dollar info-box-icon"></i>
                    <span class="info-box-text">Proforma Invoices:
                        <?php echo $conn->query("SELECT * FROM `proforma_invoice_list`")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=po_details'">
                    <i class="fas fa-file-alt info-box-icon"></i>
                    <span class="info-box-text">PO Factory Details:
                        <?php echo $conn->query("SELECT * FROM `purchase_orders`")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=leads'">
                    <i class="fas fa-user-tag info-box-icon"></i>
                    <span class="info-box-text">CRM:
                        <?php echo $conn->query("SELECT * FROM `leads`")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=tasks'">
                    <i class="fas fa-tasks info-box-icon"></i>
                    <span class="info-box-text">Tasks</span>
                </div>
            </div>

            <?php if($_settings->userdata('type') == 1): ?>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=clients'">
                    <i class="fas fa-users info-box-icon"></i>
                    <span class="info-box-text">Clients:
                        <?php echo $conn->query("SELECT * FROM `clients` where id != 1 ")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=maintenance/supplier'">
                    <i class="fas fa-truck-loading info-box-icon"></i>
                    <span class="info-box-text">Suppliers:
                        <?php echo $conn->query("SELECT * FROM `supplier_list` where `status` = 1")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=machine_items'">
                    <i class="fas fa-cogs info-box-icon"></i>
                    <span class="info-box-text">Machines:
                        <?php echo $conn->query("SELECT * FROM `machine_list`")->num_rows; ?></span>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box shadow" onclick="window.location.href='<?php echo base_url ?>admin/?page=maintenance/item'">
                    <i class="fas fa-box info-box-icon"></i>
                    <span class="info-box-text">Items:
                        <?php echo $conn->query("SELECT * FROM `item_list` where `status` = 1")->num_rows; ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>