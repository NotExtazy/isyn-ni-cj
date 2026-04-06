<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    private $siTableName = 'tbl_sinumber';
    private function resolveSiTableName(){
        try {
            $res = $this->conn->query("SHOW TABLES LIKE 'tbl_sinumber'");
            if ($res && $res->num_rows > 0) { $this->siTableName = 'tbl_sinumber'; return; }
            $res2 = $this->conn->query("SHOW TABLES LIKE 'TBL_SINUMBER'");
            if ($res2 && $res2->num_rows > 0) { $this->siTableName = 'TBL_SINUMBER'; return; }
        } catch (\Throwable $e) {}
        $this->siTableName = 'tbl_sinumber';
    }
    private function ensureSiTable(){
        try {
            $this->resolveSiTableName();
            $res = $this->conn->query("SHOW TABLES LIKE '".$this->siTableName."'");
            if (!$res || $res->num_rows === 0) {
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS tbl_sinumber (
                        user VARCHAR(100) NOT NULL,
                        SIcount INT NOT NULL DEFAULT 0,
                        PRIMARY KEY (user)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                $this->siTableName = 'tbl_sinumber';
            }
        } catch (\Throwable $e) {
            // Silent; queries will still error but won't fatal here
        }
    }
    private $prodHistoryTableName = 'tbl_prodhistory';
    private function resolveProdHistoryTableName(){
        try {
            $res = $this->conn->query("SHOW TABLES LIKE 'tbl_prodhistory'");
            if ($res && $res->num_rows > 0) { $this->prodHistoryTableName = 'tbl_prodhistory'; return; }
            $res2 = $this->conn->query("SHOW TABLES LIKE 'TBL_PRODHISTORY'");
            if ($res2 && $res2->num_rows > 0) { $this->prodHistoryTableName = 'TBL_PRODHISTORY'; return; }
        } catch (\Throwable $e) {}
        $this->prodHistoryTableName = 'tbl_prodhistory';
    }
    private function ensureProdHistoryTable(){
        try {
            $this->resolveProdHistoryTableName();
            $res = $this->conn->query("SHOW TABLES LIKE '".$this->prodHistoryTableName."'");
            if (!$res || $res->num_rows === 0) {
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS tbl_prodhistory (
                        SIno VARCHAR(128),
                        Serialno VARCHAR(128),
                        Product VARCHAR(255),
                        Supplier VARCHAR(255),
                        Category VARCHAR(100),
                        Type VARCHAR(50),
                        Quantity INT,
                        DealerPrice DECIMAL(15,2),
                        TotalPrice DECIMAL(15,2),
                        SRP DECIMAL(15,2),
                        TotalSRP DECIMAL(15,2),
                        Markup DECIMAL(15,2),
                        TotalMarkup DECIMAL(15,2),
                        VatSales DECIMAL(15,2),
                        Vat DECIMAL(15,2),
                        AmountDue DECIMAL(15,2),
                        DateAdded VARCHAR(20),
                        DatePurchase VARCHAR(20),
                        User VARCHAR(100),
                        AsOf VARCHAR(20),
                        ProdPend VARCHAR(10),
                        Stock VARCHAR(100),
                        Branch VARCHAR(100),
                        Warranty VARCHAR(255),
                        imgname VARCHAR(255)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                $this->prodHistoryTableName = 'tbl_prodhistory';
            }
        } catch (\Throwable $e) {
        }
    }
    private $salesJournalTableName = 'tbl_salesjournal';
    private function resolveSalesJournalTableName(){
        try {
            $res = $this->conn->query("SHOW TABLES LIKE 'tbl_salesjournal'");
            if ($res && $res->num_rows > 0) { $this->salesJournalTableName = 'tbl_salesjournal'; return; }
            $res2 = $this->conn->query("SHOW TABLES LIKE 'TBL_SALESJOURNAL'");
            if ($res2 && $res2->num_rows > 0) { $this->salesJournalTableName = 'TBL_SALESJOURNAL'; return; }
        } catch (\Throwable $e) {}
        $this->salesJournalTableName = 'tbl_salesjournal';
    }
    private function ensureSalesJournalTable(){
        try {
            $this->resolveSalesJournalTableName();
            $res = $this->conn->query("SHOW TABLES LIKE '".$this->salesJournalTableName."'");
            if (!$res || $res->num_rows === 0) {
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS tbl_salesjournal (
                        DateSold VARCHAR(20),
                        Reference VARCHAR(64),
                        Customer VARCHAR(255),
                        GrossSales DECIMAL(15,2),
                        VAT DECIMAL(15,2),
                        NetSales DECIMAL(15,2),
                        TIN VARCHAR(50),
                        Address VARCHAR(255),
                        Stock VARCHAR(100)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
                $this->salesJournalTableName = 'tbl_salesjournal';
            }
        } catch (\Throwable $e) {
            // Silent
        }
    }
    private function ensureTransactionTable(){
        $sql = "
        CREATE TABLE IF NOT EXISTS tbl_transaction (
            id INT AUTO_INCREMENT PRIMARY KEY,
            SI VARCHAR(64) DEFAULT NULL,
            SupplierSI VARCHAR(128),
            Batchno VARCHAR(128) DEFAULT NULL,
            Serialno VARCHAR(128),
            Product VARCHAR(255),
            Supplier VARCHAR(255),
            Category VARCHAR(100),
            Type VARCHAR(50),
            Quantity INT,
            DealerPrice DECIMAL(15,2),
            TotalPrice DECIMAL(15,2),
            SRP DECIMAL(15,2),
            TotalSRP DECIMAL(15,2),
            Markup DECIMAL(15,2),
            TotalMarkup DECIMAL(15,2),
            VatSales DECIMAL(15,2),
            VAT DECIMAL(15,2),
            AmountDue DECIMAL(15,2),
            DateAdded VARCHAR(20),
            User VARCHAR(100),
            Soldto VARCHAR(255),
            TIN VARCHAR(50),
            Address VARCHAR(255),
            Status VARCHAR(50),
            Stock VARCHAR(100),
            Branch VARCHAR(100),
            itemConsign VARCHAR(50),
            myClient VARCHAR(50),
            Area VARCHAR(100),
            Department VARCHAR(100),
            DiscProduct VARCHAR(10),
            DiscInterest DECIMAL(8,2),
            DiscAmount DECIMAL(15,2),
            DiscNewSRP DECIMAL(15,2),
            DiscNewTotalSRP DECIMAL(15,2),
            Warranty VARCHAR(255),
            imgname VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->conn->query($sql);
    }
    public function Initialize(){
        $this->ensureTransactionTable();
        $this->ensureSiTable();
        $isynBranch = $this->SelectQuery("SELECT DISTINCT Stock FROM tbl_invlist ORDER BY Stock");
        $branch = $this->SelectQuery("SELECT DISTINCT ItemName FROM tbl_maintenance WHERE ItemType='BRANCH'");
        $prodType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_prodtype ORDER BY Type");
        $customertype = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_clientlist ORDER BY Type");
        $sicount = 00000;
        $nextSI = 1;

        $user = $_SESSION['USERNAME'];
        $stmt = $this->conn->prepare("SELECT SIcount FROM ".$this->siTableName." WHERE user = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $sicount = $row['SIcount'];
        }
        $nextSI = intval($sicount) + 1;

        // Do not clear tbl_transaction on initialize to preserve in-progress items

        echo json_encode(array( 
            "ISYNBRANCH" => $isynBranch,
            "PRODTYPE" => $prodType,
            "CUSTOMERTYPE" => $customertype,
            "SICOUNT" => $sicount,
            "NEXTSI" => $nextSI,
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
    
    public function LoadCustomerName($data){
        $customerName = [];

        $customerType = $data['customerType'];
        $stmt = $this->conn->prepare("SELECT DISTINCT Name FROM tbl_clientlist WHERE Type = ? ORDER BY Name");
        $stmt->bind_param('s', $customerType);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customerName[] = $row;
            }
        }
        $stmt->close();
        
        echo json_encode(array(
            "CUSTOMERNAMELIST" => $customerName,
        ));
    }

    public function LoadCustomerNameInfo($data){
        $customerInfo = [];

        $customerName = $data['customerName'];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_clientlist WHERE Name = ?");
        $stmt->bind_param('s', $customerName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $customerInfo[] = $row;
        }
        $stmt->close();
        
        echo json_encode(array(
            "CUSTOMERINFO" => $customerInfo,
        ));
    }

    public function AddToItems($data){
        $this->ensureTransactionTable();

        $ConsignQuant = 0;
        $needQty = 0;
        $actualQty = 0;

        $isConsigment = $data['isConsignment'];
        $consignmentStatus = $data['isConsignment'] == "Yes" ? 'CONSIGNMENT' : 'NO' ;
        $isynBranch = $data['isynBranch'];
        $consignBranch = $consignmentStatus == "NO" ? $data['isynBranch'] : $data['consignBranch'];
        $type = $data['type'];
        $categ = $data['categ'];
        $serialProduct = $data['serialProduct'];
        $SINo = $data['SINo'];
        $supplierSI = $data['supplierSI'];
        $serialNo = $data['serialNo'];
        $productName = $data['productName'];
        $supplierName = $data['supplierName'];
        $psSRP = str_replace(",", "", $data['psSRP']);
        $psQty = $data['psQty'];
        $psDealerPrice = str_replace(",", "", $data['psDealerPrice']);
        $psTotalPrice = str_replace(",", "", $data['psTotalPrice']);
        $customerType = $data['customerType'];
        $customerName = $data['customerName'];
        $staffLoan = $data['staffLoan'];
        $branchUsed = $data['branchUsed'];
        $mfiUsed = $data['mfiUsed'];
        $tin = $data['tin'];
        $address = $data['address'];
        $status = $data['status'];
        $srpMS = str_replace(",", "", $data['srpMS']);
        $qtyMS = $data['qtyMS'];
        $vatMS = str_replace(",", "", $data['vatMS']);
        $totalCostMS = str_replace(",", "", $data['totalCostMS']);
        $addDiscount = $data['addDiscount'];
        $discInterest = $data['discInterest'];
        $discAmtMS = str_replace(",", "", $data['discAmtMS']);
        $newSRPMS = str_replace(",", "", $data['newSRPMS']);
        $totalDiscountMS = str_replace(",", "", $data['totalDiscountMS']);
        
        $ClientType = "";
        $Area = $data['Area'];
        $Department = "";
        
        $mark = $data['mark'];
        $Tmark = $data['Tmark'];
        $warranty = $data['Warranty'];

        $consignList = [];

        // Enable when current date is now being used
        // date_default_timezone_set('Asia/Manila');
        // $dateAdded = date("m/d/Y", strtotime("now"));

        // Used temporarily until current date encoding
        $dateAdded = $data['transactionDate'];

        $user = $_SESSION['USERNAME'];

        $stmt = $this->conn->prepare("SELECT SUM(CAST(Quantity AS UNSIGNED)) AS forQuantity FROM tbl_transaction WHERE Serialno = ? AND Supplier = ? AND Product = ? AND Category = ? AND SupplierSI = ?");
        $stmt->bind_param('sssss', $serialNo, $supplierName,$productName,$categ,$supplierSI);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $InQuant = $row['forQuantity'];
        } else {
            $InQuant = 0;
        }
        $stmt->close();
        
        if ($isConsigment != "Yes"){
            $stmt = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE Serialno = ? AND Supplier = ? AND Product = ? AND Category = ? AND SIno = ? AND Branch = ?");
            $stmt->bind_param('ssssss', $serialNo, $supplierName,$productName,$categ,$supplierSI,$isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()){
                    $ConsignQuant += $row['Quantity'];
                    $consignList[] = $row;
                }

                if ($qtyMS > ($psQty - $ConsignQuant)){
                    $needQty = $qtyMS - ($psQty - $ConsignQuant);
                    $actualQty = $psQty - $ConsignQuant;

                    $transactstatus = "WARNING";
                    $message = "Available stock (".($psQty - $ConsignQuant).") is lower than your inputted quantity. Get more stock from the branch?";

                    echo json_encode(array(
                        "STATUS" => $transactstatus,
                        "MESSAGE" => $message,
                        "ConsignList" => $consignList,
                        "ConsignQuant" => $ConsignQuant,
                        "needQty" => $needQty,
                        "actualQty" => $actualQty,
                    ));
                    return;
                    
                }
            }
            $stmt->close();
        }

        $QuantVariance = $psQty - $InQuant;

        if ($QuantVariance < 0){
            $transactstatus = "FAILED";
            $message = "The available stock quantity is lower than the quantity you entered.";
        } else {
            $vat = 0;
            $vatSales = 0;
            $amountDue = 0;
            
            if ($addDiscount == "Yes"){
                if ($type == "WITH VAT"){
                    $vat = round((floatval($newSRPMS) / 1.12) * 0.12, 2) * floatval($psQty);
                    $vatSales = floatval($totalDiscountMS) - $vat;
                    $amountDue = $totalDiscountMS;
                } else {
                    $vat = 0;
                    $vatSales = $totalDiscountMS;
                    $amountDue = $totalDiscountMS;
                }
            } else {
                if ($type == "WITH VAT"){
                    $vat = round((floatval($srpMS) / 1.12) * 0.12, 2) * floatval($psQty);
                    $vatSales = floatval($totalCostMS) - $vat;
                    $amountDue = $totalCostMS;
                } else {
                    $vat = 0;
                    $vatSales = $totalCostMS;
                    $amountDue = $totalCostMS;
                }
            }
            
            if ($customerType == "OTHER CLIENT"){
                $ClientType = "EXTERNAL";
                $Area = "-";
                $Department = "WALK IN CLIENT";
            } else if ($customerType == "EXTERNAL CLIENT"){
                $ClientType = "EXTERNAL";
                $Area = "-";
                $Department = "WALK IN CLIENT";
            } else if ($customerType == "STAFF"){
                $ClientType = "EXTERNAL";
                $Area = "-";
                if ($staffLoan == "Yes"){
                    $Department = "ISYN LOAN";
                } else {
                    $Department = "ASKI EMPLOYEE";
                }
            } else if ($customerType == "MFI BRANCHES"){
                if ($branchUsed == "BRANCH USED"){
                    $ClientType = "INTERNAL";
                    $Department = "BRANCH USED";
                } else if ($mfiUsed == "MFI CLIENT") {
                    $ClientType = "EXTERNAL";
                    $Department = "MFI CLIENT";
                }
            } else if ($customerType == "DEPARTMENT"){
                $ClientType = "INTERNAL";
                $Area = "-";
                $Department = "AGC HO";
            } else if ($customerType == "BUSINESS UNIT"){
                $ClientType = "INTERNAL";
                $Area = "-";
                if ($customerName == "ISYNERGIES INC"){
                    $Department = "ISYNERGIES INC";
                } else {
                    $Department = "BUSINESS UNIT";
                }
            } else if ($customerType == "MFI HO"){
                $ClientType = "INTERNAL";
                $Area = "-";
                $Department = "MFI HO";
            } else {
                $ClientType = "-";
                $Area = "-";
                $Department = "-";
            }
            
            $stmt1 = $this->conn->prepare("INSERT INTO tbl_transaction (SupplierSI, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt1->bind_param('ssssssssssssssssssssssssssssssssss', $supplierSI, $serialNo, $productName, $supplierName, $categ, $type, $qtyMS, $psDealerPrice, $vatMS, $srpMS, $totalCostMS, $mark, $Tmark, $vatSales, $vat, $amountDue, $dateAdded, $user, $customerName, $tin, $address, $status, $consignBranch, $isynBranch, $consignmentStatus, $ClientType, $Area, $Department, $addDiscount, $discInterest, $discAmtMS, $newSRPMS, $totalDiscountMS, $warranty);
            $stmt1->execute();
            $stmt1->close();

            $transactstatus = "SUCCESS";
            $message = "Successfully added.";            
        }
        
        echo json_encode(array(
            "STATUS" => $transactstatus,
            "MESSAGE" => $message,
            "ConsignQuant" => $ConsignQuant,
            "needQty" => $needQty,
            "actualQty" => $actualQty,
            "custtype" => $customerType,
            "branchused" => $branchUsed,
            "mfiused" => $mfiUsed,
            "clienttype" => $ClientType,
            "area" => $Area,
            "department" => $Department,
        ));
    }
    
    public function UseQtyFromBranchConsign($data){
        $qtyConsign = json_decode($data["DATA"]);
        
        for ($i=0; $i < count($qtyConsign); $i++) {
            $vVat = 0.12;

            $SI = $data["supplierSI"];
            $SerialNo = $data["serialNo"];
            $ProductName = $data["productName"];
            $MyCategory = $data["categ"];
            $Stock = $qtyConsign[$i][0];
            $Qty = $qtyConsign[$i][1];

            $stmt4 = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ?");
            $stmt4->bind_param('sssss', $SI, $SerialNo, $ProductName, $MyCategory, $Stock);
            $stmt4->execute();
            $result4 = $stmt4->get_result();
            if ($result4->num_rows > 0) {
                $row4 = $result4->fetch_assoc();

                $currentQuantity = $row4["Quantity"];
                $DealerPrice = $row4["DealerPrice"];
                $ProdSRP = $row4["SRP"];
                $Type = $row4["Type"];
                
                $rcQuantity = $currentQuantity - $Qty;
                $Totalprice = floatval($DealerPrice * $rcQuantity);

                $TSRP = floatval($ProdSRP * $rcQuantity);
                $MyMark = floatval($ProdSRP * $rcQuantity);
                $MyTMark = round($MyMark - $Totalprice, 2);

                if ($Type == "WITH VAT") {
                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                    $MySalesVat = floatval($Totalprice) - $MyVat;
                } else {
                    $MyVat = 0;
                    $MySalesVat = $Totalprice;
                }

                if ($rcQuantity == 0){
                    $stmt = $this->conn->prepare("INSERT INTO tbl_invlistconsignhistory (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ?");
                    $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$Stock);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $this->conn->prepare("DELETE FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ?");
                    $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$Stock);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ?");
                    $stmt->bind_param('sssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$Stock);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $stmt4->close();
            $status = "SUCCESS";
        }
        
        echo json_encode(array(
            "STATUS" => $status,
        ));
    }

    private $barangayTableName = 'tbl_barangays';
    private function resolveBarangayTableName(){
        try {
            $res = $this->conn->query("SHOW TABLES LIKE 'tbl_barangays'");
            if ($res && $res->num_rows > 0) { $this->barangayTableName = 'tbl_barangays'; return; }
            $res2 = $this->conn->query("SHOW TABLES LIKE 'tbl_baranggays'");
            if ($res2 && $res2->num_rows > 0) { $this->barangayTableName = 'tbl_baranggays'; return; }
        } catch (\Throwable $e) {}
        $this->barangayTableName = 'tbl_barangays';
    }

    public function LoadRegion(){
        $this->resolveBarangayTableName();
        $list = [];
        $stmt = $this->conn->prepare("SELECT DISTINCT Region FROM ".$this->barangayTableName." ORDER BY Region");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $val = $row['Region'];
                    if ($val) $list[] = ['region' => $val];
                }
            }
            $stmt->close();
        }
        echo json_encode(array("REGION" => $list));
    }

    public function LoadProvince($data){
        $this->resolveBarangayTableName();
        $list = [];
        $region = $data['region'];
        $stmt = $this->conn->prepare("SELECT DISTINCT Province FROM ".$this->barangayTableName." WHERE Region = ? ORDER BY Province");
        if ($stmt) {
            $stmt->bind_param('s', $region);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $val = $row['Province'];
                    if ($val) $list[] = ['province' => $val];
                }
            }
            $stmt->close();
        }
        echo json_encode(array("PROVINCE" => $list));
    }

    public function LoadCity($data){
        $this->resolveBarangayTableName();
        $list = [];
        $province = $data['province'];
        
        // Based on user provided schema: Column is 'CityTown'
        $stmt = $this->conn->prepare("SELECT DISTINCT CityTown FROM ".$this->barangayTableName." WHERE Province = ? ORDER BY CityTown");

        if ($stmt) {
            $stmt->bind_param('s', $province);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $val = $row['CityTown'];
                    // Frontend expects 'municipality' key
                    if ($val) $list[] = ['municipality' => $val];
                }
            }
            $stmt->close();
        }
        echo json_encode(array("CITY" => $list));
    }

    public function LoadBarangay($data){
        $this->resolveBarangayTableName();
        $list = [];
        $city = $data['city'];
        
        // Based on user provided schema: Column is 'Barangay' and city filter is 'CityTown'
        $stmt = $this->conn->prepare("SELECT DISTINCT Barangay FROM ".$this->barangayTableName." WHERE CityTown = ? ORDER BY Barangay");

        if ($stmt) {
            $stmt->bind_param('s', $city);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                     $val = $row['Barangay'];
                     // Frontend expects 'baranggay' key
                     if ($val) $list[] = ['baranggay' => $val];
                }
            }
            $stmt->close();
        }
        echo json_encode(array("BARANGAY" => $list));
    }

    // ===================================================================================
    public function LoadTransaction(){
        $this->ensureTransactionTable();
        $transactlist = [];
        $user = $_SESSION['USERNAME'];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_transaction WHERE User = ?");
        $stmt->bind_param('s', $user);
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

    public function LoadInventoryOut(){
        $list = [];
        $stmt = $this->conn->prepare("SELECT SupplierSI, Serialno, Product, Soldto, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, VatSales, VAT, AmountDue, DiscProduct, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Category, Supplier, Warranty, TIN, Address FROM tbl_inventoryout ORDER BY STR_TO_DATE(DateAdded,'%m/%d/%Y') DESC LIMIT 200");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $list[] = $row;
            }
        }
        $stmt->close();
        echo json_encode(array("INVENTORYOUT"=>$list));
    }

    public function DeleteFromItems($data){
        
        $SINo = $data["SINo"];
        $SerialNo = $data["SerialNo"];
        $Product = $data["Product"];
        $Product = html_entity_decode($Product, ENT_QUOTES, 'UTF-8');        
        
        $stmt = $this->conn->prepare("DELETE FROM tbl_transaction WHERE SupplierSI = ? AND TRIM(Serialno) = ? AND Product = ?");
        $stmt->bind_param('sss', $SINo,$SerialNo,$Product);
        $stmt->execute();
        $result1 = $stmt->affected_rows;
        $stmt->close();

        if ($result1 === 0) {
            $status = "error";
            $message = "Failed to deleted transaction [".$SINo." | ".$SerialNo." | ".$Product."].";
        } else {
            $status = "success";
            $message = "Deleted transaction [".$SINo." | ".$SerialNo." | ".$Product."].";
        }

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "DATA" => $data,
        ));
    }

    public function TransmittalSearch($data){
        $transactlist = [];

        $dateFrom = $data['dateFrom'];
        $dateTo = $data['dateTo'];        

        $stmt = $this->conn->prepare("SELECT DISTINCT TransmittalNO, NameTO, DatePrepared FROM tbl_transmittal WHERE STR_TO_DATE(DatePrepared,'%m/%d/%Y') >= STR_TO_DATE(?,'%m/%d/%Y') AND STR_TO_DATE(DatePrepared,'%m/%d/%Y') <= STR_TO_DATE(?,'%m/%d/%Y') ORDER BY TransmittalNO, str_to_date(DatePrepared,'%m/%d/%Y') DESC");
        $stmt->bind_param('ss', $dateFrom, $dateTo);
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

    public function fetchProducts($data){
        $productlist = [];
        $values = [];

        $transNo = $data['transNo'];
        $clientName = $data['clientName'];
        $date = $data['date'];

        $inventory = "";

        if (!empty($transNo)){
            $stmt = $this->conn->prepare("SELECT * FROM tbl_transmittal WHERE TransmittalNO = ?");
            $stmt->bind_param('s', $transNo);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $curQty = 0;

                    $ProductSerialNo = $row['ProductSerialNo'];
                    $Amount = $row['Amount'];
                    $TmlQty = $row['Quantity'];
                    $SIno = $row['SIno'];
                    $SerialNo = $row['SerialNo'];
                    $Product = $row['Product'];
                    $Supplier = $row['Supplier'];
                    $Category = $row['Category'];
                    $Type = $row['Type'];
                    $Stock = $row['Stock'];
                    $Branch = $row['Branch'];
                    $isConsign = $row['Consignment'];

                    if ($row['Consignment'] == "CONSIGNMENT"){
                        $stmt1 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ? AND Category = ? AND Branch = ?");
                        $stmt1->bind_param('sssss', $SIno, $SerialNo, $Product, $Category, $Branch);
                        $stmt1->execute();
                        $result1 = $stmt1->get_result();
                        $inventory = $result1->fetch_assoc();

                        if (!$inventory){
                            goto skip;
                        }
                    }

                    $table = ($row['Consignment'] == "CONSIGNMENT") ? "tbl_invconsign" : "tbl_invlist";
                    
                    $stockBranchFilter = "";
                    if ($row['Consignment'] == "CONSIGNMENT"){
                        $stockBranchFilter = " AND Stock = '$Stock' AND Branch = '$Branch' ";
                    } else {
                        $stockBranchFilter = " AND Branch = '$Branch' ";
                    }

                    $query = "SELECT * FROM $table WHERE SIno = '$SIno' AND Serialno = '$SerialNo' AND Product = '$Product' AND Category = '$Category' $stockBranchFilter";

                    $stmt2 = $this->conn->prepare("SELECT * FROM $table WHERE SIno = '$SIno' AND Serialno = '$SerialNo' AND Product = '$Product' AND Category = '$Category' $stockBranchFilter");
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($result2->num_rows > 0) {
                        $inventory = $result2->fetch_assoc();
                    } else {
                        $inventory = "";
                    }
    
                    skip:

                    // $values[] = $inventory;
                    $vMyCurQuantity = empty($inventory) ? 0 : $inventory['Quantity'];

                    $availabity = ($vMyCurQuantity == 0 || $vMyCurQuantity < $TmlQty)  ? "NOT AVAILABLE" : (($vMyCurQuantity >= $TmlQty) ? "YES" : "NO");

                    $productlist[] = [
                        "Product" => $ProductSerialNo,
                        "TQty" => $TmlQty,
                        "CIQty" => $vMyCurQuantity,
                        "Avail" => $availabity,
                        "Consign" => $isConsign,
                        "Amount" => $Amount,
                        "SIno" => $SIno,
                        "Serial" => $SerialNo,
                        "Product" => $Product,
                        "Supplier" => $Supplier,
                        "Category" => $Category,
                        "Type" => $Type,
                        "Stock" => $Stock,
                        "Branch" => $Branch,
                    ];
                }
            }
        }

        echo json_encode(array( 
            "PRODUCTLIST" => $productlist,
            "QUERY" => $query,
            "values" => $values,
        ));
    }

    public function LoadtoList($data){
        try {
            $this->conn->begin_transaction();

            $user = $_SESSION['USERNAME'];

            $stmt = $this->conn->prepare("DELETE FROM tbl_transaction WHERE User = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $stmt->close();

            $entries = json_decode($data["DATA"]);

            $customerType = $data['customerType'];
            $customerName = $data['customerName'];
            $staffLoan = $data['staffLoan'];
            $branchUsed = $data['branchUsed'];
            $mfiUsed = $data['mfiUsed'];
            $tin = $data['tin'];
            $address = $data['address'];
            $paymentStatus = $data['status'];

            $ClientType = "";
            $Area = $data['Area'];
            $Department = "";

            $dateAdded = $data['transactionDate'];

            $values = "";

            $query = ""
            ;
            for ($i=0; $i < count($entries); $i++) {
                // if ($entries[$i][3] == "YES" || $entries[$i][3] == "NO") {
                    $xQuantity = $entries[$i][1];
                    $xCurrQty = $entries[$i][2];
                    $xAvailability = $entries[$i][3];
                    $xConsign = $entries[$i][4];
                    $xTtlAmount = $entries[$i][5];
                    $xSIno = $entries[$i][6];
                    $xSerialNo = $entries[$i][7];
                    $xProduct = $entries[$i][8];
                    $xSupplier = $entries[$i][9];
                    $xCategory = $entries[$i][10];
                    $xType = $entries[$i][11];
                    $xStock = $entries[$i][12];
                    $xBranch = $entries[$i][13];

                    $xSRP = floatval($xTtlAmount) / $xQuantity;

                    if ($xAvailability == "NO"){
                        $xQuantity = $xCurrQty;
                    }

                    // $query .= "SELECT * FROM tbl_invlist WHERE SIno = $xSIno AND Serialno = $xSerialNo AND Product = $xProduct AND Category = $xCategory AND Branch = $xBranch";
                    
                    $xDP = 0;

                    $stmt5 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ? AND Category = ? AND Branch = ?");
                    $stmt5->bind_param('sssss', $xSIno,$xSerialNo,$xProduct,$xCategory,$xBranch);
                    $stmt5->execute();
                    $result5 = $stmt5->get_result();
                    $stmt5->close();
                    if ($result5->num_rows > 0) {
                        $row5 = $result5->fetch_assoc();
                        $xDP = $row5['DealerPrice'];
                    } else {
                        $xDP = 0;
                    }

                    $vat = 0;
                    $vatSales = 0;
                    $amountDue = 0;
                    if ($xType == "WITH VAT"){
                        $vat = round((floatval($xSRP) / 1.12) * 0.12, 2) * floatval($xQuantity);
                        $vatSales = floatval($xSRP * $xQuantity) - $vat;
                        $amountDue = floatval($xSRP * $xQuantity);
                    } else {
                        $vat = 0;
                        $vatSales = floatval($xSRP * $xQuantity);
                        $amountDue = floatval($xSRP * $xQuantity);
                    }

                    if ($customerType == "OTHER CLIENT"){
                        $ClientType = "EXTERNAL";
                        $Area = "-";
                        $Department = "WALK IN CLIENT";
                    } else if ($customerType == "EXTERNAL CLIENT"){
                        $ClientType = "EXTERNAL";
                        $Area = "-";
                        $Department = "WALK IN CLIENT";
                    } else if ($customerType == "STAFF"){
                        $ClientType = "EXTERNAL";
                        $Area = "-";
                        if ($staffLoan == "Yes"){
                            $Department = "ISYN LOAN";
                        } else {
                            $Department = "ASKI EMPLOYEE";
                        }
                    } else if ($customerType == "MFI BRANCHES"){
                        if ($branchUsed == "BRANCH USED"){
                            $ClientType = "INTERNAL";
                            $Department = "BRANCH USED";
                        } else if ($mfiUsed == "MFI CLIENT") {
                            $ClientType = "EXTERNAL";
                            $Department = "MFI CLIENT";
                        }
                    } else if ($customerType == "DEPARTMENT"){
                        $ClientType = "INTERNAL";
                        $Area = "-";
                        $Department = "AGC HO";
                    } else if ($customerType == "BUSINESS UNIT"){
                        $ClientType = "INTERNAL";
                        $Area = "-";
                        if ($customerName == "ISYNERGIES INC"){
                            $Department = "ISYNERGIES INC";
                        } else {
                            $Department = "BUSINESS UNIT";
                        }
                    } else if ($customerType == "MFI HO"){
                        $ClientType = "INTERNAL";
                        $Area = "-";
                        $Department = "MFI HO";
                    } else {
                        $ClientType = "-";
                        $Area = "-";
                        $Department = "-";
                    }

                    $xTotalDP = floatval($xDP * $xQuantity);
                    $xTotalSRP = floatval($xSRP * $xQuantity);
                    $xMark = floatval($xSRP - $xDP);
                    $xTMark = floatval(($xSRP - $xDP) * $xQuantity);

                    $eSIno = $this->conn->real_escape_string($xSIno);
                    $eSerialNo = $this->conn->real_escape_string($xSerialNo);
                    $eProduct = $this->conn->real_escape_string($xProduct);
                    $eSupplier = $this->conn->real_escape_string($xSupplier);
                    $eCategory = $this->conn->real_escape_string($xCategory);
                    $eType = $this->conn->real_escape_string($xType);
                    $eStock = $this->conn->real_escape_string($xStock);
                    $eBranch = $this->conn->real_escape_string($xBranch);
                    $eConsign = $this->conn->real_escape_string($xConsign);
                    
                    $eClientType = $this->conn->real_escape_string($ClientType);
                    $eArea = $this->conn->real_escape_string($Area);
                    $eDepartment = $this->conn->real_escape_string($Department);
                    $eDateAdded = $this->conn->real_escape_string($dateAdded);
                    $eUser = $this->conn->real_escape_string($user);
                    $eCustomerName = $this->conn->real_escape_string($customerName);
                    $eTin = $this->conn->real_escape_string($tin);
                    $eAddress = $this->conn->real_escape_string($address);
                    $ePaymentStatus = $this->conn->real_escape_string($paymentStatus);

                    $values .= ($i > 0) ? "," : "";
                    $values .= "('".$eSIno."','".$eSerialNo."','".$eProduct."','".$eSupplier."','".$eCategory."','".$eType."','".$xQuantity."','".$xDP."','".$xTotalDP."','".$xSRP."','".$xTotalSRP."','".$xMark."','".$xTMark."','".$vatSales."','".$vat."','".$amountDue."','".$eDateAdded."','".$eUser."','".$eCustomerName."','".$eTin."','".$eAddress."','".$ePaymentStatus."','".$eStock."','".$eBranch."','".$eConsign."','".$eClientType."','".$eArea."','".$eDepartment."','NO','0','0','0','0','-')";
                // }
            }

            $stmt = $this->conn->prepare("INSERT INTO tbl_transaction (SupplierSI, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty) VALUES".$values);
            $stmt->execute();
            $stmt->close();
            
            $this->conn->commit();

            $status = "success";
            echo json_encode(array(
                "STATUS" => $status,
                // "QRY" => $query,
                "VALUE" => $values,
            ));
            
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }

    public function SubmitInvOut($data){
        try {
            $this->conn->autocommit(false);

            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));

            $user = $_SESSION['USERNAME'];

            // IMPORTANT: Submit should only process ONE client (Soldto) per invoice.
            // The frontend sends DATA filtered by selected client.
            $tableData = json_decode($data['DATA'] ?? '[]', true);
            if (!is_array($tableData) || count($tableData) === 0) {
                throw new Exception("No selected rows to submit.");
            }
            $selectedSoldto = trim(strval($data['soldto'] ?? ($tableData[0][3] ?? '')));
            if ($selectedSoldto === '') {
                throw new Exception("Missing selected client (Sold To). Please select a row and try again.");
            }
            foreach ($tableData as $r) {
                $rSold = trim(strval($r[3] ?? ''));
                if ($rSold === '' || strcasecmp($rSold, $selectedSoldto) !== 0) {
                    throw new Exception("Mixed clients in submission. Please submit one client at a time.");
                }
            }
            $SalesInvoice = "";
            $this->ensureSiTable();
            $stmt = $this->conn->prepare("SELECT SIcount FROM ".$this->siTableName." WHERE user = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $SalesInvoice = $row['SIcount'];
            }
            $stmt->close();

            $NewSalesInvoice = intval($SalesInvoice) + 1;
    
            // Assign SI only to the selected client's pending transactions
            $stmt1 = $this->conn->prepare("UPDATE tbl_transaction SET SI = ? WHERE User = ? AND Soldto = ?");
            $stmt1->bind_param('sss', $SalesInvoice,$user,$selectedSoldto);
            $stmt1->execute();
            $stmt1->close();
            
            $stmt2 = $this->conn->prepare("UPDATE ".$this->siTableName." SET SIcount = ? WHERE user = ?");
            $stmt2->bind_param('ss', $NewSalesInvoice,$user);
            $stmt2->execute();
            $stmt2->close();

            $currentQuantity = 0;
            $DealerPrice = 0;
            $ProdSRP = 0;

            $Totalprice = 0;
            $TSRP = 0;
            $MyMark = 0;
            $MyTMark = 0;

            $MyVat = 0;
            $MySalesVat = 0;

            $SerialNo = "";
            $SI = "";
            $ProductName = "";
            $MyQuantity = "";
            $TotalSales = 0;
            $MyCategory = "";
            $ItemConsign = "";
            $isynBranch = "";
            $askiBranch = "";
            $Type = "";

            $SalesNoVat = 0;
            $SalesWithVat = 0;
            $AmountDue = 0;
            $VAT = 0.12;

            $SIRef = "-";
            $DateAdded = "-";

            // Process only the selected client's transactions for this SI
            $stmt3 = $this->conn->prepare("SELECT * FROM tbl_transaction WHERE User = ? AND Soldto = ? AND SI = ?");
            $stmt3->bind_param('sss', $user, $selectedSoldto, $SalesInvoice);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            if ($result3->num_rows > 0) {
                while ($row3 = $result3->fetch_assoc()) {
                    $SerialNo = $row3['Serialno'];
                    $SI = $row3['SupplierSI'];
                    $ProductName = $row3['Product'];
                    $MyQuantity = $row3['Quantity'];
                    $Type = $row3['Type'];

                    if ($row3['DiscProduct'] == "Yes") {
                        $TotalSales = $row3['DiscNewTotalSRP'];
                    } else {
                        $TotalSales = $row3['TotalSRP'];
                    }
                    
                    $MyCategory = $row3['Category'];
                    $ItemConsign = $row3['itemConsign']  ;
                    $isynBranch = $row3['Branch'];
                    $askiBranch = $row3['Stock'];

                    if ($ItemConsign == "CONSIGNMENT") {
                        $stmt4 = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                        $stmt4->bind_param('ssssss', $SI, $SerialNo, $ProductName, $MyCategory, $askiBranch, $isynBranch);
                        $stmt4->execute();
                        $result4 = $stmt4->get_result();
                        if ($result4->num_rows > 0) {
                            $row4 = $result4->fetch_assoc();
                            $currentQuantity = $row4["Quantity"];
                            $DealerPrice = $row4["DealerPrice"];
                            $ProdSRP = $row4["SRP"];

                            $rcQuantity = $currentQuantity - $MyQuantity;

                            if ($rcQuantity == 0){
                                $stmt = $this->conn->prepare("INSERT INTO tbl_invlistconsignhistory (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                                $stmt->bind_param('ssssss', $SerialNo,$SI,$ProductName,$MyCategory,$askiBranch,$isynBranch);
                                $stmt->execute();
                                $stmt->close();

                                $stmt = $this->conn->prepare("DELETE FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                                $stmt->bind_param('ssssss', $SerialNo,$SI,$ProductName,$MyCategory,$askiBranch,$isynBranch);
                                $stmt->execute();
                                $stmt->close();
                            } else {

                                $Totalprice = floatval($DealerPrice * $rcQuantity);
                                $TSRP = floatval($ProdSRP * $rcQuantity);
                                $MyMark = floatval($ProdSRP * $rcQuantity);
                                $MyTMark = round($MyMark - $Totalprice, 2);

                                if ($Type == "WITH VAT") {
                                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                                    $MySalesVat = floatval($Totalprice) - $MyVat;
                                } else {
                                    $MyVat = 0;
                                    $MySalesVat = $Totalprice;
                                }

                                $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                                $stmt->bind_param('sssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$askiBranch,$isynBranch);
                                $stmt->execute();
                                $stmt->close();
                            }                            
                        }
                        $stmt4->close();

                        // ==================
                        $stmt5 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                        $stmt5->bind_param('sssss', $SI, $SerialNo, $ProductName, $MyCategory, $isynBranch);
                        $stmt5->execute();
                        $result5 = $stmt5->get_result();
                        if ($result5->num_rows > 0) {
                            $row5 = $result5->fetch_assoc();
                            $currentQuantity = $row5["Quantity"];
                            $DealerPrice = $row5["DealerPrice"];
                            $ProdSRP = $row5["SRP"];

                            $rcQuantity = $currentQuantity - $MyQuantity;

                            if ($rcQuantity == 0){
                                $this->ensureProdHistoryTable();
                                $stmt = $this->conn->prepare("INSERT INTO ".$this->prodHistoryTableName." (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                $stmt->execute();
                                $stmt->close();

                                $stmt = $this->conn->prepare("DELETE FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                $stmt->execute();
                                $stmt->close();
                            } else {
                                $Totalprice = floatval($DealerPrice * $rcQuantity);
                                $TSRP = floatval($ProdSRP * $rcQuantity);
                                $MyMark = floatval($ProdSRP * $rcQuantity);
                                $MyTMark = round($MyMark - $Totalprice, 2);

                                if ($Type == "WITH VAT") {
                                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                                    $MySalesVat = floatval($Totalprice) - $MyVat;
                                } else {
                                    $MyVat = 0;
                                    $MySalesVat = $Totalprice;
                                }

                                $stmt = $this->conn->prepare("UPDATE tbl_invlist SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                $stmt->bind_param('ssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                $stmt->execute();
                                $stmt->close();
                            }                            
                        }
                        $stmt5->close();
                    } else {
                        $stmt6 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                        $stmt6->bind_param('sssss', $SI, $SerialNo, $ProductName, $MyCategory, $isynBranch);
                        $stmt6->execute();
                        $result6 = $stmt6->get_result();
                        if ($result6->num_rows > 0) {
                            $row6 = $result6->fetch_assoc();
                            $currentQuantity = $row6["Quantity"];
                            $DealerPrice = $row6["DealerPrice"];
                            $ProdSRP = $row6["SRP"];

                            $rcQuantity = $currentQuantity - $MyQuantity;

                            if ($rcQuantity == 0){
                                $this->ensureProdHistoryTable();
                                $stmt = $this->conn->prepare("INSERT INTO ".$this->prodHistoryTableName." (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                $stmt->execute();
                                $stmt->close();

                                $stmt = $this->conn->prepare("DELETE FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                $stmt->execute();
                                $stmt->close();
                            } else {
                                $Totalprice = floatval($DealerPrice * $rcQuantity);
                                $TSRP = floatval($ProdSRP * $rcQuantity);
                                $MyMark = floatval($ProdSRP * $rcQuantity);
                                $MyTMark = round($MyMark - $Totalprice, 2);

                                if ($Type == "WITH VAT") {
                                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                                    $MySalesVat = floatval($Totalprice) - $MyVat;
                                } else {
                                    $MyVat = 0;
                                    $MySalesVat = $Totalprice;
                                }

                                $stmt = $this->conn->prepare("UPDATE tbl_invlist SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                $stmt->bind_param('ssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                        $stmt6->close();
                    }

                    $SalesNoVat += $TotalSales / 1.12;
                    $SalesWithVat = round($SalesNoVat * $VAT, 2);
                    $AmountDue = round($SalesNoVat + $SalesWithVat, 2);
                }

                $stmt7 = $this->conn->prepare("SELECT DateAdded, SI, Soldto, SUM(CAST(AmountDue AS DECIMAL(15,2))) as forTotal, SUM(CAST(VatSales AS DECIMAL(15,2))) as forSales, SUM(CAST(VAT AS DECIMAL(15,2))) as forVAT, TIN, Address, Status, Branch FROM tbl_transaction WHERE User = ? AND Soldto = ? AND SI = ? GROUP BY SI, User, Branch, Soldto, DateAdded, TIN, Address, Status;");
                $stmt7->bind_param('sss', $user, $selectedSoldto, $SalesInvoice);
                $stmt7->execute();
                $result7 = $stmt7->get_result();
                if ($result7->num_rows > 0) {
                    while ($row7 = $result7->fetch_assoc()) {
                        $SIRef = $row7['SI'];
                        $DateAdded = $row7['DateAdded'];
                        $Total = $row7['forTotal'];
                        $SoldTo = $row7['Soldto'];
                        $Tin = $row7['TIN'];
                        $Address = $row7['Address'];
                        $isynBranch = $row7['Branch'];

                        $sSales = round(floatval($Total) / 1.12, 2);
                        $sVAT = round((floatval($Total) / 1.12) * 0.12, 2);

                        $this->ensureSalesJournalTable();
                        $stmt = $this->conn->prepare("INSERT INTO ".$this->salesJournalTableName." (DateSold,Reference,Customer,GrossSales,VAT,NetSales,TIN,Address,Stock) VALUES (?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param('sssssssss', $DateAdded,$SIRef,$SoldTo,$Total,$sVAT,$sSales,$Tin,$Address,$isynBranch);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $this->conn->prepare("UPDATE tbl_transaction SET VatSales = TotalPrice, VAT = 0, AmountDue = TotalPrice WHERE Type = 'NON-VAT' AND User = ? AND Soldto = ? AND SI = ?");
                    $stmt->bind_param('sss', $user, $selectedSoldto, $SalesInvoice);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $this->conn->prepare("UPDATE tbl_transaction SET VatSales = ((DealerPrice * Quantity)-(round(((DealerPrice/1.12)*0.12),2) * Quantity)),VAT = (round(((DealerPrice/1.12)*0.12),2) * Quantity), AmountDue=TotalPrice WHERE Type = 'WITH VAT' AND User = ? AND Soldto = ? AND SI = ?");
                    $stmt->bind_param('sss', $user, $selectedSoldto, $SalesInvoice);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $this->conn->prepare("INSERT INTO tbl_inventoryout (SI, SupplierSI, Batchno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty, imgname) SELECT SI, SupplierSI, COALESCE(Batchno,'0') AS Batchno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty, imgname FROM tbl_transaction WHERE User = ? AND Soldto = ? AND SI = ?");
                    $stmt->bind_param('sss', $user, $selectedSoldto, $SalesInvoice);
                    $stmt->execute();
                    $rowsOut = $stmt->affected_rows;
                    $stmt->close();
                    if ($rowsOut === 0) {
                        throw new Exception("No rows inserted into tbl_inventoryout for user: ".$user);
                    }

                    $stmt = $this->conn->prepare("DELETE FROM tbl_transaction WHERE User = ? AND Soldto = ? AND SI = ?");
                    $stmt->bind_param('sss', $user, $selectedSoldto, $SalesInvoice);
                    $stmt->execute();
                    $stmt->close();
                }
                $stmt7->close();
            }
            $stmt3->close();
            
            $status = "success";
            $message = "Product details were saved successfully.";

            unset($_SESSION['tableData']);
            unset($_SESSION['SalesNoVAT']);
            unset($_SESSION['SalesWithVAT']);
            unset($_SESSION['SIRef']);
            unset($_SESSION['DateAdded']);
            $_SESSION['tableData'] = json_decode($data['DATA']);
            $_SESSION['SalesNoVAT'] = $SalesNoVat;
            $_SESSION['SalesWithVAT'] = $SalesWithVat;
            $_SESSION['SIRef'] = $SIRef;
            $_SESSION['DateAdded'] = $DateAdded;

            $this->conn->commit();
            
            echo json_encode(array(
                "STATUS" => $status,
                "MESSAGE" => $message,
                // "NOVAT" => $SalesNoVat,
                // "YESVAT" => $SalesWithVat,
                // "TTLSLS" => $TotalSales,
                "SRLNO" => $SerialNo,
                "SI" => $SI,
                "PN" => $ProductName,
                "MYCAT" => $MyCategory,
                "ISYNBRANCH" => $isynBranch,
            ));
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
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