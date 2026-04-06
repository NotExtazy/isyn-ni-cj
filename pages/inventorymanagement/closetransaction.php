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
                background: linear-gradient(135deg, var(--primary-color) 0%, #2c3e50 100%);
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
                font-size: 1.1rem;
                font-weight: 600;
                color: var(--text-main);
                transition: all 0.2s ease;
                text-align: center;
            }
            
            .form-control:focus {
                background-color: #fff;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            }

            /* Buttons */
            .btn-action {
                background-color: var(--primary-color);
                color: white;
                border: none;
                border-radius: 12px;
                padding: 1rem;
                font-weight: 700;
                font-size: 1.1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                width: 100%;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 4px 15px rgba(67, 94, 190, 0.3);
            }
            
            .btn-action:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(67, 94, 190, 0.4);
                background-color: #364fc7;
                color: white;
            }
            
            .btn-action:active {
                transform: translateY(0);
            }
            
            .transaction-icon {
                font-size: 4rem;
                color: rgba(255,255,255,0.2);
                margin-bottom: 1rem;
            }

        </style>

        <div class="container-fluid main-container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-8 col-sm-10">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <div class="mb-3">
                                <i class="fa-solid fa-file-invoice-dollar transaction-icon"></i>
                            </div>
                            <h1 class="card-title-modern">Close Transaction</h1>
                            <p class="card-subtitle-modern">Finalize and lock today's inventory transactions</p>
                        </div>
                        <div class="card-body-modern">
                            <div class="mb-4">
                                <label for="closingDate" class="form-label text-center w-100">Select Closing Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 ps-3">
                                        <i class="fa-regular fa-calendar text-primary fs-5"></i>
                                    </span>
                                    <input type="text" class="form-control Date ps-2" id="closingDate" name="closingDate" placeholder="mm/dd/yyyy">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-action" id="closeTransactionBtn" name="closeTransactionBtn" type="button" onclick="CloseTransaction();">
                                    <i class="fa-solid fa-lock"></i>
                                    Close Transaction
                                </button>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <p class="text-muted small m-0">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    This action cannot be undone. Please ensure all data is correct.
                                </p>
                            </div>
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
        <script>
            var baseUrl = "<?php echo $base; ?>";
        </script>
        <script src="<?php echo $base; ?>/js/inventorymanagement/closetransaction.js?<?= time() ?>"></script>
        
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "' . $base . '/login.php"; </script>';
  }
?>