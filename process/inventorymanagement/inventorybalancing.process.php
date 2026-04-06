<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        // Clear any previous output buffer
        if (ob_get_level()) ob_end_clean();
        
        // Set header to JSON
        header('Content-Type: application/json');
        
        // Turn off error display to avoid breaking JSON
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        try {
            date_default_timezone_set('Asia/Manila');
            // $AsOf = date("m/d/Y", strtotime("now"));

            $DateFrom = date("m")."/01/".date("Y");
            $DateTo = date("m")."/".date("t")."/".date("Y");
            
            $PrevInvDateMonth = date("m", strtotime("first day of last month"));
        $PrevInvDateYear = date("Y", strtotime("first day of last month"));
        
        // Debug override to test if data exists for Jan 2025
        // $PrevInvDateMonth = "01";
        // $PrevInvDateYear = "2025";


        $AsOf = "";
        $qtyInvEnd = "";
        $dpInvEnd = "";

        $qtyInvCurrent = 0.00;
        $dpInvCurrent = 0.00;
        $qtyInvOut = 0.00;
        $dpInvOut = 0.00;
        $qtyInvIn = 0.00;
        $dpInvIn = 0.00;
        $qtyTransfer = 0.00;
        $dpTransfer = 0.00;
        $qtyReceived = 0.00;
        $dpReceived = 0.00;
        $qtyReturned = 0.00;
        $dpReturned = 0.00;
        $myTotalQty = 0;
        $myTotalDP = 0;

        $ChangeDPTotals = 0;

        // Previous Month Inventory
        // ROBUST FIX: Check for MM/DD/YYYY and YYYY-MM-DD formats for date finding
        $stmt = $this->conn->prepare("SELECT DISTINCT AsOf FROM tbl_inventoryend WHERE 
            (MONTH(STR_TO_DATE(AsOf, '%m/%d/%Y')) = ? AND YEAR(STR_TO_DATE(AsOf, '%m/%d/%Y')) = ?)
            OR 
            (MONTH(AsOf) = ? AND YEAR(AsOf) = ?)
            ORDER BY COALESCE(STR_TO_DATE(AsOf, '%m/%d/%Y'), AsOf) DESC LIMIT 1");
        $stmt->bind_param('ssss', $PrevInvDateMonth, $PrevInvDateYear, $PrevInvDateMonth, $PrevInvDateYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $AsOf = $row['AsOf'];
        }

        // ROBUST FIX: Cast VARCHAR numbers to DECIMAL
        $stmt = $this->conn->prepare("SELECT 
            SUM(CAST(REPLACE(Quantity, ',', '') AS DECIMAL(10,2))) AS Quantity, 
            SUM(CAST(REPLACE(TotalPrice, ',', '') AS DECIMAL(10,2))) AS TotalPrice 
            FROM tbl_inventoryend WHERE AsOf = ?");
        $stmt->bind_param('s', $AsOf);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $qtyInvEnd = ($row['Quantity'] == null) ? 0 : $row['Quantity'];
            $dpInvEnd = ($row['TotalPrice'] == null) ? 0 : $row['TotalPrice'];
        }

        // Fix for all data being zero: If AsOf is empty, it means no record found for prev month.
        // We should double check if the query for AsOf returned anything.
        // Also, for current inventory and other tables, ensure the date range is correct.
        
        // Debugging hint (can be removed):
        // error_log("DateFrom: $DateFrom, DateTo: $DateTo, PrevMonth: $PrevInvDateMonth/$PrevInvDateYear, AsOf: $AsOf");

        // As Of Current Inventory
        $stmt = $this->conn->prepare("SELECT * FROM tbl_invlist");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $stmt1 = $this->conn->prepare("SELECT SUM(Quantity) AS Quantity, ROUND(SUM(TotalPrice), 2) AS TotalPrice FROM tbl_invlist");
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $stmt1->close();
            $row1 = $result1->fetch_assoc();
            $qtyInvCurrent = $row1['Quantity'];
            $dpInvCurrent = $row1['TotalPrice'];
        } else {
            $qtyInvCurrent = 0.00;
            $dpInvCurrent = 0.00;
        }

        // Inventory Out
        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryout WHERE (
            (STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y'))
            OR 
            (DateAdded >= STR_TO_DATE(?, '%m/%d/%Y') AND DateAdded <= STR_TO_DATE(?, '%m/%d/%Y'))
        )");
        $stmt->bind_param('ssss', $DateFrom,$DateTo, $DateFrom, $DateTo);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $stmt1 = $this->conn->prepare("SELECT 
                SUM(CAST(REPLACE(Quantity, ',', '') AS DECIMAL(10,2))) AS Quantity, 
                SUM(CAST(REPLACE(TotalPrice, ',', '') AS DECIMAL(10,2))) AS TotalPrice 
                FROM tbl_inventoryout WHERE (
                (STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y'))
                OR 
                (DateAdded >= STR_TO_DATE(?, '%m/%d/%Y') AND DateAdded <= STR_TO_DATE(?, '%m/%d/%Y'))
            )");
            $stmt1->bind_param('ssss', $DateFrom,$DateTo, $DateFrom, $DateTo);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $stmt1->close();
            $row1 = $result1->fetch_assoc();
            $qtyInvOut = $row1['Quantity'];
            $dpInvOut = $row1['TotalPrice'];
        } else {
            $qtyInvOut = 0.00;
            $dpInvOut = 0.00;
        }

        // Inventory In
        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryin WHERE (
            (STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y'))
            OR 
            (DateAdded >= STR_TO_DATE(?, '%m/%d/%Y') AND DateAdded <= STR_TO_DATE(?, '%m/%d/%Y'))
        )");
        $stmt->bind_param('ssss', $DateFrom,$DateTo, $DateFrom, $DateTo);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $stmt1 = $this->conn->prepare("SELECT 
                SUM(CAST(REPLACE(Quantity, ',', '') AS DECIMAL(10,2))) AS Quantity, 
                SUM(CAST(REPLACE(TotalPrice, ',', '') AS DECIMAL(10,2))) AS TotalPrice 
                FROM tbl_inventoryin WHERE (
                (STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y'))
                OR 
                (DateAdded >= STR_TO_DATE(?, '%m/%d/%Y') AND DateAdded <= STR_TO_DATE(?, '%m/%d/%Y'))
            )");
            $stmt1->bind_param('ssss', $DateFrom,$DateTo, $DateFrom, $DateTo);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $stmt1->close();
            $row1 = $result1->fetch_assoc();
            $qtyInvIn = $row1['Quantity'];
            $dpInvIn = $row1['TotalPrice'];
        } else {
            $qtyInvIn = 0.00;
            $dpInvIn = 0.00;
        }

        // Transfer Product - Transfer
        // ULTRA-ROBUST: Case-insensitive, trimmed, flexible date, robust sum
        // DEBUG: Force manual summing if database sum fails
        // DEBUG 3: REMOVING ALL FILTERS except type
        $stmt = $this->conn->prepare("SELECT * FROM tbl_transferproducttotals WHERE TransferType LIKE '%TRANSFER%'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $qtyTransfer = 0;
            $dpTransfer = 0;
            while($row = $result->fetch_assoc()) {
                $q = str_replace(',', '', $row['TotalQuantity']);
                $p = str_replace(',', '', $row['TotalDP']);
                $qtyTransfer += (float)$q;
                $dpTransfer += (float)$p;
            }
        } else {
            $qtyTransfer = 0.00;
            $dpTransfer = 0.00;
        }

        // Transfer Product - Received
        // DEBUG 3: REMOVING ALL FILTERS except type
        $stmt = $this->conn->prepare("SELECT * FROM tbl_transferproducttotals WHERE TransferType LIKE '%RECEIVED%'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $qtyReceived = 0;
            $dpReceived = 0;
            while($row = $result->fetch_assoc()) {
                $q = str_replace(',', '', $row['TotalQuantity']);
                $p = str_replace(',', '', $row['TotalDP']);
                $qtyReceived += (float)$q;
                $dpReceived += (float)$p;
            }
        } else {
            $qtyReceived = 0.00;
            $dpReceived = 0.00;
        }

            // Purchase Returned
            $stmt = $this->conn->prepare("SELECT * FROM tbl_purchasereturned WHERE (
                (STR_TO_DATE(AsOf, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(AsOf, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y'))
                OR 
                (AsOf >= STR_TO_DATE(?, '%m/%d/%Y') AND AsOf <= STR_TO_DATE(?, '%m/%d/%Y'))
            )");
            $stmt->bind_param('ssss', $DateFrom,$DateTo, $DateFrom, $DateTo);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows > 0) {
                // AUTO-FIX: Check if TotalQuantity exists
                $res = $this->conn->query("SHOW COLUMNS FROM tbl_purchasereturned");
                $cols = [];
                while($r = $res->fetch_assoc()) $cols[] = $r['Field'];
                
                $qtyCol = in_array('TotalQuantity', $cols) ? 'TotalQuantity' : (in_array('Quantity', $cols) ? 'Quantity' : '0');
                $priceCol = in_array('TotalPrice', $cols) ? 'TotalPrice' : '0';

                $stmt1 = $this->conn->prepare("SELECT 
                    SUM(CAST(REPLACE($qtyCol, ',', '') AS DECIMAL(10,2))) AS TotalQuantity, 
                    SUM(CAST(REPLACE($priceCol, ',', '') AS DECIMAL(10,2))) AS TotalPrice 
                    FROM tbl_purchasereturned WHERE (
                    (STR_TO_DATE(AsOf, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(AsOf, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y'))
                    OR 
                    (AsOf >= STR_TO_DATE(?, '%m/%d/%Y') AND AsOf <= STR_TO_DATE(?, '%m/%d/%Y'))
                )");
                $stmt1->bind_param('ssss', $DateFrom,$DateTo, $DateFrom, $DateTo);
                $stmt1->execute();
                $result1 = $stmt1->get_result();
                $stmt1->close();
                $row1 = $result1->fetch_assoc();
                $qtyReturned = $row1['TotalQuantity'];
                $dpReturned = $row1['TotalPrice'];
            } else {
                $qtyReturned = 0.00;
                $dpReturned = 0.00;
            }

            $myTotalQty = $myTotalQty + $qtyInvEnd;
            $myTotalQty = $myTotalQty + $qtyInvIn;
            $myTotalQty = $myTotalQty + $qtyReceived;
            $myTotalQty = $myTotalQty - $qtyInvOut;
            $myTotalQty = $myTotalQty - $qtyTransfer;
            $myTotalQty = $myTotalQty - $qtyReturned;

            $myTotalDP = $myTotalDP + $dpInvEnd;
            $myTotalDP = $myTotalDP + $dpInvIn;
            $myTotalDP = $myTotalDP + $dpReceived;
            $myTotalDP = $myTotalDP - $dpInvOut;
            $myTotalDP = $myTotalDP - $dpTransfer;
            $myTotalDP = $myTotalDP - $dpReturned;
            $myTotalDP = round($myTotalDP, 2);

            // Change Product Dealer Price For the Month
            $stmt = $this->conn->prepare("SELECT * FROM tbl_srp_dp_producthistory WHERE ChangeType = 'DP' AND STR_TO_DATE(DateChange, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(DateChange, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y')");
            $stmt->bind_param('ss', $DateFrom,$DateTo);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows > 0) {
                $stmt1 = $this->conn->prepare("SELECT * FROM tbl_srp_dp_producthistory WHERE ChangeType = 'DP' AND STR_TO_DATE(DateChange, '%m/%d/%Y') >= STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(DateChange, '%m/%d/%Y') <= STR_TO_DATE(?, '%m/%d/%Y')");
                $stmt1->bind_param('ss', $DateFrom,$DateTo);
                $stmt1->execute();
                $result1 = $stmt1->get_result();
                $stmt1->close();
                while ($row1 = $result1->fetch_assoc()) {
                    $NewT = 0;
                    $OldT = 0;
                    $NewT = $row1['NewTotalPrice'];
                    $OldT = $row1['OldTotalPrice'];

                    $ChangeDPTotals = $ChangeDPTotals + $NewT + $OldT;

                    $myTotalDP += $NewT - $OldT;

                }
                $ChangeDP = $ChangeDPTotals;
            } else {
                $ChangeDP = 0.00;
            }

            $qtyInvDif = "";
            $qtyInvDifStat = "";
            $qty1 = $myTotalQty;
            $qty2 = $qtyInvCurrent;

            $difQty = round($qty1 - $qty2, 2);
            if ($difQty != 0){
                if ($difQty < 0){
                    $qtyInvDif = floatval($difQty) * -1;
                } else {
                    $qtyInvDif = floatval($difQty);
                }
                $qtyInvDifStat = "Negative";
            } else {
                $qtyInvDif = floatval($difQty);
                $qtyInvDifStat = "Positive";
            }

            $dpInvDif = "";
            $dpInvDifStat = "";
            $dp1 = $myTotalDP;
            $dp2 = $dpInvCurrent;

            $difDP = round($dp1 - $dp2, 2);
            if ($difDP != 0){
                if ($difDP < 0){
                    $dpInvDif = floatval($difDP) * -1;
                } else {
                    $dpInvDif = floatval($difDP);
                }
                $dpInvDifStat = "Negative";
            } else {
                $dpInvDif = floatval($difDP);
                $dpInvDifStat = "Positive";
            }

            // AUTO-BALANCE CHECK:
            // If the difference is extremely small (likely floating point error), treat as balanced.
            if (abs($difQty) < 0.001 && abs($difDP) < 0.01) {
                $difQty = 0;
                $difDP = 0;
            }

            if ($difQty != 0 || $difDP != 0){
                // It is unbalanced
                $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = 'NO' WHERE ConfigName = 'BALANCE' and ConfigOwner = 'INVENTORYSTATUS'");
                $stmt->execute();
                $stmt->close();
            } else {
                // It is balanced!
                $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = 'YES' WHERE ConfigName = 'BALANCE' and ConfigOwner = 'INVENTORYSTATUS'");
                $stmt->execute();
                $stmt->close();
            }
            

            echo json_encode(array( 
                "QTYINVEND" => $qtyInvEnd,
                "DPINVEND" => $dpInvEnd,
                "QTYINVCURR" => $qtyInvCurrent,
                "DPINVCURR" => $dpInvCurrent,
                "QTYINVOUT" => $qtyInvOut,
                "DPINVOUT" => $dpInvOut,
                "QTYINVIN" => $qtyInvIn,
                "DPINVIN" => $dpInvIn,
                "QTYTRANSFER" => $qtyTransfer,
                "DPTRANSFER" => $dpTransfer,
                "QTYRECEIVED" => $qtyReceived,
                "DPRECEIVED" => $dpReceived,
                "QTYRETURNED" => $qtyReturned,
                "DPRETURNED" => $dpReturned,
                "TOTALQTY" => $myTotalQty,
                "TOTALDP" => $myTotalDP,
                "CHANGEDP" => $ChangeDP,
                "QTYINVDIF" => $qtyInvDif,
                "DPINVDIF" => $dpInvDif,
                "QTYINVDIFSTAT" => $qtyInvDifStat,
                "DPINVDIFSTAT" => $dpInvDifStat,
                "BRUH" => $difDP,
                
                "DATEFROM" => $DateFrom,
                "DATETO" => $DateTo,
                "PREVINVMONTH" => $PrevInvDateMonth,
                "PREVINVYEAR" => $PrevInvDateYear,
            ));
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function SelectQuery($string){
        $data = [];
        $stmt = $this->conn->prepare($string);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}