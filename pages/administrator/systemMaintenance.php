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
    <head>
        <?php include('../../includes/pages.header.php'); ?>
        <title>iSyn | System Maintenance</title>
        
        <style>
            main { background-color: #f3f4f6; }
            
            .dashboard-card {
                background: white;
                border: none;
                border-radius: 20px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                height: 100%;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                overflow: hidden;
            }
            
            .dashboard-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            }

            .card-header-clean {
                background: transparent;
                border-bottom: 1px solid #f1f5f9;
                padding: 1.5rem 1.5rem 1rem;
            }

            .card-title-premium {
                font-family: 'Inter', sans-serif;
                font-weight: 700;
                color: #1e293b;
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0;
            }

            /* Icon Styling */
            .icon-wrapper {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                background: linear-gradient(135deg, #e0e7ff 0%, #fae8ff 100%);
                color: #4f46e5;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
                flex-shrink: 0;
            }

            .btn-action-premium {
                background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
                border: none;
                padding: 12px 24px;
                font-weight: 600;
                letter-spacing: 0.3px;
                border-radius: 10px;
                color: white;
                box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
                transition: all 0.2s;
            }

            .btn-action-premium:hover {
                background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
                box-shadow: 0 6px 10px rgba(79, 70, 229, 0.3);
                color: white;
            }

            .stat-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 0;
                border-bottom: 1px solid #f8fafc;
            }
            .stat-row:last-child { border-bottom: none; }
            .stat-label { color: #64748b; font-size: 0.9rem; }
            .stat-value { color: #0f172a; font-weight: 600; font-family: monospace; }
            
            .badge-soft-success {
                background-color: #dcfce7;
                color: #166534;
                padding: 4px 8px;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            /* Custom Download Button Styles */
            .btn-custom-download {
                width: 135px;
                height: 45px;
                border-radius: 20px;
                border: none;
                box-shadow: 1px 1px rgba(58, 87, 232, 0.3);
                padding: 5px 10px;
                background: linear-gradient(160deg, #3a57e8 0%, #2f45c5 100%); 
                color: #fff;
                font-family: 'Inter', sans-serif;
                font-weight: 600;
                font-size: 16px;
                line-height: 1;
                cursor: pointer;
                filter: drop-shadow(0 0 10px rgba(58, 87, 232, 0.4));
                transition: .5s linear;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-custom-download .mysvg {
                display: none;
            }

            .btn-custom-download:hover {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                transition: .5s linear;
                padding: 0;
            }

            .btn-custom-download:hover .texto {
                display: none;
            }

            .btn-custom-download:hover .mysvg {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn-custom-download:hover::before {
                content: '';
                position: absolute;
                top: -3px;
                left: -3px;
                width: 100%;
                height: 100%;
                border: 3.5px solid transparent;
                border-top: 3.5px solid #3a57e8;
                border-right: 3.5px solid #3a57e8;
                border-radius: 50%;
                animation: animateC 2s linear infinite;
                box-sizing: content-box;
            }

            @keyframes animateC {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
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
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase text-primary fw-bold mb-1 tracking-wide small">System Management</h6>
                    <h2 class="fw-bold text-dark display-6 mb-0" style="font-size: 2rem;">Overview & Tools</h2>
                </div>
                <div>
                    <span class="badge bg-white text-secondary border shadow-sm px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-clock me-1"></i> Server Time: <?= date('h:i A') ?>
                    </span>
                </div>
            </div>

            <div class="row g-4">
                
                <!-- MAIN COLUMN: BACKUP TOOL -->
                <div class="col-lg-8">
                    <div class="dashboard-card d-flex flex-column h-100">
                        <div class="card-header-clean">
                            <h4 class="card-title-premium">
                                <span class="icon-wrapper"><i class="fa-solid fa-database"></i></span>
                                <div>
                                    Database Administration
                                    <small class="d-block text-muted fw-normal fs-6 mt-1">Manage backups and archives</small>
                                </div>
                            </h4>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            
                            <div class="alert alert-light border-start border-primary border-3 shadow-sm mb-4">
                                <div class="d-flex">
                                    <i class="fa-solid fa-circle-info text-primary mt-1 me-3 fs-5"></i>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">Weekly Backup Recommended</h6>
                                        <p class="mb-0 small text-muted">
                                            Regular backups ensure data safety. Use the tool below to generate an immediate full snapshot of the <b>iSynergies</b> database. 
                                            The file will be downloaded as a password-protected zip (if configured) or standard SQL dump.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto p-4 bg-light rounded-3 border border-1 d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <h6 class="fw-bold mb-1">Generate Full Backup</h6>
                                    <small class="text-muted">Export current state (Tables + Data)</small>
                                </div>
                                <button class="btn-custom-download" id="btnGenerateBackup">
                                    <span class="mysvg">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <g id="Interface / Download"> 
                                                    <path id="Vector" d="M6 21H18M12 3V17M12 17L17 12M12 17L7 12" stroke="#f1f1f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                                </g> 
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="texto">Download</span>
                                </button>
                            </div>
                            
                            <div id="backupStatus" class="mt-3 text-muted small" style="display:none; min-height: 20px;"></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: SYSTEM INFO -->
                <div class="col-lg-4">
                    <div class="dashboard-card h-100">
                        <div class="card-header-clean">
                            <h5 class="card-title-premium fs-5">
                                <span class="icon-wrapper" style="width: 40px; height: 40px; font-size: 1rem; background: linear-gradient(135deg, #ffedd5 0%, #fff7ed 100%); color: #c2410c;">
                                    <i class="fa-solid fa-server"></i>
                                </span>
                                System Status
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="stat-row">
                                <span class="stat-label">PHP Version</span>
                                <span class="stat-value text-primary"><?= phpversion() ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Server OS</span>
                                <span class="stat-value text-muted small text-end" style="max-width: 150px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= php_uname('s') . ' ' . php_uname('r') ?>">
                                    <?= php_uname('s') ?>
                                </span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Database Status</span>
                                <span class="badge-soft-success"><i class="fa-solid fa-check-circle me-1"></i>Connected</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Active User</span>
                                <span class="stat-value"><?= $_SESSION['USERNAME'] ?? 'Unknown' ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Session ID</span>
                                <span class="text-muted small" style="font-family: monospace;"><?= substr(session_id(), 0, 8) ?>...</span>
                            </div>
                        </div>
                        <div class="px-4 pb-4">
                             <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fa-solid fa-code-branch me-2"></i>
                                    <span>Version 3.1.0 (Stable)</span>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php include('../../includes/pages.footer.php'); ?>
        <script src="../../js/administrator/systemMaintenance.js?v=<?= time() ?>"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>