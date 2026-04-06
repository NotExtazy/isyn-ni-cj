<?php
include_once("../../database/connection.php");

class AcashReports extends Database 
{
    public function PrintAcashReport($type){
        $rows = [];
        $title = "Acash Information - " . $type;
        
        if ($type == 'Custom') {
             $stmt = $this->conn->prepare("
                SELECT CDate, Branch, Fund, AcctNo, AcctTitle FROM tbl_acash_custom
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
             ");
        } elseif ($type == 'Raw') {
             $stmt = $this->conn->prepare("
                SELECT CDate, Branch, Fund, AcctNo, AcctTitle FROM tbl_acash_raw
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
             ");
        } else {
            // Main
            $stmt = $this->conn->prepare("
                SELECT CDate, Branch, Fund, AcctNo, AcctTitle 
                FROM tbl_books
                WHERE SLName = 'ACASH'
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
            ");
        }

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $stmt->close();
        }

        $this->GenerateHTMLReport($title, ['Date', 'Branch', 'Fund', 'Acct No', 'Acct Title'], $rows, ['CDate', 'Branch', 'Fund', 'AcctNo', 'AcctTitle']);
    }

    public function PrintEcpayReport($type){
        $rows = [];
        $title = "ECpay Transaction - " . $type;

        if ($type == 'Custom') {
             $stmt = $this->conn->prepare("
                SELECT CDate, Branch, Payee, Explanation, DrOther, CrOther FROM tbl_ecpay_custom
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
             ");
        } elseif ($type == 'Raw') {
             $stmt = $this->conn->prepare("
                SELECT CDate, Branch, Payee, Explanation, DrOther, CrOther FROM tbl_ecpay_raw
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
             ");
        } else {
            // Main
            $like = "%EC%PAY%";
            $like2 = "%ECPAY%";
            $stmt = $this->conn->prepare("
                SELECT CDate, Branch, Payee, Explanation, DrOther, CrOther 
                FROM tbl_books
                WHERE (Payee LIKE ? OR Explanation LIKE ? OR Explanation LIKE ?)
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
            ");
            $stmt->bind_param('sss', $like, $like, $like2);
        }

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                // Calculate amount logic similar to JS
                $amount = 0;
                if (!empty($row["DrOther"]) && $row["DrOther"] != 0) {
                    $amount = $row["DrOther"];
                } elseif (!empty($row["CrOther"]) && $row["CrOther"] != 0) {
                    $amount = $row["CrOther"];
                }
                $row['Amount'] = number_format((float)$amount, 2);
                $rows[] = $row;
            }
            $stmt->close();
        }

        $this->GenerateHTMLReport($title, ['Date', 'Branch', 'Payee', 'Explanation', 'Amount'], $rows, ['CDate', 'Branch', 'Payee', 'Explanation', 'Amount']);
    }

    private function GenerateHTMLReport($title, $headers, $data, $keys) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; text-align: center; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h2, .header h3 { margin: 5px 0; }
                .amount { text-align: right; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="no-print" style="margin-bottom: 20px;">
                <button onclick="window.print()">Print Report</button>
                <button onclick="window.close()">Close</button>
            </div>

            <div class="header">
                <h2>iSynergies</h2>
                <h3><?php echo htmlspecialchars($title); ?></h3>
                <p>Date Generated: <?php echo date("F d, Y"); ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <?php foreach($headers as $h): ?>
                            <th><?php echo htmlspecialchars($h); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($data) > 0) {
                        foreach ($data as $row) {
                            echo "<tr>";
                            foreach ($keys as $k) {
                                $val = isset($row[$k]) ? $row[$k] : '';
                                $class = ($k == 'Amount') ? 'class="amount"' : '';
                                echo "<td $class>" . htmlspecialchars($val) . "</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='" . count($headers) . "' style='text-align: center;'>No records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </body>
        </html>
        <?php
    }
}
?>