<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database {
    public function SearchPaidUnpaid($data){
        $from = isset($data['fromDate']) ? trim($data['fromDate']) : '';
        $to = isset($data['toDate']) ? trim($data['toDate']) : '';
        $isConsign = isset($data['isConsign']) ? $data['isConsign'] : 'No';
        $typeVal = isset($data['typeVal']) ? $data['typeVal'] : '1';
        $withSI = isset($data['withSI']) ? $data['withSI'] : 'No';
        
        // Map filters
        $status = ($typeVal == '1') ? 'PAID' : 'UNPAID';
        $consignFlag = ($isConsign === 'Yes') ? 'CONSIGNMENT' : 'NO';
        
        // Dates come as yyyy-mm-dd from input type='date'
        // If your DB is DATETIME but stored as 'mm/dd/yyyy hh:mm:ss' (varchar) or real DATETIME?
        // User says "date format is mm/dd/yyyy".
        // If DB column is true DATETIME, MySQL stores it as YYYY-MM-DD HH:MM:SS.
        // If DB column is VARCHAR storing "MM/DD/YYYY", then we need STR_TO_DATE.
        // But user earlier said "i change the varchar to datetime", implying it is now a native DATETIME column.
        
        // However, if the user means the INPUT from the HTML form is giving mm/dd/yyyy (unlikely for <input type="date"> which usually gives YYYY-MM-DD),
        // or if they mean they WANT to search using mm/dd/yyyy strings...
        
        // Let's assume the DB column is indeed DATETIME (YYYY-MM-DD HH:MM:SS).
        // The input $from and $to are usually YYYY-MM-DD from HTML5 date inputs.
        // We just need to ensure we pass YYYY-MM-DD HH:MM:SS to the query.
        
        // If the user means the *data* in the DB was imported as mm/dd/yyyy and then converted to datetime...
        // Let's stick to the standard DATETIME comparison which works for YYYY-MM-DD inputs.
        
        // BUT, if the user manually types mm/dd/yyyy into a text field (not date picker), then $from would be "mm/dd/yyyy".
        // In that case, we need to convert "mm/dd/yyyy" -> "YYYY-MM-DD" for the DB comparison if the DB is native DATETIME.
        
        $fromStr = $from ? $from : ''; // Input is YYYY
        $toStr = $to ? $to : '';     // Input is YYYY
        
        // --- NEW LOGIC: Compare Year 1 vs Year 2 independently ---
        // We are NOT doing a range.
        // We are fetching data for Year 1 AND Year 2 separately.
        
        $filters = [];
        $types = '';
        $params = [];
        
        // Base filters (Status, Consignment, SI) need to be applied to BOTH
        // But to make it efficient, we can query:
        // WHERE (Year = Y1 OR Year = Y2) AND (CommonFilters)
        
        $dateCondition = "";
        if ($fromStr !== '' && $toStr !== '') {
            $dateCondition = "(DATE_FORMAT(DateAdded, '%Y') = ? OR DATE_FORMAT(DateAdded, '%Y') = ?)";
            $types .= 'ss';
            $params[] = substr($fromStr, 0, 4);
            $params[] = substr($toStr, 0, 4);
        } else if ($fromStr !== '') {
            $dateCondition = "DATE_FORMAT(DateAdded, '%Y') = ?";
            $types .= 's';
            $params[] = substr($fromStr, 0, 4);
        } else if ($toStr !== '') {
            $dateCondition = "DATE_FORMAT(DateAdded, '%Y') = ?";
            $types .= 's';
            $params[] = substr($toStr, 0, 4);
        }
        
        if ($dateCondition !== "") {
            $filters[] = $dateCondition;
        }

        // Apply Common Filters
        $filters[] = "Status = ?";
        $types .= 's';
        $params[] = $status;
        $filters[] = "itemConsign = ?";
        $types .= 's';
        $params[] = $consignFlag;
        if ($withSI === 'Yes') {
            $filters[] = "SI IS NOT NULL AND SI <> '-'";
        }
        
        $where = count($filters) ? implode(' AND ', $filters) : '1=1';
        
        // --- FETCH ITEMS ---
        // We fetch all items that match EITHER month.
        // Frontend can split them if needed, but for "Items List" usually we show all matching.
        $items = [];
        // UPDATED: Added Quantity, TotalMarkup, AmountDue, Soldto to SELECT
        $sqlItems = "SELECT SI, DateAdded, Status, Branch, Product, DealerPrice, TotalPrice, VatSales, TotalSRP, Type, Quantity, TotalMarkup, AmountDue, Soldto FROM tbl_inventoryout WHERE ".$where." ORDER BY DateAdded DESC, SI";
        $stmt = $this->conn->prepare($sqlItems);
        if (count($params) > 0) {
            $refs = [];
            $refs[] = &$types;
            foreach ($params as $k => $v) { $refs[] = &$params[$k]; }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $items[] = $row; }
        $stmt->close();
        
        // --- SEPARATE ITEMS & CLIENTS ---
        $items1 = [];
        $items2 = [];
        $clientMap1 = [];
        $clientMap2 = [];
        
        $totals1 = ["TotalPrice"=>0,"TotalSRP"=>0,"TotalMarkup"=>0,"TotalQty"=>0];
        $totals2 = ["TotalPrice"=>0,"TotalSRP"=>0,"TotalMarkup"=>0,"TotalQty"=>0];

        foreach ($items as $row) {
            $date = $row['DateAdded'];
            $year = substr($date, 0, 4); // YYYY
            $cust = $row['Soldto'] ?? '-';
            
            // Numeric values
            // IMPORTANT: Remove ',' from strings before floatval() if they are formatted numbers (e.g. "1,234.56")
            $tp = floatval(str_replace(',', '', $row['TotalPrice'] ?? 0));
            $srp = floatval(str_replace(',', '', $row['TotalSRP'] ?? 0));
            $mk = floatval(str_replace(',', '', $row['TotalMarkup'] ?? 0));
            $qty = floatval(str_replace(',', '', $row['Quantity'] ?? 0));
            $due = floatval(str_replace(',', '', $row['AmountDue'] ?? 0));

            // Check if matches Comparison 1
            if ($fromStr && $year === $fromStr) {
                $items1[] = $row;
                
                // Totals
                $totals1["TotalPrice"] += $tp;
                $totals1["TotalSRP"] += $srp;
                $totals1["TotalMarkup"] += $mk;
                $totals1["TotalQty"] += $qty;
                
                // Client Map
                if (!isset($clientMap1[$cust])) $clientMap1[$cust] = ["amount"=>0, "qty"=>0];
                $clientMap1[$cust]["amount"] += $due;
                $clientMap1[$cust]["qty"] += $qty;
            }
            
            // Check if matches Comparison 2
            if ($toStr && $year === $toStr) {
                $items2[] = $row;
                
                // Totals
                $totals2["TotalPrice"] += $tp;
                $totals2["TotalSRP"] += $srp;
                $totals2["TotalMarkup"] += $mk;
                $totals2["TotalQty"] += $qty;
                
                // Client Map
                if (!isset($clientMap2[$cust])) $clientMap2[$cust] = ["amount"=>0, "qty"=>0];
                $clientMap2[$cust]["amount"] += $due;
                $clientMap2[$cust]["qty"] += $qty;
            }
        }
        
        // Convert Client Maps to Arrays and Sort
        $clients1 = [];
        foreach($clientMap1 as $c => $data) { $clients1[] = ["Customer"=>$c, "TotalPayables"=>$data["amount"], "TotalQty"=>$data["qty"]]; }
        usort($clients1, function($a, $b) { return $b['TotalPayables'] <=> $a['TotalPayables']; });
        
        $clients2 = [];
        foreach($clientMap2 as $c => $data) { $clients2[] = ["Customer"=>$c, "TotalPayables"=>$data["amount"], "TotalQty"=>$data["qty"]]; }
        usort($clients2, function($a, $b) { return $b['TotalPayables'] <=> $a['TotalPayables']; });
        
        // Return structured data
        echo json_encode([
            "items1" => $items1,
            "items2" => $items2,
            "totals1" => $totals1,
            "totals2" => $totals2,
            "clients1" => $clients1,
            "clients2" => $clients2
        ]);
    }

    public function GetItemDetails($data){
        $si = isset($data['si']) ? trim($data['si']) : '';
        $date = isset($data['date']) ? trim($data['date']) : '';
        $branch = isset($data['branch']) ? trim($data['branch']) : '';
        if ($si === ''){
            echo json_encode(["item"=>null]);
            return;
        }
        // Prefer matching by SI + Date + Branch when available
        $sql = "";
        $types = "";
        $params = [];
        if ($date !== '' && $branch !== ''){
            $sql = "SELECT * FROM tbl_inventoryout WHERE SI = ? AND DateAdded = ? AND Branch = ? LIMIT 1";
            $types = "sss";
            $params = [$si, $date, $branch];
        } else if ($date !== ''){
            $sql = "SELECT * FROM tbl_inventoryout WHERE SI = ? AND DateAdded = ? LIMIT 1";
            $types = "ss";
            $params = [$si, $date];
        } else {
            $sql = "SELECT * FROM tbl_inventoryout WHERE SI = ? ORDER BY STR_TO_DATE(DateAdded,'%m/%d/%Y') DESC LIMIT 1";
            $types = "s";
            $params = [$si];
        }
        $stmt = $this->conn->prepare($sql);
        $refs = [];
        $refs[] = &$types;
        foreach ($params as $k => $v) { $refs[] = &$params[$k]; }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $stmt->execute();
        $res = $stmt->get_result();
        $item = null;
        if ($row = $res->fetch_assoc()){
            $item = $row;
        }
        $stmt->close();
        echo json_encode(["item"=>$item]);
    }
    
    public function GetClientDetails($data){
        $customer = isset($data['customer']) ? trim($data['customer']) : '';
        $from = isset($data['fromDate']) ? trim($data['fromDate']) : '';
        $to = isset($data['toDate']) ? trim($data['toDate']) : '';
        $isConsign = isset($data['isConsign']) ? $data['isConsign'] : 'No';
        $typeVal = isset($data['typeVal']) ? $data['typeVal'] : '1';
        $withSI = isset($data['withSI']) ? $data['withSI'] : 'No';
        
        if ($customer === ''){
            echo json_encode(["items"=>[], "total"=>0]);
            return;
        }
        $status = ($typeVal == '1') ? 'PAID' : 'UNPAID';
        $consignFlag = ($isConsign === 'Yes') ? 'CONSIGNMENT' : 'NO';
        $fromStr = $from ? $from : ''; // Format: YYYY
        $toStr = $to ? $to : '';     // Format: YYYY
        
        // --- MATCHING THE "SINGLE YEAR" LOGIC FROM SearchPaidUnpaid ---
        // GetClientDetails is called with fromDate=YYYY and toDate=YYYY (same value) 
        // when clicking a client from the list.
        // We should use the same DATE_FORMAT logic to be consistent.
        
        $filters = ["Soldto = ?"];
        $types = "s";
        $params = [$customer];
        
        // If fromStr and toStr are the same (which they are for the modal click), treat as single year
        if ($fromStr !== '' && $fromStr === $toStr) {
             $filters[] = "DATE_FORMAT(DateAdded, '%Y') = ?";
             $types .= 's';
             $params[] = substr($fromStr, 0, 4);
        }
        // Fallback to range if different (unlikely for this specific usage but safe to keep)
        else if ($fromStr !== '' && $toStr !== '') {
             $filters[] = "(DATE_FORMAT(DateAdded, '%Y') = ? OR DATE_FORMAT(DateAdded, '%Y') = ?)";
             $types .= 'ss';
             $params[] = substr($fromStr, 0, 4);
             $params[] = substr($toStr, 0, 4);
        } 
        else if ($fromStr !== '') {
             $filters[] = "DATE_FORMAT(DateAdded, '%Y') = ?";
             $types .= 's';
             $params[] = substr($fromStr, 0, 4);
        } 
        else if ($toStr !== '') {
             $filters[] = "DATE_FORMAT(DateAdded, '%Y') = ?";
             $types .= 's';
             $params[] = substr($toStr, 0, 4);
        }

        $filters[] = "Status = ?";
        $types .= 's';
        $params[] = $status;
        $filters[] = "itemConsign = ?";
        $types .= 's';
        $params[] = $consignFlag;
        if ($withSI === 'Yes') {
            $filters[] = "SI IS NOT NULL AND SI <> '-'";
        }
        
        $where = implode(' AND ', $filters);
        
        $items = [];
        // Removed STR_TO_DATE for ORDER BY since DateAdded is DATETIME
        $sqlItems = "SELECT SI, DateAdded, Branch, Status, Product, Quantity, AmountDue, TotalPrice, TotalSRP, Type FROM tbl_inventoryout WHERE ".$where." ORDER BY DateAdded DESC, SI";
        $stmt = $this->conn->prepare($sqlItems);
        $refs = [];
        $refs[] = &$types;
        foreach ($params as $k => $v) { $refs[] = &$params[$k]; }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $items[] = $row; }
        $stmt->close();
        
        // Calculate Total manually to handle comma-formatted strings if any
        $total = 0;
        foreach ($items as $itm) {
             $total += floatval(str_replace(',', '', $itm['AmountDue'] ?? 0));
        }
        
        echo json_encode(["items"=>$items, "total"=>$total]);
    }
}
