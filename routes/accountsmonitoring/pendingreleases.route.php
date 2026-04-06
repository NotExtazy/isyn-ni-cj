<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$docRoot = $_SERVER['DOCUMENT_ROOT'];
include_once($docRoot . "/iSynApp-main/process/accountsmonitoring/pendingreleases.process.php");
include_once($docRoot . "/iSynApp-main/reports/accountsmonitoring/pendingreleases.reports.php");

$process = new Process();

// ── GET: PDF report output ───────────────────────────────────────────────────
if (isset($_GET['type'])) {
    $report = new Reports();
    $clientno = $_SESSION['PR_CLIENTNO'] ?? '';
    $loanid   = $_SESSION['PR_LOANID']   ?? '';
    if (!$clientno || !$loanid) { echo "No session data."; exit; }

    switch ($_GET['type']) {
        case 'VoucherReport': $report->VoucherReport($clientno, $loanid); break;
        case 'CheckReport':   $report->CheckReport($clientno, $loanid);   break;
        case 'LRSReport':     $report->LRSReport($clientno, $loanid);     break;
        default: echo "Unknown report type.";
    }
    exit;
}

// ── POST: actions ────────────────────────────────────────────────────────────
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'LoadPage':           $process->LoadPage();                        break;
        case 'LoadList':           $process->LoadList();                        break;
        case 'LoadClientDetails':  $process->LoadClientDetails($_POST);         break;
        case 'GetBanks':           $process->GetBanks($_POST);                  break;
        case 'GetFundTags':        $process->GetFundTags($_POST);               break;
        case 'GetVoucherEntries':  $process->GetVoucherEntries($_POST);         break;
        case 'SaveRelease':        $process->SaveRelease($_POST);               break;
        case 'SaveFundingDetails': $process->SaveFundingDetails($_POST);        break;
        case 'SetSession':
            $_SESSION['PR_CLIENTNO'] = $_POST['clientno'] ?? '';
            $_SESSION['PR_LOANID']   = $_POST['loanid']   ?? '';
            echo json_encode(['STATUS' => 'OK']);
            break;
    }
}