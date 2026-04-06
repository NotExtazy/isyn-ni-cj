<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
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
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

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
                text-transform: uppercase; /* Match original behavior */
            }
            
            input::placeholder {
                text-transform: none;
            }

            .form-control:focus, .form-select:focus {
                background-color: #fff;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            }

            .form-control:disabled, .form-select:disabled {
                background-color: #e9ecef;
                opacity: 0.8;
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
                text-transform: uppercase;
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

            .btn-warning {
                background-color: var(--warning-color);
                color: #fff;
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            }
            
            .btn-info {
                background-color: var(--info-color);
                color: #fff;
                box-shadow: 0 4px 12px rgba(13, 202, 240, 0.3);
            }

            .btn-secondary {
                background-color: var(--secondary-color);
                box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
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
                text-transform: uppercase; /* Match original behavior */
            }

            .table-custom tbody tr:last-child td {
                border-bottom: none;
            }

            .table-custom tbody tr:hover td {
                background-color: #f1f5f9;
                cursor: pointer;
            }

            .selected td {
                background-color: #e3ebf7 !important;
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
                            <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Order Confirmation</h1>
                            <p class="breadcrumb-modern m-0">Create and manage order confirmations</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Info -->
            <div class="card-modern">
                <div class="card-body-modern">
                    <form action="" method="post" id="myForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Order Confirmation No.</label>
                                <div class="input-group">
                                    <input type="text" name="order_no" class="form-control" id="order_no" placeholder="Order Confirmation No." disabled value="">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#OCSearchMDL" onclick="OCSearch()" title="Search Order Confirmation"><i class="fa-solid fa-magnifying-glass"></i></button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="recipient" class="form-label">To (Recipient)</label>
                                <input type="text" class="form-control" name="recipient" id="recipient" placeholder="Recipient Name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="sender" class="form-label">From</label>
                                <input type="text" class="form-control" name="sender" id="sender" value="ISYNERGIESINC" disabled>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Column: Product Information -->
                <div class="col-lg-5">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-clipboard-list text-primary"></i> Product Information</h5>
                        </div>
                        <div class="card-body-modern">
                            <form id="orderform" method="POST" class="needs-validation" novalidate onsubmit="return validateForm()">
                                <div class="mb-3" id="isynBranchDiv">
                                    <label class="form-label" for="isynBranch">Isyn Branch</label>
                                    <select class="form-select" name="isynBranch" id="isynBranch">
                                        <option value="" selected disabled>Select</option>
                                        <option value="HEAD OFFICE">HEAD OFFICE</option>
                                    </select>
                                </div>

                                <div class="mb-3" id="consignmentBranchContainer" style="display: None;">
                                    <label class="form-label" for="consignmentBranch">Branch</label>
                                    <select class="form-select" name="consignmentBranch" id="consignmentBranch">
                                        <option value="" selected disabled>Select</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="type">Type</label>
                                    <select class="form-select" name="type" id="type">
                                        <option value="" selected disabled>Select</option>
                                        <option value="With VAT">With VAT</option>
                                        <option value="Non-VAT">Non-VAT</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="category">Category</label>
                                    <select class="form-select" aria-label="Category" name="category" id="category">
                                        <option value="" disabled selected>Select</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Select by</label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="product">
                                            <label class="form-check-label" for="inlineRadio1">Product name</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="serial">
                                            <label class="form-check-label" for="inlineRadio2">Serial No.</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3" id="serialLabel">
                                    <label id="selectLabel" class="form-label">Select Item</label>
                                    <select id="itemSelect" name="itemSelect" class="form-select">
                                        <option value="" disabled selected>Select</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="SIno" class="form-label">SI no</label>
                                    <select id="SIno" name="SIno" class="form-select">
                                        <option value="" disabled selected>Select SI No</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Product Summary & Computation -->
                <div class="col-lg-7">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-list-check text-primary"></i> Product Summary</h5>
                        </div>
                        <div class="card-body-modern">
                            <form id="summary" method="POST" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Supplier SI</label>
                                        <input type="text" class="form-control" readonly name="supplierSIdisplay" id="supplierSIdisplay">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Serial No</label>
                                        <input type="text" class="form-control" readonly name="serialNodisplay" id="serialNodisplay">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Product</label>
                                        <input type="text" class="form-control" readonly name="productDisplay" id="productDisplay">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Supplier</label>
                                        <input type="text" class="form-control" readonly name="supplierDisplay" id="supplierDisplay">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SRP</label>
                                        <input type="text" class="form-control" readonly name="srpDisplay" id="srpDisplay">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Available Qty</label>
                                        <input type="text" class="form-control" readonly name="quantityDisplay" id="quantityDisplay">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Dealer's Price</label>
                                        <input type="text" class="form-control" readonly name="delearsPriceDisplay" id="delearsPriceDisplay">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Total Price</label>
                                        <input type="text" class="form-control" readonly name="totalPriceDisplay" id="totalPriceDisplay">
                                    </div>
                                </div>
                            </form>

                            <hr class="my-4">

                            <form id="compute" method="POST" class="needs-validation" novalidate onsubmit="return validateForm()">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label text-primary">Order Quantity</label>
                                        <input type="text" class="form-control border-primary" name="quantity" id="quantityInput" placeholder="0">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between mb-1">
                                            <label class="form-label text-primary">Final SRP</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="editSRPtoggle" id="editSRPtoggle" onclick="editSRPToggle()">
                                                <label class="form-check-label" for="editSRPtoggle" style="font-size: 0.8rem;">Edit</label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control border-primary" name="editSRP" disabled id="editSRP">
                                    </div>
                                    <div class="col-12" hidden>
                                        <input type="text" id="warranty" class="form-control" readonly disabled required>
                                        <input type="text" id="vat" class="form-control" readonly disabled required>
                                        <input type="text" id="vatsales" class="form-control" readonly disabled required>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Items List Section -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-boxes-stacked text-primary"></i> Order Details</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" id="addButton" type="button"><i class="fa-solid fa-plus"></i> Add Item</button>
                                <button class="btn btn-sm btn-danger" id="cancel-btn" onclick="cancelProduct()" disabled><i class="fa-solid fa-trash"></i> Remove Selected</button>
                            </div>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-custom table-hover" id="table1">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>SRP</th>
                                            <th>SI No.</th>
                                            <th>Vat</th>
                                            <th>Vat Sales</th>
                                            <th>Warranty</th>
                                            <th>Date Prepared</th>
                                            <th>Serial No.</th>
                                            <th>Category</th>
                                            <th>Type</th>
                                            <th>Branch</th>
                                            <th>Supplier</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody">
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end p-3">
                                <button class="btn btn-success px-4" id="submit-btn" type="button" disabled><i class="fa-solid fa-floppy-disk"></i> Submit Order</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal -->
            <div class="modal fade" id="OCSearchMDL" tabindex="-1" aria-labelledby="OCSearchMDLLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom">
                            <h1 class="modal-title fs-5 fw-bold" id="OCSearchMDLLabel">Search Order Confirmation</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4" style="max-height:70vh; overflow-y:auto;">
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search by Recipient Name" id="searchInput" oninput="OCSearch()">
                                        <button type="button" class="btn btn-primary" onclick="OCSearch()" title="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                                        <button type="button" class="btn btn-success" id="printOCBtn" onclick="printSelectedOC()" disabled title="Print selected OC"><i class="fa-solid fa-print"></i> Print</button>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="fromDate" placeholder="From date">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="toDate" placeholder="To date">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-outline-secondary w-100" onclick="OCSearch()" title="Filter by date"><i class="fa-regular fa-calendar"></i> Filter</button>
                                </div>
                            </div>
                            
                            <div class="table-responsive-custom">
                                <table class="table table-custom table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width:20%">OC No.</th>
                                            <th style="width:45%">Client</th>
                                            <th style="width:35%">Date Prepared</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ocSearchTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
            
        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/orderconfirmation.js?<?= time() ?>"></script>
        
    </body>
</html>
<?php
  } else {
    $loginUrl = $base . '/login.php';
    echo '<script> window.location.href = "'. $loginUrl .'"; </script>';
  }
?>