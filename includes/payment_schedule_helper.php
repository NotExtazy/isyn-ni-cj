<?php
/**
 * Payment Schedule Helper Functions
 * Handles payment schedule generation and late payment checks
 */

class PaymentScheduleHelper {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Generate payment schedule for a loan
     */
    public function generateSchedule($loanID) {
        $stmt = $this->conn->prepare("CALL sp_GeneratePaymentSchedule(?)");
        $stmt->bind_param("s", $loanID);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            // Clear any remaining results
            while ($this->conn->more_results()) {
                $this->conn->next_result();
            }
            
            return [
                'success' => true,
                'message' => $row['Message'] ?? 'Schedule generated'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to generate schedule: ' . $this->conn->error
        ];
    }
    
    /**
     * Check if payment is late and calculate penalty
     */
    public function checkPaymentStatus($loanID, $paymentDate = null) {
        if ($paymentDate === null) {
            $paymentDate = date('Y-m-d');
        }
        
        // Get next pending payment
        $query = "
            SELECT 
                id,
                ScheduleNo,
                DueDate,
                TotalDue,
                Balance,
                DATEDIFF(?, DueDate) as DaysLate
            FROM tbl_payment_schedule
            WHERE LoanID = ?
            AND Status = 'PENDING'
            ORDER BY ScheduleNo ASC
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $paymentDate, $loanID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $daysLate = max(0, $row['DaysLate']);
            $isLate = $daysLate > 0;
            
            // Calculate penalty (1% per month late)
            $penalty = 0;
            if ($isLate) {
                $monthsLate = ceil($daysLate / 30);
                $penalty = $row['Balance'] * 0.01 * $monthsLate;
            }
            
            return [
                'hasSchedule' => true,
                'scheduleId' => $row['id'],
                'scheduleNo' => $row['ScheduleNo'],
                'dueDate' => $row['DueDate'],
                'amountDue' => $row['TotalDue'],
                'balance' => $row['Balance'],
                'isLate' => $isLate,
                'daysLate' => $daysLate,
                'penalty' => $penalty,
                'status' => $isLate ? 'LATE' : 'ON_TIME',
                'message' => $isLate 
                    ? "Payment is {$daysLate} days late. Penalty: ₱" . number_format($penalty, 2)
                    : "Payment is on time"
            ];
        }
        
        return [
            'hasSchedule' => false,
            'message' => 'No payment schedule found for this loan'
        ];
    }
    
    /**
     * Update schedule when payment is made
     */
    public function recordPayment($loanID, $principalPaid, $interestPaid, $paymentDate = null) {
        if ($paymentDate === null) {
            $paymentDate = date('Y-m-d');
        }
        
        $totalPaid = $principalPaid + $interestPaid;
        
        // Get next pending payment
        $query = "
            SELECT id, Balance, DueDate
            FROM tbl_payment_schedule
            WHERE LoanID = ?
            AND Status = 'PENDING'
            ORDER BY ScheduleNo ASC
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $loanID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $scheduleId = $row['id'];
            $balance = $row['Balance'] - $totalPaid;
            $daysLate = max(0, (strtotime($paymentDate) - strtotime($row['DueDate'])) / 86400);
            
            // Calculate penalty if late
            $penalty = 0;
            if ($daysLate > 0) {
                $monthsLate = ceil($daysLate / 30);
                $penalty = $row['Balance'] * 0.01 * $monthsLate;
            }
            
            // Determine status
            $status = $balance <= 0 ? 'PAID' : 'PARTIAL';
            
            // Update schedule
            $updateQuery = "
                UPDATE tbl_payment_schedule
                SET PrincipalPaid = PrincipalPaid + ?,
                    InterestPaid = InterestPaid + ?,
                    TotalPaid = TotalPaid + ?,
                    Balance = ?,
                    Status = ?,
                    PaidDate = ?,
                    DaysLate = ?,
                    PenaltyAmount = ?
                WHERE id = ?
            ";
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bind_param(
                "ddddssddi",
                $principalPaid,
                $interestPaid,
                $totalPaid,
                $balance,
                $status,
                $paymentDate,
                $daysLate,
                $penalty,
                $scheduleId
            );
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'balance' => $balance,
                    'status' => $status,
                    'daysLate' => $daysLate,
                    'penalty' => $penalty
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Failed to update payment schedule'
        ];
    }
    
    /**
     * Get payment schedule for a loan
     */
    public function getSchedule($loanID) {
        $query = "
            SELECT *
            FROM tbl_payment_schedule
            WHERE LoanID = ?
            ORDER BY ScheduleNo ASC
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $loanID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedule = [];
        while ($row = $result->fetch_assoc()) {
            $schedule[] = $row;
        }
        
        return $schedule;
    }
}
?>
