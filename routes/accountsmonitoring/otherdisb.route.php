<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
include_once($docRoot . "/iSynApp-main/process/accountsmonitoring/otherdisb.process.php");
include_once($docRoot . "/iSynApp-main/reports/accountsmonitoring/otherdisb.reports.php");

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

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSLTypes'){
    $process->LoadSLTypes($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSL'){
    $process->LoadSL($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetBanks'){
    $process->GetBanks($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBankDetails'){
    $process->LoadBankDetails($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSLFromSubtype'){
    $process->LoadSLFromSubtype($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'Save'){
    $process->SaveCVEntry($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveToBooks'){
    $process->SaveToBooks($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'CDReport'){
    $report->CDReport($_SESSION["BATCHNO"]);
}

if(isset($_GET["type"]) && $_GET["type"] == 'CDReprintReport'){
    $report->CDReprintReport($_SESSION["CVDATE"],$_SESSION["CVNO"],$_SESSION["CVFUND"]);
}

if(isset($_GET["type"]) && $_GET["type"] == 'CheckReceiptMBTC'){
    $report->CheckReceiptMBTC($_SESSION["BATCHNO"]);
}
