<?php
include_once("../../process/cashier/tellersproofsheet.process.php");
include_once("../../reports/cashier/tellersproofsheet.reports.php");

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadTotals'){
    $process->LoadTotals($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadScheduleA'){
    $process->LoadScheduleA($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadScheduleB'){
    $process->LoadScheduleB($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadScheduleC'){
    $process->LoadScheduleC($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadUndepToday'){
    $process->LoadUndepToday($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ToSession'){
    $process->ToSession($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'TellerProofSheetReport'){
    $report->TellerProofSheet($_SESSION['billcoins'],$_SESSION['TotalUndepPrev'],$_SESSION['TotalCollections'],$_SESSION['TotalDeposit'],$_SESSION['TotalUndepDayEnd'],$_SESSION['scheduleA'],$_SESSION['scheduleB'],$_SESSION['scheduleC'],$_SESSION['todayUndep'],$_SESSION['psdate']);
}

