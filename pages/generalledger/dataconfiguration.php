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

            <div class="container mt-4">
                <div class="p-3 shadow rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Data Configuration</p>
                </div>

                <div class="shadow rounded-2 mb-3" style="background-color: white;">
                    <div class="mt-3">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="beginning-balance-tab" data-bs-toggle="tab" data-bs-target="#beginning-balance-tab-pane" type="button" role="tab" aria-controls="beginning-balance-tab-pane" aria-selected="true">Beginning Balance Data</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sl-balance-tab" data-bs-toggle="tab" data-bs-target="#sl-balance-tab-pane" type="button" role="tab" aria-controls="sl-balance-tab-pane" aria-selected="false">SL Balance</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="year-end-balance-data-tab" data-bs-toggle="tab" data-bs-target="#year-end-balance-data-tab-pane" type="button" role="tab" aria-controls="year-end-balance-data-tab-pane" aria-selected="false">Year End Balance Data</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="budget-variance-data-tab" data-bs-toggle="tab" data-bs-target="#budget-variance-data-tab-pane" type="button" role="tab" aria-controls="budget-variance-data-tab-pane" aria-selected="false">Budget Variance Data</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="peso-data-tab" data-bs-toggle="tab" data-bs-target="#peso-data-tab-pane" type="button" role="tab" aria-controls="peso-data-tab-pane" aria-selected="false">PESO Data</button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content" id="myTabContent">
                        <!-- BEGINNING BALANCE DATA TAB -->
                        <div class="tab-pane fade show active" id="beginning-balance-tab-pane" role="tabpanel" aria-labelledby="beginning-balance-tab" tabindex="0">
                            <div class="row">
                                <form action="" class="needs-validation" novalidate>
                                    <div class="p-3">
                                        <div class="mb-2">
                                            <label for="bbFundSelect" class="form-label mt-2 mx-2">Funding:</label>
                                            <select class="form-select" id="bbFundSelect" aria-label="Fund select" required>
                                                <option value="" selected>Select Fund</option>
                                            </select>
                                            <div class="invalid-feedback">Please choose funding</div>
                                        </div>
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-borderless table-hover bg-white" id="bbTable">
                                                <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                                    <tr>
                                                        <th>Account No.</th>
                                                        <th>Account Title</th>
                                                        <th>Category</th>
                                                        <th class="text-end">Beginning Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="bbTableBody">
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Please select a fund</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="p-3">
                                            <p class="fw-medium fs-5 my-2 mb-3">Edit Amount</p>
                                            <hr style="height: 1px">
                                            <div class="form-group row mb-2">
                                                <label for="bbAccountNo" class="col-sm-2 col-form-label">Account No.</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" id="bbAccountNo" required>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-2">
                                                <label for="bbAccountTitle" class="col-sm-2 col-form-label">Account Title</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" id="bbAccountTitle" required>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-2">
                                                <label for="bbBeginningBalance" class="col-sm-2 col-form-label">Beginning Balance</label>
                                                <div class="col-sm-5">
                                                    <input type="number" step="0.01" class="form-control" id="bbBeginningBalance" required>
                                                </div>
                                                <div class="col-md-5 text-end justify-content-end">
                                                    <button class="btn btn-secondary px-3 py-2 me-2" type="button" id="btnCancelBB"><i class="fa-solid fa-xmark"></i> Cancel</button>
                                                    <button class="btn btn-primary px-3 py-2" type="button" id="btnEditBB"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- SL BALANCE DATA TAB -->
                        <div class="tab-pane fade" id="sl-balance-tab-pane" role="tabpanel" aria-labelledby="sl-balance-tab" tabindex="0">
                            <div class="row">
                                <form action="" novalidate class="needs-validation">
                                    <div class="p-3">
                                        <div class="col-md-6 mb-2">
                                            <label for="slFundSelect" class="form-label mt-2 mx-2">Funding:</label>
                                            <select class="form-select" id="slFundSelect" aria-label="Fund select" required>
                                                <option value="" selected>Select Fund</option>
                                            </select>
                                            <div class="invalid-feedback">Please choose funding</div>
                                        </div>

                                        <div class="col-md-6 mb-2">
                                            <label for="slAccountCode" class="form-label mt-2 mx-2">Account Code</label>
                                            <select class="form-select" id="slAccountCode" aria-label="Account code select" required>
                                                <option value="" selected>Select Account Code</option>
                                            </select>
                                            <div class="invalid-feedback">Please choose account code</div>
                                        </div>

                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-borderless table-hover bg-white" id="slTable">
                                                <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                                    <tr>
                                                        <th>SL No.</th>
                                                        <th>SL Name</th>
                                                        <th class="text-end">Beginning Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="slTableBody">
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted">Please select fund and account code</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    
                                        
                                        <div class="col-md-12">
                                            <p class="fw-medium fs-5 my-2 mb-3">Add/Edit SL Balance</p>
                                            <hr style="height: 1px">
                                            <div class="form-group row mb-2">
                                                <label for="slNo" class="col-sm-2 col-form-label">SL No.</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" id="slNo" required>
                                                </div>
                                            </div>

                                            <div class="form-group row mb-2">
                                                <label for="slName" class="col-sm-2 col-form-label">SL Name</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" id="slName" required>
                                                </div>
                                            </div>

                                            <div class="form-group row mb-2">
                                                <label for="slBalance" class="col-sm-2 col-form-label">Beginning Balance</label>
                                                <div class="col-sm-5">
                                                    <input type="number" step="0.01" class="form-control" id="slBalance" required>
                                                </div>
                                                <div class="col-md-5 text-end justify-content-end">
                                                    <button class="btn btn-secondary px-3 py-2 me-2" type="button" id="btnCancelSL"><i class="fa-solid fa-xmark"></i> Cancel</button>
                                                    <button class="btn btn-primary px-3 py-2" type="button" id="btnSaveSL"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- YEAR END BALANCE DATA TAB -->
                        <div class="tab-pane fade" id="year-end-balance-data-tab-pane" role="tabpanel" aria-labelledby="year-end-balance-data-tab" tabindex="0">
                            <div class="row">
                                <form action="" novalidate class="needs-validation">
                                    <div class="p-3">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label for="yeMonth" class="form-label mt-2 mx-2">Year-End Date:</label>
                                                <input type="date" class="form-control" id="yeMonth" required>
                                                <div class="invalid-feedback">Please select year-end date</div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <label for="yeFundSelect" class="form-label mt-2 mx-2">Funding:</label>
                                                <select class="form-select" id="yeFundSelect" required>
                                                    <option value="" selected>Select Fund</option>
                                                </select>
                                                <div class="invalid-feedback">Please select funding</div>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <button class="btn btn-primary" type="button" id="btnLoadYE">
                                                    <i class="fa-solid fa-calculator"></i> Calculate Year-End Balances
                                                </button>
                                                <button class="btn btn-success" type="button" id="btnLockYE">
                                                    <i class="fa-solid fa-lock"></i> Lock Year-End
                                                </button>
                                                <button class="btn btn-warning" type="button" id="btnUnlockYE">
                                                    <i class="fa-solid fa-unlock"></i> Unlock Year-End
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-borderless table-hover bg-white" id="yeTable">
                                                <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                                    <tr>
                                                        <th>Account No.</th>
                                                        <th>Account Title</th>
                                                        <th>Category</th>
                                                        <th class="text-end">Beginning Balance</th>
                                                        <th class="text-end">Total Debits</th>
                                                        <th class="text-end">Total Credits</th>
                                                        <th class="text-end">Year-End Balance</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="yeTableBody">
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted">Please select date and fund, then click Calculate</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="p-3">
                                            <p class="fw-medium fs-5 my-2 mb-3">Edit Year-End Balance</p>
                                            <hr style="height: 1px">
                                            <div class="form-group row mb-2">
                                                <label for="yeAccountNo" class="col-sm-2 col-form-label">Account No.</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" id="yeAccountNo" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-2">
                                                <label for="yeAccountTitle" class="col-sm-2 col-form-label">Account Title</label>
                                                <div class="col-sm-5">
                                                    <input type="text" class="form-control" id="yeAccountTitle" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-2">
                                                <label for="yeBeginningBalance" class="col-sm-2 col-form-label">Year-End Balance</label>
                                                <div class="col-sm-5">
                                                    <input type="number" step="0.01" class="form-control" id="yeBeginningBalance" required>
                                                </div>
                                                <div class="col-md-5 text-end justify-content-end">
                                                    <button class="btn btn-secondary px-3 py-2 me-2" type="button" id="btnCancelYE">
                                                        <i class="fa-solid fa-xmark"></i> Cancel
                                                    </button>
                                                    <button class="btn btn-primary px-3 py-2" type="button" id="btnSaveYE">
                                                        <i class="fa-solid fa-floppy-disk"></i> Save
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <!-- BUDGET VARIANCE DATA TAB -->
                    <div class="tab-pane fade" id="budget-variance-data-tab-pane" role="tabpanel" aria-labelledby="budget-variance-data-tab" tabindex="0">
                        <div class="row">
                            <form action="" novalidate class="needs-validation">
                                <div class="p-3">
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label for="budgetMonth" class="form-label mt-2 mx-2">For the Month:</label>
                                            <input type="month" class="form-control" id="budgetMonth" required>
                                            <div class="invalid-feedback">Please select month</div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <label for="budgetFundSelect" class="form-label mt-2 mx-2">Funding:</label>
                                            <select class="form-select" id="budgetFundSelect" required>
                                                <option value="" selected>Select Fund</option>
                                            </select>
                                            <div class="invalid-feedback">Please select funding</div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <button class="btn btn-primary" type="button" id="btnLoadBudget">
                                                <i class="fa-solid fa-calculator"></i> Load Budget Variance
                                            </button>
                                            <button class="btn btn-info" type="button" id="btnCopyBudget">
                                                <i class="fa-solid fa-copy"></i> Copy Budget from Another Month
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-borderless table-hover bg-white" id="budgetTable">
                                            <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                                <tr>
                                                    <th>Account No.</th>
                                                    <th>Account Title</th>
                                                    <th>Category</th>
                                                    <th class="text-end">Budget</th>
                                                    <th class="text-end">Actual</th>
                                                    <th class="text-end">Variance</th>
                                                    <th class="text-end">Variance %</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="budgetTableBody">
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">Please select month and fund, then click Load</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="p-3">
                                        <p class="fw-medium fs-5 my-2 mb-3">Edit Budget Amount</p>
                                        <hr style="height: 1px">
                                        <div class="form-group row mb-2">
                                            <label for="budgetAccountNo" class="col-sm-2 col-form-label">Account No.</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" id="budgetAccountNo" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-2">
                                            <label for="budgetAccountTitle" class="col-sm-2 col-form-label">Account Title</label>
                                            <div class="col-sm-5">
                                                <input type="text" class="form-control" id="budgetAccountTitle" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group row mb-2">
                                            <label for="budgetAmount" class="col-sm-2 col-form-label">Budget Amount</label>
                                            <div class="col-sm-5">
                                                <input type="number" step="0.01" class="form-control" id="budgetAmount" required>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-7"></div>
                                            <div class="col-md-5 text-end justify-content-end">
                                                <button class="btn btn-secondary px-3 py-2 me-2" type="button" id="btnCancelBudget">
                                                    <i class="fa-solid fa-xmark"></i> Cancel
                                                </button>
                                                <button class="btn btn-primary px-3 py-2" type="button" id="btnSaveBudget">
                                                    <i class="fa-solid fa-floppy-disk"></i> Save
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- PESO DATA TAB -->
                    <div class="tab-pane fade" id="peso-data-tab-pane" role="tabpanel" aria-labelledby="peso-data-tab" tabindex="0">
                        <div class="row">
                            <form action="" novalidate class="needs-validation">
                                <div class="p-3">
                                    <h5 class="mb-3">PESO Data Configuration</h5>
                                    <p class="text-muted mb-3">Configure key performance indicators and economic metrics</p>
                                    
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-borderless table-hover bg-white" id="pesoTable">
                                            <thead style="position: sticky; top: 0; background-color: white; z-index: 10;">
                                                <tr>
                                                    <th style="width: 60%;">Item</th>
                                                    <th style="width: 30%;">Value</th>
                                                    <th style="width: 10%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="pesoTableBody">
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <hr class="my-3">
                                    
                                    <h6 class="mb-3">Add New Item</h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-4">
                                            <label for="pesoItem" class="form-label">Item Name</label>
                                            <input type="text" class="form-control" id="pesoItem" placeholder="e.g., Exchange Rate">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="pesoAmount" class="form-label">Value</label>
                                            <input type="number" step="0.01" class="form-control" id="pesoAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button class="btn btn-primary w-100" type="button" id="btnSavePESO">
                                                <i class="fa-solid fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            $footerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
            include($footerPath);
        ?>

        <script src="/iSynApp-main/assets/select2/js/select2.full.min.js"></script>
        <script src="/iSynApp-main/js/generalledger/dataconfiguration.js?<?= time() ?>"></script>

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

<script>
    const triggerTabList = document.querySelectorAll('#myTab button')
    triggerTabList.forEach(triggerEl => {
        const tabTrigger = new bootstrap.Tab(triggerEl)

        triggerEl.addEventListener('click', event => {
            event.preventDefault()
            tabTrigger.show()
        })
    })
</script>