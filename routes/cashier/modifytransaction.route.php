<?php
include_once(__DIR__ . "/../../process/cashier/modifytransaction.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadORTypes'){
    $process->LoadORTypes($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadTransactions'){
    $process->LoadTransactions($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetORData'){
    $process->GetORData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'CancelTransaction'){
    $process->CancelTransaction($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ArchiveTransaction'){
    $process->ArchiveTransaction($_POST);
}