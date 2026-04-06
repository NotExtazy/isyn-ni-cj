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
      <link rel="stylesheet" href="../../assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="../../assets/select2/css/select2.min.css">

    <body class="  ">
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <style>
            label { color: #090909; }
            form { width: 100%; padding: 20px; background-color: white; border-radius: 10px; }
            main { background-color: #EAEAF6; }
            th { font-weight: bold; color: #090909; z-index: 10; }
            
            .custom-input { border: none; border-bottom: .1px solid gray; outline: none; width: 85px; text-align: center; margin-top: 20px; }
            .custom-input:focus { border-bottom: 2px solid #0D6EFD; }
            .selected td { background-color: lightgray; } 
            
            /* --- CSS FOR PHONE INPUT --- */
            .custom-phone-group {
                display: flex; align-items: center; border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.375rem 0.75rem; background-color: #e9ecef; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }
            .custom-phone-group:not(.disabled-group) { background-color: #fff; }
            .custom-phone-group:focus-within { border-color: #86b7fe; outline: 0; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
            .custom-prefix { color: #495057; margin-right: 5px; pointer-events: none; user-select: none; }
            .custom-phone-input { border: none; outline: none; width: 100%; background: transparent; color: #212529; padding: 0; }
            .custom-phone-group.is-invalid { border-color: #dc3545; }

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
            #SupplierInfoTbl {
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
            #SupplierInfoTbl tbody td {
                padding: 14px 16px;
                vertical-align: middle;
                border-bottom: 1px solid #f1f1f1;
                border-right: 1px solid #f1f1f1;
                color: #555;
                font-size: 0.95rem;
                transition: background-color 0.2s;
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
            #SupplierInfoTbl tbody td:last-child {
                border-right: none;
            }
            /* Specific Column Formatting (No Wrap) */
            #SupplierInfoTbl tbody td:nth-child(1), /* Supplier No */
            #SupplierInfoTbl tbody td:nth-child(4), /* Contact No */
            #SupplierInfoTbl tbody td:nth-child(5)  /* Date */
            {
                white-space: nowrap;
            }

            #SupplierInfoTbl tbody tr:hover td {
                background-color: #f8f9fa; /* Light grey hover */
                color: #2c3e50;
            }
            #SupplierInfoTbl tbody tr:last-child td {
                border-bottom: none;
            }
            /* Rounded corners for header */
            #SupplierInfoTbl thead th:first-child { border-top-left-radius: 10px; }
            #SupplierInfoTbl thead th:last-child { border-top-right-radius: 10px; }
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
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

            <div class="container-fluid mt-1">
                <div class=" shadow p-3 rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Supplier's Information</p>
                </div>
                <div class="row mt-4 ">
                    <div class="col-md-12">
                        <div class="shadow p-3 rounded-3 table-container" style="background-color: white;">
                            <div class="align-items-center justify-content-between mb-3 d-flex">
                                <p class="fw-medium fs-5 mb-0" style="color: #090909;">Supplier's List</p>
                                <div class="d-flex gap-2">
                                     <div class="input-group input-group-sm search-group" style="width: 250px;">
                                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                        <input type="text" class="form-control" id="supplierSearch" placeholder="Search" autocomplete="off">
                                    </div>
                                    <button class="btn btn-primary btn-sm px-3 shadow-sm rounded-pill" id="reportButton" type="button">
                                        <i class="fa-solid fa-file-export me-1"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <div class="table-premium-container">
                                <table id="SupplierInfoTbl" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Supplier No.</th>
                                            <th style="width: 33%;">Supplier Name</th>
                                            <th style="width: 15%;">TIN</th>
                                            <th style="width: 25%;">Contact No.</th>
                                            <th style="width: 15%; border-top-right-radius: 10px;">Date Encoded</th>
                                            <th class="d-none">Address</th>                                     
                                        </tr>
                                    </thead>
                                    <tbody id="SupplierInfoList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <form class="p-3 needs-validation mb-3 shadow" novalidate method="POST" id="supplierInfo" autocomplete="off">
                            <div class="align-items-center justify-content-between mb-3">
                                <button class="btn btn-primary float-end mx-2" id="editButton" type="button" disabled><i class="fa-solid fa-pen-to-square" ></i> Edit</button>
                                <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="new"> <i class="fa-solid fa-plus"></i> New</button>
                                <p class=" fw-medium fs-5" style="color: #090909;">Supplier's Information</p>
                            </div>
                            <hr style="height: 1px">
                            
                            <!-- Row 1: Basic IDs & Name -->
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="supplierNo" class="form-label">Supplier No.</label>
                                    <input type="text" class="form-control" id="supplierNo" name="supplierNo" readonly>
                                    <div class="invalid-feedback">Please provide your Supplier No.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="supplierName" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="supplierName" name="supplierName" placeholder="Supplier's Name" required disabled oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s.,'&]/g, '')" maxlength="100">
                                    <div class="invalid-feedback">Please provide Company Name.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="digit" class="form-label">TIN</label>
                                    <input type="text" id="tin" name="tin" class="form-control tin-field" placeholder="###-###-###-###" maxlength="20" disabled>
                                    <div class="invalid-feedback">TIN is required.</div>
                                </div>
                            </div>

                            <!-- Row 2: Contact Info -->
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label for="mobileNumber" class="form-label">Mobile No.</label>
                                    <input type="text" class="form-control" id="mobileNumber" name="mobileNumber" placeholder="09*********" maxlength="11" disabled>
                                    <div class="invalid-feedback">Mobile or Telephone is required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="telNumber" class="form-label">Telephone No.</label>
                                    <input type="text" class="form-control" id="telNumber" name="telNumber" placeholder="02 ****-****" minlength="13" maxlength="15" disabled>
                                    <div class="invalid-feedback">Mobile or Telephone is required.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="text" class="form-control" id="email" name="email" placeholder="example@gmail.com" disabled pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address (e.g., user@example.com)" maxlength="50">
                                    <div class="invalid-feedback">Enter your email address.</div>
                                </div>
                            </div>

                            <!-- Row 3: Social & Date -->
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label for="facebookAccount" class="form-label">Social Link</label>
                                    <input type="text" class="form-control" id="facebookAccount" name="facebookAccount" placeholder="https://www.link.com/Company" disabled maxlength="100">
                                    <div class="invalid-feedback">Please provide your Valid Social Link.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="supplierSince" class="form-label">Supplier Since</label>
                                    <input type="text" class="form-control" id="supplierSince" name="supplierSince" placeholder="MM/DD/YYYY" autocomplete="off" disabled>
                                    <div class="invalid-feedback">Supplier Since date is required.</div>
                                </div>
                            </div>

                            <hr style="height:1px;">
                            
                            <p class="fw-medium fs-5" style="color: #090909;">Address</p>

                            <!-- Address Row 1 -->
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="Region" class="form-label">Region</label>
                                    <select class="form-select" id="Region" name="Region" aria-label="Default select example" required disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Please select region.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Province" class="form-label">Province</label>
                                    <select class="form-select" id="Province" name="Province" aria-label="Default select example" required disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Please select province.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="CityTown" class="form-label">City/Town</label>
                                    <select class="form-select" id="CityTown" name="CityTown" aria-label="Default select example" required disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Please select CityTown.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Barangay" class="form-label">Barangay</label>
                                    <select class="form-select" id="Barangay" name="Barangay" aria-label="Default select example" required disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Please select barangay.</div>
                                </div>
                            </div>

                            <!-- Address Row 2 -->
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <label for="street" class="form-label">Street/House No./ Zone</label>
                                    <input type="text" class="form-control" id="street" name="street" placeholder="Street/House No./ Zone" disabled oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s.,-]/g, '')" maxlength="50">
                                    <div class="invalid-feedback">Please enter street.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 mt-3">
                                    <button id="updateButton" disabled class="btn btn-primary mx-2 float-end" style="display: none;" type="button" form="customerInfo" ><i class="fa-solid fa-upload"></i>Update</button>

                                    <button id="submitButton" name = "submitButton" disabled class="btn btn-primary mx-2 float-end" type="button" form="customerInfo"><i class="fa-solid fa-check-circle"></i> Submit</button>
                                    
                                    <button class="btn btn-danger float-end" type="button" id="cancel"  disabled hidden onclick="Cancel();"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Modal -->
                <div class="modal fade" id="reportFilterModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Generate Report</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-muted mb-4">Select the report format you wish to generate.</p>
                                
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

            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../js/profiling/common_validation.js?<?= time() ?>"></script>
        <script src="../../js/profiling/supplierinfo.js?v=<?= time() ?>"></script>

    </body>
</html>
<?php
    } else {
        echo '<script> window.location.href = "../../login"; </script>';
    }
?>
?>
