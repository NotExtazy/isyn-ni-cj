<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
<?php 
    $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
    include($headerPath); 
?>
<link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">
<link rel="stylesheet" href="/iSynApp-main/assets/datatables/datatables.min.css">
<body class="  ">
<div id="loading"><div class="loader simple-loader"><div class="loader-body"></div></div></div>
<?php 
    $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
    $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
    include($sidebarPath); 
    include($navbarPath); 
?>
<style>
#tbTable thead th{background-color:#3a7ca5;color:#fff;font-weight:400;padding:12px 15px;}
#tbTable tbody tr:hover{background-color:#f5f5f5;}
#tbTable td{padding:8px 15px;vertical-align:middle;}
</style>
<div class="container-fluid" style="max-width:98%;padding-left:15px;padding-right:15px;">
<div class="p-3 shadow-sm rounded-2 mb-4" style="background:white;border-left:4px solid #3a7ca5;">
<p class="fs-5 mb-0" style="color:#3a7ca5;font-weight:700;"><i class="fa-solid fa-scale-balanced me-2"></i>Trial Balance</p>
</div>

<div class="row g-3">
<!-- Left Side: Filters -->
<div class="col-md-3">
<div class="p-3 shadow-sm rounded-2" style="background-color:white;">
<p class="fs-6" style="color:#3a7ca5;">Filters & Configuration</p>
<hr style="height:1px;background:#e0e0e0;">

<!-- Fund Selection -->
<div class="mb-3">
<label for="fundSelect" class="form-label">Fund / Tag</label>
<select class="form-select form-select-sm" id="fundSelect">
<option value="">All Funds</option>
</select>
</div>

<hr style="height:1px;background:#e0e0e0;">

<!-- Report Type -->
<div class="mb-3">
<label class="form-label">Report Type</label>

<div class="form-check">
<input class="form-check-input" type="radio" name="reportType" id="rptStandard" value="standard">
<label class="form-check-label" for="rptStandard">Standard Trial Balance</label>
</div>

<div class="form-check">
<input class="form-check-input" type="radio" name="reportType" id="rptAdjusted" value="adjusted">
<label class="form-check-label" for="rptAdjusted">Adjusted Trial Balance</label>
</div>

<div class="form-check">
<input class="form-check-input" type="radio" name="reportType" id="rptPostClosing" value="postclosing">
<label class="form-check-label" for="rptPostClosing">Post-Closing Trial Balance</label>
</div>
</div>

<hr style="height:1px;background:#e0e0e0;">

<!-- Action Buttons -->
<div class="d-grid gap-2">
<button class="btn btn-primary" id="btnRetrieve" type="button">
<i class="fa-solid fa-repeat me-1"></i> Retrieve
</button>
<button class="btn btn-success text-white" id="btnPrint" type="button">
<i class="fa-solid fa-print me-1"></i> Print
</button>
<button class="btn btn-warning text-white" id="btnClear" type="button">
<i class="fa-solid fa-arrows-rotate me-1"></i> Clear
</button>
</div>
</div>
</div>

<!-- Right Side: Generated Report -->
<div class="col-md-9">
<div class="p-3 shadow-sm rounded-2" style="background-color:white;max-height:85vh;overflow-y:auto;">
<div id="resultContainer">
<div class="text-center text-muted py-5" id="placeholderMessage">
<i class="fa-solid fa-scale-balanced fa-3x mb-3"></i>
<p class="fs-5">No Report Generated</p>
<p>Select filters and click "Retrieve" to generate a trial balance report</p>
</div>
<div id="tableContainer" style="display:none;">
<div class="table-responsive">
<table class="table table-hover table-sm" id="tbTable" style="width:100%">
<thead id="tbTableHead"></thead>
<tbody id="tbTableBody"></tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<?php 
    $footerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
    include($footerPath); 
?>
<script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
<script src="/iSynApp-main/assets/datatables/datatables.min.js"></script>
<script src="/iSynApp-main/js/generalledger/trialbalance.js?<?= time() ?>"></script>
</body></html>
<?php } else { echo '<script>window.location.href = "../../login.php";</script>'; } ?>