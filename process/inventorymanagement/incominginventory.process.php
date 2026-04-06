<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $branch = $this->SelectQuery("SELECT DISTINCT ItemName FROM tbl_maintenance WHERE ItemType='BRANCH'");
        $prodtype = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_prodtype ORDER BY Type");

        echo json_encode(array( 
            "BRANCH" => $branch,
            "PRODTYPE" => $prodtype,
        ));
    }

    public function LoadDataInventory(){
        // Sort by DateAdded (assuming m/d/Y string format) and fallback to alphabetical/natural order
        $datainv = $this->SelectQuery("SELECT * FROM tbl_inventoryin ORDER BY STR_TO_DATE(DateAdded, '%m/%d/%Y') DESC, DateAdded DESC LIMIT 1000");
        $datainvSINo = $this->SelectQuery("SELECT DISTINCT SINo FROM tbl_inventoryin ORDER BY DateAdded DESC LIMIT 1000");

        echo json_encode(array( 
            "DATAINV" => $datainv,
            "DATAINVSINO" => $datainvSINo,
        ));
    }

    public function DeleteFromDataInv($data){
        
        $SINo = $data["SINo"];
        $SerialNo = $data["SerialNo"];
        $Product = $data["Product"];
        $Product = html_entity_decode($Product, ENT_QUOTES, 'UTF-8');

        
        $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product, imgname FROM tbl_inventoryin WHERE SIno = ? AND Serialno = ? AND Product = ?");
        $stmt->bind_param('sss', $SINo,$SerialNo,$Product);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && !empty($row['imgname'])) {
            $file = "../../" . $row['imgname'];
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $stmt = $this->conn->prepare("DELETE FROM tbl_inventoryin WHERE SIno = ? AND Serialno = ? AND Product = ?");
        $stmt->bind_param('sss', $SINo,$SerialNo,$Product);
        $stmt->execute();
        $result1 = $stmt->affected_rows;
        $stmt->close();

        // Removed due to changes in the process flow - Nico
        // $stmt = $this->conn->prepare("DELETE FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ?");
        // $stmt->bind_param('sss', $SINo,$SerialNo,$Product);
        // $stmt->execute();
        // $result2 = $stmt->affected_rows;
        // $stmt->close();
        
        // if ($result1 === 0 && $result2 === 0) {
        if ($result1 === 0) {
            $status = "error";
            $message = "Failed to deleted inventory [".$SINo." | ".$SerialNo." | ".$Product."].";
        } else {
            $status = "success";
            $message = "Deleted Inventory [".$SINo." | ".$SerialNo." | ".$Product."].";
        }

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "DATA" => $data,
        ));
    }

    public function LoadProdCateg($data){
        $prodcateg = [];
        $type = $data['type'];
        $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_prodtype WHERE UPPER(Type) = UPPER(?) ORDER BY Category");
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $prodcateg[] = $row;
            }
        }

        echo json_encode(array( 
            "PRODCATEG" => $prodcateg,
        ));
    }

    public function LoadProdName($data){
        $product = [];
        $categ = trim($data['categ']);
        $stmt = $this->conn->prepare("SELECT DISTINCT Product FROM tbl_prodtype WHERE UPPER(Category) = UPPER(?) ORDER BY Product");
        $stmt->bind_param('s', $categ);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product[] = $row;
            }
        } else {
            $stmt2 = $this->conn->prepare("SELECT DISTINCT Product FROM tbl_inventoryin WHERE UPPER(Category) = UPPER(?) ORDER BY Product");
            $stmt2->bind_param('s', $categ);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows > 0) {
                while ($row2 = $res2->fetch_assoc()) {
                    $product[] = $row2;
                }
            } else {
                $stmt3 = $this->conn->prepare("SELECT DISTINCT Product FROM tbl_invlist WHERE UPPER(Category) = UPPER(?) ORDER BY Product");
                $stmt3->bind_param('s', $categ);
                $stmt3->execute();
                $res3 = $stmt3->get_result();
                if ($res3->num_rows > 0) {
                    while ($row3 = $res3->fetch_assoc()) {
                        $product[] = $row3;
                    }
                }
                $stmt3->close();
                if (empty($product)) {
                    $stmt4 = $this->conn->prepare("SELECT DISTINCT Product FROM tbl_prodtype ORDER BY Product");
                    $stmt4->execute();
                    $res4 = $stmt4->get_result();
                    if ($res4->num_rows > 0) {
                        while ($row4 = $res4->fetch_assoc()) {
                            $product[] = $row4;
                        }
                    }
                    $stmt4->close();
                }
            }
            $stmt2->close();
        }
        $stmt->close();

        echo json_encode(array( 
            "PRODUCT" => $product,
        ));
    }

    public function LoadSupplier($data){
        $supplier = [];
        $product = trim($data['productname']);
        $stmt = $this->conn->prepare("SELECT DISTINCT Supplier FROM tbl_prodtype WHERE UPPER(Product) = UPPER(?) ORDER BY Supplier");
        $stmt->bind_param('s', $product);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $supplier[] = $row;
            }
        } else {
            $stmt2 = $this->conn->prepare("SELECT DISTINCT Supplier FROM tbl_inventoryin WHERE UPPER(Product) = UPPER(?) ORDER BY Supplier");
            $stmt2->bind_param('s', $product);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows > 0) {
                while ($row2 = $res2->fetch_assoc()) {
                    $supplier[] = $row2;
                }
            } else {
                $stmt3 = $this->conn->prepare("SELECT DISTINCT Supplier FROM tbl_invlist WHERE UPPER(Product) = UPPER(?) ORDER BY Supplier");
                $stmt3->bind_param('s', $product);
                $stmt3->execute();
                $res3 = $stmt3->get_result();
                if ($res3->num_rows > 0) {
                    while ($row3 = $res3->fetch_assoc()) {
                        $supplier[] = $row3;
                    }
                }
                $stmt3->close();
                if (empty($supplier)) {
                    $stmt4 = $this->conn->prepare("SELECT DISTINCT Supplier FROM tbl_prodtype ORDER BY Supplier");
                    $stmt4->execute();
                    $res4 = $stmt4->get_result();
                    if ($res4->num_rows > 0) {
                        while ($row4 = $res4->fetch_assoc()) {
                            $supplier[] = $row4;
                        }
                    }
                    $stmt4->close();
                }
            }
            $stmt2->close();
        }
        $stmt->close();

        echo json_encode(array( 
            "SUPPLIER" => $supplier,
        ));
    }
    
    public function LoadSupplierSI($data){
        $supplierSI = [];
        $product = $data['product'];
        $supplier = $data['supplier'];
        $stmt = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_inventoryin WHERE UPPER(Product) = UPPER(?) AND UPPER(Supplier) = UPPER(?) ORDER BY SIno");
        $stmt->bind_param('ss', $product, $supplier);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $supplierSI[] = $row;
            }
        } else {
            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlist WHERE UPPER(Product) = UPPER(?) AND UPPER(Supplier) = UPPER(?) ORDER BY SIno");
            $stmt2->bind_param('ss', $product, $supplier);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows > 0) {
                while ($row2 = $res2->fetch_assoc()) {
                    $supplierSI[] = $row2;
                }
            }
            $stmt2->close();
        }
        echo json_encode(array(
            "SUPPLIERSI" => $supplierSI,
        ));
    }
    
    public function GetProductPricing($data){
        $pricing = [];
        $product = $data['product'];
        $supplier = $data['supplier'];
        
        $stmt = $this->conn->prepare("SELECT DealerPrice, SRP FROM tbl_inventoryin WHERE UPPER(Product) = UPPER(?) AND UPPER(Supplier) = UPPER(?) ORDER BY DateAdded DESC LIMIT 1");
        $stmt->bind_param('ss', $product, $supplier);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $pricing = $row;
        } else {
            // fallback to invlist if inventoryin has no match
            $stmt2 = $this->conn->prepare("SELECT DealerPrice, SRP FROM tbl_invlist WHERE UPPER(Product) = UPPER(?) AND UPPER(Supplier) = UPPER(?) ORDER BY DateAdded DESC LIMIT 1");
            $stmt2->bind_param('ss', $product, $supplier);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2->num_rows > 0) {
                $row2 = $res2->fetch_assoc();
                $pricing = $row2;
            }
            $stmt2->close();
        }
        $stmt->close();
        
        echo json_encode(array(
            "PRICING" => $pricing,
        ));
    }

    public function SaveSingle($data){
        try {
            $this->conn->autocommit(false);

            $Vat = 0;
            $VatSales = 0;
            $AmountDue = 0;

            $branch = $data['branch'];
            $type = $data['type'];
            $categ = $data['categ'];
            $product = $data['product'];
            $supplier = $data['supplier'];
            $supplierSI = $data['supplierSI'];
            $serialNo = $data['serialNo'];
            $purchaseDate = date("m/d/Y",strtotime($data['purchaseDate']));
            $warranty = $data['warranty'];
            
            $dateEncoded = date("m/d/Y",strtotime($data['dateEncoded']));
            $dealerPrice = str_replace(",", "", $data['dealerPrice']);
            $srp = str_replace(",", "", $data['srp']);
            $quantity = $data['quantity'];
            $totalPrice = str_replace(",", "", $data['totalPrice']);
            $totalSRP = str_replace(",", "", $data['totalSRP']);
            $mpi = str_replace(",", "", $data['mpi']);
            $totalMarkup = str_replace(",", "", $data['totalMarkup']);

            // Product Image saving
            $targetFolder = "../../files/productimages/";
            // $imageName = $data['imageName'];

            // Create folder if not exists
            if (!file_exists($targetFolder)) {
                mkdir($targetFolder, 0777, true);
            }

            $imageName = null; // Default null if no image uploaded

            if (isset($_FILES['imageName']) && $_FILES['imageName']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['imageName']['tmp_name'];
                $originalFileName = $_FILES['imageName']['name'];
                $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

                // Allowed formats
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, and PNG files are allowed.");
                }

                // File size limit (2MB)
                $fileSize = $_FILES['imageName']['size'];
                if ($fileSize > 2 * 1024 * 1024) { // 2MB
                    throw new Exception("File too large. Maximum allowed size is 2MB.");
                }

                // Unique file name
                $newFileName = $product.'_'. $supplierSI . '_'. $serialNo . '.' . $fileExtension;
                $destPath = $targetFolder . $newFileName;

                if (!move_uploaded_file($fileTmpPath, $destPath)) {
                    throw new Exception("Failed to move uploaded file.");
                }

                $imageName = "files/productimages/" . $newFileName; // relative path to store in DB
            }

            $user = isset($_SESSION['USERNAME']) ? $_SESSION['USERNAME'] : 'SYSTEM';
            $ProdPend = 'YES';

            if ($type === "WITH VAT") {
                $Vat = round((($dealerPrice / 1.12) * 0.12), 2) * $quantity;;
                $VatSales = $totalPrice - $Vat;
                $AmountDue = $totalPrice;
            } else {
                $Vat = $totalPrice;
                $VatSales = 0;
                $AmountDue = $totalPrice;
            }

            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));
    
            $stmt1 = $this->conn->prepare("INSERT INTO tbl_inventoryin (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt1->bind_param('sssssssssssssssssssssssss', $supplierSI,$serialNo,$product,$supplier,$categ,$type,$quantity,$dealerPrice,$totalPrice,$srp,$totalSRP,$mpi,$totalMarkup,$VatSales,$Vat,$AmountDue,$dateEncoded,$purchaseDate,$user,$AsOf,$ProdPend,$branch,$branch,$warranty,$imageName);
            if (!$stmt1->execute()) {
                throw new Exception("Execute failed: ".$stmt1->error);
            }

            // Removed due to changes in the process flow - Nico
            // $stmt2 = $this->conn->prepare("INSERT INTO tbl_invlist (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            // $stmt2->bind_param('sssssssssssssssssssssssss', $supplierSI,$serialNo,$product,$supplier,$categ,$type,$quantity,$dealerPrice,$totalPrice,$srp,$totalSRP,$mpi,$totalMarkup,$VatSales,$Vat,$AmountDue,$dateEncoded,$purchaseDate,$user,$AsOf,$ProdPend,$branch,$branch,$warranty,$imageName);
            // $stmt2->execute();
            
            $this->conn->commit();

            $status = "success";
            $message = "Product details were saved successfully.";
    
            echo json_encode(array(
                "STATUS" => $status,
                "MESSAGE" => $message,
                "DATA" => $data,
                "VAT" => $Vat,
                "VATSALES" => $VatSales,
                "AMTDUE" => $AmountDue,
            ));
            
            $stmt1->close();
            // $stmt2->close();
    
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }

    public function UpdateInventory($data){
        try {
            $this->conn->autocommit(false);

            $Vat = 0;
            $VatSales = 0;
            $AmountDue = 0;

            // New Values
            $branch = $data['branch'];
            $type = $data['type'];
            $categ = $data['categ'];
            $product = $data['product'];
            $supplier = $data['supplier'];
            $supplierSI = $data['supplierSI'];
            $serialNo = $data['serialNo'];
            $purchaseDate = date("m/d/Y",strtotime($data['purchaseDate']));
            $warranty = $data['warranty'];
            
            $dateEncoded = date("m/d/Y",strtotime($data['dateEncoded']));
            $dealerPrice = str_replace(",", "", $data['dealerPrice']);
            $srp = str_replace(",", "", $data['srp']);
            $quantity = $data['quantity'];
            $totalPrice = str_replace(",", "", $data['totalPrice']);
            $totalSRP = str_replace(",", "", $data['totalSRP']);
            $mpi = str_replace(",", "", $data['mpi']);
            $totalMarkup = str_replace(",", "", $data['totalMarkup']);

            // Original Keys for WHERE clause
            $origSIno = $data['origSIno'];
            $origSerial = $data['origSerial'];
            $origProduct = $data['origProduct'];
            $origProduct = html_entity_decode($origProduct, ENT_QUOTES, 'UTF-8');

            // Validate the original record exists; otherwise the update will affect 0 rows and look like "nothing happened"
            $oldImg = null;
            $stmt0 = $this->conn->prepare("SELECT imgname FROM tbl_inventoryin WHERE SIno = ? AND Serialno = ? AND Product = ? LIMIT 1");
            if (!$stmt0) { throw new Exception("Prepare failed: ".$this->conn->error); }
            $stmt0->bind_param('sss', $origSIno, $origSerial, $origProduct);
            $stmt0->execute();
            $res0 = $stmt0->get_result();
            if (!$res0 || $res0->num_rows === 0) {
                throw new Exception("Record not found. Please reselect the item from Data Inventory then try updating again.");
            }
            $row0 = $res0->fetch_assoc();
            $oldImg = $row0 ? $row0['imgname'] : null;
            $stmt0->close();

            // Product Image saving
            $targetFolder = "../../files/productimages/";
            $imageName = null;

            if (isset($_FILES['imageName']) && $_FILES['imageName']['error'] === UPLOAD_ERR_OK) {
                // ... (Same image upload logic as SaveSingle)
                 // Create folder if not exists
                if (!file_exists($targetFolder)) {
                    mkdir($targetFolder, 0777, true);
                }

                $fileTmpPath = $_FILES['imageName']['tmp_name'];
                $originalFileName = $_FILES['imageName']['name'];
                $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception("Invalid file type. Only JPG, JPEG, and PNG files are allowed.");
                }

                $fileSize = $_FILES['imageName']['size'];
                if ($fileSize > 2 * 1024 * 1024) { 
                    throw new Exception("File too large. Maximum allowed size is 2MB.");
                }

                $newFileName = $product.'_'. $supplierSI . '_'. $serialNo . '.' . $fileExtension;
                $destPath = $targetFolder . $newFileName;

                if (!move_uploaded_file($fileTmpPath, $destPath)) {
                    throw new Exception("Failed to move uploaded file.");
                }

                $imageName = "files/productimages/" . $newFileName;
            }

            $user = isset($_SESSION['USERNAME']) ? $_SESSION['USERNAME'] : 'SYSTEM';
            $ProdPend = 'YES';

            if ($type === "WITH VAT") {
                $Vat = round((($dealerPrice / 1.12) * 0.12), 2) * $quantity;;
                $VatSales = $totalPrice - $Vat;
                $AmountDue = $totalPrice;
            } else {
                $Vat = $totalPrice;
                $VatSales = 0;
                $AmountDue = $totalPrice;
            }

            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));

            // Construct Update Query
            $sql = "UPDATE tbl_inventoryin SET SIno=?, Serialno=?, Product=?, Supplier=?, Category=?, Type=?, Quantity=?, DealerPrice=?, TotalPrice=?, SRP=?, TotalSRP=?, Markup=?, TotalMarkup=?, VatSales=?, Vat=?, AmountDue=?, DateAdded=?, DatePurchase=?, User=?, AsOf=?, ProdPend=?, Stock=?, Branch=?, Warranty=?";
            
            $params = [$supplierSI,$serialNo,$product,$supplier,$categ,$type,$quantity,$dealerPrice,$totalPrice,$srp,$totalSRP,$mpi,$totalMarkup,$VatSales,$Vat,$AmountDue,$dateEncoded,$purchaseDate,$user,$AsOf,$ProdPend,$branch,$branch,$warranty];
            $types = "ssssssssssssssssssssssss";

            if ($imageName) {
                $sql .= ", imgname=?";
                $params[] = $imageName;
                $types .= "s";
            }

            $sql .= " WHERE SIno=? AND Serialno=? AND Product=?";
            $params[] = $origSIno;
            $params[] = $origSerial;
            $params[] = $origProduct;
            $types .= "sss";

            $stmt1 = $this->conn->prepare($sql);
            $stmt1->bind_param($types, ...$params);
            if (!$stmt1->execute()) {
                throw new Exception("Execute failed: ".$stmt1->error);
            }

            // If we uploaded a new image, remove the old one (best-effort)
            if ($imageName && $oldImg && $oldImg !== $imageName) {
                $file = "../../" . $oldImg;
                if (file_exists($file)) { @unlink($file); }
            }
            
            $this->conn->commit();

            $status = "success";
            $message = "Product details were updated successfully.";
    
            echo json_encode(array(
                "STATUS" => $status,
                "MESSAGE" => $message,
            ));
            
            $stmt1->close();
    
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }

    public function SaveAll($data){
        try {
            // Increase time limit for large bulk inserts
            set_time_limit(300); // 5 minutes
            
            // error_log("SaveAll called with data: " . print_r($data, true)); // Debug log

            $this->conn->autocommit(false);

            $entries = json_decode($data["DATA"]);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON data: " . json_last_error_msg());
            }

            if (empty($entries)) {
                throw new Exception("No data to save.");
            }

            $user = isset($_SESSION['USERNAME']) ? $_SESSION['USERNAME'] : 'SYSTEM';
            $ProdPend = 'YES';
            $AsOf = date("m/d/Y", strtotime("now")); 
            date_default_timezone_set('Asia/Manila');

            // Initialize variables to avoid bind_param issues
            $supplierSI = "";
            $serialNo = "";
            $product = "";
            $supplier = "";
            $categ = "";
            $type = "";
            $quantity = 0;
            $dealerPrice = 0;
            $totalPrice = 0;
            $srp = 0;
            $totalSRP = 0;
            $mpi = 0;
            $totalMarkup = 0;
            $VatSales = 0;
            $Vat = 0;
            $AmountDue = 0;
            $dateEncoded = "";
            $purchaseDate = "";
            $branch = "";
            $warranty = "";
            $imageName = null;

            $stmt1 = $this->conn->prepare("INSERT INTO tbl_inventoryin (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

            if (!$stmt1) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            // Bind variables (passed by reference)
            $stmt1->bind_param('sssssssssssssssssssssssss', 
                $supplierSI, $serialNo, $product, $supplier, $categ, $type, $quantity, $dealerPrice, 
                $totalPrice, $srp, $totalSRP, $mpi, $totalMarkup, $VatSales, $Vat, $AmountDue, 
                $dateEncoded, $purchaseDate, $user, $AsOf, $ProdPend, $branch, $branch, $warranty, $imageName
            );

            foreach ($entries as $entry) {
                // Ensure array keys exist (DataTables array usually indexed 0..N)
                // Mapping based on itemTbl columns
                $product = $entry[0] ?? '';
                $serialNo = $entry[1] ?? '';
                $warranty = $entry[2] ?? '';
                $dealerPriceStr = $entry[3] ?? '0';
                $srpStr = $entry[4] ?? '0';
                $quantity = $entry[5] ?? 0;
                $totalPriceStr = $entry[6] ?? '0';
                $totalSRPStr = $entry[7] ?? '0';
                $mpiStr = $entry[8] ?? '0';
                $totalMarkupStr = $entry[9] ?? '0';
                $branch = $entry[10] ?? '';
                $type = $entry[11] ?? '';
                $categ = $entry[12] ?? '';
                $supplier = $entry[13] ?? '';
                $supplierSI = $entry[14] ?? '';
                $purchaseDateStr = $entry[15] ?? '';
                // $imageNameRaw = $entry[16] ?? ''; 
                $imageName = null; // SaveAll does not support image upload yet
                $dateEncodedStr = $entry[17] ?? '';

                $purchaseDate = date("m/d/Y", strtotime($purchaseDateStr));
                $dateEncoded = date("m/d/Y", strtotime($dateEncodedStr));
                
                $dealerPrice = str_replace(",", "", $dealerPriceStr);
                $srp = str_replace(",", "", $srpStr);
                $totalPrice = str_replace(",", "", $totalPriceStr);
                $totalSRP = str_replace(",", "", $totalSRPStr);
                $mpi = str_replace(",", "", $mpiStr);
                $totalMarkup = str_replace(",", "", $totalMarkupStr);

                if ($type === "WITH VAT") {
                    $Vat = round((($dealerPrice / 1.12) * 0.12), 2) * $quantity;
                    $VatSales = $totalPrice - $Vat;
                    $AmountDue = $totalPrice;
                } else {
                    $Vat = $totalPrice;
                    $VatSales = 0;
                    $AmountDue = $totalPrice;
                }

                if (!$stmt1->execute()) {
                     throw new Exception("Execute failed for item $product: " . $stmt1->error);
                }
            }
            
            $this->conn->commit();

            $status = "success";
            $message = "All Product details were saved successfully.";
    
            echo json_encode(array(
                "STATUS" => $status,
                "MESSAGE" => $message,
                "DATA" => $data,
            ));
            
            $stmt1->close();
            // $stmt2->close();
    
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            // error_log("SaveAll Error: " . $e->getMessage());
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }

    public function PrintSupplierSalesInvoice ($data) {
        try {
            $this->conn->autocommit(false);
            $products = [];
            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));
            $ProdPend = "NO";

            $stmt = $this->conn->prepare("SELECT Quantity, DateAdded, SIno, Supplier, SUM(TotalPrice) AS forTotal, SUM(Vat) AS forVAT, SUM(VatSales) AS forSVat, Stock, Branch, User FROM tbl_inventoryin WHERE ProdPend = 'YES' AND STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y') GROUP BY SIno, Supplier, Stock, Branch, DateAdded, User");
            if (!$stmt) { throw new \Exception("Prepare failed: ".$this->conn->error); }
            $stmt->bind_param('s', $AsOf);
            $stmt->execute();
            $result = $stmt->get_result();
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

                $canJournal = $this->tableExists('tbl_purchasejournal');
                if ($canJournal) {
                    $stmt1 = $this->conn->prepare("INSERT INTO tbl_purchasejournal (DatePurchase, Reference, Supplier, GrossPurchase, InputVAT, NetPurchase, Stock, Branch) VALUES (?,?,?,?,?,?,?,?)");
                    if (!$stmt1) { throw new \Exception("Prepare failed: ".$this->conn->error); }
                    $stmt1->bind_param('ssssssss', $purchaseDate, $SINo, $supplier, $total, $vat, $svat, $stock, $branch);
                    $stmt1->execute();
                    $stmt1->close();
                }

                $stmt2 = $this->conn->prepare("SELECT tinNumber, fullAddress FROM tbl_supplier_info WHERE supplierName = ?");
                if ($stmt2) {
                    $stmt2->bind_param('s', $supplier);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($result2 && $result2->num_rows > 0) {
                        $row2 = $result2->fetch_assoc();
                        $tin = $row2['tinNumber'];
                        $address = $row2['fullAddress'];
                        
                        if ($canJournal) {
                            $stmt3 = $this->conn->prepare("UPDATE tbl_purchasejournal SET TIN = ?,  Address = ? WHERE Supplier = ? AND TIN = '-' AND Address = '-'");
                            if ($stmt3) {
                                $stmt3->bind_param('sss', $tin, $address, $supplier);
                                $stmt3->execute();
                                $stmt3->close();
                            }
                        }
                    }
                    $stmt2->close();
                }
            }
            $stmt->close();

            $stmt4 = $this->conn->prepare("INSERT INTO tbl_invlist (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ?, Stock, Branch, Warranty, imgname FROM tbl_inventoryin WHERE ProdPend = 'YES' AND STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y')");
            if (!$stmt4) { throw new \Exception("Prepare failed: ".$this->conn->error); }
            $stmt4->bind_param('ss', $ProdPend, $AsOf);
            $stmt4->execute();
            $stmt4->close();

            $stmt5 = $this->conn->prepare("UPDATE tbl_inventoryin SET ProdPend = 'NO' WHERE ProdPend = 'YES'");
            if (!$stmt5) { throw new \Exception("Prepare failed: ".$this->conn->error); }
            $stmt5->execute();
            $stmt5->close();

            $tableData = json_decode($data['DATA']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid DATA payload: ".json_last_error_msg());
            }
            unset($_SESSION['tableData']);
            $_SESSION['tableData'] = $tableData;

            $this->conn->commit();
            echo json_encode(array("STATUS"=>"success","PRODS"=>$products));
            $this->conn->autocommit(true);
        } catch (\Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS"=>"ERROR","MESSAGE"=>$e->getMessage()));
        }
    }
    
    public function tableExists($table){
        $result = $this->conn->query("SHOW TABLES LIKE '".$this->conn->real_escape_string($table)."'");
        return $result && $result->num_rows > 0;
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
