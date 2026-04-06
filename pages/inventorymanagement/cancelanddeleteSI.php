<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="../../assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="../../assets/select2/css/select2.min.css">

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
                th {
                    color: #090909;
                }

                form {
                    width: 100%;
                    padding: 20px;
                    background-color: white;
                    border-radius: 10px;
                }

                /* main {
                    background-color: #EAEAF6;
                    height: 100%;
                } */

                th {
                    font-weight: bold;
                    color: #090909;
                    position: sticky;
                    top: 0;
                }

                td,
                th {
                    color: #090909;
                    word-wrap: break-word;
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    white-space: normal;
                }

                .table {
                    border-spacing: 0px;
                    table-layout: auto;
                    table-layout: fixed;
                    width: 100%;
                    margin-left: auto;
                    margin-right: auto;
                }

                .table th,
                .table td {
                    word-wrap: break-word;
                    overflow-wrap: break-word;
                    white-space: normal;
                }

                .table td {
                    padding: 8px;
                }

                .table tbody tr.selected {
                    background-color: #d3d3d3;
                    color: #000;

                }

                .selected td {
                    background-color: lightgray;
                } 

            </style>


            <div class="container-fluid mt-1">
                <div class=" shadow p-3 rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Cancel and Delete SI</p>
                </div>
                <div class="row">
                    <!-- filter -->
                    <div class="col-md-12 mt-1">
                        <div class=" shadow p-3 rounded-3  " style="background-color: white;">
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="fw-medium fs-5" style="color: #090909;">Product Filter</p>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <div class="mb-2 row">
                                <label class="col-sm-auto col-form-label" for="transactionDate">Transaction Date:</label>
                                <div class="col-sm-2">
                                    <input type="text" id="transactionDate" name="transactionDate" class="form-control" onchange="ClearAll();">
                                </div>
                                <div class="col-sm-2">
                                    <button type="button" id="searchBtn" name="searchBtn" class="btn btn-primary" onclick="LoadTransactionsOnDate();"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--today's transaction-->
                    <div class="col-md-8 mt-1">
                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                            <div>
                                <p class="fw-medium fs-5" style="color: #090909;">Today's Transactions</p>
                                <hr style="height: 1px">
                            </div>
                            <div class="">
                                <table id="transactionTbl" style="width:100%;" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%">SI No</th>
                                            <th style="width: 57%">Product Name</th>
                                            <th style="width: 20%">Sold To</th>
                                            <th style="width: 13%">Date Sold</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionList">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!--transaction details-->
                    <div class="col-md-4 mt-1">
                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Transaction Details</p>
                            <hr style="height: 1px">

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Date Sold:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="dateSold" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Sold To:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="soldTo" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Supplier SI:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="supplierSI" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Serial No.:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="SINo" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Product:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="product" readonly>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Supplier:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="supplier" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Quantity:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="quantity" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Dealer's Price:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="dealersPrice" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Total Dealer's Price:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="totalPrice" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        SRP:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="srp" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Total SRP:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="totalsrp" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Markup:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="markup" readonly>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-4">
                                    <label class="text-end mt-3 mb-2">
                                        Total Markup:
                                    </label>
                                </div>
                                <div class="col-md-7">
                                    <input type="text" class="form-control" id="totalMarkup" readonly>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6 d-flex justify-content-start">
                                    <button class="btn btn-danger px-3 py-2 mx-1" id="deleteBtn" onclick="DELETE()" disabled><i class="fa-solid fa-trash-can"></i> Delete</button>
                                </div>
                                <div class="col-md-6 d-flex justify-content-end">
                                    <button class="btn btn-warning px-3 py-2 mx-1" id="cancelBtn" onclick="CANCEL()" disabled><i class="fa-solid fa-circle-xmark"></i> Cancel</button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="cancelReasonMDL" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="cancelReason" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel"> Cancel Reason</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-2 row">
                                        <label for="cancelReason" class="form-label col-md-3">Reason</label>
                                        <div class="col-md-9">
                                            <input type="text" id="cancelReason" name="cancelReason" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-primary px-3 py-2 mx-1" type="button" id="proceedCancel" name="proceedCancel" onclick="ProceedCancel();"> Proceed Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/inventorymanagement/cancelanddeleteSI.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>