<?php
include_once("../../process/inventorymanagement/searchproducts.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}