<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
<?php
    // Override BASE_PATH for router compatibility
    $BASE_PATH = '/iSynApp-main';
    $headerPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.header.php';
    include($headerPath);
?>
<link rel="stylesheet" href="/iSynApp-main/assets/datetimepicker/jquery.datetimepicker.css">
<link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">

<body class="  ">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --info-color: #0ea5e9;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-light: #f8fafc;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%) !important; 
            min-height: 100vh; 
        }
        .main-content {
            background: transparent !important;
        }
            label { color: var(--text-primary); font-weight: 500; font-size: 0.875rem; }
            .form-control, .form-select {
                border: 1px solid var(--border-color); border-radius: 6px;
                padding: 0.5rem 0.75rem; font-size: 0.875rem; transition: all 0.2s ease;
            }
            .form-control:focus, .form-select:focus {
                border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); outline: none;
            }
            .form-control-plaintext {
                font-size: 0.875rem; color: var(--text-primary); padding: 0.375rem 0;
            }
            .card-container {
                background: white; border-radius: 12px; box-shadow: var(--shadow-md);
                padding: 1.25rem; margin-bottom: 1.25rem; border: 1px solid var(--border-color);
                transition: transform 0.2s ease;
            }
            .card-container:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
            .header-section {
                background: white; padding: 1.25rem; border-radius: 12px;
                box-shadow: var(--shadow-sm); margin-bottom: 1.25rem; border-left: 4px solid var(--primary-color);
            }
            .header-section h5 { color: var(--text-primary); font-weight: 600; margin: 0; }
            .section-title {
                color: var(--text-primary); font-size: 1rem; font-weight: 600;
                margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--border-color);
            }
            .btn { border-radius: 6px; padding: 0.5rem 1rem; font-weight: 500; font-size: 0.875rem; transition: all 0.2s ease; border: none; }
            .btn-primary { background: var(--primary-color); color: white; }
            .btn-primary:hover:not(:disabled) { background: #1d4ed8; transform: translateY(-1px); }
            .btn-success { background: var(--success-color); color: white; }
            .btn-success:hover:not(:disabled) { background: #059669; transform: translateY(-1px); }
            .btn-info { background: var(--info-color); color: white; }
            .btn-info:hover:not(:disabled) { background: #0284c7; transform: translateY(-1px); }
            .btn-secondary { background: var(--secondary-color); color: white; }
            .btn-secondary:hover:not(:disabled) { background: #475569; transform: translateY(-1px); }
            .btn:disabled { opacity: 0.5; transform: none !important; cursor: not-allowed; pointer-events: none; }
            .table { font-size: 0.875rem; margin-bottom: 0; }
            .table thead th {
                background: var(--bg-light); color: var(--text-primary); font-weight: 600;
                border-bottom: 2px solid var(--border-color); padding: 0.75rem;
                font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;
            }
            .table tbody tr { transition: background-color 0.15s ease; cursor: pointer; }
            .table tbody tr:hover { background-color: #eff6ff; }
            .table tbody tr.table-active { background-color: #dbeafe !important; border-left: 3px solid var(--primary-color); }
            .table tbody td { padding: 0.625rem 0.75rem; color: var(--text-primary); vertical-align: middle; font-weight: 400; }
            hr { border: none; height: 1px; background: var(--border-color); margin: 0.75rem 0; }
            .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 0.25rem 0; border-bottom: 1px dashed var(--border-color); }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: 600; font-size: 0.8125rem; color: var(--text-secondary); }
            .detail-value { font-size: 0.8125rem; color: var(--text-primary); text-align: right; }
            @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
            .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>

    <div id="loading">
        <div class="loader simple-loader"><div class="loader-body"></div></div>
    </div>

    <?php
        $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
        $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
        include($sidebarPath);
        include($navbarPath);
    ?>

    <!-- Main Content Starts Here (inside main-content wrapper from navbar) -->
    <div class="container-fluid" style="padding: 2rem; background: white; min-height: 100vh;">
        <div class="header-section fade-in">
            <h5><i class="fa-solid fa-money-bill-transfer me-2"></i>Pending Releases</h5>
        </div>

            <!-- Application List -->
            <div class="card-container fade-in">
                <p class="section-title"><i class="fa-solid fa-list me-2"></i>Application List</p>
                <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                    <table class="table table-hover table-borderless" style="width:100%">
                        <thead>
                            <tr>
                                <th>Client Name</th>
                                <th>Client No.</th>
                                <th>Program</th>
                                <th>Product</th>
                                <th class="text-end">Loan Amount</th>
                                <th class="text-center">CV</th>
                                <th class="text-center">Check</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="checkVouchersTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Details Row -->
            <div class="row g-3">
                <!-- Primary Details -->
                <div class="col-md-3">
                    <div class="card-container fade-in h-100">
                        <p class="section-title">Primary Details</p>
                        <div class="detail-row"><span class="detail-label">Program</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="programPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Product</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="productPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Staff</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="staffPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Mode</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="modePenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Term</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="termPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Rate</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="ratePenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Int Computation</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="computationPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Tag</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="tagPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Pre Charges</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="chargesPenReleases"></span></div>

                        <p class="section-title mt-3">Total</p>
                        <div class="detail-row"><span class="detail-label">Loan Amount</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="loanAmountPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Interest</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="interestPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">MBA</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="mbaPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">CBU</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="cbuPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">EF</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="efPenReleases"></span></div>
                        <div class="detail-row"><span class="detail-label">Net Amount</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="netAmountPenReleases"></span></div>

                        <p class="section-title mt-3">Amortization</p>
                        <div class="detail-row"><span class="detail-label">Principal</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="principalAmort"></span></div>
                        <div class="detail-row"><span class="detail-label">Interest</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="interestAmort"></span></div>
                        <div class="detail-row"><span class="detail-label">MBA</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="mbaAmort"></span></div>
                        <div class="detail-row"><span class="detail-label">CBU</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="cbuAmort"></span></div>
                        <div class="detail-row"><span class="detail-label">EF</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="efAmort"></span></div>
                        <div class="detail-row"><span class="detail-label">Total Amount</span><span class="detail-value"><input type="text" readonly class="form-control-plaintext text-end p-0" id="totalAmort"></span></div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-9">
                    <div class="row g-3">
                        <!-- Funding Details -->
                        <div class="col-md-8">
                            <div class="card-container fade-in">
                                <p class="section-title"><i class="fa-solid fa-building-columns me-2"></i>Funding Details</p>

                                <input type="hidden" id="ClientId" name="ClientId">
                                <input type="hidden" id="IDNum" name="IDNum">

                                <div class="mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label">Release Type</label>
                                    <div class="col-sm-8">
                                        <select id="inputReleaseType" class="form-select">
                                            <option value="" disabled selected>SELECT RELEASE TYPE</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label">Bank Account</label>
                                    <div class="col-sm-8">
                                        <select id="inputBankAccount" class="form-select">
                                            <option value="" disabled selected>SELECT BANK ACCOUNT</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label">Fund/Tag</label>
                                    <div class="col-sm-8">
                                        <select id="inputFundTag" class="form-select">
                                            <option value="" disabled selected>SELECT FUND/TAG</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-2 row align-items-center">
                                    <label class="col-sm-4 col-form-label">Voucher / Check No.</label>
                                    <div class="col-sm-8">
                                        <div class="row g-2">
                                            <div class="col-5">
                                                <input id="inputVoucher" type="text" class="form-control" readonly placeholder="CV No.">
                                            </div>
                                            <div class="col-7">
                                                <input id="inputCheckNo" type="text" class="form-control" readonly placeholder="Check No.">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Release Document -->
                        <div class="col-md-4">
                            <div class="card-container fade-in">
                                <p class="section-title"><i class="fa-solid fa-file-invoice me-2"></i>Release Document</p>
                                <div class="d-grid gap-2">
                                    <button type="button" id="saveDetailsBtn" class="btn btn-success" onclick="SaveDetails()" title="Select a client first">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Details
                                    </button>
                                    <button type="button" id="voucherBtn" class="btn btn-primary" onclick="PrintVoucher()">
                                        <i class="fa-solid fa-print me-1"></i> Voucher
                                    </button>
                                    <button type="button" id="checkBtn" class="btn btn-primary" onclick="PrintCheck()">
                                        <i class="fa-solid fa-print me-1"></i> Check/Confirm
                                    </button>
                                    <button type="button" id="lrsBtn" class="btn btn-primary" onclick="PrintLRS()">
                                        <i class="fa-solid fa-print me-1"></i> LRS / Disclosure
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Voucher Details -->
                        <div class="col-md-12">
                            <div class="card-container fade-in">
                                <p class="section-title"><i class="fa-solid fa-receipt me-2"></i>Voucher Details</p>
                                <div class="mb-2">
                                    <input type="text" readonly class="form-control text-center fw-bold" id="particulars" placeholder="Particulars will appear here">
                                </div>
                                <div class="table-responsive" style="max-height: 180px; overflow-y: auto;">
                                    <table class="table table-bordered" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>Account</th>
                                                <th>Account No.</th>
                                                <th>SL</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody id="loanTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
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
        <script src="/iSynApp-main/js/accountsmonitoring/pendingreleases.js?v=<?= time() ?>"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "/iSynApp-main/login.php"; </script>';
  }
?>