<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    // ── Load page init data ──────────────────────────────────────────────
    public function LoadPage() {
        $branches = $this->SelectQuery("SELECT DISTINCT Value AS Branch FROM tbl_configuration WHERE ConfigOwner='BRANCH SETUP' AND ConfigName='BRANCHNAME'");
        $funds    = $this->SelectQuery("SELECT DISTINCT Fund FROM tbl_banksetup ORDER BY Fund");
        echo json_encode([
            "BRANCHES" => $branches,
            "FUNDS"    => $funds,
            "DB_ERROR" => $this->conn->error ?: null,
        ]);
    }

    // ── Equipment CRUD ───────────────────────────────────────────────────
    public function LoadEquipmentList() {
        $assetType = $_POST['AssetType'] ?? '';
        $status    = $_POST['Status'] ?? '';
        $statusWhere = $status ? " WHERE Status = '" . $this->conn->real_escape_string($status) . "'" : '';
        $statusAnd   = $status ? " AND Status = '" . $this->conn->real_escape_string($status) . "'" : '';

        if ($assetType === 'Fur Fix Equip') {
            $list  = $this->SelectQuery("SELECT *, FFId AS ID, PropertyNo, 'Fur Fix Equip' AS AssetType FROM tbl_ppe_furniture{$statusWhere} ORDER BY DateAcquired ASC");
            $table = 'furniture';
        } elseif ($assetType === 'Transpo Equipment') {
            $list  = $this->SelectQuery("SELECT *, TransId AS ID, PropertyNo, 'Transpo Equipment' AS AssetType FROM tbl_ppe_transpo{$statusWhere} ORDER BY DateAcquired ASC");
            $table = 'transpo';
        } elseif ($assetType === 'Leasehold Imp') {
            $list  = $this->SelectQuery("SELECT *, LeaseId AS ID, PropertyNo, 'Leasehold Imp' AS AssetType FROM tbl_ppe_leasehold{$statusWhere} ORDER BY DateAcquired ASC");
            $table = 'leasehold';
        } else {
            $sql = "
                SELECT FFId AS ID, PropertyNo, 'Fur Fix Equip' AS AssetType,
                       Description, DateAcquired, RefNo, NoOfUnits, EstUsefulLife, NoOfMonths, MonthStartedDepr,
                       TotalCostTransferred, TotalCost, MonthlyDepr, AccumDeprPrevYear, DeprThisYear, AccumDeprAsOfDate, NetBookValue,
                       DeprThisYearSummary, AccumAsOfDate,
                       LapJan, LapFeb, LapMar, LapApr, LapMay, LapJun, LapJul, LapAug, LapSep, LapOct, LapNov, LapDec, LapTotal,
                       Status
                FROM tbl_ppe_furniture{$statusWhere}
                UNION ALL
                SELECT TransId, PropertyNo, 'Transpo Equipment',
                       Description, DateAcquired, RefNo, NoOfUnits, EstUsefulLife, NoOfMonths, MonthStartedDepr,
                       NULL AS TotalCostTransferred, TotalCost, MonthlyDepr, AccumDeprPrevYear, DeprThisYear, AccumDeprAsOfDate, NetBookValue,
                       DeprThisYearSummary, AccumAsOfDate,
                       LapJan, LapFeb, LapMar, LapApr, LapMay, LapJun, LapJul, LapAug, LapSep, LapOct, LapNov, LapDec, LapTotal,
                       Status
                FROM tbl_ppe_transpo{$statusWhere}
                UNION ALL
                SELECT LeaseId, PropertyNo, 'Leasehold Imp',
                       Description, DateAcquired, RefNo, NoOfUnits, EstUsefulLife, NoOfMonths, MonthStartedDepr,
                       NULL AS TotalCostTransferred, TotalCost, MonthlyDepr, AccumDeprPrevYear, DeprThisYear, AccumDeprAsOfDate, NetBookValue,
                       DeprThisYearSummary, AccumAsOfDate,
                       LapJan, LapFeb, LapMar, LapApr, LapMay, LapJun, LapJul, LapAug, LapSep, LapOct, LapNov, LapDec, LapTotal,
                       Status
                FROM tbl_ppe_leasehold{$statusWhere}
                ORDER BY DateAcquired ASC";
            $list  = $this->SelectQuery($sql);
            $table = 'all';
        }

        echo json_encode(["LIST" => $list, "TABLE" => $table]);
    }

    public function SaveEquipment($data) {
        $totalCost  = floatval($data['UnitCost']) * intval($data['NoOfUnits']);
        $salvage    = floatval($data['SalvageValue'] ?? 0);
        $life       = max(1, intval($data['UsefulLifeYears'] ?? 5));
        $monthlyDep = ($totalCost - $salvage) / ($life * 12);
        $nbv        = $totalCost - floatval($data['AccumulatedDep'] ?? 0);
        $user       = $_SESSION['USERNAME'] ?? '-';

        if (!empty($data['ID'])) {
            // UPDATE — TransactionID stays unchanged
            $stmt = $this->conn->prepare("UPDATE tbl_equipment SET
                Category=?, Branch=?, Department=?, AssetType=?, AssetName=?, PropertyNo=?,
                RefType=?, RefNo=?, NoOfUnits=?, UnitCost=?, TotalCost=?, SalvageValue=?,
                UsefulLifeYears=?, NoOfMonths=?, MonthStartedDepr=?, MonthlyDep=?,
                NetBookValue=?, DateAcquired=?, Status=?, UpdatedBy=?
                WHERE ID=?");
            $noOfMonths = $life * 12;
            $stmt->bind_param("ssssssssiidddiisdssi",
                $data['Category'], $data['Branch'], $data['Department'],
                $data['AssetType'], $data['AssetName'], $data['PropertyNo'] ?? '',
                $data['RefType'], $data['RefNo'],
                $data['NoOfUnits'], $data['UnitCost'], $totalCost, $salvage,
                $life, $noOfMonths, $data['MonthStartedDepr'] ?? '',
                $monthlyDep, $nbv, $data['DateAcquired'], $data['Status'], $user,
                $data['ID']
            );
        } else {
            // INSERT — TransactionID auto-generated by trigger
            $stmt = $this->conn->prepare("INSERT INTO tbl_equipment
                (Category, Branch, Department, AssetType, AssetName, PropertyNo,
                 RefType, RefNo, NoOfUnits, UnitCost, TotalCost, SalvageValue,
                 UsefulLifeYears, NoOfMonths, MonthStartedDepr, MonthlyDep,
                 NetBookValue, DateAcquired, Status, CreatedBy)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $noOfMonths = $life * 12;
            $stmt->bind_param("ssssssssiiddiidsdsss",
                $data['Category'], $data['Branch'], $data['Department'],
                $data['AssetType'], $data['AssetName'], $data['PropertyNo'] ?? '',
                $data['RefType'], $data['RefNo'],
                $data['NoOfUnits'], $data['UnitCost'], $totalCost, $salvage,
                $life, $noOfMonths, $data['MonthStartedDepr'] ?? '',
                $monthlyDep, $nbv, $data['DateAcquired'], $data['Status'], $user
            );
        }

        $stmt->execute();
        $affected = $stmt->affected_rows;
        $newId    = $this->conn->insert_id;
        $stmt->close();

        echo json_encode(["STATUS" => $affected > 0 ? "SUCCESS" : "ERROR"]);
    }

    public function DisposeEquipment($data) {
        $id        = intval($data['ID']);
        $assetType = $data['AssetType'] ?? '';

        if ($assetType === 'Fur Fix Equip') {
            $sql = "UPDATE tbl_ppe_furniture SET Status='Disposed' WHERE FFId=?";
        } elseif ($assetType === 'Transpo Equipment') {
            $sql = "UPDATE tbl_ppe_transpo SET Status='Disposed' WHERE TransId=?";
        } elseif ($assetType === 'Leasehold Imp') {
            $sql = "UPDATE tbl_ppe_leasehold SET Status='Disposed' WHERE LeaseId=?";
        } else {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Unknown asset type."]);
            return;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(["STATUS" => $affected > 0 ? "SUCCESS" : "ERROR"]);
    }

    public function DeleteEquipment($data) {
        $stmt = $this->conn->prepare("DELETE FROM tbl_equipment WHERE ID=?");
        $stmt->bind_param("i", $data['ID']);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        echo json_encode(["STATUS" => $affected > 0 ? "SUCCESS" : "ERROR"]);
    }

    // ── Monthly depreciation run (update accumulated dep & NBV) ─────────
    public function RunMonthlyDepreciation() {
        $now   = new DateTime();
        $nowYM = (int)$now->format('Ym'); // e.g. 202603 — current month
        $currentYear = (int)$now->format('Y');
        $currentMonth = (int)$now->format('n'); // 1-12

        $updated = 0;

        // Helper: parse MonthStartedDepr (YYYY-MM) to integer YYYYMM
        $parseYM = function($val) {
            if (empty($val)) return 999999;
            return (int)str_replace('-', '', substr(trim($val), 0, 7));
        };

        // Helper: calculate lapsing schedule for current year (up to current month)
        $calculateLapSchedule = function($monthlyDepr, $monthStartedDepr, $currentYear, $currentMonth) {
            $lapMonths = array_fill(0, 12, 0.0);
            
            if (empty($monthStartedDepr)) return $lapMonths;
            
            // Parse date - handle both "YYYY-MM" and "Mon YYYY" formats
            $startYear = 0;
            $startMonth = 0;
            
            if (strpos($monthStartedDepr, '-') !== false) {
                // Format: "YYYY-MM" or "YYYY-M"
                $parts = explode('-', $monthStartedDepr);
                if (count($parts) >= 2) {
                    $startYear = (int)$parts[0];
                    $startMonth = (int)$parts[1];
                }
            } else {
                // Format: "Mon YYYY" or "Month YYYY"
                $parts = explode(' ', trim($monthStartedDepr));
                if (count($parts) >= 2) {
                    $monthName = $parts[0];
                    $startYear = (int)$parts[1];
                    
                    // Convert month name to number
                    $monthMap = [
                        'Jan' => 1, 'January' => 1,
                        'Feb' => 2, 'February' => 2,
                        'Mar' => 3, 'March' => 3,
                        'Apr' => 4, 'April' => 4,
                        'May' => 5,
                        'Jun' => 6, 'June' => 6,
                        'Jul' => 7, 'July' => 7,
                        'Aug' => 8, 'August' => 8,
                        'Sep' => 9, 'September' => 9,
                        'Oct' => 10, 'October' => 10,
                        'Nov' => 11, 'November' => 11,
                        'Dec' => 12, 'December' => 12
                    ];
                    
                    $startMonth = isset($monthMap[$monthName]) ? $monthMap[$monthName] : 0;
                }
            }
            
            // Validate parsed values
            if ($startYear == 0 || $startMonth == 0 || $startMonth > 12) {
                return $lapMonths;
            }
            
            // Only populate months if depreciation started this year or earlier
            if ($startYear <= $currentYear) {
                // Determine first and last month to populate
                $firstMonth = ($startYear == $currentYear) ? $startMonth : 1;
                $lastMonth = ($startYear == $currentYear) ? $currentMonth : 12;
                
                // Fill months from start month to current month (or December if previous year)
                for ($m = $firstMonth; $m <= $lastMonth; $m++) {
                    $lapMonths[$m - 1] = floatval($monthlyDepr);
                }
            }
            
            return $lapMonths;
        };

        // ── Furniture & Fixtures ─────────────────────────────────────────
        $rows = $this->SelectQuery("SELECT FFId, TotalCost, MonthlyDepr,
            AccumDeprPrevYear, DisposalReclass, MonthStartedDepr
            FROM tbl_ppe_furniture WHERE Status='Active'");
        foreach ($rows as $r) {
            $monthlyDepr = floatval($r['MonthlyDepr']);
            $prevAccum = floatval($r['AccumDeprPrevYear']);
            $reclass   = floatval($r['DisposalReclass']);
            $totalCost = floatval($r['TotalCost']);
            
            // Calculate lapsing schedule for current year (up to current month)
            $lapMonths = $calculateLapSchedule($monthlyDepr, $r['MonthStartedDepr'], $currentYear, $currentMonth);
            $lapTotal = array_sum($lapMonths);

            $accumAsOf  = $prevAccum + $lapTotal - $reclass;
            $netBookVal = $totalCost - $accumAsOf;

            $this->conn->query("UPDATE tbl_ppe_furniture SET
                LapJan={$lapMonths[0]}, LapFeb={$lapMonths[1]}, LapMar={$lapMonths[2]},
                LapApr={$lapMonths[3]}, LapMay={$lapMonths[4]}, LapJun={$lapMonths[5]},
                LapJul={$lapMonths[6]}, LapAug={$lapMonths[7]}, LapSep={$lapMonths[8]},
                LapOct={$lapMonths[9]}, LapNov={$lapMonths[10]}, LapDec={$lapMonths[11]},
                LapTotal={$lapTotal},
                DeprThisYear={$lapTotal},
                AccumDeprAsOfDate={$accumAsOf},
                AccumAsOfDate={$accumAsOf},
                NetBookValue={$netBookVal}
                WHERE FFId={$r['FFId']}");
            $updated++;
        }

        // ── Transportation Equipment ─────────────────────────────────────
        $rows = $this->SelectQuery("SELECT TransId, TotalCost, MonthlyDepr,
            AccumDeprPrevYear, MonthStartedDepr
            FROM tbl_ppe_transpo WHERE Status='Active'");
        foreach ($rows as $r) {
            $monthlyDepr = floatval($r['MonthlyDepr']);
            $prevAccum = floatval($r['AccumDeprPrevYear']);
            $totalCost = floatval($r['TotalCost']);
            
            // Calculate lapsing schedule for current year (up to current month)
            $lapMonths = $calculateLapSchedule($monthlyDepr, $r['MonthStartedDepr'], $currentYear, $currentMonth);
            $lapTotal = array_sum($lapMonths);

            $accumAsOf  = $prevAccum + $lapTotal;
            $netBookVal = $totalCost - $accumAsOf;

            $this->conn->query("UPDATE tbl_ppe_transpo SET
                LapJan={$lapMonths[0]}, LapFeb={$lapMonths[1]}, LapMar={$lapMonths[2]},
                LapApr={$lapMonths[3]}, LapMay={$lapMonths[4]}, LapJun={$lapMonths[5]},
                LapJul={$lapMonths[6]}, LapAug={$lapMonths[7]}, LapSep={$lapMonths[8]},
                LapOct={$lapMonths[9]}, LapNov={$lapMonths[10]}, LapDec={$lapMonths[11]},
                LapTotal={$lapTotal},
                DeprThisYear={$lapTotal},
                AccumDeprAsOfDate={$accumAsOf},
                AccumAsOfDate={$accumAsOf},
                NetBookValue={$netBookVal}
                WHERE TransId={$r['TransId']}");
            $updated++;
        }

        // ── Leasehold Improvements ───────────────────────────────────────
        $rows = $this->SelectQuery("SELECT LeaseId, TotalCost, MonthlyDepr,
            AccumDeprPrevYear, MonthStartedDepr
            FROM tbl_ppe_leasehold WHERE Status='Active'");
        foreach ($rows as $r) {
            $monthlyDepr = floatval($r['MonthlyDepr']);
            $prevAccum = floatval($r['AccumDeprPrevYear']);
            $totalCost = floatval($r['TotalCost']);
            
            // Calculate lapsing schedule for current year (up to current month)
            $lapMonths = $calculateLapSchedule($monthlyDepr, $r['MonthStartedDepr'], $currentYear, $currentMonth);
            $lapTotal = array_sum($lapMonths);

            $accumAsOf  = $prevAccum + $lapTotal;
            $netBookVal = $totalCost - $accumAsOf;

            $this->conn->query("UPDATE tbl_ppe_leasehold SET
                LapJan={$lapMonths[0]}, LapFeb={$lapMonths[1]}, LapMar={$lapMonths[2]},
                LapApr={$lapMonths[3]}, LapMay={$lapMonths[4]}, LapJun={$lapMonths[5]},
                LapJul={$lapMonths[6]}, LapAug={$lapMonths[7]}, LapSep={$lapMonths[8]},
                LapOct={$lapMonths[9]}, LapNov={$lapMonths[10]}, LapDec={$lapMonths[11]},
                LapTotal={$lapTotal},
                DeprThisYear={$lapTotal},
                AccumDeprAsOfDate={$accumAsOf},
                AccumAsOfDate={$accumAsOf},
                NetBookValue={$netBookVal}
                WHERE LeaseId={$r['LeaseId']}");
            $updated++;
        }

        echo json_encode(["STATUS" => "SUCCESS", "UPDATED" => $updated]);
    }

    // ── Generate JV ──────────────────────────────────────────────────────
    public function GetNextTransactionID($data) {
        $type = $data['PPEType'] ?? '';
        $map  = [
            'furniture' => ['prefix' => 'FF', 'table' => 'tbl_ppe_furniture', 'col' => 'PropertyNo'],
            'transpo'   => ['prefix' => 'TR', 'table' => 'tbl_ppe_transpo',   'col' => 'PropertyNo'],
            'leasehold' => ['prefix' => 'LH', 'table' => 'tbl_ppe_leasehold', 'col' => 'PropertyNo'],
        ];
        if (!isset($map[$type])) { echo json_encode(['STATUS'=>'ERROR']); return; }
        $cfg    = $map[$type];
        $rows   = $this->SelectQuery("SELECT {$cfg['col']} FROM {$cfg['table']} WHERE {$cfg['col']} LIKE '{$cfg['prefix']}-%' ORDER BY {$cfg['col']} DESC LIMIT 1");
        $next   = 1;
        if (!empty($rows)) {
            $num  = (int) substr($rows[0][$cfg['col']], strlen($cfg['prefix']) + 1);
            $next = $num + 1;
        }
        echo json_encode(['STATUS' => 'SUCCESS', 'TransactionID' => $cfg['prefix'] . '-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    public function SavePPE($data) {
        $type = $data['PPEType'] ?? '';
        $id   = $data['ID'] ?? '';
        $by   = $_SESSION['USERNAME'] ?? 'system';
        $tid  = $this->conn->real_escape_string($data['TransactionID'] ?? '');
        $desc = $this->conn->real_escape_string($data['Description'] ?? '');
        $da   = !empty($data['DateAcquired'])    ? "'{$data['DateAcquired']}'"    : 'NULL';
        $dt   = !empty($data['DateTransferred']) ? "'{$data['DateTransferred']}'" : 'NULL';
        $ref  = $this->conn->real_escape_string($data['RefNo'] ?? '');
        $msd  = $this->conn->real_escape_string($data['MonthStartedDepr'] ?? '');

        if ($type === 'furniture') {
            $lf = ['TotalCostTransferred','Additions','DisposalTransferOut','DisposalReclass','NoOfUnits','TotalCost','EstUsefulLife','NoOfMonths','MonthlyDepr','AccumDeprPrevYear','DeprThisYear','AccumDeprAsOfDate','NetBookValue','DeprThisYearSummary','AccumAsOfDate','LapJan','LapFeb','LapMar','LapApr','LapMay','LapJun','LapJul','LapAug','LapSep','LapOct','LapNov','LapDec','LapTotal'];
            foreach ($lf as $k) $data[$k] = floatval($data[$k] ?? 0);
            $lapSet = "LapJan={$data['LapJan']},LapFeb={$data['LapFeb']},LapMar={$data['LapMar']},LapApr={$data['LapApr']},LapMay={$data['LapMay']},LapJun={$data['LapJun']},LapJul={$data['LapJul']},LapAug={$data['LapAug']},LapSep={$data['LapSep']},LapOct={$data['LapOct']},LapNov={$data['LapNov']},LapDec={$data['LapDec']},LapTotal={$data['LapTotal']}";
            $lapVals = "{$data['LapJan']},{$data['LapFeb']},{$data['LapMar']},{$data['LapApr']},{$data['LapMay']},{$data['LapJun']},{$data['LapJul']},{$data['LapAug']},{$data['LapSep']},{$data['LapOct']},{$data['LapNov']},{$data['LapDec']},{$data['LapTotal']}";
            if ($id) {
                $sql = "UPDATE tbl_ppe_furniture SET PropertyNo='$tid',Description='$desc',DateAcquired=$da,DateTransferred=$dt,RefNo='$ref',NoOfUnits={$data['NoOfUnits']},TotalCost={$data['TotalCost']},TotalCostTransferred={$data['TotalCostTransferred']},Additions={$data['Additions']},DisposalTransferOut={$data['DisposalTransferOut']},DisposalReclass={$data['DisposalReclass']},EstUsefulLife={$data['EstUsefulLife']},NoOfMonths={$data['NoOfMonths']},MonthStartedDepr='$msd',MonthlyDepr={$data['MonthlyDepr']},AccumDeprPrevYear={$data['AccumDeprPrevYear']},DeprThisYear={$data['DeprThisYear']},AccumDeprAsOfDate={$data['AccumDeprAsOfDate']},NetBookValue={$data['NetBookValue']},DeprThisYearSummary={$data['DeprThisYearSummary']},AccumAsOfDate={$data['AccumAsOfDate']},$lapSet,UpdatedBy='$by' WHERE FFId=$id";
            } else {
                $sql = "INSERT INTO tbl_ppe_furniture (PropertyNo,Description,DateAcquired,DateTransferred,RefNo,NoOfUnits,TotalCost,TotalCostTransferred,Additions,DisposalTransferOut,DisposalReclass,EstUsefulLife,NoOfMonths,MonthStartedDepr,MonthlyDepr,AccumDeprPrevYear,DeprThisYear,AccumDeprAsOfDate,NetBookValue,DeprThisYearSummary,AccumAsOfDate,LapJan,LapFeb,LapMar,LapApr,LapMay,LapJun,LapJul,LapAug,LapSep,LapOct,LapNov,LapDec,LapTotal,Status,CreatedBy,UpdatedBy) VALUES ('$tid','$desc',$da,$dt,'$ref',{$data['NoOfUnits']},{$data['TotalCost']},{$data['TotalCostTransferred']},{$data['Additions']},{$data['DisposalTransferOut']},{$data['DisposalReclass']},{$data['EstUsefulLife']},{$data['NoOfMonths']},'$msd',{$data['MonthlyDepr']},{$data['AccumDeprPrevYear']},{$data['DeprThisYear']},{$data['AccumDeprAsOfDate']},{$data['NetBookValue']},{$data['DeprThisYearSummary']},{$data['AccumAsOfDate']},$lapVals,'Active','$by','$by')";
            }
        } elseif ($type === 'transpo') {
            $tf = ['AcquisitionCost','NoOfUnits','TotalCost','EstUsefulLife','NoOfMonths','MonthlyDepr','AccumDeprPrevYear','DeprThisYear','AccumDeprAsOfDate','NetBookValue','DeprThisYearSummary','AccumAsOfDate','LapJan','LapFeb','LapMar','LapApr','LapMay','LapJun','LapJul','LapAug','LapSep','LapOct','LapNov','LapDec','LapTotal'];
            foreach ($tf as $k) $data[$k] = floatval($data[$k] ?? 0);
            $lapSet = "LapJan={$data['LapJan']},LapFeb={$data['LapFeb']},LapMar={$data['LapMar']},LapApr={$data['LapApr']},LapMay={$data['LapMay']},LapJun={$data['LapJun']},LapJul={$data['LapJul']},LapAug={$data['LapAug']},LapSep={$data['LapSep']},LapOct={$data['LapOct']},LapNov={$data['LapNov']},LapDec={$data['LapDec']},LapTotal={$data['LapTotal']}";
            $lapVals = "{$data['LapJan']},{$data['LapFeb']},{$data['LapMar']},{$data['LapApr']},{$data['LapMay']},{$data['LapJun']},{$data['LapJul']},{$data['LapAug']},{$data['LapSep']},{$data['LapOct']},{$data['LapNov']},{$data['LapDec']},{$data['LapTotal']}";
            if ($id) {
                $sql = "UPDATE tbl_ppe_transpo SET PropertyNo='$tid',Description='$desc',DateAcquired=$da,RefNo='$ref',AcquisitionCost={$data['AcquisitionCost']},NoOfUnits={$data['NoOfUnits']},TotalCost={$data['TotalCost']},EstUsefulLife={$data['EstUsefulLife']},NoOfMonths={$data['NoOfMonths']},MonthStartedDepr='$msd',MonthlyDepr={$data['MonthlyDepr']},AccumDeprPrevYear={$data['AccumDeprPrevYear']},DeprThisYear={$data['DeprThisYear']},AccumDeprAsOfDate={$data['AccumDeprAsOfDate']},NetBookValue={$data['NetBookValue']},DeprThisYearSummary={$data['DeprThisYearSummary']},AccumAsOfDate={$data['AccumAsOfDate']},$lapSet,UpdatedBy='$by' WHERE TransId=$id";
            } else {
                $sql = "INSERT INTO tbl_ppe_transpo (PropertyNo,Description,DateAcquired,RefNo,AcquisitionCost,NoOfUnits,TotalCost,EstUsefulLife,NoOfMonths,MonthStartedDepr,MonthlyDepr,AccumDeprPrevYear,DeprThisYear,AccumDeprAsOfDate,NetBookValue,DeprThisYearSummary,AccumAsOfDate,LapJan,LapFeb,LapMar,LapApr,LapMay,LapJun,LapJul,LapAug,LapSep,LapOct,LapNov,LapDec,LapTotal,Status,CreatedBy,UpdatedBy) VALUES ('$tid','$desc',$da,'$ref',{$data['AcquisitionCost']},{$data['NoOfUnits']},{$data['TotalCost']},{$data['EstUsefulLife']},{$data['NoOfMonths']},'$msd',{$data['MonthlyDepr']},{$data['AccumDeprPrevYear']},{$data['DeprThisYear']},{$data['AccumDeprAsOfDate']},{$data['NetBookValue']},{$data['DeprThisYearSummary']},{$data['AccumAsOfDate']},$lapVals,'Active','$by','$by')";
            }
        } elseif ($type === 'leasehold') {
            $llf = ['AcquisitionCost','NoOfUnits','TotalCost','EstUsefulLife','NoOfMonths','MonthlyDepr','AccumDeprPrevYear','DeprThisYear','AccumDeprAsOfDate','NetBookValue','DeprThisYearSummary','AccumAsOfDate','LapJan','LapFeb','LapMar','LapApr','LapMay','LapJun','LapJul','LapAug','LapSep','LapOct','LapNov','LapDec','LapTotal'];
            foreach ($llf as $k) $data[$k] = floatval($data[$k] ?? 0);
            $lapSet = "LapJan={$data['LapJan']},LapFeb={$data['LapFeb']},LapMar={$data['LapMar']},LapApr={$data['LapApr']},LapMay={$data['LapMay']},LapJun={$data['LapJun']},LapJul={$data['LapJul']},LapAug={$data['LapAug']},LapSep={$data['LapSep']},LapOct={$data['LapOct']},LapNov={$data['LapNov']},LapDec={$data['LapDec']},LapTotal={$data['LapTotal']}";
            $lapVals = "{$data['LapJan']},{$data['LapFeb']},{$data['LapMar']},{$data['LapApr']},{$data['LapMay']},{$data['LapJun']},{$data['LapJul']},{$data['LapAug']},{$data['LapSep']},{$data['LapOct']},{$data['LapNov']},{$data['LapDec']},{$data['LapTotal']}";
            if ($id) {
                $sql = "UPDATE tbl_ppe_leasehold SET PropertyNo='$tid',Description='$desc',DateAcquired=$da,RefNo='$ref',AcquisitionCost={$data['AcquisitionCost']},NoOfUnits={$data['NoOfUnits']},TotalCost={$data['TotalCost']},EstUsefulLife={$data['EstUsefulLife']},NoOfMonths={$data['NoOfMonths']},MonthStartedDepr='$msd',MonthlyDepr={$data['MonthlyDepr']},AccumDeprPrevYear={$data['AccumDeprPrevYear']},DeprThisYear={$data['DeprThisYear']},AccumDeprAsOfDate={$data['AccumDeprAsOfDate']},NetBookValue={$data['NetBookValue']},DeprThisYearSummary={$data['DeprThisYearSummary']},AccumAsOfDate={$data['AccumAsOfDate']},$lapSet,UpdatedBy='$by' WHERE LeaseId=$id";
            } else {
                $sql = "INSERT INTO tbl_ppe_leasehold (PropertyNo,Description,DateAcquired,RefNo,AcquisitionCost,NoOfUnits,TotalCost,EstUsefulLife,NoOfMonths,MonthStartedDepr,MonthlyDepr,AccumDeprPrevYear,DeprThisYear,AccumDeprAsOfDate,NetBookValue,DeprThisYearSummary,AccumAsOfDate,LapJan,LapFeb,LapMar,LapApr,LapMay,LapJun,LapJul,LapAug,LapSep,LapOct,LapNov,LapDec,LapTotal,Status,CreatedBy,UpdatedBy) VALUES ('$tid','$desc',$da,'$ref',{$data['AcquisitionCost']},{$data['NoOfUnits']},{$data['TotalCost']},{$data['EstUsefulLife']},{$data['NoOfMonths']},'$msd',{$data['MonthlyDepr']},{$data['AccumDeprPrevYear']},{$data['DeprThisYear']},{$data['AccumDeprAsOfDate']},{$data['NetBookValue']},{$data['DeprThisYearSummary']},{$data['AccumAsOfDate']},$lapVals,'Active','$by','$by')";
            }
        } else {
            echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Invalid PPE type']); return;
        }

        if ($this->conn->query($sql)) {
            echo json_encode(['STATUS' => 'SUCCESS']);
        } else {
            echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => $this->conn->error]);
        }
    }

    public function GenerateJV($data) {
        $fund      = $data['Fund'];
        $jvDate    = date("Y-m-d", strtotime($data['JVDate']));
        $jvNo      = $data['JVNo'];
        $ids       = json_decode($data['EquipmentIDs'], true);
        $user      = $_SESSION['USERNAME'] ?? '-';

        if (empty($ids)) {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "No equipment selected."]);
            return;
        }

        $branchInfo = $this->SelectQuery("SELECT Value FROM tbl_configuration WHERE ConfigName='BRANCHNAME' AND ConfigOwner='BRANCH SETUP' LIMIT 1");
        $branch     = $branchInfo[0]['Value'] ?? '-';

        $bookPage   = $this->GetBookPage("GJ", $fund, $jvDate);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $this->conn->prepare("SELECT * FROM tbl_equipment WHERE ID IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $equipment = [];
        while ($row = $result->fetch_assoc()) { $equipment[] = $row; }

        $totalDep = array_sum(array_column($equipment, 'MonthlyDep'));
        $explanation = "Monthly Depreciation - " . date("F Y", strtotime($jvDate));

        // DR: Depreciation Expense (e.g. GL 61100), CR: Accumulated Depreciation (e.g. GL 16100)
        $values = "";
        foreach ($equipment as $eq) {
            $dep = $eq['MonthlyDep'];
            $assetName = $eq['AssetName'];
            // DR entry
            $values .= ($values ? "," : "") . "('$jvDate','$branch','$fund','$jvNo','$explanation','Depreciation Expense - $assetName','61100','$dep','" . ($dep * -1) . "','NO','-','$assetName','$dep','0.00','$user','-','-','$jvDate','-','-','-','0','DR','61100','DEPRECIATION','$bookPage')";
            // CR entry
            $values .= ",('$jvDate','$branch','$fund','$jvNo','$explanation','Accumulated Depreciation - $assetName','16100','" . ($dep * -1) . "','$dep','NO','-','$assetName','0.00','$dep','$user','-','-','$jvDate','-','-','-','0','CR','16100','DEPRECIATION','$bookPage')";
        }

        $sql = "INSERT INTO tbl_books (CDate,Branch,Fund,JVNo,Explanation,AcctTitle,AcctNo,SLDrCr,SLDrCr1,SLYesNo,SLNo,SLName,DrOther,CrOther,PreparedBy,ClientNo,LoanID,DatePrepared,Program,Product,Tag,postingstat,CrDr,GLNo,Nature,BookPage) VALUES $values";

        $res = $this->conn->query($sql);
        $affected = $this->conn->affected_rows;

        echo json_encode([
            "STATUS"  => $affected > 0 ? "SUCCESS" : "ERROR",
            "JVNO"    => $jvNo,
            "TOTAL"   => number_format($totalDep, 2),
            "MESSAGE" => $affected > 0 ? "JV generated successfully." : $this->conn->error,
        ]);
    }
    // ── Helpers ──────────────────────────────────────────────────────────
    private function GetBookPage($type, $fund, $date) {
        $stmt = $this->conn->prepare("SELECT BOOKPAGE FROM TBL_BOOKS WHERE BOOKTYPE='GJ' AND FUND=? AND BOOKPAGE<>'-' ORDER BY CAST(REPLACE(BOOKPAGE,'GJ-','') AS UNSIGNED) DESC LIMIT 1");
        $stmt->bind_param("s", $fund);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row  = $result->fetch_assoc();
            $last = trim(str_replace("GJ-", "", $row['BOOKPAGE']));
            return $type . "-" . (floatval($last) + 1);
        }
        return $type . "-1";
    }

    public function SelectQuery($sql) {
        $data = [];
        $res  = $this->conn->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) { $data[] = $row; }
        }
        return $data;
    }
}
