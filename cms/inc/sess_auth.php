<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    $link = "https"; 
else
    $link = "http"; 
$link .= "://"; 
$link .= $_SERVER['HTTP_HOST']; 
$link .= $_SERVER['REQUEST_URI'];

if(!isset($_SESSION['userdata']) && !strpos($link, 'login.php')){
    if($isAjax) {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Session expired', 'redirect' => 'admin/login.php']);
        exit;
    } else {
        // Regular redirect for non-AJAX requests
        echo "<script>location.href='admin/login.php'</script>";
        exit;
    }
}

if(isset($_SESSION['userdata']) && strpos($link, 'login.php')){
    echo "<script>location.href='admin/index.php'</script>";
    exit;
}
