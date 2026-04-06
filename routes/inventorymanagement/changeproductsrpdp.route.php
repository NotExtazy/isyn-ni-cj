<?php
include_once("../../process/inventorymanagement/changeproductsrpdp.process.php");
// include_once("../../reports/inventorymanagement/cancelanddeleteSI.reports.php");

$process = new Process();
// $report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCategory'){
    $process->LoadCategory($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SearchInventory'){
    $process->SearchInventory($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateProduct'){
    $process->UpdateProduct($_POST);
}