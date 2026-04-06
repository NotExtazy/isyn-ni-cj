<?php
include_once("../../process/cashier/dssetting.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadFund'){
    $process->LoadFund();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBank'){
    $process->LoadBank($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveSeries'){
    $process->SaveSeries($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveDSSetting'){
    $process->SaveDSSetting($_POST);
}
