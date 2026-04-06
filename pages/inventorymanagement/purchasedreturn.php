<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php 
        include(dirname(__DIR__, 2) . '/includes/pages.header.php');
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $pagesPos = strpos($scriptDir, '/pages');
        if ($pagesPos !== false) {
            $base = substr($scriptDir, 0, $pagesPos);
        } else {
            $base = rtrim($scriptDir, '/\\');
        }
        if ($base === '.' ) { $base = ''; }
    ?>
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/select2/css/select2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <body>
        <!-- loader -->
        <div id="loading"><div class="loader simple-loader"><div class="loader-body"></div></div></div>

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
            include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
            include_once(dirname(__DIR__, 2) . "/database/connection.php");
            $db = new Database(); $conn = $db->conn;
            $sql = "SELECT MAX(CAST(SUBSTRING(TransactionNo, 4) AS UNSIGNED)) as max_num FROM tbl_purchasereturned WHERE TransactionNo LIKE 'PRN%'";
            $result = $conn->query($sql);
            $transactionNo = 'PRN000001';
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $nextNum = ($row['max_num'] ?? 0) + 1;
                $transactionNo = 'PRN' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
            }
        ?>

<style>
/* ─── Base ─────────────────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }
body { background: #f0f2f8; font-family: 'Inter', 'Segoe UI', sans-serif; color: #1e293b; }
main { background: #f0f2f8; }

/* ─── Layout wrapper ────────────────────────────────────────────────────── */
.pr-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px 20px 60px; }

/* ─── Page header ───────────────────────────────────────────────────────── */
.pr-page-header {
    display: flex; align-items: center; gap: 14px;
    background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
    border-radius: 16px; padding: 20px 28px; margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(29,78,216,.3);
    color: #fff;
}
.pr-page-header .icon-wrap {
    width: 48px; height: 48px; background: rgba(255,255,255,.2);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 22px;
}
.pr-page-header h1 { font-size: 1.4rem; font-weight: 700; margin: 0; }
.pr-page-header p  { font-size: .85rem; margin: 2px 0 0; opacity: .8; }

/* ─── Step indicator ────────────────────────────────────────────────────── */
.pr-steps {
    display: flex; align-items: center; gap: 0;
    background: #fff; border-radius: 14px; padding: 16px 24px;
    margin-bottom: 20px; box-shadow: 0 1px 6px rgba(0,0,0,.07);
}
.pr-step {
    display: flex; align-items: center; gap: 10px;
    flex: 1; padding: 0 12px;
}
.pr-step:first-child { padding-left: 0; }
.pr-step:last-child  { padding-right: 0; }
.pr-step-num {
    width: 34px; height: 34px; border-radius: 50%;
    background: #e2e8f0; color: #64748b;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .9rem; flex-shrink: 0;
    transition: all .3s;
}
.pr-step-info .label { font-weight: 600; font-size: .88rem; color: #64748b; transition: color .3s; }
.pr-step-info .sub   { font-size: .75rem; color: #94a3b8; }
.pr-step.active .pr-step-num { background: #1d4ed8; color: #fff; box-shadow: 0 2px 8px rgba(29,78,216,.4); }
.pr-step.active .pr-step-info .label { color: #1d4ed8; }
.pr-step.done   .pr-step-num { background: #10b981; color: #fff; }
.pr-step.done   .pr-step-info .label { color: #10b981; }
.pr-step-divider {
    height: 2px; flex: 1; background: #e2e8f0; border-radius: 2px;
    max-width: 60px; margin: 0 4px;
}
.pr-step.done + .pr-step-divider { background: #10b981; }

/* ─── Cards ─────────────────────────────────────────────────────────────── */
.pr-card {
    background: #fff; border-radius: 14px;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
    overflow: hidden;
}
.pr-card-header {
    padding: 16px 20px 14px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.pr-card-header .card-title {
    font-size: .95rem; font-weight: 700; color: #1e293b;
    display: flex; align-items: center; gap: 8px;
}
.pr-card-header .card-title i { color: #3b82f6; font-size: 1rem; }
.pr-card-body { padding: 20px; }

/* ─── Filter bar ────────────────────────────────────────────────────────── */
.pr-filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr auto;
    gap: 12px; align-items: end;
}
.pr-filter-group label {
    display: block; font-size: .78rem; font-weight: 600;
    color: #64748b; text-transform: uppercase; letter-spacing: .4px;
    margin-bottom: 6px;
}
.pr-filter-group select {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid #e2e8f0; border-radius: 9px;
    font-size: .88rem; color: #1e293b; background: #f8fafc;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%2364748b' d='M8 10.5L2 4.5h12z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center; background-size: 12px;
    transition: border-color .2s, box-shadow .2s;
    cursor: pointer;
}
.pr-filter-group select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }

#search-btn {
    padding: 9px 24px; border: none; border-radius: 9px;
    background: linear-gradient(135deg,#1d4ed8,#3b82f6);
    color: #fff; font-weight: 600; font-size: .88rem;
    display: flex; align-items: center; gap: 7px;
    cursor: pointer; transition: all .2s; white-space: nowrap;
    box-shadow: 0 2px 8px rgba(29,78,216,.3);
}
#search-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(29,78,216,.4); }
#search-btn:active { transform: none; }

/* ─── Split view (product list + detail panel) ──────────────────────────── */
.pr-split { display: flex; gap: 16px; }
.pr-split .product-list-col {
    flex: 1; min-width: 0;
    transition: flex .35s cubic-bezier(.4,0,.2,1);
}
.pr-split.panel-open .product-list-col { flex: 0 0 55%; }
.pr-detail-col {
    flex: 0 0 0; overflow: hidden;
    transition: flex .35s cubic-bezier(.4,0,.2,1), opacity .3s;
    opacity: 0;
}
.pr-split.panel-open .pr-detail-col {
    flex: 0 0 calc(45% - 16px);
    opacity: 1;
}

/* ─── Product Table ─────────────────────────────────────────────────────── */
.pr-table-wrap {
    height: 420px; overflow-y: auto; overflow-x: hidden; border-radius: 0 0 10px 10px;
}
.pr-table-wrap::-webkit-scrollbar { width: 5px; }
.pr-table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
table.pr-table { width: 100%; border-collapse: collapse; }
table.pr-table thead th {
    position: sticky; top: 0; z-index: 5;
    background: #1e293b; color: #fff;
    padding: 11px 14px; font-size: .78rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px;
}
table.pr-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer; transition: background .15s;
}
table.pr-table tbody tr:hover td { background: #f0f7ff; }
table.pr-table tbody tr.selected td { background: #1d4ed8 !important; color: #fff !important; }
table.pr-table tbody tr.selected td:first-child { box-shadow: inset 4px 0 0 0 #0a3d8f; }
table.pr-table tbody td { padding: 11px 14px; font-size: .875rem; }
table.pr-table .prod-name { font-weight: 600; color: #1e293b; }
table.pr-table tbody tr.selected .prod-name { color: #fff; }
table.pr-table .prod-meta { font-size: .75rem; color: #94a3b8; margin-top: 2px; }
table.pr-table tbody tr.selected .prod-meta { color: rgba(255,255,255,.75); }
.table-hint {
    text-align: center; padding: 12px; font-size: .8rem;
    color: #94a3b8; font-style: italic; border-top: 1px solid #f1f5f9;
}
.badge-qty {
    display: inline-flex; align-items: center;
    background: #f0f9ff; color: #0369a1;
    border: 1px solid #bae6fd; border-radius: 20px;
    padding: 2px 10px; font-size: .78rem; font-weight: 600;
}
table.pr-table tbody tr.selected .badge-qty {
    background: rgba(255,255,255,.2); color: #fff; border-color: rgba(255,255,255,.3);
}

/* ─── Detail Panel ──────────────────────────────────────────────────────── */
.pr-detail-panel {
    height: 100%; display: flex; flex-direction: column;
    min-width: 280px;
}
.detail-header {
    background: linear-gradient(135deg,#1e293b,#334155);
    color: #fff; padding: 16px 20px; border-radius: 14px 14px 0 0;
}
.detail-header .detail-badge {
    font-size: .7rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; background: rgba(59,130,246,.3);
    padding: 2px 10px; border-radius: 20px; margin-bottom: 8px; display: inline-block;
}
.detail-header h3 { font-size: 1rem; font-weight: 700; margin: 0; line-height: 1.4; }
.detail-body { flex: 1; padding: 16px 20px; overflow-y: auto; background: #fff; }
.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; margin-bottom: 16px; }
.detail-field label {
    font-size: .7rem; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .4px; display: block; margin-bottom: 3px;
}
.detail-field .val {
    font-size: .88rem; font-weight: 600; color: #1e293b;
    background: #f8fafc; border-radius: 7px; padding: 6px 10px; border: 1px solid #e2e8f0;
}
.detail-field.price .val { color: #0369a1; }
.detail-divider { height: 1px; background: #f1f5f9; margin: 14px 0; }
.detail-qty-label {
    font-size: .78rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .4px; margin-bottom: 6px;
}
.detail-qty-input {
    width: 100%; padding: 9px 12px; border: 2px solid #e2e8f0;
    border-radius: 9px; font-size: 1rem; font-weight: 700;
    color: #1e293b; text-align: center;
    transition: border-color .2s;
}
.detail-qty-input:focus { outline: none; border-color: #3b82f6; }
.btn-add-return {
    width: 100%; padding: 12px;
    background: linear-gradient(135deg,#10b981,#059669);
    color: #fff; border: none; border-radius: 10px;
    font-size: .95rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    cursor: pointer; transition: all .2s; margin-top: 12px;
    box-shadow: 0 2px 8px rgba(16,185,129,.3);
}
.btn-add-return:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(16,185,129,.4); }

/* ─── Return List ───────────────────────────────────────────────────────── */
.return-list-section { margin-top: 20px; }
.pr-tabs { display: flex; gap: 4px; border-bottom: 2px solid #f1f5f9; padding: 0 20px; }
.pr-tab-btn {
    padding: 12px 18px; border: none; background: none;
    font-size: .875rem; font-weight: 600; color: #94a3b8;
    cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px;
    transition: all .2s; display: flex; align-items: center; gap: 7px;
}
.pr-tab-btn:hover { color: #3b82f6; }
.pr-tab-btn.active { color: #1d4ed8; border-bottom-color: #1d4ed8; }
.tab-pane-pr { display: none; padding: 0; }
.tab-pane-pr.active { display: block; }
.badge-count {
    background: #1d4ed8; color: #fff;
    font-size: .7rem; font-weight: 700;
    border-radius: 20px; padding: 1px 7px;
}
.return-table-wrap { height: 300px; overflow-y: auto; }
table.return-table { width: 100%; border-collapse: collapse; }
table.return-table thead th {
    position: sticky; top: 0; z-index: 5;
    background: #f8fafc; color: #475569;
    padding: 10px 14px; font-size: .77rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid #e2e8f0;
}
table.return-table tbody td {
    padding: 11px 14px; font-size: .875rem;
    border-bottom: 1px solid #f8fafc; vertical-align: middle;
}
table.return-table tbody tr:hover td { background: #fafafa; }
.btn-del {
    background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2;
    border-radius: 7px; padding: 4px 9px; cursor: pointer; font-size: .82rem;
    transition: all .15s;
}
.btn-del:hover { background: #ef4444; color: #fff; }
.empty-state {
    text-align: center; padding: 40px 20px;
    color: #94a3b8;
}
.empty-state i { font-size: 2.5rem; margin-bottom: 10px; display: block; }
.empty-state p  { font-size: .88rem; margin: 0; }

/* ─── Bottom Action Bar ─────────────────────────────────────────────────── */
.pr-action-bar {
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
    gap: 14px; padding: 16px 20px; background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}
.action-bar-left { display: flex; align-items: center; gap: 16px; }
.txn-group label {
    display: block; font-size: .7rem; font-weight: 700;
    color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px;
}
.txn-group input {
    padding: 8px 14px; border: 1.5px solid #e2e8f0; border-radius: 9px;
    font-size: .88rem; font-weight: 700; color: #1e293b; background: #fff;
    letter-spacing: 1px; min-width: 150px;
}
.action-bar-right { display: flex; gap: 10px; }
.btn-process {
    padding: 10px 24px; border: none; border-radius: 10px;
    background: linear-gradient(135deg,#1d4ed8,#3b82f6);
    color: #fff; font-weight: 700; font-size: .9rem;
    display: flex; align-items: center; gap: 8px;
    cursor: pointer; transition: all .2s;
    box-shadow: 0 2px 8px rgba(29,78,216,.35);
}
.btn-process:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(29,78,216,.45); }
.btn-print {
    padding: 10px 20px; border-radius: 10px;
    background: #1e293b; color: #fff; border: none;
    font-weight: 600; font-size: .9rem;
    display: flex; align-items: center; gap: 8px;
    cursor: pointer; transition: all .2s;
}
.btn-print:hover { background: #334155; }
</style>

        <div class="pr-wrapper">

            <!-- Page Header -->
            <div class="pr-page-header">
                <div class="icon-wrap"><i class="fa-solid fa-rotate-left"></i></div>
                <div>
                    <h1>Purchased Return</h1>
                    <p>Search, select, and process product returns from inventory</p>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="pr-steps">
                <div class="pr-step active" id="step-1">
                    <div class="pr-step-num">1</div>
                    <div class="pr-step-info">
                        <div class="label">Find Product</div>
                        <div class="sub">Set filters &amp; search</div>
                    </div>
                </div>
                <div class="pr-step-divider"></div>
                <div class="pr-step" id="step-2">
                    <div class="pr-step-num">2</div>
                    <div class="pr-step-info">
                        <div class="label">Select &amp; Configure</div>
                        <div class="sub">Pick product &amp; quantity</div>
                    </div>
                </div>
                <div class="pr-step-divider"></div>
                <div class="pr-step" id="step-3">
                    <div class="pr-step-num">3</div>
                    <div class="pr-step-info">
                        <div class="label">Review &amp; Submit</div>
                        <div class="sub">Process the return</div>
                    </div>
                </div>
            </div>

            <!-- STEP 1: Filter Bar -->
            <div class="pr-card" style="margin-bottom:20px;">
                <div class="pr-card-header">
                    <span class="card-title"><i class="fa-solid fa-filter"></i> Search Filters</span>
                </div>
                <div class="pr-card-body">
                    <div class="pr-filter-row">
                        <div class="pr-filter-group">
                            <label>Return Type</label>
                            <select id="return-type" name="return-type">
                                <option value="" selected disabled>Select Return Type</option>
                            </select>
                        </div>
                        <div class="pr-filter-group">
                            <label>Branch</label>
                            <select id="isynBranch" name="isynBranch">
                                <option value="" selected disabled>Select Branch</option>
                            </select>
                        </div>
                        <div class="pr-filter-group">
                            <label>Type</label>
                            <select id="type" name="productType[]">
                                <option value="" selected disabled>Select Type</option>
                            </select>
                        </div>
                        <div class="pr-filter-group">
                            <label>Category</label>
                            <select id="category" name="category">
                                <option value="" selected disabled>Select Category</option>
                            </select>
                        </div>
                        <button id="search-btn" type="button">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Product List + Detail Panel -->
            <div class="pr-card" style="margin-bottom: 20px;" id="step2-card">
                <div class="pr-card-header">
                    <span class="card-title"><i class="fa-solid fa-boxes-stacked"></i> Product List</span>
                    <span style="font-size:.8rem;color:#94a3b8;font-style:italic;">Click a row to view details &bull; Click again or outside to dismiss</span>
                </div>
                <div class="pr-card-body" style="padding:0;">
                    <div class="pr-split" id="prSplit">
                        <!-- Product List Column -->
                        <div class="product-list-col">
                            <div class="pr-table-wrap">
                                <table class="pr-table">
                                    <thead>
                                        <tr>
                                            <th style="width:50%">Product</th>
                                            <th style="width:20%">SI No.</th>
                                            <th style="width:20%">Serial No.</th>
                                            <th style="width:10%">Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableList">
                                        <tr>
                                            <td colspan="4" class="empty-state">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                                <p>Use filters above and click Search to load products</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-hint">
                                <i class="fa-regular fa-hand-pointer"></i>
                                Click a product row to view details and configure the return
                            </div>
                        </div>

                        <!-- Detail Panel Column (hidden until selection) -->
                        <div class="pr-detail-col" id="detailCol">
                            <div class="pr-detail-panel">
                                <div class="detail-header">
                                    <span class="detail-badge">Selected Product</span>
                                    <h3 id="detailProductName">—</h3>
                                </div>
                                <div class="detail-body">
                                    <div class="detail-grid">
                                        <div class="detail-field">
                                            <label>SI No.</label>
                                            <div class="val" id="detailSIno">—</div>
                                        </div>
                                        <div class="detail-field">
                                            <label>Serial No.</label>
                                            <div class="val" id="detailSerial">—</div>
                                        </div>
                                        <div class="detail-field">
                                            <label>Supplier</label>
                                            <div class="val" id="detailSupplier">—</div>
                                        </div>
                                        <div class="detail-field">
                                            <label>Category</label>
                                            <div class="val" id="detailCategory">—</div>
                                        </div>
                                        <div class="detail-field">
                                            <label>Type</label>
                                            <div class="val" id="detailType">—</div>
                                        </div>
                                        <div class="detail-field">
                                            <label>Branch</label>
                                            <div class="val" id="detailBranch">—</div>
                                        </div>
                                        <div class="detail-field price">
                                            <label>Dealer Price</label>
                                            <div class="val" id="detailDealerPrice">—</div>
                                        </div>
                                        <div class="detail-field price">
                                            <label>SRP</label>
                                            <div class="val" id="detailSRP">—</div>
                                        </div>
                                        <div class="detail-field" style="grid-column:1/-1">
                                            <label>Available Qty</label>
                                            <div class="val" id="detailMaxQty">—</div>
                                        </div>
                                    </div>
                                    <div class="detail-divider"></div>
                                    <div class="detail-qty-label">Reason for Return</div>
                                    <input type="text" id="returnReason" 
                                           style="width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:9px;font-size:.88rem;margin-bottom:10px;"
                                           placeholder="e.g. Defective, wrong item, warranty…">
                                    <div class="detail-qty-label">Return Quantity</div>
                                    <input type="number" id="quantityDisplay" class="detail-qty-input"
                                           value="1" min="1" placeholder="Enter qty">
                                    <input type="hidden" id="maxQuantityDisplay">
                                    <button class="btn-add-return" onclick="returnSingleItem()">
                                        <i class="fa-solid fa-circle-plus"></i> Add to Return List
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Items to Return -->
            <div class="pr-card return-list-section" id="step3-card">
                <div class="pr-card-header">
                    <span class="card-title">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Items to Return
                        <span class="badge-count" id="returnCount">0</span>
                    </span>
                </div>

                <!-- Tabs -->
                <div class="pr-tabs">
                    <button class="pr-tab-btn active" data-tab="to-return">
                        <i class="fa-solid fa-list-check"></i> To Return
                    </button>
                    <button class="pr-tab-btn" data-tab="history">
                        <i class="fa-solid fa-box-archive"></i> Returned History
                    </button>
                </div>

                <!-- To Return Tab -->
                <div class="tab-pane-pr active" id="tab-to-return">
                    <div class="return-table-wrap">
                        <table class="return-table">
                            <thead>
                                <tr>
                                    <th style="width:28%">Product</th>
                                    <th style="width:12%">SI No.</th>
                                    <th style="width:15%">Serial No.</th>
                                    <th style="width:8%">Qty</th>
                                    <th style="width:14%">Return Type</th>
                                    <th style="width:15%">Reason</th>
                                    <th style="width:8%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="returnList">
                                <tr id="returnEmptyRow">
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-inbox"></i>
                                            <p>No items added yet. Select a product above to begin.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane-pr" id="tab-history">
                    <div class="return-table-wrap">
                        <table class="return-table">
                            <thead>
                                <tr>
                                    <th style="width:11%">Txn No.</th>
                                    <th style="width:28%">Product</th>
                                    <th style="width:10%">SI No.</th>
                                    <th style="width:14%">Serial No.</th>
                                    <th style="width:6%">Qty</th>
                                    <th style="width:12%">Branch</th>
                                    <th style="width:19%">Date</th>
                                </tr>
                            </thead>
                            <tbody id="archivedList">
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                            <p>Loading history...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="pr-action-bar">
                    <div class="action-bar-left">
                        <div class="txn-group">
                            <label>Transaction ID</label>
                            <input type="text" id="returnReceiptID"
                                   value="<?php echo $transactionNo; ?>" disabled>
                        </div>
                        <div class="pr-filter-group" style="min-width:180px;">
                            <label style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Filter Category</label>
                            <select id="printCategoryFilter" style="padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.84rem;">
                                <option value="All">All Categories</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-bar-right">
                        <button class="btn-print" id="printBtn" onclick="printData()" style="display:none;">
                            <i class="fa-solid fa-print"></i> Print
                        </button>
                        <button class="btn-process" id="archiveBtn" onclick="archiveVisibleItems()">
                            <i class="fa-solid fa-box-archive"></i> Process Return
                        </button>
                    </div>
                </div>
            </div>

        </div><!-- /pr-wrapper -->

        <?php include(dirname(__DIR__, 2) . '/includes/pages.footer.php'); ?>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/purchasedreturned_maintenance.js?<?= time() ?>"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>
