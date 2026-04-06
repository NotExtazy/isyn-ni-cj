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

            <div class="container mt-5">
                <div class="p-3 rounded-2 shadow-sm" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Subsidiary Ledger</p>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Date</p>
                            <hr style="height: 1px">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="options" id="asOfOption" value="asOf">
                                        <label class="form-check-label" for="asOfOption"> As Of</label>
                                    </div>
                                    <input type="date" class="form-control mt-2" id="asOfDate" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="options" id="dateRangeOption" value="dateRange">
                                        <label class="form-check-label" for="dateRangeOption"> Date Range</label>
                                    </div>
                                    <div class="row mt-2">
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
                        <div class="p-3 shadow rounded-2 mt-4 mt-md-0" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Funding</p>
                            <hr style="height: 1px">
                            <label for="region" class="form-label mt-2">Fund / Tag</label>
                            <select class="form-select" aria-label="Default select example">
                                <option value="" selected>Select</option>
                                <option value="">ACASH</option>
                                <option value="">iSyn-Ilagan</option>
                                <option value="">iSyn-Santiago</option>
                                <option value="">iSynergies</option>
                            </select>
                        </div>
                    </div>


                    <div class="container mt-4">
                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Account Codes</p>
                            <hr style="height: 1px">

                            <table class="table mt-3">
                                <thead>
                                    <tr>
                                        <th class="fw-bold fs-6" style="color:#090909">Account No.</th>
                                        <th class="fw-bold fs-6" style="color:#090909">Account Titles</th>
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
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="container mt-4 mb-4">
                        <div class="p-3 shadow rounded-2" style="background-color: white;">
                            <p class="fw-medium fs-5" style="color: #090909;">Subsidiary Codes</p>
                            <hr style="height: 1px">
                            <div class="form-group row mb-2">
                                <label for="date" class="col-sm-2 col-form-label">SL Type</label>
                                <div class="col-sm-3">
                                    <select class="form-select" aria-label="Default select example">
                                        <option value="" selected>Select</option>
                                        <option value="">ACASH</option>
                                        <option value="">Accrued Expense</option>
                                        <option value="">Bank</option>
                                        <option value="">Client</option>
                                        <option value="">Customer</option>
                                        <option value="">Due to</option>
                                        <option value="">Employee</option>
                                        <option value="">Fund</option>
                                        <option value="">Merchandise Sales</option>
                                        <option value="">Other Expenses</option>
                                        <option value="">Others</option>
                                        <option value="">Services</option>
                                        <option value="">Stockholders</option>
                                        <option value="">Tax</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col">
                                <table class="table mt-3">
                                    <thead>
                                        <tr>
                                            <th class="fw-bold fs-6" style="color:#090909">Account No.</th>
                                            <th class="fw-bold fs-6" style="color:#090909">Account Titles</th>
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
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-end mt-3">
                                <button class="btn btn-primary px-3 py-2 mx-2" type="button" style="font-size: 14px; color: white;"><i class="fa-solid fa-repeat"></i> Retrieve</button>
                                <button class="btn btn-info px-3 py-2 mx-2" type="button" style="font-size: 14px; color: white;"><i class="fa-solid fa-magnifying-glass"></i> Preview</button>
                                <button class="text-white btn btn-warning px-3 py-2 mx-2" type="button" style="font-size: 14px; color: white;"><i class="fa-solid fa-arrows-rotate"></i> Clear Folder</button>
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