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

            <div class="container mt-4 mb-3">
                <div class=" shadow rounded-2  p-3" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">General Ledger</p>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">General</p>
                            <hr style="height: 1px">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                                        <label class="form-check-label" for="flexRadioDefault1"> As Of</label>
                                    </div>
                                    <input type="date" class="form-control" id="asOfDate" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2">
                                        <label class="form-check-label" for="flexRadioDefault2"> Date Range</label>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <input type="date" class="form-control" id="startDate" required>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="date" class="form-control" id="endDate" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Accounts Codes</p>
                            <hr style="height: 1px">
                            <table class="table table-hover table-borderless ">
                                <thead>
                                    <tr>
                                        <th>
                                            Account No.
                                        </th>
                                        <th>
                                            Account Tiles
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>123444 </td>
                                        <td>Isynergies</td>

                                    </tr>
                                    <tr>
                                        <td>568686</td>
                                        <td>Finance</td>

                                    </tr>
                                </tbody>
                            </table>
                            <div class="col-md-12 d-flex justify-content-end">
                                <button class="btn btn-primary px-3 py-2 mx-2" type="button" style="font-size: 14px; color: white;"><i class="fa-solid fa-repeat"></i> Retrieve</button>
                                <button class="btn btn-info px-3 py-2 mx-2" type="button" style="font-size: 14px; color: white;"><i class="fa-solid fa-magnifying-glass"></i> Preview</button>
                                <button class="tetxt-white btn btn-warning px-3 py-2 mx-2" type="button" style="font-size: 14px; color: white;"><i class="fa-solid fa-arrows-rotate"></i> Clear Folder</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Funding</p>
                            <hr style="height: 1px">
                            <div class="col-md-12">
                                <label for="companyname" class="form-label">Fund/Tag</label>
                                <select class="form-select" aria-label="Default select example">
                                    <option value="" selected>Select</option>
                                    <option value="">ACASH</option>
                                    <option value="">iSyn-Ilagan</option>
                                    <option value="">iSyn-Santiago</option>
                                    <option value="">iSynergies</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            $footerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
            include($footerPath);
        ?>

        <script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
        

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