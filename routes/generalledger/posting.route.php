<?php
$processPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/process/generalledger/posting.process.php';
include_once($processPath);

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'PostGL'){
    $process->PostGL($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UndoPostGL'){
    $process->UndoPostGL($_POST);
}