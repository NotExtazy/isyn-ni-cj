<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $invoutList = [];
        date_default_timezone_set('Asia/Manila');
        $currentDate = date("m/d/Y", strtotime("now"));

        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryout WHERE Product != 'CANCELLED' AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y') ORDER BY STR_TO_DATE(DateAdded,'%m/%d/%Y') DESC");
        $stmt->bind_param('s', $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoutList[] = $row;
            }
        }

        echo json_encode(array( 
            "INVOUTLIST" => $invoutList,
        ));
    }
    
    public function LoadTransactionsOnDate($data){
        $invoutList = [];
        $selectedDate = $data['date'];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryout WHERE Product != 'CANCELLED' AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y') ORDER BY STR_TO_DATE(DateAdded,'%m/%d/%Y') DESC");
        $stmt->bind_param('s', $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoutList[] = $row;
            }
        }

        echo json_encode(array( 
            "INVOUTLIST" => $invoutList,
        ));
    }
    
    public function LoadTransactionDetails($data){
        $details = "";
        $si = $data['si'];
        $productname = $data['productName'];
        $soldto = $data['soldto'];
        $datesold = $data['datesold'];
        $qry = "SELECT * FROM tbl_inventoryout WHERE SI = '".$si."' AND Product = '".$productname."' AND Soldto = '".$soldto."' AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE('".$datesold."','%m/%d/%Y')";
        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryout WHERE SI = ? AND Product = ? AND Soldto = ? AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
        $stmt->bind_param('ssss', $si, $productname, $soldto, $datesold);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $details = $row;
        }

        echo json_encode(array( 
            "DETAILS" => $details,
            "QRY" => $qry,
        ));
    }
    
    public function CancelSI($data){    
        $dateInvOut = $data['dateSold'];
        $soldTo = $data['soldTo'];
        $sino = $data['sino'];
        $cancelReason = $data['cancelReason'];
        date_default_timezone_set('Asia/Manila');
        $currentDate = date("m/d/Y", strtotime("now"));

        $iQuantity2 = 0;
        $vvDP2 = 0.0;
        $vvTDP2 = 0.0;
        $vvSRP2 = 0.0;
        $vvTSRP2 = 0.0;
        $vvMU2 = 0.0;
        $vvTMU2 = 0.0;

        $stmt = $this->conn->prepare("INSERT INTO tbl_cancelsireason (DateCancel, DateInvOut, SIno, CustomerName, Reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $currentDate, $dateInvOut, $sino, $soldTo, $cancelReason);
        $stmt->execute();
        $stmt->close();

        $invoutList = [];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryout WHERE SI = ? AND SOLDTO = ? AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
        $stmt->bind_param('sss', $sino, $soldTo, $dateInvOut);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoutList[] = $row;

                $ssProduct = $row['Product'];
                $ssSupSI = $row['SupplierSI'];
                $ssSerial = $row['Serialno'];
                $ssSupplier = $row['Supplier'];
                $iQuantity = $row['Quantity'];
                $vvDP = $row['DealerPrice'];
                $vvTDP = $row['TotalPrice'];
                $vvSRP = $row['SRP'];
                $vvTSRP = $row['TotalSRP'];
                $vvMU = $row['Markup'];
                $vvTMU = $row['TotalMarkup'];

                $sStock = $row['Stock'];
                $sBranch = $row['Branch'];
                $sConsign = $row['itemConsign'];
                $xType = $row['Type'];

                if ($sConsign != "CONSIGNMENT"){
                    // If product is not consignment
                    $stmt2 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                    $stmt2->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    $stmt2->close();
                    if ($result2->num_rows > 0) {
                        $row2 = $result2->fetch_assoc();
                        $iQuantity2 = $iQuantity + $row2['Quantity'];
                        $vvDP2 = $vvDP;
                        $vvTDP2 = round(floatval($vvTDP) + floatval($row2['TotalPrice']), 2);
                        $vvSRP2 = $vvSRP;
                        $vvTSRP2 = round(floatval($vvTSRP) + floatval($row2['TotalSRP']), 2);
                        $vvMU2 = $vvMU;
                        $vvTMU2 = round(floatval($vvTMU) + floatval($row2['TotalMarkup']), 2);

                        $vvVatSales = 0.0; 
                        $vvVAT = 0.0;

                        if ($xType == "WITH VAT"){
                            $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                            $vvVatSales = floatval($vvTDP2) - $vvVAT;
                        } else {
                            $vvVatSales = $vvTDP2;
                            $vvVAT = 0;
                        }

                        $stmt3 = $this->conn->prepare("UPDATE tbl_invlist SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt3->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt3->execute();
                        $stmt3->close();
                    } else {
                        $stmt3 = $this->conn->prepare("SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt3->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt3->execute();
                        $result3 = $stmt3->get_result();
                        $stmt3->close();
                        if ($result3->num_rows > 0) {    
                            $row3 = $result3->fetch_assoc();
                            $iQuantity2 = $iQuantity;
                            $vvDP2 = $vvDP;
                            $vvTDP2 = round(floatval($vvTDP), 2);
                            $vvSRP2 = $vvSRP;
                            $vvTSRP2 = round(floatval($vvTSRP), 2);
                            $vvMU2 = $vvMU;
                            $vvTMU2 = round(floatval($vvTMU), 2);

                            $vvVatSales = 0.0; 
                            $vvVAT = 0.0;

                            if ($xType == "WITH VAT"){
                                $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                                $vvVatSales = floatval($vvTDP2) - $vvVAT;
                            } else {
                                $vvVatSales = $vvTDP2;
                                $vvVAT = 0;
                            }

                            $stmt = $this->conn->prepare("UPDATE tbl_prodhistory SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("INSERT INTO tbl_invlist SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("DELETE FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("UPDATE tbl_invlist SET asof =?");
                            $stmt->bind_param('s', $currentDate);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                } else {
                    // If Product is Consignment
                    $stmt2 = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                    $stmt2->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    $stmt2->close();
                    if ($result2->num_rows > 0) {
                        $row2 = $result2->fetch_assoc();
                        $iQuantity2 = $iQuantity + $row2['Quantity'];
                        $vvDP2 = $vvDP;
                        $vvTDP2 = round(floatval($vvTDP) + floatval($row2['TotalPrice']), 2);
                        $vvSRP2 = $vvSRP;
                        $vvTSRP2 = round(floatval($vvTSRP) + floatval($row2['TotalSRP']), 2);
                        $vvMU2 = $vvMU;
                        $vvTMU2 = round(floatval($vvTMU) + floatval($row2['TotalMarkup']), 2);

                        $vvVatSales = 0.0; 
                        $vvVAT = 0.0;

                        if ($xType == "WITH VAT"){
                            $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                            $vvVatSales = floatval($vvTDP2) - $vvVAT;
                        } else {
                            $vvVatSales = $vvTDP2;
                            $vvVAT = 0;
                        }

                        $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt->execute();
                        $stmt->close();                    
                    } else {
                        $stmt3 = $this->conn->prepare("SELECT * FROM tbl_invlistconsignhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt3->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt3->execute();
                        $result3 = $stmt3->get_result();
                        $stmt3->close();
                        if ($result3->num_rows > 0) {
                            $row3 = $result3->fetch_assoc();
                            $iQuantity2 = $iQuantity;
                            $vvDP2 = $vvDP;
                            $vvTDP2 = round(floatval($vvTDP), 2);
                            $vvSRP2 = $vvSRP;
                            $vvTSRP2 = round(floatval($vvTSRP), 2);
                            $vvMU2 = $vvMU;
                            $vvTMU2 = round(floatval($vvTMU), 2);

                            $vvVatSales = 0.0; 
                            $vvVAT = 0.0;

                            if ($xType == "WITH VAT"){
                                $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                                $vvVatSales = floatval($vvTDP2) - $vvVAT;
                            } else {
                                $vvVatSales = $vvTDP2;
                                $vvVAT = 0;
                            }

                            $stmt = $this->conn->prepare("UPDATE tbl_invlistconsignhistory SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("INSERT INTO tbl_invlistconsign SELECT * FROM tbl_invlistconsignhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("DELETE FROM tbl_invlistconsignhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET asof =?");
                            $stmt->bind_param('s', $currentDate);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                    // Update on INVLIST

                    $stmt = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                    $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $iQuantity2 = $iQuantity + $row['Quantity'];
                        $vvDP2 = $vvDP;
                        $vvTDP2 = round(floatval($vvTDP) + floatval($row['TotalPrice']), 2);
                        $vvSRP2 = $vvSRP;
                        $vvTSRP2 = round(floatval($vvTSRP) + floatval($row['TotalSRP']), 2);
                        $vvMU2 = $vvMU;
                        $vvTMU2 = round(floatval($vvTMU) + floatval($row['TotalMarkup']), 2);

                        $vvVatSales = 0.0; 
                        $vvVAT = 0.0;

                        if ($xType == "WITH VAT"){
                            $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                            $vvVatSales = floatval($vvTDP2) - $vvVAT;
                        } else {
                            $vvVatSales = $vvTDP2;
                            $vvVAT = 0;
                        }

                        $stmt = $this->conn->prepare("UPDATE tbl_invlist SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt->execute();
                        $stmt->close();                    
                    } else {
                        $stmt = $this->conn->prepare("SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $stmt->close();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $iQuantity2 = $iQuantity;
                            $vvDP2 = $vvDP;
                            $vvTDP2 = round(floatval($vvTDP), 2);
                            $vvSRP2 = $vvSRP;
                            $vvTSRP2 = round(floatval($vvTSRP), 2);
                            $vvMU2 = $vvMU;
                            $vvTMU2 = round(floatval($vvTMU), 2);

                            $vvVatSales = 0.0; 
                            $vvVAT = 0.0;

                            if ($xType == "WITH VAT"){
                                $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                                $vvVatSales = floatval($vvTDP2) - $vvVAT;
                            } else {
                                $vvVatSales = $vvTDP2;
                                $vvVAT = 0;
                            }

                            $stmt = $this->conn->prepare("UPDATE tbl_prodhistory SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("INSERT INTO tbl_invlist SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("DELETE FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("UPDATE tbl_invlist SET asof =?");
                            $stmt->bind_param('s', $currentDate);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                }
            }
            // Perform cancellation of transaction
    
            $stmt = $this->conn->prepare("UPDATE tbl_inventoryout SET product = 'CANCELLED', Supplier = '-', category = '-', type = '-', quantity = '0', dealerprice = '0', totalprice = '0', SRP = '0', TotalSRP = '0', Markup = '0', TotalMarkup = '0', VatSales = '0', VAT = '0', AmountDue = '0', SOLDTO = 'CANCELLED', TIN = '-', ADDRESS = '-', STATUS = '-', DiscInterest = '0', DiscAmount = '0', DiscNewSRP = '0', DiscNewTotalSRP = '0' WHERE SI = ? AND SOLDTO = ? AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
            $stmt->bind_param('sss', $sino, $soldTo, $dateInvOut);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE tbl_salesjournal SET grosssales = '0', VAT = '0', netsales = '0', CUSTOMER = 'CANCELLED', TIN = '-', ADDRESS = '-' WHERE reference = ? AND STR_TO_DATE(DateSold,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
            $stmt->bind_param('ss', $sino, $dateInvOut);
            $stmt->execute();
            $stmt->close();

            $status = "SUCCESS";
            $message = "SI# " . $sino . "successfully cancelled.";
        }

        echo json_encode(array( 
            "STATUS" => $status,
            "MESSAGE" => $message,
        ));
    }
    
    public function DeleteSI($data){
        $dateInvOut = $data['dateSold'];
        $soldTo = $data['soldTo'];
        $sino = $data['sino'];
        date_default_timezone_set('Asia/Manila');
        $currentDate = date("m/d/Y", strtotime("now"));

        $iQuantity2 = 0;
        $vvDP2 = 0.0;
        $vvTDP2 = 0.0;
        $vvSRP2 = 0.0;
        $vvTSRP2 = 0.0;
        $vvMU2 = 0.0;
        $vvTMU2 = 0.0;

        $invoutList = [];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_inventoryout WHERE SI = ? AND SOLDTO = ? AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
        $stmt->bind_param('sss', $sino, $soldTo, $dateInvOut);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $invoutList[] = $row;

                $ssProduct = $row['Product'];
                $ssSupSI = $row['SupplierSI'];
                $ssSerial = $row['Serialno'];
                $ssSupplier = $row['Supplier'];
                $iQuantity = $row['Quantity'];
                $vvDP = $row['DealerPrice'];
                $vvTDP = $row['TotalPrice'];
                $vvSRP = $row['SRP'];
                $vvTSRP = $row['TotalSRP'];
                $vvMU = $row['Markup'];
                $vvTMU = $row['TotalMarkup'];

                $sStock = $row['Stock'];
                $sBranch = $row['Branch'];
                $sConsign = $row['itemConsign'];
                $xType = $row['Type'];

                if ($sConsign != "CONSIGNMENT"){
                    // If product is not consignment
                    $stmt2 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                    $stmt2->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    $stmt2->close();
                    if ($result2->num_rows > 0) {
                        $row2 = $result2->fetch_assoc();
                        $iQuantity2 = $iQuantity + $row2['Quantity'];
                        $vvDP2 = $vvDP;
                        $vvTDP2 = round(floatval($vvTDP) + floatval($row2['TotalPrice']), 2);
                        $vvSRP2 = $vvSRP;
                        $vvTSRP2 = round(floatval($vvTSRP) + floatval($row2['TotalSRP']), 2);
                        $vvMU2 = $vvMU;
                        $vvTMU2 = round(floatval($vvTMU) + floatval($row2['TotalMarkup']), 2);

                        $vvVatSales = 0.0; 
                        $vvVAT = 0.0;

                        if ($xType == "WITH VAT"){
                            $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                            $vvVatSales = floatval($vvTDP2) - $vvVAT;
                        } else {
                            $vvVatSales = $vvTDP2;
                            $vvVAT = 0;
                        }

                        $stmt3 = $this->conn->prepare("UPDATE tbl_invlist SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt3->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt3->execute();
                        $stmt3->close();
                    } else {
                        $stmt3 = $this->conn->prepare("SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt3->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt3->execute();
                        $result3 = $stmt3->get_result();
                        $stmt3->close();
                        if ($result3->num_rows > 0) {    
                            $row3 = $result3->fetch_assoc();
                            $iQuantity2 = $iQuantity;
                            $vvDP2 = $vvDP;
                            $vvTDP2 = round(floatval($vvTDP), 2);
                            $vvSRP2 = $vvSRP;
                            $vvTSRP2 = round(floatval($vvTSRP), 2);
                            $vvMU2 = $vvMU;
                            $vvTMU2 = round(floatval($vvTMU), 2);

                            $vvVatSales = 0.0; 
                            $vvVAT = 0.0;

                            if ($xType == "WITH VAT"){
                                $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                                $vvVatSales = floatval($vvTDP2) - $vvVAT;
                            } else {
                                $vvVatSales = $vvTDP2;
                                $vvVAT = 0;
                            }

                            $stmt = $this->conn->prepare("UPDATE tbl_prodhistory SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("INSERT INTO tbl_invlist SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("DELETE FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("UPDATE tbl_invlist SET asof =?");
                            $stmt->bind_param('s', $currentDate);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                } else {
                    // If Product is Consignment
                    $stmt2 = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                    $stmt2->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    $stmt2->close();
                    if ($result2->num_rows > 0) {
                        $row2 = $result2->fetch_assoc();
                        $iQuantity2 = $iQuantity + $row2['Quantity'];
                        $vvDP2 = $vvDP;
                        $vvTDP2 = round(floatval($vvTDP) + floatval($row2['TotalPrice']), 2);
                        $vvSRP2 = $vvSRP;
                        $vvTSRP2 = round(floatval($vvTSRP) + floatval($row2['TotalSRP']), 2);
                        $vvMU2 = $vvMU;
                        $vvTMU2 = round(floatval($vvTMU) + floatval($row2['TotalMarkup']), 2);

                        $vvVatSales = 0.0; 
                        $vvVAT = 0.0;

                        if ($xType == "WITH VAT"){
                            $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                            $vvVatSales = floatval($vvTDP2) - $vvVAT;
                        } else {
                            $vvVatSales = $vvTDP2;
                            $vvVAT = 0;
                        }

                        $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt->execute();
                        $stmt->close();                    
                    } else {
                        $stmt3 = $this->conn->prepare("SELECT * FROM tbl_invlistconsignhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt3->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt3->execute();
                        $result3 = $stmt3->get_result();
                        $stmt3->close();
                        if ($result3->num_rows > 0) {
                            $row3 = $result3->fetch_assoc();
                            $iQuantity2 = $iQuantity;
                            $vvDP2 = $vvDP;
                            $vvTDP2 = round(floatval($vvTDP), 2);
                            $vvSRP2 = $vvSRP;
                            $vvTSRP2 = round(floatval($vvTSRP), 2);
                            $vvMU2 = $vvMU;
                            $vvTMU2 = round(floatval($vvTMU), 2);

                            $vvVatSales = 0.0; 
                            $vvVAT = 0.0;

                            if ($xType == "WITH VAT"){
                                $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                                $vvVatSales = floatval($vvTDP2) - $vvVAT;
                            } else {
                                $vvVatSales = $vvTDP2;
                                $vvVAT = 0;
                            }

                            $stmt = $this->conn->prepare("UPDATE tbl_invlistconsignhistory SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("INSERT INTO tbl_invlistconsign SELECT * FROM tbl_invlistconsignhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("DELETE FROM tbl_invlistconsignhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET asof =?");
                            $stmt->bind_param('s', $currentDate);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                    // Update on INVLIST

                    $stmt = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                    $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $iQuantity2 = $iQuantity + $row['Quantity'];
                        $vvDP2 = $vvDP;
                        $vvTDP2 = round(floatval($vvTDP) + floatval($row['TotalPrice']), 2);
                        $vvSRP2 = $vvSRP;
                        $vvTSRP2 = round(floatval($vvTSRP) + floatval($row['TotalSRP']), 2);
                        $vvMU2 = $vvMU;
                        $vvTMU2 = round(floatval($vvTMU) + floatval($row['TotalMarkup']), 2);

                        $vvVatSales = 0.0; 
                        $vvVAT = 0.0;

                        if ($xType == "WITH VAT"){
                            $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                            $vvVatSales = floatval($vvTDP2) - $vvVAT;
                        } else {
                            $vvVatSales = $vvTDP2;
                            $vvVAT = 0;
                        }

                        $stmt = $this->conn->prepare("UPDATE tbl_invlist SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt->execute();
                        $stmt->close();                    
                    } else {
                        $stmt = $this->conn->prepare("SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                        $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $stmt->close();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $iQuantity2 = $iQuantity;
                            $vvDP2 = $vvDP;
                            $vvTDP2 = round(floatval($vvTDP), 2);
                            $vvSRP2 = $vvSRP;
                            $vvTSRP2 = round(floatval($vvTSRP), 2);
                            $vvMU2 = $vvMU;
                            $vvTMU2 = round(floatval($vvTMU), 2);

                            $vvVatSales = 0.0; 
                            $vvVAT = 0.0;

                            if ($xType == "WITH VAT"){
                                $vvVAT = round(((floatval($vvDP) / 1.12) * 0.12), 2) * floatval($iQuantity2);
                                $vvVatSales = floatval($vvTDP2) - $vvVAT;
                            } else {
                                $vvVatSales = $vvTDP2;
                                $vvVAT = 0;
                            }

                            $stmt = $this->conn->prepare("UPDATE tbl_prodhistory SET Quantity = ?, DealerPrice = ?, TotalPrice = ?, SRP = ?, TotalSRP = ?, Markup = ?, TotalMarkup = ?, VatSales = ?, Vat = ?, AmountDue = ? WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssssssssssssss', $iQuantity2, $vvDP2, $vvTDP2, $vvSRP2, $vvTSRP2, $vvMU2, $vvTMU2, $vvVatSales, $vvVAT, $vvTDP2, $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("INSERT INTO tbl_invlist SELECT * FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("DELETE FROM tbl_prodhistory WHERE SIno = ? AND Serialno = ? AND Product = ? AND Supplier = ? AND Stock = ? AND Branch = ?");
                            $stmt->bind_param('ssssss', $ssSupSI, $ssSerial, $ssProduct, $ssSupplier, $sStock, $sBranch);
                            $stmt->execute();
                            $stmt->close();

                            $stmt = $this->conn->prepare("UPDATE tbl_invlist SET asof =?");
                            $stmt->bind_param('s', $currentDate);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                }
            }
            // Perform cancellation of transaction
    
            $stmt = $this->conn->prepare("DELETE FROM tbl_inventoryout WHERE SI = ? AND SOLDTO = ? AND STR_TO_DATE(DateAdded,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
            $stmt->bind_param('sss', $sino, $soldTo, $dateInvOut);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("DELETE FROM tbl_salesjournal WHERE reference = ? AND STR_TO_DATE(DateSold,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y')");
            $stmt->bind_param('ss', $sino, $dateInvOut);
            $stmt->execute();
            $stmt->close();

            $status = "SUCCESS";
            $message = "SI# " . $sino . "successfully deleted.";
        }

        echo json_encode(array( 
            "STATUS" => $status,
            "MESSAGE" => $message,
        ));
    }

    private function SelectQuery($string){
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