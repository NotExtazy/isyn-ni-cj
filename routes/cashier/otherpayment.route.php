<?php
include_once("../../process/cashier/otherpayment.process.php");
include_once("../../reports/cashier/otherpayment.reports.php");

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'LoadPage'){
    $process->LoadPage();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadClientName'){
    $process->LoadClientName($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadClientNameInfo'){
    $process->LoadClientNameInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetBank'){
    $process->GetBank($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetORNo'){
    $process->GetORNo($_POST,"");
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSLTypes'){
    $process->LoadSLTypes($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSL'){
    $process->LoadSL($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSLFromSubtype'){
    $process->LoadSLFromSubtype($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'Save'){
    $process->SaveOtherPayment($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'ORReport'){
    $report->ORReport($_SESSION["ORDATE"],$_SESSION["ORFUND"],$_SESSION["ORNO"],$_SESSION["ORSERIES"],$_SESSION["ORPYTIN"],$_SESSION["ORPYADD"],$_SESSION["NONTAX"]);
}

if(isset($_GET["type"]) && $_GET["type"] == 'ARReport'){
    $report->ARReport($_SESSION["ORDATE"],$_SESSION["ORFUND"],$_SESSION["ORNO"],$_SESSION["ORSERIES"],$_SESSION["ORPYTIN"],$_SESSION["ORPYADD"]);
}