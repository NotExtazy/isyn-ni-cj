<?php
include_once("../../reports/profiling/bod.reports.php");
include_once("../../process/profiling/bod.process.php");

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBODList'){
    $process->LoadBODList($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCMMTTList'){
    $process->LoadCMMTTList($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSelectOptions'){
    $process->LoadSelectOptions();
}

if(isset($_POST['action']) AND $_POST['action'] == 'getBODInfo'){
    $process->getBODInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveInfo'){
    $process->SaveInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateInfo'){
    $process->UpdateInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GenerateBODReport'){
    $process->GenerateBODReport($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintBODReport'){
    if (isset($_GET['format']) && $_GET['format'] == 'excel') {
        $report->ExportBODToExcel($_SESSION['headerData'],$_SESSION['tableData'],$_SESSION['reportYear']);
    } else {
        $report->PrintBODReport($_SESSION['headerData'],$_SESSION['tableData'],$_SESSION['reportYear']);
    }
}

if(isset($_POST['action']) AND $_POST['action'] == 'GenerateCommitteeReport'){
    $process->GenerateCommitteeReport($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintCommitteeReport'){
    if (isset($_GET['format']) && $_GET['format'] == 'excel') {
        $report->ExportCommitteeToExcel($_SESSION['cmmttHeaderData'],$_SESSION['cmmttTableData'],$_SESSION['cmmttReportYear']);
    } else {
        $report->PrintCommitteeReport($_SESSION['cmmttHeaderData'],$_SESSION['cmmttTableData'],$_SESSION['cmmttReportYear']);
    }
}
