<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        
        // Include database connection
        include(dirname(__DIR__, 2) . '/database/connection.php');
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
        } catch (Exception $e) { $categoriesWithVAT = []; }

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
        } catch (Exception $e) { $categoriesNonVAT = []; }

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
        } catch (Exception $e) { $allTypes = []; }
        
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
        } catch (Exception $e) { $branches = ['HEAD OFFICE', 'ISYN-SANTIAGO']; }
        
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
        } catch (Exception $e) { $isynBranches = ['HEAD OFFICE', 'ISYN-SANTIAGO']; }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php 
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $pagesPos = strpos($scriptDir, '/pages');
        if ($pagesPos !== false) {
            $base = substr($scriptDir, 0, $pagesPos);
        } else {
            $base = rtrim($scriptDir, '/\\');
        }
        if ($base === '.' ) { $base = ''; }
        include(dirname(__DIR__, 2) . '/includes/pages.header.php'); 
    ?>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/select2/css/select2.min.css">
    <!-- Add Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <body class="">
        <div id="loading">
            <div class="loader simple-loader">
                <div class="loader-body"></div>
            </div>
        </div>

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
            include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
        ?>

        <style>
            :root {
                --primary-color: #435ebe; /* Royal Blue */
                --secondary-color: #6c757d;
                --success-color: #198754;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
                --info-color: #0dcaf0;
                --background-color: #f2f7ff; /* Very Light Blue-Gray */
                --card-bg: #ffffff;
                --text-main: #25396f;
                --text-secondary: #7c8db5;
                --border-color: #eef2f6;
                --input-bg: #f8f9fa;
            }

            body {
                background-color: var(--background-color);
                font-family: 'Inter', sans-serif;
                color: var(--text-main);
            }

            /* Layout & Spacing */
            .main-container {
                padding: 2rem;
            }

            /* Cards */
            .card-modern {
                background: var(--card-bg);
                border: none;
                border-radius: 16px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
                margin-bottom: 1.5rem;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            
            .card-header-modern {
                background: transparent;
                border-bottom: 1px solid var(--border-color);
                padding: 1.25rem 1.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .card-title-modern {
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--text-main);
                margin: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .card-body-modern {
                padding: 1.5rem;
            }

            /* Form Elements */
            .form-label {
                font-weight: 600;
                font-size: 0.85rem;
                color: var(--text-secondary);
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .form-control, .form-select {
                background-color: var(--input-bg);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 0.6rem 1rem;
                font-size: 0.95rem;
                font-weight: 500;
                color: var(--text-main);
                transition: all 0.2s ease;
            }
            
            .form-control:focus, .form-select:focus {
                background-color: #fff;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            }

            /* Select2 Overrides */
            .select2-container--default .select2-selection--single {
                background-color: var(--input-bg) !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                height: 42px !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 40px !important;
                padding-left: 1rem !important;
                color: var(--text-main) !important;
                font-weight: 500;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px !important;
                right: 10px !important;
            }
            
            .select2-dropdown {
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
                overflow: hidden;
            }

            /* Buttons */
            .btn {
                border-radius: 8px;
                padding: 0.5rem 1rem;
                font-weight: 600;
                font-size: 0.9rem;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
                border: none;
            }
            
            .btn:active {
                transform: scale(0.98);
            }

            .btn-primary {
                background-color: var(--primary-color);
                box-shadow: 0 4px 12px rgba(67, 94, 190, 0.3);
            }
            
            .btn-success {
                background-color: var(--success-color);
                box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
            }

            .btn-danger {
                background-color: var(--danger-color);
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            }

            /* Tables */
            .table-responsive-custom {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid var(--border-color);
            }

            .table-custom {
                width: 100%;
                margin-bottom: 0;
                border-collapse: separate;
                border-spacing: 0;
            }

            .table-custom thead th {
                background-color: #f8f9fa;
                color: var(--text-secondary);
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.75rem;
                padding: 1rem;
                border-bottom: 2px solid var(--border-color);
                letter-spacing: 0.5px;
                white-space: nowrap;
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .table-custom tbody td {
                padding: 1rem;
                vertical-align: middle;
                border-bottom: 1px solid var(--border-color);
                color: var(--text-main);
                font-size: 0.9rem;
                background-color: #fff;
            }

            .table-custom tbody tr:hover td {
                background-color: #f1f5f9;
                cursor: pointer;
            }

            /* Header Section */
            .page-header-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
            }
            
            .page-heading {
                font-size: 1.75rem;
                font-weight: 800;
                color: var(--text-main);
                letter-spacing: -0.5px;
                margin: 0;
            }
            
            .breadcrumb-modern {
                color: var(--text-secondary);
                font-size: 0.9rem;
                margin: 0;
            }
            
            /* Scrollbar */
            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            
            ::-webkit-scrollbar-track {
                background: transparent; 
            }
            
            ::-webkit-scrollbar-thumb {
                background: #cbd5e1; 
                border-radius: 3px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: #94a3b8; 
            }
        </style>

        <div class="container-fluid main-container">
            <!-- Page Header -->
            <div class="card-modern">
                <div class="card-body-modern py-3">
                    <div class="page-header-container m-0">
                        <div>
                            <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Consignment</h1>
                            <p class="breadcrumb-modern m-0">Manage branch consignments and transfers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Destination Branch -->
            <div class="card-modern">
                <div class="card-body-modern">
                    <form id="branchForm" novalidate>
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label for="branch" class="form-label mb-0" style="font-size: 1rem;">Destination Branch</label>
                            </div>
                            <div class="col-md-9">
                                <select class="form-select" id="branch" aria-label="Default select example">
                                    <option value="" selected disabled>Select Branch</option>
                                    <?php foreach ($branches as $b) echo "<option value='".htmlspecialchars($b)."'>".htmlspecialchars($b)."</option>"; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Left Column: Particulars -->
                <div class="col-lg-5">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-clipboard-list text-primary"></i> Particulars</h5>
                        </div>
                        <div class="card-body-modern">
                            <form id="particulars">
                                <div class="mb-3">
                                    <label for="isynBranch" class="form-label">Source Branch (ISYN)</label>
                                    <select class="form-select" required name="isynBranch" id="isynBranch">
                                        <option value="" selected disabled>Select Source</option>
                                        <?php foreach ($isynBranches as $b) echo "<option value='".htmlspecialchars($b)."'>".htmlspecialchars($b)."</option>"; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label d-block">Select Item By</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="product" checked>
                                            <label class="form-check-label" for="inlineRadio1">Product Name</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="serial">
                                            <label class="form-check-label" for="inlineRadio2">Serial No.</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="type" class="form-label">Type</label>
                                        <select class="form-select" id="type">
                                            <option value="" selected disabled>Select</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category">
                                            <option value="" selected disabled>Select</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Item Selection</label>
                                    <select id="itemSelect" name="select[]" class="form-select" required>
                                        <option value="" selected disabled>Select</option>
                                    </select>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SI No</label>
                                        <select id="SInoSelect" name="SIno[]" class="form-control">
                                            <option value="" selected disabled>Select</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="quantityInput" class="form-label text-primary">Consign Qty</label>
                                        <input type="number" class="form-control border-primary" id="quantityInput" required placeholder="0">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Product Summary -->
                <div class="col-lg-7">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-list-check text-primary"></i> Product Summary</h5>
                        </div>
                        <div class="card-body-modern">
                            <form id="summary">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="product" class="form-label">Product Name</label>
                                        <input type="text" id="product" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="supplier_si" class="form-label">Supplier SI</label>
                                        <input type="text" id="supplier_si" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="SIno" class="form-label">Serial No.</label>
                                        <input type="text" id="SIno" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="supplier" class="form-label">Supplier</label>
                                        <input type="text" id="supplier" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="quantity" class="form-label">Available Stock</label>
                                        <input type="text" id="quantity" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="srp" class="form-label">SRP</label>
                                        <input type="text" id="srp" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="dealers_price" class="form-label">Dealer Price</label>
                                        <input type="text" id="dealers_price" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="total_price" class="form-label">Total Cost</label>
                                        <input type="text" id="total_price" class="form-control" readonly>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Table -->
            <div class="card-modern mt-4">
                <div class="card-header-modern">
                    <h5 class="card-title-modern"><i class="fa-solid fa-tags text-primary"></i> Pricing & Consignment List</h5>
                    <div class="d-flex gap-2">
                         <button class="btn btn-primary btn-sm" type="button" id="add-btn" onclick="addToTable()">
                            <i class="fa-solid fa-plus"></i> Add to List
                        </button>
                        <button class="btn btn-danger btn-sm" type="button" id="cancel-btn" onclick="cancelProduct()" disabled>
                            <i class="fa-regular fa-trash-can"></i> Clear List
                        </button>
                        <button class="btn btn-success btn-sm" type="button" id="submit-btn" onclick="saveDataFromTable()" disabled>
                            <i class="fa-solid fa-check-circle"></i> Submit
                        </button>
                    </div>
                </div>
                <div class="card-body-modern p-0">
                    <div class="table-responsive-custom" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-custom table-hover" id="myTable">
                            <thead>
                                <tr>
                                    <th>Quantity</th><th>Product</th><th>Dealer Price</th><th>Total Price</th><th>SRP</th><th>Total SRP</th><th>Mark Up</th><th>Vat Sale</th><th>VAT</th><th>Amount Due</th><th>Stock</th><th>Branch</th><th>Type</th><th>Category</th><th>Supplier SI</th><th>SI No.</th><th>Supplier</th><th>Date Added</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php include(dirname(__DIR__, 2) . '/includes/pages.footer.php'); ?>
        <script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $base; ?>/assets/sweetalert2/sweetalert2.min.js"></script>
        
        <script>
            // Global variables for consignment.js
            var allTypes = <?php echo json_encode($allTypes ?? []); ?>;
            var categoriesWithVAT = <?php echo json_encode($categoriesWithVAT ?? []); ?>;
            var categoriesNonVAT = <?php echo json_encode($categoriesNonVAT ?? []); ?>;
            var ajaxBasePath = './ajax-inventory/';
        </script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/consignment.js?<?= time() ?>"></script>
    </body>
</html>
<?php
    } else {
        $loginUrl = $base . '/login.php';
        echo '<script> window.location.href = "'. $loginUrl .'"; </script>';
    }
?>