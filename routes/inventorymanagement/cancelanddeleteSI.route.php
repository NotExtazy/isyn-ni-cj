<?php
include_once("../../process/inventorymanagement/cancelanddeleteSI.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadTransactionsOnDate'){
    $process->LoadTransactionsOnDate($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadTransactionDetails'){
    $process->LoadTransactionDetails($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'CancelSI'){
    $process->CancelSI($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'DeleteSI'){
    $process->DeleteSI($_POST);
}