<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    
    // Calculate base path outside of if block so it's available in both branches
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $pagesPos = strpos($scriptDir, '/pages');
    if ($pagesPos !== false) {
        $base = substr($scriptDir, 0, $pagesPos);
    } else {
        $base = rtrim($scriptDir, '/\\');
    }
    if ($base === '.' ) { $base = ''; }
    
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once(dirname(__DIR__, 2) . '/includes/permissions.php');
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: " . $base . "/dashboard");
            exit;
        }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include(dirname(__DIR__, 2) . '/includes/pages.header.php');
    ?>
    <!-- Additional CSS for modern design -->
    <link rel="stylesheet" href="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.css">
    <link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">
    <!-- Add Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <body class="">
        <style>
            :root {
                --primary-color: #435ebe;
                --secondary-color: #6c757d;
                --success-color: #198754;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
                --info-color: #0dcaf0;
                --background-color: #f2f7ff;
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
            
            .main-container {
                padding: 2rem;
            }

            .card-modern {
                background: var(--card-bg);
                border: none;
                border-radius: 16px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
                margin-bottom: 1.5rem;
            }
            
            .card-header-modern {
                background: transparent;
                border-bottom: 1px solid var(--border-color);
                padding: 1.25rem 1.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                border-radius: 16px 16px 0 0;
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
                border-color: var(--primary-color);
                box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.25);
                background-color: var(--card-bg);
            }

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

            .btn-primary {
                background-color: var(--primary-color);
                color: white;
                box-shadow: 0 4px 12px rgba(67, 94, 190, 0.3);
            }
            
            .btn-primary:hover {
                background-color: #3a4fb8;
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(67, 94, 190, 0.4);
            }
            
            .btn-success {
                background-color: var(--success-color);
                color: white;
                box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
            }

            .btn-success:hover {
                background-color: #157347;
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(25, 135, 84, 0.4);
            }

            .btn-info {
                background-color: var(--info-color);
                color: #fff;
                box-shadow: 0 4px 12px rgba(13, 202, 240, 0.3);
            }

            .btn-warning {
                background-color: var(--warning-color);
                color: #000;
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            }

            .btn-danger {
                background-color: var(--danger-color);
                color: white;
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            }

            .btn-secondary {
                background-color: var(--secondary-color);
                color: white;
                box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
            }

            .table-responsive-custom {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid var(--border-color);
                max-height: 400px;
                overflow-y: auto;
                overflow-x: auto; /* Allow horizontal scrolling */
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
            }

            .table-custom tbody tr {
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .table-custom tbody tr:hover {
                background-color: rgba(67, 94, 190, 0.05);
            }

            .table-custom tbody tr.selected {
                background-color: rgba(67, 94, 190, 0.1);
            }

            /* DataTables specific styling */
            .dataTables_wrapper {
                font-family: 'Inter', sans-serif;
            }

            .dataTables_wrapper .dataTables_scroll {
                border-radius: 12px;
                overflow: hidden;
            }

            .dataTables_wrapper .dataTables_scrollBody {
                border: 1px solid var(--border-color);
                border-top: none;
            }

            /* Payment table specific styling */
            #paymentTbl_wrapper {
                border-radius: 12px;
                overflow: hidden;
                position: relative;
            }

            .table-container-fixed {
                position: relative;
                border-radius: 12px;
                border: 1px solid #dee2e6;
                overflow: hidden;
                /* Use min-height instead of fixed height */
                min-height: 150px;
                max-height: 400px;
            }

            .table-responsive-custom {
                /* Let the content determine the height, with limits */
                min-height: 100px;
                max-height: 350px;
                overflow-y: auto;
                overflow-x: hidden;
                border: none;
                border-radius: 0;
                margin-bottom: 0;
            }

            #paymentTbl {
                margin-bottom: 0 !important;
                border-radius: 0;
            }

            #paymentTbl thead th {
                background-color: #f8f9fa !important;
                color: var(--text-secondary) !important;
                font-weight: 700 !important;
                text-transform: uppercase !important;
                font-size: 0.75rem !important;
                letter-spacing: 0.5px !important;
                position: sticky;
                top: 0;
                z-index: 10;
                border-bottom: 2px solid #dee2e6;
            }

            #paymentTbl tbody td {
                color: var(--text-main) !important;
                font-size: 0.9rem !important;
                border-bottom: 1px solid #dee2e6;
            }

            /* Fixed footer styling */
            .table-footer-absolute {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 50px; /* Fixed height for footer */
                z-index: 20;
                background-color: #f8f9fa;
                border-top: 2px solid #dee2e6;
                box-shadow: 0 -2px 8px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
            }

            .table-footer-absolute table {
                margin-bottom: 0 !important;
                border: none !important;
                width: 100%;
            }

            .table-footer-absolute tfoot th {
                background-color: transparent !important;
                color: var(--text-secondary) !important;
                font-weight: 700 !important;
                border: none !important;
                border-top: none !important;
                padding: 0.75rem;
                vertical-align: middle;
            }

            /* Webkit scrollbar styling */
            .table-responsive-custom::-webkit-scrollbar {
                width: 8px;
            }

            .table-responsive-custom::-webkit-scrollbar-track {
                background: transparent;
            }

            .table-responsive-custom::-webkit-scrollbar-thumb {
                background-color: #dee2e6;
                border-radius: 4px;
            }

            .table-responsive-custom::-webkit-scrollbar-thumb:hover {
                background-color: #adb5bd;
            }

            /* Remove old sticky footer styles */
            .table-footer-fixed {
                /* This class is no longer used */
            }

            /* Client table styling */
            #transactClientNameTbl {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid var(--border-color);
                min-height: 450px;
                font-size: 0.8rem; /* Reduced from 0.95rem */
                width: 100%;
                table-layout: auto; /* Allow columns to size based on content */
            }

            #transactClientNameTbl thead th {
                background-color: #f8f9fa;
                color: var(--text-secondary);
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.7rem; /* Reduced from 0.75rem */
                padding: 0.6rem 0.8rem; /* Reduced from 1rem */
                border-bottom: 2px solid var(--border-color);
                letter-spacing: 0.5px;
                position: sticky;
                top: 0;
                z-index: 10;
                white-space: nowrap; /* Prevent header text wrapping */
            }

            #transactClientNameTbl tbody td {
                padding: 0.5rem 0.8rem; /* Reduced from 1.2rem */
                vertical-align: middle;
                border-bottom: 1px solid var(--border-color);
                color: var(--text-main);
                font-size: 0.8rem; /* Reduced from 0.95rem */
                line-height: 1.3; /* Reduced from 1.5 for tighter spacing */
            }

            #transactClientNameTbl tbody tr {
                transition: all 0.2s ease;
                cursor: pointer;
            }

            #transactClientNameTbl tbody tr:hover {
                background-color: rgba(67, 94, 190, 0.05);
            }

            #transactClientNameTbl tbody tr.selected {
                background-color: rgba(67, 94, 190, 0.1);
            }
            
            /* Make status columns more visible */
            #transactClientNameTbl th:nth-last-child(-n+3),
            #transactClientNameTbl td:nth-last-child(-n+3) {
                min-width: 100px; /* Reduced from 120px */
                text-align: center;
            }
            
            /* Payment Status column specific styling */
            #transactClientNameTbl td:last-child {
                min-width: 120px; /* Reduced from 140px */
                padding: 0.4rem 0.6rem; /* Reduced from 0.8rem */
            }

            /* Payment table row styling */
            .payment-row {
                transition: all 0.2s ease;
                cursor: pointer !important;
            }

            .payment-row:hover {
                background-color: rgba(67, 94, 190, 0.05) !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .payment-row.selected {
                background-color: rgba(67, 94, 190, 0.15) !important;
                border-left: 4px solid var(--primary-color);
            }

            .payment-row.editing {
                background-color: rgba(40, 167, 69, 0.15) !important;
                border-left: 4px solid #28a745;
                box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
            }

            .clickable-row {
                cursor: pointer !important;
            }

            /* Loan status styling - removed yellow highlighting for outstanding loans */
            .loan-paid {
                /* Keep green highlight for paid loans */
                background-color: rgba(40, 167, 69, 0.05) !important;
            }

            .loan-outstanding {
                /* Remove yellow background - use default/neutral background */
                background-color: transparent !important;
            }

            .loan-paid.selected {
                background-color: rgba(40, 167, 69, 0.15) !important;
                border-left: 4px solid #28a745;
            }

            .loan-outstanding.selected {
                /* Use blue selection instead of yellow for outstanding loans */
                background-color: rgba(67, 94, 190, 0.15) !important;
                border-left: 4px solid var(--primary-color);
            }

            .loan-paid.editing {
                background-color: rgba(40, 167, 69, 0.25) !important;
                border-left: 4px solid #28a745;
                box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
            }

            .loan-outstanding.editing {
                /* Use blue editing highlight instead of yellow for outstanding loans */
                background-color: rgba(67, 94, 190, 0.25) !important;
                border-left: 4px solid var(--primary-color);
                box-shadow: 0 2px 8px rgba(67, 94, 190, 0.3);
            }

            /* Select2 styling */
            .select2-container--default .select2-selection--single {
                background-color: var(--input-bg);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                height: auto;
                padding: 0.6rem 1rem;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                color: var(--text-main);
                font-weight: 500;
                padding: 0;
                line-height: 1.5;
            }

            .select2-dropdown {
                border: 1px solid var(--border-color);
                border-radius: 8px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            }

            .select2-results__option {
                color: var(--text-main);
                padding: 0.75rem 1rem;
            }

            .select2-results__option--highlighted {
                background-color: var(--primary-color);
                color: white;
            }

            /* Modal styling */
            .modal-content {
                border-radius: 16px;
                border: none;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            }

            .modal-header {
                background: linear-gradient(135deg, var(--primary-color) 0%, #3a4fb8 100%);
                color: white;
                border-radius: 16px 16px 0 0;
                border: none;
                padding: 1.5rem;
            }

            .modal-title {
                font-weight: 700;
                font-size: 1.2rem;
            }

            .modal-body {
                padding: 2rem;
                background-color: var(--card-bg);
            }

            .modal-footer {
                background-color: #f8f9fa;
                border-radius: 0 0 16px 16px;
                border: none;
                padding: 1.5rem;
            }

            /* Input group styling */
            .input-group-modern {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .input-group-modern .form-label {
                min-width: 120px;
                margin-bottom: 0;
                font-weight: 600;
            }

            /* Summary cards */
            .summary-card {
                background: linear-gradient(135deg, var(--primary-color) 0%, #3a4fb8 100%);
                color: white;
                border-radius: 12px;
                padding: 1.5rem;
                margin-bottom: 1rem;
            }

            .summary-card h6 {
                font-weight: 700;
                margin-bottom: 0.5rem;
                opacity: 0.9;
            }

            .summary-card .amount {
                font-size: 1.5rem;
                font-weight: 800;
                margin: 0;
            }

            /* Auto-calculation visual feedback */
            .auto-calculated {
                background-color: #d4edda !important;
                border-color: #c3e6cb !important;
                transition: all 0.3s ease;
                box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
            }

            .auto-calculated::after {
                content: "✓ Auto-calculated";
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 0.75rem;
                color: #198754;
                font-weight: 500;
                pointer-events: none;
            }

            /* Modern SweetAlert2 Customization */
            .swal-modern-popup {
                border-radius: 20px !important;
                padding: 0 !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
            }

            .swal-modern-title {
                padding: 1.5rem 1.5rem 0.5rem !important;
                font-size: 1.5rem !important;
                font-weight: 700 !important;
            }

            .swal-modern-html {
                padding: 0 1.5rem 1.5rem !important;
                margin: 0 !important;
            }

            .swal-modern-button {
                border-radius: 10px !important;
                padding: 0.75rem 2rem !important;
                font-size: 1rem !important;
                font-weight: 600 !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
                transition: all 0.3s ease !important;
            }

            .swal-modern-button:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2) !important;
            }

            /* Hide default SweetAlert2 icon when using custom HTML */
            .swal2-icon {
                display: none !important;
            }

            /* Animate.css support for SweetAlert2 */
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translate3d(0, -100%, 0);
                }
                to {
                    opacity: 1;
                    transform: translate3d(0, 0, 0);
                }
            }

            @keyframes fadeOutUp {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                    transform: translate3d(0, -100%, 0);
                }
            }

            .animate__animated {
                animation-duration: 0.3s;
                animation-fill-mode: both;
            }

            .animate__faster {
                animation-duration: 0.2s;
            }

            .animate__fadeInDown {
                animation-name: fadeInDown;
            }

            .animate__fadeOutUp {
                animation-name: fadeOutUp;
            }
        </style>


        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
            include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
        ?>

        <div class="container-fluid main-container">
            <!-- Page Header -->
            <div class="card-modern">
                <div class="card-body-modern py-3">
                    <div class="page-header-container m-0">
                        <div>
                            <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Loans Payment</h1>
                            <p class="breadcrumb-modern m-0">Process loan payments and manage transactions</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Client Selection -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-users text-primary"></i> Client Selection</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="TransactionType" class="form-label">Transaction Type</label>
                                    <select id="TransactionType" name="TransactionType" class="form-select" onchange="LoadTransactClientName(this.value)">
                                        <option value="" selected disabled>Select Transaction Type</option>
                                        <option value="INDIVIDUAL">INDIVIDUAL</option>
                                        <option value="CENTER">CENTER</option>
                                        <option value="GROUP">GROUP</option>
                                        <option value="WRITEOFF">WRITEOFF</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="ClientSearch" class="form-label">Search Client</label>
                                    <input type="text" id="ClientSearch" name="ClientSearch" class="form-control" placeholder="Search clients..." onkeyup="filterClients(this.value)">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button class="btn btn-info" onclick="location.reload()">
                                        <i class="fa-solid fa-refresh"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client List and Primary Details -->
                <div class="row g-4">
                    <div class="col-md-9">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-list text-primary"></i> Client List</h5>
                            </div>
                            <div class="card-body-modern">
                                <div class="table-responsive-custom" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                                    <table id="transactClientNameTbl" class="table table-bordered table-custom">
                                        <thead>
                                            <tr>
                                            </tr>
                                        </thead>
                                        <tbody id="transactClientNameList">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Please select a transaction type to view clients</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-info-circle text-primary"></i> Primary Details</h5>
                            </div>
                            
                            <div class="card-body-modern">
                                <div class="input-group-modern">
                                    <label for="productNamePD" class="form-label">Product</label>
                                    <input type="text" class="form-control" id="productNamePD" disabled>
                                </div>
                                <div class="input-group-modern">
                                    <label for="poPD" class="form-label">PO</label>
                                    <input type="text" class="form-control" id="poPD" disabled>
                                </div>
                                <div class="input-group-modern">
                                    <label for="fundPD" class="form-label">Fund</label>
                                    <input type="text" class="form-control" id="fundPD" disabled>
                                </div>
                                <div class="input-group-modern">
                                    <label for="modePD" class="form-label">Mode</label>
                                    <input type="text" class="form-control" id="modePD" disabled>
                                </div>
                                <div class="input-group-modern">
                                    <label for="dateReleasePD" class="form-label">Date Released</label>
                                    <input type="text" class="form-control" id="dateReleasePD" disabled>
                                </div>
                                
                                <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
                                <h6 class="card-title-modern" style="font-size: 1rem; margin-bottom: 1rem;">Account Details</h6>
                                
                                <div class="input-group-modern">
                                    <label for="loanAD" class="form-label">Loan Amount</label>
                                    <input type="text" class="form-control" id="loanAD" disabled>
                                </div>
                                <div class="input-group-modern">
                                    <label for="balanceAD" class="form-label">Balance</label>
                                    <input type="text" class="form-control" id="balanceAD" disabled>
                                </div>
                                <div class="input-group-modern">
                                    <label for="arrearsAD" class="form-label">Arrears</label>
                                    <input type="text" class="form-control" id="arrearsAD" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Table -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-table text-primary"></i> Payment Details</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="table-container-fixed">
                                <div class="table-responsive-custom">
                                    <table id="paymentTbl" class="table table-bordered table-custom">
                                        <thead>
                                            <tr>
                                                <th width="25%">Name</th>
                                                <th width="12%">Principal</th>
                                                <th width="12%">Interest</th>
                                                <th width="12%">Penalty</th>
                                                <th width="12%">Balance</th>
                                                <th width="12%">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentList">
                                            <!-- Payment rows will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Fixed footer outside the scrollable area -->
                                <div class="table-footer-absolute">
                                    <table class="table table-bordered table-custom mb-0">
                                        <tfoot>
                                            <tr>
                                                <th width="25%">
                                                    <small class="text-muted">
                                                        <i class="fa-solid fa-mouse-pointer me-1"></i>
                                                        Double-click row to edit
                                                    </small>
                                                </th>
                                                <th width="12%" id="totalPrincipal" class="text-end fw-bold text-primary">₱0.00</th>
                                                <th width="12%" id="totalInterest" class="text-end fw-bold text-warning">₱0.00</th>
                                                <th width="12%" id="totalPenalty" class="text-end fw-bold text-danger">₱0.00</th>
                                                <th width="12%" id="totalBalance" class="text-end fw-bold text-info">₱0.00</th>
                                                <th width="12%" id="totalAmount" class="text-end fw-bold text-success">₱0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Edit Amounts and Payment Controls -->
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-edit text-primary"></i> Edit Amounts</h5>
                            </div>
                            <div class="card-body-modern">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="input-group-modern">
                                            <label for="edit-payment" class="form-label">Payment</label>
                                            <input type="text" class="form-control" id="edit-payment" placeholder="0.00" onchange="formatInput(this);" pattern="[0-9]+(\.[0-9]{1,2})?" title="Please enter a valid amount (numbers only, up to 2 decimal places)" disabled>
                                        </div>
                                        <div class="input-group-modern">
                                            <label for="edit-principal" class="form-label">Principal</label>
                                            <input type="text" class="form-control" id="edit-principal" placeholder="0.00" onchange="formatInput(this); RecomputeAmountTotals()" pattern="[0-9]+(\.[0-9]{1,2})?" title="Please enter a valid amount (numbers only, up to 2 decimal places)" disabled>
                                        </div>
                                        <div class="input-group-modern">
                                            <label for="edit-interest" class="form-label">Interest</label>
                                            <input type="text" class="form-control" id="edit-interest" placeholder="0.00" onchange="formatInput(this); RecomputeAmountTotals()" disabled>
                                        </div>
                                        <div class="input-group-modern">
                                            <label for="edit-penalty" class="form-label">Penalty</label>
                                            <input type="text" class="form-control" id="edit-penalty" placeholder="0.00" onchange="formatInput(this); RecomputeAmountTotals()" pattern="[0-9]+(\.[0-9]{1,2})?" title="Please enter a valid amount (numbers only, up to 2 decimal places)" disabled>
                                        </div>
                                        <div class="input-group-modern">
                                            <label for="edit-total" class="form-label">Total</label>
                                            <input type="text" class="form-control" id="edit-total" placeholder="0.00" disabled>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="d-grid gap-2">
                                            <button type="button" id="btnFullPayment" class="btn btn-success" disabled>
                                                <i class="fa-solid fa-circle-check"></i> Full Payment
                                            </button>
                                            <button type="button" id="btnWaivePenaltyEdit" class="btn btn-primary" disabled>
                                                <i class="fa-solid fa-ban"></i> Waive Penalty
                                            </button>
                                            <button type="button" id="btnReset" class="btn btn-warning" disabled>
                                                <i class="fa-solid fa-rotate-right"></i> Reset
                                            </button>
                                        </div>
        
                                        <div class="d-grid gap-2 mt-4">
                                            <button type="button" id="btnDone" class="btn btn-success" disabled title="Click to add payment to the table">
                                                <i class="fa-solid fa-circle-check"></i> Add Payment
                                            </button>
                                            <button type="button" id="btnCancel" class="btn btn-danger" disabled>
                                                <i class="fa-regular fa-circle-xmark"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-credit-card text-primary"></i> Payment Type</h5>
                            </div>
                            <div class="card-body-modern">
                                <div class="input-group-modern">
                                    <label for="paymentType" class="form-label">Payment Type</label>
                                    <select class="form-select" id="paymentType" name="paymentType" onchange="SetPaymentType(this.value);">
                                        <option value="" disabled selected>Select Payment Type</option>
                                        <option value="CASH">Cash</option>
                                        <option value="CHECK">Check</option>
                                    </select>
                                </div>

                                <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
                                <h6 class="card-title-modern" style="font-size: 1rem; margin-bottom: 1rem;">Check Details</h6>

                                <div class="input-group-modern">
                                    <label for="checkdate" class="form-label">Check Date</label>
                                    <input type="date" class="form-control" id="checkdate" name="checkdate" disabled>
                                </div>
                                
                                <div class="input-group-modern">
                                    <label for="checkNo" class="form-label">Check No.</label>
                                    <input type="text" class="form-control" id="checkNo" name="checkNo" disabled>
                                </div>

                                <div class="input-group-modern">
                                    <label for="bankname" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bankname" name="bankname" disabled>
                                </div>

                                <div class="input-group-modern">
                                    <label for="bankbranch" class="form-label">Bank Branch</label>
                                    <input type="text" class="form-control" id="bankbranch" name="bankbranch" disabled>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card-modern">
                            <div class="card-header-modern">
                                <h5 class="card-title-modern"><i class="fa-solid fa-receipt text-primary"></i> OR Number & Bank</h5>
                            </div>
                            <div class="card-body-modern">
                                <div class="input-group-modern">
                                    <label for="orFrom" class="form-label">OR From</label>
                                    <select name="orFrom" id="orFrom" class="form-select" onchange="LoadORSeries(this.value);">
                                        <option selected>Select OR Series</option>
                                    </select>
                                </div>
                                
                                <div class="input-group-modern">
                                    <label for="ORNo" class="form-label">OR No.</label>
                                    <input type="text" id="ORNo" class="form-control" name="ORNo" disabled>
                                </div>

                                <div class="input-group-modern">
                                    <label for="ORLeft" class="form-label">ORs Left</label>
                                    <input type="text" id="ORLeft" class="form-control" name="ORLeft" disabled>
                                </div>

                                <hr style="border-color: var(--border-color); margin: 1.5rem 0;">
                                <h6 class="card-title-modern" style="font-size: 1rem; margin-bottom: 1rem;">Depository Bank</h6>

                                <div class="input-group-modern">
                                    <label for="depositoryBank" class="form-label">Bank</label>
                                    <select name="depositoryBank" id="depositoryBank" class="form-select">
                                        <option value="">Select Bank</option>
                                    </select>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="button" class="btn btn-primary w-100 p-3" onclick="SetOR();" style="font-size: 1.1rem; font-weight: 700;">
                                        <i class="fa-solid fa-floppy-disk"></i> Save Transaction
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Other Details Modal -->
            <div class="modal fade" id="otherDetailsMDL" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="otherDetails" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">
                                <i class="fas fa-info-circle me-2"></i>Transaction Details
                            </h1>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="input-group-modern">
                                        <label for="clientType" class="form-label">Client Type</label>
                                        <select name="clientType" id="clientType" class="form-select" onchange="GetClientName(this.value);">
                                            <option value="" disabled selected>Select Client Type</option>
                                        </select>
                                    </div>
                                    
                                    <div class="input-group-modern">
                                        <label for="clientName" class="form-label">Client Name</label>
                                        <div id="clientNameSelDiv">
                                            <select name="clientName" id="clientName" class="form-select" onchange="GetClientInfo(this.value);">
                                                <option value="" disabled selected>Select Client</option>
                                            </select>
                                        </div>
                                        <div id="clientNameTxtDiv" style="display:none;">
                                            <input type="text" name="clientNameTxt" id="clientNameTxt" class="form-control" placeholder="Enter client name">
                                        </div>
                                    </div>
                                    
                                    <div class="input-group-modern">
                                        <label for="clientAddress" class="form-label">Address</label>
                                        <input type="text" name="clientAddress" id="clientAddress" class="form-control" placeholder="Enter client address" disabled>
                                    </div>
                                    
                                    <div class="input-group-modern">
                                        <label for="clientTIN" class="form-label">TIN</label>
                                        <input type="text" name="clientTIN" id="clientTIN" class="form-control" placeholder="Enter TIN" disabled>
                                    </div>
                                    
                                    <div class="input-group-modern">
                                        <label for="particulars" class="form-label">Particulars</label>
                                        <textarea name="particulars" id="particulars" class="form-control" rows="4" placeholder="Enter transaction particulars"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button class="btn btn-primary px-4 py-2" type="button" id="proceedSaveTransact" name="proceedSaveTransact" onclick="SaveTransaction();">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Save Transaction
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <!-- Local scripts with absolute paths to avoid path resolution issues -->
        <script src="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
        <script>
            var baseUrl = "/iSynApp-main";
            
            // Function to filter clients in the table
            function filterClients(searchTerm) {
                const table = document.getElementById('transactClientNameTbl');
                const tbody = document.getElementById('transactClientNameList');
                
                if (!table || !tbody) {
                    return;
                }
                
                const rows = tbody.getElementsByTagName('tr');
                searchTerm = searchTerm.toLowerCase().trim();
                
                // If search is empty, show all rows
                if (searchTerm === '') {
                    for (let i = 0; i < rows.length; i++) {
                        rows[i].style.display = '';
                    }
                    return;
                }
                
                // Filter rows based on search term
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    // Search through all cells in the row
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent || cells[j].innerText;
                        if (cellText.toLowerCase().includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    
                    // Show or hide row based on search result
                    row.style.display = found ? '' : 'none';
                }
            }
            
            // Clear search when transaction type changes
            function LoadTransactClientName(transactionType) {
                // Clear search input
                const searchInput = document.getElementById('ClientSearch');
                if (searchInput) {
                    searchInput.value = '';
                }
                
                // Call the original function from loanspayment.js
                if (typeof window.originalLoadTransactClientName === 'function') {
                    window.originalLoadTransactClientName(transactionType);
                } else {
                    // Fallback to the function in loanspayment.js
                    console.log('Loading clients for transaction type:', transactionType);
                }
            }
        </script>
        <script src="/iSynApp-main/js/cashier/loanspayment.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "' . $base . '/login"; </script>';
  }
?>