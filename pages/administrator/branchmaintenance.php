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
    
    <body class="">
        <div id="loading">
            <div class="loader simple-loader">
                <div class="loader-body"></div>
            </div>
        </div>

        <style>
            label { color: #090909; }
            form { width: 100%; padding: 20px; background-color: white; border-radius: 10px; }
            main { background-color: #EAEAF6; }
            th { font-weight: bold; color: #090909; position: sticky; top: 0; }
            
            .selected td { background-color: lightgray; } 
        </style>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

            <div class="container-fluid mt-1">
                <div class="shadow p-3 rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Branch Setup</p>
                </div>

                <div class="row mt-4 ">
                    <div class="col-md-12">
                        <div class="shadow p-3 rounded-3" style="background-color: white; overflow: auto">
                            <div class="align-items-center justify-content-between mb-3">
                                <p class="fw-medium fs-5" style="color: #090909;">Branch List</p>
                            </div>
                            <hr style="height: 1px">
                            <table id="BranchTbl" class="table table-bordered text-center" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="text-align:center">ID</th>
                                        <th style="text-align:center">Branch Address</th>
                                        <th style="text-align:center">Telephone</th>
                                        <th style="text-align:center">Manager</th>
                                        <th style="text-align:center">Permit No.</th>
                                    </tr>
                                </thead>
                                <tbody id="BranchList">
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <form class="p-3 needs-validation mb-3 shadow" novalidate method="POST" id="branchForm" autocomplete="off">
                            <div class="align-items-center justify-content-between mb-3">
                                <button class="btn btn-primary float-end mx-2" id="editButton" type="button" disabled>
                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                </button>
                                <button class="btn btn-success float-end mx-2" id="addNew" type="button" name="new"> 
                                    <i class="fa-solid fa-plus"></i> New
                                </button>
                                <p class="fw-medium fs-5" style="color: #090909;">Branch Details</p>
                            </div>
                            <hr style="height: 1px">

                            <input type="hidden" id="ID" name="ID">

                            <div class="row">
                                <div class="col-md-4">
                                    <label for="Telephone" class="form-label">Telephone No.</label>
                                    <input type="text" class="form-control" id="Telephone" name="Telephone" placeholder="02-XXXX-XXXX" maxlength="16" required disabled oninput="this.value = this.value.replace(/[^0-9-]/g, '')">
                                    <div class="invalid-feedback">Please provide valid Telephone No.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="TIN" class="form-label">Branch TIN</label>
                                    <input type="text" class="form-control" id="TIN" name="TIN" placeholder="XXX-XXX-XXX-XXX" maxlength="20" required disabled oninput="this.value = this.value.replace(/[^0-9-]/g, '')">
                                    <div class="invalid-feedback">Please provide the TIN.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="Permit" class="form-label">Business Permit No.</label>
                                    <input type="text" class="form-control" id="Permit" name="Permit" placeholder="Permit Number" maxlength="20" required disabled oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">Please provide the Permit No.</div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label for="Manager" class="form-label">Branch Manager</label>
                                    <select class="form-select" id="Manager" name="Manager" required disabled>
                                        <option value="" selected>Select Manager</option>
                                        </select>
                                    <div class="invalid-feedback">Please select a Manager.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="BookKeeper" class="form-label">Bookkeeper</label>
                                    <input type="text" class="form-control" id="BookKeeper" name="BookKeeper" placeholder="Full Name" required disabled oninput="this.value = this.value.toUpperCase().replace(/[^a-zA-Z\s.,]/g, '')">
                                    <div class="invalid-feedback">Please provide Bookkeeper's Name.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="SalesRep" class="form-label">Sales Representative</label>
                                    <input type="text" class="form-control" id="SalesRep" name="SalesRep" placeholder="Full Name" required disabled oninput="this.value = this.value.toUpperCase().replace(/[^a-zA-Z\s.,]/g, '')">
                                    <div class="invalid-feedback">Please provide Sales Rep Name.</div>
                                </div>
                            </div>

                            <hr class="mt-4 mb-3">

                            <p class="fw-medium fs-5" style="color: #090909;">Address</p>
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <label for="Address" class="form-label">Branch Address</label>
                                    <input type="text" class="form-control" id="Address" name="Address" placeholder="Complete Address (Street, Brgy, City, Province)" required disabled oninput="this.value = this.value.toUpperCase().replace(/[^a-zA-Z0-9\s.,-]/g, '')">
                                    <div class="invalid-feedback">Please provide the complete address.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 mt-4">
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
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../js/maintenance.js?<?= time() ?>"></script>
        <script src="../../js/profiling/branchmaintenance.js?<?= time() ?>"></script> 

    </body>
</html>
<?php
    } else {
        echo '<script> window.location.href = "../../login"; </script>';
    }
?>