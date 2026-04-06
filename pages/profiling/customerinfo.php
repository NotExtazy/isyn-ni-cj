<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once('includes/permissions.php');
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: /dashboard");
            exit;
        }
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="assets/select2/css/select2.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

    <body class="">
        <div id="loading">
            <div class="loader simple-loader">
                <div class="loader-body"></div>
            </div>
        </div>

        <style>
            td { font-weight: 400; }
            form { width: 100%; padding: 20px; background-color: white; border-radius: 10px; }
            label, th { color: #090909; }
            main { background-color: #EAEAF6; }
            
            /* Sticky Header */
            th { font-weight: bold; color: #090909; background-color: #f8f9fa; z-index: 10; }
            
            /* IMPORTANT: This fixes the DataTable column count error */
            .d-none { display: none !important; } 

            .selected td { background-color: lightgray !important; } 
            .email-invalid { border: 2px solid red !important; }
            .email-error-message { color: red; font-size: 0.875rem; margin-top: 0.25rem; display: none; }
            .email-error-message.show { display: block; }

            /* Standardized Input Spacing */
            .form-label { margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; }
            .form-control, .form-select { font-size: 0.95rem; }
            .mb-3 { margin-bottom: 1rem !important; }
            
            /* Validation Styles */
            .is-invalid { border-color: #dc3545 !important; }
            .invalid-feedback { 
                display: none; 
                width: 100%; 
                margin-top: 0.25rem; 
                font-size: 0.875em; 
                color: #dc3545; 
            }

            /* Search Group Style */
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

            .dataTables_filter { display: none; }

            /* Premium Table Styles */
            .table-premium-container {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05); /* Soft shadow */
                overflow: hidden; /* For rounded corners */
                margin-top: 1rem;
            }
            #CustomerInfoTbl {
                margin-bottom: 0;
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: fixed;
            }
            .table-premium-container thead th {
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
            }
            .table-premium-container thead th:last-child {
                border-right: none;
            }
            #CustomerInfoTbl tbody td {
                padding: 14px 16px;
                vertical-align: middle;
                border-bottom: 1px solid #f1f1f1;
                border-right: 1px solid #f1f1f1;
                color: #555;
                padding: 14px 16px;
                vertical-align: middle;
                border-bottom: 1px solid #f1f1f1;
                color: #555;
                font-size: 0.95rem;
                transition: background-color 0.2s;
                white-space: normal;
                word-wrap: break-word;
                word-break: break-word;
                text-align: center;
            }
            #CustomerInfoTbl tbody td:last-child {
                border-right: none;
            }
            /* Specific Column Formatting (No Wrap) */
            #CustomerInfoTbl tbody td:nth-child(1), /* Customer No */
            #CustomerInfoTbl tbody td:nth-child(4), /* Mobile */
            #CustomerInfoTbl tbody td:nth-child(6)  /* Date */
            {
                white-space: nowrap;
            }

            #CustomerInfoTbl tbody tr:hover td {
                background-color: #f8f9fa; /* Light grey hover */
                color: #2c3e50;
            }
            #CustomerInfoTbl tbody tr:last-child td {
                border-bottom: none;
            }
            /* Rounded corners for header */
            #CustomerInfoTbl thead th:first-child { border-top-left-radius: 10px; }
            #CustomerInfoTbl thead th:last-child { border-top-right-radius: 10px; }
            /* Report Option Cards */
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
        </style>

        <?php
            include('includes/pages.sidebar.php');
            include('includes/pages.navbar.php');
        ?>

            <div class="container-fluid mt-1">
                <div class="shadow rounded-3 p-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2 "><i class="fa-solid fa-file-contract me-2"></i>Customer Information</p>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="shadow p-3 rounded-3 table-container" style="background-color: white;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <p class="fw-medium fs-5 mb-0" style="color: #090909;">Customer List</p>
                                <div class="d-flex gap-2">
                                    <div class="input-group input-group-sm search-group" style="width: 250px;">
                                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                        <input type="text" class="form-control" id="customerSearch" placeholder="Search" autocomplete="off">
                                    </div>
                                    <button class="btn btn-primary btn-sm px-3 shadow-sm rounded-pill" id="reportButton" type="button">
                                        <i class="fa-solid fa-file-export me-1"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <div class="table-premium-container">
                                <table id="CustomerInfoTbl">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width:10%;"><div class="w-100 text-center">Customer No.</div></th>
                                            <th class="text-center" style="width:30%; text-align: center;">Name</th>
                                            <th class="text-center" style="width:15%;"><div class="w-100 text-center">Customer Type</div></th>
                                            <th style="width:15%;">Mobile Number</th>
                                            <th class="text-center" style="width:20%; text-align: center;">Email</th>
                                            <th style="width:10%; border-top-right-radius: 10px;">Date Encoded</th>
                                            <th class="d-none">Address</th>
                                        </tr>
                                    </thead>
                                    <tbody id="CustomerInfoList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <form class="p-3 needs-validation shadow mb-3" novalidate method="post" id="customerinfo" autocomplete="off">
                            
                            <div class="align-items-center justify-content-between mb-4">
                                <button class="btn btn-primary float-end mx-2" id="editButton" name="editButton" type="button" disabled><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="addNew"> <i class="fa-solid fa-plus"></i> New</button>
                                <p class="fw-medium fs-5" style="color: #090909;">Customer Information</p>
                            </div>
                            <hr style="height: 1px">
                            <input type="hidden" id="customerID" name="customerID" value="">
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="customerNo" class="form-label">Customer No</label>
                                    <input type="text" name="customerNo" class="form-control" id="customerNo" placeholder="Customer No" disabled>
                                </div>
                                <div class="col-md-3">
                                    <label for="customerType" class="form-label">Customer Type</label>
                                    <select class="form-select" name="customerType" id="customerType" required disabled>
                                        <option value="" selected disabled>Select</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="companyName" class="form-label">Company Name</label>
                                    <input type="text" name="companyName" id="companyName" class="form-control" placeholder="Company Name" disabled>
                                    <div class="invalid-feedback">Please enter a valid company name</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="firstName" class="form-label">First name</label>
                                    <input type="text" name="firstName" class="form-control" id="firstName" placeholder="First Name" required disabled>
                                    <div class="invalid-feedback">Please enter a valid first name</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="middleName" class="form-label">Middle name</label>
                                    <input type="text" name="middleName" class="form-control" id="middleName" placeholder="Middle Name" disabled>
                                    <div class="invalid-feedback">Please enter a valid middle name</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="lastName" class="form-label">Last name</label>
                                    <input type="text" name="lastName" class="form-control" id="lastName" placeholder="Last Name" required disabled>
                                    <div class="invalid-feedback">Please enter a valid last name</div>
                                </div>
                                <div class="col-md-2">
                                    <label for="suffix" class="form-label">Suffix</label>
                                    <select class="form-select" name="suffix" id="suffix" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="birthdate" class="form-label">Birthdate</label>
                                    <input type="text" name="birthdate" class="form-control" id="birthdate" placeholder="MM/DD/YYYY" autocomplete="off" disabled>
                                    <div class="invalid-feedback">Please enter a valid birthdate</div>
                                </div>
                                <div class="col-md-2">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="text" name="age" class="form-control" id="age" disabled>
                                    <div class="invalid-feedback">Please enter a valid age</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Please select gender</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="mobileNumber" class="form-label">Mobile Number</label>
                                    <input type="text" name="mobileNumber" class="form-control" id="mobileNumber" placeholder="09*********" disabled maxlength="11">
                                    <div class="invalid-feedback">Please enter a valid mobile number</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="exmpl@gmail.com" disabled>
                                    <div class="invalid-feedback">Please enter a valid email address</div>
                                    <div class="email-error-message" id="emailError" style="display:none; color:red; font-size:0.875em; margin-top:0.25rem;">Invalid email address</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="tin" class="form-label">TIN</label>
                                    <input type="text" id="tin" name="tin" class="form-control tin-field" maxlength="15" placeholder="###-###-###-###" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label for="clientSince" class="form-label">Client Since</label>
                                    <input type="text" class="form-control" id="clientSince" name="clientSince" placeholder="MM/DD/YYYY" autocomplete="off" disabled>
                                    <div class="invalid-feedback">Please select a valid date.</div>
                                </div>
                            </div>

                            <hr style="height: 1px;">
                            <p class="fw-medium heading fs-5">Address</p>
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="Region" class="form-label">Region</label>
                                    <select class="form-select" id="Region" name="Region" disabled><option value="" selected>Select</option></select>
                                    <div class="invalid-feedback">Region is required</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Province" class="form-label">Province</label>
                                    <select class="form-select" id="Province" name="Province" disabled><option value="" selected>Select</option></select>
                                    <div class="invalid-feedback">Province is required</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="CityTown" class="form-label">City/Town</label>
                                    <select class="form-select" id="CityTown" name="CityTown" disabled><option value="" selected>Select</option></select>
                                    <div class="invalid-feedback">City/Town is required</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Barangay" class="form-label">Barangay</label>
                                    <select class="form-select" id="Barangay" name="Barangay" disabled><option value="" selected>Select</option></select>
                                    <div class="invalid-feedback">Barangay is required</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="street" class="form-label">Street/House No./ Zone</label>
                                    <input type="text" class="form-control" id="street" name="street" placeholder="Street/House No./Zone" disabled>
                                    <div class="invalid-feedback">Please enter street.</div>
                                </div>
                            </div>

                            <hr style="height: 1px;">
                            <p class="fw-medium heading fs-5">Product Information</p>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="productInfo" class="form-label">Product/Services Availed</label>
                                    <input type="text" class="form-control" id="productInfo" name="productInfo" placeholder="Product/Service" disabled>
                                    <div class="invalid-feedback">Please enter product/service.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 mt-3">
                                    <button id="updateButton" class="btn btn-primary mx-2 float-end" style="display: none;" type="button" disabled><i class="fa-solid fa-upload"></i> Update</button>
                                    <button id="submitButton" class="btn btn-primary mx-2 float-end" type="button" disabled><i class="fa-solid fa-check-circle"></i> Submit</button>
                                    <button class="btn btn-danger float-end" type="button" id="cancel" hidden onclick="Cancel();"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="reportFilterModal" tabindex="-1" aria-labelledby="reportFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold" id="reportFilterModalLabel">Generate Report</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-4">
                                <label for="reportCustomerType" class="form-label fw-bold">Customer Type</label>
                                <select class="form-select" id="reportCustomerType" name="reportCustomerType">
                                    <option value="ALL" selected>ALL</option>
                                    <!-- Options populated by JS -->
                                </select>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="report-option-card" data-format="pdf">
                                        <i class="fa-regular fa-file-pdf text-danger"></i>
                                        <h6 class="fw-bold mb-0">PDF</h6>
                                        <small class="text-muted">Printable format</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="report-option-card" data-format="excel">
                                        <i class="fa-regular fa-file-excel text-success"></i>
                                        <h6 class="fw-bold mb-0">Excel</h6>
                                        <small class="text-muted">Spreadsheet format</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary rounded-pill" id="confirmReportBtn">
                                <i class="fa-solid fa-download me-1"></i> Generate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        
        <?php
            include('includes/pages.footer.php');
        ?>

        <script src="assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="assets/select2/js/select2.full.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="js/profiling/common_validation.js?<?= time() ?>"></script>
        <script src="js/profiling/customerinfo.js?<?= time() ?>"></script>
        
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "/login"; </script>';
  }
?>}
?>