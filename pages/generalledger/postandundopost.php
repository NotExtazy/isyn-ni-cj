<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        $permissionsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/permissions.php';
        require_once($permissionsPath);
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
        $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
        include($headerPath);
    ?>
      <link rel="stylesheet" href="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
            $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
            include($sidebarPath);
            include($navbarPath);
        ?>


            <div class="container-fluid mt-1">
                <div class=" shadow p-3 rounded-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">GL Posting</p>
                </div>
                <div class="row">
                    <div class="col-md-6 mt-2">
                        <div class="p-3 rounded-2 shadow" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Posting</p>
                            <hr style="height: 1px">
                            <div class="col-md-12">
                                <label for="" class="form-label">Date:</label>
                                <input type="text" class="form-control Date" id="postingDate" name="postingDate">
                            </div>
                            <div class="col-md-12 d-flex justify-content-center mt-2">
                                <button class="btn btn-primary px-3 py-2 mx-1 w-100" id="postBtn" name="postBtn" type="button" onclick="PostGL();"><i
                                        class="fa-solid fa-upload"></i>
                                    Post
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mt-2">
                        <div class="p-3 rounded-2 shadow" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Undo Posting</p>
                            <hr style="height: 1px">
                            <div class="col-md-12">
                                <label for="" class="form-label">Date:</label>
                                <input type="text" class="form-control Date" id="unpostingDate" name="unpostingDate">
                            </div>
                            <div class="col-md-12 d-flex justify-content-center mt-2">
                                <button class="text-white btn btn-warning px-3 py-2 mx-1 w-100" id="unpostBtn" name="unpostBtn" type="button" onclick="UnPostGL();"><i
                                        class="fa-solid fa-rotate-left"></i>
                                    Undo Post
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <?php
            $footerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
            include($footerPath);
        ?>

        <script src="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
        <script src="/iSynApp-main/js/generalledger/posting.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>

<script>
    // calendar function
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('.form-control[type="date"]');

        dateInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                let value = input.value;
                let parts = value.split('-');
                if (parts.length === 3) {
                    let year = parts[0].substring(0, 4); // Limiting to 4 digits
                    let month = parts[1];
                    let day = parts[2];
                    input.value = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                }
            });

            input.addEventListener('blur', function() {
                let value = input.value;
                let parts = value.split('-');
                if (parts.length === 3) {
                    let year = parts[0].substring(0, 4); // Limiting to 4 digits
                    let month = parts[1].padStart(2, '0');
                    let day = parts[2].padStart(2, '0');
                    input.value = `${year}-${month}-${day}`;
                }
            });
        });
    });
</script>