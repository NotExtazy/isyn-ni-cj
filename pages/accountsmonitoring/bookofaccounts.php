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
      <title>iSyn | BOA Reports</title>

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
                .selected td {
                    background-color: lightgray;
                } 
            </style>

            <div class="container-fluid mt-1">
                <div class="p-3 rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Books of Accounts</p>
                </div>

                <div class="row">
                    <div class="col-md-4 mt-2">
                        <div class="p-3 shadow rounded-2 mb-2" style="background-color: white;">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> Print Detailed
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <button class="dropdown-item" type="button" onclick="PrintDetailedPDF()">PDF</button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button" onclick="PrintDetailedEXCEL()">EXCEL</button>
                                    </li>
                                </ul>
                            </div>
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" onclick="ExportReport()"> Export
                                </button>
                            </div>
                        </div>

                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Select Parameters</p>
                            <hr style="height: 1px">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="fromDate" class="form-label d-block">From:</label>
                                        <input type="text" class="form-control Date" id="fromDate" name="fromDate">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="toDate" class="form-label d-block">To:</label>
                                        <input type="text" class="form-control Date" id="toDate" name="toDate">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-3">
                                        <label for="bookType" class="form-label d-block">Book Type:</label>
                                        <select class="form-select" id="bookType" name="bookType" aria-label="Select Book Type" onchange="LoadDataRows(this.value)">
                                            <option value="" selected>Select</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group row mb-1">
                                    <label for="fund" class="col-sm-3 col-form-label">Fund</label>
                                    <div class="col-sm-9">
                                        <select id="fund" name="fund" class="form-select">
                                            <option value="">Select Fund</option>
                                        </select>
                                    </div>
                                </div>
                                </div>
                                <div class="col-12 text-end">
                                    <div class="form-group mt-3">
                                        <button class="btn btn-primary" type="button" id="searchBooksBtn" name="searchBooksBtn" onclick="SearchBooksBtn();"> <i class="fa-solid fa-search"></i> Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8 mt-2">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Summary of Accounts</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Transactions</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                                            <p class="fw-medium fs-5" style="color: #090909;">Summary of Accounts</p>
                                            <hr style="height: 2px">
                                            <div class="p-3" style="background-color: white;">
                                                <table id="SummaryTable"  name="SummaryTable" style="width:100%;" class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Account</th>
                                                            <th>Total Debits</th>
                                                            <th>Total Credits</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="SummaryList">

                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
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

                            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0" style="display:block !important;">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                                            <p class="fw-medium fs-5" style="color: #090909;">Transactions</p>
                                            <hr style="height: 2px">
                                            <div class="p-3" style="background-color: white;">
                                                <table id="TransactionsTable1"  name="TransactionTable1" style="width:100%;" class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Reference</th>
                                                            <th>Particulars/Explanation</th>
                                                            <th>Fund</th>
                                                            <th>SubRef</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        
                                                    </tbody>
                                                </table>
                                                <table id="TransactionsTable2"  name="TransactionTable2" style="width:100%;" class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Account</th>
                                                            <th>SL Dr(Cr)</th>
                                                            <th>Debit</th>
                                                            <th>Credit</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        
                                                    </tbody>
                                                </table>
                                                <div class="row mt-2 text-end">
                                                    <div class="col-md-12">
                                                    <button class="btn btn-secondary btn-sm btn-block" id="RePrintBtn" onclick="RePrint()" disabled><i class="fas fa-print"></i>&nbsp;Reprint</button>
                                                    </div>
                                                </div>
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
        <script src="../../js/accountsmonitoring/boa.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>