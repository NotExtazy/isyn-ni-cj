<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC - use absolute path
        $permissionsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/permissions.php';
        require_once($permissionsPath);
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: /iSynApp-main/dashboard");
            exit;
        }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        // Override BASE_PATH for router compatibility
        $BASE_PATH = '/iSynApp-main';
        $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
        include($headerPath);
    ?>
    <!-- Additional CSS for loan transaction -->
    <link rel="stylesheet" href="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.css">
    <link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">

    <body class="  ">
        <!-- Add Google Font -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
            
            .btn-success {
                background-color: var(--success-color);
                color: white;
                box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
            }

            .btn-info {
                background-color: var(--info-color);
                color: #fff;
                box-shadow: 0 4px 12px rgba(13, 202, 240, 0.3);
            }

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
            }

            /* All loan rows should be clickable */
            .table-custom tbody tr[data-loan-id] {
                cursor: pointer;
            }
            
            .table-custom tbody tr[data-loan-id]:hover {
                background-color: rgba(67, 94, 190, 0.1);
            }

            .loan-not-renewable {
                opacity: 0.8;
            }

            /* Select2 Custom Styling */
            .select2-container--bootstrap-5 .select2-selection--single {
                background-color: var(--input-bg) !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                padding: 0.6rem 1rem !important;
                font-size: 0.95rem !important;
                font-weight: 500 !important;
                color: var(--text-main) !important;
                height: auto !important;
                min-height: 38px !important;
            }

            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
                color: var(--text-main) !important;
                padding: 0 !important;
                line-height: 1.5 !important;
            }

            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
                color: var(--text-secondary) !important;
            }

            .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
                height: 36px !important;
                right: 10px !important;
            }

            .select2-container--bootstrap-5.select2-container--focus .select2-selection--single {
                border-color: var(--primary-color) !important;
                box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.25) !important;
            }

            .select2-dropdown {
                border: 1px solid var(--border-color) !important;
                border-radius: 8px !important;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1) !important;
                max-height: 300px !important;
                overflow: hidden !important;
            }

            .select2-dropdown-scroll {
                max-height: 300px !important;
            }

            .select2-results {
                max-height: 250px !important;
                overflow-y: auto !important;
            }

            .select2-results__options {
                max-height: 250px !important;
                overflow-y: auto !important;
            }

            /* Custom scrollbar for Select2 dropdown */
            .select2-results__options::-webkit-scrollbar {
                width: 8px;
            }

            .select2-results__options::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }

            .select2-results__options::-webkit-scrollbar-thumb {
                background: var(--primary-color);
                border-radius: 4px;
            }

            .select2-results__options::-webkit-scrollbar-thumb:hover {
                background: #364a8b;
            }

            .select2-container--bootstrap-5 .select2-results__option {
                padding: 0.75rem 1rem !important;
                font-size: 0.95rem !important;
                color: var(--text-main) !important;
            }

            .select2-container--bootstrap-5 .select2-results__option--highlighted {
                background-color: var(--primary-color) !important;
                color: white !important;
            }

            .select2-search--dropdown .select2-search__field {
                background-color: var(--input-bg) !important;
                border: 1px solid var(--border-color) !important;
                border-radius: 6px !important;
                padding: 0.5rem !important;
                font-size: 0.9rem !important;
                color: var(--text-main) !important;
            }

            .select2-search--dropdown .select2-search__field:focus {
                border-color: var(--primary-color) !important;
                outline: none !important;
                box-shadow: 0 0 0 0.2rem rgba(67, 94, 190, 0.25) !important;
            }

            /* Mobile responsive adjustments */
            @media (max-width: 768px) {
                .select2-dropdown {
                    max-height: 250px !important;
                }
                
                .select2-results {
                    max-height: 200px !important;
                }
                
                .select2-results__options {
                    max-height: 200px !important;
                }
            }
        </style>

        <?php
            // Override BASE_PATH for router compatibility
            $BASE_PATH = '/iSynApp-main';
            $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
            $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
            include($sidebarPath);
            include($navbarPath);
        ?>

        <div class="container-fluid main-container">
            <!-- Page Header -->
            <div class="card-modern">
                <div class="card-body-modern py-3">
                    <div class="page-header-container m-0">
                        <div>
                            <h1 class="page-heading mb-1" style="font-size: 1.5rem;">Loan Transaction</h1>
                            <p class="breadcrumb-modern m-0">Manage loan applications and renewals</p>
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
                                <div class="col-md-8">
                                    <label for="clientSelect" class="form-label">Select Client</label>
                                    <select class="form-select" id="clientSelect" name="clientSelect">
                                        <option value="" disabled selected>Loading clients...</option>
                                    </select>
                                    <small class="text-muted">
                                        <span class="badge bg-danger" style="font-size: 0.7rem;"><i class="fas fa-exclamation-circle"></i> Active</span> Has pending loans | 
                                        <span class="badge bg-success" style="font-size: 0.7rem;"><i class="fas fa-check-circle"></i> Paid</span> All loans paid
                                    </small>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-success me-2" id="addNew" type="button" disabled>
                                        <i class="fa-solid fa-plus"></i> New Loan
                                    </button>
                                    <button class="btn btn-info" onclick="location.reload()">
                                        <i class="fa-solid fa-refresh"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Table -->
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-table text-primary"></i> Client Loans</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="table-responsive-custom">
                                <table class="table-custom">
                                    <thead>
                                        <tr>
                                            <th>Loan ID</th>
                                            <th>Client Name</th>
                                            <th>Program</th>
                                            <th>Product</th>
                                            <th>Product Details</th>
                                            <th>Date Released</th>
                                            <th>Term</th>
                                            <th>Total Amount Due</th>
                                            <th>Interest</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Renewable</th>
                                        </tr>
                                    </thead>
                                    <tbody id="loanTableBody">
                                        <tr>
                                            <td colspan="12" class="text-center text-muted">Please select a client to view loans</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Messages -->
                <div class="col-12">
                    <div id="statusMessages"></div>
                </div>
            </div>
        </div>

        <!-- Eligibility Error Modal -->
        <div class="modal fade" id="eligibilityErrorModal" tabindex="-1" aria-labelledby="eligibilityErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);">
                    <div class="modal-header" style="background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%); color: white; border-radius: 16px 16px 0 0; border: none; padding: 1.5rem;">
                        <h5 class="modal-title" id="eligibilityErrorModalLabel" style="font-weight: 700; font-size: 1.2rem;">
                            <i class="fas fa-exclamation-triangle me-2"></i>Loan Application Blocked
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 2rem; background-color: var(--card-bg);">
                        <div class="text-center mb-3">
                            <i class="fas fa-ban text-danger" style="font-size: 3rem; opacity: 0.7;"></i>
                        </div>
                        <div class="text-center">
                            <h6 class="fw-bold text-danger mb-3" id="eligibilityClientName">Client Name</h6>
                            <p class="text-muted mb-3" id="eligibilityMessage">Cannot apply for a new loan due to pending obligations.</p>
                            <div class="alert alert-danger" role="alert" style="border-radius: 12px; border: none; background-color: rgba(220, 53, 69, 0.1);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Total Outstanding Balance:</span>
                                    <span class="fw-bold text-danger" id="eligibilityAmount" style="font-size: 1.2rem;">₱0.00</span>
                                </div>
                            </div>
                            <p class="small text-muted mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                All existing loans must be fully paid before applying for a new loan.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer" style="background-color: #f8f9fa; border-radius: 0 0 16px 16px; border: none; padding: 1.5rem; display: flex; justify-content: center;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SIMPLE NEW LOAN MODAL - NO COMPLEX JS -->
        <div class="modal fade" id="newLoanModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" style="border-radius: 16px;">
                    <div class="modal-header" style="background: var(--primary-color); color: white;">
                        <h5 class="modal-title">New Loan Application</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="newLoanClientId" name="clientId" value="">
                        
                        <div class="row g-4">
                            <div class="col-md-8">
                                <form id="newLoanForm">
                                    <div class="mb-3">
                                        <label class="form-label">Client Name</label>
                                        <input type="text" class="form-control" id="newLoanClientNameDisplay" readonly>
                                        <input type="hidden" id="newLoanClientId" name="clientId" value="">
                                        <small class="text-muted">The client who will receive this loan</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Loaned Product</label>
                                        <select class="form-select" id="newLoanProductDropdown" required>
                                            <option value="">Select a loaned product...</option>
                                        </select>
                                        <small class="text-muted">Select the product that was loaned to this client</small>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Product</label>
                                                <select class="form-select" id="newLoanProduct" name="add_product" required onchange="computeAmortization()">
                                                    <option value="">Loading products...</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Mode</label>
                                                <select class="form-select" id="newLoanMode" name="add_mode" required onchange="computeAmortization()">
                                                    <option value="">Loading modes...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Term (Months)</label>
                                                <select class="form-select" id="newLoanTerm" name="add_termRate" required onchange="computeAmortization()">
                                                    <option value="">SELECT TERM</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                    <option value="6">6</option>
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                    <option value="9">9</option>
                                                    <option value="10">10</option>
                                                    <option value="11">11</option>
                                                    <option value="12">12</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Interest Rate</label>
                                                <input type="text" class="form-control" id="newLoanRate" name="add_rate" value="3%" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Loan Amount</label>
                                        <input type="number" class="form-control" id="newLoanAmount" name="add_amount" step="0.01" required readonly oninput="computeAmortization(); computeAddInterest(); computeAddPrincipal(this.value);">
                                        <small class="text-muted">Auto-filled from product price</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Staff</label>
                                        <select class="form-select" id="newLoanStaff" name="add_poFco" required>
                                            <option value="">Loading staff...</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Hidden fields for computation -->
                                    <input type="hidden" id="principalAddValue" name="principalAddValue" value="0.00">
                                    <input type="hidden" id="add_downpaymentAmount" name="add_downpaymentAmount" value="0">
                                    <input type="hidden" id="newLoanProductPrice" value="0">
                                    <input type="hidden" id="newLoanInventorySI" value="">
                                </form>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card-modern" style="background-color: var(--input-bg); margin-bottom: 0;">
                                    <div class="card-header-modern" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color);">
                                        <h6 class="card-title-modern" style="font-size: 1rem; margin: 0; color: var(--primary-color);">
                                            <i class="fas fa-calculator text-primary"></i>
                                            Summary
                                        </h6>
                                    </div>
                                    <div class="card-body-modern" style="padding: 1rem;">
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Principal:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="principalAddValueDisplay" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Interest:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="interestAddValue" value="0.00">
                                            </div>
                                        </div>

                                        <hr style="border-color: var(--border-color); margin: 0.5rem 0;">
                                        <div class="row mb-3">
                                            <label class="col-5 col-form-label-sm fw-bold text-primary">Total:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end fw-bold text-primary" id="totalAddValue" value="0.00">
                                            </div>
                                        </div>
                                        
                                        <h6 class="card-title-modern" style="font-size: 0.9rem; margin: 0; color: var(--warning-color);">
                                            <i class="fas fa-calendar-alt text-warning"></i>
                                            Amortization
                                        </h6>
                                        <hr style="border-color: var(--border-color); margin: 0.5rem 0;">
                                        
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Principal:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="principalAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Interest:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="interestAmortAddValue" value="0.00">
                                            </div>
                                        </div>

                                        <hr style="border-color: var(--border-color); margin: 0.5rem 0;">
                                        <div class="row">
                                            <label class="col-5 col-form-label-sm fw-bold text-warning">Total:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end fw-bold text-warning" id="totalAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitLoan()">Submit</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- RENEWAL LOAN MODAL -->
        <div class="modal fade" id="renewalLoanModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" style="border-radius: 16px;">
                    <div class="modal-header" style="background: var(--success-color); color: white;">
                        <h5 class="modal-title"><i class="fas fa-sync-alt me-2"></i>Loan Renewal Application</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="renewalClientId" name="clientId" value="">
                        <input type="hidden" id="renewalLoanId" name="loanId" value="">
                        <input type="hidden" id="renewalInventorySI" value="">
                        
                        <!-- Client Info Display -->
                        <div class="alert alert-info mb-4">
                            <h6><i class="fas fa-user me-2"></i>Renewing loan for: <span id="renewalClientName" class="fw-bold"></span></h6>
                            <small>Previous loan ID: <span id="renewalPreviousLoanId" class="fw-bold"></span></small>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-8">
                                <form id="renewalLoanForm">
                                    <div class="mb-3">
                                        <label class="form-label">Client Name</label>
                                        <input type="text" class="form-control" id="renewalClientNameField" readonly>
                                        <small class="text-muted">The client who will receive this renewal loan</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Loaned Product</label>
                                        <select class="form-select" id="renewalProductDropdown" required>
                                            <option value="">Select a loaned product...</option>
                                        </select>
                                        <small class="text-muted">Select the product that was loaned to this client</small>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Product</label>
                                                <select class="form-select" id="renewalProduct" name="product" required onchange="calculateRenewalSummary()">
                                                    <option value="">Loading products...</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Mode</label>
                                                <select class="form-select" id="renewalMode" name="mode" required onchange="calculateRenewalSummary()">
                                                    <option value="">Loading modes...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Term (Months)</label>
                                                <select class="form-select" id="renewalTermRate" name="termRate" required onchange="calculateRenewalSummary()">
                                                    <option value="">SELECT TERM</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                    <option value="5">5</option>
                                                    <option value="6">6</option>
                                                    <option value="7">7</option>
                                                    <option value="8">8</option>
                                                    <option value="9">9</option>
                                                    <option value="10">10</option>
                                                    <option value="11">11</option>
                                                    <option value="12">12</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Interest Rate</label>
                                                <input type="text" class="form-control" id="renewalRate" name="rate" value="3%" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Loan Amount</label>
                                        <input type="number" class="form-control" id="renewalAmount" name="amount" step="0.01" required readonly oninput="calculateRenewalSummary();">
                                        <small class="text-muted">Auto-filled from product price</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Staff</label>
                                        <select class="form-select" id="renewalPoFco" name="poFco" required>
                                            <option value="">Loading staff...</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Hidden fields -->
                                    <input type="hidden" id="renewalLoanType" name="loanType" value="RENEWAL">
                                    <input type="hidden" id="renewalTag" name="tag" value="-">
                                    <input type="hidden" id="renewalProgram" name="program" value="Microfinance">
                                    <input type="hidden" id="renewalAvailment" name="availment" value="1">
                                    <input type="hidden" id="renewalDownPaymentAmount" name="downPaymentAmount" value="0">
                                    <input type="hidden" id="renewalProductPrice" value="0">
                                </form>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card-modern" style="background-color: var(--input-bg); margin-bottom: 0;">
                                    <div class="card-header-modern" style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color);">
                                        <h6 class="card-title-modern" style="font-size: 1rem; margin: 0; color: var(--success-color);">
                                            <i class="fas fa-calculator text-success"></i>
                                            Summary
                                        </h6>
                                    </div>
                                    <div class="card-body-modern" style="padding: 1rem;">
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Principal:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="renewalSummaryPrincipal" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Interest:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="renewalSummaryInterest" value="0.00">
                                            </div>
                                        </div>

                                        <hr style="border-color: var(--border-color); margin: 0.5rem 0;">
                                        <div class="row mb-3">
                                            <label class="col-5 col-form-label-sm fw-bold text-success">Total:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end fw-bold text-success" id="renewalSummaryTotal" value="0.00">
                                            </div>
                                        </div>
                                        
                                        <h6 class="card-title-modern" style="font-size: 0.9rem; margin: 0; color: var(--warning-color);">
                                            <i class="fas fa-calendar-alt text-warning"></i>
                                            Amortization
                                        </h6>
                                        <hr style="border-color: var(--border-color); margin: 0.5rem 0;">
                                        
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Principal:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="renewalAmortizationPrincipal" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row mb-2">
                                            <label class="col-5 col-form-label-sm fw-bold">Interest:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end" id="renewalAmortizationInterest" value="0.00">
                                            </div>
                                        </div>

                                        <hr style="border-color: var(--border-color); margin: 0.5rem 0;">
                                        <div class="row">
                                            <label class="col-5 col-form-label-sm fw-bold text-warning">Total:</label>
                                            <div class="col-7">
                                                <input type="text" readonly class="form-control-plaintext form-control-sm text-end fw-bold text-warning" id="renewalAmortizationTotal" value="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="submitRenewal()">Submit Renewal</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
            // Include system's JavaScript libraries
            $jsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
            include($jsPath);
        ?>

        <!-- Select2 JavaScript for searchable dropdown -->
        <script src="/iSynApp-main/assets/select2/js/select2.min.js"></script>

        <!-- Loan Success Modal -->
        <div class="modal fade" id="loanSuccessModal" tabindex="-1" aria-labelledby="loanSuccessModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);">
                    <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 16px 16px 0 0; border: none; padding: 1.5rem;">
                        <h5 class="modal-title" id="loanSuccessModalLabel" style="font-weight: 700; font-size: 1.2rem;">
                            <i class="fas fa-check-circle me-2"></i>Loan Application Successful
                        </h5>
                    </div>
                    <div class="modal-body" style="padding: 2rem;">
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle" style="font-size: 4rem; color: #28a745; margin-bottom: 1rem;"></i>
                            </div>
                            <h4 class="text-success mb-3" style="font-weight: 600;">Application Submitted Successfully!</h4>
                            <div class="alert alert-success" style="border-radius: 12px; border: none; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                                <div class="row align-items-center">
                                    <div class="col-3">
                                        <i class="fas fa-file-contract" style="font-size: 2rem; color: #155724;"></i>
                                    </div>
                                    <div class="col-9 text-start">
                                        <strong style="color: #155724;">Loan ID:</strong><br>
                                        <span id="successLoanId" style="font-family: 'Courier New', monospace; font-size: 1.1rem; font-weight: 600; color: #155724;">LOAN-20260311-00001</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border: none; padding: 1rem 2rem 2rem;">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" style="border-radius: 8px; padding: 0.75rem 2rem; font-weight: 600;">
                            <i class="fas fa-thumbs-up me-2"></i>Continue
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // ULTRA SIMPLE JAVASCRIPT - NO COMPLEX OPERATIONS
            var currentClientId = null; // Track currently selected client
            
            $(document).ready(function() {
                console.log('Page loaded - simple version');
                
                // Load clients with fallback
                setTimeout(function() {
                    loadClients();
                }, 500);
                
                // Add fallback clients if AJAX fails
                setTimeout(function() {
                    if ($('#clientSelect option').length <= 1) {
                        console.log('Adding fallback clients...');
                        var select = $('#clientSelect');
                        select.empty().append('<option value="">SELECT CLIENT</option>');
                        select.append('<option value="1">John Doe</option>');
                        select.append('<option value="2">Jane Smith</option>');
                        select.append('<option value="3">Bob Johnson</option>');
                    }
                }, 3000);
                
                // Simple button handler
                $('#addNew').click(function(e) {
                    e.preventDefault();
                    console.log('Button clicked');
                    
                    var clientId = $('#clientSelect').val();
                    var clientName = $('#clientSelect option:selected').text();
                    
                    if (!clientId || clientId === '') {
                        alert('Please select a client first');
                        return false;
                    }
                    
                    // Check client eligibility before opening modal
                    checkClientEligibility(clientId, clientName);
                    
                    return false;
                });

                
                // Client selection handler
                $('#clientSelect').change(function() {
                    var clientId = $(this).val();
                    currentClientId = clientId; // Store the selected client ID
                    console.log('Client selected:', clientId);
                    
                    if (clientId && clientId !== '') {
                        $('#addNew').prop('disabled', false);
                        loadClientLoans(clientId);
                    } else {
                        $('#addNew').prop('disabled', true);
                        currentClientId = null;
                        $('#loanTableBody').html('<tr><td colspan="10" class="text-center text-muted">Please select a client to view loans</td></tr>');
                    }
                });
                
                // Modal event handlers for form reset
                $('#newLoanModal').on('hidden.bs.modal', function () {
                    console.log('Modal closed, resetting form...');
                    resetNewLoanForm();
                });
                
                // Also reset when Cancel button is clicked
                $('#newLoanModal .btn-secondary').on('click', function() {
                    console.log('Cancel clicked, resetting form...');
                    resetNewLoanForm();
                });
            });
            
            function loadClients() {
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getClients',
                    type: 'GET',
                    dataType: 'json', // This tells jQuery to expect JSON
                    success: function(data) {
                        console.log('Clients response:', data);
                        if (data && data.success && data.clients) {
                            var select = $('#clientSelect');
                            select.empty().append('<option value="">SELECT CLIENT</option>');
                            data.clients.forEach(function(client) {
                                // Add visual indicator for clients with pending loans
                                var displayName = client.ClientName;
                                var indicator = '';
                                var dataAttrs = '';
                                
                                if (client.HasPendingLoans) {
                                    indicator = ' ⚠'; // Warning symbol for pending loans
                                    dataAttrs = ' data-has-pending="true" data-active-loans="' + client.ActiveLoans + '" data-balance="' + client.TotalBalance + '"';
                                } else if (client.TotalLoans > 0) {
                                    indicator = ' ✓'; // Checkmark for clients with fully paid loans
                                    dataAttrs = ' data-has-pending="false" data-total-loans="' + client.TotalLoans + '"';
                                }
                                
                                select.append('<option value="' + client.ClientNo + '"' + dataAttrs + '>' + displayName + indicator + '</option>');
                            });
                            console.log('Loaded ' + data.clients.length + ' clients');
                            
                            // Initialize Select2 AFTER clients are loaded for searchable dropdown
                            select.select2({
                                placeholder: 'Type to search for a client...',
                                allowClear: false,
                                width: '100%',
                                theme: 'bootstrap-5',
                                dropdownAutoWidth: false,
                                closeOnSelect: true,
                                // Ensure proper scrolling behavior
                                dropdownCssClass: 'select2-dropdown-scroll',
                                // Custom template to show loan status with colors
                                templateResult: function(client) {
                                    if (!client.id) {
                                        return client.text;
                                    }
                                    
                                    var $client = $(client.element);
                                    var hasPending = $client.data('has-pending');
                                    var activeLoans = $client.data('active-loans');
                                    var balance = $client.data('balance');
                                    var totalLoans = $client.data('total-loans');
                                    
                                    var $result = $('<div style="display: flex; align-items: center; justify-content: space-between;"></div>');
                                    var $name = $('<span></span>').text(client.text.replace(' ⚠', '').replace(' ✓', ''));
                                    $result.append($name);
                                    
                                    // Add badge for loan status
                                    if (hasPending === true) {
                                        var $badge = $('<span class="badge" style="background-color: #dc3545; color: white; font-size: 0.7rem; padding: 2px 6px; margin-left: 8px;"><i class="fas fa-exclamation-circle"></i> ' + activeLoans + ' Active</span>');
                                        $badge.attr('title', activeLoans + ' active loan(s), Balance: ₱' + (balance ? balance.toLocaleString('en-US', {minimumFractionDigits: 2}) : '0.00'));
                                        $result.append($badge);
                                    } else if (totalLoans > 0) {
                                        var $badge = $('<span class="badge" style="background-color: #28a745; color: white; font-size: 0.7rem; padding: 2px 6px; margin-left: 8px;"><i class="fas fa-check-circle"></i> Paid</span>');
                                        $badge.attr('title', 'All loans paid');
                                        $result.append($badge);
                                    }
                                    
                                    return $result;
                                },
                                templateSelection: function(client) {
                                    if (!client.id) {
                                        return client.text;
                                    }
                                    
                                    var $client = $(client.element);
                                    var hasPending = $client.data('has-pending');
                                    var activeLoans = $client.data('active-loans');
                                    var totalLoans = $client.data('total-loans');
                                    
                                    var $result = $('<span></span>');
                                    var cleanText = client.text.replace(' ⚠', '').replace(' ✓', '');
                                    $result.text(cleanText);
                                    
                                    // Add small badge in selection
                                    if (hasPending === true) {
                                        $result.append(' <span class="badge bg-danger" style="font-size: 0.65rem; vertical-align: middle;"><i class="fas fa-exclamation-circle"></i> ' + activeLoans + '</span>');
                                    } else if (totalLoans > 0) {
                                        $result.append(' <span class="badge bg-success" style="font-size: 0.65rem; vertical-align: middle;"><i class="fas fa-check"></i></span>');
                                    }
                                    
                                    return $result;
                                }
                            });
                            
                            // Re-bind change event for Select2
                            select.on('select2:select', function(e) {
                                // Trigger the original change event
                                $(this).trigger('change');
                            });
                            
                        } else {
                            console.error('Invalid response format:', data);
                            $('#clientSelect').html('<option value="">Error loading clients</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        $('#clientSelect').html('<option value="">Failed to load clients</option>');
                    }
                });
            }
            
            function loadClientLoans(clientId) {
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getClientLoans&client_id=' + clientId,
                    type: 'GET',
                    dataType: 'html', // Expecting HTML response for table rows
                    success: function(response) {
                        console.log('Loans response received');
                        $('#loanTableBody').html(response);
                        
                        // Add click handlers to loan rows after loading
                        addLoanRowClickHandlers();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading loans:', error);
                        console.error('Response:', xhr.responseText);
                        $('#loanTableBody').html('<tr><td colspan="12" class="text-center text-danger">Error loading loans</td></tr>');
                    }
                });
            }
            
            // =================================================================
            // FRONTEND VALIDATION FIX v2.0 - DUPLICATE HANDLERS REMOVED
            // =================================================================
            // Fixed: Removed duplicate click handlers and openRenewalModal functions
            // Fixed: All loan row clicks now go through validation FIRST
            // =================================================================
            
            function addLoanRowClickHandlers() {
                // Remove existing handlers to prevent duplicates
                $('#loanTableBody tr').off('click');
                
                console.log('Adding click handlers to loan rows...');
                
                // Add click handler to ALL loan rows (both paid and unpaid)
                $('#loanTableBody tr[data-loan-id]').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var $row = $(this);
                    var loanId = $row.data('loan-id');
                    var clientId = $row.data('user-id');
                    var clientName = $('#clientSelect option:selected').text();
                    
                    console.log('=== LOAN ROW CLICKED (addLoanRowClickHandlers) ===');
                    console.log('LoanID:', loanId);
                    console.log('ClientID:', clientId);
                    console.log('ClientName:', clientName);
                    
                    // Check if this row has loan data (skip header/empty rows)
                    if (!loanId || loanId === '' || loanId === undefined) {
                        console.log('Skipping row - no loan ID');
                        return;
                    }
                    
                    if (!clientId) {
                        console.log('No client ID found, using currentClientId:', currentClientId);
                        clientId = currentClientId;
                    }
                    
                    // ALWAYS check client eligibility before allowing renewal
                    // Even if clicking on a PAID loan, client might have OTHER unpaid loans
                    console.log('Checking client eligibility before any modal opens...');
                    checkClientRenewalEligibility(clientId, clientName, loanId);
                });
                
                // Log how many rows have click handlers
                var clickableRows = $('#loanTableBody tr[data-loan-id]').length;
                console.log('Added click handlers to', clickableRows, 'loan rows');
            }
            
            function checkClientEligibility(clientId, clientName) {
                console.log('Checking client eligibility for:', clientId, clientName);
                
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=checkClientEligibility&client_id=' + clientId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Eligibility response:', response);
                        
                        if (response && response.success) {
                            if (response.eligible) {
                                // Client is eligible, proceed to open modal
                                openNewLoanModal(clientId, clientName);
                            } else {
                                // Client is not eligible, show error message
                                showEligibilityError(clientName, response);
                            }
                        } else {
                            console.error('Error checking eligibility:', response);
                            alert('Error checking client eligibility. Please try again.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error checking client eligibility:', error);
                        console.error('Response:', xhr.responseText);
                        alert('Error checking client eligibility: ' + error);
                    }
                });
            }
            
            function openNewLoanModal(clientId, clientName) {
                console.log('Opening new loan modal for client:', clientName, 'ID:', clientId);
                
                // Set client information
                $('#newLoanClientId').val(clientId);
                $('#newLoanClientNameDisplay').val(clientName);
                
                // Load loaned products for this client
                loadLoanedProductsForClient(clientId);
                
                // Load other dropdown data from database
                loadFormDropdowns();
                
                // Use Bootstrap 5 modal API
                try {
                    var modalElement = document.getElementById('newLoanModal');
                    var modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } catch (error) {
                    console.error('Modal error:', error);
                    // Fallback to jQuery
                    $('#newLoanModal').modal('show');
                }
            }
            
            function loadLoanedProductsForClient(clientNo) {
                console.log('Loading loaned products for client:', clientNo);
                
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getLoanedProductsForClient&client_no=' + clientNo,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Loaned products response:', response);
                        
                        if (response && response.success && response.products) {
                            var select = $('#newLoanProductDropdown');
                            select.empty().append('<option value="">Select a loaned product...</option>');
                            
                            if (response.products.length === 0) {
                                select.append('<option value="" disabled>No available loaned products in inventory</option>');
                                console.warn('No loaned products found in inventory');
                            } else {
                                // Show info message if products exist but none for this specific client
                                if (response.matchedCount === 0 && response.products.length > 0) {
                                    select.append('<option value="" disabled>--- Available loaned products (not specifically for this client) ---</option>');
                                } else if (response.matchedCount > 0) {
                                    select.append('<option value="" disabled>--- Products for ' + response.clientName + ' ---</option>');
                                }
                                
                                response.products.forEach(function(product) {
                                    select.append('<option value="' + product.SI + '" data-price="' + product.Price + '" data-product-name="' + product.ProductName + '" data-is-for-client="' + product.IsForThisClient + '">' + product.DisplayName + '</option>');
                                });
                                
                                console.log('Loaded ' + response.products.length + ' loaned products (' + response.matchedCount + ' matched to client)');
                                console.log('Debug info:', response.debug);
                            }
                            
                            // Initialize Select2 for searchable dropdown
                            select.select2({
                                placeholder: 'Type to search for a product...',
                                allowClear: true,
                                width: '100%',
                                theme: 'bootstrap-5',
                                dropdownParent: $('#newLoanModal')
                            });
                            
                            // Handle product selection
                            select.on('select2:select', function(e) {
                                var selectedOption = $(this).find('option:selected');
                                var si = selectedOption.val();
                                var price = selectedOption.data('price');
                                var productName = selectedOption.data('product-name');
                                
                                console.log('Product selected - SI:', si, 'Price:', price, 'Name:', productName);
                                
                                // Store the inventory SI
                                $('#newLoanInventorySI').val(si);
                                
                                // Set loan amount from product price
                                $('#newLoanAmount').val(price);
                                $('#newLoanProductPrice').val(price);
                                
                                // Trigger computation with the price
                                computeAddPrincipal(price);
                            });
                            
                        } else {
                            console.error('Failed to load loaned products:', response);
                            alert('Error loading loaned products: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading loaned products:', error);
                        console.error('Response:', xhr.responseText);
                        alert('Error loading loaned products: ' + error);
                    }
                });
            }
            
            function showEligibilityError(clientName, response) {
                // Update modal content with client name and amount only
                $('#eligibilityClientName').text(clientName);
                $('#eligibilityAmount').text('₱' + (response.total_outstanding ? response.total_outstanding.toLocaleString('en-US', {minimumFractionDigits: 2}) : '0.00'));
                
                // Set a clean message without loan IDs
                var message = 'Cannot apply for a new loan because there ';
                if (response.active_loans_count > 1) {
                    message += 'are ' + response.active_loans_count + ' active loans that must be paid first.';
                } else {
                    message += 'is 1 active loan that must be paid first.';
                }
                $('#eligibilityMessage').text(message);
                
                // Show the styled modal instead of alert
                $('#eligibilityErrorModal').modal('show');
            }
            
            function showOutstandingLoansError(clientName, response) {
                console.log('=== SHOWING OUTSTANDING LOANS ERROR ===');
                console.log('Blocking renewal for client:', clientName);
                console.log('Outstanding count:', response.outstandingCount);
                console.log('Total outstanding:', response.totalOutstanding);
                
                // Update modal content for outstanding loans error
                $('#eligibilityClientName').text(clientName);
                $('#eligibilityAmount').text('₱' + (response.totalOutstanding ? response.totalOutstanding.toLocaleString('en-US', {minimumFractionDigits: 2}) : '0.00'));
                
                // Set message for renewal blocking
                var message = 'Cannot renew loan because there ';
                if (response.outstandingCount > 1) {
                    message += 'are ' + response.outstandingCount + ' outstanding loans that must be paid first.';
                } else {
                    message += 'is 1 outstanding loan that must be paid first.';
                }
                $('#eligibilityMessage').text(message);
                
                console.log('Showing eligibility error modal...');
                // Show the styled modal
                $('#eligibilityErrorModal').modal('show');
            }
            
            function showDetailedEligibilityMessage(clientName, response) {
                // This function is no longer needed since we're using the modal
                // But keeping it for compatibility
                console.log('Using modal instead of detailed message');
            }
            
            function loadFormDropdowns() {
                console.log('Loading form dropdowns from database...');
                
                // Load Products from tbl_loansetup
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getProducts',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Products response:', response);
                        if (response && response.success && response.products) {
                            var productSelect = $('#newLoanProduct');
                            productSelect.empty().append('<option value="">SELECT PRODUCT</option>');
                            response.products.forEach(function(product) {
                                productSelect.append('<option value="' + product + '">' + product + '</option>');
                            });
                            console.log('Loaded ' + response.products.length + ' products');
                        } else {
                            console.error('Failed to load products:', response);
                            $('#newLoanProduct').html('<option value="">Error loading products</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading products:', error);
                        $('#newLoanProduct').html('<option value="">Error loading products</option>');
                    }
                });
                
                // Load Modes from tbl_maintenance
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getModes',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Modes response:', response);
                        if (response && response.success && response.modes) {
                            var modeSelect = $('#newLoanMode');
                            modeSelect.empty().append('<option value="">SELECT MODE</option>');
                            response.modes.forEach(function(mode) {
                                modeSelect.append('<option value="' + mode + '">' + mode + '</option>');
                            });
                            console.log('Loaded ' + response.modes.length + ' modes');
                        } else {
                            console.error('Failed to load modes:', response);
                            $('#newLoanMode').html('<option value="">Error loading modes</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading modes:', error);
                        $('#newLoanMode').html('<option value="">Error loading modes</option>');
                    }
                });
                
                // Load Staff from tbl_po
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getStaff',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Staff response:', response);
                        if (response && response.success && response.staff) {
                            var staffSelect = $('#newLoanStaff');
                            staffSelect.empty().append('<option value="">SELECT STAFF</option>');
                            response.staff.forEach(function(staff) {
                                staffSelect.append('<option value="' + staff + '">' + staff + '</option>');
                            });
                            console.log('Loaded ' + response.staff.length + ' staff members');
                        } else {
                            console.error('Failed to load staff:', response);
                            $('#newLoanStaff').html('<option value="">Error loading staff</option>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading staff:', error);
                        $('#newLoanStaff').html('<option value="">Error loading staff</option>');
                    }
                });
            }
            
            function submitLoan() {
                // Get form data
                var formData = {
                    action: 'saveLoanApplication',
                    add_userId: $('#newLoanClientId').val(), // Use selected client from dropdown
                    add_loanType: 'NEW',
                    add_program: 'ISYN',
                    add_product: $('#newLoanProduct').val(),
                    add_mode: $('#newLoanMode').val(),
                    add_termRate: $('#newLoanTerm').val(),
                    add_amount: $('#newLoanAmount').val(),
                    add_poFco: $('#newLoanStaff').val(),
                    add_availment: '1',
                    add_interest: $('#interestAddValue').val().replace(/,/g, ''), // Add calculated interest
                    inventory_si: $('#newLoanInventorySI').val() // Add inventory SI for linking
                };
                
                // Basic validation
                if (!formData.add_userId) {
                    alert('Please select a client');
                    return;
                }
                if (!formData.add_product) {
                    alert('Please select a product');
                    return;
                }
                if (!formData.add_mode) {
                    alert('Please select a mode');
                    return;
                }
                if (!formData.add_termRate) {
                    alert('Please select a term');
                    return;
                }
                if (!formData.add_amount || parseFloat(formData.add_amount) <= 0) {
                    alert('Please enter a valid loan amount');
                    return;
                }
                if (!formData.add_poFco) {
                    alert('Please select a staff member');
                    return;
                }
                
                console.log('Submitting loan application:', formData);
                console.log('Calculated interest being sent:', formData.add_interest);
                
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Loan submission response:', response);
                        if (response && response.success) {
                            // Update the loan ID in the success modal
                            document.getElementById('successLoanId').textContent = response.loanId || 'Generated';
                            
                            // Hide the new loan modal first
                            $('#newLoanModal').modal('hide');
                            
                            // Show the success modal after a brief delay
                            setTimeout(function() {
                                $('#loanSuccessModal').modal('show');
                            }, 300);
                            
                            // Reset form after successful submission
                            resetNewLoanForm();
                            
                            // Reload client loans to show the new loan
                            var clientId = $('#clientSelect').val();
                            if (clientId) {
                                loadClientLoans(clientId);
                            }
                        } else {
                            alert('Error: ' + (response.message || 'Unknown error occurred'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error submitting loan:', error);
                        console.error('Response:', xhr.responseText);
                        alert('Error submitting loan application: ' + error);
                    }
                });
            }
            
            // Function to reset the new loan form
            function resetNewLoanForm() {
                console.log('Resetting new loan form...');
                
                // Reset form fields
                $('#newLoanForm')[0].reset();
                
                // Destroy Select2 and reset loaned products dropdown
                if ($('#newLoanProductDropdown').hasClass('select2-hidden-accessible')) {
                    $('#newLoanProductDropdown').select2('destroy');
                }
                $('#newLoanProductDropdown').empty().append('<option value="">Select a loaned product...</option>');
                
                $('#newLoanClientId').val('');
                $('#newLoanClientNameDisplay').val('');
                $('#newLoanProductPrice').val('0');
                $('#newLoanInventorySI').val('');
                
                // Reset dropdowns to loading state
                $('#newLoanProduct').html('<option value="">Loading products...</option>');
                $('#newLoanMode').html('<option value="">Loading modes...</option>');
                $('#newLoanStaff').html('<option value="">Loading staff...</option>');
                $('#newLoanTerm').val('');
                $('#newLoanRate').val('3%');
                $('#newLoanAmount').val('');
                
                // Reset summary and amortization displays
                $('#principalAddValue').val('0.00');
                $('#principalAddValueDisplay').val('0.00');
                $('#interestAddValue').val('0.00');
                $('#totalAddValue').val('0.00');
                $('#principalAmortAddValue').val('0.00');
                $('#interestAmortAddValue').val('0.00');
                $('#totalAmortAddValue').val('0.00');
                
                console.log('Form reset completed');
            }
            
            // Test backend connectivity
            function testBackend() {
                console.log('Testing backend connectivity...');
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=test',
                    type: 'GET',
                    timeout: 5000,
                    success: function(response) {
                        console.log('Backend test successful:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Backend test failed:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                    }
                });
            }
            
            // Call test on page load
            setTimeout(testBackend, 1000);
            
            // LOAN COMPUTATION FUNCTIONS FROM WORKING VERSION
            function computeAmortization() {
                var mode = document.getElementById('newLoanMode').value;
                var term = parseFloat(document.getElementById('newLoanTerm').value);
                var principal = parseFloat(document.getElementById('principalAddValue').value.replace(',', ''));
                var amount = parseFloat(document.getElementById('newLoanAmount').value.replace(',', ''));
                var rate = parseFloat(document.getElementById('newLoanRate').value.replace('%', '')) / 100;
                var downpaymentAmount = parseFloat(document.getElementById('add_downpaymentAmount').value);

                var totalPayments;

                if (mode === 'MONTHLY') {
                    totalPayments = term;
                } else if (mode === 'SEMI-MONTHLY') {
                    totalPayments = term * 2;
                } else if (mode === 'WEEKLY') {
                    totalPayments = term * 4;
                } else {
                    totalPayments = term; // Default to monthly
                }

                if (!isNaN(term) && !isNaN(rate) && !isNaN(amount)) {
                    var principalPerPayment;

                    if (!isNaN(downpaymentAmount) && downpaymentAmount > 0) {
                        principalPerPayment = principal / totalPayments;
                    } else {
                        principalPerPayment = amount / totalPayments;
                    }

                    // Round principal per payment
                    var roundedPrincipal;
                    if ((principalPerPayment - Math.floor(principalPerPayment)) >= 0.5) {
                        roundedPrincipal = Math.ceil(principalPerPayment);
                    } else {
                        roundedPrincipal = Math.floor(principalPerPayment);
                    }

                    document.getElementById('principalAmortAddValue').value = roundedPrincipal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    var totalInterest = roundedPrincipal * term * rate;

                    // Round total interest
                    if ((totalInterest - Math.floor(totalInterest)) >= 0.5) {
                        totalInterest = Math.ceil(totalInterest);
                    } else {
                        totalInterest = Math.floor(totalInterest);
                    }

                    var totalAmortization = roundedPrincipal + totalInterest;

                    document.getElementById('interestAmortAddValue').value = totalInterest.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    document.getElementById('totalAmortAddValue').value = totalAmortization.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById('principalAmortAddValue').value = "0.00";
                    document.getElementById('interestAmortAddValue').value = "0.00";
                    document.getElementById('totalAmortAddValue').value = "0.00";
                }
            }
            
            function computeAddInterest() {
                var term = parseFloat(document.getElementById('newLoanTerm').value);
                var amount = parseFloat(document.getElementById('newLoanAmount').value.replace(',', ''));
                var rate = parseFloat(document.getElementById('newLoanRate').value.replace('%', '')) / 100;
                
                if (!isNaN(term) && !isNaN(amount) && !isNaN(rate)) {
                    var interest = amount * rate * term;
                    
                    // Round interest
                    if ((interest - Math.floor(interest)) >= 0.5) {
                        interest = Math.ceil(interest);
                    } else {
                        interest = Math.floor(interest);
                    }
                    
                    document.getElementById('interestAddValue').value = interest.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    
                    computeTotal();
                } else {
                    document.getElementById('interestAddValue').value = "0.00";
                }
            }
            
            function computeAddPrincipal(amount) {
                if (amount && !isNaN(amount)) {
                    var principal = parseFloat(amount);
                    document.getElementById('principalAddValue').value = principal;
                    document.getElementById('principalAddValueDisplay').value = principal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    
                    computeTotal();
                } else {
                    document.getElementById('principalAddValue').value = "0.00";
                    document.getElementById('principalAddValueDisplay').value = "0.00";
                }
            }
            
            function computeTotal() {
                var principal = parseFloat(document.getElementById('principalAddValue').value) || 0;
                var interest = parseFloat(document.getElementById('interestAddValue').value.replace(',', '')) || 0;
                
                var total = principal + interest;
                
                document.getElementById('totalAddValue').value = total.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                // Update the loan amount field to reflect total amount due
                document.getElementById('newLoanAmount').setAttribute('data-total-due', total);
            }

            // =====================================================
            // RENEWAL FUNCTIONALITY
            // =====================================================
            // Note: Click handlers are managed by addLoanRowClickHandlers() function above

            function checkClientRenewalEligibility(clientId, clientName, selectedLoanId) {
                console.log('=== ELIGIBILITY CHECK STARTED ===');
                console.log('Checking client renewal eligibility for:', clientName);
                console.log('ClientID:', clientId);
                console.log('SelectedLoanID:', selectedLoanId);
                
                if (!clientId) {
                    alert('Client ID not found. Please select a client first.');
                    return;
                }
                
                // Show loading indicator
                console.log('Making API call to checkOutstandingLoans...');
                
                // Check if client has ANY outstanding loans (Balance > 0)
                // This will block renewal even if clicking on a PAID loan if client has OTHER unpaid loans
                fetch('/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=checkOutstandingLoans&client_no=' + clientId)
                    .then(response => {
                        console.log('API Response received:', response);
                        return response.json();
                    })
                    .then(data => {
                        console.log('=== API RESPONSE DATA ===');
                        console.log('Outstanding loans check response:', data);
                        console.log('hasOutstanding:', data.hasOutstanding);
                        console.log('outstandingCount:', data.outstandingCount);
                        console.log('totalOutstanding:', data.totalOutstanding);
                        
                        if (data.success) {
                            if (data.hasOutstanding) {
                                // Client has outstanding loans - BLOCK renewal
                                console.log('❌ BLOCKING RENEWAL - Client has outstanding loans');
                                showOutstandingLoansError(clientName, data);
                            } else {
                                // Client has NO outstanding loans - ALLOW renewal
                                console.log('✅ ALLOWING RENEWAL - No outstanding loans');
                                openRenewalModal(selectedLoanId, clientId, clientName);
                            }
                        } else {
                            console.error('Outstanding loans check failed:', data.message);
                            alert('Error checking client eligibility: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('=== API ERROR ===');
                        console.error('Outstanding loans check error:', error);
                        alert('Error checking client eligibility. Please try again.');
                    });
            }

            function openRenewalModal(loanId, clientId, clientName) {
                console.log('=== OPENING RENEWAL MODAL ===');
                console.log('Opening renewal modal for loan:', loanId, 'client:', clientName, 'ID:', clientId);
                console.log('This should ONLY happen if client has NO outstanding loans');
                
                // Get client name from multiple sources with fallbacks
                if (!clientName || clientName === '' || clientName === 'SELECT CLIENT') {
                    // Try to get from dropdown first
                    var dropdownText = $('#clientSelect option:selected').text();
                    if (dropdownText && dropdownText !== 'SELECT CLIENT' && dropdownText !== 'Loading clients...' && dropdownText !== '') {
                        clientName = dropdownText;
                    } else {
                        // Fallback: try to get from the loan row data
                        var selectedRow = $('#loanTableBody tr[data-loan-id="' + loanId + '"]');
                        if (selectedRow.length > 0) {
                            var rowClientName = selectedRow.find('td:nth-child(2)').text(); // Client name is in 2nd column
                            if (rowClientName && rowClientName !== '' && rowClientName !== '-') {
                                clientName = rowClientName;
                            }
                        }
                    }
                }
                
                // Final fallback
                if (!clientName || clientName === '' || clientName === 'SELECT CLIENT') {
                    clientName = 'Unknown Client';
                }
                
                console.log('Final client name for renewal:', clientName);
                
                // Set client and loan data
                document.getElementById('renewalClientId').value = clientId;
                document.getElementById('renewalLoanId').value = loanId;
                document.getElementById('renewalClientName').textContent = clientName;
                document.getElementById('renewalPreviousLoanId').textContent = loanId;
                
                var renewalClientNameField = document.getElementById('renewalClientNameField');
                if (renewalClientNameField) {
                    renewalClientNameField.value = clientName;
                }
                
                // Load loaned products for this client
                loadLoanedProductsForRenewal(clientId);
                
                // Load form dropdowns
                loadRenewalFormDropdowns();
                
                // Reset form (but preserve client name)
                resetRenewalForm();
                
                // Set client name again after reset
                if (renewalClientNameField) {
                    renewalClientNameField.value = clientName;
                }
                
                // Fetch and populate previous loan data
                fetchPreviousLoanData(loanId);
                
                // Show modal with simple method to avoid freezing
                $('#renewalLoanModal').modal('show');
                console.log('Renewal modal opened successfully with client:', clientName);
            }
            
            function loadLoanedProductsForRenewal(clientNo) {
                console.log('Loading loaned products for renewal, client:', clientNo);
                
                $.ajax({
                    url: '/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getLoanedProductsForClient&client_no=' + clientNo,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Loaned products for renewal response:', response);
                        
                        if (response && response.success && response.products) {
                            var select = $('#renewalProductDropdown');
                            select.empty().append('<option value="">Select a loaned product...</option>');
                            
                            if (response.products.length === 0) {
                                select.append('<option value="" disabled>No available loaned products in inventory</option>');
                                console.warn('No loaned products found for renewal');
                            } else {
                                // Show info message if products exist but none for this specific client
                                if (response.matchedCount === 0 && response.products.length > 0) {
                                    select.append('<option value="" disabled>--- Available loaned products (not specifically for this client) ---</option>');
                                } else if (response.matchedCount > 0) {
                                    select.append('<option value="" disabled>--- Products for ' + response.clientName + ' ---</option>');
                                }
                                
                                response.products.forEach(function(product) {
                                    select.append('<option value="' + product.SI + '" data-price="' + product.Price + '" data-product-name="' + product.ProductName + '" data-is-for-client="' + product.IsForThisClient + '">' + product.DisplayName + '</option>');
                                });
                                
                                console.log('Loaded ' + response.products.length + ' loaned products for renewal');
                            }
                            
                            // Initialize Select2 for searchable dropdown
                            select.select2({
                                placeholder: 'Type to search for a product...',
                                allowClear: true,
                                width: '100%',
                                theme: 'bootstrap-5',
                                dropdownParent: $('#renewalLoanModal')
                            });
                            
                            // Handle product selection
                            select.on('select2:select', function(e) {
                                var selectedOption = $(this).find('option:selected');
                                var si = selectedOption.val();
                                var price = selectedOption.data('price');
                                var productName = selectedOption.data('product-name');
                                
                                console.log('Renewal product selected - SI:', si, 'Price:', price, 'Name:', productName);
                                
                                // Store the inventory SI
                                $('#renewalInventorySI').val(si);
                                
                                // Set loan amount from product price
                                $('#renewalAmount').val(price);
                                $('#renewalProductPrice').val(price);
                                
                                // Trigger computation with the price
                                calculateRenewalSummary();
                            });
                            
                        } else {
                            console.error('Failed to load loaned products for renewal:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading loaned products for renewal:', error);
                        console.error('Response:', xhr.responseText);
                    }
                });
            }

            function loadRenewalFormDropdowns() {
                console.log('Loading renewal form dropdowns...');
                
                // Load staff
                fetch('/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getStaff')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            var staffSelect = document.getElementById('renewalPoFco');
                            staffSelect.innerHTML = '<option value="">Select Staff</option>';
                            data.staff.forEach(staff => {
                                staffSelect.innerHTML += `<option value="${staff}">${staff}</option>`;
                            });
                        }
                    })
                    .catch(error => console.error('Error loading staff:', error));

                // Load products
                fetch('/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getProducts')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            var productSelect = document.getElementById('renewalProduct');
                            productSelect.innerHTML = '<option value="">Select Product</option>';
                            data.products.forEach(product => {
                                productSelect.innerHTML += `<option value="${product}">${product}</option>`;
                            });
                        }
                    })
                    .catch(error => console.error('Error loading products:', error));

                // Load modes
                fetch('/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getModes')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            var modeSelect = document.getElementById('renewalMode');
                            modeSelect.innerHTML = '<option value="">Select Mode</option>';
                            data.modes.forEach(mode => {
                                modeSelect.innerHTML += `<option value="${mode}">${mode}</option>`;
                            });
                        }
                    })
                    .catch(error => console.error('Error loading modes:', error));
            }

            function fetchPreviousLoanData(loanId) {
                console.log('Fetching previous loan data for:', loanId);
                
                // Fetch loan details from database
                fetch('/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php?action=getLoanDetails&loan_id=' + loanId)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Previous loan data received:', data);
                        
                        if (data.success && data.loan) {
                            var loan = data.loan;
                            
                            // Wait a moment for dropdowns to load, then populate
                            setTimeout(function() {
                                populateRenewalFormWithPreviousData(loan);
                            }, 1000);
                            
                        } else {
                            console.error('Failed to fetch loan details:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching previous loan data:', error);
                    });
            }

            function populateRenewalFormWithPreviousData(loan) {
                console.log('Populating renewal form with previous loan data:', loan);
                
                try {
                    // Set Product (if available in dropdown)
                    if (loan.Product) {
                        var productSelect = document.getElementById('renewalProduct');
                        if (productSelect) {
                            // Check if the product exists in the dropdown
                            var productOption = Array.from(productSelect.options).find(option => option.value === loan.Product);
                            if (productOption) {
                                productSelect.value = loan.Product;
                                console.log('Set product to:', loan.Product);
                            } else {
                                console.log('Product not found in dropdown:', loan.Product);
                            }
                        }
                    }
                    
                    // Set Mode (if available in dropdown)
                    if (loan.Mode) {
                        var modeSelect = document.getElementById('renewalMode');
                        if (modeSelect) {
                            var modeOption = Array.from(modeSelect.options).find(option => option.value === loan.Mode);
                            if (modeOption) {
                                modeSelect.value = loan.Mode;
                                console.log('Set mode to:', loan.Mode);
                            } else {
                                console.log('Mode not found in dropdown:', loan.Mode);
                            }
                        }
                    }
                    
                    // Set Term (if available in dropdown)
                    if (loan.Term) {
                        var termSelect = document.getElementById('renewalTermRate');
                        if (termSelect) {
                            termSelect.value = loan.Term;
                            console.log('Set term to:', loan.Term);
                        }
                    }
                    
                    // Set Loan Amount (as starting point - user can modify)
                    if (loan.LoanAmount) {
                        var amountInput = document.getElementById('renewalAmount');
                        if (amountInput) {
                            amountInput.value = loan.LoanAmount;
                            console.log('Set loan amount to:', loan.LoanAmount);
                        }
                    }
                    
                    // Trigger calculation after setting values
                    calculateRenewalSummary();
                    
                    console.log('Successfully populated renewal form with previous loan data');
                    
                } catch (error) {
                    console.error('Error populating renewal form:', error);
                }
            }

            function resetRenewalForm() {
                console.log('Resetting renewal form...');
                
                try {
                    // Store client name before reset
                    var currentClientName = '';
                    var renewalClientNameField = document.getElementById('renewalClientNameField');
                    if (renewalClientNameField) {
                        currentClientName = renewalClientNameField.value;
                    }
                    
                    // Destroy Select2 and reset loaned products dropdown
                    if ($('#renewalProductDropdown').hasClass('select2-hidden-accessible')) {
                        $('#renewalProductDropdown').select2('destroy');
                    }
                    $('#renewalProductDropdown').empty().append('<option value="">Select a loaned product...</option>');
                    
                    // Reset hidden fields
                    $('#renewalInventorySI').val('');
                    $('#renewalProductPrice').val('0');
                    
                    // Reset all form fields safely
                    var renewalLoanType = document.getElementById('renewalLoanType');
                    if (renewalLoanType) renewalLoanType.value = 'RENEWAL';
                    
                    var renewalTag = document.getElementById('renewalTag');
                    if (renewalTag) renewalTag.value = '-';
                    
                    var renewalPoFco = document.getElementById('renewalPoFco');
                    if (renewalPoFco) renewalPoFco.value = '';
                    
                    var renewalProgram = document.getElementById('renewalProgram');
                    if (renewalProgram) renewalProgram.value = 'Microfinance';
                    
                    var renewalProduct = document.getElementById('renewalProduct');
                    if (renewalProduct) renewalProduct.value = '';
                    
                    var renewalMode = document.getElementById('renewalMode');
                    if (renewalMode) renewalMode.value = '';
                    
                    var renewalTermRate = document.getElementById('renewalTermRate');
                    if (renewalTermRate) renewalTermRate.value = '';
                    
                    var renewalRate = document.getElementById('renewalRate');
                    if (renewalRate) renewalRate.value = '3%';
                    
                    var renewalAvailment = document.getElementById('renewalAvailment');
                    if (renewalAvailment) renewalAvailment.value = '1';
                    
                    var renewalAmount = document.getElementById('renewalAmount');
                    if (renewalAmount) renewalAmount.value = '';
                    
                    var renewalDownPaymentAmount = document.getElementById('renewalDownPaymentAmount');
                    if (renewalDownPaymentAmount) renewalDownPaymentAmount.value = '0';
                    
                    // DON'T reset client name field - preserve it
                    // var renewalClientNameField = document.getElementById('renewalClientNameField');
                    // if (renewalClientNameField) renewalClientNameField.value = '';
                    
                    // Reset summary displays safely
                    var summaryFields = [
                        'renewalSummaryPrincipal', 'renewalSummaryInterest', 'renewalSummaryTotal',
                        'renewalAmortizationPrincipal', 'renewalAmortizationInterest', 'renewalAmortizationTotal'
                    ];
                    
                    summaryFields.forEach(function(fieldId) {
                        var field = document.getElementById(fieldId);
                        if (field) field.value = '0.00';
                    });
                    
                    console.log('Renewal form reset completed successfully');
                } catch (error) {
                    console.error('Error resetting renewal form:', error);
                }
            }

            // Add event listeners for renewal form calculations
            $(document).ready(function() {
                // Product change event for renewal
                $('#renewalProduct').on('change', function() {
                    var product = $(this).val();
                    var rate = '3%'; // Default rate, you can make this dynamic based on product
                    $('#renewalRate').val(rate);
                    calculateRenewalSummary();
                });

                // Amount, term, mode change events for renewal
                $('#renewalAmount, #renewalTermRate, #renewalMode, #renewalDownPaymentAmount').on('input change', function() {
                    calculateRenewalSummary();
                });
            });

            function calculateRenewalSummary() {
                try {
                    var amount = parseFloat(document.getElementById('renewalAmount').value.replace(/,/g, '')) || 0;
                    var term = parseInt(document.getElementById('renewalTermRate').value) || 0;
                    var mode = document.getElementById('renewalMode').value;
                    var rate = parseFloat(document.getElementById('renewalRate').value.replace('%', '')) || 3;
                    var downPayment = parseFloat(document.getElementById('renewalDownPaymentAmount').value.replace(/,/g, '')) || 0;
                    
                    if (amount > 0 && term > 0) {
                        var principal = amount - downPayment;
                        var interest = principal * (rate / 100) * term;
                        var total = principal + interest;
                        
                        // Calculate payments based on mode
                        var totalPayments = term;
                        if (mode === 'SEMI-MONTHLY') {
                            totalPayments = term * 2;
                        } else if (mode === 'WEEKLY') {
                            totalPayments = term * 4;
                        }
                        
                        var principalPerPayment = principal / totalPayments;
                        var interestPerPayment = interest / totalPayments;
                        var totalPerPayment = principalPerPayment + interestPerPayment;
                        
                        // Update summary display safely
                        var summaryPrincipal = document.getElementById('renewalSummaryPrincipal');
                        if (summaryPrincipal) summaryPrincipal.value = principal.toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                        var summaryInterest = document.getElementById('renewalSummaryInterest');
                        if (summaryInterest) summaryInterest.value = interest.toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                        var summaryTotal = document.getElementById('renewalSummaryTotal');
                        if (summaryTotal) summaryTotal.value = total.toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                        // Update amortization display safely
                        var amortPrincipal = document.getElementById('renewalAmortizationPrincipal');
                        if (amortPrincipal) amortPrincipal.value = principalPerPayment.toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                        var amortInterest = document.getElementById('renewalAmortizationInterest');
                        if (amortInterest) amortInterest.value = interestPerPayment.toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                        var amortTotal = document.getElementById('renewalAmortizationTotal');
                        if (amortTotal) amortTotal.value = totalPerPayment.toLocaleString('en-US', {minimumFractionDigits: 2});
                        
                    } else {
                        // Reset all displays to 0.00 safely
                        var fields = [
                            'renewalSummaryPrincipal', 'renewalSummaryInterest', 'renewalSummaryTotal',
                            'renewalAmortizationPrincipal', 'renewalAmortizationInterest', 'renewalAmortizationTotal'
                        ];
                        
                        fields.forEach(function(fieldId) {
                            var field = document.getElementById(fieldId);
                            if (field) field.value = '0.00';
                        });
                    }
                } catch (error) {
                    console.error('Error calculating renewal summary:', error);
                }
            }

            function submitRenewal() {
                console.log('Submitting renewal application...');
                console.log('Calculated renewal interest:', document.getElementById('renewalSummaryInterest').value);
                
                // Get form data
                var formData = {
                    action: 'saveLoanRenewal',
                    userId: document.getElementById('renewalClientId').value,
                    previousLoanId: document.getElementById('renewalLoanId').value,
                    loanType: document.getElementById('renewalLoanType').value,
                    tag: document.getElementById('renewalTag').value,
                    poFco: document.getElementById('renewalPoFco').value,
                    program: document.getElementById('renewalProgram').value,
                    product: document.getElementById('renewalProduct').value,
                    mode: document.getElementById('renewalMode').value,
                    termRate: document.getElementById('renewalTermRate').value,
                    rate: document.getElementById('renewalRate').value,
                    availment: document.getElementById('renewalAvailment').value,
                    amount: document.getElementById('renewalAmount').value,
                    downPaymentAmount: document.getElementById('renewalDownPaymentAmount').value,
                    interest: document.getElementById('renewalSummaryInterest').value.replace(/,/g, ''), // Add calculated interest
                    inventory_si: document.getElementById('renewalInventorySI').value // Add inventory SI for linking
                };
                
                // Validate required fields
                if (!formData.userId || !formData.poFco || !formData.program || !formData.product || 
                    !formData.mode || !formData.termRate || !formData.amount) {
                    alert('Please fill in all required fields.');
                    return;
                }
                
                console.log('Renewal form data:', formData);
                
                // Submit via AJAX
                fetch('/iSynApp-main/routes/accountsmonitoring/loantransaction.route.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Renewal response:', data);
                    
                    if (data.success) {
                        // Update the loan ID in the success modal
                        document.getElementById('successLoanId').textContent = data.loanId || 'Renewal Generated';
                        
                        // Hide the renewal modal first
                        $('#renewalLoanModal').modal('hide');
                        
                        // Show the success modal after a brief delay
                        setTimeout(function() {
                            $('#loanSuccessModal').modal('show');
                        }, 300);
                        
                        // Close modal
                        $('#renewalLoanModal').modal('hide');
                        
                        // Refresh loan list
                        if (currentClientId) {
                            loadClientLoans(currentClientId);
                        }
                        
                        // Reset form
                        resetRenewalForm();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to submit renewal'));
                    }
                })
                .catch(error => {
                    console.error('Renewal submission error:', error);
                    alert('Error submitting renewal. Please try again.');
                });
            }

            // Modal event handlers for renewal form reset
            $('#renewalLoanModal').on('hidden.bs.modal', function () {
                console.log('Renewal modal closed, resetting form...');
                setTimeout(function() {
                    resetRenewalForm();
                }, 100); // Small delay to prevent freezing
            });

            // Reset when Cancel button is clicked
            $('#renewalLoanModal .btn-secondary').on('click', function() {
                console.log('Renewal cancel clicked, resetting form...');
                setTimeout(function() {
                    resetRenewalForm();
                }, 100); // Small delay to prevent freezing
            });

            // Success modal event handler - refresh client loans when closed
            $('#loanSuccessModal').on('hidden.bs.modal', function () {
                console.log('Success modal closed, refreshing client loans...');
                if (currentClientId) {
                    loadClientLoans(currentClientId);
                }
            });
        </script>
        
    </body>
</html>

<?php
    } else {
        header("Location: /iSynApp-main/login");
        exit;
    }
?>