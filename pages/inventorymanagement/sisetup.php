<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        
    include(dirname(__DIR__, 2) . '/includes/pages.header.php');
    require_once(dirname(__DIR__, 2) . '/database/connection.php');
    $connection = (new Database())->conn;

    $username = $_SESSION['USERNAME'];

    function fetchSI($connection, $username) {
        // Check if table exists first to avoid error if not yet initialized
        $checkTable = $connection->query("SHOW TABLES LIKE 'tbl_sinumber'");
        if ($checkTable->num_rows == 0) {
            return 1;
        }

        $stmt = $connection->prepare("SELECT SIcount FROM tbl_sinumber WHERE user = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $current_si = intval($row["SIcount"]);
            // Return next SI number, ensuring it's at least 1
            return max($current_si + 1, 1);
        }
        return 1;
    }

    $new_si = fetchSI($connection, $username);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inputSI'])) {
        $input_si = intval($_POST['inputSI']);
        
        // Validate input
        if ($input_si < 1) {
            echo "<script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
            });
            Toast.fire({
                icon: 'error',
                title: 'SI number must be greater than 0'
            });
            </script>";
            exit;
        }
        
        // Save the exact input value (NOT input - 1)
        $si_to_save = $input_si;

        // Ensure table exists
        $connection->query("
            CREATE TABLE IF NOT EXISTS tbl_sinumber (
                user VARCHAR(100) NOT NULL,
                SIcount INT NOT NULL DEFAULT 0,
                PRIMARY KEY (user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Use transaction for data integrity
        $connection->begin_transaction();
        
        try {
            $check = $connection->prepare("SELECT user FROM tbl_sinumber WHERE user = ?");
            $check->bind_param('s', $username);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $stmt = $connection->prepare("UPDATE tbl_sinumber SET SIcount=? WHERE user=?");
                $stmt->bind_param('is', $si_to_save, $username);
                $stmt->execute();
            } else {
                $stmt = $connection->prepare("INSERT INTO tbl_sinumber (user, SIcount) VALUES (?, ?)");
                $stmt->bind_param('si', $username, $si_to_save);
                $stmt->execute();
            }
            
            $connection->commit();
            
            $new_si = $input_si;

            if (true) {
                echo "<script>
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: 'success',
                    title: 'SI number updated successfully'
                });
                </script>";
            }
        } catch (Exception $e) {
            $connection->rollback();
            echo "<script>
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: 'error',
                    title: 'Database error: " . addslashes($e->getMessage()) . "'
                });
                </script>";
        }
    }
?>

    <!-- Add Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
        text-align: center;
    }

    .form-control {
        background-color: var(--input-bg);
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem 1.25rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        transition: all 0.2s ease;
        text-align: center;
    }
    
    .form-control:focus {
        background-color: #fff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
    }

    .form-control:disabled {
        background-color: #e9ecef;
        opacity: 1;
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

    .btn-success-action {
        background-color: var(--success-color);
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
    }
    
    .btn-success-action:hover {
        background-color: #157347;
    }

    .btn-danger-action {
        background-color: var(--danger-color);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-danger-action:hover {
        background-color: #bb2d3b;
    }
    
    .header-icon {
        font-size: 4rem;
        color: rgba(255,255,255,0.2);
        margin-bottom: 1rem;
    }
</style>

<div class="container-fluid main-container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7 col-sm-10">
            <div class="card-modern">
                <div class="card-header-modern">
                    <div class="mb-3">
                        <i class="fa-solid fa-arrow-sort-numeric-up-alt header-icon"></i>
                    </div>
                    <h1 class="card-title-modern">SI Setup</h1>
                    <p class="card-subtitle-modern">Configure your Sales Invoice numbering sequence</p>
                </div>
                <div class="card-body-modern">
                    <form method="post" class="needs-validation">
                        <div class="mb-4">
                            <label class="form-label" for="inputSI">Current / Next SI Number</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="inputSI" name="inputSI" value="<?php echo $new_si; ?>" required disabled>
                            </div>
                            <div class="form-text mt-2 text-center">
                                <i class="fa-solid fa-circle-info me-1"></i> This number will be used for the next Sales Invoice.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-action" type="button" id="editButton" onclick="edit()">
                                <i class="fa-solid fa-pen-to-square"></i> Edit Configuration
                            </button>
                            
                            <div class="row g-2" id="actionButtons" style="display: none;">
                                <div class="col-6">
                                    <button class="btn btn-action btn-danger-action" type="button" id="cancelButton" onclick="cancel()">
                                        <i class="fa-solid fa-ban"></i> Cancel
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-action btn-success-action" type="button" id="submitButton" onclick="submitSI()">
                                        <i class="fa-solid fa-floppy-disk"></i> Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let originalValue;

    function edit() {
        const inputSI = document.getElementById("inputSI");
        originalValue = inputSI.value;
        inputSI.disabled = false;
        inputSI.focus();
        
        // Hide edit button, show action buttons
        document.getElementById("editButton").style.display = "none";
        document.getElementById("actionButtons").style.display = "flex";
    }

    function cancel() {
        const inputSI = document.getElementById("inputSI");
        inputSI.value = originalValue;
        inputSI.disabled = true;
        
        // Show edit button, hide action buttons
        document.getElementById("editButton").style.display = "flex";
        document.getElementById("actionButtons").style.display = "none";
    }

    function submitSI() {
        const inputSI = document.getElementById("inputSI");
        const v = inputSI.value.trim();
        if (!/^\d+$/.test(v)) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
            });
            Toast.fire({
                icon: 'error',
                title: 'Enter a valid integer'
            });
            return;
        }
        document.querySelector('form').submit();
    }
</script>

<?php
include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
?>
<?php
  } else {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $pagesPos = strpos($scriptDir, '/pages');
    if ($pagesPos !== false) {
        $base = substr($scriptDir, 0, $pagesPos);
    } else {
        $base = rtrim($scriptDir, '/\\');
    }
    if ($base === '.' ) { $base = ''; }
    echo '<script> window.location.href = "' . $base . '/login.php"; </script>';
  }
?>