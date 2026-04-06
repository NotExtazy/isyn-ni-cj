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
      <title>iSyn | DS Setting</title>

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
                <div class=" p-3 shadow rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="my-2 fs-5">DS Setting</p>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-5">
                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                            <div>
                                <button type="button" id="saveDS" class="btn btn-primary" class="btn btn-primary" onclick="SaveDS()" disabled ><i class="fa-solid fa-pen-to-square"></i>Save</button>
                                <button type="button" id="editDS" class="btn btn-primary" class="btn btn-primary" onclick="EditDS()" disabled><i class="fa-solid fa-pen-to-square"></i>Edit</button>
                                <button type="button" id="cancel" class="btn btn-danger" onclick="Cancel()" disabled><i class="fa-regular fa-circle-xmark"></i>Cancel</button>
                            </div>
                            <hr style="height:1px">
                            <form method="POST">
                                <div class="form-group row mb-2">
                                    <label for="fund" class="col-sm-4 col-form-label">Select Funding</label>
                                    <div class="col-sm-6">
                                        <select name="fund" id="fund" class="form-select" onchange="LoadBank(this.value)">

                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="bank" class="col-sm-4 col-form-label">Default Bank</label>
                                    <div class="col-sm-6">
                                        <select name="bank" id="bank" class="form-select" disabled>

                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="lastDSNo" class="col-sm-4 col-form-label">Last DS No. used</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="lastDSNo" id="lastDSNo" class="form-control" onchange="formatInput(this)" disabled>
                                    </div>
                                </div>
                                <div class="form-group row mb-2">
                                    <label for="dsPrefix" class="col-sm-4 col-form-label">DS Prefix</label>
                                    <div class="col-sm-6">
                                        <input type="text" name="dsPrefix" id="dsPrefix" class="form-control" onchange="formatInput(this)" disabled>
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
        <script src="../../js/cashier/dssetting.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>