<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include(dirname(__DIR__, 2) . '/includes/pages.header.php');
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $pagesPos = strpos($scriptDir, '/pages');
        if ($pagesPos !== false) {
            $base = substr($scriptDir, 0, $pagesPos);
        } else {
            $base = rtrim($scriptDir, '/\\');
        }
        if ($base === '.' ) { $base = ''; }
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

                .btn-outline-secondary {
                    border: 1px solid var(--secondary-color);
                    color: var(--secondary-color);
                    background: transparent;
                }
                
                .btn-outline-secondary:hover {
                    background: var(--secondary-color);
                    color: #fff;
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
                                <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Outgoing Inventory</h1>
                                <p class="breadcrumb-modern m-0">Manage outgoing stock and sales</p>
                            </div>
                            <div>
                                <span class="badge bg-light text-dark border p-2" style="font-size: 0.9rem;">
                                    SI: <span id="currentSI" class="fw-bold text-primary">-</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Left Column: Product Information -->
                    <div class="col-lg-4">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-box text-primary"></i> Product Information</h5>
                            </div>
                            <div class="card-body-modern">
                                <form id="myForm" method="POST">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label" for="transactionDate">Transaction Date</label>
                                            <input type="text" id="transactionDate" name="transactionDate" class="form-control">
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" value="Yes" name="isConsignment" id="isConsignment" onclick="isConsignmentBox();">
                                                <label class="form-check-label fw-bold text-primary" for="isConsignment">Mark as Consignment</label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="isynBranch">Isyn Branch</label>
                                            <select class="form-select" name="isynBranch" id="isynBranch" onchange="LoadBranch(this.value)">
                                                <option value="" selected>Select Branch</option>
                                            </select>
                                        </div>

                                        <div class="col-12" id="consignmentBranchContainer" style="display: None;">
                                            <label class="form-label" for="consignmentBranch">Consignment Branch</label>
                                            <select class="form-select" name="consignmentBranch" id="consignmentBranch" onchange="forBranchClear();">
                                                <option value="" selected disabled>Select Branch</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="type">Type</label>
                                            <select class="form-select" name="type" id="type" onchange="LoadCategory(this.value);">
                                                <option value="" selected>Select Type</option>
                                                <option value="With VAT">With VAT</option>
                                                <option value="Non-VAT">Non-VAT</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="category">Category</label>
                                            <select class="form-select" aria-label="Category" name="category" id="category" onchange="LoadSerialProduct(this.value);">
                                                <option value="" selected>Select Category</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Select by</label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" value="productName" name="selectBy" id="productName">
                                                    <label class="form-check-label" for="productName">Product Name</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" value="serial" name="selectBy" id="serialNo">
                                                    <label class="form-check-label" for="serialNo">Serial No.</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label" for="SerialProduct" id="SerialProdlbl">Serial / Product</label>
                                            <select id="SerialProduct" name="SerialProduct" class="form-select" onchange="LoadProductSINo(this.value);">
                                            </select>
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label" for="SINo">Supplier SI</label>
                                            <select id="SINo" name="SINo" class="form-select" onchange="LoadProductSummary();">
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Product Summary Card -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-list-check text-primary"></i> Product Summary</h5>
                            </div>
                            <div class="card-body-modern">
                                <form id="summary" method="POST">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label for="psSupplierSI" class="form-label">Supplier SI</label>
                                            <input type="text" id="psSupplierSI" name="psSupplierSI" class="form-control" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="psSerialNo" class="form-label">Serial No.</label>
                                            <input type="text" id="psSerialNo" name="psSerialNo" class="form-control" disabled>
                                        </div>
                                        <div class="col-12">
                                            <label for="psProduct" class="form-label">Product</label>
                                            <input type="text" id="psProduct" name="psProduct" class="form-control" disabled>
                                        </div>
                                        <div class="col-12">
                                            <label for="psSupplier" class="form-label">Supplier</label>
                                            <input type="text" id="psSupplier" name="psSupplier" class="form-control" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="psSRP" class="form-label">SRP</label>
                                            <input type="text" id="psSRP" name="psSRP" class="form-control" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="psQuantity" class="form-label">Quantity</label>
                                            <input type="text" id="psQuantity" name="psQuantity" class="form-control" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="psDealerPrice" class="form-label">Dealer's Price</label>
                                            <input type="text" id="psDealerPrice" name="psDealerPrice" class="form-control" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="psTotalPrice" class="form-label">Total Price</label>
                                            <input type="text" id="psTotalPrice" name="psTotalPrice" class="form-control" disabled>
                                        </div>
                                    </div>
                                </form> 
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sales & Items -->
                    <div class="col-lg-8">
                        <div class="row g-4">
                            <!-- Purchased By Section -->
                            <div class="col-lg-6">
                                <div class="card-modern h-100">
                                    <div class="card-header-modern">
                                        <h5 class="card-title-modern"><i class="fa-solid fa-user-tag text-primary"></i> Purchased By</h5>
                                    </div>
                                    <div class="card-body-modern">
                                        <form id="purchaseBy Form" method="POST">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="customerType" class="form-label">Customer Type</label>
                                                    <select id="customerType" name="customerType" class="form-select" required onchange="toggleCustomerNameInput(this.value)">
                                                        <option value="" selected disabled>SELECT TYPE</option>
                                                        <option value="OTHER CLIENT"> OTHER CLIENT</option>
                                                        <option value="BUSINESS UNIT"> BUSINESS UNIT</option>
                                                        <option value="EXTERNAL CLIENT"> EXTERNAL CLIENT</option>
                                                        <option value="MFI BRANCHES"> MFI BRANCHES</option>
                                                        <option value="MFI HO"> MFI HO</option>
                                                        <option value="OTHERS"> OTHERS</option>
                                                        <option value="STAFF"> STAFF</option>
                                                    </select>
                                                </div>

                                                <div class="col-12">
                                                    <label for="clientNameInput" class="form-label">Customer Name</label>
                                                    <div id="customerNameInputDiv">
                                                        <input type="text" id="customerNameInput" class="form-control" name="customerNameInput" readonly>
                                                    </div>
                                                    <div id="customerNameSelectDiv" style="display:None;">
                                                        <select id="customerNameSelect" class="form-select" name="customerNameSelect" onchange="LoadCustomerNameInfo(this.value);">
                                                            <option value="" selected disabled>Select Customer</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div id="checkboxes">
                                                        <!-- Checkbox for STAFF -->
                                                        <div id="staffCheckboxDiv" class="form-check mt-2" style="display: none;">
                                                            <input type="checkbox" class="form-check-input" id="staffCheckbox" name="staffCheckbox" value="staff">
                                                            <label class="form-check-label" for="staffCheckbox">STAFF LOAN</label>
                                                        </div>
                                                        <!-- Checkboxes for MFI branches -->
                                                        <div id="mfiCheckboxDiv" class="mt-2" style="display: none;">
                                                            <div class="d-flex gap-3">
                                                                <div class="form-check">
                                                                    <input type="radio" class="form-check-input" id="branchUsed" name="mfiCheckbox" value="BRANCH USED">
                                                                    <label class="form-check-label" for="branchUsed">BRANCH USED</label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input type="radio" class="form-check-input" id="mfiUsed" name="mfiCheckbox" value="MFI CLIENT">
                                                                    <label class="form-check-label" for="mfiUsed">MFI CLIENT</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-6">
                                                    <label for="tinNoinput" class="form-label">TIN</label>
                                                    <input type="text" class="form-control" name="tinNoinput" id="tinNoinput" placeholder="000-000-000-0000" required>
                                                </div>
                                                <div class="col-6">
                                                    <label for="userSINo" class="form-label">SI No.</label>
                                                    <input type="text" class="form-control" name="userSINo" id="userSINo" disabled>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label border-bottom pb-1 w-100 mb-2">Address</label>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <select id="addrRegion" name="addrRegion" class="form-select form-select-sm" onchange="LoadProvince(this.value)">
                                                                <option value="" selected disabled>Select Region</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <select id="addrProvince" name="addrProvince" class="form-select form-select-sm" onchange="LoadCity(this.value)">
                                                                <option value="" selected disabled>Select Province</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <select id="addrCity" name="addrCity" class="form-select form-select-sm" onchange="LoadBarangay(this.value)">
                                                                <option value="" selected disabled>Select City</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <select id="addrBarangay" name="addrBarangay" class="form-select form-select-sm">
                                                                <option value="" selected disabled>Select Barangay</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <input type="text" class="form-control form-control-sm" name="addrStreet" id="addrStreet" placeholder="Street/Building/House No. (Optional)">
                                                        </div>
                                                        <input type="hidden" name="fullAddress" id="fullAddress">
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label for="status" class="form-label">Status</label>
                                                    <div class="d-flex gap-2">
                                                        <select class="form-select" name="status" id="status" required>
                                                            <option value="" selected disabled>Select Status</option>
                                                            <option value="PAID">PAID</option>
                                                            <option value="UNPAID">UNPAID</option>
                                                        </select>
                                                        <button type="button" class="btn btn-info text-white text-nowrap" id="searchTransmittalBtn" name="searchTransmittalBtn" onclick="SearchTransmittal();"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Merchandise Sales Section -->
                            <div class="col-lg-6">
                                <div class="card-modern h-100">
                                    <div class="card-header-modern">
                                        <h5 class="card-title-modern"><i class="fa-solid fa-cart-shopping text-primary"></i> Merchandise Sales</h5>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" id="addDiscount" name="addDiscount" value="Yes" onclick="AddDiscount();">
                                            <label class="form-check-label" for="addDiscount">Add Discount</label>
                                        </div>
                                    </div>
                                    <div class="card-body-modern d-flex flex-column justify-content-between">
                                        <form id="sales">
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <label for="srpMerchSales" class="form-label">SRP</label>
                                                    <input type="text" id="srpMerchSales" name="srpMerchSales" class="form-control" placeholder="0.00" onchange="formatInput(this);">
                                                </div>
                                                <div class="col-6">
                                                    <label for="quantityMerchSales" class="form-label">Quantity</label>
                                                    <input type="number" name="quantityMerchSales" id="quantityMerchSales" class="form-control" placeholder="0" min="1" onchange="ComputeMerchandise();">
                                                </div>
                                                <div class="col-6">
                                                    <label for="vatMerchSales" class="form-label">DP</label>
                                                    <input type="text" class="form-control" name="vatMerchSales" id="vatMerchSales" placeholder="0.00" disabled>
                                                </div>
                                                <div class="col-6">
                                                    <label for="totalCostMerchSales" class="form-label">Total Cost</label>
                                                    <input type="text" class="form-control" name="totalCostMerchSales" id="totalCostMerchSales" placeholder="0.00" disabled>
                                                </div>

                                                <div class="col-12"><hr class="my-1"></div>

                                                <div class="col-6">
                                                    <label for="discInterestMerchSales" class="form-label">Disc. Interest</label>
                                                    <input type="number" class="form-control" id="discInterestMerchSales" name="discInterestMerchSales" placeholder="0.00" onchange="computeWithDiscount();" disabled>
                                                </div>
                                                <div class="col-6">
                                                    <label for="discountAmountMerchSales" class="form-label">Discount Amt</label>
                                                    <input type="text" class="form-control" name="discountAmountMerchSales" id="discountAmountMerchSales" placeholder="0.00" disabled>
                                                </div>
                                                <div class="col-6">
                                                    <label for="newSRPMerchSales" class="form-label">New SRP</label>
                                                    <input type="text" class="form-control" id="newSRPMerchSales" name="newSRPMerchSales" placeholder="0.00" disabled>
                                                </div>
                                                <div class="col-6">
                                                    <label for="totalDiscountMerchSales" class="form-label">Disc Total Cost</label>
                                                    <input type="text" class="form-control" id="totalDiscountMerchSales" name="totalDiscountMerchSales" placeholder="0.00" disabled>
                                                </div>
                                            </div>
                                        </form>
                                        
                                        <div class="d-flex justify-content-end mt-4">
                                            <button class="btn btn-primary w-100" type="button" id="addToList" name="addToList" onclick="AddToList();"><i class="fa-solid fa-plus-circle"></i> Add to List</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <!-- Items Table Section -->
                    <div class="col-12">
                                <div class="card-modern">
                                    <div class="card-header-modern">
                                        <h5 class="card-title-modern"><i class="fa-solid fa-boxes-stacked text-primary"></i> Items List</h5>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-success" id="saveBtn" name="saveBtn" onclick="Save();" disabled><i class="fa-solid fa-check-circle"></i> Submit</button>
                                            <button type="button" class="btn btn-sm btn-danger" id="DeleteFromListBtn" name="DeleteFromListBtn" onclick="DeleteFromItems();" disabled><i class="fa fa-trash"></i> Delete</button>
                                            <button type="button" class="btn btn-sm btn-info text-white" id="printRecentBtn" name="printRecentBtn" onclick="PrintRecentOut();"><i class="fa-solid fa-print"></i> Print</button>
                                        </div>
                                    </div>
                                    <div class="card-body-modern p-0">
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table id="itemsTbl" class="table table-custom table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Supplier SI</th>
                                                        <th>Serial No</th>
                                                        <th>Product</th>
                                                        <th>Sold To</th>
                                                        <th>Quantity</th>
                                                        <th>Unit</th>
                                                        <th>Articles</th>
                                                        <th>Unit Price</th>
                                                        <th>Amount</th>
                                                        <th>Vatable Sales</th>
                                                        <th>VAT</th>
                                                        <th>Amount Due</th>
                                                        <th>Discount Product</th>
                                                        <th>Discounted Amount</th>
                                                        <th>New Unit Price</th>
                                                        <th>New Total Amont</th>
                                                        <th>Category</th>
                                                        <th>Supplier</th>
                                                        <th>Warranty</th>
                                                        <th>TIN</th>
                                                        <th>Address</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="itemsList">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals (kept functional but styled) -->
            <div class="modal fade" id="SearchTransmittalMDL" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="SearchTransmittal" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header bg-primary text-white border-bottom">
                            <h5 class="modal-title mb-0" id="exampleModalLabel">
                                <i class="fa-solid fa-search me-2"></i>Search Transmittal Records
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Search Section -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0">
                                            <i class="fa-solid fa-calendar-days me-2"></i>Select Date Range
                                        </h6>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('today')">
                                                <i class="fa-solid fa-calendar-day me-1"></i>Today
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('week')">
                                                <i class="fa-solid fa-calendar-week me-1"></i>This Week
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('month')">
                                                <i class="fa-solid fa-calendar-alt me-1"></i>This Month
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setDateRange('year')">
                                                <i class="fa-solid fa-calendar me-1"></i>This Year
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label for="transmittalDateFrom" class="form-label fw-semibold">
                                                <i class="fa-solid fa-calendar-from me-1"></i>Date From
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fa-solid fa-calendar"></i>
                                                </span>
                                                <input type="text" name="transmittalDateFrom" id="transmittalDateFrom" 
                                                       class="form-control" placeholder="Start date">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="transmittalDateTo" class="form-label fw-semibold">
                                                <i class="fa-solid fa-calendar-to me-1"></i>Date To
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fa-solid fa-calendar"></i>
                                                </span>
                                                <input type="text" name="transmittalDateTo" id="transmittalDateTo" 
                                                       class="form-control" placeholder="End date">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="transmittalStatus" class="form-label fw-semibold">
                                                <i class="fa-solid fa-filter me-1"></i>Status
                                            </label>
                                            <select class="form-select" id="transmittalStatus" name="transmittalStatus">
                                                <option value="">All</option>
                                                <option value="active">Active</option>
                                                <option value="completed">Completed</option>
                                                <option value="pending">Pending</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="transmittalClient" class="form-label fw-semibold">
                                                <i class="fa-solid fa-user me-1"></i>Client Name
                                            </label>
                                            <input type="text" id="transmittalClient" name="transmittalClient" 
                                                   class="form-control" placeholder="Enter client name">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="transmittalTransNo" class="form-label fw-semibold">
                                                <i class="fa-solid fa-hashtag me-1"></i>Transmittal No
                                            </label>
                                            <input type="text" id="transmittalTransNo" name="transmittalTransNo" 
                                                   class="form-control" placeholder="Transmittal number">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button class="btn btn-primary w-100 btn-lg" id="transmittalSearchBtn" name="transmittalSearchBtn" onclick="TransmittalSearch();">
                                                <i class="fa-solid fa-magnifying-glass me-1"></i>Search
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fa-solid fa-info-circle me-1"></i>
                                                    Use quick date buttons or select custom date range for more accurate results
                                                </small>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSearchFilters();">
                                                        <i class="fa-solid fa-times me-1"></i>Clear Filters
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="exportTransmittalData();">
                                                        <i class="fa-solid fa-download me-1"></i>Export
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Results Section -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fa-solid fa-list me-2"></i>Transmittal Records
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive-custom" style="max-height: 250px; overflow-y: auto;">
                                        <table id="listTbl" class="table table-custom table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th class="text-center" style="width: 120px;">
                                                        <i class="fa-solid fa-hashtag me-1"></i>TRANS NO.
                                                    </th>
                                                    <th class="text-center" style="width: 200px;">
                                                        <i class="fa-solid fa-user me-1"></i>CLIENT
                                                    </th>
                                                    <th class="text-center" style="width: 120px;">
                                                        <i class="fa-solid fa-calendar me-1"></i>DATE
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="listList" class="table-group-divider">
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">
                                                        <i class="fa-solid fa-search fa-2x mb-2"></i>
                                                        <p class="mb-0">No data available in table</p>
                                                        <small class="text-muted">Select a date range and click Search to view transmittal records</small>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Details Section -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="fa-solid fa-box me-2"></i>Transmittal Details
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive-custom" style="max-height: 300px; overflow-y: auto;">
                                        <table id="productTbl" class="table table-custom table-hover">
                                            <thead class="table-light sticky-top">
                                                <tr>
                                                    <th class="text-center">Product</th>
                                                    <th class="text-center">Transmittal Qty</th>
                                                    <th class="text-center">Current Inv Qty</th>
                                                    <th class="text-center">Availability</th>
                                                    <th class="text-center">Consignment</th>
                                                    <th class="text-center">Amount</th>
                                                    <th class="text-center">SINo</th>
                                                    <th class="text-center">SerialNo</th>
                                                    <th class="text-center">Product</th>
                                                    <th class="text-center">Supplier</th>
                                                    <th class="text-center">Category</th>
                                                    <th class="text-center">Type</th>
                                                    <th class="text-center">Stock</th>
                                                    <th class="text-center">Branch</th>
                                                </tr>
                                            </thead>
                                            <tbody id="productList" class="table-group-divider">
                                                <tr>
                                                    <td colspan="14" class="text-center text-muted py-4">
                                                        <i class="fa-solid fa-box-open fa-2x mb-2"></i>
                                                        <p class="mb-0">No transmittal details available</p>
                                                        <small class="text-muted">Select a transmittal record from the table above to view details</small>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="row g-2 mt-3">
                                <div class="col-md-6">
                                    <button class="btn btn-danger w-100 btn-lg" type="button" id="deleteFromTProdList" name="deleteFromTProdList" onclick="DeleteFromTransProdList();" disabled>
                                        <i class="fa-solid fa-trash me-2"></i>Delete Selected
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-success w-100 btn-lg" type="button" id="loadToList" name="loadToList" onclick="LoadtoList();" disabled>
                                        <i class="fa-solid fa-download me-2"></i>Load to List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="PanelConsignMDL" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="PanelConsign" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-bottom">
                            <h1 class="modal-title fs-5 fw-bold" id="exampleModalLabel">Panel Consign</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label" for="branchConsign">Branch</label>
                                <select class="form-select" name="branchConsign" id="branchConsign" onchange="LoadBranchConsign(this.value);">
                                    <option value="" selected>Select</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="availableQty">Available Qty</label>
                                <input type="text" id="availableQty" name="availableQty" class="form-control" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="enteredQty">Entered Qty</label>
                                <div class="input-group">
                                    <input type="text" id="enteredQty" name="enteredQty" class="form-control">
                                    <button class="btn btn-primary" type="button" id="addBranchConsign" name="addBranchConsign" onclick="AddBranchConsign();"><i class="fa-solid fa-plus"></i></button>
                                    <button class="btn btn-danger" type="button" id="deleteBranchConsign" name="deleteBranchConsign" onclick="DeleteFromConsignQty();" disabled><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            
                            <div class="table-responsive-custom mb-3" style="max-height: 150px; overflow-y: auto;">
                                <table id="branchConsignTbl" class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Branch</th>
                                            <th>Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="branchConsignList">
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="needQty">Need Qty</label>
                                <div class="input-group">
                                    <input type="text" id="needQty" name="needQty" class="form-control" disabled>
                                    <button class="btn btn-success" type="button" id="confirmBranchConsignQty" name="confirmBranchConsignQty" onclick="ConfirmBranchConsignQty();"><i class="fa-solid fa-check"></i> Confirm</button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <?php
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            // Find the position of '/pages' in the path to determine root
            $pagesPos = strpos($scriptDir, '/pages');
            if ($pagesPos !== false) {
                $base = substr($scriptDir, 0, $pagesPos);
            } else {
                $base = rtrim($scriptDir, '/\\');
            }
            if ($base === '.' ) { $base = ''; }
        ?>
        <script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/outgoinginventory.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $pagesPos = strpos($scriptDir, '/pages');
    if ($pagesPos !== false) {
        $base = substr($scriptDir, 0, $pagesPos);
    } else {
        $base = rtrim($scriptDir, '/\\');
    }
    if ($base === '.') { $base = ''; }
    $loginUrl = $base . '/login.php';
    echo '<script> window.location.href = "'. $loginUrl .'"; </script>';
  }
?>

<script>
    document.getElementById('tinNoinput').addEventListener('input', function (e) {
        let inputValue = e.target.value.replace(/\D/g, '').substring(0, 13); // Get only digits and limit to 15 characters
        let formattedValue = '';
        
        for (let i = 0; i < inputValue.length; i++) {
            if (i > 0 && (i % 3 === 0 && i < 9)) { // Add dashes after every 3 digits until the 9th digit
                formattedValue += '-';
            } else if (i === 9) { // Add a dash after the 9th digit for the last group of 4 digits
                formattedValue += '-';
            }
            formattedValue += inputValue[i];
        }

        e.target.value = formattedValue;
    });
</script>