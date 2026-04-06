
<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        $permissionsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/permissions.php';
        require_once($permissionsPath);
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: /iSynApp-main/dashboard");
            exit;
        }
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php
        $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
        include($headerPath);
    ?>
    <title>iSyn | Accounts and Aging</title>
    <body class="  ">
        <div id="loading"><div class="loader simple-loader"><div class="loader-body"></div></div></div>
        <?php
            $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
            $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
            include($sidebarPath);
            include($navbarPath);
        ?>

        <style>
            :root {
                --primary-color: #2563eb;
                --border-color: #e2e8f0;
                --text-primary: #1e293b;
                --text-secondary: #64748b;
                --bg-light: #f8fafc;
                --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
                --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            }
            body { background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%); min-height: 100vh; }
            label { color: var(--text-primary); font-weight: 500; font-size: 0.8rem; }
            .form-control, .form-select {
                border: 1px solid var(--border-color); border-radius: 6px;
                padding: 0.375rem 0.625rem; font-size: 0.8rem; transition: all 0.2s;
            }
            .form-control:focus, .form-select:focus {
                border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none;
            }
            .card-container {
                background: white; border-radius: 12px; box-shadow: var(--shadow-md);
                padding: 1rem; margin-bottom: 1rem; border: 1px solid var(--border-color);
            }
            .header-section {
                background: white; padding: 1rem 1.25rem; border-radius: 12px;
                box-shadow: var(--shadow-sm); margin-bottom: 1rem; border-left: 4px solid var(--primary-color);
            }
            .header-section h5 { color: var(--text-primary); font-weight: 600; margin: 0; }
            .section-title {
                color: var(--text-primary); font-size: 0.875rem; font-weight: 600;
                margin-bottom: 0.5rem; padding-bottom: 0.375rem; border-bottom: 2px solid var(--border-color);
            }
            .btn { border-radius: 6px; padding: 0.4rem 0.875rem; font-weight: 500; font-size: 0.8rem; transition: all 0.2s; border: none; }
            .btn-primary { background: var(--primary-color); color: white; }
            .btn-primary:hover:not(:disabled) { background: #1d4ed8; transform: translateY(-1px); }
            .btn-success { background: #10b981; color: white; }
            .btn-success:hover:not(:disabled) { background: #059669; transform: translateY(-1px); }
            .btn-info { background: #0ea5e9; color: white; }
            .btn-info:hover:not(:disabled) { background: #0284c7; transform: translateY(-1px); }
            .btn-danger { background: #ef4444; color: white; }
            .btn-danger:hover:not(:disabled) { background: #dc2626; transform: translateY(-1px); }
            .btn-secondary { background: #64748b; color: white; }
            .btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; }
            .table { font-size: 0.8rem; margin-bottom: 0; }
            .table thead th {
                background: var(--bg-light); color: var(--text-primary); font-weight: 600;
                border-bottom: 2px solid var(--border-color); padding: 0.6rem 0.75rem;
                font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; position: sticky; top: 0; z-index: 1;
            }
            .table tbody tr { transition: background 0.15s; cursor: pointer; }
            .table tbody tr:hover { background: #eff6ff; }
            .table tbody tr.table-active { background: #dbeafe !important; border-left: 3px solid var(--primary-color); }
            .table tbody td { padding: 0.5rem 0.75rem; color: var(--text-primary); vertical-align: middle; }
            .nav-tabs .nav-link { color: var(--text-primary); font-weight: 500; font-size: 0.875rem; border-radius: 6px 6px 0 0; }
            .nav-tabs .nav-link.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
            .tab-content { background: white; border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 12px 12px; padding: 1rem; }
            .field-row { display: flex; align-items: center; margin-bottom: 0.375rem; gap: 0.5rem; }
            .field-row label { min-width: 110px; font-size: 0.775rem; color: var(--text-secondary); flex-shrink: 0; }
            .field-row .form-control { flex: 1; font-size: 0.8rem; }
            hr { border: none; height: 1px; background: var(--border-color); margin: 0.5rem 0; }
        </style>

        <div class="container-fluid mt-1">
            <div class="header-section">
                <h5><i class="fa-solid fa-chart-line me-2"></i>Accounts and Aging Information</h5>
            </div>

            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="aging" data-bs-toggle="tab" data-bs-target="#aging-pane" type="button" role="tab">Aging</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="subsidiary-ledgers" data-bs-toggle="tab" data-bs-target="#subsidiary-ledgers-pane" type="button" role="tab">Subsidiary Ledgers</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">

                <!-- ── AGING TAB ─────────────────────────────────────────── -->
                <div class="tab-pane fade show active" id="aging-pane" role="tabpanel" tabindex="0">

                    <!-- Toolbar -->
                    <div class="card-container">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="mb-0 text-nowrap">View by</label>
                                    <select class="form-select" id="selectionOrder" style="max-width:160px;">
                                        <option value="name" selected>Name</option>
                                        <option value="clientNo">Client No.</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <input type="search" class="form-control" id="searchInput" placeholder="Search accounts...">
                            </div>
                            <div class="col-md-4 d-flex gap-2 justify-content-end">
                                <button id="updateBtn" class="btn btn-primary"><i class="fa-solid fa-refresh me-1"></i>Update</button>
                                <button class="btn btn-info" id="printStatement"><i class="fa-solid fa-print me-1"></i>Print SOA</button>
                            </div>
                        </div>
                    </div>

                    <!-- Accounts Table -->
                    <div class="card-container">
                        <p class="section-title"><i class="fa-solid fa-list me-1"></i>Accounts</p>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" disabled class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal" id="editAccount"><i class="fa-solid fa-pen-to-square me-1"></i>Edit</button>
                            <button disabled class="btn btn-danger" id="removeAccount" onclick="clearAccountDetails()"><i class="fa-regular fa-circle-xmark me-1"></i>Remove</button>
                            <button disabled class="btn btn-danger" id="deleteAccount"><i class="fa-solid fa-trash-can me-1"></i>Delete Cleared</button>
                        </div>
                        <div style="height:240px; overflow:auto;">
                            <table class="table table-hover table-borderless" id="myTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Client No.</th>
                                        <th>Loan ID</th>
                                        <th>Date Released</th>
                                        <th class="text-end">Loan Amount</th>
                                        <th>Product</th>
                                        <th>Addtl</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Details Row -->
                    <div class="row g-2">
                        <!-- Account Details -->
                        <div class="col-md-4">
                            <div class="card-container h-100">
                                <p class="section-title">Account Details</p>
                                <p class="section-title" style="font-size:0.75rem;margin-top:0.5rem;">Primary Details</p>
                                <div class="field-row"><label>Loan Program</label><input readonly type="text" class="form-control" id="program"></div>
                                <div class="field-row"><label>Loan Product</label><input readonly type="text" class="form-control" id="product"></div>
                                <div class="field-row"><label>Availment</label><input readonly type="text" class="form-control" id="availment"></div>
                                <div class="field-row"><label>Release Date</label><input readonly type="date" class="form-control" id="date-release"></div>
                                <div class="field-row"><label>Maturity Date</label><input readonly type="date" class="form-control" id="date-mature"></div>
                                <div class="field-row"><label>Mode</label><input readonly type="text" class="form-control" id="mode"></div>
                                <div class="field-row"><label>Term</label><input readonly type="text" class="form-control" id="term"></div>
                                <div class="field-row"><label>PO</label><input readonly type="text" class="form-control" id="PO"></div>
                                <div class="field-row"><label>Fund</label><input readonly type="text" class="form-control" id="fund"></div>
                                <div class="field-row"><label>PN No.</label><input readonly type="text" class="form-control" id="PNNo"></div>
                                <div class="field-row"><label>Tag</label><input readonly type="text" class="form-control" id="tag"></div>
                                <hr>
                                <p class="section-title" style="font-size:0.75rem;">Amounts</p>
                                <div class="field-row"><label>Loan Amount</label><input readonly type="text" class="form-control text-end" id="loan-amount"></div>
                                <div class="field-row"><label>Interest</label><input readonly type="text" class="form-control text-end" id="interest-amount"></div>
                                <div class="field-row"><label>CBU</label><input readonly type="text" class="form-control text-end" id="cbu"></div>
                                <div class="field-row"><label>EF</label><input readonly type="text" class="form-control text-end" id="ef"></div>
                                <div class="field-row"><label>MBA</label><input readonly type="text" class="form-control text-end" id="mba"></div>
                            </div>
                        </div>

                        <!-- Account Status -->
                        <div class="col-md-4">
                            <div class="card-container h-100">
                                <p class="section-title">Account Status</p>
                                <p class="section-title" style="font-size:0.75rem;margin-top:0.5rem;">Payments Made</p>
                                <div class="field-row"><label>Principal Paid</label><input readonly type="text" class="form-control text-end" id="principal-paid"></div>
                                <div class="field-row"><label>Interest Paid</label><input readonly type="text" class="form-control text-end" id="interest-paid"></div>
                                <div class="field-row"><label>CBU Paid</label><input readonly type="text" class="form-control text-end" id="cbu-paid"></div>
                                <div class="field-row"><label>EF Paid</label><input readonly type="text" class="form-control text-end" id="ef-paid"></div>
                                <div class="field-row"><label>MBA Paid</label><input readonly type="text" class="form-control text-end" id="mba-paid"></div>
                                <div class="field-row"><label>Penalties Paid</label><input readonly type="text" class="form-control text-end" id="penalties-paid"></div>
                                <hr>
                                <p class="section-title" style="font-size:0.75rem;">Status</p>
                                <div class="field-row"><label>Principal Balance</label><input readonly type="text" class="form-control text-end" id="principal-balance"></div>
                                <hr>
                                <p class="section-title" style="font-size:0.75rem;">Date Modified</p>
                                <div class="field-row"><label>Date Restructured</label><input readonly type="text" class="form-control" id="date-restructured"></div>
                                <div class="field-row"><label>Date Written-off</label><input readonly type="text" class="form-control" id="date-writtenOff"></div>
                                <div class="field-row"><label>Date Dropped</label><input readonly type="text" class="form-control" id="date-dropped"></div>
                            </div>
                        </div>

                        <!-- Dues + Arrears -->
                        <div class="col-md-4">
                            <div class="card-container">
                                <p class="section-title">Dues</p>
                                <div class="field-row"><label>Due Date</label><input readonly type="text" class="form-control" id="due-date"></div>
                                <div class="field-row"><label>Principal</label><input readonly type="text" class="form-control text-end" id="principal"></div>
                                <div class="field-row"><label>Interest</label><input readonly type="text" class="form-control text-end" id="interest-due"></div>
                                <div class="field-row"><label>CBU</label><input readonly type="text" class="form-control text-end" id="cbuDue"></div>
                                <div class="field-row"><label>EF</label><input readonly type="text" class="form-control text-end" id="efDue"></div>
                                <div class="field-row"><label>MBA</label><input readonly type="text" class="form-control text-end" id="mbaDue"></div>
                                <div class="field-row"><label>Penalty</label><input readonly type="text" class="form-control text-end" id="penalty-due"></div>
                            </div>
                            <div class="card-container">
                                <p class="section-title">Arrears and PAR</p>
                                <div class="field-row"><label>1-30 Days</label><input readonly type="text" class="form-control text-end" id="one-thirty"></div>
                                <div class="field-row"><label>31-60 Days</label><input readonly type="text" class="form-control text-end" id="thirty-one-to-sixty"></div>
                                <div class="field-row"><label>61-90 Days</label><input readonly type="text" class="form-control text-end" id="sixtyone-to-ninety"></div>
                                <div class="field-row"><label>91-120 Days</label><input readonly type="text" class="form-control text-end" id="ninetyone-onetwenty"></div>
                                <div class="field-row"><label>121-150 Days</label><input readonly type="text" class="form-control text-end" id="onetwentyone-onefifty"></div>
                                <div class="field-row"><label>151-180 Days</label><input readonly type="text" class="form-control text-end" id="onefiftyone-oneeighty"></div>
                                <div class="field-row"><label>Over 180 Days</label><input readonly type="text" class="form-control text-end" id="over-oneeighty"></div>
                                <div class="field-row"><label>Total Arrears</label><input readonly type="text" class="form-control text-end" id="total-arrears"></div>
                                <div class="field-row"><label>PAR</label><input readonly type="text" class="form-control text-end" id="par"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── SUBSIDIARY LEDGERS TAB ────────────────────────────── -->
                <div class="tab-pane fade" id="subsidiary-ledgers-pane" role="tabpanel" tabindex="0">

                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-info" id="printSLBtn"><i class="fa-solid fa-print me-1"></i>Print</button>
                    </div>

                    <div class="row g-2 mb-2">
                        <!-- Loan List -->
                        <div class="col-md-8">
                            <div class="card-container">
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-3"><label class="mb-0">Select Client</label></div>
                                    <div class="col-md-9">
                                        <select id="selectName" class="form-select" onchange="filterTableByClient()">
                                            <option value="all" selected>Show All</option>
                                        </select>
                                    </div>
                                </div>
                                <div style="max-height:300px; overflow:auto;">
                                    <table id="loanTable" class="table table-hover table-borderless">
                                        <thead>
                                            <tr>
                                                <th>Loan ID</th>
                                                <th>Product</th>
                                                <th>Date Released</th>
                                                <th>Loan Type</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- SL Account Details -->
                        <div class="col-md-4">
                            <div class="card-container h-100">
                                <p class="section-title">Account Details</p>
                                <div class="field-row"><label>Client No.</label><input readonly type="text" class="form-control" id="clientDetails"></div>
                                <div class="field-row"><label>Program</label><input readonly type="text" class="form-control" id="programDetails"></div>
                                <div class="field-row"><label>Product</label><input readonly type="text" class="form-control" id="productDetails"></div>
                                <div class="field-row"><label>Date Released</label><input readonly type="text" class="form-control" id="dateReleaseDetails"></div>
                                <div class="field-row"><label>Date Mature</label><input readonly type="text" class="form-control" id="dateMatureDetails"></div>
                                <div class="field-row"><label>Loan Amount</label><input readonly type="text" class="form-control text-end" id="loanAmountDetails"></div>
                                <div class="field-row"><label>Interest</label><input readonly type="text" class="form-control text-end" id="interestDetails"></div>
                                <div class="field-row"><label>CBU</label><input readonly type="text" class="form-control text-end" id="cbuDetails"></div>
                                <div class="field-row"><label>PN No.</label><input readonly type="text" class="form-control" id="pnnoDetails"></div>
                                <div class="field-row"><label>PO</label><input readonly type="text" class="form-control" id="poDetails"></div>
                            </div>
                        </div>
                    </div>

                    <!-- SL Preview -->
                    <div class="card-container">
                        <p class="section-title"><i class="fa-solid fa-table me-1"></i>SL Preview</p>
                        <div style="max-height:300px; overflow:auto;">
                            <table class="table table-hover table-borderless">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Ref</th>
                                        <th>Account</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                        <th class="text-end">SL Dr/Cr</th>
                                        <th class="text-end">Balance</th>
                                        <th>Book</th>
                                        <th>Explanation</th>
                                    </tr>
                                </thead>
                                <tbody id="slPreviewBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div><!-- /container -->

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" style="background:#1e3a5f;color:white;">
                        <h5 class="modal-title" id="editModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editClientNo">
                        <input type="hidden" id="editLoanID">

                        <div class="row g-3">
                            <!-- Col 1 -->
                            <div class="col-md-4">
                                <p class="section-title">Loan Identification</p>
                                <div class="field-row"><label>Client No.</label><input type="text" readonly class="form-control" id="editClientNoDisplay"></div>
                                <div class="field-row"><label>Loan ID</label><input type="text" readonly class="form-control" id="editLoanIDDisplay"></div>
                                <div class="field-row"><label>Client Name</label><input type="text" class="form-control" id="editFullname"></div>
                                <div class="field-row"><label>PN No.</label><input type="text" class="form-control" id="editPNNO"></div>
                                <div class="field-row"><label>Tag</label><input type="text" class="form-control" id="editTag"></div>
                                <div class="field-row"><label>PO / Loan Officer</label><input type="text" class="form-control" id="editPO"></div>
                                <div class="field-row"><label>Fund</label><input type="text" class="form-control" id="editFund"></div>
                            </div>
                            <!-- Col 2 -->
                            <div class="col-md-4">
                                <p class="section-title">Loan Particulars</p>
                                <div class="field-row"><label>Program</label><input type="text" class="form-control" id="editProgram"></div>
                                <div class="field-row"><label>Product</label><input type="text" class="form-control" id="editProduct"></div>
                                <div class="field-row"><label>Mode</label><input type="text" class="form-control" id="editMode"></div>
                                <div class="field-row"><label>Term</label><input type="text" class="form-control" id="editTerm"></div>
                                <div class="field-row"><label>Interest Rate (%)</label><input type="text" class="form-control" id="editInterestRate"></div>
                                <div class="field-row"><label>Int. Computation</label><input type="text" class="form-control" id="editIntComputation"></div>
                                <div class="field-row"><label>Date Released</label><input type="date" class="form-control" id="editDateRelease"></div>
                                <div class="field-row"><label>Date Matured</label><input type="date" class="form-control" id="editDateMature"></div>
                            </div>
                            <!-- Col 3 -->
                            <div class="col-md-4">
                                <p class="section-title">Amounts</p>
                                <div class="field-row"><label>Loan Amount</label><input type="text" class="form-control text-end" id="editLoanAmount"></div>
                                <div class="field-row"><label>Interest</label><input type="text" class="form-control text-end" id="editInterest"></div>
                                <div class="field-row"><label>CBU</label><input type="text" class="form-control text-end" id="editCBU"></div>
                                <div class="field-row"><label>EF</label><input type="text" class="form-control text-end" id="editEF"></div>
                                <div class="field-row"><label>MBA</label><input type="text" class="form-control text-end" id="editMBA"></div>
                                <div class="field-row"><label>Net Amount</label><input type="text" class="form-control text-end" id="editNetAmount"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="SaveEditAccount()"><i class="fa-solid fa-floppy-disk me-1"></i>Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
            $footerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.footer.php';
            include($footerPath);
        ?>
        <script src="/iSynApp-main/js/accountsmonitoring/accountsandaging.js?<?= time() ?>"></script>
    </body>
</html>
<?php
    } else {
        echo '<script>window.location.href = "../../login.php";</script>';
    }
?>
