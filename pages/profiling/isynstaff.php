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
            td { font-weight: 400; }
            form { width: 100%; padding: 20px; background-color: white; border-radius: 10px; }
            label, th { color: #090909; }
             main { background-color: #EAEAF6; }
            th { font-weight: bold; color: #090909; }
            .selected td { background-color: lightgray; } 

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
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                overflow: hidden;
                margin-top: 1rem;
            }
            #staffTbl {
                margin-bottom: 0;
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: fixed; 
            }
            #staffTbl thead th {
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
            }
            #staffTbl thead th:last-child {
                border-right: none;
            }
            #staffTbl tbody td {
                padding: 14px 16px;
                vertical-align: middle;
                border-bottom: 1px solid #f1f1f1;
                border-right: 1px solid #f1f1f1;
                color: #555;
                font-size: 0.95rem;
                transition: background-color 0.2s;
                white-space: normal;
                word-wrap: break-word;
            }
            #staffTbl tbody td:last-child {
                border-right: none;
            }
            #staffTbl tbody tr:hover td {
                background-color: #f8f9fa;
                color: #2c3e50;
            }
            #staffTbl tbody tr:last-child td {
                border-bottom: none;
            }
            #staffTbl thead th:first-child { border-top-left-radius: 10px; }
            #staffTbl thead th:last-child { border-top-right-radius: 10px; } 

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
                <div class=" p-3 shadow rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="my-2 fs-5">iSynergies Employee</p>
                </div>
                <div class="row mt-4 mb-3">
                    <div class="col-md-12">
                        <div class=" shadow p-3 rounded-3  " style="background-color: white;">
                            <div class="align-items-center justify-content-between mb-3 d-flex">
                                <p class="fw-medium fs-5 mb-0" style="color: #090909;">Staff List</p>
                                <div class="d-flex gap-2">
                                     <div class="input-group input-group-sm search-group" style="width: 250px;">
                                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                        <input type="text" class="form-control" id="staffSearch" placeholder="Search" autocomplete="off">
                                    </div>
                                    <button class="btn btn-primary btn-sm px-3 shadow-sm rounded-pill" id="reportButton" type="button" data-bs-toggle="modal" data-bs-target="#reportFilterModal">
                                        <i class="fa-solid fa-file-export me-1"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <table id="staffTbl" class="table table-bordered text-center" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th style="width:15%;text-align:center">Employee No.</th>
                                        <th style="width:25%;text-align:center !important">Employee Name</th>
                                        <th style="width:20%;text-align:center">Employee Status</th>
                                        <th style="width:25%;text-align:center">Designation</th>
                                        <th style="width:15%;text-align:center">Date Encoded</th>
                                    </tr>
                                </thead>
                                <tbody id="staffList"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
                <div class="row mt-4">
                    <div class="col-md-12">
                        <form class="p-3 needs-validation shadow mb-3" id="staffForm" novalidate method="POST">
                            <input type="hidden" name="id_staff" id="id_staff">
                            <div class=" align-items-center justify-content-between mb-4">
                                <button class="btn btn-primary float-end mx-2" id="editButton" type="button" disabled><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="addNew" > <i class="fa-solid fa-plus"></i> New</button>

                                <p class="fw-medium fs-5" style="color: #090909;">Employee Information</p>
                            </div>
                            <hr style="height:1px;">
                            <input type="hidden" id="editMode" name="editMode" value="">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="employee_no" class="form-label">Employee No.</label>
                                    <input disabled type="text" class="form-control text-uppercase" name="employee_no" id="employee_no" placeholder="Employee No." required>
                                    <div class="invalid-feedback">Employee No. is required.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="employee_status" class="form-label">Employee Status</label>
                                    <select class="form-select" name="employee_status" id="employee_status" aria-label="Default select example"  disabled>
                                        <option value="" selected>Select Employee Status</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a status.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="date_hired" class="form-label">Date Hired</label>
                                    <input disabled type="text" class="form-control" name="date_hired" id="date_hired" placeholder="MM/DD/YYYY" required>
                                    <div class="invalid-feedback">Invalid Date</div>
                                </div>
                            </div>
            
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input disabled type="text" class="form-control" name="first_name" id="first_name" placeholder="First Name" required>
                                    <div class="invalid-feedback">Please provide your first name.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input disabled type="text" class="form-control" name="middle_name" id="middle_name" placeholder="Middle Name">
                                    <div class="invalid-feedback">Please provide your Middle name.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input disabled type="text" class="form-control" name="last_name" id="last_name" placeholder="Last Name" required>
                                    <div class="invalid-feedback">Please provide your last name.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="birthdate" class="form-label">Birthdate</label>
                                    <input disabled type="text" class="form-control" name="birthdate" id="birthdate" placeholder="MM/DD/YYYY">
                                    <div class="invalid-feedback">Invalid Date</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control" name="age" id="age" disabled>
                                    <div class="invalid-feedback">Enter your Age.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="designation" class="form-label">Designation</label>
                                    <select disabled class="form-select" name="designation" id="designation" aria-label="Default select example" >
                                        <option selected >Select Designation</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a designation.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="email_address" class="form-label">Email Address</label>
                                    <input disabled type="email" class="form-control" name="email_address" id="email_address" placeholder="https://www.facebook.com/juan" >
                                    <div class="invalid-feedback">Please provide your Email Address.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_num" class="form-label">Contact No.</label>
                                    <input disabled type="text" class="form-control" name="contact_num" id="contact_num" placeholder="09*********" maxlength="11">
                                    <div class="invalid-feedback">Please provide a valid Contact.</div>
                                </div>
                                <div class="col-md-4 overflow-auto">
                                    <label for="pagibig" class="form-label">Pag-ibig Number</label>
                                    <div class="input-group">
                                        <input type="text" id="pag_ibig" name="pag_ibig" class="form-control tin-field" maxlength="14" disabled>
                                        <div class="invalid-feedback">Pag-ibig # is required.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 overflow-auto">
                                    <label for="tin" class="form-label">TIN</label>
                                    <div class="input-group">
                                        <input type="text" id="tin" name="tin" class="form-control tin-field" maxlength="15" disabled>
                                        <div class="invalid-feedback">TIN is required.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 overflow-auto">
                                    <label for="philhealth" class="form-label">PhilHealth</label>
                                    <div class="input-group">
                                        <input type="text" id="philhealth" name="philhealth" class="form-control philhealth-field" maxlength="14" disabled>
                                        <div class="invalid-feedback">PhilHealth is required.</div>
                                    </div>
                                </div>
                                <div class="col-md-4 overflow-auto">
                                    <label for="sss" class="form-label">SSS</label>
                                    <div class="input-group">
                                        <input type="text" id="sss" name="sss" class="form-control sss1-field" maxlength="12" disabled>
                                        <div class="invalid-feedback">SSS is required.</div>
                                    </div>
                                </div>
                            </div>
                            <hr style="height:1px;">
                            <p class=" fs-5" style="color: #090909;">Employee Address</p>
                            <div class="row">
                            <div class="col-md-3">
                                    <label for="Region" class="form-label">Region</label>
                                    <select class="form-select mb-2" id="Region" name="Region" aria-label="Default select example"  disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Province" class="form-label">Province</label>
                                    <select class="form-select" id="Province" name="Province" aria-label="Default select example"  disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="CityTown" class="form-label">City/Town</label>
                                    <select class="form-select" id="CityTown" name="CityTown" aria-label="Default select example"  disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="Barangay" class="form-label">Barangay</label>
                                    <select class="form-select" id="Barangay" name="Barangay" aria-label="Default select example"  disabled>
                                        <option value="" selected>Select</option>
                                    </select>
                                    <div class="invalid-feedback">Required.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="Street" class="form-label">Street/House No./ Zone</label>
                                    <input type="text" class="form-control" id="Street" name="Street" placeholder="Street/House No./Zone" oninput="this.value = this.value.toUpperCase();" disabled>
                                    <div class="invalid-feedback">Please enter street.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 float-end mt-3">
                                    <button id="updateButton" disabled class="btn btn-primary mx-2 float-end" style="display: none;" type="button" name="updateButton" form="staffForm"><i class="fa-solid fa-rotate-right"></i> Update</button>
                                    <button id="submitButton" disabled class="btn btn-primary mx-2 float-end" type="button" name="submitButton" form="staffForm"> <i class="fa-solid fa-check-circle"></i> Submit</button>
                                    <button class="btn btn-danger float-end" type="button" id="cancel" disabled hidden onclick="Cancel();"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                                </div>
                            </div>
                        </form>
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
                                <p class="text-muted mb-4">Select the report format you wish to generate for all staff.</p>
                                
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
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>
        
        <script src="../../js/profiling/common_validation.js?<?= time() ?>"></script>
        <script src="../../js/profiling/isynstaff.js?v=<?= time() ?>"></script>
        
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>}
?>