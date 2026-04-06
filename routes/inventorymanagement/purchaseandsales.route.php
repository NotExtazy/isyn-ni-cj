<?php
include_once("../../process/inventorymanagement/purchaseandsales.process.php");
include_once("../../reports/inventorymanagement/purchaseandsales.reports.php");

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'GenerateJournalReport'){
    $process->GenerateJournalReport($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintJournalReport'){
    $report->PrintJournalReport($_SESSION['purchaseSelect'],$_SESSION['option'],$_SESSION['fromAsOf'],$_SESSION['toAsOf'],$_SESSION['month']);
}
