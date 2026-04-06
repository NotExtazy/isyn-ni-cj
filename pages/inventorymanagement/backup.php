<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    
    // Calculate base path outside the if block so it's available in both branches
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $pagesPos = strpos($scriptDir, '/pages');
    if ($pagesPos !== false) {
        $base = substr($scriptDir, 0, $pagesPos);
    } else {
        $base = rtrim($scriptDir, '/\\');
    }
    if ($base === '.' ) { $base = ''; }
    
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include(dirname(__DIR__, 2) . '/includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="<?php echo $base; ?>/assets/select2/css/select2.min.css">
      <!-- Add Google Font -->
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <body class="">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
            include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
        ?>

        <style>
            :root {
                --primary-color: #435ebe; /* Royal Blue */
                --secondary-color: #6c757d;
                --success-color: #198754;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
                --info-color: #0dcaf0;
                --background-color: #f2f7ff; /* Very Light Blue-Gray */
                --card-bg: #ffffff;
                --text-main: #25396f;
                --text-secondary: #7c8db5;
                --border-color: #eef2f6;
                --input-bg: #f8f9fa;
            }

            body {
                background-color: var(--background-color);
                font-family: 'Inter', sans-serif;
                color: var(--text-main);
            }

            /* Layout & Spacing */
            .main-container {
                padding: 2rem;
                min-height: 80vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            /* Cards */
            .card-modern {
                background: var(--card-bg);
                border: none;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                overflow: hidden;
            }
            
            .card-header-modern {
                background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
                padding: 2rem;
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }

            .card-title-modern {
                font-size: 1.5rem;
                font-weight: 700;
                color: #fff;
                margin: 0;
                letter-spacing: -0.5px;
            }
            
            .card-subtitle-modern {
                color: rgba(255,255,255,0.8);
                font-size: 0.9rem;
                margin-top: 0.5rem;
            }

            .card-body-modern {
                padding: 3rem 2.5rem;
            }

            /* Form Elements */
            .form-label {
                font-weight: 600;
                font-size: 0.9rem;
                color: var(--text-main);
                margin-bottom: 0.75rem;
                display: block;
            }

            .form-control {
                background-color: var(--input-bg);
                border: 2px solid var(--border-color);
                border-radius: 12px;
                padding: 1rem 1.25rem;
                font-size: 1rem;
                font-weight: 500;
                color: var(--text-main);
                transition: all 0.2s ease;
            }
            
            .form-control:focus {
                background-color: #fff;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            }
            
            .form-control:read-only {
                background-color: #e9ecef;
                color: var(--text-secondary);
            }

            /* Buttons */
            .btn {
                border-radius: 12px;
                padding: 0.8rem 1.5rem;
                font-weight: 600;
                font-size: 1rem;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
                border: none;
            }
            
            .btn:active {
                transform: scale(0.98);
            }

            .btn-primary {
                background-color: var(--primary-color);
                box-shadow: 0 4px 12px rgba(67, 94, 190, 0.3);
            }
            
            .btn-success {
                background-color: var(--success-color);
                box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
                width: 100%;
                justify-content: center;
                padding: 1rem;
                font-size: 1.1rem;
            }
            
            .backup-icon {
                font-size: 4rem;
                color: rgba(255,255,255,0.2);
                margin-bottom: 1rem;
            }
            
            .folder-select-group {
                display: flex;
                gap: 10px;
            }
            
            .folder-input {
                flex-grow: 1;
            }

        </style>

        <div class="container-fluid main-container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8 col-sm-10">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <div class="mb-3">
                                <i class="fa-solid fa-database backup-icon"></i>
                            </div>
                            <h1 class="card-title-modern">System Backup</h1>
                            <p class="card-subtitle-modern">Securely backup your entire database and files</p>
                        </div>
                        <div class="card-body-modern">
                            <form id="backupForm" method="POST" enctype="multipart/form-data" novalidate>
                                <div class="mb-4">
                                    <label class="form-label text-uppercase text-secondary" style="font-size: 0.8rem;">Configuration</label>
                                    <h5 class="fw-bold mb-3">Full Data Backup</h5>
                                    
                                    <div class="bg-light p-4 rounded-3 border">
                                        <label for="backupFolder" class="form-label">Destination Folder</label>
                                        <div class="folder-select-group">
                                            <div class="folder-input">
                                                <input type="text" class="form-control" id="folder-info" placeholder="Default Directory (Downloads)" readonly>
                                            </div>
                                            <button class="btn btn-primary" id="select-folder" type="button" title="Change Destination">
                                                <i class="fa-solid fa-folder-open"></i>
                                            </button>
                                        </div>
                                        <div class="form-text mt-2">
                                            <i class="fa-solid fa-circle-info me-1"></i>
                                            The system will generate a compressed .zip file containing all database tables.
                                        </div>
                                    </div>
                                </div>

                                <!-- Progress Indicator (Hidden by default) -->
                                <div id="progress-container" class="mb-4 text-center" style="display: none;">
                                    <div class="d-flex flex-column align-items-center">
                                        <!--<img src="../../assets/images/naruto-running.gif" alt="Backup in progress" class="rounded-circle shadow-sm mb-3" style="height: 80px; width: 80px; object-fit: cover;">-->
                                        <h6 class="fw-bold">Backup in Progress...</h6>
                                        <p id="progress-text" class="text-muted small m-0">Initializing process...</p>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <button class="btn btn-success btn-lg" type="submit" id="start-btn">
                                        <i class="fa-solid fa-play"></i> Start Backup Process
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    
        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <script src="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="<?php echo $base; ?>/assets/select2/js/select2.full.min.js"></script>
        <!-- <script src="<?php echo $base; ?>/js/inventorymanagement/backup.js?<?= time() ?>"></script> -->

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "' . $base . '/login.php"; </script>';
  }
?>