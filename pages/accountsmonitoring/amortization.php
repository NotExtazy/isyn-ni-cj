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

            <div class="container mt-4">
                <div class=" p-3 shadow rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="my-2 fs-5">Amortization</p>
                </div>


                <div class="row mt-4">
                    <div>
                        <ul class="nav nav-tabs rounded shadow" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-tab-pane" type="button" role="tab" aria-controls="list-tab-pane" aria-selected="true">List</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link " id="new-tab" data-bs-toggle="tab" data-bs-target="#new-tab-pane" type="button" role="tab" aria-controls="new-tab-pane" aria-selected="false">Add New</button>
                            </li>

                        </ul>
                    </div>
                    
                    <div class="tab-content" id="myTabContent">

                        <!-- LIST -->
                        <div class="tab-pane fade show active" id="list-tab-pane" role="tabpanel" aria-labelledby="list-tab" tabindex="0">
                            <div class="row">
                                <div class="d-flex">
                                    <div class="mt-4 col-md-12 p-3 rounded shadow" style="background-color:white;">
                                        <div class="row">
                                            <div class="col-md-6 mt-3">
                                                <select class="form-select" aria-label="Default select example">
                                                    <option value="" selected>Select</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mt-3">
                                                <label for="file-upload" class="btn btn-info mb-2"><i class="fa-solid fa-upload"></i> Upload</label>
                                                <input id="file-upload" type="file" style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form class="mt-4 p-3 shadow rounded-3" style="background-color: white;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="fs-5" style="color: #090909;">Equipment Lists</p>
                                </div>
                                <div class="row">
                                    <div class="col-md-1 mt-2">
                                    <label class="me-2" for="">Filter:</label>
                                    </div>
                                    <div class="col-md-5 mt-2">
                                        <select class="form-select me-2" aria-label="Default select example">
                                            <option value="" selected>Select</option>
                                            <option value="">...</option>
                                            <option value="">...</option>
                                            <option value="">...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mt-2">
                                        <button type="button" class="btn btn-danger mb-2 me-2"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </div>
                                </div>
                                <div class="overflow-auto">
                                    <table class="table table-borderless table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th>Assets</th>
                                                <th>Total Amount</th>
                                                <th>Accumulated Amortizarion</th>
                                                <th>Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>

                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>

                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                            </form>
                            <form class="mt-4 p-3 mb-3 shadow rounded-3" style="background-color: white;" >
                                <div class="overflow-auto" id="printlist">
                                    <table class="table table-borderless table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Employee Name</th>
                                                <th>Basic</th>
                                                <th>Jan</th>
                                                <th>Feb</th>
                                                <th>Mar</th>
                                                <th>Apr</th>
                                                <th>May</th>
                                                <th>Jun</th>
                                                <th>Jul</th>
                                                <th>Aug</th>
                                                <th>Sep</th>
                                                <th>Oct</th>
                                                <th>No</th>
                                                <th>Dec</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>

                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>

                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>

                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-info mt-2 mb-2 me-2" onclick="printLogsList('printlist')"><i class="fa-solid fa-print"></i> Print</button>
                                </div>
                            </form>

                        </div>


                        <!-- NEW -->
                        <div class="tab-pane fade" id="new-tab-pane" role="tabpanel" aria-labelledby="new-tab" tabindex="0">
                            <div class="mt-1 mb-4">

                                <div class="row">
                                    <div class="col-md-4">
                                        <form class="mt-4 mb-4 p-3 shadow rounded-3 container" style="background-color: white;">
                                            <div class="col d-flex align-items-center justify-content-between">
                                                <p class="fs-5" style="color: #090909;">Company Information</p>
                                                <button type="button" class="btn btn-success me-2 text-end"><i class="fa-solid fa-plus"></i> New</button>
                                            </div>
                                            <hr style="height:1px">
                                            <label for="" class="form-label fw-medium" style="color: #090909">Transaction ID:</label>
                                            <input type="text" class="form-control">
                                            <div class="mt-3">
                                                <label class="form-label fw-medium" style="color: #090909">Category:</label>
                                                <select class="form-select" aria-label="Default select example">
                                                    <option value="" selected>Select</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                </select>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label fw-medium" style="color: #090909">Branch:</label>
                                                <select class="form-select" aria-label="Default select example">
                                                    <option value="" selected>Select</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                </select>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label fw-medium" style="color: #090909">Department:</label>
                                                <select class="form-select" aria-label="Default select example">
                                                    <option value="" selected>Select</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                </select>
                                            </div>
                                            <div class="mt-3">
                                                <label class="form-label fw-medium" style="color: #090909">Type of Assets:</label>
                                                <select class="form-select" aria-label="Default select example">
                                                    <option value="" selected>Select</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                </select>
                                            </div>
                                        </form>
                                        <!-- Staff Form -->
                                        <form class="mt-4 mb-4 p-3 shadow rounded-3 container" style="background-color: white;">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <p class="fs-5" style="color: #090909;">Staff</p>

                                            </div>
                                            <hr style="height:1px">
                                            <label class="form-label fw-medium" style="color: #090909">Employee ID:</label>
                                            <input type="text" class="form-control">
                                            <div class="mt-2">
                                                <label class="form-label fw-medium" style="color: #090909">Employee name:</label>
                                                <select class="form-select" aria-label="Default select example">
                                                    <option value="" selected>Select</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                    <option value="">...</option>
                                                </select>
                                            </div>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                                <label class="form-check-label" style="color: #090909;" for="flexCheckDefault">Other </label>
                                            </div>
                                            <div>
                                                <label class="form-label fw-medium" style="color: #090909">Start</label>
                                                <input type="date" class="form-control">
                                                <div class="d-flex align-items-center justify-content-end mt-3">
                                                    <button type="button" class="btn btn-success ms-auto"><i class="fa-solid fa-check-circle"></i> Submit</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-8">
                                        <form class="mt-4 mb-4 p-3 shadow rounded-3 container" style="background-color: white;">
                                            <div class="col d-flex align-items-center justify-content-between">
                                                <p class="fs-5" style="color: #090909;">Equipment List</p>
                                                <button type="button" class="btn btn-danger mb-2"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                            </div>

                                            <hr style="height: 1px">
                                            <table class="table table-borderless table-hover mt-3">
                                                <thead>
                                                    <tr>
                                                        <th>Employee Name</th>
                                                        <th>Employee No</th>
                                                        <th>Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>John Doe</td>
                                                        <td>E001</td>
                                                        <td>₱500</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Jane Smith</td>
                                                        <td>E002</td>
                                                        <td>₱700</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Michael Johnson</td>
                                                        <td>E003</td>
                                                        <td>₱600</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Sarah Brown</td>
                                                        <td>E004</td>
                                                        <td>₱800</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </form>
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

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <!-- <script src="../../js/generalledger/posting.js?<?= time() ?>"></script> -->

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>

<script>
    function printLogsList(areaID) {
        var printContent = document.getElementById(areaID).innerHTML;
        var originalContent = document.body.innerHTML;
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
    }
</script>