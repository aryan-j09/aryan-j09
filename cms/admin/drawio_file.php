<?php
/**
 * Serves whitelisted Draw.io files with CORS so app.diagrams.net can load them.
 */

$allowed_files = [
    'Workflow_1_Lead_to_Client_to_PI_to_PO.drawio',
    'Workflow_2_PO_to_Stock_to_Project.drawio',
    'Workflow_3_Stock_Movement.drawio',
    'SB_Panchal_CMS_Architecture.drawio',
];

$file = isset($_GET['file']) ? basename((string) $_GET['file']) : '';

if (!in_array($file, $allowed_files, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'File not found';
    exit;
}

$full_path = realpath(__DIR__ . '/../' . $file);
$base_path = realpath(__DIR__ . '/..');

if ($full_path === false || $base_path === false || strpos($full_path, $base_path) !== 0 || !is_file($full_path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'File not found';
    exit;
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');
header('Content-Type: application/xml; charset=UTF-8');

readfile($full_path);
