<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("../../database/connection.php");
include_once("../../process/profiling/shareholderinfo.process.php");
include_once("../../reports/profiling/shareholdercert.reports.php");

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadShareHolderNames'){
    $process->LoadShareHolderNames();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadDropdowns'){
    $process->LoadDropdowns();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadShareHolderList'){
    $process->LoadShareHolderList($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'getShareholderInfo'){
    $process->getShareholderInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'getShareholderConfig'){
    $process->getShareholderConfig();
}

if(isset($_POST['action']) AND $_POST['action'] == 'searchNames'){
    $process->searchNames($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'gnrtCertID'){
    $process->gnrtCertID();
}

if(isset($_POST['action']) AND $_POST['action'] == 'gnrtSID'){
    $process->gnrtSID();
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveInfo'){
    $process->SaveInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateInfo'){
    $process->UpdateInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateConfig'){
    $process->UpdateConfig($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ToSession'){
    $process->ToSession($_POST);
}
if(isset($_POST["action"]) && $_POST["action"] == "getShareholderByName"){
    $process->getShareholderByName($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "getShareholderCertificates"){
    $process->getShareholderCertificates($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "MarkAsPaid"){
    $process->MarkAsPaid($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "markCertPrinted"){
    $process->markCertPrinted($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "CheckBacklog"){
    $process->CheckBacklog($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintCertificate'){
    $certId = $_GET['certId'] ?? $_SESSION["CERT_ID"] ?? null;
    $shno = $_GET['shno'] ?? $_SESSION["SHNO"] ?? null;
    $format = $_GET['format'] ?? $_SESSION["FORMAT"] ?? '10M'; // Default if missing
    
    if($shno){
        $report->PrintCertificate($shno, $format, $certId);
    } else {
        echo "Error: Shareholder Number not specified.";
    }
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintShareholderReport'){
    $type = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';
    $report->PrintShareholderReport($type);
}

if(isset($_GET["type"]) && $_GET["type"] == 'ExportShareholderExcel'){
    $type = isset($_GET['filter']) ? $_GET['filter'] : 'ALL';
    $report->ExportShareholderExcel($type);
}
?>