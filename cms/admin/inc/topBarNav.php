<?php
$today = date('Y-m-d');
$tasks_count = 0;

// Get deliveries count (today + overdue + upcoming 7 days)
$deliveries = $conn->query("SELECT COUNT(*) as count FROM purchase_orders 
    WHERE (DATE(expected_delivery) = '{$today}' 
    OR (DATE(expected_delivery) < '{$today}' AND status != 'completed')
    OR (DATE(expected_delivery) BETWEEN DATE_ADD('{$today}', INTERVAL 1 DAY) 
        AND DATE_ADD('{$today}', INTERVAL 7 DAY)))
    AND status != 'completed'");
$deliveries_count = $deliveries->fetch_assoc()['count'];

// Get pending payments count
$pending_payments = $conn->query("SELECT COUNT(DISTINCT po.id) as count 
    FROM purchase_orders po 
    INNER JOIN proforma_invoice_list pil ON po.po_code = pil.po_code 
    WHERE po.status = 'completed' 
    AND (COALESCE(po.advance_received, 0) + 
        COALESCE(po.inspection_received, 0) + 
        COALESCE(po.installation_received, 0) + 
        COALESCE(po.credit_received, 0)) < pil.total_amount");
$payments_count = $pending_payments->fetch_assoc()['count'];

// Add this under the tasks_count calculation at the top
$tasks = $conn->query("SELECT COUNT(*) as count FROM tasks 
    WHERE assigned_to = '{$_SESSION['userdata']['id']}' 
    AND status != 'completed'");
$tasks_pending = $tasks->fetch_assoc()['count'];
$tasks_count += $tasks_pending;
?>
<style>
  .user-img {
    position: absolute;
    height: 27px;
    width: 27px;
    object-fit: cover;
    left: -7%;
    top: -12%;
  }
  .btn-rounded {
    border-radius: 50px;
  }
  .main-sidebar {
    transition: transform 0.3s ease, opacity 0.3s ease;
  }
  .hidden-nav {
    transform: translateX(-100%);
    opacity: 0;
  }
  .content-wrapper {
    transition: margin-left 0.3s ease;
  }
  .expanded-content {
    margin-left: 0 !important;
  }
  .main-header {
    transition: margin-left 0.3s ease;
  }
  .expanded-header {
    margin-left: 0 !important;
  }
  .no-transition .main-sidebar,
  .no-transition .content-wrapper,
  .no-transition .main-header {
    transition: none !important;
  }
  /* Apply transitions only after the page has loaded */
  body.page-loaded .main-sidebar,
  body.page-loaded .content-wrapper,
  body.page-loaded .main-header {
    transition: transform 0.5s ease, opacity 0.5s ease, margin-left 0.5s ease;
  }
  .notifications-panel {
    position: fixed;
    right: -300px;
    top: 0;
    width: 300px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
    transition: right 0.3s ease;
    z-index: 1040;
    overflow-y: auto;
  }

  .notifications-panel.active {
    right: 0;
  }

  .notifications-header {
    padding: 15px;
    background: #007bff;
    color: #fff;
  }

  .notifications-section {
    border-bottom: 1px solid #eee;
    padding: 10px 0;
  }

  .notifications-section-header {
    padding: 10px 15px;
    font-weight: bold;
    background: #f8f9fa;
    cursor: pointer;
    user-select: none;
    position: relative;
  }

  .notifications-section-header:after {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 15px;
    transition: transform 0.3s ease;
  }

  .notifications-section-header.collapsed:after {
    transform: rotate(-90deg);
  }

  .notifications-section-content {
    max-height: 300px;
    overflow-y: auto;
    transition: max-height 0.3s ease;
  }

  .notifications-section-content.collapsed {
    max-height: 0;
    overflow: hidden;
  }

  .notification-item {
    padding: 10px 15px;
    border-bottom: 1px solid #f4f4f4;
    display: block;
    color: #333;
    text-decoration: none;
  }

  .notification-item:hover {
    background: #f8f9fa;
    text-decoration: none;
  }

  .notifications-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1039;
    display: none;
  }
  .bg-danger-light {
    background-color: rgba(220, 53, 69, 0.1);
  }
  .bg-warning-light {
    background-color: rgba(255, 193, 7, 0.1);
  }
</style>
<!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-dark border border-light border-top-0  border-left-0 border-right-0 navbar-light text-sm bg-grey">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
          <a class="nav-link" id="toggle-nav" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?></a>
          </li>
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" href="#" id="notificationsToggle">
                <i class="fas fa-bell"></i>
                <?php if(($deliveries_count + $payments_count + $tasks_pending) > 0): ?>
                    <span class="badge badge-warning navbar-badge">
                        <?php echo $deliveries_count + $payments_count + $tasks_pending ?>
                    </span>
                <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <div class="btn-group nav-link">
                  <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                  </div>
              </div>
          </li>
          <li class="nav-item">
            
          </li>
         <!--  <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
            </a>
          </li> -->
        </ul>
      </nav>
      <!-- /.navbar -->
<div class="notifications-overlay"></div>
<div class="notifications-panel">
    <div class="notifications-header d-flex justify-content-between align-items-center">
        <h5 class="m-0">Notifications</h5>
        <button type="button" class="close text-white" id="closeNotifications">
            <span>&times;</span>
        </button>
    </div>    

    <!-- Deliveries Section -->
    <div class="notifications-section">
        <div class="notifications-section-header" data-section="deliveries">
            <i class="fas fa-truck mr-2"></i> Deliveries (<?php echo $deliveries_count ?>)
        </div>
        <div class="notifications-section-content" id="deliveries-section">
            <?php if($deliveries_count > 0):
                // Overdue deliveries
                $overdue_deliveries = $conn->query("SELECT po.*, c.company_name 
                    FROM purchase_orders po 
                    LEFT JOIN clients c ON po.client_id = c.id
                    WHERE DATE(po.expected_delivery) < '{$today}' 
                    AND po.status != 'completed'
                    ORDER BY po.expected_delivery ASC");
                
                // Today's deliveries
                $today_deliveries = $conn->query("SELECT po.*, c.company_name 
                    FROM purchase_orders po 
                    LEFT JOIN clients c ON po.client_id = c.id
                    WHERE DATE(po.expected_delivery) = '{$today}' 
                    AND po.status != 'completed'
                    ORDER BY po.expected_delivery ASC");
                
                // Upcoming deliveries
                $upcoming_deliveries = $conn->query("SELECT po.*, c.company_name 
                    FROM purchase_orders po 
                    LEFT JOIN clients c ON po.client_id = c.id
                    WHERE DATE(po.expected_delivery) BETWEEN DATE_ADD('{$today}', INTERVAL 1 DAY) 
                        AND DATE_ADD('{$today}', INTERVAL 7 DAY)
                    AND po.status != 'completed'
                    ORDER BY po.expected_delivery ASC");

                // Display overdue deliveries
                while($row = $overdue_deliveries->fetch_assoc()): ?>
                    <a href="<?php echo base_url ?>admin/?page=po_details/view_po_details&id=<?php echo $row['id'] ?>" 
                       class="notification-item bg-danger-light">
                        <i class="fas fa-exclamation-circle text-danger mr-2"></i> <?php echo $row['po_code'] ?>
                        <span class="float-right text-danger text-sm">
                            Overdue: <?php echo date('d M', strtotime($row['expected_delivery'])) ?>
                        </span>
                        <br>
                        <small class="text-muted"><?php echo $row['company_name'] ?></small>
                    </a>
                <?php endwhile;

                // Display today's deliveries
                while($row = $today_deliveries->fetch_assoc()): ?>
                    <a href="<?php echo base_url ?>admin/?page=po_details/view_po_details&id=<?php echo $row['id'] ?>" 
                       class="notification-item bg-warning-light">
                        <i class="fas fa-clock text-warning mr-2"></i> <?php echo $row['po_code'] ?>
                        <span class="float-right text-warning text-sm">
                            Today
                        </span>
                        <br>
                        <small class="text-muted"><?php echo $row['company_name'] ?></small>
                    </a>
                <?php endwhile;

                // Display upcoming deliveries
                while($row = $upcoming_deliveries->fetch_assoc()): ?>
                    <a href="<?php echo base_url ?>admin/?page=po_details/view_po_details&id=<?php echo $row['id'] ?>" 
                       class="notification-item">
                        <i class="fas fa-calendar-alt text-info mr-2"></i> <?php echo $row['po_code'] ?>
                        <span class="float-right text-info text-sm">
                            <?php echo date('d M', strtotime($row['expected_delivery'])) ?>
                        </span>
                        <br>
                        <small class="text-muted"><?php echo $row['company_name'] ?></small>
                    </a>
                <?php endwhile;
            else: ?>
                <div class="notification-item text-muted">
                    <i class="fas fa-check mr-2"></i> No deliveries to show
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Payments Section -->
    <div class="notifications-section">
        <div class="notifications-section-header" data-section="payments">
            <i class="fas fa-money-bill mr-2"></i> Pending Payments (<?php echo $payments_count ?>)
        </div>
        <div class="notifications-section-content" id="payments-section">
            <?php if($payments_count > 0):
                $pending_pos = $conn->query("SELECT 
                        po.*, 
                        c.company_name,
                        pil.total_amount,
                        (COALESCE(po.advance_received, 0) + 
                         COALESCE(po.inspection_received, 0) + 
                         COALESCE(po.installation_received, 0) + 
                         COALESCE(po.credit_received, 0)) as total_received,
                        (pil.total_amount - (
                            COALESCE(po.advance_received, 0) + 
                            COALESCE(po.inspection_received, 0) + 
                            COALESCE(po.installation_received, 0) + 
                            COALESCE(po.credit_received, 0)
                        )) as balance
                    FROM purchase_orders po 
                    INNER JOIN clients c ON po.client_id = c.id
                    INNER JOIN proforma_invoice_list pil ON po.po_code = pil.po_code 
                    WHERE po.status = 'completed' 
                    AND (COALESCE(po.advance_received, 0) + 
                        COALESCE(po.inspection_received, 0) + 
                        COALESCE(po.installation_received, 0) + 
                        COALESCE(po.credit_received, 0)) < pil.total_amount
                    ORDER BY po.expected_delivery ASC");
                while($row = $pending_pos->fetch_assoc()): ?>
                    <a href="<?php echo base_url ?>admin/?page=po_details/view_po_details&id=<?php echo $row['id'] ?>" 
                       class="notification-item">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> <?php echo $row['po_code'] ?>
                        <span class="float-right text-danger text-sm">
                            ₹<?php echo number_format($row['balance'], 2) ?>
                        </span>
                        <br>
                        <small class="text-muted"><?php echo $row['company_name'] ?></small>
                    </a>
                <?php endwhile;
            else: ?>
                <div class="notification-item text-muted">
                    <i class="fas fa-check mr-2"></i> No pending payments
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tasks Section -->
    <div class="notifications-section">
        <div class="notifications-section-header" data-section="tasks">
            <i class="fas fa-tasks mr-2"></i> My Tasks (<?php echo $tasks_pending ?>)
        </div>
        <div class="notifications-section-content" id="tasks-section">
            <?php if($tasks_pending > 0):
                $pending_tasks = $conn->query("SELECT t.*, u.username as assigned_by_name 
                    FROM tasks t 
                    INNER JOIN users u ON t.assigned_by = u.id
                    WHERE t.assigned_to = '{$_SESSION['userdata']['id']}' 
                    AND t.status != 'completed'
                    ORDER BY t.due_date ASC");
                while($row = $pending_tasks->fetch_assoc()): ?>
                    <a href="<?php echo base_url ?>admin/?page=tasks/view_task&id=<?php echo $row['id'] ?>" 
                       class="notification-item <?php echo (date('Y-m-d', strtotime($row['due_date'])) < date('Y-m-d')) ? 'bg-danger-light' : '' ?>">
                        <i class="fas fa-clipboard-list mr-2"></i> <?php echo $row['title'] ?>
                        <span class="float-right text-muted text-sm">
                            Due: <?php echo date('d M', strtotime($row['due_date'])) ?>
                        </span>
                        <br>
                        <small class="text-muted">Assigned by: <?php echo $row['assigned_by_name'] ?></small>
                    </a>
                <?php endwhile;
            else: ?>
                <div class="notification-item text-muted">
                    <i class="fas fa-check mr-2"></i> No pending tasks
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add the no-transition class initially
    document.body.classList.add('no-transition');

    // Apply the saved state on page load
    if (localStorage.getItem('sidebarHidden') === 'true') {
      document.querySelector('.main-sidebar').classList.add('hidden-nav');
      document.querySelector('.content-wrapper').classList.add('expanded-content');
      document.querySelector('.main-header').classList.add('expanded-header');
    }

    // Remove the no-transition class and add the page-loaded class after the page has loaded
    setTimeout(function() {
      document.body.classList.remove('no-transition');
      document.body.classList.add('page-loaded');
    }, 0);

    // Toggle the sidebar and save the state
    document.getElementById('toggle-nav').addEventListener('click', function() {
      document.querySelector('.main-sidebar').classList.toggle('hidden-nav');
      document.querySelector('.content-wrapper').classList.toggle('expanded-content');
      document.querySelector('.main-header').classList.toggle('expanded-header');

      // Save the state in localStorage
      const sidebarHidden = document.querySelector('.main-sidebar').classList.contains('hidden-nav');
      localStorage.setItem('sidebarHidden', sidebarHidden);
    });

    const panel = document.querySelector('.notifications-panel');
    const overlay = document.querySelector('.notifications-overlay');
    const toggleBtn = document.getElementById('notificationsToggle');
    const closeBtn = document.getElementById('closeNotifications');
    const sectionHeaders = document.querySelectorAll('.notifications-section-header');
    
    function openPanel() {
        panel.classList.add('active');
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        panel.classList.remove('active');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Simplified collapsible sections without localStorage
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sectionContent = this.nextElementSibling;
            this.classList.toggle('collapsed');
            sectionContent.classList.toggle('collapsed');
        });
    });

    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openPanel();
    });

    closeBtn.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
});
</script>