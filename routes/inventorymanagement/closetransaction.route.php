<?php
$basePath = dirname(__DIR__, 2);
include_once($basePath . "/process/inventorymanagement/closetransaction.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'CloseTransaction'){
    $process->CloseTransaction($_POST);
}