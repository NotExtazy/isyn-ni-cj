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
      <title>iSyn | Teller's Proofsheet</title>

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
                label,
                thead {
                    color: #090909;
                }

                main {
                    background-color: #EAEAF6;
                }

                th {
                    font-weight: bold;
                    color: #090909 !important;
                }

                @media print {
                    #print {
                        height: 100%;
                        width: 50%;
                        overflow: visible !important;
                        transform: scale(.5);
                        position: fixed;
                    }

                    @page {
                        size: auto;
                        margin: 0;
                        position: fixed;
                    }
                }

                .show-important {
                    display: block !important;
                }

                .dc-highlight {
                    background-color: #e7490ad2 !important;
                    color: #ffffffff !important;
                }

                .Prev-Undeposited {
                    background-color: #532a09d2 !important;
                    color: #ffffffff !important;
                }
            </style>

            <div class="container-fluid mt-1">
                <div class="row mt-1 mb-auto">
                    <div class="col-md-12">
                        <div class=" p-3 shadow rounded-2" style="background-color: white;">
                            <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Teller's Proofsheet</p>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-4 mt-2">
                                <form id="billscoinsForm" method="POST">
                                    <div class="shadow p-3 rounded-3" style="background-color: white;">
                                        <div class="col-md-12">
                                            <div class="fw-medium fs-5">Cash Count</div>
                                        </div>
                                        <hr style="height: 1px">
                                        <div class="col-md-12 mt-1">
                                            <div class="col-md-3"><p class="fw-bold fs-6">Bills</p></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-2">
                                                <label class="form-label mt-2">1000</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2 ">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input1000" name="input1000" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class="col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control mt-1" id="total1000" name="total1000" placeholder="0" readonly>
                                            </div>
                                        </div>
        
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">500</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input500" name="input500" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total500" name="total500" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">200</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input200" name="input200" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total200" name="total200" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">100</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input100" name="input100" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total100" name="total100" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">50</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input50" name="input50" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total50" name="total50" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">20</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input20" name="input20" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total20" name="total20" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="col-3"><p class="fw-bold fs-6">Coins</p></div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">20</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input20_coin" name="input20_coin" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total20_coin" name="total20_coin" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">10</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input10" name="input10" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total10" name="total10" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">5</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input5" name="input5" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total5" name="total5" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">1</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input1" name="input1" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total1" name="total1" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-2">
                                                <label class="form-label mt-2">0.50</label>
                                            </div>
                                            <div class="col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control" id="input0_50" name="input0_50" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class="col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control mt-1" id="total0_50" name="total0_50" placeholder="0" readonly>
                                            </div>
                                        </div>
        
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">0.25</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input0_25" name="input0_25" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total0_25" name="total0_25" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">0.05</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input0_05" name="input0_05" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total0_05" name="total0_05" placeholder="0" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class=" col-2">
                                                <label class="form-label mt-2">0.01</label>
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">X</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control" id="input0_01" name="input0_01" placeholder="0" oninput="computeTotal()">
                                            </div>
                                            <div class=" col-1">
                                                <label class="form-label mt-2">=</label>
                                            </div>
                                            <div class=" col-4">
                                                <input type="text" class="form-control mt-1" id="total0_01" name="total0_01" placeholder="0" readonly>
                                            </div>
                                        </div>
                                            
                                        
                                        <div class="col-md-12 mt-4">
                                            <form>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label class="form-label mt-2">Total Bills:</label>
                                                    </div>
                                                    <div class="col-8">
                                                        <input type="text" id="totalBills" name="totalBills" class="form-control mt-1" readonly>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-4">
                                                        <label class="form-label mt-2">Total Checks:</label><br>
                                                        <small>See Schedule B</small>
                                                    </div>
                                                    <div class="col-8">
                                                        <input type="text" id="totalChecks" name="totalChecks"  class="form-control mt-3" readonly>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label class="form-label mt-2">Grand Total:</label>
                                                    </div>
                                                    <div class="col-8 mb-2">
                                                        <input type="text" id="grandTotal" name="grandTotal" class="form-control mt-1" readonly>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label class="form-label mt-2">System Total:</label>
                                                    </div>
                                                    <div class="col-8">
                                                        <input type="text" id="systemTotal" name="systemTotal" class="form-control" readonly>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-8 mt-2">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                                            <div class="fw-medium fs-5">Debit/Credit</div>
                                            <hr style="height:1px">
                                            <div class="table-responsive">
                                                <table id="debitcreditTbl" class="table table-bordered table-sm" style="width: 100%">
                                                    <thead>
                                                        <tr>
                                                            <th width="65%"></th>
                                                            <th width="35%">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="debitcreditList">
                                                    <tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 mt-2">
                                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                                            <div class="fw-medium fs-5">Collections and Transactions</div>
                                            <hr style="height:1px">
                                            <ul class="nav nav-tabs" id="myTab" role="tablist">
            
                                                <button class="nav-link active" id="schedA-tab" data-bs-toggle="tab" data-bs-target="#schedA-tab-pane" type="button" role="tab" aria-controls="schedA-tab-pane" aria-selected="true" onclick="LoadScheduleA();">Sched A-Collection</button>
                                                <button class="nav-link" id="schedB-tab" data-bs-toggle="tab" data-bs-target="#schedB-tab-pane" type="button" role="tab" aria-controls="schedB-tab-pane" aria-selected="false" onclick="LoadScheduleB();">Sched B-Undeposited Checks</button>
                                                <button class="nav-link" id="schedC-tab" data-bs-toggle="tab" data-bs-target="#schedC-tab-pane" type="button" role="tab" aria-controls="schedC-tab-pane" aria-selected="false" onclick="LoadScheduleC();">Sched C-Undeposited Previous</button>
            
                                            </ul>
                                            <div class="tab-content" id="myTabContent">
                                                <div class="tab-pane fade show active" id="schedA-tab-pane" role="tabpanel" aria-labelledby="schedA-tab" tabindex="0">
                                                    <div class="row mb-2">
                                                        <div class="col-md">
                                                            <div class="p-3 shadow rounded-2 mb-3" style="background-color: white;">
                                                                <div class="table-responsive">
                                                                    <table id="scheduleATbl" class="table table-bordered table-sm" style="width: 100%">
                                                                        <thead>
                                                                            <tr>
                                                                                <th width="20%">Fund</th>
                                                                                <th width="10%">Total Collections</th>
                                                                                <th width="10%">Undep(Previous)</th>
                                                                                <th width="10%">Deposit</th>
                                                                                <th width="10%">Undep(DayEnd)</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="scheduleAList">
                
                                                                        </tbody>
                                                                        <tfoot>
                                                                            <tr>
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
                                                <div class="tab-pane fade" id="schedB-tab-pane" role="tabpanel" aria-labelledby="schedB-tab" tabindex="0">
                                                    <div class="col-md ">
                                                        <div class="p-3 shadow rounded-2 mb-3" style="background-color: white;">
                                                            <div class="table-responsive">
                                                                <table id="scheduleBTbl" class="table table-bordered table-sm" style="width: 100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th width="20%">Check No.</th>
                                                                            <th width="20%">Bank Name</th>
                                                                            <th width="20%">Bank Branch</th>
                                                                            <th width="20%">Amount</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="scheduleBList">
                
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
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
                                                <div class="tab-pane fade" id="schedC-tab-pane" role="tabpanel" aria-labelledby="schedC-tab" tabindex="0">
                                                    <div class="col-md">
                                                        <div class="p-3 shadow rounded-2 mb-3" style="background-color: white;">
                                                            <div class="table-responsive">
                                                                <table id="scheduleCTbl" class="table table-bordered table-sm" style="width: 100%">
                                                                    <thead>
                                                                        <tr>
                                                                            <th width="20%">Date</th>
                                                                            <th width="20%">Particulars</th>
                                                                            <th width="20%">Status</th>
                                                                            <th width="20%">Amount</th>
                                                                            <th width="20%">Deposited</th>
                                                                            <th width="20%">Undeposited</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="scheduleCList">
                    
                                                                    </tbody>
                                                                    <tfoot>
                                                                        <tr>
                                                                            <td></td>
                                                                            <td></td>
                                                                            <td class="Prev-Undeposited"></td>
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
                                        </div>
                                    </div>
                                    <div class="col-sm-12 mt-2">
                                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="fw-medium fs-5">Today's Undeposited Amounts</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="float-end mx-2">
                                                        <div class="col-md-12 d-flex justify-content-end">
                                                            <button type="button" class="btn btn-success btn-sm px-3 py-2 mx-1" onclick="Confirm();">
                                                                <i class="fa-solid fa-check"></i> Confirm
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr style="height:1px">
                                            <div class="table-responsive">
                                                <table id="todayUndepTbl" class="table table-bordered table-sm" style="width: 100%">
                                                    <thead>
                                                        <tr>
                                                            <th>Fund</th>
                                                            <th>Bank</th>
                                                            <th>Type</th>
                                                            <th>Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="todayUndepList">

                                                    <tbody>
                                                    <tfoot>
                                                        <tr>
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
                        </div>
                    </div>
                </div>
            </div>

            
        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/cashier/tellersproofsheet.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>

<script>
    function computeTotal() {
        calculateSingleInput('1000');
        calculateSingleInput('500');
        calculateSingleInput('200');
        calculateSingleInput('100');
        calculateSingleInput('50');
        calculateSingleInput('20');
        calculateSingleInput('20_coin');
        calculateSingleInput('10');
        calculateSingleInput('5');
        calculateSingleInput('1');
        calculateSingleInput('0_50');
        calculateSingleInput('0_25');
        calculateSingleInput('0_05');
        calculateSingleInput('0_01');

        // Calculate and display the total bills
        var totalBills = 0;
        totalBills += getNumericValue("total1000");
        totalBills += getNumericValue("total500");
        totalBills += getNumericValue("total200");
        totalBills += getNumericValue("total100");
        totalBills += getNumericValue("total50");
        totalBills += getNumericValue("total20");
        totalBills += getNumericValue("total20_coin");
        totalBills += getNumericValue("total10");
        totalBills += getNumericValue("total5");
        totalBills += getNumericValue("total1");
        totalBills += getNumericValue("total0_50");
        totalBills += getNumericValue("total0_25");
        totalBills += getNumericValue("total0_05");
        totalBills += getNumericValue("total0_01");

        document.getElementById("totalBills").value = formatNumber(totalBills);
        var totalChecks = $("#totalChecks").val().replace(/,/g, '');

        var grandTotal = 0;
        grandTotal += totalBills;
        grandTotal += parseFloat(totalChecks);

        document.getElementById("grandTotal").value = formatNumber(grandTotal);
    }

    function calculateSingleInput(inputId) {
        var inputElement = document.getElementById("input" + inputId.replace('.', '_'));

        // Add input event listener for numeric validation
        inputElement.addEventListener('input', function() {
            validateNumericInput(inputElement);
        });

        // Validate initial input value
        validateNumericInput(inputElement);

        var inputValue = parseFloat(inputElement.value);

        var denomination = parseFloat(inputId.replace('_', '.'));
        var result = denomination * (isNaN(inputValue) ? 0 : inputValue);

        document.getElementById("total" + inputId.replace('.', '_')).value = formatNumber(result);
    }

    function getNumericValue(elementId) {
        var rawValue = document.getElementById(elementId).value;

        // ✅ Remove commas before parsing
        var cleanValue = rawValue.replace(/,/g, '');

        var value = parseFloat(cleanValue) || 0;
        return value;
    }

    function validateNumericInput(inputElement) {
        inputElement.value = inputElement.value.replace(/[^0-9.]/g, '');
    }

    function formatNumber(num) {
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
</script>

