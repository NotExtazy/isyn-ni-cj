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
      <title>iSyn | OR Setting</title>

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
            <style>
                main {
                    background-color: #EAEAF6;
                    height: 100vh;
                }
            </style>

            <div class="container-fluid mt-1">
                <div class="p-3 shadow rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="my-2 fs-5">OR Setting</p>
                </div>

                <div class="row mt-4">
                    <div class="col-md-7">
                        <div class="shadow mb-3 p-3 rounded-2" style="background-color: white;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="fw-medium fs-5" style="color: #090909;">Issued ORs</p>
                                <div>
                                    <button class="btn btn-primary me-2" id="addButton" onclick="AddNewSeries();" disabled>
                                        <i class="fa-regular fa-plus-square"></i> Add
                                    </button>
                                    <button class="btn btn-primary me-2" id="editButton" type="button" onclick="EditSeries();" disabled>
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                
                                    <button class="btn btn-danger" id="cancelButton" type="button" onclick="Cancel()" disabled>
                                        <i class="fa-solid fa-circle-xmark"></i> Cancel
                                    </button>
                                </div>
                            </div>
                            <hr style="height:1px">
                            <table id="orTbl" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                    </tr>
                                </thead>
                                <tbody id="orList">
                                
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div id="orSettingForm" class="shadow p-3 rounded-3" style="background-color: white;" disabled>
                            <p class="fw-medium fs-5" style="color: #090909;">OR Setting</p>
                            <hr style="height:1px">
                            <form id="ORForm" >
                                <div class="form-group row mb-2">
                                    <div class="col-sm-12">
                                        <input type="hidden" name="orID" id="orID" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <div class="col-sm-12">
                                        <input type="hidden" name="orName" id="orName" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="seriesStatus" class="col-sm-3 col-form-label">Status</label>
                                    <div class="col-sm-9">
                                        <select name="seriesStatus" id="seriesStatus" class="form-select" disabled>
                                            <option value="" selected disabled></option>
                                            <option value="OR">OR</option>
                                            <option value="AR">AR</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="from" class="col-sm-3 col-form-label">From</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="from" id="from" class="form-control" onchange="formatInput(this)" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="to" class="col-sm-3 col-form-label">To</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="to" id="to" class="form-control" onchange="formatInput(this)" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="nextOR" class="col-sm-3 col-form-label">Next OR</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="nextOR" id="nextOR" class="form-control" onchange="formatInput(this)" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="orsleft" class="col-sm-3 col-form-label">ORs Left</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="orsleft" id="orsleft" class="form-control" onchange="formatInput(this)" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <div class="col-sm-9">
                                        <button type="button" class="btn btn-primary me-2" id="saveButton" onclick="saveSeries();" disabled>
                                        <i class="fa-regular fa-floppy-disk"></i> Save
                                    </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/cashier/orsetting.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>