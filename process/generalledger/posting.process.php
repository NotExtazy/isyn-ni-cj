<?php
$connectionPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/database/connection.php';
include_once($connectionPath);

class Process extends Database
{    
    public function PostGL($data){
        set_time_limit(0);

        $qrytest = "";

        $cdate = date("Y-m-d",strtotime($data["date"]));

        $user = $_SESSION['USERNAME'];
        
        $branchname = '';
        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('BRANCHNAME','ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();
        $branchsetup->close();

        while ($bsrow = $bsresult->fetch_assoc()) {
            switch ($bsrow['ConfigName']) {
                case 'BRANCHNAME':
                    $branchname = $bsrow["Value"];
                    break;
                case 'ORGNAME':
                    $orgname = $bsrow["Value"];
                    break;
                case 'BRANCHADDRESS':
                    $orgaddress = $bsrow["Value"];
                    break;
                case 'BRANCHTELNO':
                    $orgtelno = $bsrow["Value"];
                    break;
            }
        }

        // $qrytest = "SELECT cdate FROM tbl_glsnapshot WHERE STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE('$cdate','%Y-%m-%d')";

        $stmt = $this->conn->prepare("SELECT cdate FROM tbl_glsnapshot WHERE STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d')");
        $stmt->bind_param("s",$cdate);
        $stmt->execute();
        $resultsnapshot = $stmt->get_result();
        $stmt->close();

        $stmt = $this->conn->prepare("SELECT * FROM tbl_glpostingdate WHERE STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d')");
        $stmt->bind_param("s",$cdate);
        $stmt->execute();
        $resultpostingdate = $stmt->get_result();
        $stmt->close();

        if($resultsnapshot->num_rows <= 0 && $resultpostingdate->num_rows <= 0){
            $stmt = $this->conn->prepare("INSERT INTO tbl_glpostingdate (cdate,user) VALUES (?,?)");
            $stmt->bind_param("ss",$cdate, $user);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE tbl_books SET postingstat = 'YES' WHERE cdate = ?");
            $stmt->bind_param("s",$cdate);
            $stmt->execute();
            $stmt->close();

            $lastDate = $this->getLastDateOfPreviousMonth($cdate);

            $this->PostEntries($cdate);
            $this->PostGeneralLedger($cdate);
            $this->CreateTables();
            $this->ComputeBeginningBalance("tbl_glperfundbalance",$cdate);
            $this->ComputeTB("tbl_glperfundbalance","tbl_gltrialbalance",$cdate);
            $this->PreviousFSBalance("tbl_glcashflowprev",$lastDate);
            // // //New Function For Merchandise
            $this->GetPrevMonthMI("tbl_glmerchandisebeg",$lastDate);
            $this->PopulateGrossRev("tbl_glismerchandise","tbl_glisservice","tbl_glcashflowprev","tbl_glmerchandisebeg","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformGrossRevComputation("tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");
            $this->PopulatePerFundGrossRev("tbl_glismerchandise","tbl_glisservice","tbl_glcashflowprev","tbl_glmerchandisebeg","tbl_perfundbalance",$cdate,$branchname);
            $this->PerformPerFundGrossRevComputation("tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");
            // //End of New Function For Merchandise
            $this->PopulateFS("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformFSComputation("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");
            $this->PopulatePerFundFS("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformPerFundFSComputation("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");

            $this->ComputeBeginBalanceSnapshot("tbl_glbeginperfundbalance",$cdate);
            $this->ComputeBeginBalanceGL("tbl_glbeginperfundbalancegl","CURRENT",$cdate);
            $this->ComputeBeginBalanceSchedule("tbl_glbeginperfundbalancesched",$cdate);
            // // $this->BackupData();

            $status = "SUCCESS";
        }else{
            $status = "POSTED";
        }

        echo json_encode(array( 
            "STATUS" => $status,
            "DATE" => $cdate,
            // "qry" => $qrytest,
            // "lastDate" => $lastDate,
            // "branchname" => $branchname,
            // "sample" => $sample,
        ));
    }

    public function UndoPostGL($data){
        set_time_limit(0);

        $cdate = date("Y-m-d",strtotime($data["date"]));
        $message = "";
        $status = "";

        $user = $_SESSION['USERNAME'];
        
        $branchname = '';
        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();
        $branchsetup->close();

        while ($bsrow = $bsresult->fetch_assoc()) {
            switch ($bsrow['ConfigName']) {
                case 'BRANCHNAME':
                    $branchname = $bsrow["Value"];
                    break;
                case 'ORGNAME':
                    $orgname = $bsrow["Value"];
                    break;
                case 'BRANCHADDRESS':
                    $orgaddress = $bsrow["Value"];
                    break;
                case 'BRANCHTELNO':
                    $orgtelno = $bsrow["Value"];
                    break;
            }
        }

        $stmt = $this->conn->prepare("SELECT * FROM tbl_glpostingdate WHERE STR_TO_DATE(cdate, '%Y-%m-%d') = STR_TO_DATE('" . $cdate . "','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if($result->num_rows > 0){
            $stmt = $this->conn->prepare("DELETE FROM tbl_glentries WHERE STR_TO_DATE(cdate, '%Y-%m-%d') >= STR_TO_DATE('" . $cdate . "','%Y-%m-%d')");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("DELETE FROM tbl_glpostingdate WHERE STR_TO_DATE(cdate, '%Y-%m-%d') >= STR_TO_DATE('" . $cdate . "','%Y-%m-%d')");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("DELETE FROM tbl_glsnapshot WHERE STR_TO_DATE(cdate, '%Y-%m-%d') >= STR_TO_DATE('" . $cdate . "','%Y-%m-%d')");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("DELETE FROM tbl_glslentries WHERE STR_TO_DATE(cdate, '%Y-%m-%d') >= STR_TO_DATE('" . $cdate . "','%Y-%m-%d')");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE tbl_books SET postingstat = 'NO' WHERE cdate = ?");
            $stmt->bind_param("s",$cdate);
            $stmt->execute();
            $stmt->close();

            if(date("Y",strtotime($cdate)) <> date("Y")){
                $forYearDate = date("Y",strtotime("-1 year", $cdate));

                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glperfundbalance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginperfundbalance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginperfundbalancegl");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginningbalance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("CREATE TABLE tbl_glperfundbalance LIKE monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginperfundbalance LIKE monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginperfundbalancegl LIKE monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginningbalance LIKE monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("INSERT INTO tbl_glperfundbalance SELECT * FROM monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginperfundbalance SELECT * FROM monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginperfundbalancegl SELECT * FROM monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginningbalance SELECT * FROM monthlyfs.tbl_" . $forYearDate . "balance");
                $stmt->execute();
                $stmt->close();
            }else{
                $this->CreateTables();
            }
            
            $this->ComputeBeginningBalance("tbl_glperfundbalance",$cdate);
            $this->ComputeTB("tbl_glperfundbalance","tbl_gltrialbalance",$cdate);
            $this->PreviousFSBalance("tbl_glcashflowprev",date("Y-m-d",strtotime("-1 month", strtotime($cdate))));
            $this->GetPrevMonthMI("tbl_glmerchandisebeg",date("Y-m-d",strtotime("-1 month", strtotime($cdate))));
            $this->PopulateGrossRev("tbl_glismerchandise","tbl_glisservice","tbl_glcashflowprev","tbl_glmerchandisebeg","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformGrossRevComputation("tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");
            $this->PopulatePerFundGrossRev("tbl_glismerchandise","tbl_glisservice","tbl_glcashflowprev","tbl_glmerchandisebeg","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformPerFundGrossRevComputation("tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");
            $this->PopulateFS("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformFSComputation("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");
            $this->PopulatePerFundFS("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance",$cdate,$branchname);
            $this->PerformPerFundFSComputation("tbl_glincomestatement","tbl_glbalancesheet","tbl_glismerchandise","tbl_glisservice","tbl_glperfundbalance");

            $this->ComputeBeginBalanceSnapshot("tbl_glbeginperfundbalance",$cdate);
            $this->ComputeBeginBalanceGL("tbl_glbeginperfundbalancegl","CURRENT",$cdate);
            $this->ComputeBeginBalanceSchedule("tbl_glbeginperfundbalancesched",$cdate);

            if($cdate <> date("Y-m-d")){
                $status = "SUCCESS1";
                $message = "POSTED Transactions from the inclusive dates (" . $cdate . " - " . date("m/d/Y") . "), succesfully rolled back.";
            }else{
                $status = "SUCCESS2";
                $message = "POSTED Transactions for the date (" . $cdate . "), succesfully rolled back.";
            }
        }else{
            $message = "No Transaction to UNDO with the date supplied.";
            $status = "NODATA";
        }

        echo json_encode(array( 
            "STATUS" => $status,
            "MESSAGE" => $message,
        ));
    }

    // ==========
    private function PostEntries($cdate){
        $stmt = $this->conn->prepare("SELECT * FROM tbl_glfunds");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $fund = $row["fundname"];

            $forCluster = $forBranch = $forFund = $forProduct = $forProgram = $forPO = "";
            $forBookType = $forReference = $forAcctNo = $forAcctTitle = $forSLNo = "";
            $forSLType = $forGLNo = $forParticulars = $forPage = "";

            $forDebit = $forCredit = $forSLDRCR = 0.0;

            $stmt = $this->conn->prepare("SELECT tag, branch, fund, product, program, po, booktype, acctno, accttitle, slno, glno, slname, explanation, drother, crother, sldrcr, bookpage, orno, cvno, jvno FROM tbl_books WHERE CDate = STR_TO_DATE(?,'%Y-%m-%d') AND fund = ?");
            $stmt->bind_param("ss",$cdate,$fund);
            $stmt->execute();
            $resultbks = $stmt->get_result();
            $stmt->close();

            while ($rowbks = $resultbks->fetch_assoc()) {

                $forCluster = $rowbks["tag"]; 
                $forBranch = $rowbks["branch"];
                $forFund = $rowbks["fund"];
                $forProduct = $rowbks["product"];
                $forProgram = $rowbks["program"];
                $forPO = $rowbks["po"];
                $forBookType = $rowbks["booktype"];
                $forAcctNo = $rowbks["acctno"];
                $forAcctTitle = $rowbks["accttitle"];
                $forSLNo = $rowbks["slno"];
                $forGLNo = $rowbks["glno"];
                $forSLType = $rowbks["slname"];
                $forParticulars = $rowbks["explanation"];
                $forDebit = $rowbks["drother"];
                $forCredit = $rowbks["crother"];
                $forSLDRCR = $rowbks["sldrcr"];
                $forPage = $rowbks["bookpage"];

                switch ($forBookType){
                    case 'CRB':
                        $forReference = "OR-" . $rowbks["orno"];
                        break;
                        
                    case 'CDB':
                        $forReference = "CV-". $rowbks["cvno"];
                        break;
                        
                    case 'GJ':
                            $forReference = "JV-". $rowbks["jvno"];
                        break;
                }

                if ($forSLDRCR != 0){
                    if ($forSLNo == "0" || $forSLNo == "-") {
                        $forSLNo = $rowbks["acctno"];
                    }

                    $stmt = $this->conn->prepare("SELECT acctitles FROM tbl_accountcodes WHERE acctcodes = ?");
                    $stmt->bind_param("s",$forGLNo);
                    $stmt->execute();
                    $resultbks = $stmt->get_result();
                    $stmt->close();
                    $row = $resultbks->fetch_assoc();
                    $forAcctName = $row["accttitles"];

                    if ($forSLDRCR < 0) {
                        $forDebit = 0;
                        $forCredit = $forSLDRCR;

                        $stmt = $this->conn->prepare("INSERT INTO tbl_glslentries (cdate,cluster, branch, fund, product, program, po, booktype, reference, acctno, glname, glno, accttitle, sltype, particulars, debit, credit, page) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("sssssssssssssssssss",$cdate, $forCluster, $forBranch, $forFund, $forProduct, $forProgram, $forPO, $forBookType, $forReference, $forSLNo, $forAcctName, $forGLNo, $forAcctTitle, $forSLType, $forParticulars, $forDebit, $forCredit, $forPage);
                        $stmt->execute();
                        $stmt->close();
                    } else if ($forSLDRCR > 0) {
                        $forDebit = $forSLDRCR;
                        $forCredit = 0;

                        $stmt = $this->conn->prepare("INSERT INTO tbl_glslentries (cdate,cluster, branch, fund, product, program, po, booktype, reference, acctno, glname, glno, accttitle, sltype, particulars, debit, credit, page) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("sssssssssssssssssss",$cdate, $forCluster, $forBranch, $forFund, $forProduct, $forProgram, $forPO, $forBookType, $forReference, $forSLNo, $forAcctName, $forGLNo, $forAcctTitle, $forSLType, $forParticulars, $forDebit, $forCredit, $forPage);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    if ($forDebit != 0){
                        $forCredit = 0;

                        $stmt = $this->conn->prepare("INSERT INTO tbl_glsnapshot (cdate,cluster, branch, fund, product, program, po, booktype, reference, acctno, accttitle, particulars, debit, credit, page) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("sssssssssssssss",$cdate, $forCluster, $forBranch, $forFund, $forProduct, $forProgram, $forPO, $forBookType, $forReference, $forSLNo, $forAcctTitle, $forParticulars, $forDebit, $forCredit, $forPage);
                        $stmt->execute();
                        $stmt->close();
                    } else if ($forCredit != 0){
                        $forDebit = 0;

                        $stmt = $this->conn->prepare("INSERT INTO tbl_glsnapshot (cdate,cluster, branch, fund, product, program, po, booktype, reference, acctno, accttitle, particulars, debit, credit, page) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("sssssssssssssss",$cdate, $forCluster, $forBranch, $forFund, $forProduct, $forProgram, $forPO, $forBookType, $forReference, $forSLNo, $forAcctTitle, $forParticulars, $forDebit, $forCredit, $forPage);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }

    private function PostGeneralLedger($cdate){
        $forGLDate = $forGLCluster = $forGLBranch = ""; 
        $forGLFund = $forGLProduct = $forGLProgram = "";
        $forGLPO = $forGLBookType = $forGLReference = "";
        $forGLAcctNo = $forGLAcctTitle = $forGLPage = ""; 
        
        $forGLDebit = $forGLCredit = 0.0;

        $stmt = $this->conn->prepare("SELECT cdate, cluster, branch, fund, product, program, po, booktype, reference,  acctno, accttitle, particulars, SUM(debit) AS fordebit, SUM(credit) AS forcredit, page FROM tbl_glsnapshot WHERE booktype <> 'GJ' AND STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') GROUP BY booktype, fund, acctno");
        $stmt->bind_param("s",$cdate);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $forGLDate = $row["cdate"];
            $forGLCluster = $row["cluster"];
            $forGLBranch = $row["branch"];
            $forGLFund = $row["fund"];
            $forGLProduct = $row["product"];
            $forGLProgram = $row["program"];
            $forGLPO = $row["po"];
            $forGLReference = $row["reference"];
            $forGLAcctNo = $row["acctno"];
            $forGLAcctTitle = $row["accttitle"];
            $forGLDebit = $row["fordebit"];
            $forGLCredit = $row["forcredit"];
            $forGLBookType = $row["booktype"];
            $forGLPage = $row["page"];

            $forGLParticulars = "TOTAL ".$forGLBookType." FOR THE DAY";

            $stmt = $this->conn->prepare("INSERT INTO tbl_glslentries (cdate,cluster, branch, fund, product, program, po, booktype, reference, acctno, accttitle, particulars, debit, credit, page) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssssssssssssss",$forGLDate, $forGLCluster, $forGLBranch, $forGLFund, $forGLProduct, $forGLProgram, $forGLPO, $forGLBookType, $forGLReference, $forGLAcctNo, $forGLAcctTitle, $forGLParticulars, $forGLDebit, $forGLCredit, $forGLPage);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $this->conn->prepare("INSERT INTO tbl_glentries SELECT * FROM tbl_glsnapshot WHERE booktype = 'GJ' AND STR_TO_DATE(cdate,'%Y-%m-%d') = DATE_FORMAT(?,'%Y-%m-%d')");
        $stmt->bind_param("s",$cdate);
        $stmt->execute();
        $stmt->close();
    }

    private function CreateTables(){
        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glperfundbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginperfundbalance");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginperfundbalancegl");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginperfundbalancesched");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glbeginperfundbalancesl");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glperfundbalance LIKE tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginperfundbalance LIKE tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginperfundbalancegl LIKE tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginperfundbalancesched LIKE tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glbeginperfundbalancesl LIKE tbl_glslbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glperfundbalance SELECT * FROM tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginperfundbalance SELECT * FROM tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginperfundbalancegl SELECT * FROM tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginperfundbalancesched SELECT * FROM tbl_glbeginningbalance");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glbeginperfundbalancesl SELECT * FROM tbl_glslbeginningbalance");
        $stmt->execute();
        $stmt->close();
    }

    private function ComputeBeginningBalance($TableBalance,$cdate){
        // $this->__construct();

        $this->DropCreateGLSnapshotYear($cdate);

        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while($row = $result->fetch_assoc()){
            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS fortotal, acctno, accttitle FROM tbl_gladjustments WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = " . floatval(date("Y",strtotime($cdate))) - 1 . " GROUP BY acctno");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $strFunds = $this->StringFund(DB,"" . $TableBalance . "");

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS foramount, acctno, accttitle FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) GROUP BY acctno");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " SET consolidated = ROUND(" . $strFunds . ",2)");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function ComputeTB($TableBalance,$TableTB,$cdate){
        $strFunds = $this->StringZeroFund($TableTB);

        $stmt = $this->conn->prepare("UPDATE " . $TableTB . " SET cdate = '" . $cdate . "', consolidated = 0, ". $strFunds);
        $stmt->execute();
        $stmt->close();

        $strFunds = $this->StringFundTB();

        $stmt = $this->conn->prepare("UPDATE " . $TableTB . " tb INNER JOIN " . $TableBalance . " pb ON tb.acctno = pb.acctno SET tb.consolidated = ROUND(pb.consolidated,2), " . $strFunds);
        $stmt->execute();
        $stmt->close();

        $strFunds = $this->StringTBTotal($TableBalance);

        $stmt = $this->conn->prepare("UPDATE " . $TableTB . " SET consolidated = (SELECT ROUND(SUM(consolidated),2) FROM " . $TableBalance . "), " . $strFunds . " WHERE acctno = 'TOTAL'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glprinttb");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glprinttb LIKE " . $TableTB . "");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glprinttb SELECT * FROM " . $TableTB . "");
        $stmt->execute();
        $stmt->close();
    }

    private function PreviousFSBalance($TableBalance,$cfdate){
        // $cfyear = date("Y",strtotime($cfdate));
        $cfyear = $cfdate;

        if($cfyear != date("Y")){
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'monthlyfs' AND TABLE_NAME = 'tbl_" . floatval($cfyear) - 1 . "balance'");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            if(floatval($row["count"]) == 0){
                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS " . $TableBalance . "");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("CREATE TABLE " . $TableBalance . " LIKE tbl_glbeginningbalance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("INSERT INTO " . $TableBalance . " SELECT * FROM tbl_glbeginningbalance");
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS " . $TableBalance . "");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("CREATE TABLE " . $TableBalance . " LIKE monthlyfs.tbl_".floatval($cfyear-1)."balance");
                $stmt->execute();
                $stmt->close();

                $stmt = $this->conn->prepare("INSERT INTO " . $TableBalance . " SELECT * FROM monthlyfs.tbl_".floatval($cfyear-1)."balance");
                $stmt->execute();
                $stmt->close();

            }
        }else{
            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS " . $TableBalance . "");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE " . $TableBalance . " LIKE tbl_glbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO " . $TableBalance . " SELECT * FROM tbl_glbeginningbalance");
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glsnapshotyear");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glsnapshotyear LIKE tbl_glsnapshot");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glsnapshotyear SELECT * FROM tbl_glsnapshot WHERE YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE(?,'%Y-%m-%d')) AND acctno = '11910'");
        $stmt->bind_param("s",$cfdate);
        $stmt->execute();
        $stmt->close();


        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND COLUMN_NAME NOT IN ('acctno', 'accttitle', 'category', 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS fortotal, acctno, accttitle FROM tbl_gladjustments WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = " . floatval(date("Y",strtotime($cfdate))) - 1 . " AND acctno = '11910' GROUP BY acctno");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $strFunds = $this->StringFund(DB,"" . $TableBalance . "");

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS foramount, acctno, accttitle FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE(?,'%Y-%m-%d') AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE(?,'%Y-%m-%d')) GROUP BY acctno");
            $stmt->bind_param("ss",$cfdate,$cfdate);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " SET consolidated = ROUND(" . $strFunds . ",2)");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function GetPrevMonthMI($TableMSBeg,$cdate){
        $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " SET thisday = 0, thismonth = 0, thisyear = 0");
        $stmt->execute();
        $stmt->close();

        // ==========

        $this->DropCreateGLSnapshotYear($cdate);

        // ========== Day of Last Month MI
        $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND acctno = '11910' GROUP BY acctno");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.fund = 'CONSOLIDATED'");
        $stmt->execute();
        $stmt->close();

        // ========== Last Month MI
        $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND MONTH(STR_TO_DATE(cdate,'%Y-%m-%d')) = MONTH(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND acctno = '11910' GROUP BY acctno");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.fund = 'CONSOLIDATED'");
        $stmt->execute();
        $stmt->close();

        // ==========
        $stmt = $this->conn->prepare("SELECT * FROM " . $TableMSBeg . " WHERE fund <> 'consolidated'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while($row = $result->fetch_assoc()){
            $forFund = $row["fund"];

            // ========== Day of Last Month MI
            $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%m/%d/%Y') <= STR_TO_DATE('" . $cdate . "','%m/%d/%Y') AND MONTH(STR_TO_DATE(cdate,'%m/%d/%Y')) = MONTH(STR_TO_DATE('" . $cdate . "','%m/%d/%Y')) AND YEAR(STR_TO_DATE(cdate,'%m/%d/%Y')) = YEAR(STR_TO_DATE('" . $cdate . "','%m/%d/%Y')) AND acctno = '11910' GROUP BY acctno");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.fund = '" . $forFund . "'");
            $stmt->execute();
            $stmt->close();

            // ========== Last Month MI
            $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%m/%d/%Y') <= STR_TO_DATE('" . $cdate . "','%m/%d/%Y') AND MONTH(STR_TO_DATE(cdate,'%m/%d/%Y')) = MONTH(STR_TO_DATE('" . $cdate . "','%m/%d/%Y')) AND YEAR(STR_TO_DATE(cdate,'%m/%d/%Y')) = YEAR(STR_TO_DATE('" . $cdate . "','%m/%d/%Y')) AND acctno = '11910' GROUP BY acctno");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.fund = '" . $forFund . "'");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function PopulateGrossRev($TableMS,$TableISS,$TableCFPrev,$TableMSBeg,$TableBalance,$cdate,$branchname){
        $this->DropCreateGLSnapshotYear($cdate);

        // ==========
        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " SET cdate = '" . $cdate . "', fund = 'CONSOLIDATED', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " SET cdate = '" . $cdate . "', fund = 'CONSOLIDATED', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0");
        $stmt->execute();
        $stmt->close();

        // ========== This day

        $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE('" . $cdate . "', '%Y-%m-%d') GROUP BY acctno");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE beg.acctno = '41100'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.acctno = '41110'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.acctno = '41120'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '412'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT thisday AS forday FROM " . $TableMSBeg . " WHERE acctno = '11910' AND fund = 'CONSOLIDATED') b SET a.thisday = ROUND(b.forday,2) WHERE acctno = 'MERCHANDISE BEG'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.acctno = '11910'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE beg.acctno = '42100'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '422'");
        $stmt->execute();
        $stmt->close();

        // ========== This month
        
        $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND MONTH(STR_TO_DATE(cdate,'%Y-%m-%d')) = MONTH(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) GROUP BY acctno");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE beg.acctno = '41100'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.acctno = '41110'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.acctno= '41120'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '412'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT thismonth AS formonth FROM " . $TableMSBeg . " WHERE acctno = '11910' AND fund = 'CONSOLIDATED') b SET a.thismonth = ROUND(b.formonth,2) WHERE acctno = 'MERCHANDISE BEG'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.acctno = '11910'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE beg.acctno = '42100'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '422'");
        $stmt->execute();
        $stmt->close();

        // ========== This year

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated * -1,2) WHERE beg.acctno = '41100' AND beg.category <> 'TITLE'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE beg.acctno = '41110'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE beg.acctno = '41120'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE SUBSTRING(beg.acctno,1,3) = '412'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " beg INNER JOIN " . $TableCFPrev . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE beg.fund = 'CONSOLIDATED'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT thisyear AS foryear FROM " . $TableMSBeg . " WHERE acctno = '11910' AND fund = 'CONSOLIDATED') b SET a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'MERCHANDISE BEG'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE beg.acctno = '11910'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated * -1,2) WHERE beg.acctno = '42100' AND beg.category <> 'TITLE'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE SUBSTRING(beg.acctno,1,3) = '422'");
        $stmt->execute();
        $stmt->close();
    }

    private function PerformGrossRevComputation($TableMS,$TableISS,$TableBalance){
        
        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT (sales.thisday - allowance.thisday) - discount.thisday AS forday, (sales.thismonth - allowance.thismonth) - discount.thismonth AS formonth, (sales.thisyear - allowance.thisyear) - discount.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . " WHERE acctno = '41100') AS sales, (SELECT * FROM " . $TableMS . " WHERE acctno = '41110') AS allowance, (SELECT * FROM " . $TableMS . " WHERE acctno = '41120') AS discount) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'SALES'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableMS . " WHERE acctno = '41200' OR acctno = '11910') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'AFS'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT afs.thisday - mibeg.thisday AS forday, afs.thismonth - mibeg.thismonth AS formonth, afs.thisyear - mibeg.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . " WHERE acctno = 'AFS') AS afs, (SELECT * FROM " . $TableMS . " WHERE acctno = 'MERCHANDISE BEG') AS mibeg) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'NETP'");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT afs.thisday - miend.thisday AS forday, afs.thismonth - miend.thismonth AS formonth, afs.thisyear - miend.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . " WHERE acctno = 'AFS') AS afs, (SELECT * FROM " . $TableMS . " WHERE acctno = '11910') AS miend) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'COS'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableMS . " a, (SELECT sales.thisday - cos.thisday AS forday, sales.thismonth - cos.thismonth AS formonth, sales.thisyear - cos.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . " WHERE acctno = 'SALES') AS sales, (SELECT * FROM " . $TableMS . " WHERE acctno = 'COS') AS cos) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'INCOME'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableISS . " WHERE SUBSTRING(acctno,1,3) = '422') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'COS'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableISS . " a, (SELECT service.thisday - cos.thisday AS forday, service.thismonth - cos.thismonth AS formonth, service.thisyear - cos.thisyear AS foryear FROM (SELECT * FROM " . $TableISS . " WHERE acctno = '42100') AS service, (SELECT * FROM " . $TableISS . " WHERE acctno = 'COS') AS cos) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'INCOME'");
        $stmt->execute();
        $stmt->close();
    }

    private function PopulatePerFundGrossRev($TableMS,$TableISS,$TableCFPrev,$TableMSBeg,$TableBalance,$cdate,$branchname){
        $this->DropCreateGLSnapshotYear($cdate);

        // ==========
        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while($row = $result->fetch_assoc()){
            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " SET cdate = '" . $cdate . "', fund = '" . strtoupper($forFund) . "', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0 ");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . $forFund . " SET cdate = '" . $cdate . "', fund = '" . strtoupper($forFund) . "', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0");
            $stmt->execute();
            $stmt->close();

            // ========== This day

            $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE('" . $cdate . "', '%Y-%m-%d') GROUP BY acctno");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE beg.acctno = '41100'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.acctno = '41110'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.acctno = '41120'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '412'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT thisday AS forday FROM " . $TableMSBeg . " WHERE acctno = '11910' AND fund = '" . $forFund . "') b SET a.thisday = ROUND(b.forday,2) WHERE acctno = 'MERCHANDISE BEG'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE beg.acctno = '11910'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE beg.acctno = '42100'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '422'");
            $stmt->execute();
            $stmt->close();

            // ========== This month
            
            $stmt = $this->conn->prepare("DELETE FROM tbl_glrev");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_glrev(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND MONTH(STR_TO_DATE(cdate,'%Y-%m-%d')) = MONTH(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) GROUP BY acctno");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE beg.acctno = '41100'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.acctno = '41110'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.acctno= '41120'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '412'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT thismonth AS formonth FROM " . $TableMSBeg . " WHERE acctno = '11910' AND fund = '" . $forFund . "') b SET a.thismonth = ROUND(b.formonth,2) WHERE acctno = 'MERCHANDISE BEG'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE beg.acctno = '11910'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE beg.acctno = '42100'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . " beg INNER JOIN tbl_glrev end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,3) = '422'");
            $stmt->execute();
            $stmt->close();

            // ========== This year

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . " * -1,2) WHERE beg.acctno = '41100' AND beg.category <> 'TITLE'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . ",2) WHERE beg.acctno = '41110'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . ",2) WHERE beg.acctno = '41120'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . ",2) WHERE SUBSTRING(beg.acctno,1,3) = '412'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMSBeg . " beg INNER JOIN " . $TableCFPrev . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . ",2) WHERE beg.fund = '" . $forFund . "'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT thisyear AS foryear FROM " . $TableMSBeg . " WHERE acctno = '11910' AND fund = '" . $forFund . "') b SET a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'MERCHANDISE BEG'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . ",2) WHERE beg.acctno = '11910'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . $forFund .  " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . " * -1,2) WHERE beg.acctno = '42100' AND beg.category <> 'TITLE'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." . $forFund . ",2) WHERE SUBSTRING(beg.acctno,1,3) = '422'");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function PerformPerFundGrossRevComputation($TableMS,$TableISS,$TableBalance){
        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while($row = $result->fetch_assoc()){
            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT (sales.thisday - allowance.thisday) - discount.thisday AS forday, (sales.thismonth - allowance.thismonth) - discount.thismonth AS formonth, (sales.thisyear - allowance.thisyear) - discount.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = '41100') AS sales, (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = '41110') AS allowance, (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = '41120') AS discount) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'SALES'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableMS . $forFund . " WHERE acctno = '41200' OR acctno = '11910') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'AFS'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT afs.thisday - mibeg.thisday AS forday, afs.thismonth - mibeg.thismonth AS formonth, afs.thisyear - mibeg.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = 'AFS') AS afs, (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = 'MERCHANDISE BEG') AS mibeg) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'NETP'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT afs.thisday - miend.thisday AS forday, afs.thismonth - miend.thismonth AS formonth, afs.thisyear - miend.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = 'AFS') AS afs, (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = '11910') AS miend) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'COS'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableMS . $forFund . " a, (SELECT sales.thisday = cos.thisday AS forday, sales.thismonth - cos.thismonth AS formonth, sales.thisyear - cos.thisyear AS foryear FROM (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = 'SALES') AS sales, (SELECT * FROM " . $TableMS . $forFund . " WHERE acctno = 'COS') AS cos) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'INCOME'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableISS . $forFund . " WHERE SUBSTRING(acctno,1,3) = '422') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'COS'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableISS . $forFund . " a, (SELECT service.thisday - cos.thisday AS forday, service.thismonth - cos.thismonth AS formonth, service.thisyear - cos.thisyear AS foryear FROM (SELECT * FROM " . $TableISS . $forFund . " WHERE acctno = '42100') AS service, (SELECT * FROM " . $TableISS . $forFund . " WHERE acctno = 'COS') AS cos) b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'INCOME'");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function PopulateFS($TableIS,$TableBS,$TableMS,$TableISS,$TableBalance,$cdate,$branchname){
        $this->DropCreateGLSnapshotYear($cdate);
        
        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " SET cdate = '" . $cdate . "', fund = 'CONSOLIDATED', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " SET cdate = '" . $cdate . "', fund = 'CONSOLIDATED', branch = '" . $branchname . "', thisday = 0, thisday = 0, thisday = 0");
        $stmt->execute();
        $stmt->close();


        // ============= This Day IS and BS
        $stmt = $this->conn->prepare("DELETE FROM tbl_glis");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("INSERT INTO tbl_glis(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE('" . $cdate . "', '%Y-%m-%d') GROUP BY acctno");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thisday AS foramount FROM " . $TableMS . " WHERE acctno = 'INCOME') b SET a.thisday = ROUND(b.foramount,2) WHERE acctno = '41000'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thisday AS foramount FROM " . $TableISS . " WHERE acctno = 'INCOME') b SET a.thisday = ROUND(b.foramount,2) WHERE acctno = '42000'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,2) = '43'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '5'");
        $stmt->execute();
        $stmt->close();


        // =============
        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '1'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '2'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '3'");
        $stmt->execute();
        $stmt->close();


        // ============= This Month IS and BS
        $stmt = $this->conn->prepare("DELETE FROM tbl_glis");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("INSERT INTO tbl_glis(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND MONTH(STR_TO_DATE(cdate,'%Y-%m-%d')) = MONTH(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) GROUP BY acctno");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thismonth AS foramount FROM " . $TableMS . " WHERE acctno = 'INCOME') b SET a.thismonth = ROUND(b.foramount,2) WHERE acctno = '41000'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thismonth AS foramount FROM " . $TableISS . " WHERE acctno = 'INCOME') b SET a.thismonth = ROUND(b.foramount,2) WHERE acctno = '42000'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,2) = '43'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '5'");
        $stmt->execute();
        $stmt->close();


        // =============
        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '1'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '2'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '3'");
        $stmt->execute();
        $stmt->close();


        // ============= This Year IS AND BS
        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thisyear AS foramount FROM " . $TableMS . " WHERE acctno = 'INCOME') b SET a.thisyear = ROUND(b.foramount,2) WHERE acctno = '41000'");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thisyear AS foramount FROM " . $TableISS . " WHERE acctno = 'INCOME') b SET a.thisyear = ROUND(b.foramount,2) WHERE acctno = '42000'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated * -1,2) WHERE SUBSTRING(beg.acctno,1,2) = '43'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE SUBSTRING(beg.acctno,1,1) = '5'");
        $stmt->execute();
        $stmt->close();


        // =============
        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated,2) WHERE SUBSTRING(beg.acctno,1,1) = '1'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '2'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end.consolidated * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '3'");
        $stmt->execute();
        $stmt->close();
    }

    private function PerformFSComputation($TableIS,$TableBS,$TableMS,$TableISS,$TableBalance){
         
        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . " WHERE SUBSTRING(acctno,1,1) = '4') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'REVENUES'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . " WHERE SUBSTRING(acctno,1,2) = '51' OR SUBSTRING(acctno,1,2) = '52' OR SUBSTRING(acctno,1,2) = '53' OR SUBSTRING(acctno,1,2) = '54') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL SAE'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . " WHERE acctno = 'TOTAL SAE' OR acctno = '55000') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'EXPENSES'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . " WHERE acctno = 'REVENUES') b, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . " WHERE acctno = 'EXPENSES') c SET a.thisday = ROUND(b.forday - c.forday,2), a.thismonth = ROUND(b.formonth - c.formonth,2), a.thisyear = ROUND(b.foryear - c.foryear,2) WHERE acctno = 'PROFIT BEFORE'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . " WHERE acctno = 'PROFIT BEFORE' OR acctno = 'GRANTS') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'BEFORE TAX'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableIS . " a, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . " WHERE acctno = 'BEFORE TAX') b, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . " WHERE acctno = '56000' OR acctno = '57000') c SET a.thisday = ROUND(b.forday - c.forday,2), a.thismonth = ROUND(b.formonth - c.formonth,2), a.thisyear = ROUND(b.foryear - c.foryear,2) WHERE acctno = 'PROFIT AFTER'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE tbl_glnetincome a, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . " WHERE acctno = 'BEFORE TAX') b, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . " WHERE acctno = '56000' OR acctno = '57000') c SET a.thisday = ROUND(b.forday - c.forday,2), a.thismonth = ROUND(b.formonth - c.formonth,2), a.thisyear = ROUND(b.foryear - c.foryear,2) WHERE fundname = 'CONSOLIDATED'");
        $stmt->execute();
        $stmt->close();

        // BALANCE SHEET

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE SUBSTRING(acctno,1,2) =  '11' OR SUBSTRING(acctno,1,2) = '12') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL CA'");
        $stmt->execute();
        $stmt->close();
        
        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE SUBSTRING(acctno,1,2) =  '13' OR SUBSTRING(acctno,1,2) = '14') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL NON-CA'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE acctno = 'TOTAL CA' OR acctno = 'TOTAL NON-CA') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL ASSETS'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE SUBSTRING(acctno,1,2) = '21') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL CL'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE SUBSTRING(acctno,1,2) = '22' OR SUBSTRING(acctno,1,2) = '24') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL NON-CL'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE acctno = 'TOTAL CL' OR acctno = 'TOTAL NON-CL') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL LIABILITIES'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM tbl_glnetincome WHERE fundname = 'CONSOLIDATED') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'PROFIT AFTER'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE SUBSTRING(acctno,1,2) = '31' OR SUBSTRING(acctno,1,2) = '32' OR acctno = 'PROFIT AFTER') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL EQUITY'");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("UPDATE " . $TableBS . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . " WHERE acctno = 'TOTAL LIABILITIES' OR acctno = 'TOTAL EQUITY') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL LQ'");
        $stmt->execute();
        $stmt->close();
    }

    private function PopulatePerFundFS($TableIS,$TableBS,$TableMS,$TableISS,$TableBalance,$cdate,$branchname){

        $this->DropCreateGLSnapshotYear($cdate);

        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {

            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " SET cdate = '" .  $cdate . "', fund = '" . strtoupper($forFund) . "', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " SET cdate = '" .  $cdate . "', fund = '" . strtoupper($forFund) . "', branch = '" . $branchname . "', thisday = 0, thismonth = 0, thisyear = 0");
            $stmt->execute();
            $stmt->close();

            // =============

            // ============= This Day IS and BS

            $stmt = $this->conn->prepare("DELETE FROM tbl_glis");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("INSERT INTO tbl_glis(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" .  $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') = STR_TO_DATE('" .  $cdate . "', '%Y-%m-%d') GROUP BY acctno");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thisday AS foramount FROM " . $TableMS . $forFund . " WHERE acctno = 'INCOME') b SET a.thisday = ROUND(b.foramount,2) WHERE acctno = '41000'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thisday AS foramount FROM " . $TableISS . $forFund . " WHERE acctno = 'INCOME') b SET a.thisday = ROUND(b.foramount,2) WHERE acctno = '42000'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,2) = '43'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '5'");
            $stmt->execute();
            $stmt->close();


            // =============
            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '1'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '2'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thisday = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '3'");
            $stmt->execute();
            $stmt->close();


            // ============= This Month IS and BS
            $stmt = $this->conn->prepare("DELETE FROM tbl_glis");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("INSERT INTO tbl_glis(acctno, accttitle, amount) SELECT acctno, accttitle, ROUND(SUM(debit-credit),2) FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" .  $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" .  $cdate . "','%Y-%m-%d') AND MONTH(STR_TO_DATE(cdate,'%Y-%m-%d')) = MONTH(STR_TO_DATE('" .  $cdate . "','%Y-%m-%d')) AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" .  $cdate . "','%Y-%m-%d')) GROUP BY acctno");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thismonth AS foramount FROM " . $TableMS . $forFund . " WHERE acctno = 'INCOME') b SET a.thismonth = ROUND(b.foramount,2) WHERE acctno = '41000'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thismonth AS foramount FROM " . $TableISS . $forFund . " WHERE acctno = 'INCOME') b SET a.thismonth = ROUND(b.foramount,2) WHERE acctno = '42000'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,2) = '43'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '5'");
            $stmt->execute();
            $stmt->close();


            // =============
            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount,2) WHERE SUBSTRING(beg.acctno,1,1) = '1'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '2'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN tbl_glis end ON beg.acctno = end.acctno SET beg.thismonth = ROUND(end.amount * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '3'");
            $stmt->execute();
            $stmt->close();


            // ============= This Year IS and BS
            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thisyear AS foramount FROM " . $TableMS . $forFund . " WHERE acctno = 'INCOME') b SET a.thisyear = ROUND(b.foramount,2) WHERE acctno = '41000'");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thisyear AS foramount FROM " . $TableISS . $forFund . " WHERE acctno = 'INCOME') b SET a.thisyear = ROUND(b.foramount,2) WHERE acctno = '42000'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." .  $forFund . " * -1,2) WHERE SUBSTRING(beg.acctno,1,2) = '43'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." .  $forFund . ",2) WHERE SUBSTRING(beg.acctno,1,1) = '5'");
            $stmt->execute();
            $stmt->close();


            // =============
            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." .  $forFund . ",2) WHERE SUBSTRING(beg.acctno,1,1) = '1'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." .  $forFund . " * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '2'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " beg INNER JOIN " . $TableBalance . " end ON beg.acctno = end.acctno SET beg.thisyear = ROUND(end." .  $forFund . " * -1,2) WHERE SUBSTRING(beg.acctno,1,1) = '3'");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function PerformPerFundFSComputation($TableIS,$TableBS,$TableMS,$TableISS,$TableBalance){

        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {

            $forFund = $row["COLUMN_NAME"];

            // Income Statement
            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . $forFund . " WHERE SUBSTRING(acctno,1,1) = '4') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'REVENUES'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . $forFund . " WHERE SUBSTRING(acctno,1,2) = '51' OR SUBSTRING(acctno,1,2) = '52' OR SUBSTRING(acctno,1,2) = '53' OR SUBSTRING(acctno,1,2) = '54') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL SAE'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = 'TOTAL SAE' OR acctno = '55000') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'EXPENSES'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = 'REVENUES') b, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = 'EXPENSES') c SET a.thisday = ROUND(b.forday - c.forday,2), a.thismonth = ROUND(b.formonth - c.formonth,2), a.thisyear = ROUND(b.foryear - c.foryear,2) WHERE acctno = 'PROFIT BEFORE'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = 'PROFIT BEFORE' OR acctno = 'GRANTS') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'BEFORE TAX'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableIS . $forFund . " a, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = 'BEFORE TAX') b, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = '56000' or acctno = '57000') c SET a.thisday = ROUND(b.forday - c.forday,2), a.thismonth = ROUND(b.formonth - c.formonth,2), a.thisyear = ROUND(b.foryear - c.foryear,2) WHERE acctno = 'PROFIT AFTER'");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE tbl_glnetincome a, (SELECT thisday AS forday, thismonth AS formonth, thisyear AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = 'BEFORE TAX') b, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableIS . $forFund . " WHERE acctno = '56000' or acctno = '57000') c SET a.thisday = ROUND(b.forday - c.forday,2), a.thismonth = ROUND(b.formonth - c.formonth,2), a.thisyear = ROUND(b.foryear - c.foryear,2) WHERE fundname = '" . $forFund . "'");
            $stmt->execute();
            $stmt->close();


            // ============= Balance Sheet
            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE SUBSTRING(acctno,1,2) =  '11' OR SUBSTRING(acctno,1,2) = '12') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL CA'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE SUBSTRING(acctno,1,2) =  '13' OR SUBSTRING(acctno,1,2) = '14') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL NON-CA'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE acctno = 'TOTAL CA' OR acctno = 'TOTAL NON-CA') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL ASSETS'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE SUBSTRING(acctno,1,2) = '21') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL CL'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE SUBSTRING(acctno,1,2) = '22' OR SUBSTRING(acctno,1,2) = '24') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL NON-CL'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE acctno = 'TOTAL CL' OR acctno = 'TOTAL NON-CL') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL LIABILITIES'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM tbl_glnetincome WHERE fundname = '" . $forFund . "') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'PROFIT AFTER'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE SUBSTRING(acctno,1,2) = '31' OR SUBSTRING(acctno,1,2) = '32' OR acctno = 'PROFIT AFTER') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL EQUITY'");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBS . $forFund . " a, (SELECT SUM(thisday) AS forday, SUM(thismonth) AS formonth, SUM(thisyear) AS foryear FROM " . $TableBS . $forFund . " WHERE acctno = 'TOTAL LIABILITIES' OR acctno = 'TOTAL EQUITY') b SET a.thisday = ROUND(b.forday,2), a.thismonth = ROUND(b.formonth,2), a.thisyear = ROUND(b.foryear,2) WHERE acctno = 'TOTAL LQ'");
            $stmt->execute();
            $stmt->close();
        }
    }


    // ======================
    private function ComputeBeginBalanceSnapshot($TableBalance,$cdate){
        $this->DropCreateGLSnapshotYear($cdate);

        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while($row = $result->fetch_assoc()){
            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance . "");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS fortotal, acctno, accttitle FROM tbl_gladjustments WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = " . floatval(date("Y",strtotime($cdate))) - 1 . " GROUP BY acctno");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();


            // =============
            $strFunds = $this->StringFund(DB,$TableBalance);

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance . "");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS foramount, acctno, accttitle FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%Y-%m-%d') <= STR_TO_DATE('" . $cdate . "','%Y-%m-%d') AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) GROUP BY acctno");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " SET consolidated = ROUND(" . $strFunds . ",2)");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function ComputeBeginBalanceGL($TableBalance,$forStatus,$cdate){
        $this->DropCreateGLSnapshotYear($cdate);

        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while($row = $result->fetch_assoc()){
            $forFund = $row["COLUMN_NAME"];

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance . "");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS fortotal, acctno, accttitle FROM tbl_gladjustments WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND YEAR(STR_TO_DATE(cdate,'%m/%d/%Y')) = " . floatval(date("Y",strtotime($cdate))) - 1 . " GROUP BY acctno");
            $stmt->execute();
            $stmt->close();
            
            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $strFunds = $this->StringFund(DB,$TableBalance);

            if($forStatus == "CURRENT"){
                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance . "");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS foramount, acctno, accttitle FROM tbl_glsnapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND MONTH(STR_TO_DATE(cdate,'%Y-%m-%d')) < MONTH(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) AND YEAR(STR_TO_DATE(cdate,'%Y-%m-%d')) = YEAR(STR_TO_DATE('" . $cdate . "','%Y-%m-%d')) GROUP BY acctno");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " SET consolidated = ROUND(" . $strFunds . ",2)");
                $stmt->execute();
                $stmt->close();
            }else{
                $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance . "");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS foramount, acctno, accttitle FROM tbl_snapshotyear WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND STR_TO_DATE(cdate,'%m/%d/%Y') <= STR_TO_DATE('" . $cdate . "','%m/%d/%Y') AND YEAR(STR_TO_DATE(cdate,'%m/%d/%Y')) = YEAR(STR_TO_DATE('" . $cdate . "','%m/%d/%Y')) GROUP BY acctno");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
                $stmt->execute();
                $stmt->close();
    
                $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " SET consolidated = ROUND(" . $strFunds . ",2)");
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    private function ComputeBeginBalanceSchedule($TableBalance,$cdate){

        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $forFund = $row["COLUMN_NAME"];

            $strFunds = $this->StringFund(DB,$TableBalance);

            $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_gltempbeginningbalance");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("CREATE TABLE tbl_gltempbeginningbalance LIKE " . $TableBalance . "");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("INSERT INTO tbl_gltempbeginningbalance(" . $forFund . ", acctno, accttitle) SELECT ROUND(SUM(debit-credit),2) AS fortotal, acctno, accttitle FROM tbl_gladjustments WHERE REPLACE(REPLACE(fund,' ',''),'-','') = '" . $forFund . "' AND YEAR(STR_TO_DATE(cdate,'%m/%d/%Y')) = YEAR(STR_TO_DATE('" . $cdate . "','%m/%d/%Y')) GROUP BY acctno");
            $stmt->execute();
            $stmt->close(); 

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " beg INNER JOIN tbl_gltempbeginningbalance end ON beg.acctno = end.acctno SET beg." . $forFund . " = ROUND(beg." . $forFund . " + end." . $forFund . ",2)");
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE " . $TableBalance . " SET consolidated = ROUND(" . $strFunds . ",2)");
            $stmt->execute();
            $stmt->close();
        }
    }

    private function DropCreateGLSnapshotYear($cdate){
        $stmt = $this->conn->prepare("DROP TABLE IF EXISTS tbl_glsnapshotyear");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("CREATE TABLE tbl_glsnapshotyear LIKE tbl_glsnapshot");
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("INSERT INTO tbl_glsnapshotyear SELECT * FROM tbl_glsnapshot WHERE YEAR(STR_TO_DATE(cdate, '%Y-%m-%d')) = YEAR(STR_TO_DATE(?, '%Y-%m-%d'))");
        $stmt->bind_param("s",$cdate);
        $stmt->execute();
        $stmt->close();
    }

    
    // ======================
    private function StringFund($database,$table){
        $str = "";
        $count = 0;
        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $database . "' AND TABLE_NAME = '" . $table . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            if($count == 0){
                $str = $str . "" . $row["COLUMN_NAME"];
            }else{
                $str = $str . " + " . $row["COLUMN_NAME"];
            }
            $count++;
        }
        return $str;
    }

    private function StringZeroFund($table){
        $str = "";
        $count = 0;
        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $table . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            if(($result->num_rows - 1) == $count){
                $str = $str . "" . $row["COLUMN_NAME"] . " = 0 ";
            }else{
                $str = $str . "" . $row["COLUMN_NAME"] . " = 0, ";
            }
            $count++;
        }
        return $str;
    }

    private function StringFundTB(){
        $str = "";
        $count = 0;
        $stmt = $this->conn->prepare("SELECT * FROM tbl_glfunds");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $string = $this->removeSpecialCharacters($row["fundname"]);
            if(($result->num_rows - 1) == $count){
                $str = $str . "tb." . $string . " = ROUND(pb." . $string . ",2)";
            }else{
                $str = $str . "tb." . $string . " = ROUND(pb." . $string . ",2),";
            }
            $count++;
        }
        return $str;
    }

    private function StringTBTotal($TableBalance){
        $str = "";
        $count = 0;
        $stmt = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . DB . "' AND TABLE_NAME = '" . $TableBalance . "' AND (COLUMN_NAME <> 'id' AND COLUMN_NAME <> 'cdate' AND COLUMN_NAME <> 'acctno' AND COLUMN_NAME <> 'accttitle' AND COLUMN_NAME <> 'slno' AND COLUMN_NAME <> 'slname' AND COLUMN_NAME <> 'category' AND COLUMN_NAME <> 'consolidated')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            if(($result->num_rows -1) == $count){
                $str = $str . "" . $row["COLUMN_NAME"] . " = (SELECT ROUND(SUM(" . $row["COLUMN_NAME"] . "),2) FROM ".$TableBalance.")";
            }else{
                $str = $str . "" . $row["COLUMN_NAME"] . " = (SELECT ROUND(SUM(" . $row["COLUMN_NAME"] . "),2) FROM ".$TableBalance."),";
            }
            $count++;
        }
        return $str;
    }

    private function removeSpecialCharacters($string) {
        $pattern = '/[^a-zA-Z0-9]/';
        $replacement = '';
        $cleanString = strtolower(preg_replace($pattern, $replacement, $string));
        return $cleanString;
    }

    private function FillRow($data){
        $arr = [];
        while ($row = $data->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }

    function getLastDateOfPreviousMonth($forDate) {
        // Convert string date to DateTime object
        $date = new DateTime($forDate);
        
        // Subtract one month
        $date->modify('-1 month');
    
        // Get the last day of the previous month
        $lastDay = $date->format('t'); // 't' gives the number of days in the month
    
        // Construct the final date (last day of the previous month)
        return $date->format("Y-m") . "-" . $lastDay;
    }
}