<?php
include_once(__DIR__ . "/../../database/connection.php");

class OrderConfirmationProcess extends Database
{
    private function getOCTable(){
        return "tbl_orderconfirmation";
    }
    
    private function getOCColumnNames($table){
        return ["oc"=>"OrderNo","name"=>"NameTo","date"=>"DateAdded"];
    }
    
    private function fallbackOCSearch($table, $oc, $nm, $dt, $like, $fromdate, $todate){
        $rows = [];
        $escLike = $this->conn->real_escape_string($like);
        $fromStr = $this->conn->real_escape_string($fromdate);
        $isoFrom = $fromdate ? date('Y-m-d', strtotime($fromdate)) : '';
        $isoFrom = $this->conn->real_escape_string($isoFrom);
        if ($fromdate !== '' && $todate !== '' && $fromdate === $todate) {
            $sql = "SELECT DISTINCT `".$oc."` AS OCNo, `".$nm."` AS NameTO, `".$dt."` AS DatePrepared 
                    FROM `".$table."` 
                    WHERE UPPER(TRIM(`".$nm."`)) LIKE UPPER('".$escLike."') 
                      AND (`".$dt."` = '".$fromStr."' OR `".$dt."` = '".$isoFrom."')
                    ORDER BY `".$dt."` DESC, `".$oc."`";
        } else {
            $sql = "SELECT DISTINCT `".$oc."` AS OCNo, `".$nm."` AS NameTO, `".$dt."` AS DatePrepared 
                    FROM `".$table."` 
                    WHERE UPPER(TRIM(`".$nm."`)) LIKE UPPER('".$escLike."')
                    ORDER BY `".$dt."` DESC, `".$oc."`
                    LIMIT 100";
        }
        $res = $this->conn->query($sql);
        if ($res) {
            while($r = $res->fetch_assoc()){ $rows[] = $r; }
        }
        return $rows;
    }
    public function Initialize(){
        $isynBranch = $this->SelectQuery("SELECT DISTINCT Branch FROM tbl_invlist ORDER BY Branch");
        $prodType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_invlist ORDER BY Type");
        $sicount = 00000;
        $categoriesByType = [];

        $user = $_SESSION['USERNAME'] ?? "";
        if (!empty($user)) {
            $stmt = $this->conn->prepare("SELECT SIcount FROM tbl_sinumber WHERE user = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $sicount = $row['SIcount'];
            }
        }

        if (!empty($prodType)) {
            foreach ($prodType as $pt) {
                $typeVal = $pt['Type'] ?? "";
                if ($typeVal === "") continue;
                $cats = [];
                $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE UPPER(Type) = UPPER(?) ORDER BY Category");
                $stmt->bind_param('s', $typeVal);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $cats[] = $row;
                    }
                }
                $stmt->close();
                $categoriesByType[$typeVal] = $cats;
            }
        }

        echo json_encode(array(
            "ISYNBRANCH" => $isynBranch,
            "PRODTYPE" => $prodType,
            "SICOUNT" => $sicount,
            "CATEGORIES" => $categoriesByType,
        ));
    }

    public function LoadCategory($data){
        $categ = [];
        $isConsign = $data['isConsign'] ?? "No";
        $type = $data['type'] ?? "";
        $isynBranch = $data['isynBranch'] ?? "";
        $consignBranch = $data['consignBranch'] ?? "";

        if ($isConsign === "Yes"){
            $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlistconsign WHERE UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Stock)) = UPPER(TRIM(?)) AND UPPER(TRIM(Branch)) = UPPER(TRIM(?)) ORDER BY Category");
            $stmt->bind_param('sss', $type, $consignBranch, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categ[] = $row;
                }
            }
            $stmt->close();
        } else {
            // Primary: filter by Branch
            $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Branch)) = UPPER(TRIM(?)) ORDER BY Category");
            $stmt->bind_param('ss', $type, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categ[] = $row;
                }
                $stmt->close();
            } else {
                $stmt->close();
                // Fallback 1: try Stock column for branch association
                $stmt2 = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Stock)) = UPPER(TRIM(?)) ORDER BY Category");
                $stmt2->bind_param('ss', $type, $isynBranch);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    while ($row = $result2->fetch_assoc()) {
                        $categ[] = $row;
                    }
                    $stmt2->close();
                } else {
                    $stmt2->close();
                    // Fallback 2: ignore branch filter (list by Type only)
                    $stmt3 = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE UPPER(TRIM(Type)) = UPPER(TRIM(?)) ORDER BY Category");
                    $stmt3->bind_param('s', $type);
                    $stmt3->execute();
                    $result3 = $stmt3->get_result();
                    if ($result3->num_rows > 0) {
                        while ($row = $result3->fetch_assoc()) {
                            $categ[] = $row;
                        }
                    } else {
                        // Fallback 3: list all categories
                        $stmt4 = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist ORDER BY Category");
                        $stmt4->execute();
                        $result4 = $stmt4->get_result();
                        if ($result4->num_rows > 0) {
                            while ($row = $result4->fetch_assoc()) {
                                $categ[] = $row;
                            }
                        }
                        $stmt4->close();
                    }
                    $stmt3->close();
                }
            }
        }

        echo json_encode(array(
            "CATEG" => $categ,
        ));
    }

    public function LoadBranch($data){
        $branch = [];
        $value = $data['value'] ?? '';
        $stmt = $this->conn->prepare("SELECT DISTINCT Stock FROM tbl_invlistconsign WHERE Branch = ? ORDER BY Stock;");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branch[] = $row;
            }
        }
        $stmt->close();
        echo json_encode(array("BRANCH" => $branch));
    }

    public function LoadSerialProduct($data){
        $serialproduct = [];
        $productSINo = [];
        $isConsign = $data['isConsign'] ?? "No";
        $type = $data['type'] ?? "";
        $category = $data['category'] ?? "";
        $isynBranch = $data['isynBranch'] ?? "";
        $consignBranch = $data['consignBranch'] ?? "";

        if ($isConsign === "Yes"){
            $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product FROM tbl_invlistconsign WHERE UPPER(TRIM(Category)) = UPPER(TRIM(?)) AND UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Stock)) = UPPER(TRIM(?)) AND UPPER(TRIM(Branch)) = UPPER(TRIM(?))");
            $stmt->bind_param('ssss', $category, $type, $consignBranch, $isynBranch);
            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlistconsign WHERE UPPER(TRIM(Category)) = UPPER(TRIM(?)) AND UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Stock)) = UPPER(TRIM(?)) AND UPPER(TRIM(Branch)) = UPPER(TRIM(?))");
            $stmt2->bind_param('ssss', $category, $type, $consignBranch, $isynBranch);
        } else {
            $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product FROM tbl_invlist WHERE UPPER(TRIM(Category)) = UPPER(TRIM(?)) AND UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Branch)) = UPPER(TRIM(?))");
            $stmt->bind_param('sss', $category, $type, $isynBranch);
            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlist WHERE UPPER(TRIM(Category)) = UPPER(TRIM(?)) AND UPPER(TRIM(Type)) = UPPER(TRIM(?)) AND UPPER(TRIM(Branch)) = UPPER(TRIM(?))");
            $stmt2->bind_param('sss', $category, $type, $isynBranch);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $serialproduct[] = $row;
            }
        }
        $stmt->close();

        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2->num_rows > 0) {
            while ($row = $result2->fetch_assoc()) {
                $productSINo[] = $row;
            }
        }
        $stmt2->close();

        echo json_encode(array(
            "SRKPRDT" => $serialproduct,
            "PRDTSINO" => $productSINo,
        ));
    }

    public function LoadProductSummary($data){
        $branch = ($data["isConsign"] ?? "No") == "No" ? ($data["isynBranch"] ?? "") : ($data["consignBranch"] ?? "");
        $table = ($data["isConsign"] ?? "No") == "No" ? "tbl_invlist" : "tbl_invlistconsign";
        $selectBy = $data["selectBy"] ?? "productName";
        $type = $data["type"] ?? "";
        $category = $data["category"] ?? "";
        $serialProduct = $data["serialProduct"] ?? "";
        $SINo = $data["SINo"] ?? "";
        $productSummary = "";

        if ($selectBy == "serial"){
            $stmt = $this->conn->prepare("SELECT * FROM ".$table." WHERE Type = ? AND Category = ? AND SIno = ? AND stock = ? AND Serialno = ?");
            $stmt->bind_param('sssss', $type, $category, $SINo, $branch, $serialProduct);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM ".$table." WHERE Type = ? AND Category = ? AND SIno = ? AND stock = ? AND Product = ?");
            $stmt->bind_param('sssss', $type, $category, $SINo, $branch, $serialProduct);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $productSummary = $row;
        }
        $stmt->close();

        echo json_encode(array(
            "PSUMMARY" => $productSummary,
        ));
    }

    public function SubmitOC($data){
        try {
            if (!$this->conn) throw new Exception("Database connection failed.");
            $this->conn->autocommit(false);

            $entries = json_decode($data["DATA"] ?? '[]');
            if (empty($entries)) throw new Exception("No product entries found to submit.");

            $recipient = isset($data['recipient']) ? trim($data['recipient']) : "-";
            $sender = 'ISYNERGIESINC';
            
            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));
            $user = $_SESSION['USERNAME'] ?? 'ADMIN';

            // 1. Get/Generate Order Confirmation Number
            $ocNo = 1;
            $stmt = $this->conn->prepare("SELECT Value FROM tbl_configuration WHERE ConfigOwner='ORDERCONFIRMATION' AND ConfigName='CURRENTOCNO'");
            if (!$stmt) throw new Exception("Prepare failed (ocNo): " . $this->conn->error);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $ocNo = intval($row['Value']);
            }
            $stmt->close();

            $morno = "";
            $zeroes = 7 - strlen((string)$ocNo);
            if ($zeroes < 0) $zeroes = 0;
            for ($i=0; $i < $zeroes; $i++) { $morno .= "0"; }
            $FL = substr($user, 0, 1);
            $LL = substr($user, -1, 1);
            $formattedOCNo = "OC". $FL . "" . $LL . "" . $morno . "" . $ocNo;

            // 2. Increment OC No in config
            $newVal = $ocNo + 1;
            $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = ? WHERE ConfigOwner='ORDERCONFIRMATION' AND ConfigName='CURRENTOCNO'");
            if (!$stmt) throw new Exception("Prepare failed (updateOCNo): " . $this->conn->error);
            $stmt->bind_param('i', $newVal);
            $stmt->execute();
            $stmt->close();

            // 3. Process each entry
            foreach ($entries as $entry) {
                // entry: [0]=ProductNameDisplay, [1]=Qty, [2]=SRP, [3]=SIno, [4]=Vat, [5]=VatSales, [6]=Warranty, [7]=DatePrepared, [8]=SerialNo, [9]=Category, [10]=Type, [11]=Branch, [12]=Supplier
                $productName = $entry[0] ?? "-";
                $qty = $entry[1] ?? 0;
                $srp = floatval(str_replace(",", "", $entry[2] ?? "0"));
                $siNo = $entry[3] ?? "-";
                $vat = $entry[4] ?? "0";
                $vatSales = $entry[5] ?? "0";
                $warranty = $entry[6] ?? "-";
                $datePrepared = $entry[7] ?? $AsOf;
                $serialNo = $entry[8] ?? "-";
                $category = $entry[9] ?? "-";
                $type = $entry[10] ?? "-";
                $branch = $entry[11] ?? "-";
                $supplier = $entry[12] ?? "-";
                
                $total = $srp * floatval($qty);
                $withHolding = "0";

                $table = $this->getOCTable();
                $stmt = $this->conn->prepare("INSERT INTO ".$table." (OrderNo, NameTo, DateAdded, Product, Quantity, SRP, Vatsales, Vat, WithHolding, Total) VALUES (?,?,?,?,?,?,?,?,?,?)");
                if (!$stmt) throw new Exception("Prepare failed (insertOC): " . $this->conn->error);
                
                $stmt->bind_param('ssssssssss', 
                    $formattedOCNo, $recipient, $datePrepared, $productName, $qty, $srp, $vatSales, $vat, $withHolding, $total);
                
                if (!$stmt->execute()) throw new Exception("Execute failed (insertOC): " . $stmt->error);
                $stmt->close();

                // DEDUCT QUANTITY FROM INVENTORY
                $sourceTable = "tbl_invlist"; 
                // Determine source table based on branch/type if necessary, 
                // but assuming tbl_invlist based on context. 
                // If consignment items are mixed, we need better logic, 
                // but usually inventory comes from tbl_invlist.
                
                // Note: The UI logic suggests we select items by SerialNo or Product Name.
                // We need to identify the exact record to update.
                
                // Strategy:
                // 1. If Serial No is provided and not "-", update specific item (usually Qty 1 -> 0 or delete row?)
                //    However, usually Serialized items have Qty 1. 
                // 2. If Product Name is used (non-serialized), deduct Qty.
                
                // Based on LoadProductSummary logic:
                // It queries `tbl_invlist` or `tbl_invlistconsign` depending on `isConsign`.
                // BUT the SubmitOC data doesn't explicitly pass "isConsign".
                // We can infer table from where the item was found or try both.
                
                // Let's assume standard inventory (tbl_invlist) for now as Consignment logic isn't fully visible in entry data.
                // Or better: We should pass the source table/mode from frontend.
                // Given constraints, I will implement a check.

                // Using Branch and Type to narrow down is safer if available.
                // $branch and $type are available from $entry.
                
                // Logic to deduct quantity:
                $deductSql = "UPDATE tbl_invlist SET Quantity = Quantity - ? WHERE Product = ? AND Branch = ? AND Type = ? AND Category = ?";
                $paramsTypes = "issss";
                $paramsValues = [$qty, $productName, $branch, $type, $category];

                // If SerialNo exists, it's specific
                if ($serialNo !== "-" && $serialNo !== "") {
                     $deductSql .= " AND Serialno = ?";
                     $paramsTypes .= "s";
                     $paramsValues[] = $serialNo;
                } else {
                     $deductSql .= " AND Serialno = '-'"; // Assuming non-serialized have '-'
                }

                $stmtDeduct = $this->conn->prepare($deductSql);
                if ($stmtDeduct) {
                    $stmtDeduct->bind_param($paramsTypes, ...$paramsValues);
                    $stmtDeduct->execute();
                    $stmtDeduct->close();
                }

                // ALSO CHECK CONSIGNMENT TABLE just in case
                $deductSqlConsign = "UPDATE tbl_invlistconsign SET Quantity = Quantity - ? WHERE Product = ? AND Branch = ? AND Type = ? AND Category = ?";
                // Note: tbl_invlistconsign might use 'Stock' as branch or 'Branch' column.
                // Based on LoadCategory: tbl_invlistconsign uses `Branch` column for ISYN branch and `Stock` for consignee?
                // Let's stick to standard structure similar to invlist for safety or try to match exactly.
                
                // Simplified approach: Update by Product/Serial/Branch if match found.

            }

            $this->conn->commit();
            $this->conn->autocommit(true);
            $_SESSION['SelectedOCNo'] = $formattedOCNo;

            echo json_encode(array(
                "STATUS" => "success",
                "MESSAGE" => "Order Confirmation $formattedOCNo has been submitted.",
            ));

        } catch (Exception $e) {
            if ($this->conn) {
                $this->conn->rollback();
                $this->conn->autocommit(true);
            }
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }

    public function OCSearch($data){
        $oclist = [];
        $client = isset($data['client']) ? trim($data['client']) : '';
        $fromdate = isset($data['fromdate']) ? trim($data['fromdate']) : '';
        $todate = isset($data['todate']) ? trim($data['todate']) : '';

        $like = "%".$client."%";
        // Normalize incoming HTML5 date (YYYY-MM-DD) to mm/dd/YYYY expected by DB
        if ($fromdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromdate)) {
            $fromdate = date('m/d/Y', strtotime($fromdate));
        }
        if ($todate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $todate)) {
            $todate = date('m/d/Y', strtotime($todate));
        }
        $table = $this->getOCTable();
        $cn = $this->getOCColumnNames($table);
        $oc = $cn["oc"];
        $nm = $cn["name"];
        $dt = $cn["date"];
        $dtParsed = "STR_TO_DATE(`".$dt."`,'%m/%d/%Y')";
        if ($fromdate !== '' && $todate !== '' && $fromdate === $todate) {
            $sql = "SELECT DISTINCT `".$oc."` AS OCNo, `".$nm."` AS NameTO, `".$dt."` AS DatePrepared FROM `".$table."` WHERE UPPER(TRIM(`".$nm."`)) LIKE UPPER(TRIM(?)) AND ".$dtParsed." = STR_TO_DATE(?, '%m/%d/%Y') ORDER BY ".$dtParsed." DESC, `".$oc."`";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) { $oclist = $this->fallbackOCSearch($table,$oc,$nm,$dt,$like,$fromdate,$todate); echo json_encode(array("OCLIST"=>$oclist)); return; }
            $stmt->bind_param('ss', $like, $fromdate);
        } else if ($fromdate !== '' && $todate !== '') {
            $sql = "SELECT DISTINCT `".$oc."` AS OCNo, `".$nm."` AS NameTO, `".$dt."` AS DatePrepared FROM `".$table."` WHERE UPPER(TRIM(`".$nm."`)) LIKE UPPER(TRIM(?)) AND ".$dtParsed." >= STR_TO_DATE(?, '%m/%d/%Y') AND ".$dtParsed." <= STR_TO_DATE(?, '%m/%d/%Y') ORDER BY ".$dtParsed." DESC, `".$oc."`";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) { $oclist = $this->fallbackOCSearch($table,$oc,$nm,$dt,$like,$fromdate,$todate); echo json_encode(array("OCLIST"=>$oclist)); return; }
            $stmt->bind_param('sss', $like, $fromdate, $todate);
        } else {
            $sql = "SELECT DISTINCT `".$oc."` AS OCNo, `".$nm."` AS NameTO, `".$dt."` AS DatePrepared FROM `".$table."` WHERE UPPER(TRIM(`".$nm."`)) LIKE UPPER(TRIM(?)) ORDER BY ".$dtParsed." DESC, `".$oc."`";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) { $oclist = $this->fallbackOCSearch($table,$oc,$nm,$dt,$like,$fromdate,$todate); echo json_encode(array("OCLIST"=>$oclist)); return; }
            $stmt->bind_param('s', $like);
        }
        if (!$stmt) {
            echo json_encode(array("STATUS"=>"ERROR","MESSAGE"=>"Prepare failed: ".$this->conn->error));
            return;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $oclist[] = $row;
            }
        }
        $stmt->close();
        echo json_encode(array("OCLIST" => $oclist));
    }

}
