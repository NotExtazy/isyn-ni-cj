<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent any output before JSON
ob_start();

$processPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/process/accountsmonitoring/accountsandaging.process.php';
$reportsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/reports/accountsmonitoring/accounstandaging.reports.php';

include_once($processPath);

// Only include reports if file exists (needed for PDF generation)
if (file_exists($reportsPath)) {
    include_once($reportsPath);
}

$process = new Process();

// GET: PDF output
if (isset($_GET['type'])) {
    ob_end_clean(); // Clear buffer for PDF output
    
    // Check if Reports class exists
    if (!class_exists('Reports')) {
        echo "Reports class not found. Please check the reports file.";
        exit;
    }
    
    $report   = new Reports();
    $clientno = $_SESSION['AA_CLIENTNO'] ?? '';
    $loanid   = $_SESSION['AA_LOANID']   ?? '';
    if (!$clientno || !$loanid) { echo "No session data."; exit; }

    switch ($_GET['type']) {
        case 'SOAReport': $report->SOAReport($clientno, $loanid); break;
        case 'SLReport':  $report->SLReport($clientno, $loanid);  break;
        default: echo "Unknown report type.";
    }
    exit;
}

// POST: actions
if (isset($_POST['action'])) {
    // Clear any unwanted output
    ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'LoadList':      $process->LoadList($_POST);        break;
        case 'LoadSLList':    $process->LoadSLList($_POST);      break;
        case 'LoadSLPreview': $process->LoadSLPreview($_POST);   break;
        case 'UpdateAging':   $process->UpdateAging();           break;
        case 'UpdateAccount': $process->UpdateAccount($_POST);   break;
        case 'SetSession':
            $_SESSION['AA_CLIENTNO'] = $_POST['clientno'] ?? '';
            $_SESSION['AA_LOANID']   = $_POST['loanid']   ?? '';
            echo json_encode(['STATUS' => 'OK']);
            break;
    }
    
    ob_end_flush();
}
?>