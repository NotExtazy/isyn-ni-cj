    <?php
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        // Find the position of '/pages' in the path to determine root
        $pagesPos = strpos($scriptDir, '/pages');
        if ($pagesPos !== false) {
            $BASE_PATH = substr($scriptDir, 0, $pagesPos);
        } else {
            $BASE_PATH = rtrim($scriptDir, '/\\');
        }
        if ($BASE_PATH === '.' || $BASE_PATH === '/') { $BASE_PATH = ''; }
    ?>
    <main class="main-content">
      <div class="position-relative iq-banner">
        <!--Nav Start-->
        <nav class="nav navbar navbar-expand-lg navbar-light iq-navbar" style="background: rgb(81,125,235);
background: linear-gradient(90deg, rgba(81,125,235,1) 24%, rgba(101,226,237,1) 67%);
">
          <div class="container-fluid navbar-inner">
            <a href="<?php echo $BASE_PATH; ?>/dashboard" class="navbar-brand">                
                <!--Logo start-->
                <div class="logo-main">
                    <img src="<?php echo $BASE_PATH; ?>/assets/images/complete-logo.png" alt="isynergies logo" width="150vh">
                </div>
                <!--logo End-->
            </a>
            <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
                <i class="icon">
                 <svg  width="20px" class="icon-20" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
                </svg>
                </i>
            </div>
            <button class="navbar-toggler px-2 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">
                    <span class="navbar-toggler-bar bar1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
              <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
                <li class="nav-item dropdown">
                  <a class="py-0 nav-link d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?php echo $BASE_PATH; ?>/assets/images/avatars/01.png" alt="User-Profile" class="theme-color-default-img img-fluid avatar avatar-50 avatar-rounded">
                    <div class="caption ms-3 d-none d-md-block ">
                        <h6 class="mb-0 caption-title"><?= $_SESSION['USERNAME'];?></h6>
                        <!-- <p class="mb-0 caption-sub-title">Marketing Administrator</p> -->
                    </div>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="/dashboard/app/user-profile.html">Profile</a></li>
                    <li><a class="dropdown-item" href="/dashboard/app/user-privacy-setting.html">Privacy Setting</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#Logout" onclick="logout('other');">Logout</a></li>
                  </ul>
                </li>
              </ul>
            </div>
          </div>
        </nav>

        
        <!--Nav End-->
      </div>
