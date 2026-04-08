<?php
// Check if user is logged in before accessing session data
if (!isset($_SESSION['userdata']) || empty($_SESSION['userdata']['id'])) {
    header('Location: ' . base_url . 'admin/login.php');
    exit;
}

$user_id = $_SESSION['userdata']['id'];
$today = date('Y-m-d');

$tasks_count = $conn->query("SELECT COUNT(*) as count FROM tasks 
    WHERE assigned_to = '{$user_id}' 
      AND status IN ('pending', 'in_progress')")->fetch_assoc()['count'];

$followups_count = $conn->query("SELECT COUNT(*) as count FROM lead_activities 
    WHERE DATE(next_followup) <= '{$today}' AND created_by = '{$user_id}' AND (handled IS NULL OR handled = 0)")->fetch_assoc()['count'];

$daily_tasks_count = $conn->query("SELECT COUNT(*) as count FROM daily_tasks 
    WHERE user_id = '{$user_id}' 
      AND completed = 0
      AND task_date <= '{$today}'")->fetch_assoc()['count'];

$total_tasks_count = $tasks_count + $followups_count + $daily_tasks_count;
$current_user = isset($_SESSION['userdata']) ? $_SESSION['userdata'] : array();
?>

<!-- Flash CSS for Tasks link when user has pending assigned tasks -->
<style>
  /* Flash only the nav item's background (yellow) 5 times; do not change text/icon color */
  .nav-item .nav-link.flash-tasks {
    /* slower: run the flash 3 times */
    animation: task-flash 1.2s ease-in-out 0s 3 both !important;
  }
  @keyframes task-flash {
    0% { background-color: transparent; }
    50% { background-color: rgba(255,193,7,1); }
    100% { background-color: transparent; }
  }
</style>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4 sidebar-no-expand">
  <!-- Brand Logo -->
  <a href="<?php echo base_url ?>admin" class="brand-link bg-primary text-sm">
    <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="Store Logo" class="brand-image" style="width: 13rem; height: auto;">
  </a>
  <!-- Sidebar -->
  <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
    <div class="os-resize-observer-host observed">
      <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
    </div>
    <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
      <div class="os-resize-observer"></div>
    </div>
    <div class="os-content-glue" style="margin: 0px -8px; width: 249px; height: 646px;"></div>
    <div class="os-padding">
      <div class="os-viewport os-viewport-native-scrollbars-invisible" style="overflow-y: scroll;">
        <div class="os-content" style="padding: 0px 8px; height: 100%; width: 100%;">
          <!-- Sidebar user panel (optional) -->
          <div class="clearfix"></div>
          <!-- Sidebar Menu -->
          <nav class="mt-4">
            <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
              <?php if(cms_user_can_access_module($conn, $current_user, 'home')): ?>
              <li class="nav-item dropdown">
                <a href="./" class="nav-link nav-home">
                  <i class="nav-icon fas fa-tachometer-alt"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'purchase_order')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=purchase_order" class="nav-link nav-purchase_order">
                  <i class="nav-icon fas fa-th-list"></i>
                  <p>Purchase Order</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'stock')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=stock" class="nav-link nav-stock">
                  <i class="nav-icon fas fa-exchange-alt"></i>
                  <p>Stock In/Out</p>
                </a>
              </li>
              <?php endif; ?>              
              <?php if(cms_user_can_access_module($conn, $current_user, 'chemical_inventory')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=chemical_inventory" class="nav-link nav-chemical_inventory nav-chemical_inventory_incoming nav-chemical_inventory_outgoing">
                  <i class="nav-icon fas fa-flask"></i>
                  <p>Lab Inventory</p>
                </a>
              </li>
              <?php endif; ?>
              <!-- <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=stock_orders" class="nav-link nav-stock_orders">
                  <i class="nav-icon fas fa-shopping-cart"></i>
                  <p>Stock Order</p>
                </a>
              </li> -->
              <?php if(cms_user_can_access_module($conn, $current_user, 'proforma_invoice')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=proforma_invoice" class="nav-link nav-proforma_invoice">
                  <i class="nav-icon fas fa-file-invoice-dollar"></i>
                  <p>Proforma Invoice</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'po_details')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=po_details" class="nav-link nav-po_details">
                  <i class="nav-icon fas fa-file-alt"></i>
                  <p>PO Factory Details</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'project_planner2')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=project_planner2" class="nav-link nav-project_planner2">
                  <i class="nav-icon fas fa-sitemap"></i>
                  <p>Project Planner</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'leads')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=leads" class="nav-link nav-leads">
                  <i class="nav-icon fas fa-user-tag"></i>
                  <p>CRM</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'tasks')): ?>
              <li class="nav-item">
                <?php // Flash the Tasks link only immediately after login when user has pending assigned tasks. Use session flag set at login and clear it so refresh won't re-trigger. ?>
                <?php
                  $show_tasks_flash = false;
                  if(isset($_SESSION['show_tasks_flash']) && $_SESSION['show_tasks_flash'] && $tasks_count > 0){
                    $show_tasks_flash = true;
                    unset($_SESSION['show_tasks_flash']);
                  }
                ?>
                <a href="<?php echo base_url ?>admin/?page=tasks" class="nav-link nav-tasks <?php echo ($show_tasks_flash) ? 'flash-tasks' : '' ?>">
                  <i class="nav-icon fas fa-tasks"></i>
                  <p>
                    Tasks
                    <?php if ($total_tasks_count > 0): ?>
                      <span class="badge badge-warning right">
                        <?php echo $total_tasks_count; ?>
                      </span>
                    <?php endif; ?>
                  </p>
                </a>
              </li>
              <?php endif; ?>
              <!-- <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=quotations" class="nav-link nav-quotations">
                  <i class="nav-icon fas fa-file-invoice"></i>
                  <p>Quotations</p>
                </a>
              </li> -->
              <?php if(cms_user_can_access_module($conn, $current_user, 'clients')): ?>
                <li class="nav-item dropdown">
                  <a href="<?php echo base_url ?>admin/?page=clients" class="nav-link nav-clients">
                    <i class="nav-icon fas fa-users"></i>
                    <p>Client list</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'maintenance')): ?>
                <li class="nav-item dropdown">
                  <a href="<?php echo base_url ?>admin/?page=maintenance/supplier" class="nav-link nav-maintenance_supplier">
                    <i class="nav-icon fas fa-truck-loading"></i>
                    <p>Supplier List</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'utility')): ?>
                <li class="nav-item dropdown">
                  <a href="<?php echo base_url ?>admin/?page=utility" class="nav-link nav-utility">
                    <i class="nav-icon fas fa-bolt"></i>
                    <p>Utility Suppliers</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'chemicals')): ?>
              <li class="nav-item">
                <a href="<?php echo base_url ?>admin/?page=chemicals" class="nav-link nav-chemicals">
                  <i class="nav-icon fas fa-vials"></i>
                  <p>Lab Items</p>
                </a>
              </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'machine_items')): ?>
                <li class="nav-item">
                  <a href="<?php echo base_url ?>admin/?page=machine_items" class="nav-link nav-machine_items">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>Machine Items</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'quote_items')): ?>
                <li class="nav-item">
                  <a href="<?php echo base_url ?>admin/?page=quote_items" class="nav-link nav-quote_items">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>Quote Items</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'maintenance')): ?>
                <li class="nav-item dropdown">
                  <a href="<?php echo base_url ?>admin/?page=maintenance/item" class="nav-link nav-maintenance_item">
                    <i class="nav-icon fas fa-box"></i>
                    <p>Item List</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'user')): ?>
                <li class="nav-item dropdown">
                  <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                    <i class="nav-icon fas fa-users"></i>
                    <p>User List</p>
                  </a>
                </li>
              <?php endif; ?>
              <?php if(cms_user_can_access_module($conn, $current_user, 'system_info')): ?>
                <li class="nav-item dropdown">
                  <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
                    <i class="nav-icon fas fa-cogs"></i>
                    <p>Settings</p>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
          <!-- /.sidebar-menu -->
        </div>
      </div>
    </div>
    <div class="os-scrollbar os-scrollbar-horizontal os-scrollbar-unusable os-scrollbar-auto-hidden">
      <div class="os-scrollbar-track">
        <div class="os-scrollbar-handle" style="width: 100%; transform: translate(0px, 0px);"></div>
      </div>
    </div>
    <div class="os-scrollbar os-scrollbar-vertical os-scrollbar-auto-hidden">
      <div class="os-scrollbar-track">
        <div class="os-scrollbar-handle" style="height: 55.017%; transform: translate(0px, 0px);"></div>
      </div>
    </div>
    <div class="os-scrollbar-corner"></div>
  </div>
  <!-- /.sidebar -->
</aside>
<script>
  var page;
  $(document).ready(function() {
    page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
    page = page.replace(/\//gi, '_');

    if ($('.nav-link.nav-' + page).length > 0) {
      $('.nav-link.nav-' + page).addClass('active')
      if ($('.nav-link.nav-' + page).hasClass('tree-item') == true) {
        $('.nav-link.nav-' + page).closest('.nav-treeview').siblings('a').addClass('active')
        $('.nav-link.nav-' + page).closest('.nav-treeview').parent().addClass('menu-open')
      }
      if ($('.nav-link.nav-' + page).hasClass('nav-is-tree') == true) {
        $('.nav-link.nav-' + page).parent().addClass('menu-open')
      }
    }

    $('#receive-nav').click(function() {
      $('#uni_modal').on('shown.bs.modal', function() {
        $('#find-transaction [name="tracking_code"]').focus();
      })
      uni_modal("Enter Tracking Number", "transaction/find_transaction.php");
    })
  })
</script>