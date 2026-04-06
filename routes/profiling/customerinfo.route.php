<?php
include_once("../../process/profiling/customerinfo.process.php");
include_once("../../reports/profiling/customer.reports.php");

$report = new Reports();
$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomerList'){
    $process->LoadCustomerList();
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetCustomerInfo'){
    $process->GetCustomerInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomerTypes'){
    $process->LoadCustomerTypes();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadGenders'){
    $process->LoadGenders();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSuffixes'){
    $process->LoadSuffixes();
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveInfo'){
    $process->SaveInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateInfo'){
    $process->UpdateInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GenerateCustomerNo'){
    $process->GenerateCustomerNo();
}

if(isset($_GET['action']) && $_GET['action'] == 'PrintCustomerReport'){
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';
    $report->PrintCustomerReport($filter);
}

if(isset($_GET['action']) && $_GET['action'] == 'PrintCustomerReportExcel'){
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';
    $report->PrintCustomerReportExcel($filter);
}
?>