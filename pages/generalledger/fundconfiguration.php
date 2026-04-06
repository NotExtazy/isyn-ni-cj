<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        $permissionsPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/permissions.php';
        require_once($permissionsPath);
        $permissions = new Permissions();
        
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
      <link rel="stylesheet" href="/iSynApp-main/assets/select2/css/select2.min.css">
      <style>
        .fund-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .fund-item {
            cursor: pointer;
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        .fund-item:hover {
            background-color: #f8f9fa;
        }
        .fund-item.selected {
            background-color: #d1ecf1;
            border-left: 3px solid #3a7ca5;
        }
        .arrow-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
      </style>

    <body class="  ">
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>

        <?php
            $sidebarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.sidebar.php';
            $navbarPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/includes/pages.navbar.php';
            include($sidebarPath);
            include($navbarPath);
        ?>

            <div class="container-fluid mt-4" style="max-width:98%;padding-left:15px;padding-right:15px;">
                <div class="p-3 shadow-sm rounded-2 mb-4" style="background:white;border-left:4px solid #3a7ca5;">
                    <p class="fs-5 mb-0" style="color:#3a7ca5;font-weight:700;"><i class="fa-solid fa-layer-group me-2"></i>Fund Configuration</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="p-3 shadow-sm rounded-2" style="background-color: white;">
                            <p class="fs-6" style="color:#3a7ca5;">Configure GL Funds</p>
                            <hr style="height:1px;background:#e0e0e0;">
                            
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Available AMS Funds</label>
                                        <p class="text-muted small">Select funds to add to GL</p>
                                        <div class="fund-list" id="amsFundsList"></div>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="arrow-buttons" style="padding-top:80px;">
                                        <button class="btn btn-primary" id="btnAddToGL" type="button" title="Add to GL">
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                        <button class="btn btn-danger" id="btnRemoveFromGL" type="button" title="Remove from GL">
                                            <i class="fa-solid fa-arrow-left"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Configured GL Funds</label>
                                        <p class="text-muted small">Funds available in General Ledger</p>
                                        <div class="fund-list" id="glFundsList"></div>
                                    </div>
                                </div>
                            </div>

                            <hr style="height:1px;background:#e0e0e0;">

                            <div class="d-flex justify-content-end gap-2">
                                <button class="btn btn-success" id="btnSave" type="button">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Configuration
                                </button>
                                <button class="btn btn-warning text-white" id="btnReset" type="button">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Reset
                                </button>
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
        <script src="/iSynApp-main/js/generalledger/fundconfiguration.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>
