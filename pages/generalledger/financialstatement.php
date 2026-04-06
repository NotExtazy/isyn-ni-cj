<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        $permissionsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/permissions.php';
        require_once($permissionsPath);
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: ../../dashboard");
            exit;
        }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
        include($headerPath);
    ?>
      <link rel="stylesheet" href="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">
      <style>
        .statement-header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #3a7ca5;
        }
        .statement-header h3 { 
            margin: 5px 0; 
            color: #3a7ca5; 
            font-size: 22px; 
            font-weight: 400;
        }
        .statement-header h4 { 
            margin: 8px 0; 
            color: #666; 
            font-size: 18px; 
            font-weight: 400;
        }
        .statement-header p { 
            margin: 3px 0; 
            color: #888; 
            font-size: 14px; 
        }
        #statementContent table { 
            font-size: 14px; 
            margin-bottom: 0;
        }
        #statementContent .table-primary th { 
            background-color: #3a7ca5 !important; 
            color: white !important;
            font-weight: 400; 
            padding: 12px 15px;
            border: none;
        }
        #statementContent .table-success td { 
            background-color: #d4edda; 
            font-weight: 400;
            font-size: 16px;
            padding: 15px;
        }
        #statementContent .table-info td { 
            background-color: #d1ecf1; 
            font-weight: 400;
            padding: 12px 15px;
        }
        #statementContent .table-light td {
            background-color: #f8f9fa;
        }
        #statementContent tbody tr:hover {
            background-color: #f5f5f5;
        }
        #statementContent td {
            padding: 8px 15px;
            vertical-align: middle;
        }
      </style>

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
            $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
            include($sidebarPath);
            include($navbarPath);
        ?>

            <div class="container-fluid" style="max-width:98%;padding-left:15px;padding-right:15px;">
                <div class="p-3 shadow-sm rounded-2 mb-4" style="background:white;border-left:4px solid #3a7ca5;">
                    <p class="fs-5 mb-0" style="color:#3a7ca5;font-weight:700;"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Financial Statement</p>
                </div>
                
                <div class="row g-3">
                    <!-- Left Side: Filters -->
                    <div class="col-md-3">
                        <!-- Combined Filters Card -->
                        <div class="p-3 shadow-sm rounded-2" style="background-color: white;">
                            <p class="fs-6" style="color: #3a7ca5;">Filters & Configuration</p>
                            <hr style="height: 1px; background: #e0e0e0;">
                            
                            <!-- Date Filter Section -->
                            <div class="mb-4">
                                <label class="form-label">Date Filter</label>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="dateFilter" id="radioMonthRange" value="monthrange">
                                    <label class="form-check-label" for="radioMonthRange">Date Range</label>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small">From <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="fromMonth" disabled>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">To</label>
                                        <input type="date" class="form-control form-control-sm" id="toMonth" disabled>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="dateFilter" id="radioYear" value="year" checked>
                                    <label class="form-check-label" for="radioYear">Fiscal Year</label>
                                </div>
                                <select class="form-select form-select-sm mb-3" id="yearSelect">
                                    <option value="">Select Year</option>
                                </select>
                            </div>
                            
                            <hr style="height: 1px; background: #e0e0e0;">
                            
                            <!-- Fund Selection -->
                            <div class="mb-3">
                                <label for="fundSelect" class="form-label">Fund / Tag</label>
                                <select class="form-select form-select-sm" id="fundSelect">
                                    <option value="">All Funds</option>
                                </select>
                            </div>
                            
                            <hr style="height: 1px; background: #e0e0e0;">
                            
                            <!-- Report Type -->
                            <div class="mb-3">
                                <label class="form-label">Report Type</label>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="reportType" id="rptIncome" value="income">
                                    <label class="form-check-label" for="rptIncome">
                                        Income Statement
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="reportType" id="rptBalance" value="balance">
                                    <label class="form-check-label" for="rptBalance">
                                        Balance Sheet
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="reportType" id="rptCashFlow" value="cashflow">
                                    <label class="form-check-label" for="rptCashFlow">
                                        Cash Flow Statement
                                    </label>
                                </div>
                            </div>
                            
                            <hr style="height: 1px; background: #e0e0e0;">
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" id="btnRetrieve" type="button">
                                    <i class="fa-solid fa-repeat me-1"></i> Retrieve
                                </button>
                                <button class="btn btn-success text-white" id="btnPrint" type="button">
                                    <i class="fa-solid fa-print me-1"></i> Print
                                </button>
                                <button class="btn btn-warning text-white" id="btnClear" type="button">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Side: Generated Report -->
                    <div class="col-md-9">
                        <div class="p-3 shadow-sm rounded-2" style="background-color: white; max-height: 85vh; overflow-y: auto;">
                            <div id="resultContainer">
                                <div class="text-center text-muted py-5">
                                    <i class="fa-solid fa-file-invoice fa-3x mb-3"></i>
                                    <p class="fs-5">No Report Generated</p>
                                    <p>Select filters and click "Retrieve" to generate a financial statement</p>
                                </div>
                                <div id="statementContent" style="display:none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            $footerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
            include($footerPath);
        ?>

        <script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
        <script src="/iSynApp-main/js/generalledger/financialstatement.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>