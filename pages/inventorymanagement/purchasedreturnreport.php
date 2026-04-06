<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="../../assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="../../assets/select2/css/select2.min.css">

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

            <style>
                :root {
                    --primary-color: #0d6efd;
                    --bg-color: #f4f6f9;
                    --card-bg: #ffffff;
                    --text-color: #333;
                    --text-muted: #6c757d;
                    --border-color: #e9ecef;
                    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
                    --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
                    --radius-md: 10px;
                    --radius-lg: 15px;
                }

                body {
                    background-color: var(--bg-color);
                    color: var(--text-color);
                    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                }

                /* Card Styling */
                .custom-card {
                    background-color: var(--card-bg);
                    border-radius: var(--radius-lg);
                    box-shadow: var(--shadow-sm);
                    border: 1px solid rgba(0,0,0,0.02);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                }

                .custom-card:hover {
                    box-shadow: var(--shadow-md);
                }

                .card-header-title {
                    color: var(--primary-color);
                    font-weight: 600;
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 0;
                }

                .section-divider {
                    margin: 1.5rem 0;
                    border-top: 1px solid var(--border-color);
                    opacity: 0.5;
                }

                /* Form Elements */
                .form-label {
                    font-weight: 600;
                    font-size: 0.95rem;
                    color: var(--text-muted);
                    margin-bottom: 0.4rem;
                }

                .form-control {
                    border-radius: var(--radius-md);
                    border: 1px solid #dee2e6;
                    padding: 0.7rem 1rem;
                    font-size: 1rem;
                    color: var(--text-color) !important;
                    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                }

                .form-control:focus {
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
                }

                /* Buttons */
                .btn-primary {
                    background-color: var(--primary-color);
                    border-color: var(--primary-color);
                    padding: 0.7rem 1.5rem;
                    font-weight: 500;
                    border-radius: var(--radius-md);
                    transition: all 0.2s;
                    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
                }

                .btn-primary:hover {
                    background-color: #0b5ed7;
                    border-color: #0a58ca;
                    transform: translateY(-1px);
                    box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
                }

                /* Table Styling */
                .table-container {
                    border-radius: var(--radius-md);
                    overflow: hidden;
                    border: 1px solid var(--border-color);
                }

                .table {
                    margin-bottom: 0;
                    width: 100%;
                    border-collapse: separate;
                    border-spacing: 0;
                }

                .table th {
                    background-color: #f8f9fa;
                    color: #495057;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 0.8rem;
                    letter-spacing: 0.5px;
                    padding: 12px 16px;
                    border-bottom: 2px solid var(--border-color);
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }

                .table td {
                    padding: 12px 16px;
                    vertical-align: middle;
                    border-bottom: 1px solid var(--border-color);
                    font-size: 0.95rem;
                    color: var(--text-color);
                }

                .table-hover tbody tr:hover {
                    background-color: #f1f8ff;
                    cursor: pointer;
                }
            </style>

            <div class="container-fluid mt-4">
                <div class="custom-card p-3 mb-4">
                    <div class="card-header-title">
                        <i class="fa-solid fa-file-invoice fs-4"></i> <span class="fs-5">Purchased Return Report</span>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <div class="custom-card p-4 mb-4">
                            <form method="GET" class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="from-date" class="form-label">From Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-calendar text-muted"></i></span>
                                        <input type="date" id="from-date" name="from-date" class="form-control border-start-0 ps-0" value="<?php echo isset($_GET['from-date']) ? $_GET['from-date'] : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label for="to-date" class="form-label">To Date</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-calendar text-muted"></i></span>
                                        <input type="date" id="to-date" name="to-date" class="form-control border-start-0 ps-0" value="<?php echo isset($_GET['to-date']) ? $_GET['to-date'] : ''; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100" id="search-btn">
                                        <i class="fa-solid fa-magnifying-glass me-2"></i> Search
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php
                    include_once("../../database/connection.php");
                    $db = new Database();
                    $conn = $db->conn;

                    if (isset($_GET['from-date']) && isset($_GET['to-date'])) {
                        $fromDate = $_GET['from-date'];
                        $toDate = $_GET['to-date'];
                ?>
                <div class="custom-card p-4">
                    <h5 class="fw-bold text-dark mb-3">Report Results</h5>
                    <div class="table-container">
                        <table class="table table-hover" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transaction No</th>
                                    <th>Product</th>
                                    <th>SI No</th>
                                    <th>Serial No</th>
                                    <th>Quantity</th>
                                    <th>Reason</th>
                                    <th>Branch</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    $sql = "SELECT * FROM tbl_purchasereturned WHERE DATE(DateAdded) BETWEEN '$fromDate' AND '$toDate' ORDER BY DateAdded DESC";
                                    $result = $conn->query($sql);

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?php echo $row['DateAdded']; ?></td>
                                    <td><?php echo $row['TransactionNo']; ?></td>
                                    <td><?php echo $row['Product']; ?></td>
                                    <td><?php echo $row['SIno']; ?></td>
                                    <td><?php echo $row['Serialno']; ?></td>
                                    <td><?php echo $row['Quantity']; ?></td>
                                    <td><?php echo isset($row['Reason']) ? htmlspecialchars($row['Reason']) : ''; ?></td>
                                    <td><?php echo $row['Branch']; ?></td>
                                    <td><?php echo $row['User']; ?></td>
                                </tr>
                                <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center'>No records found</td></tr>";
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                    }
                ?>
            </div>

        
        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/generalledger/posting.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>

<script>
    // calendar function
    document.addEventListener('DOMContentLoaded', function() {
        const dateInputs = document.querySelectorAll('.form-control[type="date"]');

        dateInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                let value = input.value;
                let parts = value.split('-');
                if (parts.length === 3) {
                    let year = parts[0].substring(0, 4); // Limiting to 4 digits
                    let month = parts[1];
                    let day = parts[2];
                    input.value = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
                }
            });

            input.addEventListener('blur', function() {
                let value = input.value;
                let parts = value.split('-');
                if (parts.length === 3) {
                    let year = parts[0].substring(0, 4); // Limiting to 4 digits
                    let month = parts[1].padStart(2, '0');
                    let day = parts[2].padStart(2, '0');
                    input.value = `${year}-${month}-${day}`;
                }
            });
        });
    });
</script>