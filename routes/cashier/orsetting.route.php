<?php
include_once("../../process/cashier/orsetting.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadORs'){
    $process->LoadORs();
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetORData'){
    $process->GetORData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveSeries'){
    $process->SaveSeries($_POST);
}
