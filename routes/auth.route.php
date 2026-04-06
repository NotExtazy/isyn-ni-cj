<?php
$basePath = dirname(__DIR__);
include_once($basePath . "/process/auth.process.php");
$process = new Process();

if (isset($_POST["action"]) && $_POST["action"] == "Login") {
    $process->Login($_POST);
}

if(isset($_POST["action"]) && $_POST["action"] == "logout"){
    $process->Logout($_POST);
}