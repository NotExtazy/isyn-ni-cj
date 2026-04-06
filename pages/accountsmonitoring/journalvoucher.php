<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once('../../includes/permissions.php');
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: ../../dashboard");
            exit;
        }
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="../../assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="../../assets/select2/css/select2.min.css">
      <title>iSyn | Journal Voucher</title>

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
            }
            .selected td {
                background-color: lightgray;
            } 
        </style>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>
        

            <div class="container-fluid mt-1">
                <div class=" shadow p-3 rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Journal Vouchers</p>
                </div>

                <div class="row mt-2">
                    <div class="col-md-3 mt-2">
                        <div class="shadow p-3 rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">General</p>
                            <hr style="height: 1px">
                            <div class="mt-4">
                                <label for="Date" class="form-label">JV Date</label>
                                <input type="text" class="form-control Input Data1" id="Date" data-id="1" data-inputmask-alias="datetime">
                            </div>
                            <label class="form-label mt-2" for="nature">Nature of Adjustment</label>
                            <select class="form-select" id="NatureAdjustment" onchange="SelectNature(this.value);" aria-label="Nature of Adjustment selection">
                                <option value="">Select</option>
                            </select>
                            <label for="VoucherExplanation" class="form-label mt-2">Particulars</label>
                            <textarea name="VoucherExplanation" id="VoucherExplanation" class="form-control" rows="4" oninput="this.value = this.value.toUpperCase()"></textarea>
                        </div>
                    </div>

                    <div class="col-md-9 mt-2">
                        <div class="shadow p-3 rounded-2" style="background-color: white;">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="fw-medium fs-5" style="color: #090909;">Add Entries</p>
                                </div>
                                <div class="col-md-6 d-flex align-items-end flex-column">
                                    <button type="button" id="ViewSaved" class="btn btn-warning align-self-end" onclick="ViewSaved();" disabled><i class="fas fa-edit"></i>&nbsp;View Saved <i id="SavedCounter"></i></button>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <table class="table table-bordered" style="width:100%" id="AccountCodesTable">
                                <thead>
                                    <tr>
                                        <th scope="col" width="10%">Acct. Codes</th>
                                        <th scope="col" width="10%">Acct. Titles</th>
                                        <th scope="col">Normal Value</th>
                                        <th scope="col">FS Type</th>
                                        <th scope="col">SL</th>
                                        <th scope="col">SL Name</th>
                                    </tr>
                                </thead>
                                <tbody id="AccountCodesTbody" style="overflow-y: auto;">
                                    
                                </tbody>
                            </table>
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
                                            <input type="text" class="form-control form-control-sm text-end" id="AcctTitle" disabled>
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
                                            <input type="text" class="form-control form-control-sm text-end" id="GLTotalAmount" onchange="formatInput(this)">
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
                                            <input type="text" class="form-control form-control-sm text-end" id="SLAmount" value="0.00" onchange="formatInput(this)" disabled>
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
                                    <div class="form-group row">
                                        <label class="col-sm-6 col-form-label"></label>
                                        <div class="col-sm-12">
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

                <div class="row mt-2">
                    
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class=" shadow mb-4 p-3 rounded-2 " style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Current Entries</p>
                            <hr style="height: 1px">
                            <div class="buttons text-end">
                                <button class="btn btn-secondary me-2" id="DeleteEntryBtn" onclick="DeleteEntry();" disabled><i class="fa-solid fa-trash-can"></i> Delete Entry</button>
                                <button class="text-white btn btn-danger" id="clearEntriesBtn" onclick="ClearEntries();"> <i class="fa-solid fa-rotate-right"></i> Clear Entries</button>
                                <button class="btn btn-primary" id="AssignJVNoBtn" onclick="AssignJVNo();"><i class="fa-solid fa-square-plus"></i> Assign JV No.</button>
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
                                                    <td width="70%">Particulars</td>
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

                <div class="modal text-left" id="AssignJVNoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModalLabel6" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered"> 
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="myModalLabel6">
                                    Assign Journal Voucher No.
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
                                            <label class="input-group-text">Fund</label>
                                            <select name="Fund" id="Fund" class="form-select" onchange="GetJVNo(this.value)">
                                                <option value="" selected></option>
                                            </select>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary text-white col-md-4 pe-1 btn-block my-1" onclick="Edit()" id="EditBtn" disabled><i class="fas fa-edit"></i>&nbsp;Edit Voucher</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-8 pe-1">
                                        <div class="input-group my-1">
                                            <label class="input-group-text">JV No</label>
                                            <input type="text" name="JVNo" id="JVNo" class="form-control" disabled>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary text-white col-md-4 pe-1 btn-block my-1" onclick="Print()" id="PrintBtn" disabled><i class="fas fa-print"></i>&nbsp;Print Voucher</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>
        
        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/accountsmonitoring/journalvoucher.js?<?= time() ?>"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>