<?php 
  if (session_status() == PHP_SESSION_NONE) {
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
  }
  if (isset($_SESSION['ID'])) {
        // Enforce RBAC
        require_once('../../includes/permissions.php');
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: ../../dashboard");
            exit;
        }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Board of Directors | iSynPro</title>
        
        <?php
            include('../../includes/pages.header.php');
        ?>

        <style>
            /* Custom Table Styles */
            .table-premium-container {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                padding: 1rem;
                overflow: hidden;
            }

            .table-premium-container table.table {
                margin-bottom: 0;
                border-collapse: separate;
                border-spacing: 0;
                width: 100% !important;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                overflow: hidden;
            }

            .table-premium-container table.table thead th {
                background-color: #3a57e8;
                color: #ffffff;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 16px;
                font-size: 0.8rem;
                vertical-align: middle;
                border: none;
                text-align: center;
                border-right: 1px solid rgba(255,255,255,0.2);
            }
            .table-premium-container table.table thead th:last-child {
                border-right: none;
            }

            .table-premium-container table.table tbody td {
                padding: 14px 16px;
                vertical-align: middle;
                color: #495057;
                font-size: 0.95rem;
                border-bottom: 1px solid #dee2e6;
                transition: background-color 0.2s;
                border-right: 1px solid #f0f0f0;
            }
            .table-premium-container table.table tbody td:last-child {
                border-right: none;
            }

            /* Hover Effect */
            .table-premium-container table.table tbody tr:hover td {
                background-color: #f8f9fa;
                color: #3a57e8;
            }


            /* Search Box Styling */
            .search-group {
                position: relative;
                width: 300px;
            }
            
            .search-group .form-control {
                border-radius: 20px;
                padding-left: 40px;
                border: 1px solid #e0e0e0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.02);
                transition: all 0.3s ease;
            }

            .search-group .form-control:focus {
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

    </head>
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

            <div class="container-fluid mt-1">
                <div class=" p-3 shadow rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2"><i class="fa-solid fa-users-gear me-2"></i>Board of Directors and Committee</p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12 mt-2">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Board of Directors</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Committee</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Board of Directors List</h5>
                                                    <small class="text-muted">View board members by year</small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button id="openBODReportModalBtn" class="btn btn-primary btn-sm px-3 shadow-sm rounded-pill" type="button">
                                                        <i class="fa-solid fa-file-export me-1"></i> Generate Report
                                                    </button>
                                                </div>
                                            </div>
                                            <hr style="height: 1px">
                                            <div class="table-premium-container mb-3"> <!-- Added mb-3 to prevent overlap -->
                                                <table id="bodTbl" class="table table-bordered text-center" style="width:100%;">
                                                    <thead>
                                                        <tr>
                                                            <th style="display:none;"></th>
                                                            <th style="text-align: center !important">Fullname</th>
                                                            <th>Designation</th>
                                                            <th>From</th>
                                                            <th>To</th>
                                                            <th style="width:15%">Date Encoded</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="bodList">

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h5 class="fw-bold mb-0 text-dark">Committee List</h5>
                                                    <small class="text-muted">View committee members by year</small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button id="openCommitteeReportModalBtn" class="btn btn-primary btn-sm px-3 shadow-sm rounded-pill" type="button">
                                                        <i class="fa-solid fa-file-export me-1"></i> Generate Report
                                                    </button>
                                                </div>
                                            </div>
                                            <hr style="height: 1px">
                                            <div class="table-premium-container mb-3"> <!-- Added mb-3 -->
                                                <table id="committeeTbl" class="table table-bordered text-center" style="width:100%;">
                                                    <thead>
                                                        <tr>
                                                            <th style="display: none;"></th>
                                                            <th style="text-align: center !important">Fullname</th>
                                                            <th>Designation</th>
                                                            <th style="width: 15%;">Committee Type</th>
                                                            <th>Specialized Position</th>
                                                            <th>From</th>
                                                            <th>To</th>
                                                            <th style="width:15%">Date Encoded</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="committeeList">
                                                        
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                

                <div class="row mt-4">
                    <div class="col-md-12">
                        <form id="bodForm" method="POST" class="p-3 needs-validation shadow mb-3 mb-4" autocomplete="off" novalidate style="background-color: white;"> <!-- Added novalidate -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">Board Member Information</h5>
                                    <small class="text-muted">Enter board of director details</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success mx-2" id="addNew" type="button" name="new" > <i class="fa-solid fa-plus"></i>  New</button>
                                    <button  class="btn btn-primary mx-2" id="editButton" class="btn btn-primary" type="button" disabled><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                </div>
                            </div>
                            <hr class="mb-4">
                            <input type="hidden" id="boardID" name="boardID" readonly>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label class="form-label fw-bold small text-uppercase text-secondary">Shareholder Name</label>
                                        <input type="hidden" id="shareholderID" name="shareholderID" class="form-control" disabled>
                                        <input type="text" class="form-control" id="shareholderName" name="shareholderName" placeholder="SELECT SHAREHOLDER NAME" autocomplete="off" disabled style="text-transform: uppercase;" required> 
                                        <div class="invalid-feedback">Shareholder Name is required</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label for="region" class="form-label">Designation</label>
                                    <select class="form-select" name="BODdesignation" id="BODdesignation" aria-label="Default select example" disabled required>
                                        <option value="" selected>SELECT</option>
                                    
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="province" class="form-label">Committee Type</label>
                                    <select class="form-select" name="committeeType" id="committeeType" aria-label="Default select example" disabled >
                                        <option value="" selected>SELECT</option>
                                        
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="province" class="form-label">Specialized Position</label>
                                    <select class="form-select" name="specializedposition" id="specializedposition" aria-label="Default select example" disabled >
                                        <option value="" selected>SELECT</option>
                                    
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <p class=" fw-medium fs-5 mt-3" style="color: #090909;">Period of Assignment</p>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label">From:</label>
                                    <input type="text" name="fromdate" class="form-control date" id="fromdate" placeholder="MM/DD/YYYY" required disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">To:</label>
                                    <input type="text" name="toDate" class="form-control date" id="toDate" placeholder="MM/DD/YYYY" required disabled>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 text-end mt-3">
                                    <button id="updateButton" disabled class="btn btn-primary mx-2 float-end" style="display: none;" type="submit" form="bodForm"><i class="fa-solid fa-rotate-right"></i> Update</button>

                                    <button id="submitButton" disabled class="btn btn-primary mx-2 float-end" type="submit" form="bodForm"><i class="fa-solid fa-check-circle"></i> Submit</button>

                                    <button class="btn btn-danger float-end" type="button" id="cancel"  disabled hidden onclick="Cancel();"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <!-- BOD Report Generation Modal -->
        <div class="modal fade" id="SelectBODReportMDL" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Generate Board of Directors Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- NEW: Year Filter -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Year</label>
                            <select class="form-select" id="slctYearBODModal">
                                <option value="All">ALL YEARS</option>
                                <!-- Populated by JavaScript -->
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
                        <button type="button" class="btn btn-primary rounded-pill" id="confirmBODReportBtn">
                            <i class="fa-solid fa-download me-1"></i> Generate
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Committee Report Generation Modal -->
        <div class="modal fade" id="SelectCommitteeReportMDL" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Generate Committee Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- NEW: Year Filter -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Year</label>
                            <select class="form-select" id="slctYearCmmttModal">
                                <option value="All">ALL YEARS</option>
                                <!-- Populated by JavaScript -->
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
                        <button type="button" class="btn btn-primary rounded-pill" id="confirmCommitteeReportBtn">
                            <i class="fa-solid fa-download me-1"></i> Generate
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../js/profiling/bod.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>}
?>