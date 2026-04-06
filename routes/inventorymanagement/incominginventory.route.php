<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests, check if user is authenticated
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Authentication required']);
    exit;
}

$basePath = dirname(__DIR__, 2);
include_once($basePath . "/process/inventorymanagement/incominginventory.process.php");
include_once($basePath . "/reports/inventorymanagement/incominginventory.reports.php");

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

if(isset($_POST['action']) AND $_POST['action'] == 'LoadDataInventory'){
    $process->LoadDataInventory();
}

if(isset($_POST['action']) AND $_POST['action'] == 'DeleteFromDataInv'){
    $process->DeleteFromDataInv($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadProdCateg'){
    $process->LoadProdCateg($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadProdName'){
    $process->LoadProdName($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSupplier'){
    $process->LoadSupplier($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSupplierSI'){
    $process->LoadSupplierSI($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetProductPricing'){
    $process->GetProductPricing($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveSingle'){
    $process->SaveSingle($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateInventory'){
    $process->UpdateInventory($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveAll'){
    $process->SaveAll($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'PrintSupplierSalesInvoice'){
    $process->PrintSupplierSalesInvoice($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintSuppRcpt'){
    $report->PrintSupplierReceipt($_SESSION['tableData']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || empty($_POST['action']))) {
    echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>"Missing action"]);
}
