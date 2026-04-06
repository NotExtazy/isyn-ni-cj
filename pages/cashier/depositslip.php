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
      <title>iSyn | Deposit Slip</title>

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
                td {
                    font-weight: 400;
                }

                form {
                    width: 100%;
                    padding: 20px;
                    background-color: white;
                    border-radius: 10px;
                }

                label {
                    color: #090909;
                }

                main {
                    background-color: #EAEAF6;
                    height: 100% ;
                }
            </style>

            <div class="container-fluid mt-1">
                <div class=" p-3 shadow rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="my-2 fs-5">Deposit Slip</p>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class=" shadow p-3 rounded-3  " style="background-color: white;">
                            <p class=" fs-5" style="color: #090909;">Today's Transactions</p>
                            <hr style="height:1px">
                            <form id="DepositTodayForm">
                                <div class="form-group row mb-1">
                                    <div class="col-sm-12">
                                        <input type="text" class="form-control" name="lastdsno" id="lastdsno" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <label for="fund" class="col-sm-3 col-form-label">Fund</label>
                                    <div class="col-sm-9">
                                        <select name="fund" id="fund" class="form-select" onchange="GetBanks(this.value)">
                                            <option selected disabled>Select Fund</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <label for="bank" class="col-sm-3 col-form-label">Bank</label>
                                    <div class="col-sm-9">
                                        <select name="bank" id="bank" class="form-select" disabled>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <label for="type" class="col-sm-3 col-form-label">Type</label>
                                    <div class="col-sm-9">
                                        <select name="type" id="type" class="form-select" onchange="LoadDepositDetails(this.value)">
                                            <option selected disabled>Select Type</option>
                                            <option value="CASH">CASH</option>
                                            <option value="CHECK">CHECK</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <label for="depositSlipNo" class="col-sm-3 col-form-label">Deposit Slip No.</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="depositSlipNo" id="depositSlipNo" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="amount" class="col-sm-3 col-form-label">Amount</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="amount" id="amount" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-1">
                                    <div class="col-sm-3"></div>
                                    <div class="col-sm-9">
                                        <div class="d-grid gap-2 col-12 mx-auto">
                                            <button type="button" id="depositTodayBtn" name="depositTodatBtn" class="btn btn-block btn-primary" onclick="DepositToday()">Deposit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class=" shadow mb-3 p-3 rounded-2 " style="background-color: white;">
                            <p class="fs-5" style="color: #090909;">Undeposited ORs (for this DS) </p>
                            <hr style="height:1px">
                            <table id="undeptodayTbl" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>OR No.</th>
                                        <th>Name</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="undeptodayList">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mt-2">
                    <div class="shadow p-3 rounded-2" style="background-color: white">
                        <p class="fs-5" style="color: #090909;">Previous Undeposited Transaction(s)</p>
                        <hr style="height: 1px">
                        <div class="buttons">
                            <button type="button" id="depositPrev" name="depositPrev" class="btn btn-info" onclick="DepositPrev()" disabled><i class="fa-solid fa-money-bill"></i> Deposit</button>
                            <button type="button" id="depositPrevAll" name="depositPrevAll" class="btn btn-success" onclick="DepositPrevAll()" disabled><i class="fa-solid fa-money-bills"></i> Deposit All</button>
                        </div>
                        <table id="undepprevTbl" class="table table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th width="20%">Date</th>
                                    <th width="20%">Particulars</th>
                                    <th width="20%">Type</th>
                                    <th width="20%">Amount</th>
                                    <th width="20%">Status</th>
                                    <th width="20%">Fund</th>
                                    <th width="20%">Bank</th>
                                    <th width="20%">Last DSNo</th>
                                </tr>
                            </thead>
                            <tbody id="undepprevList">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/cashier/depositslip.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>