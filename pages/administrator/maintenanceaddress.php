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
    <title>iSyn | Maintenance Address</title>
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
            
            .selected td { background-color: lightgray !important; } 

            /* Standardized Input Spacing */
            .form-label { margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; }
            .form-control, .form-select { font-size: 0.95rem; }
            .mb-3 { margin-bottom: 1rem !important; }

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
            #AddressTbl {
                margin-bottom: 0;
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: fixed;
            }
            .table-premium-container thead th {
                background-color: #3a57e8;
                color: #ffffff;
                padding: 16px 8px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.3px;
                border: none;
                border-right: 1px solid rgba(255, 255, 255, 0.2);
                text-align: center;
                line-height: 1.2;
            }
            .table-premium-container thead th:last-child {
                border-right: none;
            }
            #AddressTbl tbody td {
                padding: 10px 8px;
                vertical-align: middle;
                border-bottom: 1px solid #f1f1f1;
                border-right: 1px solid #f1f1f1;
                color: #555;
                font-size: 0.85rem;
                transition: background-color 0.2s;
                white-space: normal;
                word-wrap: break-word;
                word-break: break-word;
                text-align: center;
                line-height: 1.3;
                max-width: 150px;
                overflow-wrap: break-word;
            }
            #AddressTbl tbody td:last-child {
                border-right: none;
            }

            #AddressTbl tbody tr:hover td {
                background-color: #f8f9fa;
                color: #2c3e50;
            }
            #AddressTbl tbody tr:last-child td {
                border-bottom: none;
            }
            #AddressTbl thead th:first-child { border-top-left-radius: 10px; }
            #AddressTbl thead th:last-child { border-top-right-radius: 10px; }
            
            /* Bauble Toggle Design */
            .bauble_box {
                display: inline-block;
                vertical-align: middle;
            }
            
            .bauble_box .bauble_label {
                background: #c22;
                background-position: 42px 3px;
                background-repeat: no-repeat;
                background-size: auto 3px;
                border: 0;
                border-radius: 50px;
                box-shadow: inset 0 6px 12px rgba(0,0,0,.4), 0 -1px 0px rgba(0,0,0,.2), inset 0 -1px 0px #fff;
                cursor: pointer;
                display: inline-block;
                font-size: 0;
                height: 28px;
                position: relative;
                -webkit-transition: all 500ms ease;
                transition: all 500ms ease;
                width: 60px;
            }

            .bauble_box .bauble_label:before {
                background-color: rgba(255,255,255,.2);
                background-position: 0 0;
                background-repeat: repeat;
                background-size: 30% auto;
                border-radius: 50%;
                box-shadow: inset 0 -3px 15px #500, 0 6px 12px rgba(0,0,0,.4);
                content: '';
                display: block;
                height: 22px;
                left: 3px;
                position: absolute;
                top: 3px;
                -webkit-transition: all 500ms ease;
                transition: all 500ms ease;
                width: 22px;
                z-index: 2;
            }

            .bauble_box input.bauble_input {
                opacity: 0;
                z-index: 0;
                position: absolute;
            }

            /* Checked - Active State */
            .bauble_box input.bauble_input:checked + .bauble_label {
                background-color: #2c2;
            }

            .bauble_box input.bauble_input:checked + .bauble_label:before {
                background-position: 150% 0;
                box-shadow: inset 0 -3px 15px #050, 0 6px 12px rgba(0,0,0,.4);
                left: calc(100% - 25px);
            }
            
            /* Status Badge Transitions */
            .badge {
                transition: all 0.3s ease-in-out;
            }
            
            @keyframes statusChange {
                0% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.5; transform: scale(0.95); }
                100% { opacity: 1; transform: scale(1); }
            }
            
            .status-updating {
                animation: statusChange 0.3s ease-in-out;
            }
        </style>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

            <div class="container-fluid mt-1">
                <div class="shadow rounded-3 p-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2 "><i class="fa-solid fa-map-location-dot me-2"></i>Address Maintenance</p>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="shadow p-3 rounded-3 table-container" style="background-color: white;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <p class="fw-medium fs-5 mb-0" style="color: #090909;">List of Barangays</p>
                                <div class="d-flex gap-2">
                                    <div class="input-group input-group-sm search-group" style="width: 250px;">
                                        <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                        <input type="text" class="form-control" id="addressSearch" placeholder="Search" autocomplete="off">
                                    </div>
                                </div>
                            </div>
                            <hr style="height: 1px">
                            <div class="table-premium-container">
                                <table id="AddressTbl">
                                    <thead>
                                        <tr>
                                            <th style="width:22%;">Region</th>
                                            <th style="width:22%;">Province</th>
                                            <th style="width:18%;">City/<br>Town</th>
                                            <th style="width:22%;">Barangay</th>
                                            <th style="width:9%;">Status</th>
                                            <th style="width:7%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="AddressList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <form class="p-3 needs-validation mb-3 shadow" novalidate method="POST" id="addressForm" autocomplete="off">
                            <div class="align-items-center justify-content-between mb-3">
                                <button class="btn btn-primary float-end mx-2" id="editButton" type="button" disabled>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="new"> 
                                    <i class="fa-solid fa-plus"></i> New
                                </button>
                                <p class="fw-medium fs-5" style="color: #090909;">Address Details</p>
                            </div>
                            <hr style="height: 1px">

                            <input type="hidden" id="id_barangay" name="id_barangay">

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="Region" class="form-label">Region</label>
                                    <select class="form-select" id="Region" name="Region" required disabled>
                                        <option value="" selected disabled>Select Region</option>
                                        <option value="AUTONOMOUS REGION IN MUSLIM MINDANAO (ARMM)">AUTONOMOUS REGION IN MUSLIM MINDANAO (ARMM)</option>
                                        <option value="CORDILLERA ADMINISTRATIVE REGION (CAR)">CORDILLERA ADMINISTRATIVE REGION (CAR)</option>
                                        <option value="NATIONAL CAPITAL REGION (NCR)">NATIONAL CAPITAL REGION (NCR)</option>
                                        <option value="REGION I (ILOCOS REGION)">REGION I (ILOCOS REGION)</option>
                                        <option value="REGION II (CAGAYAN VALLEY)">REGION II (CAGAYAN VALLEY)</option>
                                        <option value="REGION III (CENTRAL LUZON)">REGION III (CENTRAL LUZON)</option>
                                        <option value="REGION IV-A (CALABARZON)">REGION IV-A (CALABARZON)</option>
                                        <option value="REGION IV-B (MIMAROPA)">REGION IV-B (MIMAROPA)</option>
                                        <option value="REGION V (BICOL REGION)">REGION V (BICOL REGION)</option>
                                        <option value="REGION VI (WESTERN VISAYAS)">REGION VI (WESTERN VISAYAS)</option>
                                        <option value="REGION VII (CENTRAL VISAYAS)">REGION VII (CENTRAL VISAYAS)</option>
                                        <option value="REGION VIII (EASTERN VISAYAS)">REGION VIII (EASTERN VISAYAS)</option>
                                        <option value="REGION IX (ZAMBOANGA PENINSULA)">REGION IX (ZAMBOANGA PENINSULA)</option>
                                        <option value="REGION X (NORTHERN MINDANAO)">REGION X (NORTHERN MINDANAO)</option>
                                        <option value="REGION XI (DAVAO REGION)">REGION XI (DAVAO REGION)</option>
                                        <option value="REGION XII (SOCCSKSARGEN)">REGION XII (SOCCSKSARGEN)</option>
                                        <option value="REGION XIII (Caraga)">REGION XIII (Caraga)</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a Region.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="Province" class="form-label">Province</label>
                                    <select class="form-select" id="Province" name="Province" required disabled>
                                        <option value="" selected disabled>Select Region First</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a Province.</div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label for="CityTown" class="form-label">City / Town</label>
                                    <select class="form-select" id="CityTown" name="CityTown" required disabled>
                                        <option value="" selected disabled>Select Province First</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a City/Town.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="Barangay" class="form-label">Barangay</label>
                                    <input type="text" class="form-control" id="Barangay" name="Barangay" placeholder="Barangay Name" required disabled oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">Please provide the Barangay.</div>
                                </div>
                                </div>

                            <div class="row">
                                <div class="col-12 mt-3">
                                    <button id="updateButton" disabled class="btn btn-primary mx-2 float-end" style="display: none;" type="button">
                                        <i class="fa-solid fa-upload"></i> Update
                                    </button>

                                    <button id="submitButton" name="submitButton" disabled class="btn btn-primary mx-2 float-end" type="button">
                                        <i class="fa-solid fa-check-circle"></i> Submit
                                    </button>
                                    
                                    <button class="btn btn-danger float-end" type="button" id="cancel" disabled hidden onclick="Cancel();">
                                        <i class="fa-regular fa-circle-xmark"></i> Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../js/maintenance.js?<?= time() ?>"></script>
        <script src="../../js/administrator/maintenanceaddress.js?<?= time() ?>"></script> 

    </body>
</html>
<?php
    } else {
        echo '<script> window.location.href = "../../login"; </script>';
    }
?>