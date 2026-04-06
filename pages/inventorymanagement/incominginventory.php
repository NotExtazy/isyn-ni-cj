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
    ?>
 
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

                /* Standard Dropdown Styling */
                .form-select {
                    background-color: var(--input-bg);
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    height: 42px;
                    padding: 8px 12px;
                    color: var(--text-main);
                    font-weight: 500;
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
                                <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Incoming Inventory</h1>
                                <p class="breadcrumb-modern m-0">Manage your incoming stock and items</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Left Column: Particulars Form -->
                    <div class="col-lg-4">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-clipboard-list text-primary"></i> Particulars</h5>
                                <!-- Action Buttons moved here for better context -->
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-danger" type="button" id="cancel" onclick="Cancel()" disabled hidden>Cancel</button>
                                    <button id="updateBtn" class="btn btn-sm btn-success" type="button" onclick="Save();" disabled hidden><i class="fa-solid fa-floppy-disk"></i> Update</button>
                                    <button id="editSaveBtn" class="btn btn-sm btn-primary" type="button" onclick="Save();" disabled hidden><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                    <button id="addToList" class="btn btn-sm btn-primary" type="button" disabled hidden><i class="fa-solid fa-plus"></i> Add to List</button>
                                    <button id="save" class="btn btn-sm btn-success" type="button" onclick="Save();" disabled hidden><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                    <button id="editBtn" class="btn btn-sm btn-warning" type="button" disabled hidden><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    
                                     <button type="button" id="addNew" class="btn btn-sm btn-success">
                                        <i class="fa-solid fa-plus"></i> New
                                    </button>
                                </div>
                            </div>
                            <div class="card-body-modern">
                                <form id="inventoryinForm" method="POST" enctype="multipart/form-data">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="branch" class="form-label">Isyn Branch</label>
                                            <select class="form-select" id="branch" name="branch" required>
                                                <option value="" selected disabled>Select Branch</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="type" class="form-label">Product Type</label>
                                            <select class="form-select" name="type" id="type" required>
                                                <option value="" selected disabled>Select Type</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="category" class="form-label">Product Category</label>
                                            <select class="form-select" name="category" id="category">
                                                <option value="" selected disabled>Select Category</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="product" class="form-label">Product Name</label>
                                            <select class="form-select" name="product" id="product" required>
                                                <option value="" selected disabled>Select Product</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="supplier" class="form-label">Supplier</label>
                                            <select class="form-select" name="supplier" id="supplier" required disabled>
                                                <option value="" selected disabled>Select Supplier</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="suppliersSI" class="form-label">Supplier(s) SI</label>
                                            <input type="text" class="form-control" id="suppliersSI" name="suppliersSI" required disabled>
                                        </div>
                                        <div class="col-12">
                                            <label for="serialNo" class="form-label">Serial Number</label>
                                            <input type="text" class="form-control" id="serialNo" name="serialNo" required disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="purchaseDate" class="form-label">Purchase Date</label>
                                            <input type="text" class="form-control" id="purchaseDate" name="purchaseDate" required disabled value="<?php echo date('m/d/Y'); ?>">
                                        </div>
                                        <div class="col-6">
                                            <label for="warranty" class="form-label">Warranty</label>
                                            <input type="text" class="form-control" id="warranty" name="warranty" disabled>
                                        </div>
                                        <div class="col-12">
                                            <label for="imageName" class="form-label">Image</label>
                                            <input type="file" class="form-control" id="imageName" name="imageName" accept=".jpg,.jpeg,.png" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="dealersPrice" class="form-label">Dealer Price</label>
                                            <input type="text" class="form-control" id="dealersPrice" name="dealersPrice" placeholder="0.00" onchange="formatInput(this);Compute()" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="srp" class="form-label">SRP</label>
                                            <input type="text" class="form-control" id="srp" name="srp" placeholder="0.00" onchange="formatInput(this);Compute()" disabled>
                                        </div>
                                        <div class="col-12">
                                            <label for="quantity" class="form-label">Quantity</label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" placeholder="0" onchange="Compute()" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="totalPrice" class="form-label">Total Price</label>
                                            <input type="text" class="form-control" id="totalPrice" name="totalPrice" placeholder="0.00" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="totalSRP" class="form-label">Total SRP</label>
                                            <input type="text" class="form-control" id="totalSRP" name="totalSRP" placeholder="0.00" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="mpi" class="form-label">MPI</label>
                                            <input type="text" class="form-control" id="mpi" name="mpi" placeholder="0.00" disabled>
                                        </div>
                                        <div class="col-6">
                                            <label for="totalmarkup" class="form-label">Total Markup</label>
                                            <input type="text" class="form-control" id="totalmarkup" name="totalmarkup" placeholder="0.00" disabled>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column: Data Tables -->
                    <div class="col-lg-8">
                        <!-- Data Inventory Section -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-database text-primary"></i> Data Inventory</h5>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light">From</span>
                                        <input type="text" class="form-control" id="filterDateFrom" placeholder="mm/dd/yyyy" style="max-width: 120px;">
                                        <span class="input-group-text bg-light">To</span>
                                        <input type="text" class="form-control" id="filterDateTo" placeholder="mm/dd/yyyy" style="max-width: 120px;">
                                    </div>
                                    <input type="text" class="form-control form-control-sm" id="searchDataInv" placeholder="Search..." style="max-width: 150px;">
                                    <button class="btn btn-sm btn-outline-secondary" id="clearFilterBtn" type="button" onclick="ClearFilter()" title="Clear Filters"><i class="fa-solid fa-eraser"></i></button>
                                    <button class="btn btn-sm btn-danger" name="DeleteFromDataInvBtn" id="DeleteFromDataInvBtn" type="button" onclick="DeleteFromDataInv()" disabled><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="card-body-modern p-0">
                                <div class="table-responsive" style="max-height: 970px; overflow-y: auto;">
                                    <table id="dataInvTbl" class="table table-custom table-hover">
                                        <thead>
                                            <tr>
                                                <th>SI No.</th>
                                                <th>Serial No.</th>
                                                <th>Product Name</th>
                                                <th>Supplier</th>
                                                <th>Category</th>
                                                <th>Type</th>
                                                <th>Branch</th>
                                                <th>Purchase Date</th>
                                                <th>Warranty</th>
                                                <th>Date Encoded</th>
                                                <th>Quantity</th>
                                                <th>Dealer(s) Price</th>
                                                <th>SRP</th>
                                                <th>Total Price</th>
                                                <th>Total SRP</th>
                                                <th>MPI</th>
                                                <th>Total Markup</th>
                                                <th>Vatable Sales</th>
                                                <th>VAT</th>
                                                <th>Amount Due</th>
                                                <th>Image Name</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dataInvList">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-boxes-stacked text-primary"></i> Items List</h5>
                                <button class="btn btn-sm btn-danger" name="DeleteFromListBtn" id="DeleteFromListBtn" type="button" onclick="DeleteFromList()" disabled><i class="fa-solid fa-trash"></i> Delete</button>
                            </div>
                            <div class="card-body-modern p-0">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table id="itemTbl" class="table table-custom table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Serial No.</th>
                                                <th>Warranty</th>
                                                <th>Dealer(s) Price</th>
                                                <th>SRP</th>
                                                <th>Qty</th>
                                                <th>Total Price</th>
                                                <th>Total SRP</th>
                                                <th>MPI</th>
                                                <th>Total Markup</th>
                                                <th>Branch</th>
                                                <th>Type</th>
                                                <th>Category</th>
                                                <th>Supplier</th>
                                                <th>Supplier(s) SI</th>
                                                <th>Purchase Date</th>
                                                <th>Image Name</th>
                                                <th>Date Encoded</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemList">
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="18"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
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
        <script src="<?php echo $base; ?>/assets/sweetalert2/sweetalert2.min.js"></script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/incominginventory.js"></script>
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
