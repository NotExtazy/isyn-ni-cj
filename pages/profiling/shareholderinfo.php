<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once('../../includes/permissions.php');
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
        include('../../includes/pages.header.php');
    ?>
    <title>iSyn | Shareholder</title>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <body class="  ">
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <style>
            td { font-weight: 400; }
            form {
                padding: 20px;
                background-color: white;
                border-radius: 10px;
            }
            label, th { color: #090909; }
            main { background-color: #EAEAF6; }
            th {
                font-weight: bold;
                color: #090909;
                background-color: #fff;
            }
            .selected td { background-color: lightgray; } 

            /* Premium Table Styles */
            .table-premium-container {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                overflow: hidden;
                margin-top: 1rem;
            }
            #shareholderTbl {
                margin-bottom: 0;
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            #shareholderTbl thead th {
                /* Removed custom blue style */
                background-color: #3a57e8;
                color: #ffffff;
                padding: 16px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.8rem;
                letter-spacing: 0.5px;
                border: none;
                border-right: 1px solid rgba(255, 255, 255, 0.2);
                text-align: center;
                vertical-align: middle;
            }
            #shareholderTbl thead th:last-child {
                border-right: none;
            }
            #shareholderTbl tbody td {
                padding: 14px 16px;
                vertical-align: middle;
                border-bottom: 1px solid #dee2e6; /* Standard BS border color */
                border-right: 1px solid #f1f1f1;
                color: #212529; /* Standard text color */
                font-size: 0.95rem;
            }
            #shareholderTbl tbody td:last-child {
                border-right: none;
            }
            #shareholderTbl thead th:first-child { border-top-left-radius: 10px; }
            #shareholderTbl thead th:last-child { border-top-right-radius: 10px; }

            .report-option-card {
                border: 2px solid #e9ecef;
                border-radius: 10px;
                padding: 15px;
                cursor: pointer;
                transition: all 0.2s;
                text-align: center;
            }
            .report-option-card:hover, .report-option-card.active {
                border-color: #3a57e8;
                background-color: #f0f4ff;
                color: #3a57e8;
            }
            .report-option-card i {
                font-size: 2rem;
                margin-bottom: 10px;
                display: block;
            }

            /* Hide Default DataTable Search ONLY. Keep Length visible */
            .dataTables_filter { display: none; }
            .dataTables_length { float: left; margin-top: 10px; }
            .dataTables_wrapper .dataTables_paginate { float: right; margin-top: 10px; }
            .dataTables_wrapper .dataTables_info { float: left; margin-top: 15px; }

            /* Text Truncation */
            .text-truncate-cell {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 0;
            }

            /* Custom Search Style */
            .search-group {
                border-radius: 50px;
                border: 1px solid #dee2e6;
                overflow: hidden;
                background-color: white;
                transition: all 0.2s;
            }
            .search-group:focus-within {
                border-color: #3a57e8;
                box-shadow: 0 0 0 0.2rem rgba(58, 87, 232, 0.15);
            }
            .search-group .input-group-text {
                background-color: white !important;
                border: none !important;
                padding-left: 15px;
                color: #6c757d;
            }
            .search-group input {
                border: none !important;
                background-color: white !important;
            }
            .search-group input:focus {
                box-shadow: none !important;
            }
        </style>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

            <div class="container-fluid mt-1">
                <div class="shadow rounded-3 p-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Shareholder Information</p>
                </div>

                <div class="row mt-4 mb-2">
                    <div>
                        <div class="shadow p-3 rounded-3 table-container" style="background-color: white;">
                            
                            <style>
                                #shareholderTbl td {
                                    word-wrap: break-word;
                                    white-space: normal;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                            </style>
                            
                            <!-- Header / Toolbar -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Shareholder List</h5>
                                    <small class="text-muted">Manage system shareholders</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <!-- Search Input -->
                                    <div class="input-group input-group-sm search-group" style="width: 250px;">
                                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                        <input class="form-control" id="shNames" placeholder="Search by Name..." autocomplete="off">
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <button id="openReportModalBtn" class="btn btn-primary btn-sm px-3 shadow-sm rounded-pill" type="button">
                                        <i class="fa-solid fa-file-export me-1"></i> Generate Report
                                    </button>
                                    <button id="ConfigurationBtn" class="btn btn-light btn-sm px-3 shadow-sm rounded-pill border" type="button">
                                        <i class="fa-solid fa-gear me-1"></i> Config
                                    </button>
                                </div>
                            </div>

                            <table id="shareholderTbl" class="table table-bordered text-center" style="width:100%; table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th style="width:15%; text-align:center">ID</th>
                                        <th style="width:25%; text-align:center !important">Full Name</th>
                                        <th style="width:18%; text-align:center">Shareholder Type</th>
                                        <th style="width:12%; text-align:center">Type</th>
                                        <th style="width:15%; text-align:center">No. Of Shares</th>
                                        <th style="width:15%; text-align:center">Date Encoded</th>
                                    </tr>
                                </thead>
                                <tbody id="shareholderList"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <!-- Report Generation Modal -->
            <div class="modal fade" id="SelectReportMDL" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Generate Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">1. Select Filter</label>
                                <select class="form-select form-select-lg bg-light border-0" id="modalReportFilter">
                                    <option value="ALL">All Shareholders</option>
                                    <!-- Dynamic options will be appended here -->
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-secondary text-uppercase small">2. Select Format</label>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="report-option-card active" data-type="pdf" onclick="selectReportFormat(this, 'pdf')">
                                            <i class="fa-solid fa-file-pdf text-danger"></i>
                                            <div class="fw-bold">PDF Document</div>
                                            <small class="text-muted">Printable Format</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="report-option-card" data-type="excel" onclick="selectReportFormat(this, 'excel')">
                                            <i class="fa-solid fa-file-excel text-success"></i>
                                            <div class="fw-bold">Excel Spreadsheet</div>
                                            <small class="text-muted">Data Export</small>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="selectedReportFormat" value="pdf">
                            </div>

                            <div class="d-grid">
                                <button type="button" id="generateReportConfirmBtn" class="btn btn-primary btn-lg rounded-3 shadow-sm">
                                    Execute Export <i class="fa-solid fa-arrow-right ms-2"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
                
                <div class="row mt-4">
                    <div class="col-md-12 mb-2">
                        <form class="p-3 needs-validation shadow mb-4" novalidate method="POST" id="shareholderInfo">
                            <div class=" align-items-center justify-content-between mb-3">
                                <button type="button" name="printCert" id="printCert" class="btn btn-info px-3 py-2 float-end" onclick="PrintReport();" disabled><i class="fa fa-print"></i> Generate Certificate</button>
                                <button id="editButton" class="btn btn-primary  px-3 py-2 float-end mx-2" type="button"  disabled><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                <button id="addNew" class="btn btn-success  px-3 py-2 float-end mx-2" type="button"  ><i class="fa-solid fa-plus"></i> New</button>
                                <p class="fw-medium fs-5 mb-4" style="color: #090909;">Shareholder's Information</p>
                            </div>
                            <hr style="height: 1px">

                            <div class="row">
                                <div class="col-md-6">
                                    <input type="hidden" id="shareID" name="shareID" class="form-control" disabled>
                                </div>
                                <div class="col-md-6">
                                    <input type="hidden" id="actualNo" name="actualNo" class="form-control" readonly>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="shareholderID" class="form-label">Shareholder ID</label>
                                    <input type="text" class="form-control" id="shareholderID" name="shareholderID" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="cert_no" class="form-label">Issued Certificate No.</label>
                                    <input type="number" class="form-control" id="cert_no" name="cert_no"  disabled>
                                </div>
                                <div class="col-md-4 mt-5 d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Yes" name="president" id="president" disabled>
                                        <label class="form-check-label" for="president">President</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Yes" name="emp_resign" id="emp_resign" disabled>
                                        <label class="form-check-label" for="emp_resign">Emp-resigned</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="shareholderName" class="form-label mt-2">Shareholder Name</label>
                                    <input type="text" class="form-control searchName" id="shareholderName" name="shareholderName" placeholder="SHAREHOLDER NAME" required autocomplete="off" style="text-transform: uppercase;" disabled maxlength="100">
                                    <div class="invalid-feedback">Shareholder Name required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="tin" class="form-label mt-2">TIN</label>
                                    <input type="text" class="form-control" id="tin" name="tin" placeholder="000-000-000-000" disabled maxlength="20">
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_number" class="form-label mt-2">Contact No.</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" placeholder="09*********" maxlength="11" disabled>
                                    <div class="invalid-feedback">Invalid Contact No.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="email" class="form-label mt-2">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="example@gmail.com" disabled maxlength="50">
                                    <div class="invalid-feedback">Invalid Email.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="facebook_account" class="form-label mt-2">Facebook Link</label>
                                    <input type="url" class="form-control" id="facebook_account" name="facebook_account" placeholder="https://www.facebook.com/juan" disabled maxlength="100">
                                    <div class="invalid-feedback">Invalid Facebook Link.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="shareholder_type" class="form-label mt-2">Shareholder Type:</label>
                                    <select class="form-select" id="shareholder_type" name="shareholder_type" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label mt-2">Type of Shares:</label>
                                    <select class="form-select" id="type" name="type" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="noofshare" class="form-label mt-2">No of Shares:</label>
                                    <input type="number" class="form-control" id="noofshare" name="noofshare" required oninput="calculateAmount()" disabled>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="amount_share" class="form-label mt-2">Amount of Shares:</label>
                                    <input type="number" class="form-control" id="amount_share" name="amount_share" disabled>
                                </div>

                            </div>

                            <hr style="height: 1px;">
                            <p class="fw-medium fs-5 mb-3" style="color: #090909;">Address Information</p>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="Region" class="form-label">Region</label>
                                    <select class="form-select mb-2" id="Region" name="Region" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Province" class="form-label">Province</label>
                                    <select class="form-select" id="Province" name="Province" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="CityTown" class="form-label">City/Town</label>
                                    <select class="form-select" id="CityTown" name="CityTown" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Barangay" class="form-label">Barangay</label>
                                    <select class="form-select" id="Barangay" name="Barangay" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label for="street" class="form-label mt-2">Street/House No./Zone</label>
                                    <input type="text" class="form-control" id="street" name="street" placeholder="Street/House No./Zone" disabled maxlength="100">
                                    <div class="invalid-feedback">Address is required.</div>
                                </div>
                            </div>
                            <div class="row ">
                                <div class="col-12 text-end mt-3">
                                    <button id="updateButton" disabled class="btn btn-primary mx-2 float-end" style="display: none;" type="button" form="shareholderInfo"><i class="fa-solid fa-upload"></i>Update</button>
                                    <button id="submitButton" name = "submitButton" disabled class="btn btn-primary mx-2 float-end" type="button" form="shareholderInfo"><i class="fa-solid fa-check-circle"></i> Submit</button>
                                    <button class="btn btn-danger float-end" type="button" id="cancel" hidden disabled onclick="Cancel();"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="configurationMDL" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5">Configuration</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="configurationForm" method="POST">
                                <div class="form-group row"><label class="form-label">Signatory 1:</label><div class="col-sm-6"><input type="text" class="form-control form-control-sm" name="signatory1Name" id="signatory1Name"></div><div class="col-sm-4"><input type="text" class="form-control form-control-sm" name="signatory1Desig" id="signatory1Desig"></div></div>
                                <div class="form-group row mt-1"><label class="form-label">Signatory 2:</label><div class="col-sm-6"><input type="text" class="form-control form-control-sm" name="signatory2Name" id="signatory2Name"></div><div class="col-sm-4"><input type="text" class="form-control form-control-sm" name="signatory2Desig" id="signatory2Desig"></div></div>
                                <div class="form-group row mt-1"><label class="form-label">Signatory Sub 2:</label><div class="col-sm-6"><input type="text" class="form-control form-control-sm" name="signatorySub2Name" id="signatorySub2Name"></div><div class="col-sm-4"><input type="text" class="form-control form-control-sm" name="signatorySub2Desig" id="signatorySub2Desig"></div></div>
                                <div class="form-group row mt-3"><label class="form-label">Current Certificate No:</label><div class="col-sm-6"><input type="text" class="form-control form-control-sm" name="currentCertNo" id="currentCertNo"></div></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" id="updateConfigBtn" class="btn btn-primary"> Update</button>
                        </div>
                    </div>
                </div>
            </div>

        <?php include('../../includes/pages.footer.php'); ?>

        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        
        <!-- Common Validation Logic -->
        <script src="../../js/profiling/common_validation.js?<?= time() ?>"></script>
        
        <script src="../../js/profiling/shareholderinfo.js?<?= time() ?>"></script>
        
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>}
?>