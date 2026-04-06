<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests, check if user is authenticated
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Authentication required']);
    exit;
}

include_once(__DIR__ . "/../../process/inventorymanagement/inventory.process.php");
include_once(__DIR__ . "/../../reports/inventorymanagement/inventory.reports.php");

$process = new Process();
$report = new Reports();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json');
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
    register_shutdown_function(function() {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>"Server error: ".$e['message']]);
        }
    });
}
if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'BuildReportTable'){
    $process->BuildReportTable($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomColumnNames'){
    $process->LoadCustomColumnNames($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomColumnValue'){
    $process->LoadCustomColumnValue($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SearchInventory'){
    $process->SearchInventory($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GenerateInventoryReport'){
    $process->GenerateInventoryReport($_POST);
}

if(isset($_GET['type']) AND $_GET['type'] == 'PrintInventoryReport'){
    $headerData = $_SESSION['headerData'];
    $tableData = $_SESSION['tableData'];
    $isynbranch = $_SESSION['isynbranch'];
    $reportType = $_SESSION['reportType'];
    $report->PrintInventoryReport($headerData, $tableData, $isynbranch, $reportType);
}

