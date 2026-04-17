<?php
// DEBUG: Remember to revert any error reporting or config changes after debugging!
ob_start();
ini_set('date.timezone','Asia/Kolkata');
date_default_timezone_set('Asia/Kolkata');
session_start();

require_once('initialize.php');
require_once('classes/DBConnection.php');
require_once('classes/SystemSettings.php');
$db = new DBConnection;
$conn = $db->conn;

function redirect($url=''){
	if(!empty($url))
	echo '<script>location.href="'.base_url .$url.'"</script>';
}
function validate_image($file){
	if(!empty($file)){
			// exit;
        $ex = explode('?',$file);
        $file = $ex[0];
        $param =  isset($ex[1]) ? '?'.$ex[1]  : '';
		if(is_file(base_app.$file)){
			return base_url.$file.$param;
		}else{
			return base_url.'dist/img/no-image-available.png';
		}
	}else{
		return base_url.'dist/img/no-image-available.png';
	}
}
function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );

    //Return true if Mobile User Agent is detected
    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    //Otherwise return false..  
    return false;
}

function cms_module_catalog(){
    return array(
        'home' => 'Dashboard',
        'purchase_order' => 'Purchase Order',
        'stock' => 'Stock In/Out',
        'chemicals' => 'Chemicals',
        'chemical_inventory' => 'Chemical Inventory',
        'proforma_invoice' => 'Proforma Invoice',
        'po_details' => 'PO Factory Details',
        'project_planner2' => 'Project Planner',
        'lab_trial_reports' => 'Lab Trial Reports',
        'leads' => 'CRM',
        'tasks' => 'Tasks',
        'clients' => 'Client list',
        'maintenance' => 'Supplier List',
        'utility' => 'Utility Suppliers',
        'machine_items' => 'Machine Items',
        'quote_items' => 'Quote Items',
        'user' => 'User List',
        'system_info' => 'Settings'
    );
}

function cms_admin_only_modules(){
    return array('user', 'system_info');
}

function cms_assignable_user_modules(){
    return array('home', 'purchase_order', 'stock', 'chemicals', 'chemical_inventory', 'proforma_invoice', 'po_details', 'project_planner2', 'lab_trial_reports', 'leads', 'tasks', 'clients', 'maintenance', 'utility', 'machine_items', 'quote_items');
}

function cms_bootstrap_access_table($conn){
    static $ready = false;
    if($ready){
        return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS `user_module_access` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `modules` text DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_user_module_access_user_id` (`user_id`),
        KEY `idx_user_module_access_updated_by` (`updated_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    $ready = true;
}

function cms_extract_page_module($page){
    $page = trim((string)$page);
    if($page === ''){
        return 'home';
    }
    $page = str_replace('\\', '/', $page);
    $parts = explode('/', $page);
    $module = trim((string)$parts[0]);
    return $module === '' ? 'home' : $module;
}

function cms_get_user_access_modules($conn, $user_id, $user_type){
    $catalog = cms_module_catalog();
    if((int)$user_type === 1){
        return array_keys($catalog);
    }

    cms_bootstrap_access_table($conn);
    $user_id = (int)$user_id;
    $qry = $conn->query("SELECT modules FROM user_module_access WHERE user_id = {$user_id} LIMIT 1");

    if($qry && $qry->num_rows > 0){
        $row = $qry->fetch_assoc();
        $decoded = json_decode((string)$row['modules'], true);
        if(!is_array($decoded)){
            $decoded = array();
        }
        $allowed = array();
        foreach($decoded as $module){
            $module = trim((string)$module);
            if($module !== '' && isset($catalog[$module])){
                $allowed[] = $module;
            }
        }
        return array_values(array_unique($allowed));
    }

    return cms_assignable_user_modules();
}

function cms_user_can_access_module($conn, $user_data, $module){
    if(!is_array($user_data) || !isset($user_data['id'])){
        return false;
    }

    $module = trim((string)$module);
    if($module === ''){
        $module = 'home';
    }

    $catalog = cms_module_catalog();
    if(!isset($catalog[$module])){
        return false;
    }

    $user_id = (int)$user_data['id'];
    
    $fresh_user = $conn->query("SELECT type FROM users WHERE id = {$user_id} LIMIT 1");
    if(!$fresh_user || $fresh_user->num_rows === 0){
        return false;
    }
    $user_row = $fresh_user->fetch_assoc();
    $user_type = (int)$user_row['type'];

    if($user_type === 1){
        return true;
    }

    if(in_array($module, cms_admin_only_modules(), true)){
        return false;
    }

    $allowed_modules = cms_get_user_access_modules($conn, $user_id, $user_type);
    return in_array($module, $allowed_modules, true);
}

function cms_user_can_access_page($conn, $user_data, $page){
    $module = cms_extract_page_module($page);
    return cms_user_can_access_module($conn, $user_data, $module);
}

function cms_save_user_access_modules($conn, $user_id, $modules, $updated_by = null){
    cms_bootstrap_access_table($conn);
    $catalog = cms_module_catalog();

    $user_id = (int)$user_id;
    if($user_id <= 0){
        return false;
    }

    if(!is_array($modules)){
        $modules = array();
    }

    $clean = array();
    foreach($modules as $module){
        $module = trim((string)$module);
        if($module !== '' && isset($catalog[$module])){
            $clean[] = $module;
        }
    }
    $clean = array_values(array_unique($clean));

    $json_modules = $conn->real_escape_string(json_encode($clean));
    $updated_by_sql = is_null($updated_by) ? 'NULL' : (int)$updated_by;

    $sql = "INSERT INTO user_module_access (user_id, modules, updated_by)
            VALUES ({$user_id}, '{$json_modules}', {$updated_by_sql})
            ON DUPLICATE KEY UPDATE modules = VALUES(modules), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP";
    return $conn->query($sql);
}
ob_end_flush();
?>

