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
                label {
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
            </style>

            <div class="container-fluid mt-1">
                <div class="shadow rounded-3 p-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Search Products</p>
                </div>

                <div class="row">
                    <div class="col-md-8 mt-2">
                        <div class="shadow p-3 rounded-3 mb-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Product List</p>
                            <hr style="height: 1px">
                            <div class="row">
                                <div class="col-md-12">
                                    <table id="productTbl" style="width:100%;" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="10%">Product</th>
                                                <th width="10%">Category</th>
                                                <th width="10%">Qty</th>
                                                <th width="10%">SRP</th>
                                                <th width="10%">Total SRP</th>
                                                <th width="10%">Dealer Price</th>
                                                <th width="10%">Total Price</th>
                                                <th width="10%">Warranty</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productList">
        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="overflow-auto" style="max-height: 400px;">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 mt-2">
                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                            <div class="align-items-center justify-content-between mb-3">
                                <p class="fw-medium fs-5" style="color: #090909;">Product Details</p>
                                <hr style="height: 1px">
                            </div>
                            <div class="container">
                                <div class="row">
                                    <div class="mb-2"><label for="productName">Product:</label><input type="text" id="productName" name="productName" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="category">Category:</label><input type="text" id="category" name="category" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="warranty">Warranty:</label><input type="text" id="warranty" name="warranty" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="quantity">Quantity:</label><input type="number" id="quantity" name="quantity" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="dealerPrice">Dealer Price:</label><input type="number" id="dealerPrice" name="dealerPrice" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="totalDP">Total DP:</label><input type="number" id="totalDP" name="totalDP" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="totalSRP">Total SRP:</label><input type="number" id="totalSRP" name="totalSRP" class="form-control" readonly></div>
                                    <div class="mb-2"><label for="SRP">SRP:</label><input type="number" id="SRP" name="SRP" class="form-control" readonly></div>
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
        <script src="../../js/inventorymanagement/searchproducts.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>