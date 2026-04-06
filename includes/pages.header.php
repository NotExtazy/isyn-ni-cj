<?php
// Guard removed for direct access
// Compute base URL path for assets when app runs from a subdirectory
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
// Find the position of '/pages' in the path to determine root
$pagesPos = strpos($scriptDir, '/pages');
if ($pagesPos !== false) {
    // We are inside /pages/, so base path is everything before it
    $BASE_PATH = substr($scriptDir, 0, $pagesPos);
} else {
    // Fallback if not in /pages/ (e.g. root script using this header?)
    $BASE_PATH = rtrim($scriptDir, '/\\');
}
if ($BASE_PATH === '.' || $BASE_PATH === '/') { $BASE_PATH = ''; }
?>
<head>
    <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <!-- <title>iSynergies Inc.</title> -->
      
      <!-- Favicon -->
      <link rel="shortcut icon" href="<?php echo $BASE_PATH; ?>/assets/images/small-logo.png" />
      
      <!-- Library / Plugin Css Build -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/css/core/libs.min.css" />
      <!-- <link rel="stylesheet" href="../../assets/datatables/datatables.min.css" /> -->
      
      <!-- Aos Animation Css -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/vendor/aos/dist/aos.css" />
      
      <!-- Hope Ui Design System Css -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/css/hope-ui.min.css?v=2.0.0" />
      
      <!-- Custom Css -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/css/custom.min.css?v=2.0.0" />
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/css/custom.css" />
      
      <!-- Dark Css -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/css/dark.min.css"/>
      
      <!-- Customizer Css -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/css/customizer.min.css" />
      
      <!-- RTL Css -->
      <!-- <link rel="stylesheet" href="../../assets/css/rtl.min.css"/> -->

      <!-- Bootstrap -->
      <!-- <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css" /> -->
      
      <!-- Font Awesome -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/fontawesome/css/all.min.css" />

      <!-- SweetAlert2 -->
      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/sweetalert2/sweetalert2.min.css" />

      <link rel="stylesheet" href="<?php echo $BASE_PATH; ?>/assets/datetimepicker/jquery.datetimepicker.css">
    </head>
    <?php
      // Use ROUTED_PATH from router.php if available, otherwise fallback to SCRIPT_NAME
      $currentPath = defined('ROUTED_PATH') ? ROUTED_PATH : $_SERVER['SCRIPT_NAME'];
      $urlParts = explode('/', ltrim($currentPath, '/'));
      
      // Get the parent folder or module
      if (count($urlParts) >= 2) {
          $parentModule = $urlParts[count($urlParts) - 2];
          $page = $urlParts[count($urlParts) - 1];
      } else {
          $parentModule = 'Dashboard';
          $page = !empty($urlParts[0]) ? $urlParts[0] : 'index';
      }

      $parentModuleFormatted = ucwords(str_replace('-', ' ', strtolower($parentModule)));
      $module = ucwords(str_replace(['-', '.php'], ' ', strtolower($page)));

      $_SESSION['parent_module'] = $parentModuleFormatted;
      $_SESSION['current_module'] = $module;
    ?>
    <script>
        window.BASE_PATH = "<?php echo $BASE_PATH; ?>";
        const CSRF_TOKEN = "<?php echo $_SESSION['csrf_token'] ?? ''; ?>";

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
    <style>
        .sidebar span {
            font-size: 11px;
        }
    </style>
