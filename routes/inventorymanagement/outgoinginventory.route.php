<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests, check if user is authenticated
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Authentication required']);
    exit;
}

include_once(__DIR__ . "/../../process/inventorymanagement/outgoinginventory.process.php");
include_once(__DIR__ . "/../../reports/inventorymanagement/outgoinginventory.reports.php");

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

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBranch'){
    $process->LoadBranch($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCategory'){
    $process->LoadCategory($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSerialProduct'){
    $process->LoadSerialProduct($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadProductSummary'){
    $process->LoadProductSummary($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomerName'){
    $process->LoadCustomerName($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomerNameInfo'){
    $process->LoadCustomerNameInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'AddToItems'){
    $process->AddToItems($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UseQtyFromBranchConsign'){
    $process->UseQtyFromBranchConsign($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadRegion'){
    $process->LoadRegion();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadProvince'){
    $process->LoadProvince($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCity'){
    $process->LoadCity($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBarangay'){
    $process->LoadBarangay($_POST);
}

// ======================================================================
// Load Transaction List
if(isset($_POST['action']) AND $_POST['action'] == 'LoadTransaction'){
    $process->LoadTransaction();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadInventoryOut'){
    $process->LoadInventoryOut();
}

if(isset($_POST['action']) AND $_POST['action'] == 'PrintRecentOut'){
    $_SESSION['recentOutData'] = json_decode($_POST['DATA']);
    echo json_encode(["STATUS"=>"success"]);
}
if(isset($_POST['action']) AND $_POST['action'] == 'DeleteFromItems'){
    $process->DeleteFromItems($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'TransmittalSearch'){
    $process->TransmittalSearch($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'fetchProducts'){
    $process->fetchProducts($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadtoList'){
    $process->LoadtoList($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SubmitInvOut'){
    $process->SubmitInvOut($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintSalesInvoice'){
    $report->PrintOutgoingSalesInvoice($_SESSION['tableData'],$_SESSION['SalesNoVAT'],$_SESSION['SalesWithVAT'], $_SESSION['SIRef'],$_SESSION['DateAdded']);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintRecentOut'){
    $report->PrintRecentInventoryOut($_SESSION['recentOutData'] ?? []);
}