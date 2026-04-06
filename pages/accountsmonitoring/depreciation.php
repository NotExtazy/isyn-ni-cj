<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$pagesPos  = strpos($scriptDir, '/pages');
$base      = ($pagesPos !== false) ? substr($scriptDir, 0, $pagesPos) : rtrim($scriptDir, '/\\');
if ($base === '.') { $base = ''; }
$BASE_PATH = $base; // Set BASE_PATH for use in script tags
if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true):
?>
<!doctype html>
<html lang="en" dir="ltr">
<?php include(dirname(__DIR__, 2) . '/includes/pages.header.php'); ?>
<style>
    #loading { display: none !important; }
    #filterAssets, #selType { min-width: 160px; }
    #equipmentTbl tbody tr.selected { background-color: #cfe2ff !important; }
    #equipmentTbl tbody tr { cursor: pointer; }
    #equipmentTbl thead th, #equipmentTbl tbody td { padding-top:6px; padding-bottom:6px; font-size:0.82rem; }
    
    /* Table with fixed footer pagination */
    #lists { display: flex; flex-direction: column; height: calc(100vh - 250px); }
    .table-responsive {
        flex: 1;
        overflow-y: auto;
        overflow-x: auto;
        min-height: 300px;
    }
    .dataTables_wrapper { 
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .dataTables_wrapper .table-responsive {
        flex: 1;
        overflow: auto;
    }
    /* Fixed footer for pagination */
    .pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
        margin-top: 0;
    }
    .dataTables_info {
        margin: 0 !important;
    }
    .dataTables_paginate {
        margin: 0 !important;
    }
</style>
<body class="">
<div id="loading"><div class="loader simple-loader"><div class="loader-body"></div></div></div>
<script>window.BASE_PATH = '<?= $BASE_PATH ?>';</script>
<?php
    include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
    include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
?>
<div class="container-fluid" style="padding:2rem;">
    <div class="shadow p-3 rounded-3 mb-3" style="background:white;">
        <p style="color:blue;font-weight:bold;" class="fs-5 my-2"><i class="fa-solid fa-chart-line me-2"></i>Depreciation</p>
    </div>
    <div class="shadow p-3 rounded-3 mb-3" style="background:white;" id="lists">
        <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list me-2"></i>Equipment List</h4>
        <hr class="mt-0 mb-3">
        <input type="hidden" id="filterAssets" value="">
        <input type="hidden" id="selType" value="">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2 align-items-center" style="margin-left:1rem;">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm active" id="btnFurniture" onclick="setAssetFilter('Fur Fix Equip')"><i class="fa-solid fa-chair me-1"></i>Furniture</button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnTranspo" onclick="setAssetFilter('Transpo Equipment')"><i class="fa-solid fa-car me-1"></i>Transpo</button>
                    <button type="button" class="btn btn-outline-warning btn-sm" id="btnLeasehold" onclick="setAssetFilter('Leasehold Imp')"><i class="fa-solid fa-building me-1"></i>Leasehold</button>
                </div>
                <div class="btn-group ms-2" role="group">
                    <button type="button" class="btn btn-outline-success btn-sm active" id="btnActive" onclick="setStatusFilter('Active')">Active</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnDisposed" onclick="setStatusFilter('Disposed')">Disposed</button>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;min-width:360px;">

                <button class="btn btn-success btn-sm" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> New</button>
                <button class="btn btn-primary btn-sm" onclick="openEditModal()"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                <button class="btn btn-danger btn-sm" onclick="DisposeEquipment()"><i class="fa-solid fa-ban"></i> Dispose</button>
                <button class="btn btn-warning btn-sm" onclick="RunMonthlyDep()"><i class="fa-solid fa-rotate"></i> Update</button>
                <button class="btn btn-info btn-sm" onclick="openPrintModal()"><i class="fa-solid fa-print"></i> Print</button>
                <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#generateJV"><i class="fa-solid fa-newspaper"></i> Generate JV</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="equipmentTbl" class="table table-hover table-sm" style="width:100%;">
                <thead></thead>
                <tbody id="equipmentBody"></tbody>
            </table>
        </div>
        <div class="pagination-footer">
            <div id="equipmentTbl_info_container"></div>
            <div id="equipmentTbl_paginate_container"></div>
        </div>
    </div>
</div>

<!-- Type Picker Modal -->
<div class="modal fade" id="typePickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-layer-group me-2"></i>Select Asset Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Choose the type of asset you want to add:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary text-start" onclick="pickType('Fur Fix Equip')"><i class="fa-solid fa-chair me-2"></i> Furniture &amp; Fixture Equipment</button>
                    <button class="btn btn-outline-success text-start" onclick="pickType('Transpo Equipment')"><i class="fa-solid fa-car me-2"></i> Transportation Equipment</button>
                    <button class="btn btn-outline-warning text-start" onclick="pickType('Leasehold Imp')"><i class="fa-solid fa-building me-2"></i> Leasehold Improvement</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Furniture & Fixture (tbl_ppe_furniture) -->
<div class="modal fade" id="modalFurniture" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-chair me-2"></i>Furniture &amp; Fixture Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ff_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="fw-medium mb-2">Basic Information</p><hr class="mt-0">
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Property No.:</label><div class="col-md-7"><input type="text" id="ff_transactionid" class="form-control form-control-sm" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Description:</label><div class="col-md-7"><input type="text" id="ff_description" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Date Acquired:</label><div class="col-md-7"><input type="date" id="ff_dateacquired" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Ref No:</label><div class="col-md-7"><input type="text" id="ff_refno" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">No of Units:</label><div class="col-md-7"><input type="number" id="ff_noofunits" class="form-control form-control-sm" value="1" min="1"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Total Cost/<br>Transferred:</label><div class="col-md-7"><input type="number" id="ff_totalcosttransferred" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Total Cost:</label><div class="col-md-7"><input type="text" id="ff_totalcost" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Disposals/Transfer Out:</label><div class="col-md-7"><input type="number" id="ff_disposaltransferout" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Disposal/Reclass:</label><div class="col-md-7"><input type="number" id="ff_disposalreclass" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-medium mb-2">Depreciation Information</p><hr class="mt-0">
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Est Useful Life (yrs):</label><div class="col-md-7"><input type="number" id="ff_estusefullife" class="form-control form-control-sm" value="5" min="1"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">No of Months:</label><div class="col-md-7"><input type="text" id="ff_noofmonths" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Month Started Depr:</label><div class="col-md-7"><input type="month" id="ff_monthstarteddepr" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Monthly Depr:</label><div class="col-md-7"><input type="text" id="ff_monthlydepr" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Accum Depr (Prev Year):</label><div class="col-md-7"><input type="number" id="ff_accumdeprprevyear" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Depr This Year:</label><div class="col-md-7"><input type="text" id="ff_deprthisyear" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Accum Depr (As of Date):</label><div class="col-md-7"><input type="text" id="ff_accumdeprasofdate" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Net Book Value:</label><div class="col-md-7"><input type="text" id="ff_netbookvalue" class="form-control form-control-sm bg-light" readonly></div></div>
                    </div>
                </div>
                <hr class="mt-2 mb-2">
                <p class="fw-medium mb-2">Lapsing Schedule</p>
                <div class="row g-2">
                    <div class="col-md-1"><label class="form-label form-label-sm">Jan</label><input type="text" id="ff_lapjan" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Feb</label><input type="text" id="ff_lapfeb" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Mar</label><input type="text" id="ff_lapmar" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Apr</label><input type="text" id="ff_lapapr" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">May</label><input type="text" id="ff_lapmay" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Jun</label><input type="text" id="ff_lapjun" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Jul</label><input type="text" id="ff_lapjul" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Aug</label><input type="text" id="ff_lapaug" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Sep</label><input type="text" id="ff_lapsep" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Oct</label><input type="text" id="ff_lapoct" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Nov</label><input type="text" id="ff_lapnov" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Dec</label><input type="text" id="ff_lapdec" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-2"><label class="form-label form-label-sm fw-bold">Total</label><input type="text" id="ff_laptotal" class="form-control form-control-sm bg-light fw-bold" readonly></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="SavePPE('furniture')"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL: Transportation Equipment (tbl_ppe_transpo) -->
<div class="modal fade" id="modalTranspo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-car me-2"></i>Transportation Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tr_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="fw-medium mb-2">Basic Information</p><hr class="mt-0">
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Property No.:</label><div class="col-md-7"><input type="text" id="tr_transactionid" class="form-control form-control-sm" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Description:</label><div class="col-md-7"><input type="text" id="tr_description" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Date Acquired:</label><div class="col-md-7"><input type="date" id="tr_dateacquired" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Ref No:</label><div class="col-md-7"><input type="text" id="tr_refno" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Acquisition Cost:</label><div class="col-md-7"><input type="number" id="tr_acquisitioncost" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">No of Units:</label><div class="col-md-7"><input type="number" id="tr_noofunits" class="form-control form-control-sm" value="1" min="1"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Total Cost:</label><div class="col-md-7"><input type="text" id="tr_totalcost" class="form-control form-control-sm bg-light" readonly></div></div>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-medium mb-2">Depreciation Information</p><hr class="mt-0">
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Est Useful Life (yrs):</label><div class="col-md-7"><input type="number" id="tr_estusefullife" class="form-control form-control-sm" value="5" min="1"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">No of Months:</label><div class="col-md-7"><input type="text" id="tr_noofmonths" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Month Started Depr:</label><div class="col-md-7"><input type="month" id="tr_monthstarteddepr" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Monthly Depr:</label><div class="col-md-7"><input type="text" id="tr_monthlydepr" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Accum Depr (Prev Year):</label><div class="col-md-7"><input type="number" id="tr_accumdeprprevyear" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Depr This Year:</label><div class="col-md-7"><input type="text" id="tr_deprthisyear" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Accum Depr (As of Date):</label><div class="col-md-7"><input type="text" id="tr_accumdeprasofdate" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Net Book Value:</label><div class="col-md-7"><input type="text" id="tr_netbookvalue" class="form-control form-control-sm bg-light" readonly></div></div>
                    </div>
                </div>
                <hr class="mt-2 mb-2">
                <p class="fw-medium mb-2">Lapsing Schedule</p>
                <div class="row g-2">
                    <div class="col-md-1"><label class="form-label form-label-sm">Jan</label><input type="text" id="tr_lapjan" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Feb</label><input type="text" id="tr_lapfeb" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Mar</label><input type="text" id="tr_lapmar" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Apr</label><input type="text" id="tr_lapapr" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">May</label><input type="text" id="tr_lapmay" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Jun</label><input type="text" id="tr_lapjun" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Jul</label><input type="text" id="tr_lapjul" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Aug</label><input type="text" id="tr_lapaug" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Sep</label><input type="text" id="tr_lapsep" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Oct</label><input type="text" id="tr_lapoct" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Nov</label><input type="text" id="tr_lapnov" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Dec</label><input type="text" id="tr_lapdec" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-2"><label class="form-label form-label-sm fw-bold">Total</label><input type="text" id="tr_laptotal" class="form-control form-control-sm bg-light fw-bold" readonly></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="SavePPE('transpo')"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL: Leasehold Improvement (tbl_ppe_leasehold) -->
<div class="modal fade" id="modalLeasehold" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-building me-2"></i>Leasehold Improvement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="lh_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="fw-medium mb-2">Basic Information</p><hr class="mt-0">
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Property No.:</label><div class="col-md-7"><input type="text" id="lh_transactionid" class="form-control form-control-sm" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Description:</label><div class="col-md-7"><input type="text" id="lh_description" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Date Acquired:</label><div class="col-md-7"><input type="date" id="lh_dateacquired" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Ref No:</label><div class="col-md-7"><input type="text" id="lh_refno" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Acquisition Cost:</label><div class="col-md-7"><input type="number" id="lh_acquisitioncost" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">No of Units:</label><div class="col-md-7"><input type="number" id="lh_noofunits" class="form-control form-control-sm" value="1" min="1"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Total Cost:</label><div class="col-md-7"><input type="text" id="lh_totalcost" class="form-control form-control-sm bg-light" readonly></div></div>
                    </div>
                    <div class="col-md-6">
                        <p class="fw-medium mb-2">Depreciation Information</p><hr class="mt-0">
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Est Useful Life (yrs):</label><div class="col-md-7"><input type="number" id="lh_estusefullife" class="form-control form-control-sm" value="5" min="1"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">No of Months:</label><div class="col-md-7"><input type="text" id="lh_noofmonths" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Month Started Depr:</label><div class="col-md-7"><input type="month" id="lh_monthstarteddepr" class="form-control form-control-sm"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Monthly Depr:</label><div class="col-md-7"><input type="text" id="lh_monthlydepr" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Accum Depr (Prev Year):</label><div class="col-md-7"><input type="number" id="lh_accumdeprprevyear" class="form-control form-control-sm" value="0" step="0.01"></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Depr This Year:</label><div class="col-md-7"><input type="text" id="lh_deprthisyear" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Accum Depr (As of Date):</label><div class="col-md-7"><input type="text" id="lh_accumdeprasofdate" class="form-control form-control-sm bg-light" readonly></div></div>
                        <div class="row mb-2"><label class="col-md-5 col-form-label col-form-label-sm">Net Book Value:</label><div class="col-md-7"><input type="text" id="lh_netbookvalue" class="form-control form-control-sm bg-light" readonly></div></div>
                    </div>
                </div>
                <hr class="mt-2 mb-2">
                <p class="fw-medium mb-2">Lapsing Schedule</p>
                <div class="row g-2">
                    <div class="col-md-1"><label class="form-label form-label-sm">Jan</label><input type="text" id="lh_lapjan" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Feb</label><input type="text" id="lh_lapfeb" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Mar</label><input type="text" id="lh_lapmar" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Apr</label><input type="text" id="lh_lapapr" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">May</label><input type="text" id="lh_lapmay" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Jun</label><input type="text" id="lh_lapjun" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Jul</label><input type="text" id="lh_lapjul" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Aug</label><input type="text" id="lh_lapaug" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Sep</label><input type="text" id="lh_lapsep" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Oct</label><input type="text" id="lh_lapoct" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Nov</label><input type="text" id="lh_lapnov" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-1"><label class="form-label form-label-sm">Dec</label><input type="text" id="lh_lapdec" class="form-control form-control-sm bg-light" readonly></div>
                    <div class="col-md-2"><label class="form-label form-label-sm fw-bold">Total</label><input type="text" id="lh_laptotal" class="form-control form-control-sm bg-light fw-bold" readonly></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="SavePPE('leasehold')"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Generate JV Modal -->
<div class="modal fade" id="generateJV" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-newspaper me-2"></i>Generate Journal Voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Fund</label>
                        <select id="jvFund" class="form-select form-select-sm"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">JV Date</label>
                        <input type="date" id="jvDate" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">JV No</label>
                        <input type="text" id="jvNo" class="form-control form-control-sm">
                    </div>
                </div>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr><th><input type="checkbox" id="jvSelectAll"></th><th>Equipment</th><th>Monthly Dep</th></tr>
                    </thead>
                    <tbody id="jvEquipmentList"></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="GenerateJV()"><i class="fa-solid fa-newspaper"></i> Generate</button>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="modalViewDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="viewDetailsTitle">Asset Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" id="viewDetailsBody"></div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- Print Scope Modal (First Modal) - Similar to Type Picker -->
<div class="modal fade" id="modalPrintScope" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-print me-2"></i>Select What to Print/Export</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Choose which equipment to print/export:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary text-start" onclick="selectPrintScope('furniture')">
                        <i class="fa-solid fa-chair me-2"></i> Fur Fix Equip
                    </button>
                    <button class="btn btn-outline-success text-start" onclick="selectPrintScope('transpo')">
                        <i class="fa-solid fa-car me-2"></i> Transpo
                    </button>
                    <button class="btn btn-outline-warning text-start" onclick="selectPrintScope('leasehold')">
                        <i class="fa-solid fa-building me-2"></i> Leasehold
                    </button>
                    <hr class="my-2">
                    <button class="btn btn-outline-info text-start" onclick="selectPrintScope('all')">
                        <i class="fa-solid fa-list me-2"></i> Print All
                    </button>
                </div>
            </div>      <i class="fa-solid fa-list me-2"></i> Print All
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Options Modal (Second Modal) -->
<div class="modal fade" id="modalPrintOptions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:320px;">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fa-solid fa-file-export me-2"></i>Choose Export Format</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body d-grid gap-2 p-3">
                <button class="btn btn-danger" onclick="executePrint('pdf')">
                    <i class="fa-solid fa-file-pdf me-2"></i>PDF
                </button>
                <button class="btn btn-success" onclick="executePrint('excel')">
                    <i class="fa-solid fa-file-excel me-2"></i>Excel
                </button>
            </div>
        </div>
    </div>
</div>

<?php include(dirname(__DIR__, 2) . '/includes/pages.footer.php'); ?>
<script src="https://unpkg.com/exceljs@4.4.0/dist/exceljs.min.js"></script>
<script src="https://unpkg.com/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<script src="<?= $BASE_PATH ?>/js/accountsmonitoring/depreciation.js?v=<?= time() ?>"></script>
</body>
</html>
<?php endif; ?>
