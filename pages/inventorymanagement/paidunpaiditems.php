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
            }
            
            .form-control:focus, .form-select:focus {
                background-color: #fff;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            }

            .form-check-input:checked {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
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

            /* Charts */
            #summaryPie { width: 100%; min-height: 300px; }

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
        </style>

        <div class="container-fluid main-container">
            <!-- Page Header -->
            <div class="card-modern">
                <div class="card-body-modern py-3">
                    <div class="page-header-container m-0">
                        <div>
                            <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Paid/Unpaid Items Report</h1>
                            <p class="breadcrumb-modern m-0">Track payments and outstanding balances</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Column: Filter -->
                <div class="col-lg-4 col-md-12">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-filter text-primary"></i> Filter Options</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="fromDate" class="form-label">Comparison Year 1</label>
                                    <input type="number" class="form-control" id="fromDate" placeholder="YYYY" min="2000" max="2099" required>
                                </div>
                                <div class="col-6">
                                    <label for="toDate" class="form-label">Comparison Year 2</label>
                                    <input type="number" class="form-control" id="toDate" placeholder="YYYY" min="2000" max="2099" required>
                                </div>
                                
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="" id="consignmentCheckbox">
                                            <label class="form-check-label fw-medium" for="consignmentCheckbox">Consignment Only</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="flexCheckIndeterminate">
                                            <label class="form-check-label fw-medium" for="flexCheckIndeterminate">With SI Number</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="typeSelect" class="form-label">Payment Status</label>
                                    <select class="form-select" id="typeSelect">
                                        <option value="1">Paid</option>
                                        <option value="2">Unpaid</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-3">
                                    <button id="searchButton" class="btn btn-primary w-100 py-2">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Summary -->
                <div class="col-lg-8 col-md-12">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-chart-bar text-primary"></i> Financial Summary</h5>
                            <div class="ms-3 d-flex gap-2">
                                <span class="badge bg-primary" id="comparison-label-1" style="font-size: 0.8rem;"></span>
                                <span class="badge bg-success" id="comparison-label-2" style="font-size: 0.8rem;"></span>
                            </div>
                        </div>
                        <div class="card-body-modern">
                            <div class="row">
                                <div class="col-md-7">
                                    <div id="summaryBar" style="width: 100%; min-height: 350px;"></div>
                                </div>
                                <div class="col-md-5">
                                    <div class="row g-3 h-100">
                                        <!-- Comparison Column 1 -->
                                        <div class="col-6 d-flex flex-column">
                                            <div class="text-center mb-2 fw-bold text-primary" id="year1-label">Year 1</div>
                                            <div class="p-3 bg-light rounded-3 border flex-grow-1">
                                                <div class="mb-3 d-flex flex-column justify-content-center align-items-center border-bottom pb-2 text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total Price</span>
                                                    <span class="fs-6 fw-bold text-primary" id="totalPriceLabel1">0.00</span>
                                                </div>
                                                <div class="mb-3 d-flex flex-column justify-content-center align-items-center border-bottom pb-2 text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total SRP</span>
                                                    <span class="fs-6 fw-bold text-dark" id="totalSRPLabel1">0.00</span>
                                                </div>
                                                <div class="mb-3 d-flex flex-column justify-content-center align-items-center border-bottom pb-2 text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total Markup</span>
                                                    <span class="fs-6 fw-bold text-success" id="totalMarkupLabel1">0.00</span>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center align-items-center text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total Quantity</span>
                                                    <span class="fs-6 fw-bold text-dark" id="totalQuantityLabel1">0</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Comparison Column 2 -->
                                        <div class="col-6 d-flex flex-column">
                                            <div class="text-center mb-2 fw-bold text-success" id="year2-label">Year 2</div>
                                            <div class="p-3 bg-light rounded-3 border flex-grow-1">
                                                <div class="mb-3 d-flex flex-column justify-content-center align-items-center border-bottom pb-2 text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total Price</span>
                                                    <span class="fs-6 fw-bold text-primary" id="totalPriceLabel2">0.00</span>
                                                </div>
                                                <div class="mb-3 d-flex flex-column justify-content-center align-items-center border-bottom pb-2 text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total SRP</span>
                                                    <span class="fs-6 fw-bold text-dark" id="totalSRPLabel2">0.00</span>
                                                </div>
                                                <div class="mb-3 d-flex flex-column justify-content-center align-items-center border-bottom pb-2 text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total Markup</span>
                                                    <span class="fs-6 fw-bold text-success" id="totalMarkupLabel2">0.00</span>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center align-items-center text-center">
                                                    <span class="text-secondary fw-bold text-uppercase" style="font-size: 0.65rem;">Total Quantity</span>
                                                    <span class="fs-6 fw-bold text-dark" id="totalQuantityLabel2">0</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client List Table (Comparison 1) -->
                <div class="col-lg-6 col-md-12">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-users text-primary"></i> Client List (<span id="client-label-1">Month 1</span>)</h5>
                            <div class="ms-auto" style="width: 200px;">
                                <input type="search" id="clientSearch1" class="form-control form-control-sm" placeholder="Search Client...">
                            </div>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive-custom" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-custom table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Total Payables</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientListTbody1"></tbody>
                                </table>
                            </div>
                            <div class="p-3 border-top bg-light">
                                <div id="clientPagination1" class="mb-3 d-flex justify-content-center"></div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Clients</label>
                                        <input type="text" class="form-control fw-bold bg-white" id="totalClientsCount1" readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Amount</label>
                                        <input type="text" class="form-control fw-bold bg-white text-primary" id="totalPayables1" placeholder="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table (Comparison 1) -->
                <div class="col-lg-6 col-md-12">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-list-check text-primary"></i> Items List (<span id="items-label-1">Month 1</span>)</h5>
                            <div class="ms-auto" style="width: 200px;">
                                <input type="search" id="tableSearch1" class="form-control form-control-sm" placeholder="Search Item...">
                            </div>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive-custom" style="max-height: 520px; overflow-y: auto;">
                                <table id="itemsTable1" class="table table-custom table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>SI No.</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>City</th>
                                            <th>Product</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">VAT Sales</th>
                                            <th class="text-end">Total SRP</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody1"></tbody>
                                </table>
                            </div>
                            <div class="p-3 border-top bg-light">
                                <div id="itemsPagination1" class="d-flex justify-content-center"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client List Table (Comparison 2) -->
                <div class="col-lg-6 col-md-12">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-users text-success"></i> Client List (<span id="client-label-2">Month 2</span>)</h5>
                            <div class="ms-auto" style="width: 200px;">
                                <input type="search" id="clientSearch2" class="form-control form-control-sm" placeholder="Search Client...">
                            </div>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive-custom" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-custom table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th class="text-end">Quantity</th>
                                            <th class="text-end">Total Payables</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientListTbody2"></tbody>
                                </table>
                            </div>
                            <div class="p-3 border-top bg-light">
                                <div id="clientPagination2" class="mb-3 d-flex justify-content-center"></div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Clients</label>
                                        <input type="text" class="form-control fw-bold bg-white" id="totalClientsCount2" readonly>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Amount</label>
                                        <input type="text" class="form-control fw-bold bg-white text-success" id="totalPayables2" placeholder="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table (Comparison 2) -->
                <div class="col-lg-6 col-md-12">
                    <div class="card-modern h-100">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-list-check text-success"></i> Items List (<span id="items-label-2">Month 2</span>)</h5>
                            <div class="ms-auto" style="width: 200px;">
                                <input type="search" id="tableSearch2" class="form-control form-control-sm" placeholder="Search Item...">
                            </div>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive-custom" style="max-height: 520px; overflow-y: auto;">
                                <table id="itemsTable2" class="table table-custom table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>SI No.</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>City</th>
                                            <th>Product</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Amount</th>
                                            <th class="text-end">VAT Sales</th>
                                            <th class="text-end">Total SRP</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody2"></tbody>
                                </table>
                            </div>
                            <div class="p-3 border-top bg-light">
                                <div id="itemsPagination2" class="d-flex justify-content-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Item Details Modal -->
        <div class="modal fade" id="itemDetailsModal" tabindex="-1" aria-labelledby="itemDetailsLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="itemDetailsLabel">Item Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="itemDetailsBody"></div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Details Modal -->
        <div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="clientDetailsLabel">Client Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="clientDetailsBody"></div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $base; ?>/assets/js/charts/apexcharts.js"></script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/paidunpaiditems.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    $loginUrl = $base . '/login.php';
    echo '<script> window.location.href = "'. $loginUrl .'"; </script>';
  }
?>

<script>
    function searchTable() {
        // Declare variables
        var input, filter, table, tr, td, i, j, txtValue;
        input = document.getElementById("tableSearch");
        filter = input.value.toUpperCase();
        table = document.getElementById("itemsTable");
        tr = table.getElementsByTagName("tr");

        // Loop through all table rows, and hide those who don't match the search query
        for (i = 1; i < tr.length; i++) { // Start from 1 to skip the table header
            tr[i].style.display = "none"; // Initially hide all rows
            td = tr[i].getElementsByTagName("td");
            for (j = 0; j < td.length; j++) {
                if (td[j]) {
                    txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = ""; // Show the row if match is found
                        break; // Stop searching this row as we already found a match
                    }
                }
            }
        }
    }
</script>