<?php
include_once("../../process/profiling/isynstaff.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadStaff'){
    $process->LoadStaff();
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetStaffInfo'){
    $process->GetStaffInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveInfo'){
    $process->SaveInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateInfo'){
    $process->UpdateInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadDropdowns'){ 
    $process->LoadDropdowns(); 
}

// --- REPORTS ---
include_once("../../reports/profiling/isynstaff.reports.php");
$reports = new StaffReports();

if(isset($_GET['action']) AND $_GET['action'] == 'PrintStaffReport'){
    $reports->PrintStaffReportPDF($_GET['filter'] ?? 'ALL');
}

if(isset($_GET['action']) AND $_GET['action'] == 'PrintStaffReportExcel'){
    $reports->PrintStaffReportExcel($_GET['filter'] ?? 'ALL');
}
?>