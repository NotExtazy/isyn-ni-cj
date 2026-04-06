<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Test - Pending Releases</title>
    <style>
        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
        .test-box { background: #f0f0f0; padding: 20px; border: 2px solid #333; margin: 20px; }
    </style>
</head>
<body>
    <div class="test-box">
        <h1>Pending Releases Test Page</h1>
        <p>If you can see this, the basic page structure is working.</p>
        <p>Session Info:</p>
        <ul>
            <li>EMPNO: <?php echo $_SESSION['EMPNO'] ?? 'Not set'; ?></li>
            <li>USERNAME: <?php echo $_SESSION['USERNAME'] ?? 'Not set'; ?></li>
            <li>AUTHENTICATED: <?php echo $_SESSION['AUTHENTICATED'] ? 'Yes' : 'No'; ?></li>
        </ul>
    </div>
</body>
</html>
<?php
  } else {
    echo '<h1>Not Authenticated</h1>';
    echo '<p>Please <a href="/iSynApp-main/login.php">login</a> first.</p>';
  }
?>
