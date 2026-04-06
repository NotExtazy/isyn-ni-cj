<?php
    if (session_status() === PHP_SESSION_NONE) {
        if (session_status() == PHP_SESSION_NONE) {            session_start();        }
    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        $rootPath = dirname(__DIR__, 2);
        require_once($rootPath . '/includes/permissions.php');
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: " . (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?: '') . "/dashboard");
            exit;
        }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include($rootPath . '/includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/select2/css/select2.min.css">

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include($rootPath . '/includes/pages.sidebar.php');
            include($rootPath . '/includes/pages.navbar.php');
        ?>

            <div class="container-fluid mt-1 ">
                <div class="shadow p-3 rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Inventory</p>
                </div>
                <div class="row">
                    <!-- Product Filter Column -->
                    <div class="col-lg-4 col-md-4 mt-2">
                        <div class=" shadow p-3 rounded-3" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Product Filter</p>
                            <hr style="height: 1px">
                            <form id="productFilterForm" method="POST">
                                <div class="mb-3 row">
                                    <label class="col-sm-12" for="isynBranch">Isyn Branch:</label>
                                    <div class="col-sm-9">
                                        <select class="form-select" name="isynBranch" id="isynBranch">
                                            <option value="" selected disabled>Select</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- <div class="mb-3 row">
                                    <div class="col-sm-12">
                                        <label for="isConsignment">
                                            <input type="checkbox" id="isConsignment" name="isConsignment" class="form-check-input" value="Yes"> Consignment Branch:
                                        </label>
                                    </div>
                                    <div class="col-sm-9">
                                        <select class="form-select" name="cosignBranch" id="cosignBranch" disabled>
                                            <option value="" selected>Select</option>
                                        </select>
                                    </div>                                    
                                </div> -->

                                <div class="mb-3 row">
                                    <div class="col-sm-12">
                                        <label for="isPreset">
                                            <input type="radio" id="isPreset" name="selection" class="form-check-input" value="Yes" checked onclick="isPresetSelect();"> Preset:
                                        </label>
                                    </div>
                                    <div class="col-sm-9 mb-1">
                                        <select class="form-select" name="presetSelect" id="presetSelect" onchange="PresetSelectVal(this.value);">
                                            <option value="" selected>Select</option>
                                            <option value="CURRENT INVENTORY">CURRENT INVENTORY</option>
                                            <option value="ENDING INVENTORY">ENDING INVENTORY</option>
                                            <option value="INCOMING INVENTORY">INCOMING INVENTORY</option>
                                            <option value="OUTGOING INVENTORY">OUTGOING INVENTORY</option>
                                            <option value="PREVIOUS INVENTORY">PREVIOUS INVENTORY</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="WithoutConsignment">
                                            <input type="checkbox" id="WithoutConsignment" name="WithoutConsignment" class="form-check-input" value="Yes" onclick="WithoutConsign();"> Without Consignment
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="onlyConsignment">
                                            <input type="checkbox" id="onlyConsignment" name="onlyConsignment" class="form-check-input" value="Yes" onclick="ConsignOnly();"> Consignment Only
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="noFreebies">
                                            <input type="checkbox" id="noFreebies" name="noFreebies" class="form-check-input" value="Yes"> Exclude Freebies
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="onlyFreebies">
                                            <input type="checkbox" id="onlyFreebies" name="onlyFreebies" class="form-check-input" value="Yes"> Freebies Only
                                        </label>
                                    </div>
                                    <div class="col-sm-6" id="TransProdDiv" style="display: none;">
                                        <label for="incTransProd">
                                            <input type="checkbox" id="incTransProd" name="incTransProd" class="form-check-input" value="Yes"> Include Transfer Product
                                        </label>
                                    </div>
                                    <div class="col-sm-6" id="DiscProdDiv" style="display: none;">
                                        <label for="discProd">
                                            <input type="checkbox" id="discProd" name="discProd" class="form-check-input" value="Yes"> Discounted Products
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-2 row">
                                    <div class="col-sm-12 mb-1">
                                        <label for="isCustom">
                                            <input type="radio" id="isCustom" name="selection" class="form-check-input" value="Yes" onclick="isCustomSelect();"> Custom:
                                        </label>
                                    </div>
                                    <div class="col-sm-9 mb-1">
                                        <select class="form-select" name="customSelect" id="customSelect" disabled onchange="PresetCustomVal(this.value);">
                                            <option value="" selected>Select</option>
                                            <option value="CURRENT INVENTORY">CURRENT INVENTORY</option>
                                            <option value="OUTGOING INVENTORY">OUTGOING INVENTORY</option>
                                            <option value="PREVIOUS INVENTORY">PREVIOUS INVENTORY</option>
                                        </select>
                                    </div>

                                </div>
                                
                                <div class="mb-2 row">
                                    <label class="col-sm-2 col-form-label" for="customColumns">Column:</label>
                                    <div class="col-sm-7">
                                        <select class="form-select" name="customColumns" id="customColumns" onchange="LoadCustomColumnValue(this.value);" disabled>
                                            <option value="" selected>Select</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-2 row">
                                    <label class="col-sm-2 col-form-label" for="customValues">Value:</label>
                                    <div class="col-sm-7">
                                        <select class="form-select" name="customValues" id="customValues" disabled>
                                            <option value="" selected>Select</option>
                                        </select>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>

                    <!-- Date Filter Column -->
                    <div class="col-lg-4 col-md-4 mt-2">
                        <div class=" shadow p-3 rounded-3" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Date Filter</p>
                            <hr style="height: 1px">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>From:</label>
                                    <input type="text" id="fromDate" name="fromDate" class="form-control Date">
                                </div>
                                <div class="col-md-6">
                                    <label>To:</label>
                                    <input type="text" id="toDate" name="toDate" class="form-control Date">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button type="button" id="searchBtn" name="searchBtn" class="btn btn-primary w-100" onclick="SearchBtn();"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Summary Column -->
                    <div class="col-lg-4 col-md-4 mt-2">
                        <div class=" shadow p-3 rounded-3" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Product Summary</p>
                            <hr style="height: 1px">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Total Quantity:</label>
                                    <input type="text" id="totalQuantity" name="totalQuantity" class="form-control" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>Dealer's Price:</label>
                                    <input type="text" id="dealersPrice" name="dealersPrice" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Total DP:</label>
                                    <input type="text" id="totalDP" name="totalDP" class="form-control" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>SRP:</label>
                                    <input type="text" id="srp" name="srp" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Total SRP:</label>
                                    <input type="text" id="totalsrp" name="totalsrp" class="form-control" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label>VAT Sales:</label>
                                    <input type="text" id="vatSales" name="vatSales" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label>VAT:</label>
                                    <input type="text" id="vat" name="vat" class="form-control" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Particulars Table -->
                    <div class="col-lg-12 mt-2">
                        <div class=" shadow p-3 rounded-3" style="background-color: white;overflow:auto">
                            <div class="row">
                                <div class="col-lg-6 col-md-6">
                                    <p class="fw-medium fs-5" style="color: #090909;">Particulars</p>
                                </div>
                                <div class="col-lg-6 col-md-6 d-flex justify-content-end">
                                    <button type="button" id="print" name="print" class="btn btn-primary" onclick="PrintInventoryReport();"><i class="fa fa-print"></i> Print</button>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <div class="">
                                <table id="particularsTbl" style="width:100%;" class="table table-bordered">
                                    <thead>
                                        <tr>
                                        </tr>
                                    </thead>
                                    <tbody id="particularsList">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include($rootPath . '/includes/pages.footer.php');
        ?>

        <script src="<?php echo $BASE_PATH; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $BASE_PATH; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $BASE_PATH; ?>/js/inventorymanagement/inventory.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "' . (rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') ?: '') . '/login.php"; </script>';
  }
?>


<script>
    // calendar function
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('.form-control[type="date"]');

        dateInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                let value = input.value;
                let parts = value.split('-');
                if (parts.length === 3) {
                    let year = parts[0].substring(0, 4); // Limiting to 4 digits
                    let month = parts[1];
                    let day = parts[2];
                    input.value = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                }
            });

            input.addEventListener('blur', function() {
                let value = input.value;
                let parts = value.split('-');
                if (parts.length === 3) {
                    let year = parts[0].substring(0, 4); // Limiting to 4 digits
                    let month = parts[1].padStart(2, '0');
                    let day = parts[2].padStart(2, '0');
                    input.value = `${year}-${month}-${day}`;
                }
            });
        });
    });
</script>