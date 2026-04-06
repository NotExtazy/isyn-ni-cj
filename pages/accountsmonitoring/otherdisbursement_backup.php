<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
<?php
    // Override BASE_PATH for router compatibility
    $BASE_PATH = '/iSynApp-main';
    $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
    include($headerPath);
?>
<link rel="stylesheet" href="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.css">
<link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">

<body class="  ">
    <!-- loader Start -->
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>
    <!-- loader END -->

    <style>
        label,
        thead {
            color: #090909;
        }

        main {
            background-color: #eaeaf6;
        }

        th {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
        }
        
        .selected td {
            background-color: lightgray;
        }
        
        /* Fix DataTables wrapper overlapping */
        .dataTables_wrapper {
            overflow-x: auto;
        }
        
        /* Ensure proper spacing for tables */
        .table-responsive {
            margin-bottom: 1rem;
        }
        
        /* Fix modal z-index */
        .modal {
            z-index: 1050;
        }
        
        .modal-backdrop {
            z-index: 1040;
        }
        
        /* Prevent content from going under sidebar */
        .content-inner {
            margin-left: 0;
        }
        
        /* Fix select2 dropdown in modals */
        .select2-container {
            z-index: 1060;
        }
        
        /* Improved form styling */
        .form-label {
            font-weight: 500;
            font-size: 0.875rem;
            color: #495057;
        }
        
        .form-control, .form-select {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .form-control:disabled {
            background-color: #e9ecef;
            opacity: 0.7;
        }
        
        /* Button improvements */
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }
        
        .btn-outline-success:hover {
            color: #fff;
            background-color: #198754;
            border-color: #198754;
        }
        
        .btn-outline-danger:hover {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        /* Card improvements */
        .shadow {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        
        /* Select2 styling improvements */
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>

    <?php
        $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
        $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
        include($sidebarPath);
        include($navbarPath);
    ?>

    <div class="container-fluid mt-1">
        <div class=" shadow p-3 rounded-2" style="background-color: white;">
            <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Other Disbursement</p>
        </div>
        <div class="row mt-4">
            <div class="col-md-3 mt-2">
                <div class="  p-3 rounded-2 shadow" style="background-color: white;">
                    <p class="fw-medium fs-5" style="color: #090909;">General</p>
                    <hr style="height: 1px">
                    <div class="mt-4">
                        <label for="Date" class="form-label">Check Date</label>
                        <input type="text" class="form-control Input Data1" id="Date" data-id="1" data-inputmask-alias="datetime">
                    </div>
                    
                    <label class="form-label mt-2" for="Payee">Payee</label>
                    <input type="text" class="form-control" id="Payee" autocomplete="off">
                    
                    <label for="Particulars" class="form-label mt-2">Particulars</label>
                    <input type="text" class="form-control" id="Particulars" autocomplete="off">

                    <label for="DisbType" class="form-label mt-2">Disbursement Type</label>
                    <select name="DisbType" id="DisbType" class="form-select" onchange="GetBanks(this.value);">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div class="col-md-9 mt-2">
                <div class="shadow p-3 rounded-3  " style="background-color: white;">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="fw-medium fs-5" style="color: #090909;">Add Entries</p>
                        </div>
                        <div class="col-md-6 d-flex align-items-end flex-column">
                            <button type="button" id="ViewSaved" class="btn btn-warning align-self-end" onclick="ViewSaved();" disabled><i class="fas fa-edit"></i>&nbsp;View Saved <i id="SavedCounter"></i></button>
                        </div>
                    </div>
                    <hr style="height: 1px">
                    <div class="row mt-3">
                                <div class="col-md-6">
                                    <p class="fw-medium fs-6" style="color: #090909;">GL Details</p>
                                    <hr style="height: 1px">
                                    <div class="form-group row mb-0">
                                        <label class="col-sm-6 col-form-label">Entry Side</label>
                                        <div class="col-sm-6">
                                            <select class="form-select form-control-sm text-end" id="EntrySide">
                                                <option value=""></option>
                                                <option value="DEBIT">DEBIT</option>
                                                <option value="CREDIT">CREDIT</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-0">
                                        <!-- <label class="col-sm-6 col-form-label">GL-SL Name.</label> -->
                                        <div class="col-sm-6">
                                            <input type="hidden" class="form-control form-control-sm text-end" id="GlSlname" disabled>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-0">
                                        <label class="col-sm-6 col-form-label">Account title.</label>
                                        <div class="col-sm-6">
                                            <select class="form-select form-control-sm text-end" id="AcctTitle">
                                                <option value=""></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-0">
                                        <label class="col-sm-6 col-form-label">Account No.</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm text-end" id="AcctNo" disabled>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-0">
                                        <label class="col-sm-6 col-form-label">GL Total Amount</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm text-end" id="GLTotalAmount" onchange="formatInput(this)" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-0">
                                        <label class="col-sm-6 col-form-label">SL Total Amount</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm text-end" id="SLTotalAmount" value="0.00" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <p class="fw-medium fs-6" style="color: #090909;">SL Details</p>
                                    <hr style="height:1px;">
                                    <div class="form-group row mb-0">
                                        <label class="col-sm-6 col-form-label">SL Type</label>
                                        <div class="col-sm-6">
                                        <select class="form-select form-control-sm text-end" id="SLType" onchange="LoadSL(this.value)" disabled>
                                            <option value=""></option>
                                        </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-0" id="SubTypeDiv">
                                        <label class="col-sm-6 col-form-label">SubType</label>
                                        <div class="col-sm-6">
                                            <select class="form-select form-control-sm text-end" id="SubType" onchange="LoadSLFromSubtype(this.value)">
                                                <option value="CURRENT">CURRENT</option>
                                                <option value="OLD">OLD</option>
                                                <option value="WRITEOFF">WRITEOFF</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">SL Name</label>
                                        <div class="col-sm-6">
                                            <select class="form-select form-control-sm text-end" id="SLName" disabled>
                                                <option value=""></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">SL Amount</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control form-control-sm text-end" id="SLAmount" value="0.00" onchange="formatInput(this)" autocomplete="off" disabled>
                                        </div>
                                    </div>
                                    <div class="form-group row mt-1 mb-1">
                                        <label class="col-sm-6 col-form-label"></label>
                                        <div class="col-sm-6">
                                            <button class="btn btn-outline-success btn-block col-sm-12" id="AddSLButton" onclick="AddSLEntry()" disabled><i class="fas fa-plus-circle"></i></button>
                                        </div>
                                    </div>
                                    <div class="form-group row mt-0">
                                        <label class="col-sm-6 col-form-label"></label>
                                        <div class="col-sm-6">
                                            <button class="btn btn-outline-danger btn-block col-sm-12" id="RemoveSLButton" onclick="RemoveSLEntry()" disabled><i class="fas fa-minus-circle"></i></button>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table id="SLTable" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <td>Subcode</td>
                                                <td>Subname</td>
                                                <td>Amount</td>
                                                <td>SL Type</td>
                                                <td>Loan ID</td>
                                                <td>Program</td>
                                                <td>Product</td>
                                                <td>GLNo</td>
                                            </tr>
                                        </thead>
                                        <tbody id="SLTblList">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-1">
                            <div class="col-md-12">
                                <div class="buttons text-end">
                                    <button class="btn btn-primary" id="AddGLEntry" onclick="AddGLEntry()"><i class="fas fa-save"></i>&nbsp;Add Entry</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-12 mt-4">
                    <div class="col-md-12">
                        <div class=" shadow mb-4 p-3 rounded-2 " style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Current Entries</p>
                            <hr style="height: 1px">
                            <div class="buttons text-end">
                                <button class="btn btn-secondary me-2" id="DeleteEntryBtn" onclick="DeleteEntry();" disabled><i class="fa-solid fa-trash-can"></i> Delete Entry</button>
                                <button class="text-white btn btn-danger" id="clearEntriesBtn" onclick="ClearEntries();"> <i class="fa-solid fa-rotate-right"></i> Clear Entries</button>
                                <button class="btn btn-primary" id="AssignCVNoBtn" onclick="AssignCVNo();"><i class="fa-solid fa-square-plus"></i> Assign CV No.</button>
                            </div>
                            <div class="overflow-auto" style="height: 300px; max-height: 400px">
                                <table id="EntryTable" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="30%">Account Titles</th>
                                            <th width="15%">SL Amount</th>
                                            <th width="15%">Debit</th>
                                            <th width="15%">Credit</th>
                                            <th>Slcode</th>
                                            <th>entryside</th>
                                            <th>HaveSL</th>
                                            <th>SlType</th>
                                            <th>loanid</th>
                                            <th>program</th>
                                            <th>product</th>
                                            <th>glno</th>
                                            <th>type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="EntryTblList">

                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2"></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="ViewSavedMdl" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="staticBackdropLabel">Saved Transactions</h1>
                                <button type="button" class="btn-close"  data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <table id="SavedTransactTbl" width="100%" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <td width="30%">Batch No.</td>
                                                    <td width="30%">Payee</td>
                                                    <td width="40%">Particular</td>
                                                </tr>
                                            </thead>
                                            <tbody id="SavedTransactList">

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="row w-100">
                                    <div class="col text-start">
                                        <button type="button" id="DeleteSavedBtn" class="btn btn-danger col-3" onclick="DeleteSaved();" disabled><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </div>
                                    <div class="col text-end">
                                        <button type="button" id="LoadSavedBtn" class="btn btn-success col-3" onclick="LoadSaved()" disabled><i class="fa-solid fa-file-arrow-down"></i> Load</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal text-left" id="AssignCVNoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModalLabel6" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered"> 
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="myModalLabel6">
                                    Funding Details
                                </h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">Trans Type</label>
                                            <select name="TransType" id="TransType" class="form-select" disabled>
                                                <option value="-" selected>-</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary text-white col-md-4 pe-1 btn-block my-1" onclick="Save()" id="SaveBtn"><i class="far fa-check-circle"></i>&nbsp;Save Details</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">Bank</label>
                                            <select name="Bank" id="Bank" class="form-select" onchange="LoadBankDetails(this.value)">
                                                <option value="" selected></option>
                                            </select>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary text-white col-md-4 pe-1 btn-block my-1" onclick="Edit()" id="EditBtn" disabled><i class="fas fa-edit"></i>&nbsp;Edit Voucher</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">Disb. Amount</label>
                                            <input type="text" id="DisbAmount" class="form-control" value="0.00" onchange="formatInput(this)" readonly>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary text-white col-md-4 pe-1 btn-block my-1" onclick="PrintVouchernCheck()" id="PrintVoucherBtn" disabled><i class="fas fa-print"></i>&nbsp;Print Voucher & Check</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">Fund</label>
                                            <select name="Fund" id="Fund" class="form-select" disabled>
                                                <option value="" selected></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pe-1"></div>
                                    <!-- <button class="btn btn-primary text-white col-md-4 pe-1 btn-block my-1" onclick="PrintCheck()" id="PrintCheckBtn" disabled><i class="fas fa-print"></i>&nbsp;Print Check</button> -->
                                </div>
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">CV No</label>
                                            <input type="text" name="CVNo" id="CVNo" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pe-1"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">Check No</label>
                                            <input type="text" name="CheckNo" id="CheckNo" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4 pe-1"></div>
                                </div>
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

    <script src="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
    <script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
    <script src="/iSynApp-main/js/accountsmonitoring/otherdisb.js?<?= time() ?>"></script>
</body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "/iSynApp-main/login"; </script>';
  }
?>