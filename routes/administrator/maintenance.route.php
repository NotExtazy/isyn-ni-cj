<?php
include_once("../../process/administrator/maintenance.process.php");
include_once("../../reports/administrator/maintenance.reports.php");

$process = new Process();
$reports = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'ListSubmodules'){
    $process->ListSubmodules($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ListItems'){
    $process->ListItems($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ListModules'){
    $process->ListModules();
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetHierarchy'){
    $reports->GetHierarchy();
}

if(isset($_POST['action']) AND $_POST['action'] == 'DeleteChoice'){
    $process->DeleteChoice($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadHierarchy'){
    // Alias for GetHierarchy if needed, or if frontend uses LoadHierarchy
    // Previous code used LoadHierarchy in JS but reports has GetHierarchy?
    // Let's check JS later. For now, keep existing.
    $reports->GetHierarchy();
}

if(isset($_POST['action']) AND $_POST['action'] == 'CreateModule'){
    $process->CreateModule($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'CreateSubmodule'){
    $process->CreateSubmodule($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'CreateItem'){
    $process->CreateItem($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateName'){
    $process->UpdateName($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'DeleteNode'){
    $process->DeleteNode($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadChoices'){
    $process->LoadChoices($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveChoice'){
    $process->SaveChoice($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateChoice'){
    $process->UpdateChoice($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'ArchiveChoice'){
    $process->ArchiveChoice($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetFieldConfig'){
    $process->GetFieldConfig($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveFieldConfig'){
    $process->SaveFieldConfig($_POST);
}
?>