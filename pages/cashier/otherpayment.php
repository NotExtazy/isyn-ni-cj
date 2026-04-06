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
      <title>iSyn | Other Transaction</title>

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>    

            <style>
                label, thead {
                    color: #090909;
                }
                .form-control {
                    color: #090909 !important;
                    border:1px solid #000000 !important;
                }
                .form-select {
                    color: #090909 !important;
                    border:1px solid #000000 !important;
                }
                /* Selection box */
                .select2-container--default .select2-selection--single {
                    border: 1px solid #000000 !important;
                }

                .select2-container--default .select2-selection--single .select2-selection__rendered {
                    color: #090909 !important;
                }

                /* Dropdown */
                .select2-dropdown {
                    border: 1px solid #000000 !important;
                }

                .select2-results__option {
                    color: #090909 !important;
                }

                /* Optional: Highlighted option */
                .select2-results__option--highlighted {
                    background-color: #e0e0e0 !important;
                    color: #090909 !important;
                }
                form {
                    width: 100%;
                    padding: 20px;
                    background-color: white;
                    border-radius: 10px;
                }
                th {
                    font-weight: bold;
                    color: #090909;
                    position: sticky;
                    top: 0;
                }            
                main {
                    background-color: #eaeaf6;
                }
                .selected td {
                    background-color: lightgray;
                } 
            </style>

            <div class="container-fluid mt-1">
                <div class="shadow rounded-3 p-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2 ">Other Payment</p>
                </div>
                <div class="row">
                    <!-- Column 1: Payment Details -->
                    <div class="col-md-4 col-sm-6 mt-2">
                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Payment Details</p>
                            <hr style="height: 1px;">
                            <form id="otherpayment" method="POST" autocomplete="off">
                                <div class="form-group row mb-1">
                                    <label for="transactionDate" class="col-sm-3 col-form-label">Transaction Date</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="transactionDate" name="transactionDate" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <label for="clientType" class="col-sm-3 col-form-label">Client Type</label>
                                    <div class="col-sm-9">
                                        <select name="clientType" id="clientType" class="form-select" onchange="LoadClientName(this.value)">
                                            <option selected disabled>Select Client Type</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row mb-1">
                                    <label for="payee" class="col-sm-3 col-form-label">Name</label>
                                    <div id="payeeSelDiv" class="col-sm-9">
                                        <select name="payeeSel" id="payeeSel" class="form-select" onchange="LoadClientNameInfo(this.value)">
                                            <option selected disabled>Select Name</option>
                                        </select>
                                    </div>
                                    <div id="payeeTxtDiv" class="col-sm-9" style="display: none;">
                                        <input type="text" name="payeeTxt" id="payeeTxt" class="form-control">
                                    </div>
                                </div>

                                <div id="payeeTINDiv" class="form-group row mb-1" style="display: none;">
                                    <label for="payeeTIN" class="col-sm-3 col-form-label">TIN</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="payeeTIN" id="payeeTIN" class="form-control">
                                    </div>
                                </div>
                                
                                <div id="payeeAddressDiv" class="form-group row mb-1" style="display: none;">
                                    <label for="payeeAddress" class="col-sm-3 col-form-label">Address</label>
                                    <div  class="col-sm-9">
                                        <input type="text" name="payeeAddress" id="payeeAddress" class="form-control">
                                    </div>
                                </div>

                                <div class="form-group row mb-1">
                                    <label for="particulars" class="col-sm-3 col-form-label">Particulars</label>
                                    <div class="col-sm-9">
                                        <textarea name="particulars" id="particulars" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="form-group row mb-1">
                                    <label for="fund" class="col-sm-3 col-form-label">Fund</label>
                                    <div class="col-sm-9">
                                        <select id="fund" name="fund" class="form-select" onchange="GetBank(this.value)">
                                            <option value="" selected disabled>Select Fund</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row mb-1">
                                    <label for="bank" class="col-sm-3 col-form-label">Bank</label>
                                    <div class="col-sm-9">
                                        <select id="bank" name="bank" class="form-select">
                                            <option value="" selected disabled>Select Bank</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row mb-1">
                                    <label for="tag" class="col-sm-3 col-form-label">Tag</label>
                                    <div class="col-sm-9">
                                        <select id="tag" name="tag" class="form-select">
                                            <option value="" selected disabled>Select Tag</option>
                                            <option value="-">-</option>
                                            <option value="Interest">Interest</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row mb-3">
                                    <label for="paymentType" class="col-sm-3 col-form-label">Payment Type</label>
                                    <div class="col-sm-9">
                                        <select id="paymentType" name="paymentType" class="form-select" onchange="toggleCheckDetails(this.value)">
                                            <option value="" selected disabled>Select Payment Type</option>
                                            <option value="CASH">CASH</option>
                                            <option value="CHECK">CHECK</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Check Details -->
                                <!-- <div id="CheckDetailsDiv" style="display: none;"> -->
                                <div id="CheckDetailsDiv">
                                    <p class="fw-medium fs-5" style="color: #090909;">Check Details</p>
                                    <hr style="height: 1px;">

                                    <div class="form-group row mb-1">
                                        <label for="checkDate" class="col-sm-3 col-form-label">Check Date</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="checkDate" name="checkDate" class="form-control" disabled>
                                        </div>
                                    </div>
                
                                    <div class="form-group row mb-1">
                                        <label for="checkNo" class="col-sm-3 col-form-label">Check No.</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="checkNo" name="checkNo" class="form-control" disabled>
                                        </div>
                                    </div>
                
                                    <div class="form-group row mb-1">
                                        <label for="bankName" class="col-sm-3 col-form-label">Bank Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="bankName" name="bankName" class="form-control" disabled>
                                        </div>                                        
                                    </div>
                
                                    <div class="form-group row mb-1">
                                        <label for="bankBranch" class="col-sm-3 col-form-label">Bank Branch</label>
                                        <div class="col-sm-9">
                                            <input type="text" id="bankBranch" name="bankBranch" class="form-control" disabled>
                                        </div>                                        
                                    </div>
                                    
                                    <hr style="height: 1px;">
                                </div>

                                <div class="form-group row mb-1">
                                    <label for="paymentAmount" class="col-sm-3 col-form-label">Payment Amount</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="paymentAmount" name="paymentAmount" class="form-control" onchange="paymentEntryAmount(this)">
                                    </div>                                        
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Column 3: Add Entries -->
                    <div class="col-md-8 mt-2">
                        <div class="shadow p-3 rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Add Entries</p>
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
                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">Entry Side</label>
                                        <div class="col-sm-6">
                                            <select class="form-select text-end" id="EntrySide">
                                                <option value=""></option>
                                                <option value="DEBIT">DEBIT</option>
                                                <option value="CREDIT">CREDIT</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-0">
                                        <!-- <label class="col-sm-6 col-form-label">GL-SL Name.</label> -->
                                        <div class="col-sm-6">
                                            <input type="hidden" class="form-control text-end" id="GlSlname" disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">Account title.</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-end" id="AcctTitle" disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">Account No.</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-end" id="AcctNo" disabled>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">GL Total Amount</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-end" id="GLTotalAmount" onchange="formatInput(this)">
                                        </div>
                                    </div>

                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">SL Total Amount</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-end" id="SLTotalAmount" value="0.00" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <p class="fw-medium fs-6" style="color: #090909;">SL Details</p>
                                    <hr style="height:1px;">
                                    <div class="form-group row mb-1">
                                        <label class="col-sm-6 col-form-label">SL Type</label>
                                        <div class="col-sm-6">
                                        <select class="form-select form-control-sm text-end" id="SLType" onchange="LoadSL(this.value)" disabled>
                                            <option value=""></option>
                                        </select>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-1" id="SubTypeDiv">
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
                    <div class="col-md-12">
                        <div class=" shadow mb-4 p-3 rounded-2 " style="background-color: white;">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="fw-medium fs-5" style="color: #090909;">Current Entries</p>
                                </div>
                                <div class="col-md-6 d-flex align-items-end flex-column">
                                    <div class="buttons text-end">
                                        <button class="btn btn-secondary me-2" id="DeleteEntryBtn" onclick="DeleteEntry();" disabled><i class="fa-solid fa-trash-can"></i> Delete Entry</button>
                                        <button class="text-white btn btn-danger" id="clearEntriesBtn" onclick="ClearEntries();"> <i class="fa-solid fa-rotate-right"></i> Clear Entries</button>
                                        <button class="btn btn-primary" id="AssignORNoBtn" onclick="AssignORNo();"><i class="fa-solid fa-square-plus"></i> Assign OR No.</button>
                                    </div>
                                </div>
                            </div>
                            <hr style="height: 1px">
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

            </div>

            <div class="modal text-left" id="AssignORNoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModalLabel6" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered"> 
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="myModalLabel6">
                                    Finalize Transaction
                                </h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-sm-8">
                                        <div class="form-group row mb-1">
                                            <label for="orseries" class="col-sm-4 col-form-label">Series From </label>
                                            <div class="col-sm-8">
                                                <select name="orseries" id="orseries" class="form-select" onchange="GetORNo(this.value)">
                                                    <option value="" selected disabled></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <label for="seriesNo" class="col-sm-4 col-form-label">Series No </label>
                                            <div class="col-sm-8">
                                                <input type="text" id="seriesNo" name="seriesNo" class="form-control" disabled>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <label for="seriesLeftNo" class="col-sm-4 col-form-label">Series Left </label>
                                            <div class="col-sm-8">
                                                <input type="text" id="seriesLeftNo" name="seriesLeftNo" class="form-control" disabled>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-1">
                                            <div class="col-sm-12">
                                                <input type="text" id="seriesStatus" name="seriesStatus" class="form-control" disabled style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="d-flex justify-content-start mb-4">
                                            <div class="form-check">
                                                <input class="form-check-input me-1" type="checkbox" value="YES" name="nonTax" id="nonTax" disabled>
                                                <label class="form-check-label" for="nonTax">Non-Tax</label>
                                            </div>
                                        </div>
                                        <button class="btn btn-primary text-white" onclick="Save()" id="SaveBtn"><i class="far fa-floppy-disc"></i>&nbsp;Save Transaction</button>
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
        <script src="../../js/cashier/otherpayment.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>