<?php
include_once("../../process/cashier/collectionreport.process.php");
include_once("../../reports/cashier/collectionreport.reports.php");

$process = new Process();
$report = new Reports();


if(isset($_POST['action']) AND $_POST['action'] == 'LoadPOs'){
    $process->LoadPOs();
}

if(isset($_POST['action']) AND $_POST['action'] == 'SearchTransactions'){
    $process->SearchTransactions($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ToSession'){
    $process->ToSession($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'CollectionReport'){
    $report->CollectionReport($_SESSION["CRDATA"],$_SESSION["CRDATE"],$_SESSION["CRENCODEDBY"],$_SESSION["CRFROM"],$_SESSION["CRTO"]);
}

