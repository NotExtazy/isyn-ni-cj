<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
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
            
            <?php
                $maintenance = isset($_GET['maintenance']) ? $_GET['maintenance'] : '';
            ?>

            <style>
                main {
                    background-color: #eaeaf6;
                    height: 100% ;
                }
            </style>

            <div class="container mt-4">
                <div class="p-3 shadow rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Configuration Accounts Monitoring</p>
                </div>

                <div class="shadow rounded-2 mb-3" style="background-color: white;">
                    <div class="container mt-4">
                        <div class="container mt-4">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'loansetup' || empty($_GET['maintenance'])) ? 'active bg-primary text-white' : ''; ?>" id="schedA-tab" data-bs-toggle="tab" data-bs-target="#schedA-tab-pane" type="button" role="tab" aria-controls="schedA-tab-pane" aria-selected="<?= ($maintenance === 'loansetup' || empty($_GET['maintenance'])) ? 'true' : 'false'; ?>" href="?maintenance=loansetup">Loan Setup</button>
                                </li>
                                <li class="nav-item" role="presentation2">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'loan_type') ? 'active bg-primary text-white' : ''; ?>" id="schedB-tab" data-bs-toggle="tab" data-bs-target="#schedB-tab-pane" type="button" role="tab" aria-controls="schedB-tab-pane" aria-selected="<?= ($maintenance === 'loan_type') ? 'true' : 'false'; ?>" href="?maintenance=loan_type">Loan Type</button>
                                </li>
                                <li class="nav-item" role="presentation3">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'mode') ? 'active bg-primary text-white' : ''; ?>" id="schedC-tab" data-bs-toggle="tab" data-bs-target="#schedC-tab-pane" type="button" role="tab" aria-controls="schedC-tab-pane" aria-selected="<?= ($maintenance === 'mode') ? 'true' : 'false'; ?>" href="?maintenance=mode">Mode</button>
                                </li>
                                <li class="nav-item" role="presentation4">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'insurance') ? 'active bg-primary text-white' : ''; ?>" id="schedD-tab" data-bs-toggle="tab" data-bs-target="#schedD-tab-pane" type="button" role="tab" aria-controls="schedD-tab-pane" aria-selected="<?= ($maintenance === 'insurance') ? 'true' : 'false'; ?>" href="?maintenance=insurance">Insurance </button>
                                </li>
                                <li class="nav-item" role="presentation5">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'sector') ? 'active bg-primary text-white' : ''; ?>" id="schedE-tab" data-bs-toggle="tab" data-bs-target="#schedE-tab-pane" type="button" role="tab" aria-controls="schedE-tab-pane" aria-selected="<?= ($maintenance === 'sector') ? 'true' : 'false'; ?>" href="?maintenance=sector">Sector </button>
                                </li>
                                <li class="nav-item" role="presentation6">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'religion') ? 'active bg-primary text-white' : ''; ?>" id="schedF-tab" data-bs-toggle="tab" data-bs-target="#schedF-tab-pane" type="button" role="tab" aria-controls="schedF-tab-pane" aria-selected="<?= ($maintenance === 'religion') ? 'true' : 'false'; ?>" href="?maintenance=religion">Religion </button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'location') ? 'active bg-primary text-white' : ''; ?>" id="schedG-tab" data-bs-toggle="tab" data-bs-target="#schedG-tab-pane" type="button" role="tab" aria-controls="schedG-tab-pane" aria-selected="<?= ($maintenance === 'location') ? 'true' : 'false'; ?>" href="?maintenance=location">Location</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'citizenship') ? 'active bg-primary text-white' : ''; ?>" id="schedH-tab" data-bs-toggle="tab" data-bs-target="#schedH-tab-pane" type="button" role="tab" aria-controls="schedH-tab-pane" aria-selected="<?= ($maintenance === 'citizenship') ? 'true' : 'false'; ?>" href="?maintenance=citizenship">Citizenship</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'occupation') ? 'active bg-primary text-white' : ''; ?>" id="schedI-tab" data-bs-toggle="tab" data-bs-target="#schedI-tab-pane" type="button" role="tab" aria-controls="schedI-tab-pane" aria-selected="<?= ($maintenance === 'occupation') ? 'true' : 'false'; ?>" href="?maintenance=occupation">Occupation</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'educlevel') ? 'active bg-primary text-white' : ''; ?>" id="schedJ-tab" data-bs-toggle="tab" data-bs-target="#schedJ-tab-pane" type="button" role="tab" aria-controls="schedJ-tab-pane" aria-selected="<?= ($maintenance === 'educlevel') ? 'true' : 'false'; ?>" href="?maintenance=educlevel">Educ Level</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'gender') ? 'active bg-primary text-white' : ''; ?>" id="schedK-tab" data-bs-toggle="tab" data-bs-target="#schedK-tab-pane" type="button" role="tab" aria-controls="schedK-tab-pane" aria-selected="<?= ($maintenance === 'gender') ? 'true' : 'false'; ?>" href="?maintenance=gender">Gender</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'cstatus') ? 'active bg-primary text-white' : ''; ?>" id="schedL-tab" data-bs-toggle="tab" data-bs-target="#schedL-tab-pane" type="button" role="tab" aria-controls="schedL-tab-pane" aria-selected="<?= ($maintenance === 'cstatus') ? 'true' : 'false'; ?>" href="?maintenance=cstatus">Civil Status</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'relation') ? 'active bg-primary text-white' : ''; ?>" id="schedM-tab" data-bs-toggle="tab" data-bs-target="#schedM-tab-pane" type="button" role="tab" aria-controls="schedM-tab-pane" aria-selected="<?= ($maintenance === 'relation') ? 'true' : 'false'; ?>" href="?maintenance=relation">Relation to Client</button>
                                </li>

                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'beneficiary') ? 'active bg-primary text-white' : ''; ?>" id="schedN-tab" data-bs-toggle="tab" data-bs-target="#schedN-tab-pane" type="button" role="tab" aria-controls="schedN-tab-pane" aria-selected="<?= ($maintenance === 'beneficiary') ? 'true' : 'false'; ?>" href="?maintenance=beneficiary">Beneficiary Type</button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'country') ? 'active bg-primary text-white' : ''; ?>" id="schedO-tab" data-bs-toggle="tab" data-bs-target="#schedO-tab-pane" type="button" role="tab" aria-controls="schedO-tab-pane" aria-selected="<?= ($maintenance === 'country') ? 'true' : 'false'; ?>" href="?maintenance=country">Country </button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'repayment') ? 'active bg-primary text-white' : ''; ?>" id="schedP-tab" data-bs-toggle="tab" data-bs-target="#schedP-tab-pane" type="button" role="tab" aria-controls="schedP-tab-pane" aria-selected="<?= ($maintenance === 'repayment') ? 'true' : 'false'; ?>" href="?maintenance=repayment">Repayment Type </button>
                                </li>
                                <li class="nav-item" role="presentation7">
                                    <button class="nav-link maintenance-nav <?= ($maintenance === 'interest_computation') ? 'active bg-primary text-white' : ''; ?>" id="schedQ-tab" data-bs-toggle="tab" data-bs-target="#schedQ-tab-pane" type="button" role="tab" aria-controls="schedQ-tab-pane" aria-selected="<?= ($maintenance === 'interest_computation') ? 'true' : 'false'; ?>" href="?maintenance=interest_computation"> Interest Computation </button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade <?= ($maintenance === 'loansetup' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedA-tab-pane" role="tabpanel" aria-labelledby="schedA-tab" tabindex="0">
                                    <?php
                                    include('config-loan-setup.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'loan_type' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedB-tab-pane" role="tabpanel" aria-labelledby="schedB-tab" tabindex="0">
                                    <?php
                                    include('config-loantype.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'mode' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedC-tab-pane" role="tabpanel" aria-labelledby="schedC-tab" tabindex="0">
                                    <?php
                                    include('config-mode.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'insurance' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedD-tab-pane" role="tabpanel" aria-labelledby="schedD-tab" tabindex="0">
                                    <?php
                                    include('config-insurance.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'sector' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedE-tab-pane" role="tabpanel" aria-labelledby="schedE-tab" tabindex="0">
                                    <?php
                                    include('config-sector.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'religion' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedF-tab-pane" role="tabpanel" aria-labelledby="schedF-tab" tabindex="0">
                                    <?php
                                    include('config-religion.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'location' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedG-tab-pane" role="tabpanel" aria-labelledby="schedG-tab" tabindex="0">
                                    <?php
                                    include('config-location.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'citizenship' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedH-tab-pane" role="tabpanel" aria-labelledby="schedH-tab" tabindex="0">
                                    <?php
                                    include('config-citizenship.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'occupation' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedI-tab-pane" role="tabpanel" aria-labelledby="schedI-tab" tabindex="0">
                                    <?php
                                    include('config-occupation.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'educlevel' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedJ-tab-pane" role="tabpanel" aria-labelledby="schedJ-tab" tabindex="0">
                                    <?php
                                    include('config-educlevel.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'gender' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedK-tab-pane" role="tabpanel" aria-labelledby="schedK-tab" tabindex="0">
                                    <?php
                                    include('config-gender.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'cstatus' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedL-tab-pane" role="tabpanel" aria-labelledby="schedL-tab" tabindex="0">
                                    <?php
                                    include('config-cstatus.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'relation' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedM-tab-pane" role="tabpanel" aria-labelledby="schedM-tab" tabindex="0">
                                    <?php
                                    include('config-relation.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'beneficiary' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedN-tab-pane" role="tabpanel" aria-labelledby="schedN-tab" tabindex="0">
                                    <?php
                                    include('config-beneficiary.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'country' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedO-tab-pane" role="tabpanel" aria-labelledby="schedO-tab" tabindex="0">
                                    <?php
                                    include('config-country.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'repayment' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedP-tab-pane" role="tabpanel" aria-labelledby="schedP-tab" tabindex="0">
                                    <?php
                                    include('config-repayment.php')
                                    ?>
                                </div>
                                <div class="tab-pane fade <?= ($maintenance === 'interest_computation' || empty($_GET['maintenance'])) ? 'show active' : ''; ?>" id="schedQ-tab-pane" role="tabpanel" aria-labelledby="schedQ-tab" tabindex="0">
                                    <?php
                                    include('config-interestComp.php')
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <!-- <script src="../../js/inventorymanagement/inventory.js?<?= time() ?>"></script> -->

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>

<script>
    $(document).ready(function() {
        function switchTab(tabId, maintenanceParam) {
            window.history.pushState(null, null, `?maintenance=${maintenanceParam}`);

            $('.maintenance-nav').removeClass('active bg-primary text-white');

            $(tabId).addClass('active bg-primary text-white');

            $('.tab-pane').removeClass('show active');
            $(`${tabId}-pane`).addClass('show active');
        }

        $('.maintenance-nav').click(function() {
            const maintenanceParam = $(this).attr('href').split('=')[1];

            switchTab(`#${$(this).attr('id')}`, maintenanceParam);
        });
    });
</script>