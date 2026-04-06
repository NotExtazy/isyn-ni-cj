<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        
        include_once("../../database/connection.php");
        $db = new Database();
        $conn = $db->conn;

        $transactionNo = $_GET['transactionNo'] ?? '';

        if (empty($transactionNo)) {
            die("Invalid Transaction Number");
        }

        // Fetch Transaction Details
        $sql = "SELECT * FROM tbl_purchasereturned WHERE TransactionNo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $transactionNo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        $header = null;
        while($row = $result->fetch_assoc()) {
            if (!$header) $header = $row;
            $items[] = $row;
        }
        $stmt->close();

        if (empty($items)) {
            die("Transaction not found.");
        }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Receipt - <?php echo $transactionNo; ?></title>
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Purchase Return Receipt</h2>
            <p><strong>Transaction No:</strong> <?php echo $transactionNo; ?></p>
            <p><strong>Date:</strong> <?php echo $header['DateAdded']; ?></p>
            <p><strong>Processed By:</strong> <?php echo $header['User']; ?></p>
        </div>

        <div class="details">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SI No.</th>
                        <th>Serial No.</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['Product']); ?></td>
                        <td><?php echo htmlspecialchars($item['SIno']); ?></td>
                        <td><?php echo htmlspecialchars($item['Serialno']); ?></td>
                        <td><?php echo htmlspecialchars($item['TransactionType']); ?></td>
                        <td><?php echo htmlspecialchars($item['Quantity']); ?></td>
                        <td><?php echo htmlspecialchars($item['TransactionType']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="footer text-center mt-5">
            <p>__________________________</p>
            <p>Authorized Signature</p>
        </div>

        <div class="text-center no-print mt-4">
            <button onclick="window.print()" class="btn btn-primary">Print</button>
            <button onclick="window.close()" class="btn btn-secondary">Close</button>
        </div>
    </div>
</body>
</html>
<?php
    } else {
        echo "Unauthorized Access";
    }
?>
