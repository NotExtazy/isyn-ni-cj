<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    
    // Calculate base path for assets
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
        require_once(__DIR__ . '/../../includes/permissions.php');
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
      <link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="<?php echo $base; ?>/assets/select2/css/select2.min.css">
    <link href="<?php echo $base; ?>/assets/css/hope-ui.min.css" rel="stylesheet">
    
    <!-- jQuery must be loaded first -->
    <script src="<?php echo $base; ?>/assets/jquery/jquery.js"></script>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/select2/css/select2.min.css">
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
        // $clientName = isset($_GET['clientName']) ? $_GET['clientName'] : '';
        // $productName = isset($_GET['productName']) ? $_GET['productName'] : '';
        // $siNum = isset($_GET['siNum']) ? $_GET['siNum'] : '';
        // $quantity = isset($_GET['quantity']) ? $_GET['quantity'] : '';
        ?>

        <style>
            main {
                background-color: #EAEAF6;
                min-height: 100%;
                display: flex;
                flex-direction: column;
            }

            /* Custom Modal Animations and Styles */
            .modal.fade .modal-dialog {
                transform: scale(0.8) translateY(-50px);
                transition: all 0.3s ease-out;
            }
            
            .modal.show .modal-dialog {
                transform: scale(1) translateY(0);
            }

            /* Gradient Button Hover Effects */
            .btn[style*="gradient"]:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
            }

            /* Icon Animation */
            .modal-body .rounded-circle {
                animation: modalIconPulse 0.6s ease-out;
            }

            @keyframes modalIconPulse {
                0% {
                    transform: scale(0);
                    opacity: 0;
                }
                50% {
                    transform: scale(1.1);
                }
                100% {
                    transform: scale(1);
                    opacity: 1;
                }
            }

            /* Close button styling */
            .btn-close {
                transition: all 0.2s ease;
            }
            
            .btn-close:hover {
                opacity: 0.8 !important;
                transform: scale(1.1);
            }

            /* Modal backdrop blur effect */
            .modal-backdrop {
                backdrop-filter: blur(4px);
                background-color: rgba(0, 0, 0, 0.4);
                cursor: pointer;
            }

            /* Add a subtle hint for dismissal */
            .modal-backdrop::after {
                content: '';
                position: absolute;
                top: 20px;
                right: 20px;
                width: 30px;
                height: 30px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                border: 2px solid rgba(255, 255, 255, 0.3);
                opacity: 0;
                animation: backdropHint 2s ease-in-out 1s forwards;
            }

            @keyframes backdropHint {
                0%, 80% { opacity: 0; transform: scale(0.8); }
                20%, 60% { opacity: 1; transform: scale(1); }
                100% { opacity: 0; transform: scale(0.8); }
            }
        </style>

        <?php
        // Include sidebar and navbar
        include(__DIR__ . '/../../includes/pages.sidebar.php');
        include(__DIR__ . '/../../includes/pages.navbar.php');
        ?>


            <div class="container mt-4">
                <div class=" shadow p-3 rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Loan Transaction</p>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <form id="loanApplicationForm" method="POST" class="text-sm">
            
                            <div class="modal-header">
                                <!-- <h1 class="modal-title fs-5" id="exampleModalLabel">Application Details</h1> -->
                                <h5 class="modal-title fw-bold text-primary" id="exampleModalLabel">Application Details</h5>
            
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
            
                                <input type="hidden" class="form-control" id="add_userIdInput" name="add_userId" value="">
            
                                <div class="row mt-4">
                                    <div class="col-md-8">
            
                                        <div class="col-md-12">
                                            <div class="mb-3 row">
                                                <label class="col-sm-2 col-form-label">Client Name</label>
                                                <div class="col-sm-10">
                                                    <input type="text" readonly class="form-control" name="clientName" id="clientName" value="">
                                                </div>
                                            </div>
                                        </div>
            
                                        <!-- <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Client Name</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" readonly class="form-control text-end" name="clientName" id="clientName" value="<?php 
                                                        // echo $clientName; 
                                                        ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Product Name</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" readonly class="form-control text-end" name="productName" id="productName" value="<?php 
                                                        // echo $productName; 
                                                        ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">SI No.</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" readonly class="form-control text-end" name="siNum" id="siNum" value="<?php echo $siNum; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Quantity</label>
                                                    <div class="col-sm-8">
                                                        <input type="text" readonly class="form-control text-end" name="productQuantity" id="productQuantity" value="<?php 
                                                        // echo $quantity; 
                                                        ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div> -->
            
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Loan Type</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_loanType" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option value="NEW" selected>NEW</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Tag</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_tag" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option value="-">-</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Staff</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_poFco" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected disabled>SELECT PO/FCO</option>
                                                            <?php
                                                            // $query_po = "SELECT * FROM tbl_po ORDER BY PONick";
                                                            // $query_po_run = mysqli_query($connection, $query_po);
            
                                                            // if (mysqli_num_rows($query_po_run) > 0) {
                                                            //     while ($row_po = mysqli_fetch_assoc($query_po_run)) {
                                                            ?>
                                                                    <!-- <option value=" -->
                                                                    <?php
                                                                    //  echo $row_po['PONick'] 
                                                                     ?>
                                                                     <!-- "> -->
                                                                     <?php 
                                                                    //  echo $row_po['PONick'] 
                                                                     ?>
                                                                     <!-- </option> -->
                                                            <?php
                                                            //     }
                                                            // }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
            
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Program</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_program" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected disabled>SELECT PROGRAM</option>
                                                            <option value="ISYN">ISYN</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Product</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_product" id="add_product" class="form-select border-secondary border-opacity-50" aria-label="Default select example" onchange="computePrincipal(); computeAmortization();">
                                                            <option selected disabled>SELECT PRODUCT</option>
                                                            <?php
                                                            // $query_addProduct = "SELECT * FROM tbl_loansetup ORDER BY Product";
                                                            // $query_addProduct_run = mysqli_query($connection, $query_addProduct);
            
                                                            // if (mysqli_num_rows($query_addProduct_run) > 0) {
                                                            //     while ($row_addProduct = mysqli_fetch_assoc($query_addProduct_run)) {
                                                            ?>
                                                                    <!-- <option value=" -->
                                                                    <?php 
                                                                    // echo $row_addProduct['Product'] 
                                                                    ?>
                                                                    <!-- "> -->
                                                                    <?php 
                                                                    // echo $row_addProduct['Product'] 
                                                                    ?>
                                                                    <!-- </option> -->
                                                            <?php
                                                            //     }
                                                            // }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
            
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Mode</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_mode" id="add_mode" class="form-select border-secondary border-opacity-50" aria-label="Default select example" onchange="computeAmortization()">
                                                            <option selected disabled>SELECT MODE</option>
                                                            <?php
                                                            // $query_mode = "SELECT * FROM tbl_maintenance WHERE ItemType = 'MODE'";
                                                            // $query_mode_run = mysqli_query($connection, $query_mode);
            
                                                            // if (mysqli_num_rows($query_mode_run) > 0) {
                                                            //     while ($row_mode = mysqli_fetch_assoc($query_mode_run)) {
                                                            ?>
                                                                    <!-- <option value=" -->
                                                                    <?php 
                                                                    // echo $row_mode['ItemName'] 
                                                                    ?>
                                                                    <!-- "> -->
                                                                    <?php 
                                                                    // echo $row_mode['ItemName'] 
                                                                    ?>
                                                                    <!-- </option> -->
                                                            <?php
                                                            //     }
                                                            // }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label">Term</label>
                                            <div class="col-sm-4">
                                                <select name="add_termRate" id="add_termRate" class="form-select border-secondary border-opacity-50" aria-label="Default select example" onchange="computeAmortization()" oninput="computeAddInterest()">
                                                    <option selected disabled>SELECT TERM</option>
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
            
                                            <label class="col-sm-2 col-form-label">Interest</label>
                                            <div class="col-sm-4">
                                                <input name="add_rate" id="add_rate" class="form-control" readonly>
                                            </div>
                                        </div>
            
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label">No. of Availment</label>
                                            <div class="col-sm-10">
                                                <input name="add_availment" id="add_availment" type="text" class="form-control" value="" readonly>
                                            </div>
                                        </div>
            
                                        <div class="mb-3 row">
                                            <label class="col-sm-2 col-form-label fw-bold">Loan Amount</label>
                                            <div class="col-sm-10">
                                                <input name="add_amount" id="add_amount" value="" type="text" class="form-control border-secondary border-opacity-50" oninput="computeAmortization(); computeAddInterest(); computeAddPrincipal(this.value); validateNumber(this);" onchange="computeAmortization();">
                                            </div>
                                        </div>
            
                                        <div class="mb-3 row">
                                            <div class="col-sm-2 col-form-label">
                                                <div class="form-check">
                                                    <input name="add_downPayment" class="form-check-input border-secondary border-opacity-50" type="checkbox" value="1" id="add_downpayment">
                                                    <label class="form-check-label" for="flexCheckChecked"> DP </label>
                                                </div>
                                            </div>
            
                                            <div class="col-sm-10">
                                                <input name="add_downpaymentAmount" id="add_downpaymentAmount" type="text" class="form-control border-secondary border-opacity-50" disabled oninput="computeDownPayment(); computeAmortization(); validateNumber(this);" onchange="computeAmortization();">
                                            </div>
                                        </div>
            
                                        <hr>
            
                                        <!-- <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Sector</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_sector" id="add_sector" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected disabled>SELECT SECTOR</option>
                                                            <?php
                                                            // $query_sector = "SELECT * FROM tbl_maintenance WHERE ItemType = 'SECTOR' ORDER BY ItemName";
                                                            // $query_sector_run = mysqli_query($connection, $query_sector);
            
                                                            // if (mysqli_num_rows($query_sector_run) > 0) {
                                                            //     while ($row_sector = mysqli_fetch_assoc($query_sector_run)) {
                                                            ?>
                                                                    <option value="<?php
                                                                    //  echo $row_sector['ItemName'] 
                                                                     ?>"><?php 
                                                                    //  echo $row_sector['ItemName'] 
                                                                     ?></option>
                                                            <?php
                                                            //     }
                                                            // }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Age (Yr/Mo)</label>
                                                    <div class="col-sm-8 d-flex text-center">
                                                        <input name="add_ageYear" type="text" class="form-control border-secondary border-opacity-50">
                                                        <input name="add_ageMonth" type="text" class="form-control border-secondary border-opacity-50">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Nature</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_nature" id="add_nature" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected disabled>SELECT NATURE</option>
                                                            <?php
                                                            // $query_add_nature = "SELECT DISTINCT BizNature FROM tbl_bizsetup ORDER BY BizNature ASC";
                                                            // $query_add_nature_run = mysqli_query($connection, $query_add_nature);
            
                                                            // if (mysqli_num_rows($query_add_nature_run) > 0) {
                                                            //     while ($row_add_nature = mysqli_fetch_assoc($query_add_nature_run)) {
                                                            ?>
                                                                    <option value="<?php 
                                                                    // echo $row_add_nature['BizNature']
                                                                     ?>"><?php 
                                                                    // echo $row_add_nature['BizNature']
                                                                     ?></option>
                                                            <?php
                                                            //     }
                                                            // }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Capital</label>
                                                    <div class="col-sm-8 d-flex text-center">
                                                        <input name="add_capital" type="text" class="form-control border-secondary border-opacity-50">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Type</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_type" id="add_type" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected disabled>SELECT TYPE</option>
            
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Workers</label>
                                                    <div class="col-sm-8 d-flex text-center">
                                                        <input name="add_workers" type="text" class="form-control border-secondary border-opacity-50">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Prod/Svc</label>
                                                    <div class="col-sm-8">
                                                        <select name="add_prodServices" id="add_productService" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected disabled>SELECT PRODUCT / SERVICE</option>
            
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-4 col-form-label">Mo. Income</label>
                                                    <div class="col-sm-8 d-flex text-center">
                                                        <input name="add_moIncome" type="text" class="form-control border-secondary border-opacity-50">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
            
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="mb-3 row">
                                                    <label class="col-sm-3 col-form-label">Group</label>
                                                    <div class="col-sm-9">
                                                        <select name="add_group" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                            <option selected>Open this select menu</option>
                                                            <option value="1">One</option>
                                                            <option value="2">Two</option>
                                                            <option value="3">Three</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
            
                                            <div class="col-md-4">
                                                <button class="btn btn-primary">Clear</button>
                                            </div>
                                        </div> -->
            
                                        <div class="row mt-3">
                                            <div class="col-sm-12 col-md-12">
                                                <h5 class="fw-bold text-primary">Co-Maker Details</h5>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="add_firstName" class="form-label mb-0">First Name</label>
                                                            <input name="add_firstName" type="text" class="form-control border-secondary border-opacity-50" id="add_firstName" placeholder="">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="add_middleName" class="form-label mb-0">Middle Name</label>
                                                            <input name="add_middleName" type="text" class="form-control border-secondary border-opacity-50" id="add_middleName" placeholder="">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="add_lastName" class="form-label mb-0">Last Name</label>
                                                            <input name="add_lastName" type="text" class="form-control border-secondary border-opacity-50" id="add_lastName" placeholder="">
                                                        </div>
                                                    </div>
                                                </div>
            
                                                <!-- <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Street No. / Block</label>
                                                            <input name="add_street" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
            
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Barangay</label>
                                                            <input name="add_barangay" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
            
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">City / Town</label>
                                                            <input name="add_city" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
            
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Province</label>
                                                            <input name="add_province" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
                                                </div>
            
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Birthdate</label>
                                                            <input name="add_birthdate" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
            
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Age</label>
                                                            <input name="add_age" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
            
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Contact No.</label>
                                                            <input name="add_contact" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
                                                </div>
            
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Occupation</label>
                                                            <input name="add_occupation" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
            
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="formGroupExampleInput" class="form-label mb-0">Relation to Client</label>
                                                            <input name="add_relation" type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                                        </div>
                                                    </div>
                                                </div> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-12 ">
                                        <h5 class="text-primary fw-bold mb-0">Summary</h5>
                                        <hr>
                                        <div class="row">
                                            <label for="principalAddValue" class="col-sm-4 col-form-label fw-bold">Principal</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="principalAddValue" id="principalAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="interestAddValue" class="col-sm-4 col-form-label fw-bold">Interest</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="interestAddValue" id="interestAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="mbaAddValue" class="col-sm-4 col-form-label fw-bold">MBA</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="mbaAddValue" id="mbaAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="cbuAddValue" class="col-sm-4 col-form-label fw-bold">CBU</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="cbuAddValue" id="cbuAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="efAddValue" class="col-sm-4 col-form-label fw-bold">EF</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="efAddValue" id="efAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="totalAddValue" class="col-sm-4 col-form-label fw-bold">Total</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="totalAddValue" id="totalAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <hr>
                                        <h5 class="text-primary fw-bold mb-0">Amortization</h5>
                                        <hr>
                                        <div class="row">
                                            <label for="principalAmortAddValue" class="col-sm-4 col-form-label fw-bold">Principal</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="principalAmortAddValue" id="principalAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="interestAmortAddValue" class="col-sm-4 col-form-label fw-bold">Interest</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="interestAmortAddValue" id="interestAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="mbaAmortAddValue" class="col-sm-4 col-form-label fw-bold">MBA</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="mbaAmortAddValue" id="mbaAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="cbuAmortAddValue" class="col-sm-4 col-form-label fw-bold">CBU</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="cbuAmortAddValue" id="cbuAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="efAmortAddValue" class="col-sm-4 col-form-label fw-bold">EF</label>
                                            <div class="col-sm-8">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="efAmortAddValue" id="efAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <label for="totalAmortAddValue" class="col-sm-5 col-form-label fw-bold">Total Amount</label>
                                            <div class="col-sm-7">
                                                <input type="text" readonly class="form-control-plaintext text-end" name="totalAmortAddValue" id="totalAmortAddValue" value="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Utilization Details Section -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="shadow rounded-3 p-3 bg-white">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <h5 class="fw-bold text-primary">Utilization Details</h5>
                                                <div class="btn-group-utilization">
                                                    <button class="btn btn-primary btn-sm" id="addUtilizationRow" type="button"> <i class="fa-solid fa-plus"></i> Add Row</button>
                                                    <button class="btn btn-secondary btn-sm ms-2" id="clearUtilizationTable" type="button"> <i class="fa-solid fa-eraser"></i> Clear All</button>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="mb-2">
                                                <small class="text-muted">Specify how the loan will be used. Total must equal loan amount.</small>
                                            </div>
                                            <table class="table table-hover table-bordered table-sm" id="utilizationTable">
                                                <thead>
                                                    <tr class="text-center">
                                                        <th class="fw-bold" style="width: 50%">Purpose</th>
                                                        <th class="fw-bold" style="width: 35%">Amount</th>
                                                        <th class="fw-bold" style="width: 15%">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="utilizationTableBody">
                                                    <tr>
                                                        <td>
                                                            <select class="form-select form-select-sm utilization-purpose" name="utilization_purpose[]">
                                                                <option value="">Select Purpose</option>
                                                                <option value="Working Capital">Working Capital</option>
                                                                <option value="Equipment Purchase">Equipment Purchase</option>
                                                                <option value="Business Expansion">Business Expansion</option>
                                                                <option value="Debt Consolidation">Debt Consolidation</option>
                                                                <option value="Emergency Expenses">Emergency Expenses</option>
                                                                <option value="Agricultural Inputs">Agricultural Inputs</option>
                                                                <option value="Livestock Purchase">Livestock Purchase</option>
                                                                <option value="Education">Education</option>
                                                                <option value="Housing Improvement">Housing Improvement</option>
                                                                <option value="Other">Other</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm utilization-amount" name="utilization_amount[]" placeholder="0.00" step="0.01" min="0">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-danger btn-sm remove-utilization-row" disabled><i class="fa-solid fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-light">
                                                        <td class="text-end fw-bold">Total:</td>
                                                        <td><input type="text" class="form-control form-control-sm fw-bold" id="utilizationTotal" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr class="table-info">
                                                        <td class="text-end fw-bold">Loan Amount:</td>
                                                        <td><input type="text" class="form-control form-control-sm fw-bold" id="utilizationLoanAmount" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                    <tr class="table-warning">
                                                        <td class="text-end fw-bold">Difference:</td>
                                                        <td><input type="text" class="form-control form-control-sm fw-bold" id="utilizationDifference" readonly value="0.00"></td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="submit_btn" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Modal -->

            <div class="container mt-auto">
                <?php 
                // include('../../includes/message-prompt.php');
                 ?>
                <div class="row mt-4 mb-4 ">
                    <div class="col-md-12 ">
                        <div class="shadow p-3 rounded-3 bg-white ">
                            <div class="align-items-center justify-content-between mb-3">
                                <!-- <button id="editButton" name="renewal_button" disabled class="btn btn-warning text-white float-end" type="button" onclick="toggleEdit()"><i class="fa-solid fa-rotate"></i> Save Renewal</button> -->
            
                                <!-- <button class="btn btn-success float-end mx-2" type="reset" name="new" onclick="clearForm()"> <i class="fa-solid fa-plus"></i> Add New</button> -->
                                <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="new" data-bs-toggle="modal" data-bs-target="#exampleModal" disabled> <i class="fa-solid fa-plus"></i> New</button>
                                <p class="fw-medium fs-5" style="color: #090909;">Client List</p>
                            </div>
                            <hr style="height: 1px">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3 row">
                                        <label class="col-sm-3 col-form-label">Select Client</label>
                                        <div class="col-sm-9">
                                            <select class="form-select" id="clientSelect" aria-label="Default select example" name="clientSelect">
                                                <option selected disabled>SELECT</option>
                                                <?php
                                                // $query = "SELECT * FROM tbl_clientinfo ORDER BY lastName ASC";
                                                // $query_run = mysqli_query($connection, $query);
            
                                                // if (mysqli_num_rows($query_run) > 0) {
                                                //     while ($row = mysqli_fetch_assoc($query_run)) {
                                                //         $selected = ($row['ClientName'] === $clientName) ? 'selected' : '';
                                                ?>
                                                        <!-- <option value=" -->
                                                        <?php 
                                                        // echo $row['ClientNo']; 
                                                        ?>
                                                        <!-- " data-clientname=" -->
                                                        <?php 
                                                        // echo $row['ClientName']; 
                                                        ?>
                                                        <!-- "  -->
                                                        <?php
                                                        // echo $selected; 
                                                        ?>
                                                        <!-- > -->
                                                            <?php 
                                                            // echo $row['ClientName'];
                                                             ?>
                                                        <!-- </option> -->
            
            
                                                <?php
                                                //     }
                                                // }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <div class="overflow-auto" style="height: 200px;">
                                    <table class="table  table-hover  table-borderless " style="background-color: white;">
                                        <thead>
                                            <tr>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Loan ID</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Client Name</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Program</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Product</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Date Released</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Loan Amount</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Balance</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">CBU</th>
                                                <th class="fw-bold fs-6 text-uppercase" style="color:#090909">Arrears</th>
                                            </tr>
                                        </thead>
                                        <tbody id="loanTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form action="/isyn-app-v2/process/accounts_monitoring/config_accounts_monitoring/config_loan_renewal" id="form-renewal" method="POST">
                    <div class="row mb-4 ">
                        <div class="col-sm-12 col-md-8  ">
                            <div class="shadow rounded-3 p-3 bg-white">
                                <div class="align-items-center justify-content-between mb-4">
                                    <button type="button" id="cancelRenewalBtn" class="btn btn-secondary float-end mx-2" disabled><i class="fa-solid fa-times"></i> Cancel</button>
                                    <button type="submit" id="editButton" name="renewal_button" disabled class="btn btn-warning text-white float-end"><i class="fa-solid fa-rotate"></i> Save Renewal</button>
                                    <p class="fw-medium fs-5" style="color: #090909;">Renewal-Application Details</p>
                                    <!-- <button class="btn btn-success float-end mx-2" type="reset" name="new" onclick="clearForm()"> <i class="fa-solid fa-plus"></i> Add New</button> -->
                                    <!-- <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="new" data-bs-toggle="modal" data-bs-target="#exampleModal" disabled> <i class="fa-solid fa-plus"></i> Add New</button> -->
                                </div>
                                <hr>
                                <!-- <form action="/isyn-app/src/config/config_accounts_monitoring/config_loan_renewal" class="text-sm" id="form-renewal"> -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <input type="hidden" class="form-control" id="userIdInput" name="userId" value="">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Loan Type</label>
                                            <div class="col-sm-8">
                                                <select id="loanType" name="loanType" class="form-select border-secondary border-opacity-50 " aria-label="Default select example" disabled>
                                                    <?php
                                                    // $query_loan = "SELECT * FROM tbl_maintenance WHERE ItemType = 'LOANTYPE'";
                                                    // $query_loan_run = mysqli_query($connection, $query_loan);
            
                                                    // if (mysqli_num_rows($query_loan_run) > 0) {
                                                    //     while ($row_loan = mysqli_fetch_assoc($query_loan_run)) {
                                                    //         $itemName = $row_loan['ItemName'];
                                                    //         $isSelected = ($itemName == 'RENEW') ? 'selected' : ''; // Check if item name is 'RENEW'
            
                                                    //         // Output option with selected attribute if item name is 'RENEW'
                                                    //         echo '<option value="' . $itemName . '" ' . $isSelected . '>' . $itemName . '</option>';
                                                    //     }
                                                    // }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Tag</label>
                                            <div class="col-sm-8">
                                                <select id="tag" name="tag" class="form-select border-secondary border-opacity-50" aria-label="Default select example" disabled>
                                                    <option value="-">-</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Staff</label>
                                            <div class="col-sm-8">
                                                <select id="poFco" name="poFco" class="form-select border-secondary border-opacity-50" aria-label="Default select example" disabled>
                                                    <option selected disabled>SELECT STAFF</option>
                                                    <?php
                                                    // $query_po = "SELECT * FROM tbl_po";
                                                    // $query_po_run = mysqli_query($connection, $query_po);
            
                                                    // if (mysqli_num_rows($query_po_run) > 0) {
                                                    //     while ($row_po = mysqli_fetch_assoc($query_po_run)) {
                                                    ?>
                                                            <!-- <option value=" -->
                                                            <?php 
                                                            // echo $row_po['PONick'] 
                                                            ?>
                                                            <!-- "> -->
                                                            <?php 
                                                            // echo $row_po['PONick'] 
                                                            ?>
                                                            <!-- </option> -->
                                                    <?php
                                                    //     }
                                                    // }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Program</label>
                                            <div class="col-sm-8">
                                                <select id="program" name="program" class="form-select border-secondary border-opacity-50" aria-label="Default select example" readonly disabled>
                                                    <option selected disabled>SELECT PROGRAM</option>
                                                    <option value="ISYN">ISYN</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Product</label>
                                            <div class="col-sm-8">
                                                <select id="product" name="product" class="form-select border-secondary border-opacity-50" aria-label="Default select example" onchange="updateRate()" disabled>
                                                    <option selected disabled>SELECT PRODUCT</option>
                                                    <?php
                                                    // $query_product = "SELECT * FROM tbl_loansetup ORDER BY Product";
                                                    // $query_product_run = mysqli_query($connection, $query_product);
            
                                                    // if (mysqli_num_rows($query_product_run) > 0) {
                                                    //     while ($row_product = mysqli_fetch_assoc($query_product_run)) {
                                                    ?>
                                                            <!-- <option value=" -->
                                                            <?php 
                                                            // echo $row_product['Product'] 
                                                            ?>
                                                            <!-- "> -->
                                                            <?php
                                                            //  echo $row_product['Product'] 
                                                             ?>
                                                             <!-- </option> -->
                                                    <?php
                                                    //     }
                                                    // }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Mode</label>
                                            <div class="col-sm-8">
                                                <select id="mode" name="mode" class="form-select border-secondary border-opacity-50" aria-label="Default select example" onchange="computeAmortizationValue()" disabled>
                                                    <option selected disabled>SELECT MODE</option>
                                                    <?php
                                                    // $query_mode = "SELECT * FROM tbl_maintenance WHERE ItemType = 'MODE'";
                                                    // $query_mode_run = mysqli_query($connection, $query_mode);
            
                                                    // if (mysqli_num_rows($query_mode_run) > 0) {
                                                    //     while ($row_mode = mysqli_fetch_assoc($query_mode_run)) {
                                                    ?>
                                                            <!-- <option value=" -->
                                                            <?php
                                                            //  echo $row_mode['ItemName'] 
                                                             ?>
                                                             <!-- "> -->
                                                             <?php
                                                            //   echo $row_mode['ItemName']
                                                               ?>
                                                               <!-- </option> -->
                                                    <?php
                                                    //     }
                                                    // }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-2 col-form-label">Term</label>
                                    <div class="col-sm-4">
                                        <select id="termRate" name="termRate" class="form-select border-secondary border-opacity-50" aria-label="Default select example" onchange="computeAmortizationValue()" oninput="computeInterest()" disabled>
                                            <option selected disabled>SELECT TERM</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
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
                                    <label class="col-sm-2 col-form-label">Interest</label>
                                    <div class="col-sm-4">
                                        <input name="rate" id="rate" class="form-control border-secondary border-opacity-50" readonly disabled>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-sm-2 col-form-label">No. of Availment</label>
                                    <div class="col-sm-10">
                                        <input id="availment" name="availment" type="text" class="form-control border-secondary border-opacity-50" readonly disabled>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                <label class="col-sm-2 col-form-label">Amount</label>
                                    <div class="col-sm-10">
                                        <input id="amount" name="amount" type="text" class="form-control border-secondary border-opacity-50" oninput="computeAmortizationValue(); computeInterest(); updatePrincipal(this.value);  validateNumber(this);" disabled>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-sm-2 col-form-label">
                                        <div class="form-check">
                                            <input id="downPayment" name="downPayment" class="form-check-input border-secondary border-opacity-50" type="checkbox" value="" disabled>
                                            <label class="form-check-label" for="flexCheckChecked"> DP </label>
                                        </div>
                                    </div>
            
                                    <div class="col-sm-10">
                                        <input id="downPaymentAmount" name="downPaymentAmount" type="text" class="form-control border-secondary border-opacity-50" oninput="computeDownPaymentValue(); validateNumber(this)" disabled>
                                    </div>
                                </div>
                                <hr>
                                <p class="fw-medium fs-5" style="color: #090909;">Co-Maker Details</p>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="renewal_firstName" class="form-label mb-0">First Name</label>
                                            <input type="text" name="firstname" class="form-control border-secondary border-opacity-50" id="renewal_firstName" placeholder="" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="renewal_middleName" class="form-label mb-0">Middle Name</label>
                                            <input type="text" name="middlename" class="form-control border-secondary border-opacity-50" id="renewal_middleName" placeholder="" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="renewal_lastName" class="form-label mb-0">Last Name</label>
                                            <input type="text" name="lastname" class="form-control border-secondary border-opacity-50" id="renewal_lastName" placeholder="" disabled>
                                        </div>
                                    </div>
                                </div>
            
                                <!-- <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Sector</label>
                                            <div class="col-sm-8">
                                                <select id="sector" name="sector" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                    <option selected disabled>SELECT SECTOR</option>
                                                    <?php
                                                    // $query_sector = "SELECT * FROM tbl_maintenance WHERE ItemType = 'SECTOR' ORDER BY ItemName";
                                                    // $query_sector_run = mysqli_query($connection, $query_sector);
            
                                                    // if (mysqli_num_rows($query_sector_run) > 0) {
                                                    //     while ($row_sector = mysqli_fetch_assoc($query_sector_run)) {
                                                    ?>
                                                            <option value="
                                                            <?php 
                                                            // echo $row_sector['ItemName'] 
                                                            ?>
                                                            ">
                                                            <?php 
                                                            // echo $row_sector['ItemName'] 
                                                            ?></option>
                                                    <?php
                                                    //     }
                                                    // }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Age (Yr/Mo)</label>
                                            <div class="col-sm-8 d-flex text-center">
                                                <input id="ageYear" name="ageYear" type="text" class="form-control border-secondary border-opacity-50">
                                                <input id="ageMonth" name="ageMonth" type="text" class="form-control border-secondary border-opacity-50">
                                            </div>
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Nature</label>
                                            <div class="col-sm-8">
                                                <select id="nature" name="nature" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                    <option selected disabled>SELECT NATURE</option>
                                                    <?php
                                                    // $query_nature = "SELECT DISTINCT BizNature FROM tbl_bizsetup ORDER BY BizNature ASC";
                                                    // $query_nature_run = mysqli_query($connection, $query_nature);
            
                                                    // if (mysqli_num_rows($query_nature_run) > 0) {
                                                    //     while ($row_nature = mysqli_fetch_assoc($query_nature_run)) {
                                                    ?>
                                                            <option value="<?php
                                                            //  echo $row_nature['BizNature']
                                                              ?>"><?php 
                                                            //   echo $row_nature['BizNature'] 
                                                              ?></option>
                                                    <?php
                                                    //     }
                                                    // }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Capital</label>
                                            <div class="col-sm-8 d-flex text-center">
                                                <input id="capital" name="capital" type="text" class="form-control border-secondary border-opacity-50">
                                            </div>
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Type</label>
                                            <div class="col-sm-8">
                                                <select id="type" name="type" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                    <option selected disabled>SELECT TYPE</option>
                                                    <?php
            
                                                    // if (isset($_POST['nature'])) {
            
            
                                                    //     $query_type = "SELECT DISTINCT BizType FROM tbl_bizsetup ORDER BY BizType ASC";
                                                    //     $query_type_run = mysqli_query($connection, $query_type);
            
                                                    //     if (mysqli_num_rows($query_type_run) > 0) {
                                                    //         while ($row_type = mysqli_fetch_assoc($query_type_run)) {
                                                    ?>
                                                                <option value="<?php 
                                                                // echo $row_type['BizType']
                                                                 ?>"><?php
                                                                //   echo $row_type['BizType'] 
                                                                  ?></option>
                                                    <?php
                                                    //         }
                                                    //     }
                                                    // } else {
                                                    // }
                                                    ?>
            
            
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Workers</label>
                                            <div class="col-sm-8 d-flex text-center">
                                                <input id="workers" name="workers" type="text" class="form-control border-secondary border-opacity-50">
                                            </div>
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Prod/Svc</label>
                                            <div class="col-sm-8">
                                                <select id="productServices" name="productServices" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                    <option selected disabled>SELECT PRODUCT / SERVICE</option>
                                                    <?php 
                                                    // echo $productServiceOptions;
                                                     ?>
            
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 row">
                                            <label class="col-sm-4 col-form-label">Mo. Income</label>
                                            <div class="col-sm-8 d-flex text-center">
                                                <input id="moIncome" name="moIncome" type="text" class="form-control border-secondary border-opacity-50">
                                            </div>
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3 row">
                                            <label class="col-sm-3 col-form-label">Group</label>
                                            <div class="col-sm-9">
                                                <select id="group" name="group" class="form-select border-secondary border-opacity-50" aria-label="Default select example">
                                                    <option selected disabled>SELECT GROUP</option>
                                                    <option value="1">One</option>
                                                    <option value="2">Two</option>
                                                    <option value="3">Three</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
            
                                    <div class="col-md-4">
                                        <button class="btn btn-primary">Clear</button>
                                    </div>
            
                                </div> -->
                            </div>
                        </div>
            
                        <div class="col-md-4 col-sm-12 ">
                            <div class="shadow rounded-3 p-3 bg-white">
                                <p class="fw-medium fs-5" style="color: #090909;">Summary</p>
                                <hr>
            
                                <div class="row">
                                    <label for="principalViewValue" class="col-sm-4 col-form-label fw-bold">Principal</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="principalViewValue" id="principalViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="interestViewValue" class="col-sm-4 col-form-label fw-bold">Interest</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="interestViewValue" id="interestViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="mbaViewValue" class="col-sm-4 col-form-label fw-bold">MBA</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="mbaViewValue" id="mbaViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="cbuViewValue" class="col-sm-4 col-form-label fw-bold">CBU</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="cbuViewValue" id="cbuViewValue" value="0.00">
                                    </div>
                                </div>
            
            
                                <div class="row">
                                    <label for="efViewValue" class="col-sm-4 col-form-label fw-bold">EF</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="efViewValue" id="efViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="totalViewValue" class="col-sm-4 col-form-label fw-bold">Total</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="totalViewValue" id="totalViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <hr>
                                <p class="fw-medium fs-5" style="color: #090909;">Amortization</p>
                                <hr>
            
                                <div class="row">
                                    <label for="principalAmortViewValue" class="col-sm-4 col-form-label fw-bold">Principal</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="principalAmortViewValue" id="principalAmortViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="interestAmortViewValue" class="col-sm-4 col-form-label fw-bold">Interest</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="interestAmortViewValue" id="interestAmortViewValue" value="0.00">
                                    </div>
                                </div>
            
            
            
                                <div class="row">
                                    <label for="mbaAmortViewValue" class="col-sm-4 col-form-label fw-bold">MBA</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="mbaAmortViewValue" id="mbaAmortViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="cbuAmortViewValue" class="col-sm-4 col-form-label fw-bold">CBU</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="cbuAmortViewValue" id="cbuAmortViewValue" value="0.00">
                                    </div>
                                </div>
            
            
                                <div class="row">
                                    <label for="efAmortViewValue" class="col-sm-4 col-form-label fw-bold">EF</label>
                                    <div class="col-sm-8">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="efAmortViewValue" id="efAmortViewValue" value="0.00">
                                    </div>
                                </div>
            
                                <div class="row">
                                    <label for="totalAmortViewValue" class="col-sm-5 col-form-label fw-bold">Total Amount</label>
                                    <div class="col-sm-7">
                                        <input type="text" readonly class="form-control-plaintext text-end" name="totalAmortViewValue" id="totalAmortViewValue" value="0.00">
                                    </div>
                                </div>
            
                            </div>
                        </div>
                    </div>
                </form>
            
            
                <!-- <div class="row mb-4">
                    <div class="col-sm-12 col-md-12">
                        <div class="shadow rounded-3 p-3 bg-white">
                            <h5 class="fw-bold text-primary">Co-Maker Details</h5>
                            <hr>
            
                            <form action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Last Name</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">First Name</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Middle Name</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Street No. / Block</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Barangay</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">City / Town</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Province</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Birthdate</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Age</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Contact No.</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
                                </div>
            
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Occupation</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
            
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="formGroupExampleInput" class="form-label mb-0">Relation to Client</label>
                                            <input type="text" class="form-control border-secondary border-opacity-50" id="formGroupExampleInput" placeholder="">
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div> -->
            
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="shadow rounded-3 p-3 bg-white">
            
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="fw-bold text-primary">Utilization Details (Renewal)</h5>
            
                                <div class="btn-group-utilization">
                                    <button class="btn btn-primary btn-sm" id="addRenewalUtilizationRow" type="button"> <i class="fa-solid fa-plus"></i> Add Row</button>
                                    <button class="btn btn-secondary btn-sm ms-2" id="clearRenewalUtilizationTable" type="button"> <i class="fa-solid fa-eraser"></i> Clear All</button>
                                </div>
                            </div>
            
                            <hr>

                            <div class="mb-2">
                                <small class="text-muted">Specify how the loan will be used. Total must equal loan amount.</small>
                            </div>
            
                            <table class="table table-hover table-bordered table-sm" id="renewalUtilizationTable">
                                <thead>
                                    <tr class="text-center">
                                        <th class="fw-bold" style="width: 50%">Purpose</th>
                                        <th class="fw-bold" style="width: 35%">Amount</th>
                                        <th class="fw-bold" style="width: 15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="renewalUtilizationTableBody">
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm renewal-utilization-purpose" name="renewal_utilization_purpose[]" disabled>
                                                <option value="">Select Purpose</option>
                                                <option value="Working Capital">Working Capital</option>
                                                <option value="Equipment Purchase">Equipment Purchase</option>
                                                <option value="Business Expansion">Business Expansion</option>
                                                <option value="Debt Consolidation">Debt Consolidation</option>
                                                <option value="Emergency Expenses">Emergency Expenses</option>
                                                <option value="Agricultural Inputs">Agricultural Inputs</option>
                                                <option value="Livestock Purchase">Livestock Purchase</option>
                                                <option value="Education">Education</option>
                                                <option value="Housing Improvement">Housing Improvement</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm renewal-utilization-amount" name="renewal_utilization_amount[]" placeholder="0.00" step="0.01" min="0" disabled>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-renewal-utilization-row" disabled><i class="fa-solid fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td class="text-end fw-bold">Total:</td>
                                        <td><input type="text" class="form-control form-control-sm fw-bold" id="renewalUtilizationTotal" readonly value="0.00"></td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-info">
                                        <td class="text-end fw-bold">Loan Amount:</td>
                                        <td><input type="text" class="form-control form-control-sm fw-bold" id="renewalUtilizationLoanAmount" readonly value="0.00"></td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td class="text-end fw-bold">Difference:</td>
                                        <td><input type="text" class="form-control form-control-sm fw-bold" id="renewalUtilizationDifference" readonly value="0.00"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Scripts -->
        <script src="<?php echo $base; ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>

        <script>
            // Test jQuery immediately
            console.log('Testing jQuery availability...');
            if (typeof $ !== 'undefined') {
                console.log('✓ jQuery is loaded successfully');
                console.log('jQuery version:', $.fn.jquery);
            } else {
                console.error('✗ jQuery is NOT loaded');
                alert('jQuery failed to load. Please check the console for errors.');
            }
            
            // Wait for all scripts to load before initializing
            $(document).ready(function() {
                // Add a small delay to ensure all scripts are loaded
                setTimeout(function() {
                    // Ensure jQuery and Select2 are available
                    if (typeof $ === 'undefined') {
                        console.error('jQuery is not loaded');
                        return;
                    }
                    
                    if (typeof $.fn.select2 === 'undefined') {
                        console.error('Select2 is not loaded');
                        return;
                    }
                    
                    console.log('Loading dropdowns...');
                    
                    // Declare clientSelect at proper scope
                    var clientSelect = $('#clientSelect');
                    
                    // Test connection first
                    $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=testConnection',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Database test:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Test connection error:', error, xhr.responseText);
                    }
                });
                
                // Load clients
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getClients',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Clients response:', response);
                        if (response.success && response.clients) {
                            response.clients.forEach(function(client) {
                                clientSelect.append(
                                    $('<option></option>')
                                        .attr('value', client.ClientNo)
                                        .attr('data-clientname', client.ClientName)
                                        .text(client.ClientName)
                                );
                            });
                            console.log('Loaded ' + response.clients.length + ' clients');
                            
                            // Initialize Select2 AFTER clients are loaded
                            try {
                                if (typeof $.fn.select2 !== 'undefined') {
                                    clientSelect.select2({
                                        placeholder: 'Type to search for a client...',
                                        allowClear: true,
                                        width: '100%'
                                    });
                                    
                                    // Re-bind change event for Select2
                                    clientSelect.on('select2:select', function(e) {
                                        // Trigger the original change event
                                        $(this).trigger('change');
                                    });
                                    console.log('Select2 initialized successfully');
                                } else {
                                    console.warn('Select2 not available, using regular select');
                                }
                            } catch (error) {
                                console.error('Select2 initialization error:', error);
                                // Fallback: use regular select without Select2
                            }
                        } else {
                            console.error('No clients data:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading clients:', error);
                        console.error('Response text:', xhr.responseText);
                        console.error('Status:', xhr.status);
                        console.error('Full URL:', '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getClients');
                    }
                });

                // Load staff
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getStaff',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Staff response:', response);
                        if (response.success && response.staff) {
                            var staffSelect = $('select[name="add_poFco"]');
                            response.staff.forEach(function(staff) {
                                staffSelect.append(
                                    $('<option></option>')
                                        .attr('value', staff)
                                        .text(staff)
                                );
                            });
                            console.log('Loaded ' + response.staff.length + ' staff');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading staff:', error, xhr.responseText);
                    }
                });

                // Load products
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getProducts',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Products response:', response);
                        if (response.success && response.products) {
                            var productSelect = $('#add_product');
                            response.products.forEach(function(product) {
                                productSelect.append(
                                    $('<option></option>')
                                        .attr('value', product)
                                        .text(product)
                                );
                            });
                            console.log('Loaded ' + response.products.length + ' products');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading products:', error, xhr.responseText);
                    }
                });

                // Load modes
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getModes',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Modes response:', response);
                        if (response.success && response.modes) {
                            var modeSelect = $('#add_mode');
                            var renewalModeSelect = $('#mode');
                            response.modes.forEach(function(mode) {
                                modeSelect.append(
                                    $('<option></option>')
                                        .attr('value', mode)
                                        .text(mode)
                                );
                                renewalModeSelect.append(
                                    $('<option></option>')
                                        .attr('value', mode)
                                        .text(mode)
                                );
                            });
                            console.log('Loaded ' + response.modes.length + ' modes');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading modes:', error, xhr.responseText);
                    }
                });

                // Populate renewal form dropdowns
                // Staff for renewal
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getStaff',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.staff) {
                            var staffSelect = $('#poFco');
                            response.staff.forEach(function(staff) {
                                staffSelect.append(
                                    $('<option></option>')
                                        .attr('value', staff)
                                        .text(staff)
                                );
                            });
                        }
                    }
                });

                // Products for renewal
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getProducts',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.products) {
                            var productSelect = $('#product');
                            response.products.forEach(function(product) {
                                productSelect.append(
                                    $('<option></option>')
                                        .attr('value', product)
                                        .text(product)
                                );
                            });
                        }
                    }
                });

                // Add loan type options for renewal (only RENEW option, keep disabled)
                $('#loanType').append('<option value="RENEW" selected>RENEW</option>');
                $('#loanType').prop('disabled', true);
                
                // Select2 is now initialized after clients are loaded (see above)
                }, 500); // 500ms delay to ensure all scripts are loaded
            });

            document.getElementById('add_product').addEventListener('change', function() {
                var selectedOption = this.value;
                var rateInput = document.getElementById('add_rate');

                var numbers = selectedOption.match(/\d+/);
                if (numbers) {
                    var rate = numbers[0] + '%';
                    rateInput.value = rate;
                } else {
                    rateInput.value = '';
                }
            });

            function validateNumber(input) {
                input.value = input.value.replace(/\D/g, '');

            }

            //ADD TYPE
            $('#add_nature').on('change', function() {
                var add_nature = $(this).val();
                if (add_nature) {
                    $.ajax({
                        type: 'POST',
                        url: 'ajax/ajax_bizType.php',
                        data: {
                            add_nature: add_nature
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            $('#add_type').html(data.add_types);
                            // $('#type').empty().append(data.types);

                        }
                    });
                }
            });

            //ADD PRODUCT SERVICES
            $('#add_type').on('change', function() {
                var add_type = $(this).val();
                if (add_type) {
                    $.ajax({
                        type: 'POST',
                        url: 'ajax/ajax_bizType.php',
                        data: {
                            add_type: add_type
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            $('#add_productService').html(data.add_products);
                        }
                    });
                }
            });
        </script>

        <script>
            function validateNumber(input) {
                input.value = input.value.replace(/\D/g, '');

            }
        </script>

        <script>
            //TYPE
            $('#nature').on('change', function() {
                var nature = $(this).val();
                if (nature) {
                    $.ajax({
                        type: 'POST',
                        url: 'ajax/ajax_bizType.php',
                        data: {
                            nature: nature
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            $('#type').html(data.types);
                            // $('#type').empty().append(data.types);

                        }
                    });
                }
            });


            //PRODUCT SERVICES
            $('#type').on('change', function() {
                var type = $(this).val();
                if (type) {
                    $.ajax({
                        type: 'POST',
                        url: 'ajax/ajax_bizType.php',
                        data: {
                            type: type
                        },
                        success: function(response) {
                            var data = JSON.parse(response);
                            $('#productServices').html(data.products);
                        }
                    });
                }
            });
        </script>

        <script>
            function updateUserIdInput(clientId) {
                document.getElementById('add_userIdInput').value = clientId;
            }

            function openModal() {
                var modalButton = document.getElementById('addNew');
                if (modalButton) {
                    modalButton.click(); // Simulate a click on the 'addNew' button to open the modal
                }
            }

            // Event listener for when the page finishes loading
            // Removed duplicate DOMContentLoaded handler - using jQuery handler below instead




            // Function to open the modal





            // Client selection handler (works with Select2)
            $('#clientSelect').on('change', function() {
                console.log('Client select changed!');
                
                var clientId = $(this).val();
                var selectedOption = $(this).find('option:selected');
                var clientName = selectedOption.attr('data-clientname') || selectedOption.text();
                
                console.log('Selected client ID:', clientId);
                console.log('Selected client name:', clientName);
                
                if (!clientId) {
                    console.log('No client selected, returning');
                    return;
                }

                document.getElementById('add_userIdInput').value = clientId;
                document.getElementById('userIdInput').value = clientId;

                var newUrl = new URL(window.location.href);
                newUrl.searchParams.set('clientName', clientName);
                newUrl.searchParams.set('client_id', clientId);
                window.history.pushState({
                    path: newUrl.href
                }, '', newUrl.href);

                // Only update the input field if the $clientName variable is empty
                var clientNameInput = document.getElementById('clientName');
                if (!clientNameInput.value) {
                    clientNameInput.value = clientName;
                }

                // Add click handlers to loan rows for renewal
                function attachLoanRowHandlers() {
                    document.querySelectorAll('.loan-row').forEach(function(row) {
                        row.addEventListener('click', function() {
                            var loanId = this.getAttribute('data-loan-id');
                            loadLoanForRenewal(loanId);
                        });
                    });
                }

                console.log('Loading loans for client:', clientId);
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            console.log('Loans loaded successfully');
                            document.getElementById('loanTableBody').innerHTML = xhr.responseText;
                            document.getElementById('addNew').disabled = false;

                            // Add click handlers to loan rows for renewal
                            attachLoanRowHandlers();

                            var xhr2 = new XMLHttpRequest();
                            xhr2.onreadystatechange = function() {
                                if (xhr2.readyState === XMLHttpRequest.DONE) {
                                    if (xhr2.status === 200) {
                                        console.log(xhr2.responseText); // Check response here
                                        var response = JSON.parse(xhr2.responseText);

                                        if (response.success) {
                                            var loanAvailment = parseInt(response.loanAvailment) || 0;
                                            var nextAvailment = loanAvailment + 1;

                                            if (nextAvailment < 1) {
                                                nextAvailment = 1;
                                            }

                                            document.getElementById('add_availment').value = nextAvailment;
                                            console.log(nextAvailment);
                                        } else {
                                            console.error('Error retrieving loan data');
                                        }

                                        var xhr3 = new XMLHttpRequest();
                                        xhr3.onreadystatechange = function() {
                                            if (xhr3.readyState === XMLHttpRequest.DONE) {
                                                if (xhr3.status === 200) {
                                                    console.log(xhr3.responseText); // Check response here
                                                    var response = JSON.parse(xhr3.responseText);

                                                    if (response.success) {
                                                        var loanAvailment = parseInt(response.loanAvailment) || 0;
                                                        var nextAvailment = loanAvailment + 1;

                                                        if (nextAvailment < 1) {
                                                            nextAvailment = 1;
                                                        }

                                                        document.getElementById('availment').value = nextAvailment;
                                                        console.log(nextAvailment);
                                                    } else {
                                                        console.error('Error retrieving loan data');
                                                    }

                                                } else {
                                                    console.error('Error: ' + xhr3.status);
                                                }
                                            }
                                        };

                                        xhr3.open('GET', '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getLoanAvailment&client_id=' + clientId, true);
                                        xhr3.send();

                                    } else {
                                        console.error('Error: ' + xhr2.status);
                                    }
                                }
                            };

                            xhr2.open('GET', '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getLoanAvailment&client_id=' + clientId, true);
                            xhr2.send();

                            // Fetch client business information
                            var xhr4 = new XMLHttpRequest();
                            xhr4.onreadystatechange = function() {
                                if (xhr4.readyState === XMLHttpRequest.DONE) {
                                    if (xhr4.status === 200) {
                                        console.log('Client business data:', xhr4.responseText);
                                        var clientData = JSON.parse(xhr4.responseText);

                                        if (clientData.success) {
                                            // Populate business fields in new application form
                                            if (document.getElementById('add_sector')) {
                                                document.getElementById('add_sector').value = clientData.Sector || '';
                                            }
                                            if (document.getElementById('add_nature')) {
                                                document.getElementById('add_nature').value = clientData.BizNature || '';
                                            }
                                            if (document.getElementById('add_type')) {
                                                document.getElementById('add_type').value = clientData.BizType || '';
                                            }
                                            if (document.getElementById('add_productService')) {
                                                document.getElementById('add_productService').value = clientData.ProductService || '';
                                            }
                                        }
                                    } else {
                                        console.error('Error fetching client business data: ' + xhr4.status);
                                    }
                                }
                            };

                            xhr4.open('GET', '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getLoanTransaction&userId=' + clientId, true);
                            xhr4.send();

                        } else {
                            console.error('Error: ' + xhr.status);
                        }
                    }
                };
                xhr.open('GET', '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getClientLoans&client_id=' + clientId, true);
                xhr.send();
            });

            // document.getElementById('clientSelect').addEventListener('change', function() {
            //     var clientId = this.value; // Get the ID of the selected client
            //     document.getElementById('add_userIdInput').value = clientId; // Set the value of the input field

            //     // Make an AJAX request to fetch LoanAvailment data from another PHP file
            //     var xhr = new XMLHttpRequest();
            //     xhr.onreadystatechange = function() {
            //         if (xhr.readyState === XMLHttpRequest.DONE) {
            //             if (xhr.status === 200) {
            //                 var loanAvailment = xhr.responseText.trim(); // Trim any whitespace

            //                 // Check if loanAvailment is truthy (i.e., not empty or undefined)
            //                 if (loanAvailment) {
            //                     // Get the current value of add_availment and increment by 1
            //                     var currentAvailment = parseInt(document.getElementById('add_availment').value) || 0;
            //                     document.getElementById('add_availment').value = currentAvailment + 1;
            //                 }
            //             } else {
            //                 console.error('Error: ' + xhr.status);
            //             }
            //         }
            //     };

            //     // Specify the URL of your separate AJAX PHP file to fetch LoanAvailment
            //     xhr.open('GET', '/isyn-app/src/includes/accounts-monitoring/ajax/ajax_loan_availment.php?client_id=' + clientId, true);
            //     xhr.send();
            // });




            // xhr.open('GET', '/isyn-app/src/includes/accounts-monitoring/ajax/ajax_loan_availment.php?client_id=' + clientId, true);
        </script>

        <script>
            var originalValues = {};

            var formFields = document.querySelectorAll('form input, form select');
            formFields.forEach(function(field) {
                field.addEventListener('input', function() {
                    // document.getElementById('update_btn').disabled = false;

                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                var loanTypeSelect = document.getElementById('loanType');
                var renewOption = loanTypeSelect.querySelector('option[value="RENEW"]');

                if (renewOption) {
                    renewOption.selected = true; // Set 'RENEW' option as selected
                }
            });

            function enableFormFields() {
                var formElements = document.querySelectorAll('#form-renewal input, #form-renewal select');

                formElements.forEach(function(element) {
                    element.removeAttribute('disabled');
                });
            }


            function populateFormFields(row) {
                var userId = row.dataset.userId;
                // document.getElementById('userIdInput').value = userId;

                console.log(userId);

                if (userId) {
                    // Make an AJAX request to fetch user details
                    var xhr = new XMLHttpRequest();
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            if (xhr.status === 200) {
                                var userData = JSON.parse(xhr.responseText);

                                enableFormFields();

                                document.getElementById('editButton').disabled = false;


                                // Populate form fields with retrieved user data
                                // document.getElementById('loanType').value = userData.LoanType;
                                document.getElementById('tag').value = userData.Tag;
                                document.getElementById('poFco').value = userData.PO;
                                document.getElementById('program').value = userData.Program;
                                document.getElementById('product').value = userData.Product;
                                document.getElementById('mode').value = userData.Mode;
                                document.getElementById('termRate').value = userData.Term;
                                // document.getElementById('availment').value = userData.LoanAvailment;
                                // document.getElementById('amount').value = userData.LoanAmount;

                                var product = userData.Product;
                                var percentage = parseFloat(product.split(' ')[1].replace('%', ''));
                                document.getElementById('rate').value = percentage + "%";

                                document.getElementById('sector').value = userData.Sector;
                                document.getElementById('ageYear').value = userData.BizAgeYr;
                                document.getElementById('ageMonth').value = userData.BizAgeMo;
                                document.getElementById('nature').value = userData.BizNature;
                                document.getElementById('capital').value = userData.BizCapital;
                                document.getElementById('type').value = userData.BizType;
                                document.getElementById('workers').value = userData.Workers;
                                document.getElementById('productServices').value = userData.ProductService;
                                document.getElementById('moIncome').value = userData.MoIncome;
                                document.getElementById('group').value = userData.GroupName;


                                document.getElementById('lastname').value = userData.LastName;
                                document.getElementById('firstname').value = userData.FirstName;
                                document.getElementById('middlename').value = userData.MiddleName;

                                originalValues.loanType = userData.LoanType;
                                originalValues.tag = userData.Tag;
                                originalValues.poFco = userData.PO;
                                originalValues.program = userData.Program;
                                originalValues.product = userData.Product;
                                originalValues.mode = userData.Mode;
                                originalValues.termRate = userData.Term;
                                // originalValues.availment = userData.LoanAvailment;
                                originalValues.amount = userData.LoanAmount;
                                originalValues.rate = percentage + "%";
                                originalValues.sector = userData.Sector;
                                originalValues.ageYear = userData.BizAgeYr;
                                originalValues.ageMonth = userData.BizAgeMo;
                                originalValues.nature = userData.BizNature;
                                originalValues.capital = userData.BizCapital;
                                originalValues.type = userData.BizType;
                                originalValues.workers = userData.Workers;
                                originalValues.productServices = userData.ProductService;
                                originalValues.moIncome = userData.MoIncome;
                                originalValues.group = userData.GroupName;

                                originalValues.lastname = userData.LastName;
                                originalValues.firstname = userData.FirstName;
                                originalValues.middlename = userData.MiddleName;

                            } else {
                                console.error('Failed to fetch user details');
                            }
                        }
                    };

                    xhr.open('GET', '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getLoanTransaction&userId=' + userId, true);
                    xhr.send();
                } else {
                    clearFormFields();

                    document.getElementById('addNew').disabled = false;
                }
            }
        </script>

        <!---------------------------------------------------------------- VIEWING ---------------------------------------------------------------->
        <script>
            const dp = document.getElementById('downPayment');
            const dpAmount = document.getElementById('downPaymentAmount');
            const amount = document.getElementById('amount');


            dp.addEventListener('change', function() {
                if (!this.checked) {
                    dpAmount.value = '';

                    updatePrincipal(amount.value);
                    computeInterest();
                    computeTotaValue();
                    computeAmortizationValue();
                }

                dpAmount.disabled = !this.checked;

                computeDownPaymentValue();
            });

            var originalAmountValue = null;

            function computeDownPaymentValue() {
                var downpaymentAmount = parseFloat(document.getElementById('downPaymentAmount').value);
                var principalValue = document.getElementById('principalViewValue');

                if (!isNaN(downpaymentAmount)) {
                    var originalAmountValue = parseFloat(document.getElementById('amount').value);
                    var remainingAmount = originalAmountValue - downpaymentAmount;

                    principalValue.value = remainingAmount.toFixed(2);

                    computeInterest();
                    computeTotal();
                    computeAmortizationValue();
                }
            }


            function updateRate() {
                var productSelect = document.getElementById("product");
                var selectedOption = productSelect.options[productSelect.selectedIndex];
                var rateInput = document.getElementById("rate");

                var percentage = selectedOption.value.split(' ')[1];

                rateInput.value = percentage;

                computeInterest();
                computeTotaValue();
                computeAmortizationValue()
            }

            function updatePrincipal(amount) {
                const parsedAmount = parseFloat(amount);
                const principalValueElement = document.getElementById("principalViewValue");

                if (!isNaN(parsedAmount)) {
                    const formattedAmount = parsedAmount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    principalValueElement.value = formattedAmount;
                } else {
                    principalValueElement.value = "0.00";
                }

                computeInterest();
                computeTotaValue();
                computeAmortizationValue()

            }

            function computeInterest() {
                var viewTermRate = parseFloat(document.getElementById('termRate').value);
                var viewRate = parseFloat(document.getElementById('rate').value.replace('%', ''));
                var viewPrincipal = parseFloat(document.getElementById('principalViewValue').value);
                var viewDownpaymentAmount = parseFloat(document.getElementById('downPaymentAmount').value);

                var amountValue = parseFloat(document.getElementById('amount').value);


                // if (!isNaN(termRate) && !isNaN(rate)) {
                if (!isNaN(viewTermRate) && !isNaN(viewRate) && !isNaN(amountValue) && !isNaN(viewPrincipal)) {

                    if (!isNaN(viewDownpaymentAmount)) {
                        var interestValue = (viewTermRate * viewRate * viewPrincipal) / 100;
                    } else if (!isNaN(amountValue)) {
                        var interestValue = (viewTermRate * viewRate * amountValue) / 100;
                    }
                    document.getElementById('interestViewValue').value = interestValue.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById('interestViewValue').value = "0.00";
                }
                computeTotaValue();

            }

            function computeTotaValue() {
                var principalView = parseFloat(document.getElementById('principalViewValue').value.replace(',', ''));
                var interestView = parseFloat(document.getElementById('interestViewValue').value.replace(',', ''));

                if (!isNaN(principalView) && !isNaN(interestView)) {
                    var totalValue = principalView + interestView;
                    document.getElementById('totalViewValue').value = totalValue.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });


                } else {
                    document.getElementById('totalViewValue').value = "0.00";
                }
            }


            function computeAmortizationValue() {
                var mode = document.getElementById('mode').value;
                var term = parseFloat(document.getElementById('termRate').value);
                var principal = parseFloat(document.getElementById('principalViewValue').value.replace(',', ''));
                var amount = parseFloat(document.getElementById('amount').value.replace(',', ''));
                var rate = parseFloat(document.getElementById('rate').value.replace('%', '')) / 100;
                var downpaymentAmount = parseFloat(document.getElementById('downPaymentAmount').value);


                var totalPayments;

                if (mode === 'MONTHLY') {
                    totalPayments = term;
                } else if (mode === 'SEMI-MONTHLY') {
                    totalPayments = term * 2;
                } else if (mode === 'WEEKLY') {
                    totalPayments = term * 4;
                }

                if (!isNaN(term) && !isNaN(rate) && !isNaN(amount)) {
                    // var principalPerPayment;

                    if (!isNaN(downpaymentAmount)) {
                        var principalPerPayment = principal / totalPayments;
                    } else if (!isNaN(amount)) {
                        var principalPerPayment = amount / totalPayments;
                    }

                    // var roundedPrincipal = Math.round(principalPerPayment); // Round principal per payment

                    if ((principalPerPayment - Math.floor(principalPerPayment)) >= 0.5) {
                        var roundedPrincipal = Math.ceil(principalPerPayment);
                    } else {
                        var roundedPrincipal = Math.floor(principalPerPayment);
                    }

                    document.getElementById('principalAmortViewValue').value = roundedPrincipal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });


                    var totalInterest = roundedPrincipal * term * rate; // Calculate total interest

                    if ((totalInterest - Math.floor(totalInterest)) >= 0.5) {
                        totalInterest = Math.ceil(totalInterest);
                    } else {
                        totalInterest = Math.floor(totalInterest);
                    }

                    var totalAmortization = roundedPrincipal + totalInterest; // Calculate total amortization

                    document.getElementById('interestAmortViewValue').value = totalInterest.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    document.getElementById('totalAmortViewValue').value = totalAmortization.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById('principalAmortViewValue').value = "0.00";
                    document.getElementById('interestAmortViewValue').value = "0.00";
                    document.getElementById('totalAmortViewValue').value = "0.00";
                }
            }
        </script>


        <!--------------------------------------------------------- ADDING --------------------------------------------------------->
        <script>
            const add_dp = document.getElementById('add_downpayment');
            const add_dpAmount = document.getElementById('add_downpaymentAmount');
            const add_amount = document.getElementById('add_amount');


            add_dp.addEventListener('change', function() {
                if (!this.checked) {
                    add_dpAmount.value = '';

                    computeAddPrincipal(add_amount.value);
                    computeAddInterest();
                    computeTotal();
                    computeAmortization();
                }

                add_dpAmount.disabled = !this.checked;

                computeDownPayment();
            });

            var originalAmount = null;

            function computeDownPayment() {
                var downpaymentAmount = parseFloat(document.getElementById('add_downpaymentAmount').value);
                var principalValue = document.getElementById('principalAddValue');

                if (!isNaN(downpaymentAmount)) {
                    var originalAmount = parseFloat(document.getElementById('add_amount').value);
                    var remainingAmount = originalAmount - downpaymentAmount;

                    principalValue.value = remainingAmount.toFixed(2);



                    computeAddInterest();
                    computeTotal();
                    computeAmortization();
                }
            }


            function computePrincipal() {
                var productAddSelect = document.getElementById("add_product");
                var selectedAddOption = productAddSelect.options[productAddSelect.selectedIndex];
                var rateAddInput = document.getElementById("add_rate");

                var percentageAdd = selectedAddOption.value.split(' ')[1];

                rateAddInput.value = percentageAdd;

                computeAddInterest();

                computeTotal();

                computeAmortization();
            }

            function computeAddPrincipal(amountAdd) {
                const parsedAddAmount = parseFloat(amountAdd);
                const principalAddValueElement = document.getElementById("principalAddValue");

                if (!isNaN(parsedAddAmount)) {
                    const formattedAmount = parsedAddAmount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    principalAddValueElement.value = formattedAmount;
                } else {
                    principalAddValueElement.value = "0.00";
                }

                computeTotal();
            }

            function computeAddInterest() {
                var termRate = parseFloat(document.getElementById('add_termRate').value);
                var rate = parseFloat(document.getElementById('add_rate').value.replace('%', ''));
                var principal = parseFloat(document.getElementById('principalAddValue').value);
                var downpaymentAmount = parseFloat(document.getElementById('add_downpaymentAmount').value);

                var amount = parseFloat(document.getElementById('add_amount').value);


                // if (!isNaN(termRate) && !isNaN(rate)) {
                if (!isNaN(termRate) && !isNaN(rate) && !isNaN(amount) && !isNaN(principal)) {

                    if (!isNaN(downpaymentAmount)) {
                        var interest = (termRate * rate * principal) / 100;
                    } else if (!isNaN(amount)) {
                        var interest = (termRate * rate * amount) / 100;
                    }

                    document.getElementById('interestAddValue').value = interest.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    document.getElementById('interestAddValue').value = "0.00";
                }
                computeTotal();
            }


            function computeTotal() {
                var principal = parseFloat(document.getElementById('principalAddValue').value.replace(',', ''));
                var interest = parseFloat(document.getElementById('interestAddValue').value.replace(',', ''));

                if (!isNaN(principal) && !isNaN(interest)) {
                    var total = principal + interest;

                    document.getElementById('totalAddValue').value = total.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                } else {
                    document.getElementById('totalAddValue').value = "0.00";
                }
            }


            function computeAmortization() {
                // Use the original system's calculation logic
                var mode = document.getElementById('add_mode').value;
                var term = parseFloat(document.getElementById('add_termRate').value);
                var principal = parseFloat(document.getElementById('principalAddValue').value.replace(',', ''));
                var amount = parseFloat(document.getElementById('add_amount').value.replace(',', ''));
                var rate = parseFloat(document.getElementById('add_rate').value.replace('%', '')) / 100;
                var downpaymentAmount = parseFloat(document.getElementById('add_downpaymentAmount').value);

                // Debug: Log the input values
                console.log('Original system computation inputs:', {
                    mode: mode,
                    term: term,
                    principal: principal,
                    amount: amount,
                    rate: rate,
                    downpaymentAmount: downpaymentAmount
                });

                // Calculate total payments based on mode (original logic)
                var totalPayments;
                if (mode === 'MONTHLY') {
                    totalPayments = term;
                } else if (mode === 'SEMI-MONTHLY') {
                    totalPayments = term * 2;
                } else if (mode === 'WEEKLY') {
                    totalPayments = term * 4;
                } else {
                    totalPayments = term; // Default to monthly if mode not selected
                }

                if (!isNaN(term) && !isNaN(rate) && !isNaN(amount)) {
                    // Original system logic for principal per payment
                    var principalPerPayment;
                    if (!isNaN(downpaymentAmount)) {
                        principalPerPayment = principal / totalPayments;
                    } else if (!isNaN(amount)) {
                        principalPerPayment = amount / totalPayments;
                    }

                    // Original rounding logic
                    var roundedPrincipal;
                    if ((principalPerPayment - Math.floor(principalPerPayment)) >= 0.5) {
                        roundedPrincipal = Math.ceil(principalPerPayment);
                    } else {
                        roundedPrincipal = Math.floor(principalPerPayment);
                    }

                    // Original interest calculation: roundedPrincipal * term * rate
                    var totalInterest = roundedPrincipal * term * rate;

                    // Original rounding for interest
                    if ((totalInterest - Math.floor(totalInterest)) >= 0.5) {
                        totalInterest = Math.ceil(totalInterest);
                    } else {
                        totalInterest = Math.floor(totalInterest);
                    }

                    // Calculate total amortization per payment
                    var totalAmortizationPerPayment = roundedPrincipal + totalInterest;

                    console.log('Original system calculated values:', {
                        totalPayments: totalPayments,
                        principalPerPayment: principalPerPayment,
                        roundedPrincipal: roundedPrincipal,
                        totalInterest: totalInterest,
                        totalAmortizationPerPayment: totalAmortizationPerPayment
                    });

                    // Update Summary section (total amounts)
                    var summaryInterest = totalInterest * totalPayments; // Total interest for entire loan
                    var summaryTotal = principal + summaryInterest;

                    document.getElementById('interestAddValue').value = summaryInterest.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    document.getElementById('totalAddValue').value = summaryTotal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    // Set CBU, EF, MBA to 0.00 (not used in original system)
                    document.getElementById('cbuAddValue').value = "0.00";
                    document.getElementById('efAddValue').value = "0.00";
                    document.getElementById('mbaAddValue').value = "0.00";

                    // Update Amortization section (per payment amounts)
                    document.getElementById('principalAmortAddValue').value = roundedPrincipal.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    document.getElementById('interestAmortAddValue').value = totalInterest.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    // Set CBU, EF, MBA amortization to 0.00
                    document.getElementById('cbuAmortAddValue').value = "0.00";
                    document.getElementById('efAmortAddValue').value = "0.00";
                    document.getElementById('mbaAmortAddValue').value = "0.00";

                    document.getElementById('totalAmortAddValue').value = totalAmortizationPerPayment.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                } else {
                    console.log('Calculation conditions not met:', {
                        termValid: !isNaN(term),
                        rateValid: !isNaN(rate),
                        amountValid: !isNaN(amount),
                        term: term,
                        rate: rate,
                        amount: amount
                    });

                    // Show helpful message if required fields are missing
                    if (isNaN(term)) {
                        console.log('Please select a term to calculate amortization');
                    }
                    if (!mode) {
                        console.log('Please select a mode to calculate amortization');
                    }

                    // Reset all fields to 0.00 if inputs are invalid
                    var zeroValue = "0.00";

                    // Summary section
                    document.getElementById('interestAddValue').value = zeroValue;
                    document.getElementById('cbuAddValue').value = zeroValue;
                    document.getElementById('efAddValue').value = zeroValue;
                    document.getElementById('mbaAddValue').value = zeroValue;
                    document.getElementById('totalAddValue').value = zeroValue;

                    // Amortization section
                    document.getElementById('principalAmortAddValue').value = zeroValue;
                    document.getElementById('interestAmortAddValue').value = zeroValue;
                    document.getElementById('cbuAmortAddValue').value = zeroValue;
                    document.getElementById('efAmortAddValue').value = zeroValue;
                    document.getElementById('mbaAmortAddValue').value = zeroValue;
                    document.getElementById('totalAmortAddValue').value = zeroValue;
                }
            }

            function getUrlParameter(name) {
                name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
                var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
                var results = regex.exec(location.search);
                return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
            };

            // Get loan amount from URL query parameter
            var loanAmount = getUrlParameter('loanAmount');

            // Set loan amount in the input field
            document.getElementById('add_amount').value = loanAmount;

            // Form submission handler
            $('#loanApplicationForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate required fields
                var clientId = $('#add_userIdInput').val();
                var staff = $('select[name="add_poFco"]').val();
                var program = $('select[name="add_program"]').val();
                var product = $('#add_product').val();
                var mode = $('#add_mode').val();
                var term = $('#add_termRate').val();
                var amount = $('#add_amount').val();
                
                if (!clientId) {
                    showWarningNotification('Please select a client first', 'You must select a client before creating a loan application.');
                    return;
                }
                
                if (!staff || !program || !product || !mode || !term || !amount) {
                    showWarningNotification('Please fill in all required fields', 'All fields marked as required must be completed before submitting.');
                    return;
                }
                
                // Get form data
                var formData = $(this).serialize();
                formData += '&action=saveLoanApplication';
                
                // Show loading
                var submitBtn = $(this).find('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
                
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccessNotification('Loan application saved successfully!', 'Loan ID: ' + response.loanId);
                            $('#exampleModal').modal('hide');
                            $('#loanApplicationForm')[0].reset();
                            
                            // Reload loan table for the client
                            var clientId = $('#clientSelect').val();
                            if (clientId) {
                                $('#clientSelect').trigger('change');
                            }
                        } else {
                            showErrorNotification('Error saving loan application', response.message || 'An unknown error occurred.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        showErrorNotification('Connection Error', 'An error occurred while saving the loan application. Please try again.');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Function to load loan details for renewal
            function loadLoanForRenewal(loanId) {
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php?action=getLoanDetails&loan_id=' + loanId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.loan) {
                            var loan = response.loan;
                            
                            // Populate renewal form
                            $('#userIdInput').val(loan.ClientNo);
                            $('#loanType').val('RENEW');
                            $('#tag').val(loan.Tag || '-');
                            $('#poFco').val(loan.Staff);
                            $('#program').val(loan.Program);
                            $('#product').val(loan.Product);
                            $('#mode').val(loan.Mode);
                            $('#termRate').val(loan.Term);
                            $('#rate').val(loan.InterestRate + '%');
                            $('#availment').val(parseInt(loan.Availment || 0) + 1);
                            $('#amount').val(loan.LoanAmount);
                            
                            // Co-maker details if available
                            $('#form-renewal input[name="firstname"]').val(loan.CoMakerFirstName || '');
                            $('#form-renewal input[name="middlename"]').val(loan.CoMakerMiddleName || '');
                            $('#form-renewal input[name="lastname"]').val(loan.CoMakerLastName || '');
                            
                            // Enable form fields
                            $('#form-renewal input, #form-renewal select').prop('disabled', false);
                            $('#editButton').prop('disabled', false);
                            $('#cancelRenewalBtn').prop('disabled', false);
                            
                            // Enable renewal utilization fields
                            enableRenewalUtilizationFields();
                            
                            // Update renewal utilization loan amount
                            $('#renewalUtilizationLoanAmount').val(loan.LoanAmount);
                            updateRenewalUtilizationTotals();
                            
                            // Scroll to renewal form
                            $('html, body').animate({
                                scrollTop: $('#form-renewal').offset().top - 100
                            }, 500);
                        } else {
                            showErrorNotification('Error loading loan details', response.message || 'Unknown error occurred while loading loan information.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        showErrorNotification('Connection Error', 'An error occurred while loading loan details. Please try again.');
                    }
                });
            }

            // Cancel renewal button handler
            $('#cancelRenewalBtn').on('click', function() {
                // Clear all form fields
                $('#form-renewal')[0].reset();
                
                // Disable all form fields
                $('#form-renewal input, #form-renewal select').prop('disabled', true);
                
                // Disable buttons
                $('#editButton').prop('disabled', true);
                $('#cancelRenewalBtn').prop('disabled', true);
                
                // Clear hidden field
                $('#userIdInput').val('');
                
                // Clear renewal utilization table
                $('#renewalUtilizationTableBody tr').slice(1).remove();
                $('#renewalUtilizationTableBody tr:first select, #renewalUtilizationTableBody tr:first input').val('').prop('disabled', true);
                updateRenewalUtilizationTotals();
                
                // Scroll back to top of page
                $('html, body').animate({
                    scrollTop: 0
                }, 500);
            });

            // ========== UTILIZATION DETAILS FUNCTIONALITY ==========
            
            // Handle "New" button click to populate client name in modal
            $('#addNew').on('click', function() {
                var clientSelect = $('#clientSelect');
                var selectedOption = clientSelect.find('option:selected');
                var clientName = selectedOption.attr('data-clientname') || selectedOption.text();
                var clientId = clientSelect.val();
                
                // Populate client name in modal
                $('#clientName').val(clientName);
                $('#add_userIdInput').val(clientId);
            });
            
            // Add row to new application utilization table
            $('#addUtilizationRow').on('click', function() {
                var newRow = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm utilization-purpose" name="utilization_purpose[]">
                                <option value="">Select Purpose</option>
                                <option value="Working Capital">Working Capital</option>
                                <option value="Equipment Purchase">Equipment Purchase</option>
                                <option value="Business Expansion">Business Expansion</option>
                                <option value="Debt Consolidation">Debt Consolidation</option>
                                <option value="Emergency Expenses">Emergency Expenses</option>
                                <option value="Agricultural Inputs">Agricultural Inputs</option>
                                <option value="Livestock Purchase">Livestock Purchase</option>
                                <option value="Education">Education</option>
                                <option value="Housing Improvement">Housing Improvement</option>
                                <option value="Other">Other</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm utilization-amount" name="utilization_amount[]" placeholder="0.00" step="0.01" min="0">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-utilization-row"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#utilizationTableBody').append(newRow);
                updateUtilizationTotals();
            });

            // Remove row from new application utilization table
            $(document).on('click', '.remove-utilization-row', function() {
                if ($('#utilizationTableBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateUtilizationTotals();
                }
            });

            // Update totals when amount changes
            $(document).on('input', '.utilization-amount', function() {
                updateUtilizationTotals();
            });

            // Update loan amount when add_amount changes
            $(document).on('input', '#add_amount', function() {
                var loanAmount = parseFloat($(this).val().replace(/,/g, '')) || 0;
                $('#utilizationLoanAmount').val(loanAmount.toFixed(2));
                updateUtilizationTotals();
            });

            // Clear utilization table button
            $('#clearUtilizationTable').on('click', function() {
                if (confirm('Are you sure you want to clear all utilization details?')) {
                    // Remove all rows except the first one
                    $('#utilizationTableBody tr').slice(1).remove();
                    
                    // Clear the first row
                    $('#utilizationTableBody tr:first select').val('');
                    $('#utilizationTableBody tr:first input').val('');
                    
                    // Update totals
                    updateUtilizationTotals();
                }
            });

            function updateUtilizationTotals() {
                var total = 0;
                $('.utilization-amount').each(function() {
                    var amount = parseFloat($(this).val()) || 0;
                    total += amount;
                });
                
                var loanAmount = parseFloat($('#utilizationLoanAmount').val()) || 0;
                var difference = loanAmount - total;
                
                $('#utilizationTotal').val(total.toFixed(2));
                $('#utilizationDifference').val(difference.toFixed(2));
                
                // Color code the difference
                if (Math.abs(difference) < 0.01) {
                    $('#utilizationDifference').removeClass('text-danger text-warning').addClass('text-success');
                } else {
                    $('#utilizationDifference').removeClass('text-success text-warning').addClass('text-danger');
                }
            }

            // Add row to renewal utilization table
            $('#addRenewalUtilizationRow').on('click', function() {
                var newRow = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm renewal-utilization-purpose" name="renewal_utilization_purpose[]">
                                <option value="">Select Purpose</option>
                                <option value="Working Capital">Working Capital</option>
                                <option value="Equipment Purchase">Equipment Purchase</option>
                                <option value="Business Expansion">Business Expansion</option>
                                <option value="Debt Consolidation">Debt Consolidation</option>
                                <option value="Emergency Expenses">Emergency Expenses</option>
                                <option value="Agricultural Inputs">Agricultural Inputs</option>
                                <option value="Livestock Purchase">Livestock Purchase</option>
                                <option value="Education">Education</option>
                                <option value="Housing Improvement">Housing Improvement</option>
                                <option value="Other">Other</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm renewal-utilization-amount" name="renewal_utilization_amount[]" placeholder="0.00" step="0.01" min="0">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-renewal-utilization-row"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#renewalUtilizationTableBody').append(newRow);
                updateRenewalUtilizationTotals();
            });

            // Remove row from renewal utilization table
            $(document).on('click', '.remove-renewal-utilization-row', function() {
                if ($('#renewalUtilizationTableBody tr').length > 1) {
                    $(this).closest('tr').remove();
                    updateRenewalUtilizationTotals();
                }
            });

            // Update totals when renewal amount changes
            $(document).on('input', '.renewal-utilization-amount', function() {
                updateRenewalUtilizationTotals();
            });

            // Update renewal loan amount when amount changes
            $(document).on('input', '#amount', function() {
                var loanAmount = parseFloat($(this).val().replace(/,/g, '')) || 0;
                $('#renewalUtilizationLoanAmount').val(loanAmount.toFixed(2));
                updateRenewalUtilizationTotals();
            });

            // Clear renewal utilization table button
            $('#clearRenewalUtilizationTable').on('click', function() {
                if (confirm('Are you sure you want to clear all renewal utilization details?')) {
                    // Remove all rows except the first one
                    $('#renewalUtilizationTableBody tr').slice(1).remove();
                    
                    // Clear the first row
                    $('#renewalUtilizationTableBody tr:first select').val('');
                    $('#renewalUtilizationTableBody tr:first input').val('');
                    
                    // Update totals
                    updateRenewalUtilizationTotals();
                }
            });

            function updateRenewalUtilizationTotals() {
                var total = 0;
                $('.renewal-utilization-amount').each(function() {
                    var amount = parseFloat($(this).val()) || 0;
                    total += amount;
                });
                
                var loanAmount = parseFloat($('#renewalUtilizationLoanAmount').val()) || 0;
                var difference = loanAmount - total;
                
                $('#renewalUtilizationTotal').val(total.toFixed(2));
                $('#renewalUtilizationDifference').val(difference.toFixed(2));
                
                // Color code the difference
                if (Math.abs(difference) < 0.01) {
                    $('#renewalUtilizationDifference').removeClass('text-danger text-warning').addClass('text-success');
                } else {
                    $('#renewalUtilizationDifference').removeClass('text-success text-warning').addClass('text-danger');
                }
            }

            // Enable renewal utilization fields when loan is loaded
            function enableRenewalUtilizationFields() {
                $('#renewalUtilizationTableBody select, #renewalUtilizationTableBody input, #renewalUtilizationTableBody button').prop('disabled', false);
            }

            // Renewal form submission handler
            $('#form-renewal').on('submit', function(e) {
                e.preventDefault();
                
                // Validate required fields
                var clientId = $('#userIdInput').val();
                var staff = $('#poFco').val();
                var program = $('#program').val();
                var product = $('#product').val();
                var mode = $('#mode').val();
                var term = $('#termRate').val();
                var amount = $('#amount').val();
                
                if (!clientId) {
                    showWarningNotification('Please select a loan first', 'You must select a loan from the table before creating a renewal.');
                    return;
                }
                
                if (!staff || !program || !product || !mode || !term || !amount) {
                    showWarningNotification('Please fill in all required fields', 'All fields marked as required must be completed before submitting the renewal.');
                    return;
                }
                
                // Get form data
                var formData = $(this).serialize();
                formData += '&action=saveLoanRenewal';
                
                // Show loading
                var submitBtn = $('#editButton');
                var originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');
                
                $.ajax({
                    url: '<?php echo $base; ?>/routes/accountsmonitoring/loantransaction_simple.route.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccessNotification('Loan renewal saved successfully!', 'Loan ID: ' + response.loanId);
                            
                            // Reset form
                            $('#form-renewal')[0].reset();
                            $('#form-renewal input, #form-renewal select').prop('disabled', true);
                            submitBtn.prop('disabled', true);
                            
                            // Reload loan table for the client
                            var clientId = $('#clientSelect').val();
                            if (clientId) {
                                $('#clientSelect').trigger('change');
                            }
                        } else {
                            showErrorNotification('Error saving loan renewal', response.message || 'An unknown error occurred.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        showErrorNotification('Connection Error', 'An error occurred while saving the loan renewal. Please try again.');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

        // Function to show success notification
        function showSuccessNotification(message, details = '', autoDismiss = false) {
            $('#successMessage').text(message);
            $('#successDetails').text(details);
            
            // Clear any existing timers
            if (window.successTimer) {
                clearTimeout(window.successTimer);
            }
            if (window.progressTimer) {
                clearTimeout(window.progressTimer);
            }
            
            // Reset progress bar
            $('#successProgress').hide();
            $('#successProgress .progress-bar').css('width', '0%');
            
            // Show modal
            $('#successModal').modal('show');
            
            // Auto-dismiss success notifications after 3 seconds (optional)
            if (autoDismiss) {
                // Show and animate progress bar
                $('#successProgress').show();
                window.progressTimer = setTimeout(() => {
                    $('#successProgress .progress-bar').css('width', '100%');
                }, 100);
                
                window.successTimer = setTimeout(() => {
                    $('#successModal').modal('hide');
                }, 3000);
            }
            
            // Fallback cleanup after 10 seconds to prevent blocking
            setTimeout(() => {
                if ($('#successModal').hasClass('show')) {
                    $('#successModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }
            }, 10000);
            
            // Add haptic feedback for mobile devices
            addHapticFeedback('success');
        }

        // Function to show error notification
        function showErrorNotification(message, details = '') {
            $('#errorMessage').text(message);
            $('#errorDetails').text(details);
            
            setTimeout(() => {
                $('#errorModal').modal('show');
                
                // Add haptic feedback for mobile devices
                addHapticFeedback('error');
            }, 100);
        }

        // Function to show warning notification
        function showWarningNotification(message, details = '') {
            $('#warningMessage').text(message);
            $('#warningDetails').text(details);
            
            setTimeout(() => {
                $('#warningModal').modal('show');
                
                // Add haptic feedback for mobile devices
                addHapticFeedback('warning');
            }, 100);
        }

        // Optional: Add subtle vibration for mobile devices
        function addHapticFeedback(type = 'light') {
            if ('vibrate' in navigator) {
                switch(type) {
                    case 'success':
                        navigator.vibrate([100, 50, 100]);
                        break;
                    case 'error':
                        navigator.vibrate([200, 100, 200]);
                        break;
                    case 'warning':
                        navigator.vibrate([150]);
                        break;
                    default:
                        navigator.vibrate(100);
                }
            }
        }

        // Add event handlers for proper modal cleanup
        $(document).ready(function() {
            // Initialize calculations
            computeAmortization();
            
            // Success modal cleanup
            $('#successModal').on('hidden.bs.modal', function () {
                // Clear any existing timers
                if (window.successTimer) {
                    clearTimeout(window.successTimer);
                    window.successTimer = null;
                }
                if (window.progressTimer) {
                    clearTimeout(window.progressTimer);
                    window.progressTimer = null;
                }
                
                // Reset progress bar
                $('#successProgress').hide();
                $('#successProgress .progress-bar').css('width', '0%');
                
                // Ensure backdrop is removed and body is restored
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }, 100);
            });

            // Error modal cleanup
            $('#errorModal').on('hidden.bs.modal', function () {
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }, 100);
            });

            // Warning modal cleanup
            $('#warningModal').on('hidden.bs.modal', function () {
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }, 100);
            });

            // Allow clicking on progress bar to dismiss modal immediately
            $('#successProgress').on('click', function() {
                $('#successModal').modal('hide');
            });

            // Add keyboard support for dismissing modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    if ($('#successModal').hasClass('show')) {
                        $('#successModal').modal('hide');
                    }
                    if ($('#errorModal').hasClass('show')) {
                        $('#errorModal').modal('hide');
                    }
                    if ($('#warningModal').hasClass('show')) {
                        $('#warningModal').modal('hide');
                    }
                }
            });
        });

        </script>

        <!-- Success Notification Modal -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #1aa053 0%, #22c55e 100%);">
                                <i class="fa-solid fa-check text-white" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="mb-3 fw-bold" style="color: #1aa053;" id="successMessage">Operation completed successfully!</h5>
                        <p class="text-muted mb-4" id="successDetails"></p>
                        
                        <!-- Auto-dismiss progress indicator -->
                        <div class="progress mb-3" style="height: 3px; display: none;" id="successProgress">
                            <div class="progress-bar" style="background: linear-gradient(90deg, #1aa053, #22c55e); width: 0%; transition: width 3s linear;"></div>
                        </div>
                        
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold" 
                                style="background: linear-gradient(135deg, #1aa053 0%, #22c55e 100%); border: none; color: white;"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-check me-2"></i>OK
                        </button>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" 
                            data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
                </div>
            </div>
        </div>

        <!-- Error Notification Modal -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #c03221 0%, #ef4444 100%);">
                                <i class="fa-solid fa-xmark text-white" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="mb-3 fw-bold" style="color: #c03221;" id="errorMessage">An error occurred!</h5>
                        <p class="text-muted mb-4" id="errorDetails"></p>
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold" 
                                style="background: linear-gradient(135deg, #c03221 0%, #ef4444 100%); border: none; color: white;"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-times me-2"></i>OK
                        </button>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" 
                            data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
                </div>
            </div>
        </div>

        <!-- Warning Notification Modal -->
        <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #f16a1b 0%, #f59e0b 100%);">
                                <i class="fa-solid fa-exclamation text-white" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="mb-3 fw-bold" style="color: #f16a1b;" id="warningMessage">Please check your input!</h5>
                        <p class="text-muted mb-4" id="warningDetails"></p>
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold" 
                                style="background: linear-gradient(135deg, #f16a1b 0%, #f59e0b 100%); border: none; color: white;"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-exclamation me-2"></i>OK
                        </button>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" 
                            data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
                </div>
            </div>
        </div>

        <!-- Success Notification Modal -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #1aa053 0%, #22c55e 100%);">
                                <i class="fa-solid fa-check text-white" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="mb-3 fw-bold" style="color: #1aa053;" id="successMessage">Operation completed successfully!</h5>
                        <p class="text-muted mb-4" id="successDetails"></p>
                        
                        <!-- Auto-dismiss progress indicator -->
                        <div class="progress mb-3" style="height: 3px; display: none;" id="successProgress">
                            <div class="progress-bar" style="background: linear-gradient(90deg, #1aa053, #22c55e); width: 0%; transition: width 3s linear;"></div>
                        </div>
                        
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold" 
                                style="background: linear-gradient(135deg, #1aa053 0%, #22c55e 100%); border: none; color: white;"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-check me-2"></i>OK
                        </button>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" 
                            data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
                </div>
            </div>
        </div>

        <!-- Error Notification Modal -->
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #c03221 0%, #ef4444 100%);">
                                <i class="fa-solid fa-xmark text-white" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="mb-3 fw-bold" style="color: #c03221;" id="errorMessage">An error occurred!</h5>
                        <p class="text-muted mb-4" id="errorDetails"></p>
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold" 
                                style="background: linear-gradient(135deg, #c03221 0%, #ef4444 100%); border: none; color: white;"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-times me-2"></i>OK
                        </button>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" 
                            data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
                </div>
            </div>
        </div>

        <!-- Warning Notification Modal -->
        <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body text-center p-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #f16a1b 0%, #f59e0b 100%);">
                                <i class="fa-solid fa-exclamation text-white" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="mb-3 fw-bold" style="color: #f16a1b;" id="warningMessage">Please check your input!</h5>
                        <p class="text-muted mb-4" id="warningDetails"></p>
                        <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold" 
                                style="background: linear-gradient(135deg, #f16a1b 0%, #f59e0b 100%); border: none; color: white;"
                                data-bs-dismiss="modal">
                            <i class="fa-solid fa-exclamation me-2"></i>OK
                        </button>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" 
                            data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.5;"></button>
                </div>
            </div>
        </div>

    </body>
</html>

<?php
} else {
    // Authentication required but not provided
    echo "<!DOCTYPE html><html><head><title>Access Denied</title></head><body>";
    echo "<div style='text-align: center; margin-top: 50px;'>";
    echo "<h2>Access Denied</h2>";
    echo "<p>Please log in to access this page.</p>";
    echo "<a href='" . $base . "/login'>Go to Login</a>";
    echo "</div></body></html>";
}
?>