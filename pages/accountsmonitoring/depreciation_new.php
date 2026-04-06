<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    // Set test authentication if not already set (for development)
    if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !isset($_SESSION["AUTHENTICATED"])) {
        $_SESSION['EMPNO'] = 'TEST001';
        $_SESSION['USERNAME'] = 'testuser';
        $_SESSION['AUTHENTICATED'] = true;
    }
    
    // Skip RBAC for development - can be re-enabled later
    // require_once('../../includes/permissions.php');
    // $permissions = new Permissions();
    // if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
    //     header("Location: ../../dashboard");
    //     exit;
    // }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include(__DIR__ . '/../../includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/select2/css/select2.min.css">

    <style>#loading { display: none !important; }</style>
    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include(__DIR__ . '/../../includes/pages.sidebar.php');
            include(__DIR__ . '/../../includes/pages.navbar.php');
        ?>

            <div class="container mt-4">
                <div class="shadow p-3 rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Depreciation</p>
                </div>

                <div class="row mt-2">
                    <div class="col-md-4 mt-2">
                        <div class="shadow p-3 rounded-2" style="background-color:white">
                            <div class=" d-flex mt-3 mb-3 justify-content-start">
                                <button class="btn btn-info mx-2" onclick="printList('lists')"><i class="fa-solid fa-print"></i> Print</button>
                                <button class="text-white btn btn-primary" type="submit" data-bs-toggle="modal" data-bs-target="#generateJV"> <i class="fa-solid fa-newspaper"></i> Generate JV</button>
                            </div>
                            <label for="" class="form-label">Select:</label>
                            <select class="form-select">
                                <option value="">Normal</option>
                                <option value="">...</option>
                                <option value="">...</option>
                            </select>
                            <div class="justify-content-end">
                                <button class="btn btn-primary mt-3" type="submit"> <i class="fa-solid fa-floppy-disk"></i> Update</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8 mt-2">
                        <div class="shadow p-3 rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Memorandum History </p>
                            <hr style="height: 1px">
                            <table class="table table-bordered" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Employee No.</th>
                                        <th>Employee Name</th>
                                        <th>Position </th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    
                </div>
                
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="shadow mb-4 p-3 rounded-2" style="background-color:white">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="fw-medium fs-5" style="color: #090909;">Equipment List</p>
                                </div>
                                <div class="col-md-6 buttons text-end">
                                    <button class="btn btn-success " data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa-solid fa-plus"></i> New</button>
                                    <button class="btn btn-primary float-end mx-2" id="editButton" class="btn btn-primary float-end" type="button" onclick="toggleEdit()"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                    <button class="btn btn-danger" type="reset"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                </div>
                            </div>
                            <hr style="height: 1px">
                    
                            <div class="container">
                                <div class="row m-1">
                                    <div class="col-md-2">
                                        <p>Equipment log</p>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                        <div class="text">
                                            <label for="filterAssets" class="form-label">Filter:</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8 d-flex align-items-center">
                                        <div class="text">
                                            <select id="filterAssets" class="form-select">
                                                <option value="">All Assets</option>
                                                <option value="Asset 1">Asset 1</option>
                                                <option value="Asset 2">Asset 2</option>
                                                <option value="Asset 3">Asset 3</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                    
                                <div class="row">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Assets</th>
                                                <th>Total Cost</th>
                                                <th>Monthly Dep</th>
                                                <th>Net Book Value</th>
                                                <th>Date Acquired</th>
                                                <th>Memorandum</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>
                    
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal for Generate JV -->
                <div class="modal fade" id="generateJV" tabindex="-1" aria-labelledby="generateJV" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="generateJV">Journal Vouchers</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-1 mt-2">
                                        <label for="" class="form-label">Date:</label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="date" class="form-control" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label for="" class="form-label">Report Type:</label>
                                    </div>
                                    <div class="col-md-4">
                                        <select name="" id="" class="form-select">
                                            <option value="" selected>Select</option>
                                            <option value="">...</option>
                                            <option value="">...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-info mb-2 me-2"><i class="fa-solid fa-caret-down"></i> Retrieve</button>
                                    </div>
                                </div>
                                <div class="container mt-3">
                                    <p class=" fs-5" style="color: #090909;">Memorandum History </p>
                                    <hr style="height:1px">
                                    <table class="table table-borderless table-hover mt-3">
                                        <thead>
                                            <tr>
                                                <th>Assets</th>
                                                <th>Monthly Dep.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-center ">
                                    <button type="button" class="btn btn-primary mx-2"><i class="fa-solid fa-arrow-up"></i></button>
                                    <button type="button" class="btn btn-warning"><i class="fa-solid fa-arrow-down"></i></button>
                                </div>
                                <div class="navs-tabs mt-3">
                                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="select-equipment-tab" data-bs-toggle="tab" data-bs-target="#select-equipment-tab-pane" type="button" role="tab" aria-controls="select-equipment-tab-pane" aria-selected="true">Select equipment</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="undepreciate-tab" data-bs-toggle="tab" data-bs-target="#undepreciate-tab-pane" type="button" role="tab" aria-controls="undepreciate-tab-pane" aria-selected="false">Undepreciate previous month</button>
                                        </li>
                                    </ul>
                
                
                                    <div class="tab-content p-3" id="myTabContent">
                                        <div class="tab-pane fade show active mt-3" id="select-equipment-tab-pane" role="tabpanel" aria-labelledby="select-equipment-tab" tabindex="0">
                                            <input type="checkbox" class="me-2"><label for=""> Select all</label>
                                            <div class="overflow-auto mt-2 mb-2">
                                                <table class="table table-borderless table-hover mt-3">
                                                    <thead>
                                                        <tr>
                                                            <th>Assets</th>
                                                            <th>Monthly Dep.</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
