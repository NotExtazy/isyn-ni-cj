<?php
include_once("../../process/accountsmonitoring/boa.process.php");
include_once("../../reports/accountsmonitoring/boa.reports.php");

$process = new Process();
$reports = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadDataRows'){
    $process->LoadDataRows($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SearchBooks'){
    $process->SearchBooks($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SearchTransaction'){
    $process->SearchTransaction($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "PrintDetailedBOA"){
    $process->PrintDetailedBOA($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "PrintSummaryBOA"){
    $process->PrintSummaryBOA($_POST);
}

if(isset($_GET["BOOKTYPE"]) && $_GET["BOOKTYPE"] == "CRB" && $_GET["REPORTTYPE"] == "DETAILEDPDF"){
    $reports->BOADetailedCRBPDF();
}

if(isset($_GET["BOOKTYPE"]) && $_GET["BOOKTYPE"] == "CRB" && $_GET["REPORTTYPE"] == "DETAILEDEXCEL"){
    $reports->BOADetailedCRBEXCEL();
}

if(isset($_GET["BOOKTYPE"]) && $_GET["BOOKTYPE"] == "GJ" && $_GET["REPORTTYPE"] == "DETAILEDPDF"){
    $reports->BOADetailedGJPDF();
}

if(isset($_GET["BOOKTYPE"]) && $_GET["BOOKTYPE"] == "GJ" && $_GET["REPORTTYPE"] == "DETAILEDEXCEL"){
    $reports->BOADetailedGJEXCEL();
}

if(isset($_GET["BOOKTYPE"]) && $_GET["BOOKTYPE"] == "CDB" && $_GET["REPORTTYPE"] == "DETAILEDPDF"){
    $reports->BOADetailedCDBPDF();
}

if(isset($_GET["BOOKTYPE"]) && $_GET["BOOKTYPE"] == "CDB" && $_GET["REPORTTYPE"] == "DETAILEDEXCEL"){
    $reports->BOADetailedCDBEXCEL();
}