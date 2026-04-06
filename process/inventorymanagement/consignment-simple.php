<?php
    session_start();
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        
        // Include database connection
        include('../../database/connection.php');
        $db = new Database();
        $conn = $db->conn;
        
        // Fetch categories with VAT
        $categoriesWithVAT = [];
        try {
            $sql = "SELECT DISTINCT Category FROM tbl_invlist WHERE UPPER(Type) = 'WITH VAT' ORDER BY Category ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categoriesWithVAT[] = $row['Category'];
                }
            }
        } catch (Exception $e) {
            $categoriesWithVAT = [];
        }

        // Fetch categories without VAT
        $categoriesNonVAT = [];
        try {
            $sql = "SELECT DISTINCT Category FROM tbl_invlist WHERE UPPER(Type) = 'NON-VAT' ORDER BY Category ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categoriesNonVAT[] = $row['Category'];
                }
            }
        } catch (Exception $e) {
            $categoriesNonVAT = [];
        }

        //Fetch all available types from inventory
        $allTypes = [];
        try {
            $sql = "SELECT DISTINCT Type FROM tbl_invlist ORDER BY Type ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $allTypes[] = $row['Type'];
                }
            }
        } catch (Exception $e) {
            $allTypes = [];
        }
        
        // Fetch branches
        $branches = [];
        try {
            $sql = "SELECT DISTINCT ItemName FROM tbl_maintenance WHERE ItemType='BRANCH' ORDER BY ItemName ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $branches[] = $row['ItemName'];
                }
            }
        } catch (Exception $e) {
            $branches = ['HEAD OFFICE', 'ISYN-SANTIAGO'];
        }
        
        // Fetch ISYN branches from inventory
        $isynBranches = [];
        try {
            $sql = "SELECT DISTINCT Branch FROM tbl_invlist ORDER BY Branch ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $isynBranches[] = $row['Branch'];
                }
            }
        } catch (Exception $e) {
            $isynBranches = ['HEAD OFFICE', 'ISYN-SANTIAGO'];
        }
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
            td {
                font-weight: 400;
            }

            form {
                padding: 20px;
                background-color: white;
                border-radius: 10px;
            }

            label,
            thead {
                color: #090909;
            }

            main {
                background-color: #EAEAF6;
                height: 100% ;
            }

            th {
                font-weight: bold;
                color: #090909;
                position: sticky;
                top: 0;
            }
        </style>

            <div class="container-fluid mt-4">
                <div class="customer-profile shadow p-3 rounded-2 mb-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2"><i class="fa-solid fa-file-contract me-2"></i>Consignment (Simplified)</p>
                </div>
                <div class="row mt-2 align-items-start">
                    <div class="col-md-6">
                        <!-- Particulars Section -->
                        <div class="col mt-3">
                            <form class="p-3 needs-validation shadow" id="branchForm" novalidate>
                                <div class="col-md-12 mb-3">
                                    <label for="branch" class="fw-medium fs-5">Branch</label>
                                    <hr style="height: 1px">
                                    <select class="form-select" id="branch" aria-label="Default select example">
                                        <option value="" selected disabled>Select</option>
                                        <?php foreach($branches as $branch): ?>
                                            <option value="<?= htmlspecialchars($branch) ?>"><?= htmlspecialchars($branch) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                            <form class="mt-3 shadow" id="particulars">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="fw-medium fs-5" style="color: #090909;">Particulars</p>
                                </div>
                                <hr style="height: 1px">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="isynBranch" class="form-label">Isyn Branch</label>
                                        <select class="form-select" aria-label="Default select example" required name="isynBranch" id="isynBranch">
                                            <option value="" selected disabled>Select</option>
                                            <?php foreach($isynBranches as $branch): ?>
                                                <option value="<?= htmlspecialchars($branch) ?>"><?= htmlspecialchars($branch) ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <label for="type" class="form-label mt-2">Type</label>
                                        <select class="form-select" id="type" aria-label="Default select example">
                                            <option value="" selected disabled>Select</option>
                                        </select>

                                        <label for="category" class="form-label mt-2">Category</label>
                                        <select class="form-select mb-2" id="category" aria-label="Default select example">
                                            <option value="" selected disabled>Select</option>
                                        </select>
                                        <label for="quantityInput" class="form-label mt-2">Quantity</label>
                                        <input type="number" class="form-control" id="quantityInput" placeholder="" required>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="col-12 mb-1">
                                            <label for="selectBy" class="form-label">Select by:</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="product" checked>
                                            <label class="form-check-label" for="inlineRadio1">Product Name</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="serial">
                                            <label class="form-check-label" for="inlineRadio2">Serial No.</label>
                                        </div>
                                        <div>
                                            <label id="selectLabel" class="form-label mt-3">Select:</label>
                                            <select id="itemSelect" name="select[]" class="form-select" aria-label="Default select example" required>
                                                <option value="" selected disabled>Select</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label id="selectLabel" class="form-label mt-2">SI No</label>
                                            <select id="SInoSelect" name="SIno[]" class="form-control">
                                                <option value="" selected disabled>Select</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Product Summary Section -->
                        <div class="col-md-12 mt-3 mb-3">
                            <form class="p-3 rounded-2 shadow" style="background-color: white;" id="summary">
                                <p class="fw-medium fs-5" style="color: #090909;">Product Summary</p>
                                <hr style="height: 1px">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="supplier_si" class="form-label mt-2">Supplier SI</label>
                                        <input type="text" id="supplier_si" name="supplier_si[]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="serialNo3" class="form-label mt-2">Serial No.:</label>
                                        <input type="text" id="SIno" name="SIno]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="product" class="form-label mt-2">Product:</label>
                                        <input type="text" id="product" name="product[]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="supplier" class="form-label mt-2">Supplier:</label>
                                        <input type="text" id="supplier" name="supplier[]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="srp" class="form-label mt-2">SRP:</label>
                                        <input type="text" id="srp" name="srp[]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="quantity" class="form-label mt-2">Quantity:</label>
                                        <input type="text" id="quantity" name="quantity[]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="dealerPrice" class="form-label mt-2">Dealer's Price:</label>
                                        <input type="text" id="dealers_price" name="dealers_price[]" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="totalPrice" class="form-label mt-2">Total Price:</label>
                                        <input type="text" id="total_price" name="total_price[]" class="form-control" readonly>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="container-fluid mt-4 table-responsive p-3 rounded-2 shadow" style="background-color: white;">
                    <div class="align-items-center justify-content-between mb-4">
                        <button class="btn btn-danger px-3 py-2 mx-1 float-end" type="button" id="cancel-btn" onclick="cancelProduct()" disabled>
                            <i class="fa-regular fa-circle-xmark"></i> Cancel Product
                        </button>
                        <button class="btn btn-success px-3 py-2 mx-1 float-end" type="button" id="submit-btn" onclick="saveDataFromTable()" disabled>
                        <i class="fa-solid fa-check-circle"></i> Submit
                        </button>
                        <button class="btn btn-primary px-3 py-2 mx-1 float-end" type="button" id="add-btn" onclick="addToTable()">
                            <i class="fa-solid fa-plus"></i> Add
                        </button>
                        <p class="fw-medium fs-5" style="color: #090909;">Pricing</p>
                    </div>
                    <hr style="height: 1px">
                    <div class="overflow-auto mb-3" style="max-height: 300px;">
                        <table class="table table-hover table-borderless" id="myTable">
                            <thead>
                                <tr>
                                    <th>Quantity</th>
                                    <th>Product</th>
                                    <th>Dealer Price</th>
                                    <th>Total Price</th>
                                    <th>SRP</th>
                                    <th>Total SRP</th>
                                    <th>Mark Up</th>
                                    <th>Vat Sale</th>
                                    <th>VAT</th>
                                    <th>Amount Due</th>
                                    <th>Stock</th>
                                    <th>Branch</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Supplier SI</th>
                                    <th>SI No.</th>
                                    <th>Supplier</th>
                                    <th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script>
            // Global variables for consignment.js
            var allTypes = <?php echo json_encode($allTypes ?? []); ?>;
            var categoriesWithVAT = <?php echo json_encode($categoriesWithVAT ?? []); ?>;
            var categoriesNonVAT = <?php echo json_encode($categoriesNonVAT ?? []); ?>;
            var ajaxBasePath = '../../pages/inventorymanagement/ajax-inventory/';
        </script>
        <script src="../../js/inventorymanagement/consignment.js?<?= time() ?>"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>
