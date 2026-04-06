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
                :root {
                    --primary-color: #0d6efd;
                    --bg-color: #f4f6f9;
                    --card-bg: #ffffff;
                    --text-color: #333;
                    --text-muted: #6c757d;
                    --border-color: #e9ecef;
                    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
                    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
                    --radius-md: 10px;
                    --radius-lg: 15px;
                }

                body {
                    background-color: var(--bg-color);
                    color: var(--text-color);
                    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                }

                /* Card Styling */
                .custom-card {
                    background-color: var(--card-bg);
                    border-radius: var(--radius-lg);
                    box-shadow: var(--shadow-sm);
                    border: 1px solid rgba(0,0,0,0.02);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                }

                .custom-card:hover {
                    box-shadow: var(--shadow-md);
                }

                .card-header-title {
                    color: var(--primary-color);
                    font-weight: 600;
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 0;
                }

                .section-divider {
                    margin: 1.5rem 0;
                    border-top: 1px solid var(--border-color);
                    opacity: 0.5;
                }

                /* Form Elements */
                .form-label, .col-form-label {
                    font-weight: 600;
                    font-size: 0.95rem;
                    color: var(--text-color);
                }

                .form-control, .form-select {
                    border-radius: var(--radius-md);
                    border: 1px solid #dee2e6;
                    padding: 0.7rem 1rem;
                    font-size: 1rem;
                    color: var(--text-color) !important;
                    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                }

                .form-control:focus, .form-select:focus {
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
                }

                .form-control:disabled, .form-select:disabled {
                    background-color: #f8f9fa;
                    cursor: not-allowed;
                    border-color: #e9ecef;
                    color: #6c757d !important;
                }

                /* Radio Buttons */
                .form-check-input {
                    width: 1.2em;
                    height: 1.2em;
                    margin-top: 0.2em;
                    cursor: pointer;
                    border-color: #dee2e6;
                }

                .form-check-input:checked {
                    background-color: var(--primary-color);
                    border-color: var(--primary-color);
                }

                .form-check-label {
                    cursor: pointer;
                    font-weight: 500;
                    color: var(--text-color);
                    margin-left: 0.5rem;
                }
                
                .radio-group-container {
                    background-color: #f8f9fa;
                    padding: 10px 15px;
                    border-radius: var(--radius-md);
                    border: 1px solid var(--border-color);
                    display: inline-block;
                    margin-bottom: 1rem;
                }

                /* Buttons */
                .btn-primary {
                    background-color: var(--primary-color);
                    border-color: var(--primary-color);
                    padding: 0.7rem 2rem;
                    font-weight: 500;
                    border-radius: var(--radius-md);
                    transition: all 0.2s;
                    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
                }

                .btn-primary:hover {
                    background-color: #0b5ed7;
                    border-color: #0a58ca;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
                }

                /* Select2 Customization */
                .select2-container--default .select2-selection--single {
                    border: 1px solid #dee2e6 !important;
                    border-radius: var(--radius-md) !important;
                    height: 48px !important;
                    padding: 8px 0;
                }
                
                .select2-container--default .select2-selection--single .select2-selection__rendered {
                    color: var(--text-color) !important;
                    line-height: 30px !important;
                    padding-left: 1rem !important;
                }
                
                .select2-container--default .select2-selection--single .select2-selection__arrow {
                    height: 46px !important;
                    right: 10px !important;
                }
                
                .select2-dropdown {
                    border: 1px solid #dee2e6 !important;
                    border-radius: var(--radius-md) !important;
                    box-shadow: var(--shadow-md);
                }
            </style>

            <div class="container-fluid mt-4">
                <div class="custom-card p-3 mb-4">
                    <div class="card-header-title">
                        <i class="fa-solid fa-chart-line fs-4"></i> <span class="fs-5">Purchases & Sales Report</span>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="custom-card p-4">
                            <form action="POST" class="h-100 d-flex flex-column">
                                <h5 class="fw-bold mb-4 text-center text-dark">Generate Report</h5>
                                
                                <div class="mb-4">
                                    <label class="form-label" for="purchaseSelect">Select Journal Type</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-book text-muted"></i></span>
                                        <select id="purchaseSelect" name="purchaseSelect" class="form-select border-start-0 ps-2">
                                            <option value="PJ">Purchase Journal</option>
                                            <option value="SJ">Sales Journal</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="section-divider"></div>

                                <!-- Date Range Section -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="Yes" name="option" id="asof" onclick="AsOf('FromTo');">
                                            <label class="form-check-label fw-bold" for="asof">
                                                Date Range Report
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-3 ps-4" style="border-left: 3px solid #e9ecef; margin-left: 5px;">
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted" for="fromAsOf">From Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-regular fa-calendar text-muted"></i></span>
                                                <input type="text" id="fromAsOf" name="fromAsOf" class="form-control" placeholder="Select start date" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small text-muted" for="toAsOf">To Date</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-regular fa-calendar text-muted"></i></span>
                                                <input type="text" id="toAsOf" name="toAsOf" class="form-control" placeholder="Select end date" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="section-divider"></div>

                                <!-- Monthly Section -->
                                <div class="mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" value="Yes" name="option" id="asofMonthly" onclick="AsOf('Monthly');">
                                            <label class="form-check-label fw-bold" for="asofMonthly">
                                                Monthly Report
                                            </label>
                                        </div>
                                    </div>

                                    <div class="row ps-4" style="border-left: 3px solid #e9ecef; margin-left: 5px;">
                                        <div class="col-md-12">
                                            <label class="form-label small text-muted" for="month">Select Month</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="fa-regular fa-calendar-days text-muted"></i></span>
                                                <input type="text" id="month" name="month" class="form-control" placeholder="Select month/year" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-2">
                                    <button class="btn btn-primary w-100 py-3" type="button" id="searchBtn" name="searchBtn" onclick="Search();">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i> Generate Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        
        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/inventorymanagement/purchaseandsales.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>