<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $isynBranch = $this->SelectQuery("SELECT DISTINCT Stock FROM tbl_invlist ORDER BY Stock");
        // $prodType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_prodtype ORDER BY Type");
        
        // Fetch Update Types (Parent 134)
        $updateType = $this->SelectQuery("SELECT module FROM tbl_maintenance_module WHERE module_no = 134 AND module_type = 3 AND status = 1 ORDER BY module");

        // Fetch Product Types (Parent 135)
        $prodType = $this->SelectQuery("SELECT module as Type FROM tbl_maintenance_module WHERE module_no = 135 AND module_type = 3 AND status = 1 ORDER BY module");

        echo json_encode(array( 
            "ISYNBRANCH" => $isynBranch,
            "PRODTYPE" => $prodType,
            "UPDATETYPE" => $updateType
        ));
    }

    public function LoadCategory($data){
        $categ = [];
        // Fetch Categories from Maintenance (Parent 136)
        $stmt = $this->conn->prepare("SELECT module as Category FROM tbl_maintenance_module WHERE module_no = 136 AND module_type = 3 AND status = 1 ORDER BY module");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categ[] = $row;
            }
        }

        echo json_encode(array( 
            "CATEG" => $categ,
        ));
    }
    
    public function SearchInventory($data){
        $invList = [];
        $isynbranch = $data['isynBranch'];
        $type = $data['type'];
        $category = $data['category'];

        $stmt = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE Type = ? AND  Category = ? AND Branch = ? ORDER BY Product ASC");
        $stmt->bind_param('sss', $type, $category, $isynbranch);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invList[] = $row;
            }
        }

        echo json_encode(array( 
            "INVLIST" => $invList,
        ));
    }
    
    public function UpdateProduct($data){
        $entries = json_decode($data["DATA"]);
        $updatetype = $data["UPDATETYPE"];

        $changeno = 1;

        $stmt = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigName='CURRENTNO' AND ConfigOwner='CHANGESRP';");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $changeno = $row["Value"];
        }
        $stmt->close();

        $genNo = $this->generateSevenDigitNumber($changeno);
        $TransNo = "SRPAN" . $genNo;

        $vQty = 0;
        $vTotalPrice = 0.0;

        $vTSRP = 0.0;
        $vMark = 0.0;
        $vTMark = 0.0;
        $mySalesVat = 0.0;
        $myVat = 0.0;
        
        $values = "";
        $vVAT = 0.12;

        date_default_timezone_set('Asia/Manila');
        $date = date("m/d/Y", strtotime("now"));

        if ($updatetype == "SRP") {
            $stmt = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigName='CURRENTNO' AND ConfigOwner='CHANGESRP';");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $changeno = $row["Value"];
            }
            $stmt->close();

            $genNo = $this->generateSevenDigitNumber($changeno);
            $TransNo = "SRPAN" . $genNo;

            for ($i=0; $i < count($entries); $i++) {
                $productName = $entries[$i][0];
                $SINo = $entries[$i][1];
                $SerialNo = $entries[$i][2];
                $Quantity = $entries[$i][3];
                $OldPrice = str_replace(",", "", $entries[$i][4]);
                $NewPrice = str_replace(",", "", $entries[$i][5]);
                $Supplier = $entries[$i][6];
                $Category = $entries[$i][7];
                $Type = $entries[$i][8];
                $Branch = $entries[$i][9];
                $DealerPrice = str_replace(",", "", $entries[$i][10]);

                $vQty = $Quantity;
                $vTotalPrice = floatval($DealerPrice * $vQty);
                $vTSRP = floatval($NewPrice * $vQty);
                $vMark = floatval($NewPrice - $DealerPrice);
                $vTMark = $vMark * $vQty;

                if ($Type == "WITH VAT") {
                    $mySalesVat = floatval($DealerPrice / 1.12);
                    $myVat = $mySalesVat * $vVAT;
                    $mySalesVat = $mySalesVat * $vQty;
                    $myVat = $myVat * $vQty;
                } else {
                    $mySalesVat = $vTotalPrice;
                    $myVat = 0;
                }

                $stmt = $this->conn->prepare("UPDATE tbl_invlist SET SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ? WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Stock = ? AND Branch = ?");
                $stmt->bind_param('ssssssssss', $NewPrice, $vTSRP, $vMark, $vTMark, $SerialNo, $SINo, $productName, $Category, $Branch, $Branch);
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("UPDATE tbl_inventoryin SET SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ? WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Stock = ? AND Branch = ?");
                $stmt->bind_param('ssssssssss', $NewPrice, $vTSRP, $vMark, $vTMark, $SerialNo, $SINo, $productName, $Category, $Branch, $Branch);
                $stmt->execute();
                $stmt->close();

                // Check if the product is in consignment ================================
                $stmt = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Branch = ?");
                $stmt->bind_param('sssss', $SerialNo, $SINo, $productName, $Category, $Branch);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, TotalPrice = ?, Quantity = ?, Vat = ?, VatSales = ? WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Branch = ?");
                    $stmt->bind_param('sssssssssssss', $NewPrice, $vTSRP, $vMark, $vTMark, $vTotalPrice, $vQty, $myVat, $mySalesVat, $SerialNo, $SINo, $productName, $Category, $Branch);
                    $stmt->execute();
                    $stmt->close();
                }
                $stmt->close();

                $toDP = 0;
                $oldTotalPrice =floatval($DealerPrice * $Quantity);
                $oldTotalSRP =floatval($OldPrice * $Quantity);

                $ChangeType = "SRP";
                $ChangeStatus = ($OldPrice < $NewPrice) ? "INCREASE" : "DECREASE";
                $AmountDif = $vTSRP - floatval($OldPrice * $Quantity);

                $stmt = $this->conn->prepare("INSERT INTO tbl_srp_dp_producthistory (SIno, SerialNo, Product, Supplier, Category, Type, Qty, FromDP, ToDP, OldTotalPrice, NewTotalPrice, FromSRP, toSRP, OldTotalSRP, NewTotalSRP, VatSales, Vat, DateChange, iSynBranch, TransNo, ChangeType, ChangeStatus, AmountDif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssssssssssssssssssss', $SINo, $SerialNo, $productName, $Supplier, $Category, $Type, $vQty, $DealerPrice, $toDP, $oldTotalPrice, $vTotalPrice, $OldPrice, $NewPrice, $oldTotalSRP, $vTSRP, $mySalesVat, $myVat, $date, $Branch, $TransNo, $ChangeType, $ChangeStatus, $AmountDif);
                $stmt->execute();
                $stmt->close();

            }

            $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = Value + 1 WHERE ConfigName='CURRENTNO' AND ConfigOwner='CHANGESRP';");
            $stmt->execute();
            $stmt->close();

            $status = "SUCCESS";
            $message = "Product SRP Updated.";
            
        } else if ($updatetype = "DP") {
            $stmt = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigName='CURRENTNO' AND ConfigOwner='CHANGEDP';");
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $changeno = $row["Value"];
            }
            $stmt->close();

            $genNo = $this->generateSevenDigitNumber($changeno);
            $TransNo = "DPAN" . $genNo;

            for ($i=0; $i < count($entries); $i++) {
                $productName = $entries[$i][0];
                $SINo = $entries[$i][1];
                $SerialNo = $entries[$i][2];
                $Quantity = $entries[$i][3];
                $OldPrice = str_replace(",", "", $entries[$i][4]);
                $NewPrice = str_replace(",", "", $entries[$i][5]);
                $Supplier = $entries[$i][6];
                $Category = $entries[$i][7];
                $Type = $entries[$i][8];
                $Branch = $entries[$i][9];
                $SRP = str_replace(",", "", $entries[$i][10]);

                $vQty = $Quantity;
                $vTotalPrice = floatval($NewPrice * $vQty);
                $vTSRP = floatval($SRP * $vQty);
                $vMark = floatval($SRP - $NewPrice);
                $vTMark = $vMark * $vQty;

                if ($Type == "WITH VAT") {
                    $mySalesVat = floatval($NewPrice / 1.12);
                    $myVat = $mySalesVat * $vVAT;
                    $mySalesVat = $mySalesVat * $vQty;
                    $myVat = $myVat * $vQty;
                } else {
                    $mySalesVat = $vTotalPrice;
                    $myVat = 0;
                }

                $stmt = $this->conn->prepare("UPDATE tbl_invlist SET DealerPrice = ?, Markup = ?, TotalMarkup = ?, TotalPrice = ? WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Stock = ? AND Branch = ?");
                $stmt->bind_param('ssssssssss', $NewPrice, $vMark, $vTMark, $vTotalPrice, $SerialNo, $SINo, $productName, $Category, $Branch, $Branch);
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("UPDATE tbl_inventoryin SET DealerPrice = ?, Markup = ?, TotalMarkup = ?, TotalPrice = ? WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Stock = ? AND Branch = ?");
                $stmt->bind_param('ssssssssss', $NewPrice, $vMark, $vTMark, $vTotalPrice, $SerialNo, $SINo, $productName, $Category, $Branch, $Branch);
                $stmt->execute();
                $stmt->close();

                // Check if the product is in consignment ================================
                $stmt = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Branch = ?");
                $stmt->bind_param('sssss', $SerialNo, $SINo, $productName, $Category, $Branch);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET TotalSRP = ?, DealerPrice = ?, Markup = ?, TotalMarkup = ?, TotalPrice = ?, Quantity = ?, Vat = ?, VatSales = ? WHERE Serialno = ? AND SIno = ? AND Product = ? AND Category = ? AND Branch = ?");
                    $stmt->bind_param('sssssssssssss', $vTSRP, $NewPrice, $vMark, $vTMark, $vTotalPrice, $vQty, $myVat, $mySalesVat, $SerialNo, $SINo, $productName, $Category, $Branch);
                    $stmt->execute();
                    $stmt->close();
                }
                $stmt->close();

                $toSRP = 0;
                $oldTotalPrice =floatval($OldPrice * $Quantity);
                $oldTotalSRP =floatval($SRP * $Quantity);

                $ChangeType = "DP";
                $ChangeStatus = ($OldPrice < $NewPrice) ? "INCREASE" : "DECREASE";
                $AmountDif = $vTSRP - floatval($OldPrice * $Quantity);

                $stmt = $this->conn->prepare("INSERT INTO tbl_srp_dp_producthistory (SIno, SerialNo, Product, Supplier, Category, Type, Qty, FromDP, ToDP, OldTotalPrice, NewTotalPrice, FromSRP, toSRP, OldTotalSRP, NewTotalSRP, VatSales, Vat, DateChange, iSynBranch, TransNo, ChangeType, ChangeStatus, AmountDif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssssssssssssssssssss', $SINo, $SerialNo, $productName, $Supplier, $Category, $Type, $vQty, $OldPrice, $NewPrice, $oldTotalPrice, $vTotalPrice, $SRP, $toSRP, $oldTotalSRP, $vTSRP, $mySalesVat, $myVat, $date, $Branch, $TransNo, $ChangeType, $ChangeStatus, $AmountDif);
                $stmt->execute();
                $stmt->close();
            }

            $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = Value + 1 WHERE ConfigName='CURRENTNO' AND ConfigOwner='CHANGEDP';");
            $stmt->execute();
            $stmt->close();

            $status = "SUCCESS";
            $message = "Product Dealer's Price Updated.";
        } else {
            $status = "WARNING";
            $message = "Unknown update type. Please reload/refresh the module.";
        }


        echo json_encode(array( 
            "STATUS" => $status,
            "MESSAGE" => $message,
            "ENTRIES" => $entries,
            "UPDATETYPE" => $updatetype,
            "VALUES" => $values,
            "NPRIce" => $NewPrice,
            "qty" => $Quantity,
        ));
    }

    // ===================================
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

    private function generateSevenDigitNumber($userInput) {
        // Convert to string and pad with leading zeros to 7 digits
        return str_pad($userInput, 7, '0', STR_PAD_LEFT);
    }
}
