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
      <title>iSyn | Collection Report</title>

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

                label,
                th {
                    color: #090909;
                }

                th {
                    position: sticky;
                    top: 0;
                }

                main {
                    background-color: #EAEAF6;
                }

                .button-group {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                }
            </style>

            <div class="container-fluid mt-1">
                <div class="p-3 shadow rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="my-2 fs-5">Collection Report</p>
                </div>
                <div class="row mt-4">
                    <div class="col">
                        <form method="POST">
                            <div>
                                <p class="fw-medium fs-5" style="color: #090909;">Set OR</p>
                                <hr style="height: 1px">
                            </div>
                            <div class="row">
                                <div class="col-sm-4">
                                    <div class="form-group row mb-2">
                                        <label for="transactionDate" class="col-sm-4 col-form-label">Transaction Date: </label>
                                        <div class="col-sm-6">
                                            <input type="text" id="transactionDate" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label for="encodedBy" class="col-sm-4 col-form-label">Encoded By: </label>
                                        <div class="col-sm-6">
                                            <select name="encodedBy" id="encodedBy" class="form-select">
        
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="form-group row mb-2">
                                        <label for="from" class="col-sm-3 col-form-label">From: </label>
                                        <div class="col-sm-6">
                                            <input type="text" id="from" class="form-control" onchange="formatInput(this)">
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <label for="to" class="col-sm-3 col-form-label">To: </label>
                                        <div class="col-sm-6">
                                            <input type="text" id="to" class="form-control" onchange="formatInput(this)">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group row mb-2">
                                        <div class="col-sm-6">
                                            <button type="button" class="btn btn-warning" onclick="Reset();"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-2">
                                        <div class="col-sm-6">
                                            <button type="button" class="btn btn-primary" onclick="Search();"><i class="fa-solid fa-search"></i> Search</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div> 

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="shadow p-3 rounded-3 mb-4" style="background-color: white;">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="fw-medium fs-5" style="color: #090909;">Transaction</p>
                                </div>
                                <div class="col-md-6 d-flex align-items-end flex-column">
                                    <div class="buttons text-end">
                                        <button class="btn btn-secondary me-2" id="PrintBtn" onclick="PrintReport();"><i class="fa-solid fa-print"></i> Print</button>
                                    </div>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <div class="table-responsive">
                                <table id="transactionTbl" class="table table-bordered" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>OR No.</th>
                                            <th>Name</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>CBU</th>
                                            <th>Penalty</th>
                                            <th>MBA</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionList">

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
                                        </tr>
                                    </tfoot>
                                </table>
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
        <script src="../../js/cashier/collectionreport.js?<?= time() ?>"></script>


    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>