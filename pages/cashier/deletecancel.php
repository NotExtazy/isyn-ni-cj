<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$pagesPos  = strpos($scriptDir, '/pages');
$base      = ($pagesPos !== false) ? substr($scriptDir, 0, $pagesPos) : rtrim($scriptDir, '/\\');
if ($base === '.' || $base === '') {
    // Fallback: derive from DOCUMENT_ROOT vs current file path
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    $appRoot = dirname(dirname(dirname(__FILE__)));
    $base    = str_replace($docRoot, '', $appRoot);
    $base    = str_replace('\\', '/', $base);
}
$base = rtrim($base, '/');

if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true):
?>
<!doctype html>
<html lang="en" dir="ltr">
<?php include(dirname(__DIR__, 2) . '/includes/pages.header.php'); ?>
<style>#loading { display: none !important; }</style>
<link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
<link rel="stylesheet" href="<?php echo $base; ?>/assets/select2/css/select2.min.css">
<body class="">
<div id="loading"><div class="loader simple-loader"><div class="loader-body"></div></div></div>
<?php
    include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
    include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
?>
<div class="container-fluid" style="padding:2rem;">
    <div class="shadow p-3 rounded-3 mb-3" style="background:white;">
        <p style="color:blue;font-weight:bold;" class="fs-5 my-2">Modify Transactions</p>
    </div>
    <div class="row mb-5">
        <div class="col-md-8">
            <div class="shadow p-3 rounded-3" style="background:white;">
                <div class="row align-items-center mb-2">
                    <div class="col-md-6">
                        <p class="fw-medium fs-5 text-dark mb-0">Today's Transaction</p>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end gap-2">
                        <button type="button" id="deleteTransaction" class="btn btn-danger" onclick="ArchiveTransaction()" disabled>
                            <i class="fa-solid fa-box-archive"></i> Archive Transaction
                        </button>
                        <button type="button" id="cancelTransaction" class="btn btn-warning text-white" onclick="CancelTransaction()" disabled>
                            <i class="fa-regular fa-circle-xmark"></i> Cancel OR
                        </button>
                    </div>
                </div>
                <hr>
                <div class="form-group row mb-3">
                    <label for="orTypes" class="col-sm-auto col-form-label">OR Type</label>
                    <div class="col-sm-4">
                        <select name="orTypes" id="orTypes" class="form-select" onchange="LoadTransactions(this.value)"></select>
                    </div>
                    <div class="col-sm-auto">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="ChangeDate()">
                            <i class="fa-regular fa-calendar"></i> <span id="selectedDateLabel">Set Date</span>
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="transactionTbl" class="table table-hover" style="width:100%;">
                        <thead>
                            <tr><th>OR No.</th><th>Name</th><th>ClientNo</th><th>LoanID</th><th>Nature</th><th>Fund</th><th>CDate</th></tr>
                        </thead>
                        <tbody id="transactionList"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="shadow p-3 rounded-3" style="background:white;">
                <p class="fw-medium fs-5 text-dark">Payment Details</p>
                <hr>
                <?php foreach(['orno'=>'OR No','fund'=>'Fund','po'=>'PO','nature'=>'Nature','principal'=>'Principal','interest'=>'Interest','cbu'=>'CBU','penalty'=>'Penalty','mba'=>'MBA','total'=>'Total'] as $id=>$label): ?>
                <div class="form-group row mb-2">
                    <label for="<?=$id?>" class="col-sm-4 col-form-label"><?=$label?>:</label>
                    <div class="col-sm-7"><input type="text" id="<?=$id?>" class="form-control form-control-sm" disabled></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php include(dirname(__DIR__, 2) . '/includes/pages.footer.php'); ?>
<script>window.BASE_PATH = '<?php echo $base; ?>';</script>
<script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
<script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
<script src="<?php echo $base; ?>/js/cashier/modifytransaction.js?<?= time() ?>"></script>
</body>
</html>
<?php else:
    header('Location: ' . $base . '/login');
    exit;
endif; ?>
