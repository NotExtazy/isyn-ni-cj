<?php
include_once("../../process/inventorymanagement/productmaintenance.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveProduct'){
    $process->SaveProduct($_POST);
}