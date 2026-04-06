<?php
include_once("../../process/cashier/depositslip.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadFund'){
    $process->LoadFund($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadUndepPrev'){
    $process->LoadUndepPrev($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetBanks'){
    $process->GetBanks($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadDepositDetails'){
    $process->LoadDepositDetails($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SetDSNO'){
    $process->SetDSNO($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveDepositSlip'){
    $process->SaveDepositSlip($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'DSUndepPrevious'){
    $process->DSUndepPrevious($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'DSUndepPreviousALL'){
    $process->DSUndepPreviousALL($_POST);
}