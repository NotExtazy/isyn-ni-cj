<?php
include_once("../../process/profiling/acashinfo.process.php");
if (function_exists('opcache_invalidate')) {
    opcache_invalidate(realpath("../../process/profiling/acashinfo.process.php"), true);
}
include_once("../../reports/profiling/acashinfo.reports.php");

/** @var AcashInfoProcess $process */
$process = new AcashInfoProcess();
$report = new AcashReports();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadAcashInfo'){
    $process->LoadAcashInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadEcpayTransactions'){
    $process->LoadEcpayTransactions($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadEmails'){
    $process->LoadEmails($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBranches'){
    $process->LoadBranches($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadMaintenanceData'){
    $process->LoadMaintenanceData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveMaintenanceData'){
    $process->SaveMaintenanceData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadGraphData'){
    $process->LoadGraphData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UploadCustom'){
    $process->UploadCustom($_FILES);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UploadRaw'){
    $process->UploadRaw($_FILES);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadZeroAmountAcash'){
    $process->LoadZeroAmountAcash($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadYearlyData'){
    $process->LoadYearlyData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SimpleDataTest'){
    $process->SimpleDataTest();
}

if(isset($_POST['action']) AND $_POST['action'] == 'TestDataCheck'){
    $process->TestDataCheck();
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateZeroAmounts'){
    $process->UpdateZeroAmounts($_POST);
}

if(isset($_GET['action']) AND $_GET['action'] == 'PrintAcashReport'){
    $type = isset($_GET['type']) ? $_GET['type'] : 'Main';
    $report->PrintAcashReport($type);
}

if(isset($_GET['action']) AND $_GET['action'] == 'PrintEcpayReport'){
    $type = isset($_GET['type']) ? $_GET['type'] : 'Main';
    $report->PrintEcpayReport($type);
}

