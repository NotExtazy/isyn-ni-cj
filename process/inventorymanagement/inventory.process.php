<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $rawBranches = $this->SelectQuery("SELECT DISTINCT Branch AS ItemName, 'BRANCH' AS ItemType FROM tbl_invlist WHERE Branch IS NOT NULL AND Branch <> '' UNION SELECT DISTINCT Branch, 'BRANCH' FROM tbl_inventoryin WHERE Branch IS NOT NULL AND Branch <> ''");
        
        $isynBranch = [];
        $seen = [];
        foreach ($rawBranches as $b) {
            $name = trim($b['ItemName']);
            $normalized = strtoupper(str_replace(' ', '', $name));
            
            // Deduplicate HEAD OFFICE vs HEADOFFICE
            if ($normalized === 'HEADOFFICE') {
                if (isset($seen['HEADOFFICE'])) continue;
                $b['ItemName'] = 'HEAD OFFICE'; // Normalize to "HEAD OFFICE"
                $isynBranch[] = $b;
                $seen['HEADOFFICE'] = true;
            } else {
                if (!isset($seen[$normalized])) {
                    $isynBranch[] = $b;
                    $seen[$normalized] = true;
                }
            }
        }
        
        $prodType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_inventoryin WHERE Type IS NOT NULL AND Type <> '' UNION SELECT DISTINCT Type FROM tbl_invlist WHERE Type IS NOT NULL AND Type <> '' ORDER BY Type");
        
        $customertype = [];
        if ($this->tableExists('tbl_clientlist')) {
            $customertype = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_clientlist ORDER BY Type");
        }

        $sicount = [];
        if ($this->tableExists('tbl_sinumber')) {
            $sicount = $this->SelectQuery("SELECT * FROM tbl_sinumber");
        }

        echo json_encode(array( 
            "ISYNBRANCH" => $isynBranch,
            "PRODTYPE" => $prodType,
            "CUSTOMERTYPE" => $customertype,
            "SICOUNT" => $sicount,
        ));
    }
    
    public function BuildReportTable($data){
        $tblHeader = [];
        $listviewname = $data['listViewName'];
        $listname = $data['ListName'];
        $value = $listviewname . "-" . $listname;
        $stmt = $this->conn->prepare("SELECT * FROM TBL_DYNALISTS WHERE LISTVIEWNAME = ? ORDER BY CAST(COLUMNPOS AS UNSIGNED) ASC");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tblHeader[] = $row;
            }
        }

        echo json_encode(array( 
            "TBLHEADER" => $tblHeader,
        ));
    }

    public function LoadCustomColumnNames($data){
        $columnNames = [];
        $table = $data['table'];

        if ($table != ""){
            switch ($table){
                case 'CURRENT INVENTORY':
                    $tableCustom = "tbl_invlist";
                    $qry = "SELECT DISTINCT UPPER(column_name) AS Columns FROM information_schema.columns WHERE table_schema = '" . DB . "' AND table_name = 'tbl_invlist' AND column_name NOT IN ('DateAdded','User','AsOf') ORDER BY ordinal_position";
                    
                    break;
                case 'OUTGOING INVENTORY':
                    $tableCustom = "tbl_inventoryout";
                    $qry = "SELECT DISTINCT UPPER(column_name) AS Columns FROM information_schema.columns WHERE table_schema = '" . DB . "' AND table_name = 'tbl_inventoryout' AND column_name NOT IN ('DateAdded','User','AsOf') ORDER BY ordinal_position";
                    
                    break;
                case 'PREVIOUS INVENTORY':
                    $tableCustom = "tbl_inventorychecking";
                    $qry = "SELECT DISTINCT UPPER(column_name) AS Columns FROM information_schema.columns WHERE table_schema = '" . DB . "' AND table_name = 'tbl_inventorychecking' AND column_name NOT IN ('DateAdded','User','AsOf') ORDER BY ordinal_position";
                            
                    break;
            }
    
            $query = $qry;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
    
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $columnNames[] = $row;
                }
            } else {
                $columnNames = "";
            }
        } else {
            $columnNames = "";
        }
        
        echo json_encode(array( 
            "COLUMNS" => $columnNames,
            "TBLCUSTOM" => $tableCustom,
        ));
    }

    public function LoadCustomColumnValue($data){
        $columnValues = [];
        $table = $data['table'];
        $column = $data['column'];

        switch ($table){
            case 'CURRENT INVENTORY':
                $qry = "tbl_invlist";
                break;

            case 'OUTGOING INVENTORY':
                $qry = "tbl_inventoryout";                
                break;
                
            case 'PREVIOUS INVENTORY':
                $qry = "tbl_inventorychecking";                        
                break;
        }

        $query = "SELECT DISTINCT UPPER($column) AS ColVal FROM " . $qry;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $columnValues[] = $row;
            }
        } else {
            $columnValues = "";
        }
        
        echo json_encode(array( 
            "VALUES" => $columnValues
        ));
    }
    
    public function SearchInventory($data){
        $inventoryReport = [];
        
        $isynBranch = $data['isynBranch'];
        $ispreset = $data['ispreset'];
        $presetSelect = $data['presetSelect'];
        $withoutconsign = $data['withoutconsign'];
        $onlyconsign = $data['onlyconsign'];
        $nofreebies = $data['nofreebies'];
        $onlyfreebies = $data['onlyfreebies'];
        $inctranferprod = $data['inctransferprod'];
        $discprod = $data['discprod'];

        $iscustom = $data['iscustom'];
        $tableCustomVal = $data['tableCustomVal'];
        $customSelect = $data['customSelect'];
        $customColumn = $data['customColumn'];
        $customValue = $data['customValue'];
        $fromdate = $data['fromdate'];
        $todate = $data['todate'];

        $type = "";
        $column = "";
        $value = "";

        $qry = "";
        $branchName = "";

        if ($iscustom == "Yes"){
            $type = "CUSTOM";
            $column = $customColumn;
            $value = $customValue;
        } else {
            $type = $presetSelect;
            $column = "";
            $value = "";
        }

        if ($isynBranch == "OVERALL") {
            $qry = " ";
        } else {
            $qry = " BRANCH='" . $isynBranch . "' AND ";
            $branchName = strtoupper($isynBranch);
        }

        $qryDelete = "";
        $qryinsert1 = "";
        $qryinsert2 = "";

        if ($type == "CURRENT INVENTORY" || $type == "ENDING INVENTORY" || $type == "PREVIOUS INVENTORY") {
            if ($onlyconsign != "Yes") {
                switch ($type){
                    case 'CURRENT INVENTORY':
                        if ($isynBranch == "OVERALL") {
                            $qry = " ";
                            $branchName = "OVERALL";
                        } else {
                            $qry = " WHERE BRANCH='" . $isynBranch . "'";
                            $branchName = strtoupper($isynBranch);
                        }

                        $qryDelete = "DELETE FROM tbl_invlisttemporary";
                        $stmt = $this->conn->prepare($qryDelete);
                        $stmt->execute();
                        $stmt->close();
                        
                        $qryinsert1 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_invlist "  . $qry;
                        $stmt = $this->conn->prepare($qryinsert1);
                        $stmt->execute();
                        $stmt->close();
                        break;
                        
                    case 'ENDING INVENTORY':
                        if ($fromdate != $todate) {
                            $qryDelete = "DELETE FROM tbl_invlisttemporary";
                            $stmt = $this->conn->prepare($qryDelete);
                            $stmt->execute();
                            $stmt->close();
                            
                            $qryinsert1 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_inventoryend WHERE "  . $qry . " STR_TO_DATE(datepurchased, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(datepurchased, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert1);
                            $stmt->execute();
                            $stmt->close();

                            $qryinsert2 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_invlistconsignend WHERE "  . $qry . " STR_TO_DATE(datepurchased, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(datepurchased, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $qryDelete = "DELETE FROM tbl_invlisttemporary";
                            $stmt = $this->conn->prepare($qryinsert2);
                            $stmt->execute();
                            $stmt->close();
                            
                        } else {
                            $qryDelete = "DELETE FROM tbl_invlisttemporary";
                            $stmt = $this->conn->prepare($qryDelete);
                            $stmt->execute();
                            $stmt->close();
                            
                            $qryinsert1 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_inventoryend WHERE "  . $qry . " STR_TO_DATE(asof, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(asof, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert2);
                            $stmt->execute();
                            $stmt->close();

                            $qryinsert2 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_invlistconsignend WHERE "  . $qry . " STR_TO_DATE(asof, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(asof, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert2);
                            $stmt->execute();
                            $stmt->close();
                        }
                        break;
                        
                    case 'PREVIOUS INVENTORY':
                        if ($fromdate != $todate) {
                            $qryDelete = "DELETE FROM tbl_invlisttemporary";
                            $stmt = $this->conn->prepare($qryDelete);
                            $stmt->execute();
                            $stmt->close();
                            
                            $qryinsert1 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_inventorychecking WHERE "  . $qry . " STR_TO_DATE(datepurchased, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(datepurchased, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert2);
                            $stmt->execute();
                            $stmt->close();

                            $qryinsert2 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_invlistconsigchecking WHERE "  . $qry . " STR_TO_DATE(datepurchased, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(datepurchased, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert2);
                            $stmt->execute();
                            $stmt->close();
                        } else {
                            $qryDelete = "DELETE FROM tbl_invlisttemporary";
                            $stmt = $this->conn->prepare($qry);
                            $stmt->execute();
                            $stmt->close();
                            
                            $qryinsert1 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_inventorychecking WHERE "  . $qry . " STR_TO_DATE(asof, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(asof, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert1);
                            $stmt->execute();
                            $stmt->close();

                            $qryinsert2 = "INSERT INTO tbl_invlisttemporary SELECT * FROM tbl_invlistconsigchecking WHERE "  . $qry . " STR_TO_DATE(asof, '%m%d%y') >= STR_TO_DATE('".$fromdate."', '%m%d%y') AND STR_TO_DATE(asof, '%m%d%y') <= STR_TO_DATE('".$todate."', '%m%d%y')";
                            $stmt = $this->conn->prepare($qryinsert2);
                            $stmt->execute();
                            $stmt->close();
                        }
                        break;
                }

                if ($withoutconsign == "Yes" && $ispreset == "Yes" && $onlyconsign == "") {
                    $stmt = $this->conn->prepare("DELETE FROM tbl_invlisttemporary WHERE stock <> branch");
                    $stmt->execute();
                    $stmt->close();
                }
                
                if ($nofreebies == "Yes") {
                    $stmt = $this->conn->prepare("DELETE FROM tbl_invlisttemporary WHERE category = 'FREEBIES'");
                    $stmt->execute();
                    $stmt->close();
                } else if ($onlyfreebies == "Yes"){
                    $stmt = $this->conn->prepare("DELETE FROM tbl_invlisttemporary WHERE category <> 'FREEBIES'");
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        if ($isynBranch == "OVERALL") {
            $qry = " ";
            $branchName = "OVERALL";
        } else {
            $qry = " BRANCH='" . $isynBranch . "' AND ";
            $branchName = strtoupper($isynBranch);
        }

        switch ($type){
            case 'CURRENT INVENTORY':
                $reportType = $type;

                $mytable = "";
                
                if ($onlyconsign == "Yes") {
                    $mytable = "tbl_invlistconsign";
                } else {
                    $mytable = "tbl_invlisttemporary";
                }

                if ($fromdate != $todate) {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE " .$qry. "STR_TO_DATE(dateadded,'%m/%d/%Y') >= STR_TO_DATE('".$fromdate."','%m/%d/%Y') AND STR_TO_DATE(dateadded,'%m/%d/%Y') <= STR_TO_DATE('".$todate."','%m/%d/%Y') ORDER BY category,product";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                } else {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE " .$qry."STR_TO_DATE(asof,'%m/%d/%Y') = STR_TO_DATE('".$fromdate."','%m/%d/%Y')  order by category,product";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                }
                break;
                
            case 'ENDING INVENTORY':
                $reportType = $type;

                $mytable = "";
                
                if ($onlyconsign == "Yes") {
                    $mytable = "tbl_invlistconsignend";
                } else {
                    $mytable = "tbl_invlisttemporary";
                }

                if ($fromdate != $todate) {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE " .$qry. "STR_TO_DATE(dateadded,'%m/%d/%Y') >= STR_TO_DATE('".$fromdate."','%m/%d/%Y') AND STR_TO_DATE(dateadded,'%m/%d/%Y') <= STR_TO_DATE('".$todate."','%m/%d/%Y') ORDER BY category,product";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                } else {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE " .$qry."STR_TO_DATE(asof,'%m/%d/%Y') = STR_TO_DATE('".$fromdate."','%m/%d/%Y')  order by category,product";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                }
                break;

            case 'INCOMING INVENTORY':
                $reportType = $type;

                $mytable = "";
                
                if ($onlyconsign == "Yes") {
                    $mytable = "tbl_invconsignin";
                } else {
                    $mytable = "tbl_inventoryin";
                }

                if ($fromdate != $todate) {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE ";

                    if ($nofreebies == "Yes") {
                        $qryinsert1 .= " CATEGORY <> 'FREEBIES' AND ";
                    } elseif ($onlyfreebies == "Yes") {
                        $qryinsert1 .= " CATEGORY = 'FREEBIES' AND ";
                    }
                    
                    $qryinsert1 .= "STR_TO_DATE(dateadded, '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y') 
                               AND STR_TO_DATE(dateadded, '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y') 
                               ORDER BY product, category";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                } else {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE ";

                    if ($nofreebies == "Yes") {
                        $qryinsert1 .= " CATEGORY <> 'FREEBIES' AND ";
                    } elseif ($onlyfreebies == "Yes") {
                        $qryinsert1 .= " CATEGORY = 'FREEBIES' AND ";
                    }
                    
                    $qryinsert1 .= "STR_TO_DATE(asof, '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y') 
                               AND STR_TO_DATE(asof, '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y') 
                               ORDER BY product, category";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                }
                break;

            case 'OUTGOING INVENTORY':
                $reportType = $type;
                
                if ($onlyconsign == "Yes") {
                    if ($isynBranch == "OVERALL") {
                        $qry .= " ";
                        $branchName = "OVERALL";
                        $qry .= " ITEMCONSIGN = 'CONSIGNMENT' AND ";
                    } else {
                        $qry .= " BRANCH='" . $isynBranch . "' AND ITEMCONSIGN = 'CONSIGNMENT' AND";
                        $branchName = strtoupper($isynBranch);
                    }
                }

                if ($discprod == "Yes") {
                    if ($isynBranch == "OVERALL") {
                        $qry .= " DISCPRODUCT = 'YES' AND ";
                    } else {
                        if ($onlyconsign == "Yes") {
                            $qry .= " DISCPRODUCT = 'YES' AND ";
                        } else {
                            $qry .= " BRANCH = '" . $isynBranch . "' AND ITEMCONSIGN = 'CONSIGNMENT' AND ";
                        }
                    }
                }

                $qryinsert1 = "SELECT * FROM TBL_INVENTORYOUT WHERE" .$qry. "";

                if ($nofreebies == "Yes") {
                    $qryinsert1 .= " CATEGORY <> 'FREEBIES' AND ";
                } elseif ($onlyfreebies == "Yes") {
                    $qryinsert1 .= " CATEGORY = 'FREEBIES' AND ";
                }
                
                $qryinsert1 .= "STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y') 
                               AND STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y') 
                               ORDER BY si, product ASC";

                $stmt = $this->conn->prepare($qryinsert1);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $inventoryReport[] = $row;
                    }
                }
                break;
                
            case 'PREVIOUS INVENTORY':
                $reportType = $type;

                $mytable = "";
                
                if ($onlyconsign == "Yes") {
                    $mytable = "tbl_invlistconsigchecking";
                } else {
                    $mytable = "tbl_invlisttemporary";
                }

                if ($fromdate != $todate) {                    
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE " .$qry. "STR_TO_DATE(dateadded,'%m/%d/%Y') >= STR_TO_DATE('".$fromdate."','%m/%d/%Y') AND STR_TO_DATE(dateadded,'%m/%d/%Y') <= STR_TO_DATE('".$todate."','%m/%d/%Y') ORDER BY category,product";
                    $stmt = $this->conn->prepare($qryinsert2);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                } else {
                    $qryinsert1 = "SELECT * FROM " .$mytable. " WHERE " .$qry. "STR_TO_DATE(asof,'%m/%d/%Y') >= STR_TO_DATE('".$fromdate."','%m/%d/%Y') ORDER BY category,product";
                    $stmt = $this->conn->prepare($qryinsert1);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $inventoryReport[] = $row;
                        }
                    }
                }
                break;

            case 'CUSTOM':
                $reportType = $type;
                
                $qryinsert1 = "SELECT * FROM " .$tableCustomVal. " WHERE" .$qry. "" .$customColumn. " = '" .$customValue. "'";
                $stmt = $this->conn->prepare($qryinsert1);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $inventoryReport[] = $row;
                    }
                }
                break;
        }

        $transferProdRpt = [];

        if ($inctranferprod == "Yes" && $type == "INCOMING INVENTORY") {           
            $qryinsert1 = "SELECT * FROM tbl_transferproducttotals WHERE STR_TO_DATE(DateTransfer,'%m/%d/%Y') >= STR_TO_DATE('".$fromdate."','%m/%d/%Y') AND STR_TO_DATE(DateTransfer,'%m/%d/%Y') <= STR_TO_DATE('".$todate."','%m/%d/%Y')";
            $stmt = $this->conn->prepare($qryinsert1);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $transferProdRpt[] = $row;
                }
            }
        }

        echo json_encode(array( 
            "REPORTDATA" => $inventoryReport,
            "TRANSFERPRODRPT" => $transferProdRpt,
            "TABLECUSTOMVAL" => $tableCustomVal,
            "REPORTTYPE" => $reportType,
            // "DATA" => $data,
            "QRY" => $qryinsert1,
        ));
    }

    
    public function GenerateInventoryReport ($data) {
        $headerData = json_decode($data['HEADERDATA']);
        $isynbranch = isset($data['ISYNBRANCH']) ? trim($data['ISYNBRANCH']) : '';
        $reportType = $data['REPORTTYPE'];
        $ispreset = $data['ispreset'];
        $presetSelect = $data['presetSelect'];
        $withoutconsign = $data['withoutconsign'];
        $onlyconsign = $data['onlyconsign'];
        $nofreebies = $data['nofreebies'];
        $onlyfreebies = $data['onlyfreebies'];
        $inctranferprod = $data['inctransferprod'];
        $discprod = $data['discprod'];
        $iscustom = $data['iscustom'];
        $tableCustomVal = $data['tableCustomVal'];
        $customSelect = $data['customSelect'];
        $customColumn = $data['customColumn'];
        $customValue = $data['customValue'];
        $fromdate = $data['fromdate'];
        $todate = $data['todate'];
        $postedRows = isset($data['DATA']) ? json_decode($data['DATA']) : null;
        $inventoryReport = [];
        $type = "";
        $column = "";
        $value = "";
        if ($iscustom == "Yes"){
            $type = "CUSTOM";
            $column = $customColumn;
            $value = $customValue;
        } else {
            $type = $presetSelect;
            $column = "";
            $value = "";
        }
        $qry = "";
        $branchName = "";
        if ($isynbranch == "" || strtoupper($isynbranch) == "OVERALL") {
            $qry = " ";
            $branchName = "OVERALL";
        } else {
            $qry = " BRANCH='" . $isynbranch . "' AND ";
            $branchName = strtoupper($isynbranch);
        }
        if (!$postedRows && ($type == "CURRENT INVENTORY" || $type == "ENDING INVENTORY" || $type == "PREVIOUS INVENTORY")) {
            if ($onlyconsign != "Yes") {
                switch ($type){
                    case 'CURRENT INVENTORY':
                        $mytable = "tbl_invlist";
                        $filters = [];
                        if (!($isynbranch == "" || strtoupper($isynbranch) == "OVERALL")) { $filters[] = "BRANCH='" . $isynbranch . "'"; }
                        if ($nofreebies == "Yes") { $filters[] = "CATEGORY <> 'FREEBIES'"; }
                        if ($onlyfreebies == "Yes") { $filters[] = "CATEGORY = 'FREEBIES'"; }
                        if ($fromdate != "" && $todate != "") {
                            if ($fromdate === $todate) {
                                $filters[] = "STR_TO_DATE(DateAdded, '%m/%d/%Y') = STR_TO_DATE('".$fromdate."', '%m/%d/%Y')";
                            } else {
                                $filters[] = "STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y')";
                                $filters[] = "STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y')";
                            }
                        }
                        $where = count($filters) ? (" WHERE " . implode(" AND ", $filters)) : "";
                        $stmt = $this->conn->prepare("SELECT * FROM ".$mytable.$where." ORDER BY category,product");
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res->num_rows > 0) { while ($row = $res->fetch_assoc()) { $inventoryReport[] = $row; } }
                        $stmt->close();
                        break;
                    case 'ENDING INVENTORY':
                        $mytable = "tbl_inventoryend";
                        if ($isynbranch == "OVERALL") {
                            $f = " ";
                        } else {
                            $f = " BRANCH='" . $isynbranch . "' AND ";
                        }
                        $dateCol = $this->findFirstColumn($mytable, ['DatePurchase','DatePurchased','datepurchased','AsOf','asof']);
                        if ($fromdate != "" && $todate != "" && $dateCol) {
                            if ($fromdate === $todate) {
                                $q = "SELECT * FROM ".$mytable." WHERE ".$f." STR_TO_DATE(".$dateCol.", '%m/%d/%Y') = STR_TO_DATE('".$fromdate."', '%m/%d/%Y')";
                            } else {
                                $q = "SELECT * FROM ".$mytable." WHERE ".$f." STR_TO_DATE(".$dateCol.", '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y') AND STR_TO_DATE(".$dateCol.", '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y')";
                            }
                        } else {
                            $q = "SELECT * FROM ".$mytable." WHERE ".$f." 1=1";
                        }
                        if ($nofreebies == "Yes") { $q .= " AND CATEGORY <> 'FREEBIES'"; }
                        if ($onlyfreebies == "Yes") { $q .= " AND CATEGORY = 'FREEBIES'"; }
                        $stmt = $this->conn->prepare($q);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res->num_rows > 0) { while ($row = $res->fetch_assoc()) { $inventoryReport[] = $row; } }
                        $stmt->close();
                        break;
                    case 'PREVIOUS INVENTORY':
                        $mytable = "tbl_inventorychecking";
                        if ($isynbranch == "OVERALL") { $f = " "; } else { $f = " BRANCH='" . $isynbranch . "' AND "; }
                        $dateColPrev = $this->findFirstColumn($mytable, ['DatePurchase','DatePurchased','datepurchased','AsOf','asof']);
                        if ($fromdate != "" && $todate != "" && $dateColPrev) {
                            if ($fromdate === $todate) {
                                $q = "SELECT * FROM ".$mytable." WHERE ".$f." STR_TO_DATE(".$dateColPrev.", '%m/%d/%Y') = STR_TO_DATE('".$fromdate."', '%m/%d/%Y')";
                            } else {
                                $q = "SELECT * FROM ".$mytable." WHERE ".$f." STR_TO_DATE(".$dateColPrev.", '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y') AND STR_TO_DATE(".$dateColPrev.", '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y')";
                            }
                        } else {
                            $q = "SELECT * FROM ".$mytable." WHERE ".$f." 1=1";
                        }
                        $stmt = $this->conn->prepare($q);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        if ($res->num_rows > 0) { while ($row = $res->fetch_assoc()) { $inventoryReport[] = $row; } }
                        $stmt->close();
                        break;
                }
            }
        }
        if (!$postedRows && $type == "INCOMING INVENTORY") {
            $mytable = "tbl_inventoryin";
            $conditions = [];
            if ($isynbranch != "OVERALL") { $conditions[] = " Branch='" . $isynbranch . "'"; }
            if ($nofreebies == "Yes") { $conditions[] = " CATEGORY <> 'FREEBIES'"; }
            if ($onlyfreebies == "Yes") { $conditions[] = " CATEGORY = 'FREEBIES'"; }
            if ($fromdate != "" && $todate != "") {
                if ($fromdate === $todate) {
                    $conditions[] = "STR_TO_DATE(dateadded, '%m/%d/%Y') = STR_TO_DATE('".$fromdate."', '%m/%d/%Y')";
                } else {
                    $conditions[] = "STR_TO_DATE(dateadded, '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y')";
                    $conditions[] = "STR_TO_DATE(dateadded, '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y')";
                }
            }
            $where = count($conditions) ? (" WHERE " . implode(" AND ", $conditions)) : "";
            $q = "SELECT * FROM ".$mytable.$where." ORDER BY product, category";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) { while ($row = $res->fetch_assoc()) { $inventoryReport[] = $row; } }
            $stmt->close();
        }
        if (!$postedRows && $type == "OUTGOING INVENTORY") {
            $f = "";
            if ($onlyconsign == "Yes") {
                if ($isynbranch == "OVERALL") {
                    $f .= " ITEMCONSIGN = 'CONSIGNMENT' AND ";
                } else {
                    $f .= " BRANCH='" . $isynbranch . "' AND ITEMCONSIGN = 'CONSIGNMENT' AND ";
                }
            } else {
                if ($isynbranch != "OVERALL") { $f .= " BRANCH='" . $isynbranch . "' AND "; }
            }
            if ($discprod == "Yes") { $f .= " DISCPRODUCT = 'YES' AND "; }
            $q = "SELECT * FROM TBL_INVENTORYOUT WHERE ".$f;
            if ($nofreebies == "Yes") { $q .= " CATEGORY <> 'FREEBIES' AND "; }
            if ($onlyfreebies == "Yes") { $q .= " CATEGORY = 'FREEBIES' AND "; }
            if ($fromdate != "" && $todate != "") {
                if ($fromdate === $todate) {
                    $q .= "STR_TO_DATE(DateAdded, '%m/%d/%Y') = STR_TO_DATE('".$fromdate."', '%m/%d/%Y') ";
                } else {
                    $q .= "STR_TO_DATE(DateAdded, '%m/%d/%Y') >= STR_TO_DATE('".$fromdate."', '%m/%d/%Y') AND STR_TO_DATE(DateAdded, '%m/%d/%Y') <= STR_TO_DATE('".$todate."', '%m/%d/%Y') ";
                }
            } else {
                $q .= " 1=1 ";
            }
            $q .= " ORDER BY si, product ASC";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) { while ($row = $res->fetch_assoc()) { $inventoryReport[] = $row; } }
            $stmt->close();
        }
        if (!$postedRows && $type == "CUSTOM") {
            if ($tableCustomVal != "" && $customColumn != "") {
                $where = [];
                if (!($isynbranch == "" || strtoupper($isynbranch) == "OVERALL")) {
                    $branchCol = ($tableCustomVal === "tbl_inventoryin") ? "Branch" : "BRANCH";
                    $where[] = $branchCol . "='" . $isynbranch . "'";
                }
                if ($customValue !== "") {
                    $where[] = $customColumn . " = '" . $customValue . "'";
                }
                $q = "SELECT * FROM " . $tableCustomVal . (count($where) ? (" WHERE " . implode(" AND ", $where)) : "");
                $stmt = $this->conn->prepare($q);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) { while ($row = $res->fetch_assoc()) { $inventoryReport[] = $row; } }
                $stmt->close();
            }
        }
        // Normalize header to include important columns when present in data (only when building from DB)
        if (!$postedRows) {
        $hdrNames = [];
        $normalized = function($s){ return strtolower(trim($s)); };
        if (is_array($headerData)) {
            foreach ($headerData as $col) {
                $name = is_array($col) ? ($col['ColumnName'] ?? '') : (isset($col->ColumnName) ? $col->ColumnName : '');
                if ($name !== '') { $hdrNames[] = $normalized($name); }
            }
        }
        $hasColumnInRows = function($rows, $candidates) use ($normalized) {
            foreach ($rows as $r) {
                foreach ($candidates as $c) {
                    foreach ($r as $k => $v) {
                        if ($normalized($k) === $normalized($c)) { return true; }
                    }
                }
            }
            return false;
        };
        $ensureHeader = function(&$headerData, $name){
            $headerData[] = ['ColumnName' => $name];
        };
        if (!in_array('quantity', $hdrNames, true) && $hasColumnInRows($inventoryReport, ['Quantity','qty'])) {
            $ensureHeader($headerData, 'Quantity');
        }
        if (!in_array('sino', $hdrNames, true) && $hasColumnInRows($inventoryReport, ['SIno','SI','SupplierSI'])) {
            // prefer SI if present, else SIno, else SupplierSI
            if ($hasColumnInRows($inventoryReport, ['SI'])) { $ensureHeader($headerData, 'SI'); }
            elseif ($hasColumnInRows($inventoryReport, ['SIno'])) { $ensureHeader($headerData, 'SIno'); }
            else { $ensureHeader($headerData, 'SupplierSI'); }
        }
        // Reorder header to preferred print sequence
        $preferred = ['SIno','SI','Serialno','Product','Supplier','Stock','Branch','Category','Quantity','DateAdded','SRP','Vat','VatSales'];
        $mapIdx = [];
        $finalHdr = [];
        foreach ($headerData as $idx => $col) {
            $nm = is_array($col) ? ($col['ColumnName'] ?? '') : (isset($col->ColumnName) ? $col->ColumnName : '');
            if ($nm !== '') { $mapIdx[strtolower($nm)] = $idx; }
        }
        foreach ($preferred as $p) {
            $lk = strtolower($p);
            if (isset($mapIdx[$lk])) { $finalHdr[] = $headerData[$mapIdx[$lk]]; unset($mapIdx[$lk]); }
        }
        foreach ($headerData as $idx => $col) {
            $nm = is_array($col) ? ($col['ColumnName'] ?? '') : (isset($col->ColumnName) ? $col->ColumnName : '');
            if ($nm !== '' && !in_array(strtolower($nm), array_map('strtolower',$preferred), true)) { $finalHdr[] = $col; }
        }
        $headerData = $finalHdr;
        }
        
        unset($_SESSION['headerData']);
        unset($_SESSION['tableData']);
        unset($_SESSION['isynbranch']);
        unset($_SESSION['reportType']);
        $_SESSION['headerData'] = $headerData;
        if ($postedRows && is_array($postedRows) && count($postedRows) > 0) {
            $_SESSION['tableData'] = $postedRows;
        } else {
            $printRows = [];
            foreach ($inventoryReport as $r) {
                $vals = [];
                foreach ($headerData as $col) {
                    $colName = is_array($col) ? ($col['ColumnName'] ?? '') : $col->ColumnName;
                    $vals[] = isset($r[$colName]) ? $r[$colName] : '';
                }
                $printRows[] = $vals;
            }
            $_SESSION['tableData'] = $printRows;
        }
        $_SESSION['isynbranch'] = $isynbranch;
        $_SESSION['reportType'] = $type;
        $_SESSION['use_ui_order'] = ($postedRows && is_array($postedRows) && count($postedRows) > 0);

        $transferProdRpt = [];
        if ($inctranferprod == "Yes" && $type == "INCOMING INVENTORY") {           
            if ($fromdate != "" && $todate != "") {
                $qryinsert1 = "SELECT * FROM tbl_transferproducttotals WHERE STR_TO_DATE(DateTransfer,'%m/%d/%Y') >= STR_TO_DATE('".$fromdate."','%m/%d/%Y') AND STR_TO_DATE(DateTransfer,'%m/%d/%Y') <= STR_TO_DATE('".$todate."','%m/%d/%Y')";
            } else {
                $qryinsert1 = "SELECT * FROM tbl_transferproducttotals";
            }
            $stmt = $this->conn->prepare($qryinsert1);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $transferProdRpt[] = $row;
                }
            }
        }
        echo json_encode(array(
            "REPORTDATA" => $inventoryReport,
            "TRANSFERPRODRPT" => $transferProdRpt,
            "TABLECUSTOMVAL" => $tableCustomVal,
            "REPORTTYPE" => $type,
            "ISYNBRANCH" => $isynbranch,
        ));
    }

    
    
    
    
    private function columnExists($table, $column){
        $exists = false;
        $stmt = $this->conn->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND LOWER(column_name) = LOWER(?)");
        if ($stmt) {
            $stmt->bind_param('ss', $table, $column);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) { $exists = true; }
            $stmt->close();
        }
        return $exists;
    }
    
    private function findFirstColumn($table, $candidates){
        foreach ($candidates as $c) {
            if ($this->columnExists($table, $c)) return $c;
        }
        return null;
    }
}