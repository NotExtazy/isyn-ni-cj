<?php
/**
 * Loan Status Detection Helper Functions
 * 
 * This file contains helper functions for detecting and managing loan statuses
 * throughout the loan management system.
 */

class LoanStatusHelper {
    
    /**
     * Calculate loan status based on balance and loan details
     * 
     * @param float $balance Current loan balance
     * @param float $loanAmount Original loan amount
     * @param float $interest Interest amount
     * @param float $cbu CBU fee amount
     * @param float $ef EF fee amount
     * @param float $mba MBA fee amount
     * @param string $dateRelease Loan release date (Y-m-d format)
     * @param string $dateMature Loan maturity date (Y-m-d format)
     * @return string Loan status (PAID, PARTIAL, ACTIVE, OVERDUE, WRITEOFF, UNKNOWN)
     */
    public static function calculateLoanStatus($balance, $loanAmount, $interest = 0, $cbu = 0, $ef = 0, $mba = 0, $dateRelease = null, $dateMature = null) {
        // Convert to float to ensure proper calculation
        $balance = floatval($balance);
        $loanAmount = floatval($loanAmount);
        $interest = floatval($interest);
        $cbu = floatval($cbu);
        $ef = floatval($ef);
        $mba = floatval($mba);
        
        // Calculate total amount due (principal + interest + fees)
        $totalAmountDue = $loanAmount + $interest + $cbu + $ef + $mba;
        
        // Determine status based on balance
        if ($balance == 0) {
            return 'PAID';
        } elseif ($balance > 0 && $balance < $totalAmountDue) {
            // Check if overdue for partial payments
            if (self::isOverdue($dateMature)) {
                return 'OVERDUE';
            }
            return 'PARTIAL';
        } elseif ($balance >= $totalAmountDue) {
            // Check if overdue for active loans
            if (self::isOverdue($dateMature)) {
                return 'OVERDUE';
            }
            return 'ACTIVE';
        } else {
            return 'UNKNOWN';
        }
    }
    
    /**
     * Check if a loan is overdue based on maturity date
     * 
     * @param string $dateMature Loan maturity date (Y-m-d format)
     * @param int $gracePeriodDays Grace period in days (default: 30)
     * @return bool True if loan is overdue
     */
    public static function isOverdue($dateMature, $gracePeriodDays = 30) {
        if (empty($dateMature)) {
            return false;
        }
        
        try {
            $maturityDate = new DateTime($dateMature);
            $today = new DateTime();
            
            // Add grace period to maturity date
            $maturityDate->add(new DateInterval('P' . $gracePeriodDays . 'D'));
            
            return $today > $maturityDate;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get status badge HTML for display
     * 
     * @param string $status Loan status
     * @return string HTML badge
     */
    public static function getStatusBadge($status) {
        $badges = [
            'PAID' => '<span class="badge bg-success">PAID</span>',
            'PARTIAL' => '<span class="badge bg-warning">PARTIAL</span>',
            'ACTIVE' => '<span class="badge bg-primary">ACTIVE</span>',
            'OVERDUE' => '<span class="badge bg-danger">OVERDUE</span>',
            'WRITEOFF' => '<span class="badge bg-dark">WRITEOFF</span>',
            'UNKNOWN' => '<span class="badge bg-secondary">UNKNOWN</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">UNKNOWN</span>';
    }
    
    /**
     * Get status color class for styling
     * 
     * @param string $status Loan status
     * @return string CSS class
     */
    public static function getStatusColorClass($status) {
        $colors = [
            'PAID' => 'text-success',
            'PARTIAL' => 'text-warning',
            'ACTIVE' => 'text-primary',
            'OVERDUE' => 'text-danger',
            'WRITEOFF' => 'text-dark',
            'UNKNOWN' => 'text-secondary'
        ];
        
        return $colors[$status] ?? 'text-secondary';
    }
    
    /**
     * Update loan status in database
     * 
     * @param mysqli $conn Database connection
     * @param string $loanId Loan ID
     * @param string $clientNo Client number
     * @return string Updated status
     */
    public static function updateLoanStatusInDB($conn, $loanId, $clientNo) {
        try {
            // Get loan details
            $query = "SELECT Balance, LoanAmount, IFNULL(Interest, 0) as Interest, 
                            IFNULL(CBU, 0) as CBU, IFNULL(EF, 0) as EF, IFNULL(MBA, 0) as MBA,
                            DateRelease, DateMature
                     FROM tbl_loans 
                     WHERE LoanID = ? LIMIT 1";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return 'UNKNOWN';
            }
            
            $loan = $result->fetch_assoc();
            
            // Calculate new status
            $newStatus = self::calculateLoanStatus(
                $loan['Balance'],
                $loan['LoanAmount'],
                $loan['Interest'],
                $loan['CBU'],
                $loan['EF'],
                $loan['MBA'],
                $loan['DateRelease'],
                $loan['DateMature']
            );
            
            // Update tbl_loans if LoanStatus column exists
            $checkColumn = "SELECT COUNT(*) as exists_col FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_NAME = 'tbl_loans' AND COLUMN_NAME = 'LoanStatus' 
                           AND TABLE_SCHEMA = DATABASE()";
            $checkResult = $conn->query($checkColumn);
            $hasColumn = $checkResult->fetch_assoc()['exists_col'] > 0;
            
            if ($hasColumn) {
                $updateLoans = "UPDATE tbl_loans SET LoanStatus = ? WHERE LoanID = ?";
                $stmt = $conn->prepare($updateLoans);
                $stmt->bind_param("ss", $newStatus, $loanId);
                $stmt->execute();
            }
            
            // Update tbl_aging if LOANSTATUS column exists
            $checkAgingColumn = "SELECT COUNT(*) as exists_col FROM INFORMATION_SCHEMA.COLUMNS 
                                WHERE TABLE_NAME = 'tbl_aging' AND COLUMN_NAME = 'LOANSTATUS' 
                                AND TABLE_SCHEMA = DATABASE()";
            $checkAgingResult = $conn->query($checkAgingColumn);
            $hasAgingColumn = $checkAgingResult->fetch_assoc()['exists_col'] > 0;
            
            if ($hasAgingColumn) {
                $updateAging = "UPDATE tbl_aging SET LOANSTATUS = ? WHERE ClientNo = ?";
                $stmt = $conn->prepare($updateAging);
                $stmt->bind_param("ss", $newStatus, $clientNo);
                $stmt->execute();
            }
            
            return $newStatus;
            
        } catch (Exception $e) {
            error_log("Error updating loan status: " . $e->getMessage());
            return 'UNKNOWN';
        }
    }
    
    /**
     * Get payment progress percentage
     * 
     * @param float $balance Current balance
     * @param float $totalAmountDue Total amount due
     * @return float Progress percentage (0-100)
     */
    public static function getPaymentProgress($balance, $totalAmountDue) {
        if ($totalAmountDue <= 0) {
            return 0;
        }
        
        $amountPaid = $totalAmountDue - $balance;
        $progress = ($amountPaid / $totalAmountDue) * 100;
        
        return max(0, min(100, round($progress, 2)));
    }
    
    /**
     * Check if loan is eligible for renewal
     * 
     * @param string $status Loan status
     * @param float $balance Current balance
     * @return bool True if eligible for renewal
     */
    public static function isEligibleForRenewal($status, $balance) {
        return ($status === 'PAID' && $balance == 0);
    }
    
    /**
     * Get all possible loan statuses with descriptions
     * 
     * @return array Status definitions
     */
    public static function getStatusDefinitions() {
        return [
            'PAID' => [
                'label' => 'Paid',
                'description' => 'Loan is fully paid (balance = 0)',
                'color' => 'success',
                'renewable' => true
            ],
            'PARTIAL' => [
                'label' => 'Partial',
                'description' => 'Loan is partially paid (0 < balance < total)',
                'color' => 'warning',
                'renewable' => false
            ],
            'ACTIVE' => [
                'label' => 'Active',
                'description' => 'Loan is active with full balance remaining',
                'color' => 'primary',
                'renewable' => false
            ],
            'OVERDUE' => [
                'label' => 'Overdue',
                'description' => 'Loan payment is past due date',
                'color' => 'danger',
                'renewable' => false
            ],
            'WRITEOFF' => [
                'label' => 'Write-off',
                'description' => 'Loan marked as bad debt',
                'color' => 'dark',
                'renewable' => false
            ],
            'UNKNOWN' => [
                'label' => 'Unknown',
                'description' => 'Status cannot be determined',
                'color' => 'secondary',
                'renewable' => false
            ]
        ];
    }
}

/**
 * Convenience function for quick status calculation
 */
function calculateLoanStatus($balance, $loanAmount, $interest = 0, $cbu = 0, $ef = 0, $mba = 0, $dateRelease = null, $dateMature = null) {
    return LoanStatusHelper::calculateLoanStatus($balance, $loanAmount, $interest, $cbu, $ef, $mba, $dateRelease, $dateMature);
}

/**
 * Convenience function for status badge
 */
function getLoanStatusBadge($status) {
    return LoanStatusHelper::getStatusBadge($status);
}
?>