<?php
    define('IS_FRONT_CONTROLLER', true);
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Calculate base path
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $pagesPos = strpos($scriptDir, '/pages');
    if ($pagesPos !== false) {
        $base = substr($scriptDir, 0, $pagesPos);
    } else {
        $base = rtrim($scriptDir, '/\\');
    }
    if ($base === '.' ) { $base = ''; }
    
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('includes/index.header.php');
    ?>
    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <!-- Datetimepicker CSS -->
        <link rel="stylesheet" href="assets/datetimepicker/jquery.datetimepicker.css">
        <!-- Add Google Font -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <style>
            :root {
                --primary-color: #3a57e8;
                --secondary-color: #6c757d;
                /*--success-color: #198754;*/
                --info-color: #0dcaf0;
                --warning-color: #ffc107;
                /*--danger-color: #dc3545;*/
                --light-color: #f8f9fa;
                --dark-color: #212529;
                --border-radius: 16px;
                --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: #f2f7ff;
            }

            .content-inner span {
                font-size: 11px;
            }
            .icon-xxl {
                font-size: 2.2rem; /* Reduced from 3rem */
            }
            .price-xxl {
                font-size: 1.8rem; /* Reduced from 2.5rem */
                margin-bottom: 0;
                font-weight: 800;
                line-height: 1;
            }
            
            /* Overview Card Styling - consistent size */
            .overview-card .icon-xxl {
                font-size: 2.2rem !important; 
            }
            .overview-card .price-xxl {
                font-size: 1.8rem !important; 
                font-weight: 700;
            }
            .overview-card .circle-progress {
                width: 100px !important; 
                height: 100px !important;
            }
            .overview-card h6.text-secondary {
                font-size: 1rem !important; 
                font-weight: 600;
                margin-bottom: 1.5rem !important;
            }
            
            /* Spacing adjustments for the grid */
            .overview-item {
                margin-bottom: 2rem;
            }
            
            /* Card Modernization */
            .card {
                border: none;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                background: #fff;
                margin-bottom: 1.5rem;
                /* Use !important to override AOS animation duration for snappy hover */
                transition: transform 0.1s ease !important;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
            }
            
            /* Header Styling */
            .card-header {
                background: transparent;
                border-bottom: none;  
                padding: 1rem; /* Reduced padding */
            }
            
            .header-title .card-title {
                font-size: 1.1rem; /* Reduced font size */
                font-weight: 700;
                margin-bottom: 0;
                color: #2c3e50;
            }
            
            /* Progress Widget */
            .progress-widget {
                padding: 1rem;
                display: flex;
                flex-direction: column;
                justify-content: center; 
                align-items: center; 
                height: 100%; 
                min-height: 100px; /* Further Reduced */
                width: 100%;
            }
            
            .circle-progress {
                margin: 0 auto 0.5rem; /* Reduced margin */
                width: 80px; /* Reduced from 100px */
                height: 80px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .progress-detail {
                width: 100%;
                text-align: center;
                margin-top: 0.5rem;
            }

            .card-body {
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center; /* Ensure body content is centered */
                padding-left: 0;
                padding-right: 0;
            }
            

            
            .input-group-text {
                background-color: var(--primary-color) !important;
                border: none;
                font-weight: 600;
            }
            
            .form-select {
                border: none;
                font-weight: 500;
                background-color: #f8f9fa;
            }
            
            /* Chart Container */
            .d-main {
                min-height: 200px;
                width: 100%;
                margin-left: 0;
                margin-right: 0;
            }

            /* Zoom Hover Effect for Stat Cards (3-column grid) */
           
        </style>

        <?php
            include('includes/index.sidebar.php');
            include('includes/index.navbar.php');
        ?>

            <div class="container-fluid content-inner mt-n5 py-0">
                <div class="row">
                    <div class="col-md-12 col-lg-12">
                        <div class="row">
                            <!-- Top Row: Ratio (Left) & Receivables/Payables (Right) -->
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="card" data-aos="fade-up" data-aos-delay="800"  style="height: 90%;">
                                            <div class="flex-wrap card-header d-flex align-items-center" style="position: relative;">
                                                <div class="d-flex align-items-center" style="flex: 0 0 auto; z-index: 1; width: 200px; flex-shrink: 0;">
                                                    <div class="input-group input-group-sm" style="max-width: 95px; flex: 1;">
                                                        <span class="input-group-text bg-primary text-white" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;">Y1</span>
                                                        <select class="form-select" id="dashboard-year" style="font-size: 0.75rem; padding: 0.2rem;">
                                                            <?php
                                                                $currentYear = date('Y');
                                                                // Show 20 years back from current year and 10 years forward
                                                                for($i = $currentYear + 10; $i >= $currentYear - 20; $i--){
                                                                    $selected = ($i == $currentYear) ? 'selected' : '';
                                                                    echo "<option value='$i' $selected>$i</option>";
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <div class="input-group input-group-sm" style="max-width: 95px; flex: 1; margin-left: 3px;">
                                                        <span class="input-group-text bg-secondary text-white" style="padding: 0.2rem 0.4rem; font-size: 0.7rem;">Y2</span>
                                                        <select class="form-select" id="dashboard-year-compare" style="font-size: 0.75rem; padding: 0.2rem;">
                                                            <option value="" selected>None</option>
                                                            <?php
                                                                // Show 20 years back from current year and 10 years forward
                                                                for($i = $currentYear + 10; $i >= $currentYear - 20; $i--){
                                                                    echo "<option value='$i'>$i</option>";
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="header-title" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); white-space: nowrap; z-index: 2;">
                                                    <h4 class="card-title" id="title-liquidity" style="margin: 0;">Statement of Financial Condition </h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="d-main" class="d-main" style="min-height: 300px;"></div>
                                                
                                                <!-- Financial Balance Sheet Data Table -->
                                                <div class="mt-4" style="padding-left: 15px; padding-right: 15px;">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered text-center" id="financial-balance-sheet-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Year</th>
                                                                    <th style="color: #000;">Assets</th>
                                                                    <th style="color: #000;">Liabilities</th>
                                                                    <th style="color: #000;">Equity</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="fw-bold text-primary" id="year1-label" >Year 1 January</td>
                                                                    <td class="text-center" id="year1-assets" style="color: #3a57e8; font-weight: 450;">₱0.00</td>
                                                                    <td class="text-center" id="year1-liability" style="color: #3a57e8; font-weight: 450;">₱0.00</td>
                                                                    <td class="text-center" id="year1-equity" style="color: #3a57e8; font-weight: 450;">₱0.00</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-bold" id="year2-label" style="color: #06b6d4;">Year 2 January</td>
                                                                    <td class="text-center" id="year2-assets" style="color: #06b6d4; font-weight: 450;">₱0.00</td>
                                                                    <td class="text-center" id="year2-liability" style="color: #06b6d4; font-weight: 450;">₱0.00</td>
                                                                    <td class="text-center" id="year2-equity" style="color: #06b6d4; font-weight: 450;">₱0.00</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card" data-aos="fade-up" data-aos-delay="800" style="height: 90%;">
                                            <div class="flex-wrap card-header d-flex align-items-center">
                                                <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                                    <h4 class="card-title" id="title-arap" style="margin: 0;">Receivables & Payables</h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="d-arap" class="d-main" style="min-height: 200px;"></div>
                                                
                                                <!-- Receivables & Payables Data Table -->
                                                <div class="mt-2" style="padding-left: 15px; padding-right: 15px;">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered text-center table-sm" id="receivables-payables-table" style="font-size: 0.85rem; padding-left: 15px; padding-right: 15px;">
                                                            <thead>
                                                                <tr>
                                                                    <th style="padding: 0.5rem;">Year</th>
                                                                    <th style="color: #000;" >Receivables</th>
                                                                    <th style="color: #000;" >Payables</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="fw-bold text-primary" id="receivables-year1-label" style="padding: 0.4rem;">Year 1 January</td>
                                                                    <td class="text-center" id="year1-receivables" style="color: #3a57e8; font-weight: 100; padding: 0.4rem;">₱0.00</td>
                                                                    <td class="text-center" id="year1-payables" style="color: #3a57e8; font-weight: 100; padding: 0.4rem;">₱0.00</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-bold" id="receivables-year2-label" style="padding: 0.4rem; color: #06b6d4;">Year 2 January</td>
                                                                    <td class="text-center" id="year2-receivables" style="color: #06b6d4; font-weight: 100; padding: 0.4rem;">₱0.00</td>
                                                                    <td class="text-center" id="year2-payables" style="color: #06b6d4; font-weight: 100; padding: 0.4rem;">₱0.00</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Income Statement Chart (Full Width) -->
                            <div class="col-md-12">
                                <div class="card" data-aos="fade-up" data-aos-delay="800">
                                    <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                        <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                            <h4 class="card-title" id="title-income-statement" style="margin: 0;">STATEMENT OF OPERATION</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="d-income-statement" class="d-main" style="min-height: 250px;"></div>
                                        
                                        <!-- Income Statement Data Table -->
                                        <div class="mt-4" style="padding-left: 15px; padding-right: 15px;">
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center" id="income-statement-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Year</th>
                                                            <th style="color: #000;" > Revenue</th>
                                                            <th style="color: #000;" >Expenses</th>
                                                            <th style="color: #000;" >Net Profit Before Tax</th>
                                                            <th style="color: #000;" >Provision For Income Tax</th>
                                                            <th style="color: #000;" >Net Income After Tax</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-bold text-primary" id="income-statement-year1-label">Year 1 January</td>
                                                            <td class="text-center" id="revenue-year1" style="font-weight: 600; color: #3a57e8;">₱0.00</td>
                                                            <td class="text-center" id="expenses-year1" style="font-weight: 600; color: #3a57e8;">₱0.00</td>
                                                            <td class="text-center" id="income-before-tax-year1" style="font-weight: 600; color: #3a57e8;">₱0.00</td>
                                                            <td class="text-center" id="provision-year1" style="font-weight: 600; color: #3a57e8;">₱0.00</td>
                                                            <td class="text-center text-success" id="income-after-tax-year1" style="font-weight: 600; color: #3a57e8;">₱0.00</td>
                                                        </tr>
                                                        <tr id="income-statement-year2-row" style="display:none;">
                                                            <td class="fw-bold" id="income-statement-year2-label" style="color: #06b6d4;">Year 2 January</td>
                                                            <td class="text-center" id="revenue-year2" style="font-weight: 600; color: #06b6d4;">₱0.00</td>
                                                            <td class="text-center" id="expenses-year2" style="font-weight: 600; color: #06b6d4;">₱0.00</td>
                                                            <td class="text-center" id="income-before-tax-year2" style="font-weight: 600; color: #06b6d4;">₱0.00</td>
                                                            <td class="text-center" id="provision-year2" style="font-weight: 600; color: #06b6d4;">₱0.00</td>
                                                            <td class="text-center text-success" id="income-after-tax-year2" style="font-weight: 600; color: #06b6d4;">₱0.00</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Row: Income Breakdown (Full Width) -->
                            <div class="col-md-12">
                                <div class="card" data-aos="fade-up" data-aos-delay="800">
                                    <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                        <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                            <h4 class="card-title" id="title-income-breakdown" style="margin: 0;">INCOME BREAKDOWN</h4>
                                            </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="d-income-breakdown" class="d-main" style="min-height: 250px;"></div>
                                        
                                        <!-- Income Breakdown Data Table -->
                                        <div class="mt-4" style="padding-left: 15px; padding-right: 15px;">
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center" id="income-breakdown-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Year</th>
                                                            <th style="color: #000;" >Merchandise</th>
                                                            <th style="color: #000;" >Service</th>
                                                            <th style="color: #000;" >Other</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-bold text-primary" id="income-breakdown-year1-label">Year 1 January</td>
                                                            <td class="text-center" id="year1-merchandise" style="color: #3a57e8; font-weight: 300;">₱0.00</td>
                                                            <td class="text-center" id="year1-service" style="color: #3a57e8; font-weight: 300;">₱0.00</td>
                                                            <td class="text-center" id="year1-other" style="color: #3a57e8; font-weight: 300;">₱0.00</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="fw-bold" id="income-breakdown-year2-label" style="color: #06b6d4;">Year 2 January</td>
                                                            <td class="text-center" id="year2-merchandise" style="color: #06b6d4; font-weight: 300;">₱0.00</td>
                                                            <td class="text-center" id="year2-service" style="color: #06b6d4; font-weight: 300;">₱0.00</td>
                                                            <td class="text-center" id="year2-other" style="color: #06b6d4; font-weight: 300;">₱0.00</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Third Row: Net Income Trend (Full Width) -->
                            <div class="col-md-12">
                                <div class="card" data-aos="fade-up" data-aos-delay="800">
                                    <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                        <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                            <h4 class="card-title" id="title-net-income" style="margin: 0;">Net Income Trend</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="d-net-income" class="d-main" style="min-height: 250px;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Inventory Value Chart (Full Width) -->
                            <div class="col-md-12">
                                <div class="card" data-aos="fade-up" data-aos-delay="800">
                                    <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                        <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                            <h4 class="card-title" id="title-inventory" style="margin: 0;">INVENTORY VALUE</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="d-inventory" class="d-main" style="min-height: 300px;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Paid/Unpaid Items Chart (Full Width) -->
                            <div class="col-md-12">
                                <div class="card" data-aos="fade-up" data-aos-delay="800">
                                    <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                        <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                            <h4 class="card-title" id="title-paid-unpaid" style="margin: 0;">PAID & UNPAID ITEMS</h4>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="d-paid-unpaid" class="d-main" style="min-height: 250px;"></div>
                                        
                                        <!-- Paid/Unpaid Summary Data Table -->
                                        <div class="mt-4" style="padding-left: 15px; padding-right: 15px;">
                                            <div class="table-responsive">
                                                <table class="table table-bordered text-center" id="paid-unpaid-summary-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Year</th>
                                                            <th style="color: #000;" >Total Paid</th>
                                                            <th style="color: #000;" >Total Unpaid</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="fw-bold text-primary" id="summary-year1-label">Year 1 January</td>
                                                            <td class="text-center" id="total-paid-year1" style="font-weight: 600; color: #3a57e8;" >₱0.00</td>
                                                            <td class="text-center" id="total-unpaid-year1" style="font-weight: 600; color: #3a57e8;" >₱0.00</td>
                                                        </tr>
                                                        <tr id="paid-unpaid-year2-row" style="display:none;">
                                                            <td class="fw-bold" id="summary-year2-label" style="color: #06b6d4;">Year 2 January</td>
                                                            <td class="text-center" id="total-paid-year2" style="color: #06b6d4;" style="font-weight: 600;">₱0.00</td>
                                                            <td class="text-center" id="total-unpaid-year2" style="color: #06b6d4;" style="font-weight: 600;">₱0.00</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Eighth Row: Clients & Top 5 Sales -->
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card" data-aos="fade-up" data-aos-delay="800">
                                            <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                                <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                                    <h4 class="card-title" id="title-top-sales" style="margin: 0;">TOP 5 SALES PRODUCTS</h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="d-top-sales" class="d-main" style="min-height: 350px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card" data-aos="fade-up" data-aos-delay="800">
                                            <div class="flex-wrap card-header d-flex justify-content-between align-items-center">
                                                <div class="header-title" style="text-align: center; flex: 1; display: flex; justify-content: center; align-items: center;">
                                                    <h4 class="card-title" style="margin: 0;">CUSTOMERS</h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="d-client-type" class="d-main" style="min-height: 350px;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                                   
                                   
                                    <!-- Today -->
                                    <!-- Moved to Side of Budget -->

                                    <!-- Members -->
                                    <!-- Moved to Side of Budget -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
        <?php
            include('includes/index.footer.php');
        ?>
        
        <!-- JavaScript for Dashboard Charts -->
        <script src="assets/js/charts/dashboard.js"></script>
        <!-- Try CDN first, fallback to local copy -->
        <script>
            // Try to load ApexCharts from CDN first, fallback to local copy
            var script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js';
            script.crossOrigin = 'anonymous';
            script.onerror = function() {
                // CDN failed, load local copy
                var localScript = document.createElement('script');
                localScript.src = 'assets/js/charts/apexcharts.js';
                document.head.appendChild(localScript);
            };
            document.head.appendChild(script);
        </script>
    
  </body>
</html>

<?php
  } else {
    echo '<script> window.location.href = "' . $base . '/login.php"; </script>';
}
?>
