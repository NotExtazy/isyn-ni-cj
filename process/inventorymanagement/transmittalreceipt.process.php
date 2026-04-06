<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $isynBranch = $this->SelectQuery("SELECT DISTINCT Stock FROM tbl_invlist ORDER BY Stock");
        $branch = $this->SelectQuery("SELECT DISTINCT ItemName FROM tbl_maintenance WHERE ItemType='BRANCH'");
        $prodType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_prodtype ORDER BY Type");
        $customertype = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_clientlist ORDER BY Type");
        $transmittno = 0000;
        $orgname = "ISYNERGIESINC";

        $stmt = $this->conn->prepare("SELECT Value FROM tbl_configuration WHERE ConfigOwner='TRANSMITTALRECEIPT' AND ConfigName='CURRENTTRANSMITTALNO'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $transmittno = $row['Value'];
        }

        $morno = "";
        $zeroes = 7 - strlen($transmittno);
        for ($i=0; $i < $zeroes; $i++) { 
            $morno = "0".$morno;
        }
        
        $currUser = $_SESSION['USERNAME'];
        $FL = substr($currUser, 0, 1);
        $LL = substr($currUser, -1, 1);

        $transmittno = "TM". $FL . "" . $LL . "" . $morno . "" . $transmittno;

        $stmt = $this->conn->prepare("SELECT Value FROM tbl_configuration WHERE ConfigOwner='TRANSMITTALRECEIPT' AND ConfigName='ORGNAME'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $orgname = $row['Value'];
        }

        $stmtClear = $this->conn->prepare("DELETE FROM tbl_transaction");
        $stmtClear->execute();

        echo json_encode(array( 
            "ISYNBRANCH" => $isynBranch,
            "PRODTYPE" => $prodType,
            "CUSTOMERTYPE" => $customertype,
            "TRANSMITNO" => $transmittno,
            "ORGNAME" => $orgname,
        ));
    }
    
    public function LoadBranch($data){
        $branch = [];
        $value = $data['value'];
        $stmt = $this->conn->prepare("SELECT DISTINCT Stock FROM tbl_invlistconsign WHERE Branch = ? ORDER BY Stock;");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $branch[] = $row;
            }
        }

        echo json_encode(array( 
            "BRANCH" => $branch,
        ));
    }

    public function LoadCategory($data){
        $categ = [];
        $isConsign = $data['isConsign'];
        $type = $data['type'];
        $isynBranch = $data['isynBranch'];
        $consignBranch = $data['consignBranch'];

        if ($isConsign === "Yes"){
            $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlistconsign WHERE Type = ? AND Stock = ? AND Branch = ? ORDER BY Category");
            $stmt->bind_param('sss', $type, $consignBranch, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categ[] = $row;
                }
            }
        } else {
            $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE Type = ? AND Branch = ? ORDER BY Category");
            $stmt->bind_param('ss', $type, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categ[] = $row;
                }
            }
        }

        echo json_encode(array( 
            "CATEG" => $categ,
        ));
    }

    public function LoadSerialProduct($data){
        $serialproduct = [];
        $productSINo = [];
        $isConsign = $data['isConsign'];
        $type = $data['type'];
        $category = $data['category'];
        $isynBranch = $data['isynBranch'];
        $consignBranch = $data['consignBranch'];

        if ($isConsign === "Yes"){
            $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product FROM tbl_invlistconsign WHERE Category = ? AND Type = ? AND Stock = ? AND Branch = ?");
            $stmt->bind_param('ssss', $category, $type, $consignBranch, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serialproduct[] = $row;
                }
            }

            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlistconsign WHERE Category = ? AND Type = ? AND Stock = ? AND Branch = ?");
            $stmt2->bind_param('ssss', $category, $type, $consignBranch, $isynBranch);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $productSINo[] = $row;
                }
            }
        } else {
            $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product FROM tbl_invlist WHERE Category = ? AND Type = ? AND Branch = ?");
            $stmt->bind_param('sss', $category, $type, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serialproduct[] = $row;
                }
            }

            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlist WHERE Category = ? AND Type = ? AND Branch = ?");
            $stmt2->bind_param('sss', $category, $type, $isynBranch);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $productSINo[] = $row;
                }
            }
        }

        echo json_encode(array( 
            "SRKPRDT" => $serialproduct,
            "PRDTSINO" => $productSINo,
        ));
    }

    public function LoadProductSummary($data){
        $branch = $data["isConsign"] == "No" ? $data["isynBranch"] : $data["consignBranch"];
        $table = $data["isConsign"] == "No" ? "tbl_invlist" : "tbl_invlistconsign";
        $selectBy = $data["selectBy"];
        $type = $data["type"];
        $category = $data["category"];
        $serialProduct = $data["serialProduct"];
        $SINo = $data["SINo"];
        // $isynBranch = $data["isynBranch"];
        // $consignBranch = $data["consignBranch"];
        $productSummary = "";

        if ($selectBy == "serial"){
            $stmt = $this->conn->prepare("SELECT * FROM ".$table." WHERE Type = ? AND Category = ? AND SIno = ? AND stock = ? AND Serialno = ?");
            $stmt->bind_param('sssss', $type, $category, $SINo, $branch, $serialProduct);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $productSummary = $row;
            }
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM ".$table." WHERE Type = ? AND Category = ? AND SIno = ? AND stock = ? AND Product = ?");
            $stmt->bind_param('sssss', $type, $category, $SINo, $branch, $serialProduct);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $productSummary = $row;
            }

        }

        echo json_encode(array(
            "PSUMMARY" => $productSummary,
        ));
    }

    // ===================================================================================

    public function TransmittalSearch($data){
        $transactlist = [];

        $client = isset($data['client']) ? trim($data['client']) : '';
        $fromdate = isset($data['fromdate']) ? trim($data['fromdate']) : '';
        $todate = isset($data['todate']) ? trim($data['todate']) : '';

        $like = "%".$client."%";
        $dateExpr = "COALESCE(NULLIF(DatePrepared,''), NULLIF(DateCarrier,''), NULLIF(DateReceived,''))";
        $dtParsed = "COALESCE(STR_TO_DATE(".$dateExpr.",'%m/%d/%Y'), STR_TO_DATE(".$dateExpr.",'%Y-%m-%d'))";
        if ($fromdate !== '' && $todate !== '' && $fromdate === $todate) {
            $sql = "SELECT DISTINCT TransmittalNO, NameTO, ".$dateExpr." AS DatePrepared, isOUT, SalesInvoice FROM tbl_transmittal WHERE UPPER(TRIM(NameTO)) LIKE UPPER(TRIM(?)) AND ".$dtParsed." = STR_TO_DATE(?, '%m/%d/%Y') ORDER BY ".$dtParsed." DESC, TransmittalNO";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ss', $like, $fromdate);
        } else if ($fromdate !== '' && $todate !== '') {
            $sql = "SELECT DISTINCT TransmittalNO, NameTO, ".$dateExpr." AS DatePrepared, isOUT, SalesInvoice FROM tbl_transmittal WHERE UPPER(TRIM(NameTO)) LIKE UPPER(TRIM(?)) AND ".$dtParsed." >= STR_TO_DATE(?, '%m/%d/%Y') AND ".$dtParsed." <= STR_TO_DATE(?, '%m/%d/%Y') ORDER BY ".$dtParsed." DESC, TransmittalNO";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('sss', $like, $fromdate, $todate);
        } else {
            $sql = "SELECT DISTINCT TransmittalNO, NameTO, ".$dateExpr." AS DatePrepared, isOUT, SalesInvoice FROM tbl_transmittal WHERE UPPER(TRIM(NameTO)) LIKE UPPER(TRIM(?)) ORDER BY ".$dtParsed." DESC, TransmittalNO";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $like);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $transactlist[] = $row;
            }
        }

        echo json_encode(array( 
            "TRANSACTIONLIST" => $transactlist,
        ));
    }

    public function SubmitInvOut($data){
        try {
            if (!$this->conn) {
                throw new Exception("Database connection failed.");
            }
            
            $this->conn->autocommit(false);

            $entries = json_decode($data["DATA"] ?? '[]');
            if (empty($entries)) {
                throw new Exception("No product entries found to submit.");
            }

            $recipient = isset($data['toRep']) ? trim($data['toRep']) : "-";
            $from = (isset($data['fromRep']) && trim($data['fromRep']) != "" && trim($data['fromRep']) != "-") ? trim($data['fromRep']) : "ISYNERGIESINC";
            $carrier = isset($data['carrier']) ? trim($data['carrier']) : "-";
            $dateCarrier = isset($data['dateCarrier']) ? trim($data['dateCarrier']) : "-";
            $receivedBy = isset($data['receivedBy']) ? trim($data['receivedBy']) : "-";
            $dateReceived = isset($data['dateReceivedBy']) ? trim($data['dateReceivedBy']) : "-";
            $remarks = isset($data['remarks']) ? trim($data['remarks']) : "-";
            
            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));
            $user = $_SESSION['USERNAME'] ?? 'ADMIN';

            // 1. Generate/Get Transmittal Number
            $transmittalNo = 1;
            $stmt = $this->conn->prepare("SELECT Value FROM tbl_configuration WHERE ConfigOwner='TRANSMITTALRECEIPT' AND ConfigName='CURRENTTRANSMITTALNO'");
            if (!$stmt) throw new Exception("Prepare failed (transNo): " . $this->conn->error);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $transmittalNo = intval($row['Value']);
            }
            $stmt->close();

            $morno = "";
            $zeroes = 7 - strlen((string)$transmittalNo);
            if ($zeroes < 0) $zeroes = 0;
            for ($i=0; $i < $zeroes; $i++) { $morno .= "0"; }
            $FL = substr($user, 0, 1);
            $LL = substr($user, -1, 1);
            $formattedTransNo = "TM". $FL . "" . $LL . "" . $morno . "" . $transmittalNo;

            // 2. Increment Transmittal No in config
            $newVal = $transmittalNo + 1;
            $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = ? WHERE ConfigOwner='TRANSMITTALRECEIPT' AND ConfigName='CURRENTTRANSMITTALNO'");
            if (!$stmt) throw new Exception("Prepare failed (updateTransNo): " . $this->conn->error);
            $stmt->bind_param('i', $newVal);
            $stmt->execute();
            $stmt->close();

            // 3. Process each entry
            foreach ($entries as $entry) {
                // entry: [0]=ProdSerialNo, [1]=Qty, [2]=TotalAmount, [3]=SIno, [4]=SerialNo, [5]=ProductName, [6]=Supplier, [7]=Category, [8]=Type, [9]=Branch
                $prodSerialNo = $entry[0] ?? "-";
                $qty = $entry[1] ?? 0;
                $amtStr = strval($entry[2] ?? "0");
                $amt = floatval(str_replace(",", "", $amtStr));
                $siNo = $entry[3] ?? "-";
                $serialNo = $entry[4] ?? "-";
                $product = $entry[5] ?? "-";
                $supplier = $entry[6] ?? "-";
                $category = $entry[7] ?? "-";
                $type = $entry[8] ?? "-";
                $branch = $entry[9] ?? "-";
                
                // Insert into tbl_transmittal
                $stmt = $this->conn->prepare("INSERT INTO tbl_transmittal (TransmittalNO, NameTO, NameFROM, InOrder, Quantity, ProductSerialNo, Amount, Remarks, Carrier, DateCarrier, ReceivedBy, DateReceived, totalAmount, DatePrepared, SIno, SerialNo, Product, Supplier, Category, Type, Branch, Consignment, Stock, isOUT, SalesInvoice) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                if (!$stmt) throw new Exception("Prepare failed (insertTrans): " . $this->conn->error);
                
                $inOrder = "1";
                $consign = "No"; 
                $stock = $branch;
                $isOut = "YES";
                $salesInvoice = "-";

                $stmt->bind_param('sssssssssssssssssssssssss', 
                    $formattedTransNo, $recipient, $from, $inOrder, $qty, $prodSerialNo, $amt, 
                    $remarks, $carrier, $dateCarrier, $receivedBy, $dateReceived, $amt, 
                    $AsOf, $siNo, $serialNo, $product, $supplier, $category, $type, 
                    $branch, $consign, $stock, $isOut, $salesInvoice);
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed (insertTrans): " . $stmt->error);
                }
                $stmt->close();

                // 4. Handle Stock Reduction
                // Try tbl_invlist
                $stmt6 = $this->conn->prepare("SELECT Quantity FROM tbl_invlist WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                if (!$stmt6) throw new Exception("Prepare failed (selectInv): " . $this->conn->error);
                $stmt6->bind_param('sssss', $siNo, $serialNo, $product, $category, $branch);
                $stmt6->execute();
                $res6 = $stmt6->get_result();
                if ($row6 = $res6->fetch_assoc()) {
                    $currentQty = intval($row6["Quantity"]);
                    $rcQuantity = $currentQty - intval($qty);
                    
                    if ($rcQuantity <= 0) {
                        $stmtDel = $this->conn->prepare("DELETE FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                        $stmtDel->bind_param('sssss', $serialNo, $siNo, $product, $category, $branch);
                        $stmtDel->execute();
                        $stmtDel->close();
                    } else {
                        $stmtUpd = $this->conn->prepare("UPDATE tbl_invlist SET QUANTITY = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                        $stmtUpd->bind_param('isssss', $rcQuantity, $serialNo, $siNo, $product, $category, $branch);
                        $stmtUpd->execute();
                        $stmtUpd->close();
                    }
                } else {
                    // Try tbl_invlistconsign
                    $stmt4 = $this->conn->prepare("SELECT Quantity FROM tbl_invlistconsign WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                    if (!$stmt4) throw new Exception("Prepare failed (selectConsign): " . $this->conn->error);
                    $stmt4->bind_param('sssss', $siNo, $serialNo, $product, $category, $branch);
                    $stmt4->execute();
                    $res4 = $stmt4->get_result();
                    if ($row4 = $res4->fetch_assoc()) {
                        $currentQty = intval($row4["Quantity"]);
                        $rcQuantity = $currentQty - intval($qty);
                        
                        if ($rcQuantity <= 0) {
                            $stmtDel = $this->conn->prepare("DELETE FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                            $stmtDel->bind_param('sssss', $serialNo, $siNo, $product, $category, $branch);
                            $stmtDel->execute();
                            $stmtDel->close();
                        } else {
                            $stmtUpd = $this->conn->prepare("UPDATE tbl_invlistconsign SET QUANTITY = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                            $stmtUpd->bind_param('isssss', $rcQuantity, $serialNo, $siNo, $product, $category, $branch);
                            $stmtUpd->execute();
                            $stmtUpd->close();
                        }
                    }
                    $stmt4->close();
                }
                $stmt6->close();
            }

            $this->conn->commit();
            $this->conn->autocommit(true);
            $_SESSION['SelectedTransNo'] = $formattedTransNo;

            echo json_encode(array(
                "STATUS" => "success",
                "MESSAGE" => "Transmittal Receipt $formattedTransNo has been submitted and saved.",
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

    public function PrintSalesInvoice ($data) {
        $products = [];
        date_default_timezone_set('Asia/Manila');
        $AsOf = date("m/d/Y", strtotime("now"));
        $ProdPend = "NO";

        $stmt = $this->conn->prepare("SELECT Quantity, DateAdded, SIno, Supplier, SUM(TotalPrice) AS forTotal, SUM(Vat) AS forVAT, SUM(VatSales) AS forSVat, Stock, Branch, User FROM tbl_inventoryin WHERE ProdPend = 'YES' AND STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y') GROUP BY SIno, Supplier, Stock, Branch, DateAdded, User");
        $stmt->bind_param('s', $AsOf);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;

                $purchaseDate = $row['DateAdded'];
                $SINo = $row['SIno'];
                $supplier = $row['Supplier'];
                $total = $row['forTotal'];
                $vat = $row['forVAT'];
                $svat = $row['forSVat'];
                $stock = $row['Stock'];
                $branch = $row['Branch'];

                $stmt1 = $this->conn->prepare("INSERT INTO tbl_purchasejournal (DatePurchase, Reference, Supplier, GrossPurchase, InputVAT, NetPurchase, Stock, Branch) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt1->bind_param('ssssssss', $purchaseDate, $SINo, $supplier, $total, $vat, $svat, $stock, $branch);
                $stmt1->execute();

                $stmt2 = $this->conn->prepare("SELECT tinNumber, fullAddress FROM tbl_supplier_info WHERE supplierName = ?");
                $stmt2->bind_param('s', $supplier);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    $row2 = $result2->fetch_assoc();
                    $tin = $row2['tinNumber'];
                    $address = $row2['fullAddress'];
                    
                    $stmt3 = $this->conn->prepare("UPDATE tbl_purchasejournal SET TIN = ?,  Address = ? WHERE Supplier = ? AND TIN = '-' AND Address = '-'");
                    $stmt3->bind_param('sss', $tin, $address, $supplier);
                    $stmt3->execute();
                }
            }
        }

        $stmt3 = $this->conn->prepare("INSERT INTO tbl_invlist (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ?, Stock, Branch, Warranty, imgname FROM tbl_inventoryin WHERE ProdPend = 'YES' AND STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y')");
        $stmt3->bind_param('ss', $ProdPend, $AsOf);
        $stmt3->execute();

        $stmt3 = $this->conn->prepare("UPDATE tbl_inventoryin SET ProdPend = 'NO' WHERE ProdPend = 'YES'");
        $stmt3->execute();

        $tableData = json_decode($data['DATA']);
        unset($_SESSION['tableData']);
        $_SESSION['tableData'] = $tableData;

        echo json_encode(array(
            "PRODS" => $products,
            "DATAINVSESS" => $tableData,
        ));
    }

    public function SelectQuery($sql){
        $data = [];
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}
