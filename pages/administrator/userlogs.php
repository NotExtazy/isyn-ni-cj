<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once('../../includes/permissions.php');
        $permissions = new Permissions();
        
        // Direct module access check for User Logs (module_id=167)
        if (!$permissions->hasAccess(167)) {
            header("Location: ../../dashboard");
            exit;
        }
?>
<!doctype html>
<html lang="en" dir="ltr">
    <head>
        <?php include('../../includes/pages.header.php'); ?>
        <title>iSyn | System User Logs</title>
        
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
        
        <style>
            main { background-color: #f3f4f6; }
            
            /* --- Dashboard Card --- */
            .dashboard-card {
                background: white;
                border: none;
                border-radius: 16px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                overflow: hidden;
            }

            /* --- Filter Bar Styles --- */
            .filter-bar {
                background: white;
                border-radius: 12px;
                padding: 1.25rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                border: 1px solid #f1f5f9;
            }
            
            .form-label-wm {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 700;
                color: #64748b;
                margin-bottom: 0.5rem;
            }
            
            .form-control-wm, .form-select-wm {
                border-color: #e2e8f0;
                border-radius: 8px;
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            
            .form-control-wm:focus, .form-select-wm:focus {
                border-color: #6366f1;
                box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            }

            /* --- Premium Table Styles --- */
            .table-premium-container {
                border-radius: 12px;
                overflow: hidden;
            }
            
            #logTable thead th {
                background-color: #3a57e8;
                color: #ffffff;
                padding: 16px;
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.8px;
                border: none;
                vertical-align: middle;
            }
            
            #logTable tbody td {
                padding: 16px;
                vertical-align: middle;
                border-bottom: 1px solid #f1f5f9;
                color: #334155;
                font-size: 0.9rem;
            }
            
            #logTable tbody tr:hover {
                background-color: #f8fafc;
            }

            /* --- Badges --- */
            .badge-soft {
                padding: 6px 10px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }

            .badge-insert { background-color: #dbeafe; color: #1e40af; }
            .badge-update { background-color: #fee2e2; color: #991b1b; }
            .badge-delete { background-color: #fee2e2; color: #991b1b; }
            .badge-login  { background-color: #d1fae5; color: #065f46; }
            .badge-export { background-color: #ffedd5; color: #9a3412; }
            .badge-default{ background-color: #f1f5f9; color: #475569; }

            /* --- Page Header --- */
            .page-title {
                font-family: 'Inter', sans-serif;
                font-weight: 800;
                letter-spacing: -0.5px;
                color: #1e293b;
            }

            /* Custom Search Button */
            .btn-filter {
                background-color: #3a57e8;
                color: white;
                border: none;
                border-radius: 8px;
                padding: 8px 16px;
                font-weight: 600;
                transition: all 0.2s;
            }
            .btn-filter:hover {
                background-color: #3146ce;
                transform: translateY(-1px);
            }
        </style>
    </head>

    <body>
        <div id="loading">
            <div class="loader simple-loader">
                <div class="loader-body"></div>
            </div>
        </div>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

        <div class="container-fluid py-4 px-4">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h6 class="text-uppercase text-primary fw-bold mb-1 tracking-wide small">Security & Audit</h6>
                    <h2 class="page-title display-6 mb-0" style="font-size: 1.75rem;">System User Logs</h2>
                    <p class="text-muted small mt-1 mb-0">Track all timeline activities, data modifications, and security events.</p>
                </div>
                <div>
                    <div class="bg-white px-3 py-2 rounded-pill shadow-sm border d-flex align-items-center gap-2 text-secondary small fw-medium">
                        <i class="fa-solid fa-shield-halved text-success"></i> Audit Logging Active
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-bar mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label-wm">Start Date</label>
                        <input type="date" id="filterStartDate" class="form-control form-control-wm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label-wm">End Date</label>
                        <input type="date" id="filterEndDate" class="form-control form-control-wm" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label-wm">Module</label>
                        <select id="filterModule" class="form-select form-select-wm">
                            <option value="">All Modules</option>
                        </select>
                    </div>
                     <div class="col-lg-2 col-md-4">
                        <label class="form-label-wm">User</label>
                        <select id="filterUser" class="form-select form-select-wm">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label-wm">Action Type</label>
                        <select id="filterAction" class="form-select form-select-wm">
                            <option value="">All Actions</option>
                            <option value="LOGIN">LOGIN</option>
                            <option value="INSERT">INSERT (Entry)</option>
                            <option value="UPDATE">UPDATE (Edit)</option>
                            <option value="DELETE">DELETE</option>
                            <option value="EXPORT">EXPORT / GENERATE</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="d-flex gap-2">
                            <button id="btnFilter" class="btn btn-filter w-100"><i class="fa-solid fa-filter me-2"></i>Apply</button>
                            <button id="btnRefresh" class="btn btn-light border w-auto px-3" title="Reset Filters"><i class="fa-solid fa-rotate-right text-muted"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="dashboard-card">
                
                <!-- Custom Controls Header -->
                <div class="d-flex w-100 justify-content-between align-items-center px-4 py-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary small fw-bold text-uppercase">Show</span>
                        <select id="customLength" class="form-select form-select-sm border-2" style="width: 70px; font-weight: 600;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="text-secondary small fw-bold text-uppercase">Entries</span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                         <div class="input-group input-group-sm" style="width: 280px;">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="customSearch" class="form-control border-start-0 ps-1" placeholder="Search logs..." autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="table-responsive table-premium-container">
                    <table id="logTable" class="table w-100 mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width: 12%;">Timestamp</th>
                                <th style="width: 15%;">User</th>
                                <th style="width: 15%;">Module</th>
                                <th style="width: 10%;">Action</th>
                                <th style="width: 38%;">Description</th>
                                <th style="width: 10%;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody id="logList">
                            <!-- JS Populated -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <?php include('../../includes/pages.footer.php'); ?>
        
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
        <script src="../../js/administrator/userlogs.js?v=<?= time() ?>"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>