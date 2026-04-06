<?php
include_once("../../process/accountsmonitoring/journalvoucher.process.php");
include_once("../../reports/accountsmonitoring/journalvoucher.reports.php");

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadPage'){
    $process->LoadPage();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBatchList'){
    $process->LoadBatchList();
}

if(isset($_POST['action']) AND $_POST['action'] == 'DeleteSaveBatchNo'){
    $process->DeleteSaveBatchNo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSaveBatchNo'){
    $process->LoadSaveBatchNo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetJVNo'){
    $process->GetJVNo($_POST,"");
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSLTypes'){
    $process->LoadSLTypes($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSL'){
    $process->LoadSL($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSLFromSubtype'){
    $process->LoadSLFromSubtype($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'Save'){
    $process->SaveJVEntry($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveToBooks'){
    $process->SaveToBooks($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'JVReport'){
    $report->JVReport($_SESSION["JVDATE"],$_SESSION["JVFUND"],$_SESSION["JVNO"]);
}