<?php
    // Start session properly
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Calculate base path - extract application folder from SCRIPT_NAME
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $parts = explode('/', trim($scriptName, '/'));
    
    // If we have at least one part, use the first part as base (e.g., 'iSynApp-main')
    if (count($parts) > 0 && $parts[0] !== '') {
        $base = '/' . $parts[0];
    } else {
        $base = '';
    }
    
    // Debug: Show base path calculation
    error_log("Login.php - Base path calculated: '$base'");
    error_log("Login.php - SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);
    
    // Check if already authenticated
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        $redirectUrl = $base . '/dashboard';
        error_log("Login.php - Redirecting to: $redirectUrl");
        echo '<script> window.location.href = "' . $redirectUrl . '"; </script>';
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
    
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">        
        <title>Login</title>

        <!-- Favicon -->
        <link rel="shortcut icon" href="<?php echo $base; ?>/assets/images/small-logo.png" />
        
        <!-- Library / Plugin Css Build -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/core/libs.min.css" />
        <!-- <link rel="stylesheet" href="assets/datatables/datatables.min.css" /> -->
        
        <!-- Aos Animation Css -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/vendor/aos/dist/aos.css" />
        
        <!-- Hope Ui Design System Css -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/hope-ui.min.css?v=2.0.0" />
        
        <!-- Custom Css -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/custom.min.css?v=2.0.0" />
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/custom.css" />
        
        <!-- Dark Css -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/dark.min.css"/>
        
        <!-- Customizer Css -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/customizer.min.css" />

        <!-- Bootstrap -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/bootstrap/css/bootstrap.min.css" />
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/fontawesome/css/all.min.css" />

        <!-- SweetAlert2 -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/sweetalert2/sweetalert2.min.css" />

        <!-- Custom Css -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/login.css">

        <!-- Set base path for JavaScript redirects -->
        <script>
            var basePath = '<?php echo $base; ?>';
            console.log('Base path set to:', basePath);
        </script>

    </head>

    <body class="bg-gray-200">
        <main class="main-content  mt-0">
            <div class="page-header align-items-start min-vh-100 bg-login d-flex">
                <span class="mask bg-gradient-dark opacity-6"></span>
                <div class="container my-auto">
                    <div class="row">

                        <div class="col-12 col-md-8 col-lg-4 mx-auto">
                            <div class="card z-index-0 fadeIn3 fadeInBottom">
                                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                                    <div class="bg-primary shadow rounded-3 py-5 pe-1 d-flex justify-content-center">
                                        <img src="<?php echo $base; ?>/assets/images/complete-logo.png" class="img-fluid logo-login" alt="">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form id="loginForm" method="POST">
                                        <div class="form-floating mb-3 inputBx">
                                            <input type="text" class="form-control" id="usernameField" placeholder="Username" name="usernameField">
                                            <label for="usernameField">Username</label>
                                            <span id="usernameError" class="error-message text-danger validation-info"></span>
                                        </div>
                                        
                                        <div class="form-floating inputBx mb-3">
                                            <input type="password" class="form-control" id="passwordField" placeholder="Password" name="passwordField">
                                            <label for="passwordField">Password</label>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 col-md-6">
                                                <div class="input-group">
                                                    <input type="checkbox" class="form-check-input" id="togglePassword">
                                                    <label class="form-check-label" for="togglePassword" style="margin-left: 5px;">
                                                        Show Password
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-12  col-md-6">
                                                <div class="forgot">
                                                    <a href="#" class="float-end text-decoration-none">Forgot Password?</a>
                                                </div>
                                            </div>
                                        </div>  

                                        <div class="d-grid gap-2 inputBx">
                                            <button type="submit" name="loginBtn" id="loginBtn" class="btn bg-primary text-white w-100 my-4 mb-2">Login</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Library Bundle Script -->
        <script src="<?php echo $base; ?>/assets/js/core/libs.min.js"></script>
        <!-- Bootstrap -->
        <script src="<?php echo $base; ?>/assets/bootstrap/js/bootstrap.min.js" defer></script>
        <!-- SweetAlert2 -->
        <script src="<?php echo $base; ?>/assets/sweetalert2/sweetalert2.min.js"></script>
        <script src="<?php echo $base; ?>/js/login.js"></script>
    </body>
</html>