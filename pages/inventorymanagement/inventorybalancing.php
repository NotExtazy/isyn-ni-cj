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
            
            .form-control:read-only {
                background-color: #f8f9fa; /* Lighter background for better readability */
                opacity: 1;
                border-color: var(--border-color);
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

            /* Specific Page Styles */
            .Negative {
                color: var(--danger-color) !important;
                font-weight: 700;
            }
            
            .section-label {
                font-size: 0.8rem;
                font-weight: 700;
                color: var(--text-secondary);
                text-transform: uppercase;
                margin-bottom: 0.5rem;
                display: block;
            }
            
            .balance-card {
                background: #fff;
                border: 1px solid var(--border-color);
                border-radius: 12px;
                padding: 1.5rem;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .balance-value {
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--text-main);
                margin-bottom: 0.25rem;
            }
            
            .balance-label {
                font-size: 0.85rem;
                color: var(--text-secondary);
                font-weight: 500;
            }
        </style>

        <div class="container-fluid main-container">
            <!-- Page Header -->
            <div class="card-modern">
                <div class="card-body-modern py-3">
                    <div class="page-header-container m-0">
                        <div>
                            <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Inventory Balancing</h1>
                            <p class="breadcrumb-modern m-0">Reconcile inventory records and track discrepancies</p>
                        </div>
                        <div>
                            <button type="button" id="recomputeButton" class="btn btn-primary px-4">
                                <i class="fa-solid fa-arrows-rotate"></i> Re-compute
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Top Row: Inventory IN -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-arrow-right-to-bracket text-success"></i> Inventory In / Additions</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-4">
                                <!-- Last Month Inventory End -->
                                <div class="col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <span class="section-label">Last Month Inventory End</span>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Qty</label>
                                                <input type="text" id="lastInvEndTotalQty" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total DP</label>
                                                <input type="text" id="lastInvEndTotalDP" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inventory Incoming -->
                                <div class="col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <span class="section-label">Inventory Incoming</span>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Qty</label>
                                                <input type="text" id="InvInTotalQty" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total DP</label>
                                                <input type="text" id="InvInTotalDP" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfer Product (Received) -->
                                <div class="col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <span class="section-label">Transfer Product (Received)</span>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Qty</label>
                                                <input type="text" id="transferProductReceivedTotalQty" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total DP</label>
                                                <input type="text" id="transferProductReceivedTotalDP" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Row: Inventory OUT -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-arrow-right-from-bracket text-danger"></i> Inventory Out / Deductions</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-4">
                                <!-- Inventory Outgoing -->
                                <div class="col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <span class="section-label">Inventory Outgoing</span>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Qty</label>
                                                <input type="text" id="InvOutTotalQty" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total DP</label>
                                                <input type="text" id="InvOutTotalDP" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfer Product (Transfer) -->
                                <div class="col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <span class="section-label">Transfer Product (Transfer)</span>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Qty</label>
                                                <input type="text" id="transferProductTransferTotalQty" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total DP</label>
                                                <input type="text" id="transferProductTransferTotalDP" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Purchase Returned -->
                                <div class="col-lg-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <span class="section-label">Purchase Returned</span>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total Qty</label>
                                                <input type="text" id="PurchaseReturnedTotalQty" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-secondary" style="font-size: 0.75rem;">Total DP</label>
                                                <input type="text" id="PurchaseReturnedTotalDP" class="form-control fw-bold bg-white" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Reconciliation -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-scale-balanced text-primary"></i> Reconciliation & Difference</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-4">
                                <!-- End of the Month -->
                                <div class="col-lg-4">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <h6 class="text-primary fw-bold mb-3">END OF THE MONTH (CALCULATED)</h6>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary mb-1">Total Qty</label>
                                            <input type="text" id="EndOfMonthTotalQty" class="form-control fw-bold bg-white form-control-lg border-0 shadow-sm" readonly>
                                        </div>
                                        <div>
                                            <label class="form-label text-secondary mb-1">Total DP</label>
                                            <input type="text" id="EndOfMonthTotalDP" class="form-control fw-bold bg-white form-control-lg border-0 shadow-sm" readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Inventory -->
                                <div class="col-lg-4">
                                    <div class="p-4 bg-light rounded-3 border h-100">
                                        <h6 class="text-primary fw-bold mb-3">CURRENT INVENTORY (ACTUAL)</h6>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary mb-1">Total Qty</label>
                                            <input type="text" id="CurrentInvTotalQty" class="form-control fw-bold bg-white form-control-lg border-0 shadow-sm" readonly>
                                        </div>
                                        <div>
                                            <label class="form-label text-secondary mb-1">Total DP</label>
                                            <input type="text" id="CurrentInvTotalDP" class="form-control fw-bold bg-white form-control-lg border-0 shadow-sm" readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Difference -->
                                <div class="col-lg-4">
                                    <div class="p-4 rounded-3 border h-100" style="background-color: #fff8f8; border-color: #ffcccc !important;">
                                        <h6 class="text-danger fw-bold mb-3" id="diffLbl">DIFFERENCE</h6>
                                        <div class="mb-3">
                                            <label class="form-label text-secondary mb-1">Total Qty</label>
                                            <input type="text" id="DifferenceTotalQty" class="form-control fw-bold bg-white form-control-lg border-0 shadow-sm text-danger" readonly>
                                        </div>
                                        <div>
                                            <label class="form-label text-secondary mb-1">Total DP</label>
                                            <input type="text" id="DifferenceTotalDP" class="form-control fw-bold bg-white form-control-lg border-0 shadow-sm text-danger" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between">
                                        <div>
                                            <label class="form-label mb-0 me-3">CHANGE DEALER PRICE:</label>
                                        </div>
                                        <div style="width: 300px;">
                                            <input type="text" id="ChangeDealerPrice" class="form-control fw-bold bg-white border-0 shadow-sm" readonly>
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
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/inventorybalancing.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    $loginUrl = $base . '/login.php';
    echo '<script> window.location.href = "'. $loginUrl .'"; </script>';
  }
?>