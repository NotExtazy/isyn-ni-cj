<?php
include_once("../../process/profiling/supplierinfo.process.php");
include_once("../../reports/profiling/supplier.reports.php");

$process = new Process();
$report = new SupplierReports();

if(isset($_POST['action'])){
    $action = $_POST['action'];
    switch($action){
        case 'LoadSupplierList': $process->LoadSupplierList(); break;
        case 'gnrtSupID': $process->gnrtSupID(); break;
        case 'SaveInfo': $process->SaveInfo($_POST); break;
        case 'GetSupplierInfo': $process->GetSupplierInfo($_POST); break;
        case 'UpdateInfo': $process->UpdateInfo($_POST); break;
    }
}

if(isset($_GET['action'])){
    $action = $_GET['action'];
    if($action == 'PrintSupplierReport'){
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';
        $report->PrintSupplierReportPDF($filter);
    }
    if($action == 'PrintSupplierReportExcel'){
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';
        $report->PrintSupplierReportExcel($filter);
    }
}
?>