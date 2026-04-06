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
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <style>
            :root {
                --primary-color: #0d6efd;
                --secondary-color: #6c757d;
                --success-color: #198754;
                --info-color: #0dcaf0;
                --warning-color: #ffc107;
                --danger-color: #dc3545;
                --light-bg: #f8f9fa;
                --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                --card-border-radius: 0.75rem;
                --transition-base: all 0.3s ease;
            }

            .header-icon-container {
                background: rgba(13, 110, 253, 0.1);
                color: var(--primary-color);
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: var(--transition-base);
            }

            .header-icon-container:hover {
                background: var(--primary-color);
                color: white;
                transform: rotate(15deg);
            }

            body {
                background-color: #f0f2f5;
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
            }

            .main-content {
                padding-top: 20px;
            }

            .custom-card {
                background: #ffffff;
                border: none;
                border-radius: var(--card-border-radius);
                box-shadow: var(--card-shadow);
                margin-bottom: 1.5rem;
                transition: transform 0.2s ease;
            }

            .page-header-card {
                background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                border-left: 5px solid var(--primary-color);
            }

            .nav-tabs {
                border-bottom: 2px solid #eef2f7;
                gap: 0.5rem;
            }

            .nav-tabs .nav-link {
                border: none;
                color: var(--secondary-color);
                padding: 0.75rem 1.25rem;
                border-radius: 0.5rem;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .nav-tabs .nav-link:hover {
                background-color: #f1f4f8;
                color: var(--primary-color);
            }

            .nav-tabs .nav-link.active {
                background-color: #e7f1ff;
                color: var(--primary-color);
                font-weight: 600;
            }

            .sub-tabs .nav-link {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }

            .table {
                border-collapse: separate;
                border-spacing: 0;
                width: 100% !important;
            }

            .table thead th {
                background-color: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                color: #495057;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.025em;
                padding: 1rem;
            }

            .table tbody td {
                padding: 1rem;
                vertical-align: middle;
                border-bottom: 1px solid #f1f4f8;
                color: #444;
            }

            .table tbody tr:hover {
                background-color: #f8fbff;
            }

            .btn {
                border-radius: 0.5rem;
                padding: 0.5rem 1rem;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                transition: all 0.2s;
            }

            .btn-primary { background-color: var(--primary-color); border: none; }
            .btn-secondary { background-color: #eef2f7; color: #444; border: none; }
            .btn-secondary:hover { background-color: #e2e7ee; color: #222; }

            .form-label {
                font-weight: 500;
                color: #444;
                margin-bottom: 0.5rem;
                font-size: 0.875rem;
            }

            .form-select, .form-control {
                border-radius: 0.5rem;
                border: 1px solid #dce1e7;
                padding: 0.6rem 1rem;
            }

            .form-select:focus, .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            }

            /* Analytics specific */
            .card-header {
                background-color: transparent;
                border-bottom: 1px solid #f1f4f8;
                padding: 1.25rem;
            }

            .card-title {
                color: #2d3748;
                font-weight: 600;
            }

            #acash-analytics-list .list-group-item {
                border: none;
                padding: 1rem 1.25rem;
                margin-bottom: 0.5rem;
                border-radius: 0.5rem;
                background-color: #f8f9fa;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: background-color 0.2s;
            }

            #acash-analytics-list .list-group-item:hover {
                background-color: #f1f4f8;
            }

            /* Custom DataTables styling */
            .dataTables_wrapper .dataTables_filter input {
                border-radius: 0.5rem;
                padding: 0.4rem 0.8rem;
                border: 1px solid #dce1e7;
            }

            /* Notification */
            #upload-status-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 250px;
                max-width: 350px;
                border-radius: 12px;
                border: none;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                padding: 1rem;
                margin: 0;
                animation: slideIn 0.3s ease-out;
            }

            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        </style>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

        <!-- Notification Container -->
        <div id="upload-status-notification" class="alert alert-info shadow-sm" role="alert" style="display: none;">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-2" role="status" id="upload-spinner">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <i class="fa-solid fa-check-circle me-2 d-none" id="upload-success-icon"></i>
                <i class="fa-solid fa-circle-exclamation me-2 d-none" id="upload-error-icon"></i>
                <span id="upload-status-text">Uploading file...</span>
            </div>
        </div>

            <div class="container-fluid main-content mb-5">
                <div class="p-4 custom-card page-header-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="h4 mb-1 text-primary fw-bold">ACash Module</h2>
                            <p class="text-muted mb-0 small text-uppercase letter-spacing-1">Manage ACash Information and ECpay Transactions</p>
                        </div>
                        <div class="header-icon-container">
                            <i class="fa-solid fa-wallet fa-2x"></i>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="custom-card p-4">
                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs mb-4" id="acashTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="acash-info-tab" data-bs-toggle="tab" data-bs-target="#acash-info" type="button" role="tab" aria-controls="acash-info" aria-selected="true">
                                        <i class="fa-solid fa-circle-info"></i> Acash Information
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="ecpay-transaction-tab" data-bs-toggle="tab" data-bs-target="#ecpay-transaction" type="button" role="tab" aria-controls="ecpay-transaction" aria-selected="false">
                                        <i class="fa-solid fa-receipt"></i> ECpay Transaction
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab" aria-controls="maintenance" aria-selected="false">
                                        <i class="fa-solid fa-gears"></i> Maintenance
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Tab Content -->
                            <div class="tab-content" id="acashTabsContent">
                                <!-- Acash Information Tab -->
                                <div class="tab-pane fade show active" id="acash-info" role="tabpanel" aria-labelledby="acash-info-tab">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <h3 class="h5 mb-0 fw-bold text-dark">Acash Analytics & Data</h3>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-secondary" onclick="PrintAcashInfo()">
                                                <i class="fa-solid fa-print"></i> Print
                                            </button>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload File
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Filter By Branch/Category</label>
                                            <select id="acash-analytics-filter" class="form-select form-select-sm">
                                                <option value="">Loading Filters...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                             <label class="form-label">Analytics Metric</label>
                                             <select id="acash-analytics-metric" class="form-select">
                                                 <option value="all">All (Amount & Count)</option>
                                                 <option value="amount">Total Amount</option>
                                                 <option value="count">Transaction Count</option>
                                             </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button class="btn btn-primary w-100" onclick="LoadSpecificAnalytics('acash', '#acash-analytics-chart', '#acash-analytics-list')">
                                                <i class="fa-solid fa-filter"></i> Apply Analytics Filter
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-4 mb-4">
                                        <div class="col-lg-9">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h4 class="card-title h6 mb-0">Transaction Trends</h4>
                                                    <div class="d-flex gap-2">
                                                        <select id="acash-chart-year" class="form-select form-select-sm" style="width: auto;" onchange="LoadSpecificAnalytics('acash', '#acash-analytics-chart', '#acash-analytics-list')">
                                                            <option value="">Year</option>
                                                            <option value="2023">2023</option>
                                                            <option value="2024">2024</option>
                                                            <option value="2025">2025</option>
                                                            <option value="2026">2026</option>
                                                        </select>
                                                        <select id="acash-chart-month" class="form-select form-select-sm" style="width: auto;" onchange="LoadSpecificAnalytics('acash', '#acash-analytics-chart', '#acash-analytics-list')">
                                                            <option value="">Month</option>
                                                            <option value="1">Jan</option>
                                                            <option value="2">Feb</option>
                                                            <option value="3">Mar</option>
                                                            <option value="4">Apr</option>
                                                            <option value="5">May</option>
                                                            <option value="6">Jun</option>
                                                            <option value="7">Jul</option>
                                                            <option value="8">Aug</option>
                                                            <option value="9">Sep</option>
                                                            <option value="10">Oct</option>
                                                            <option value="11">Nov</option>
                                                            <option value="12">Dec</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="acash-analytics-chart" style="min-height: 350px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-header">
                                                    <h6 class="card-title h6 mb-0">Top Users</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <ul id="acash-analytics-list" class="list-group list-group-flush">
                                                        <li class="list-group-item text-center text-muted small">Loading analytics...</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Year-Based Analytics -->
                                    <div class="mt-5">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <h4 class="h5 mb-0 fw-bold text-dark">Historical Comparison</h4>
                                            <div class="d-flex gap-2 align-items-center">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <small class="text-muted">Years:</small>
                                                    <select id="acash-year1-select" class="form-select form-select-sm" style="width: 100px;">
                                                        <option value="">Year 1</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2024">2024</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2019">2019</option>
                                                        <option value="2018">2018</option>
                                                    </select>
                                                    <select id="acash-year2-select" class="form-select form-select-sm" style="width: 100px;">
                                                        <option value="">Year 2</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2024">2024</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2019">2019</option>
                                                        <option value="2018">2018</option>
                                                    </select>
                                                    <select id="acash-year3-select" class="form-select form-select-sm" style="width: 100px;">
                                                        <option value="">Year 3</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2024">2024</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2019">2019</option>
                                                        <option value="2018">2018</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <small class="text-muted">Months:</small>
                                                    <select id="acash-year-comparison-months" class="form-select form-select-sm" multiple style="width: 200px;">
                                                        <option value="">All Months</option>
                                                        <option value="1">Jan</option>
                                                        <option value="2">Feb</option>
                                                        <option value="3">Mar</option>
                                                        <option value="4">Apr</option>
                                                        <option value="5">May</option>
                                                        <option value="6">Jun</option>
                                                        <option value="7">Jul</option>
                                                        <option value="8">Aug</option>
                                                        <option value="9">Sep</option>
                                                        <option value="10">Oct</option>
                                                        <option value="11">Nov</option>
                                                        <option value="12">Dec</option>
                                                    </select>
                                                </div>
                                                <button class="btn btn-primary btn-sm" onclick="LoadYearComparison('acash')">
                                                    <i class="fa-solid fa-chart-line"></i> Update
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Line Graph Display -->
                                        <div class="card border-0 shadow-sm mb-4">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Transaction Trends</h5>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="acash-historical-chart" style="min-height: 300px;"></canvas>
                                            </div>
                                        </div>

                                        <!-- Comparison Table -->
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Yearly Comparison</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover" id="acash-comparison-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 20%; text-align: center; background-color: #f8f9fa;">Year</th>
                                                                <th style="width: 25%; text-align: center; background-color: #e3f2fd;">Commission</th>
                                                                <th style="width: 25%; text-align: center; background-color: #bbdefb;">Amount of Transaction</th>
                                                                <th style="width: 30%; text-align: center; background-color: #cfe2ff;">No. of Transaction</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="acash-comparison-tbody">
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">
                                                                    <em>Select years and months to generate comparison data</em>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <ul class="nav nav-tabs sub-tabs mb-4" id="acashSubTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="acash-main-tab" data-bs-toggle="tab" data-bs-target="#acash-main" type="button" role="tab">Main View</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="acash-custom-tab" data-bs-toggle="tab" data-bs-target="#acash-custom" type="button" role="tab">Custom Filter</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="acash-raw-tab" data-bs-toggle="tab" data-bs-target="#acash-raw" type="button" role="tab">Raw Data</button>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="acashSubTabsContent">
                                            <div class="tab-pane fade show active" id="acash-main" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table id="AcashInfoTbl" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Fund Source</th>
                                                                <th>Email</th>
                                                                <th>Account Name</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="AcashInfoList"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="acash-custom" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table id="AcashInfoTblCustom" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Fund Source</th>
                                                                <th>Account No</th>
                                                                <th>Account Title</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="AcashInfoListCustom"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="acash-raw" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table id="AcashInfoTblRaw" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Fund Source</th>
                                                                <th>Email</th>
                                                                <th>Account Name</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="AcashInfoListRaw"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- ECpay Transaction Tab -->
                                <div class="tab-pane fade" id="ecpay-transaction" role="tabpanel">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <h3 class="h5 mb-0 fw-bold text-dark">ECpay Transactions</h3>
                                        <div>
                                            <button class="btn btn-secondary" onclick="PrintEcpayTxn()">
                                                <i class="fa-solid fa-print"></i> Print
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Filter By Branch/Category</label>
                                            <select id="ecpay-analytics-filter" class="form-select form-select-sm">
                                                <option value="">Loading Filters...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                             <label class="form-label">Analytics Metric</label>
                                             <select id="ecpay-analytics-metric" class="form-select">
                                                 <option value="all">All (Amount & Count)</option>
                                                 <option value="amount">Total Amount</option>
                                                 <option value="count">Transaction Count</option>
                                             </select>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button class="btn btn-primary w-100" onclick="LoadSpecificAnalytics('ecpay', '#ecpay-analytics-chart', '#ecpay-analytics-list')">
                                                <i class="fa-solid fa-filter"></i> Apply Analytics Filter
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-4 mb-4">
                                        <div class="col-lg-9">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h4 class="card-title h6 mb-0">Transaction Trends</h4>
                                                    <div class="d-flex gap-2">
                                                        <select id="ecpay-chart-year" class="form-select form-select-sm" style="width: auto;" onchange="LoadSpecificAnalytics('ecpay', '#ecpay-analytics-chart', '#ecpay-analytics-list')">
                                                            <option value="">Year</option>
                                                            <option value="2023">2023</option>
                                                            <option value="2024">2024</option>
                                                            <option value="2025">2025</option>
                                                            <option value="2026">2026</option>
                                                        </select>
                                                        <select id="ecpay-chart-month" class="form-select form-select-sm" style="width: auto;" onchange="LoadSpecificAnalytics('ecpay', '#ecpay-analytics-chart', '#ecpay-analytics-list')">
                                                            <option value="">Month</option>
                                                            <option value="1">Jan</option>
                                                            <option value="2">Feb</option>
                                                            <option value="3">Mar</option>
                                                            <option value="4">Apr</option>
                                                            <option value="5">May</option>
                                                            <option value="6">Jun</option>
                                                            <option value="7">Jul</option>
                                                            <option value="8">Aug</option>
                                                            <option value="9">Sep</option>
                                                            <option value="10">Oct</option>
                                                            <option value="11">Nov</option>
                                                            <option value="12">Dec</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="ecpay-analytics-chart" style="min-height: 350px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="card border-0 shadow-sm h-100">
                                                <div class="card-header">
                                                    <h6 class="card-title h6 mb-0">Top Users</h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <ul id="ecpay-analytics-list" class="list-group list-group-flush">
                                                        <li class="list-group-item text-center text-muted small">Loading analytics...</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Year-Based Analytics -->
                                    <div class="mt-5">
                                        <div class="d-flex align-items-center justify-content-between mb-4">
                                            <h4 class="h5 mb-0 fw-bold text-dark">Historical Comparison</h4>
                                            <div class="d-flex gap-2 align-items-center">
                                                <div class="d-flex gap-2 align-items-center">
                                                    <small class="text-muted">Years:</small>
                                                    <select id="ecpay-year1-select" class="form-select form-select-sm" style="width: 100px;">
                                                        <option value="">Year 1</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2024">2024</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2019">2019</option>
                                                        <option value="2018">2018</option>
                                                    </select>
                                                    <select id="ecpay-year2-select" class="form-select form-select-sm" style="width: 100px;">
                                                        <option value="">Year 2</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2024">2024</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2019">2019</option>
                                                        <option value="2018">2018</option>
                                                    </select>
                                                    <select id="ecpay-year3-select" class="form-select form-select-sm" style="width: 100px;">
                                                        <option value="">Year 3</option>
                                                        <option value="2026">2026</option>
                                                        <option value="2025">2025</option>
                                                        <option value="2024">2024</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2019">2019</option>
                                                        <option value="2018">2018</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <small class="text-muted">Months:</small>
                                                    <select id="ecpay-year-comparison-months" class="form-select form-select-sm" multiple style="width: 200px;">
                                                        <option value="">All Months</option>
                                                        <option value="1">Jan</option>
                                                        <option value="2">Feb</option>
                                                        <option value="3">Mar</option>
                                                        <option value="4">Apr</option>
                                                        <option value="5">May</option>
                                                        <option value="6">Jun</option>
                                                        <option value="7">Jul</option>
                                                        <option value="8">Aug</option>
                                                        <option value="9">Sep</option>
                                                        <option value="10">Oct</option>
                                                        <option value="11">Nov</option>
                                                        <option value="12">Dec</option>
                                                    </select>
                                                </div>
                                                <button class="btn btn-primary btn-sm" onclick="LoadYearComparison('ecpay')">
                                                    <i class="fa-solid fa-chart-line"></i> Update
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Line Graph Display -->
                                        <div class="card border-0 shadow-sm mb-4">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Transaction Trends</h5>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="ecpay-historical-chart" style="min-height: 300px;"></canvas>
                                            </div>
                                        </div>

                                        <!-- Comparison Table -->
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-light">
                                                <h5 class="card-title mb-0">Yearly Comparison</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover" id="ecpay-comparison-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 20%; text-align: center; background-color: #f8f9fa;">Year</th>
                                                                <th style="width: 25%; text-align: center; background-color: #e3f2fd;">Commission</th>
                                                                <th style="width: 25%; text-align: center; background-color: #bbdefb;">Amount of Transaction</th>
                                                                <th style="width: 30%; text-align: center; background-color: #cfe2ff;">No. of Transaction</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="ecpay-comparison-tbody">
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">
                                                                    <em>Select years and months to generate comparison data</em>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <ul class="nav nav-tabs sub-tabs mb-4" id="ecpaySubTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="ecpay-main-tab" data-bs-toggle="tab" data-bs-target="#ecpay-main" type="button" role="tab">Main View</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="ecpay-custom-tab" data-bs-toggle="tab" data-bs-target="#ecpay-custom" type="button" role="tab">Custom Data</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="ecpay-raw-tab" data-bs-toggle="tab" data-bs-target="#ecpay-raw" type="button" role="tab">Raw Data</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="ecpay-ecpay-tab" data-bs-toggle="tab" data-bs-target="#ecpay-ecpay" type="button" role="tab">ECPAY</button>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="ecpaySubTabsContent">
                                            <div class="tab-pane fade show active" id="ecpay-main" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table id="EcpayTxnTbl" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Account No</th>
                                                                <th>Account Name</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="EcpayTxnList"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="ecpay-custom" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table id="EcpayTxnTblCustom" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Account No</th>
                                                                <th>Account Name</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="EcpayTxnListCustom"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="ecpay-raw" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table id="EcpayTxnTblRaw" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Account No</th>
                                                                <th>Account Name</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="EcpayTxnListRaw"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="ecpay-ecpay" role="tabpanel">
                                                <div class="p-3 bg-light rounded-3 mb-4">
                                                    <div class="row g-3 align-items-end">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Category Filter</label>
                                                            <select id="ecpay-category-filter" class="form-select">
                                                                <option value="">Select Category...</option>
                                                                <option value="LOADS">Loads</option>
                                                                <option value="PAYBILLS">Paybills</option>
                                                                <option value="SERVICES">Services</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table id="EcpayTxnTblEcpay" class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Branch</th>
                                                                <th>Account No</th>
                                                                <th>Account Name</th>
                                                                <th>Amount</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="EcpayTxnListEcpay"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Maintenance Tab -->
                                <div class="tab-pane fade" id="maintenance" role="tabpanel">
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <h3 class="h5 mb-0 fw-bold text-dark">Module Maintenance</h3>
                                        <div>
                                            <button class="btn btn-warning me-2" onclick="allowZeroAmountModal(); return false;">
                                                <i class="fa-solid fa-exclamation-triangle me-1"></i>ACash Entries Without Amount
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-4 bg-light rounded-3 mb-4">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label class="form-label">Search Email</label>
                                                <select id="email-search-dropdown" class="form-select" style="width: 100%;">
                                                    <option value="">Select Email...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Owner Name</label>
                                                <input type="text" id="owner-name-input" class="form-control" placeholder="Optional name">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Search Branch</label>
                                                <select id="branch-search-dropdown" class="form-select" style="width: 100%;">
                                                    <option value="">Select Branch...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-primary w-100" id="btn-add-maintenance">
                                                    <i class="fa-solid fa-plus"></i> Add Entry
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="maintenance-table">
                                            <thead>
                                                <tr>
                                                    <th>Account No</th>
                                                    <th>Full Name</th>
                                                    <th>Branch</th>
                                                </tr>
                                            </thead>
                                            <tbody id="maintenance-list"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/select2/js/select2.min.js"></script>
        <!-- Upload Modal -->
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="uploadModalLabel">Upload File</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="upload_category" class="form-label">Category</label>
                            <select class="form-select" id="upload_category">
                                <option value="Custom">Custom</option>
                                <option value="Raw">Raw</option>
                                <option value="LOADS">Loads</option>
                                <option value="PAYBILLS">Paybills</option>
                                <option value="SERVICES">Services</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="upload_file" class="form-label">Choose File</label>
                            <input class="form-control" type="file" id="upload_file" accept=".csv, .xlsx">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="btn-upload-file">Upload</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="zeroAmountModal" tabindex="-1" aria-labelledby="zeroAmountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="zeroAmountModalLabel">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>ACash Entries Without Amount
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fa-solid fa-info-circle me-2"></i>
                            <div>
                                <strong>Instructions:</strong> Enter the missing amounts for each entry below. Amount fields accept only numbers and will be formatted to 2 decimal places automatically.
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="ZeroAmountTbl" class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Branch</th>
                                        <th>Fund</th>
                                        <th>Account No</th>
                                        <th>Particulars</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="zero-amount-list"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="fa-solid fa-lightbulb me-1"></i>
                                Only entries with amounts > 0 will be saved
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                    <i class="fa-solid fa-times me-1"></i>Close
                                </button>
                                <button type="button" class="btn btn-success" onclick="SubmitZeroAmounts()">
                                    <i class="fa-solid fa-save me-1"></i>Save Amounts
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="../../js/profiling/acashinfo.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>
