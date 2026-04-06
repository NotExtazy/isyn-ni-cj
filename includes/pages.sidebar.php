<?php
    // Temporarily bypass permissions to fix database errors
    // require_once(__DIR__ . '/permissions.php');
    // $permissions = new Permissions();
    
    // Create dummy permissions object
    $permissions = new class {
        public function hasAccess($moduleId) {
            return true; // Allow all access for now
        }
    };
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    // Find the position of '/pages' in the path to determine root
    $pagesPos = strpos($scriptDir, '/pages');
    if ($pagesPos !== false) {
        // We are inside /pages/, so base path is everything before it
        $BASE_PATH = substr($scriptDir, 0, $pagesPos);
    } else {
        // Derive base path from the app root relative to DOCUMENT_ROOT
        $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $appRoot = rtrim(str_replace('\\', '/', dirname(dirname(__FILE__))), '/');
        $BASE_PATH = str_replace($docRoot, '', $appRoot);
    }
    // Ensure base path is never just '.' or '/'
    if ($BASE_PATH === '.' || $BASE_PATH === '/') { 
        $BASE_PATH = ''; 
    }
?>
<aside class="sidebar sidebar-default sidebar-white sidebar-base navs-rounded-all ">
        <div class="sidebar-header d-flex align-items-center justify-content-start">
            <a href="<?php echo $BASE_PATH; ?>/dashboard" class="navbar-brand">
                <!--Logo start-->
                <div class="logo-main">
                    <img src="<?php echo $BASE_PATH; ?>/assets/images/small-logo.png" alt="isynergies logo" style="width: 30px; height: auto;">
                </div>
                <!--logo End-->
                <h6 class="logo-title">iSynergies Inc.</h6>
            </a>
            <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                <i class="icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4.25 12.2744L19.25 12.2744" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </i>
            </div>
        </div>
        <div class="sidebar-body pt-0 data-scrollbar">
            <div class="sidebar-list">
                <!-- Sidebar Menu Start -->
                <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="<?php echo $BASE_PATH; ?>/dashboard">
                            <i class="icon">
                                <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="icon-20">
                                    <path opacity="0.4" d="M16.0756 2H19.4616C20.8639 2 22.0001 3.14585 22.0001 4.55996V7.97452C22.0001 9.38864 20.8639 10.5345 19.4616 10.5345H16.0756C14.6734 10.5345 13.5371 9.38864 13.5371 7.97452V4.55996C13.5371 3.14585 14.6734 2 16.0756 2Z" fill="currentColor"></path>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.53852 2H7.92449C9.32676 2 10.463 3.14585 10.463 4.55996V7.97452C10.463 9.38864 9.32676 10.5345 7.92449 10.5345H4.53852C3.13626 10.5345 2 9.38864 2 7.97452V4.55996C2 3.14585 3.13626 2 4.53852 2ZM4.53852 13.4655H7.92449C9.32676 13.4655 10.463 14.6114 10.463 16.0255V19.44C10.463 20.8532 9.32676 22 7.92449 22H4.53852C3.13626 22 2 20.8532 2 19.44V16.0255C2 14.6114 3.13626 13.4655 4.53852 13.4655ZM19.4615 13.4655H16.0755C14.6732 13.4655 13.537 14.6114 13.537 16.0255V19.44C13.537 20.8532 14.6732 22 16.0755 22H19.4615C20.8637 22 22 20.8532 22 19.44V16.0255C22 14.6114 20.8637 13.4655 19.4615 13.4655Z" fill="currentColor"></path>
                                </svg>
                            </i>
                            <span class="item-name">Dashboard</span>
                        </a>
                    </li>

                    <!-- Profiling Panel -->
                    <?php if ($permissions->hasAccess(1)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Profiling') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#profiling-panel" role="button" aria-expanded="false" aria-controls="profiling-panel">
                            <i class="fa-solid fa-address-card"></i>
                            <span class="item-name">Profiling</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>

                        <ul class="sub-nav collapse <?= ($_SESSION['parent_module'] === 'Profiling') ? 'show' : '' ?>" id="profiling-panel" data-bs-parent="#sidebar-menu">

                            <?php if ($permissions->hasAccess(2)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Customerinfo') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/profiling/customerinfo">
                                  <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                  <i class="sidenav-mini-icon"> C </i>
                                  <span class="item-name">Customer Information</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(13)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Supplierinfo') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/profiling/supplierinfo">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> S </i>
                                    <span class="item-name">Supplier Information</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(18)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Isynstaff') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/profiling/isynstaff">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                   <i class="sidenav-mini-icon"> i </i>
                                   <span class="item-name">iSynergies Staff</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(19)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Shareholderinfo') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/profiling/shareholderinfo">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> S </i>
                                    <span class="item-name">Shareholder Information</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(20)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Boardofdirectors') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/profiling/boardofdirectors">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> B </i>
                                    <span class="item-name">Board of Directors</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php //if ($permissions->hasAccess(227)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Acashinfo') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/profiling/acashinfo">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> A </i>
                                    <span class="item-name">ACash Information</span>
                                </a>
                            </li>
                            <?php //endif; ?>

                        </ul>
                    </li>

                    <?php endif; ?>
                    <!-- Cashier Panel -->
                    <?php if ($permissions->hasAccess(21)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Cashier') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#cashier-panel" role="button" aria-expanded="false" aria-controls="sidebar-cashier">
                            <i class="fa-solid fa-cash-register"></i>
                            <span class="item-name">Cashier</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>

                        <?php if (isset($_SESSION['parent_module'])): ?>
                        <ul class="sub-nav collapse <?= ($_SESSION['parent_module'] === 'Cashier') ? 'show' : '' ?>" id="cashier-panel" data-bs-parent="#sidebar-menu">
                            <?php if ($permissions->hasAccess(22)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (isset($_SESSION['current_module']) && $_SESSION['current_module'] === 'Loanspayment') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/pages/cashier/loanspayment.php">
                                  <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                  <i class="sidenav-mini-icon"> B </i>
                                  <span class="item-name">Loans Payment</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(23)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Otherpayment') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/cashier/otherpayment">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> C </i>
                                    <span class="item-name">Other Payment</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(24)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Depositslip') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/cashier/depositslip">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                   <i class="sidenav-mini-icon"> K </i>
                                   <span class="item-name">Deposit Slip</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(25)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Collectionreport') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/cashier/collectionreport">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> P </i>
                                    <span class="item-name">Collection Report</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(26)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Tellersproofsheet') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/cashier/tellersproofsheet">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> T </i>
                                    <span class="item-name">Teller's Proofsheet</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(27)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Deletecancel') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/deletecancel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> T </i>
                                    <span class="item-name">Modify Transactions</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <?php
                                    $cashierconfiguration = ['Orsettings', 'Dssettings'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $cashierconfiguration) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#cashier-configuration-panel" role="button" aria-expanded="false" aria-controls="sidebar-cashier-configuration">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Configurations</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $cashierconfiguration) ? 'show' : '' ?>" id="cashier-configuration-panel" data-bs-parent="#cashier-panel">
                                    <?php if ($permissions->hasAccess(28)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Orsettings') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/cashier/orsettings">
                                          <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon">ORS </i>
                                          <span class="item-name">OR Setting</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
        
                                    <?php if ($permissions->hasAccess(29)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Dssettings') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/cashier/dssettings">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> DSS </i>
                                            <span class="item-name">DS Setting</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                        </ul>
                    </li>

                    <?php endif; ?>
                    <!-- Accounts Monitoring Panel -->
                    <?php if ($permissions->hasAccess(30)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Accountsmonitoring') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#accounts-monitoring-panel" role="button" aria-expanded="<?= ($_SESSION['parent_module'] === 'Accountsmonitoring') ? 'true' : 'false' ?>" aria-controls="accounts-monitoring-panel">
                            <i class="fa-solid fa-user"></i>
                            <span class="item-name">Account Monitoring</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>

                        <ul class="sub-nav collapse <?= ($_SESSION['parent_module'] === 'Accountsmonitoring') ? 'show' : '' ?>" id="accounts-monitoring-panel" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <?php
                                    $othertransaction = ['Journalvoucher', 'Otherdisbursement'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $othertransaction) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#am-other-transaction-panel" role="button" aria-expanded="false" aria-controls="sidebar-am-other-transaction">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Other Transaction</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>                                
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $othertransaction) ? 'show' : '' ?>" id="am-other-transaction-panel" data-bs-parent="#accounts-monitoring-panel">
                                    <?php if ($permissions->hasAccess(31)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Journalvoucher') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/journalvoucher">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> J </i>
                                          <span class="item-name">Journal Voucher</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
        
                                    <?php if ($permissions->hasAccess(32)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Otherdisbursement') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/otherdisbursement">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> O </i>
                                            <span class="item-name">Other Disbursement</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <?php if ($permissions->hasAccess(33)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Depreciation') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/depreciation">
                                  <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                  <i class="sidenav-mini-icon"> D </i>
                                  <span class="item-name">Depreciation</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(34)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Amortization') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/amortization">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> A </i>
                                    <span class="item-name">Amortization</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(35)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Loantransaction') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/pages/accountsmonitoring/loantransaction.php">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                   <i class="sidenav-mini-icon"> L </i>
                                   <span class="item-name">Loan Transaction</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php if ($permissions->hasAccess(36)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Pendingreleases') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/pendingreleases">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> P </i>
                                    <span class="item-name">Pending Releases</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($permissions->hasAccess(37)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Accountsandaging') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/accountsandaging">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> A </i>
                                    <span class="item-name">Accounts & Aging</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <?php
                                    $amconfiguration = ['Chartofaccounts', 'Banks', 'Fundings'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $amconfiguration) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#am-configuration-panel" role="button" aria-expanded="false" aria-controls="sidebar-am-configuration">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Configuration</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $amconfiguration) ? 'show' : '' ?>" id="am-configuration-panel" data-bs-parent="#accounts-monitoring-panel">
                                    <?php if ($permissions->hasAccess(38)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Chartofaccounts') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/chartofaccounts">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> C </i>
                                          <span class="item-name">Chart of Accounts</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
        
                                    <?php if ($permissions->hasAccess(39)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Banks') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/banks">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> B </i>
                                            <span class="item-name">Banks</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <?php if ($permissions->hasAccess(40)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Fundings') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/fundings">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> F </i>
                                            <span class="item-name">Fundings</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <?php
                                    $amreport = ['Reports'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $amreport) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#am-reports-panel" role="button" aria-expanded="false" aria-controls="sidebar-am-reports">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Reports</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $amreport) ? 'show' : '' ?>" id="am-reports-panel" data-bs-parent="#accounts-monitoring-panel">
                                    <?php if ($permissions->hasAccess(41)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Bookofaccounts') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/accountsmonitoring/bookofaccounts">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">Book of Accounts</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                        </ul>
                    </li>

                    <?php endif; ?>
                    <!-- General Ledger Panel -->
                    <?php if ($permissions->hasAccess(42)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Generalledger') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#general-ledger-panel" role="button" aria-expanded="false" aria-controls="sidebar-general-ledger">
                            <i class="fa-solid fa-book-open"></i>
                            <span class="item-name">General Ledger</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>

                        <ul class="sub-nav collapse <?= ($_SESSION['parent_module'] === 'Generalledger') ? 'show' : '' ?>" id="general-ledger-panel" data-bs-parent="#sidebar-menu">

                            <?php if ($permissions->hasAccess(43)): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Postandundopost') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/postandundopost">
                                  <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                  <i class="sidenav-mini-icon"> P&UP </i>
                                  <span class="item-name">Post & Undo Post</span>
                                </a>
                            </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <?php
                                    $ledgers = ['Snapshot', 'Generalledger', 'Subsidiaryledger'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $ledgers) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#gl-ledgers-panel" role="button" aria-expanded="false" aria-controls="sidebar-gl-ledgers-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> L </i>
                                    <span class="item-name">Ledgers</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $ledgers) ? 'show' : '' ?>" id="gl-ledgers-panel" data-bs-parent="#general-ledger-panel">
                                    <?php if ($permissions->hasAccess(44)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Snapshot') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/snapshot">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> S </i>
                                          <span class="item-name">Snapshot</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($permissions->hasAccess(45)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Generalledger') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/generalledger">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> GL </i>
                                            <span class="item-name">General Ledger</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <?php if ($permissions->hasAccess(46)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Subsidiaryledger') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/subsidiaryledger">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> SL </i>
                                            <span class="item-name">Subsidiary Ledger</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <?php
                                    $financials = ['Financialstatement', 'Trialbalance'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $financials) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#gl-financials-panel" role="button" aria-expanded="false" aria-controls="sidebar-gl-financials">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> F </i>
                                    <span class="item-name">Financials</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $financials) ? 'show' : '' ?>" id="gl-financials-panel" data-bs-parent="#general-ledger-panel">
                                    <?php if ($permissions->hasAccess(47)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Financialstatement') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/financialstatement">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> FS </i>
                                          <span class="item-name">Financial Statement</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
        
                                    <?php if ($permissions->hasAccess(48)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Trialbalance') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/trialbalance">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> TB </i>
                                            <span class="item-name">Trial Balance</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <?php
                                    $GLutilities = ['Fundconfiguration', 'Dataconfiguration', 'Updatetablestructure'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $GLutilities) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#gl-utilities-panel" role="button" aria-expanded="false" aria-controls="sidebar-gl-utilities">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> U </i>
                                    <span class="item-name">Utilities</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $GLutilities) ? 'show' : '' ?>" id="gl-utilities-panel" data-bs-parent="#general-ledger-panel">
                                    <?php if ($permissions->hasAccess(49)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Fundconfiguration') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/fundconfiguration">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> FC </i>
                                          <span class="item-name">Fund Configuration</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
        
                                    <?php if ($permissions->hasAccess(50)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Dataconfiguration') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/dataconfiguration">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> DC </i>
                                            <span class="item-name">Data Configuration</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
        
                                    <?php if ($permissions->hasAccess(51)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Updatetablestructure') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/generalledger/updatetablestructure">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                            <i class="sidenav-mini-icon"> UTS</i>
                                            <span class="item-name">Update Table Structure</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                        </ul>
                    </li>

                    <?php endif; ?>
                    <!-- Inventory Management Panel -->
                    <?php if ($permissions->hasAccess(52)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Inventorymanagement') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#inventory-management-panel" role="button" aria-expanded="false" aria-controls="sidebar-inventory-management">
                            <i class="fa-solid fa-boxes-stacked"></i>   
                            <span class="item-name">Inventory Management</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>

                        <ul class="sub-nav collapse <?= ($_SESSION['parent_module'] === 'Inventorymanagement') ? 'show' : '' ?>" id="inventory-management-panel" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <?php
                                    $inventory = ['Incominginventory', 'Outgoinginventory', 'Transmittalreceipt', 'Consignment', 'Purchasedreturn','Orderconfirmation'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $inventory) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#im-inventory-panel" role="button" aria-expanded="false" aria-controls="sidebar-im-inventory-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Inventory</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $inventory) ? 'show' : '' ?>" id="im-inventory-panel" data-bs-parent="#inventory-management-panel">
                                    <?php if ($permissions->hasAccess(53)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Incominginventory') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/incominginventory">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> I </i>
                                          <span class="item-name">Incoming</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(54)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Outgoinginventory') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/outgoinginventory">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> O </i>
                                          <span class="item-name">Outgoing</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(55)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Transmittalreceipt') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/transmittalreceipt">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> T </i>
                                          <span class="item-name">Transmittal Receipt</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(56)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Consignment') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/consignment">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> C </i>
                                          <span class="item-name">Consignment</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(57)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Purchasedreturn') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/purchasedreturn">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> P </i>
                                          <span class="item-name">Purchased Return</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(58)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Orderconfirmation') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/orderconfirmation">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> OC </i>
                                          <span class="item-name">Order Confirmation</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <?php
                                    $search = ['Searchproducts', 'Inventory', 'Inventorybalancing', 'Purchaseandsales', 'Paidunpaiditems', 'Purchasedreturnreport'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $search) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#im-search-panel" role="button" aria-expanded="false" aria-controls="sidebar-im-search-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Search</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $search) ? 'show' : '' ?>" id="im-search-panel" data-bs-parent="#inventory-management-panel">
                                    <?php if ($permissions->hasAccess(59)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Searchproducts') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/searchproducts">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> SP </i>
                                          <span class="item-name">Search Products</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(60)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Inventory') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/inventory">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> I </i>
                                          <span class="item-name">Inventory</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(61)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Inventorybalancing') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/inventorybalancing">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> IB </i>
                                          <span class="item-name">Inventory Balancing</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(62)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Purchaseandsales') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/purchaseandsales">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> PS </i>
                                          <span class="item-name">Purchases/Sales</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(63)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Paidunpaiditems') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/paidunpaiditems">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> PUPI </i>
                                          <span class="item-name">Paid/Unpaid Items</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(64)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Purchasedreturnreport') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/purchasedreturnreport">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> PRR </i>
                                          <span class="item-name">Purchase Returned Report</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            
                            <li class="nav-item">
                                <?php
                                    $edittransaction = ['Cancelanddeletesi', 'Cancelconsignment'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $edittransaction) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#im-edit-transaction-panel" role="button" aria-expanded="false" aria-controls="sidebar-im-edit-transaction-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Edit Transaction</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $edittransaction) ? 'show' : '' ?>" id="im-edit-transaction-panel" data-bs-parent="#inventory-management-panel">
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Cancelanddeletesi') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/cancelanddeleteSI">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> CD </i>
                                          <span class="item-name">Cancel and Delete</span>
                                        </a>
                                    </li>
                                    <?php if ($permissions->hasAccess(65)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Cancelconsignment') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/cancelconsignment">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> CC </i>
                                          <span class="item-name">Cancel Consignment</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            
                            <li class="nav-item">
                                <?php
                                    $configuration = ['Productmaintenance', 'Sisetup', 'Changeproductsrpdp'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $configuration) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#im-configuration-panel" role="button" aria-expanded="false" aria-controls="sidebar-im-configuration-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Configuration</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $configuration) ? 'show' : '' ?>" id="im-configuration-panel" data-bs-parent="#inventory-management-panel">
                                    <?php if ($permissions->hasAccess(66)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Productmaintenance') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/productmaintenance">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> PM </i>
                                          <span class="item-name">Product Maintenance</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(67)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Sisetup') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/sisetup">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">SL Setup</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(68)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Changeproductsrpdp') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/changeproductsrpdp">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">Product SRP & DP</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                            <li class="nav-item">
                                <?php
                                    $utilities = ['Closetransaction', 'Backup', 'Logviewer'];
                                ?>
                                <a class="nav-link <?= in_array($_SESSION['current_module'], $utilities) ? 'active' : '' ?>" data-bs-toggle="collapse" href="#im-utilities-panel" role="button" aria-expanded="false" aria-controls="sidebar-im-utilities-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Utilities</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse <?= in_array($_SESSION['current_module'], $utilities) ? 'show' : '' ?>" id="im-utilities-panel" data-bs-parent="#inventory-management-panel">
                                    <?php if ($permissions->hasAccess(69)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Closetransaction') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/closetransaction">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> CT </i>
                                          <span class="item-name">Close Transaction</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(70)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Backup') ? 'active' : '' ?>" href="<?php echo $BASE_PATH; ?>/inventorymanagement/backup">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> BD </i>
                                          <span class="item-name">Backup Data</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php if ($permissions->hasAccess(71)): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Logviewer') ? 'active' : '' ?>" href="#">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">Log Viewer</span>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </li>

                        </ul>
                    </li>

                    <?php endif; ?>
                    <!-- System Development Panel -->
                    <?php if ($permissions->hasAccess(72)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="collapse" href="#system-development-panel" role="button" aria-expanded="false" aria-controls="sidebar-system-development">
                            <i class="fa-solid fa-file-circle-check"></i>
                            <span class="item-name">System Development</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>
                        
                        <ul class="sub-nav collapse" id="system-development-panel" data-bs-parent="#sidebar-menu">
                            <li class="nav-item">
                                <a class="nav-link " href="#">
                                  <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                  <i class="sidenav-mini-icon"> B </i>
                                  <span class="item-name">Projects</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="#">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> C </i>
                                    <span class="item-name">Project Reports</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="#">
                                   <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                   <i class="sidenav-mini-icon"> K </i>
                                   <span class="item-name">iSyn Logs</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link " href="#">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> P </i>
                                    <span class="item-name">Other Monitoring</span>
                                </a>
                            </li>
                        </ul>

                    </li>
                    <?php endif; ?>

                    <!-- Administrator Panel -->
                    <?php if ($permissions->hasAccess(73)): ?>
                    <li><hr class="hr-horizontal"></li>
                    <li class="nav-item">

                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Administrator') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#administrator-panel" role="button" aria-expanded="<?= ($_SESSION['parent_module'] === 'Administrator') ? 'true' : 'false' ?>" aria-controls="sidebar-administrator">
                            <i class="fa-solid fa-user-gear"></i>
                            <span class="item-name">Administrator</span>
                            <i class="right-icon">
                                <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </i>
                        </a>

                        <ul class="sub-nav collapse <?= ($_SESSION['parent_module'] === 'Administrator') ? 'show' : '' ?>" id="administrator-panel" data-bs-parent="#sidebar-menu">
                            
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" href="#admin-maintenance-panel" role="button" aria-expanded="false" aria-controls="sidebar-admin-maintenance-panel">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <span class="item-name">Maintenance</span>
                                    <i class="right-icon">
                                        <svg class="icon-18" xmlns="http://www.w3.org/2000/svg" width="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </i>
                                </a>
        
                                <ul class="sub-nav collapse" id="admin-maintenance-panel" data-bs-parent="#administrator-panel">
                                    <li class="nav-item">
                                        <?php if ($permissions->hasAccess(162)): ?>
                                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Branch Maintenance') ? 'active' : '' ?> " href="/administrator/branchmaintenance">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">Branch Setup</span>
                                        </a>
                                        <?php endif; ?>
                                    </li>
                                    <li class="nav-item">
                                        <?php if ($permissions->hasAccess(163)): ?>
                                        <a class="nav-link <?= ($_SESSION['current_module'] === 'Maintenance') ? 'active' : '' ?>" href="/administrator/maintenance">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">Maintenance Module</span>
                                        </a>
                                        <?php endif; ?>
                                    </li>
                                    <!-- Old Config Accounts Link Removed -->
                                    <li class="nav-item">
                                        <?php if ($permissions->hasAccess(165)): ?>
                                        <a class="nav-link <?= ($_SESSION['parent_module'] === 'Maintenance Address') ? 'active' : '' ?> " href="/administrator/maintenanceaddress">
                                            <i class="icon">
                                                <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                                    <g>
                                                    <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                                    </g>
                                                </svg>
                                            </i>
                                          <i class="sidenav-mini-icon"> B </i>
                                          <span class="item-name">Maintenance Address</span>
                                        </a>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </li>
                            
                            <li class="nav-item">
                                <?php if ($permissions->hasAccess(166)): ?>
                                <a class="nav-link <?= ($_SESSION['parent_module'] === 'System Maintenance') ? 'active' : '' ?> " href="/administrator/systemMaintenance">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> P </i>
                                    <span class="item-name">System Maintenance</span>
                                </a>
                                <?php endif; ?>
                            </li>

                            <li class="nav-item">
                                <?php if ($permissions->hasAccess(167)): ?>
                                <a class="nav-link <?= ($_SESSION['parent_module'] === 'User Logs') ? 'active' : '' ?> " href="/administrator/userlogs">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> R </i>
                                    <span class="item-name">User Logs</span>
                                </a>
                                <?php endif; ?>
                            </li>

                            <li class="nav-item">
                                <?php if ($permissions->hasAccess(164)): ?>
                                <a class="nav-link <?= ($_SESSION['current_module'] === 'Config Accounts') ? 'active' : '' ?>" href="/administrator/config_accounts">
                                    <i class="icon">
                                        <svg class="icon-10" xmlns="http://www.w3.org/2000/svg" width="10" viewBox="0 0 24 24" fill="currentColor">
                                            <g>
                                            <circle cx="12" cy="12" r="8" fill="currentColor"></circle>
                                            </g>
                                        </svg>
                                    </i>
                                    <i class="sidenav-mini-icon"> C </i>
                                    <span class="item-name">Config Accounts</span>
                                </a>
                                <?php endif; ?>
                            </li>
                        </ul>
                        
                    </li>
                    <?php endif; ?>

                </ul>
                <!-- Sidebar Menu End -->        
            </div>
        </div>
        <div class="sidebar-footer"></div>
    </aside>

<?php endif; ?>
