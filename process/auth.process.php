<?php
$basePath = dirname(__DIR__);
include_once($basePath . "/database/connection.php");

class Process extends Database
{
    public function Login($data){
        // Ensure session is started
        if (session_status() == PHP_SESSION_NONE) { session_start(); }

        $usrnm = $data["usernameField"];
        $pwrd = filter_var($data["passwordField"], FILTER_SANITIZE_STRING);

        $stmt = $this->conn->prepare("SELECT * FROM tbl_users WHERE Username = ?");
        $stmt->bind_param('s', $usrnm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $status = "ERROR";
        $message = "User account does not exist!";

        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if($row['Username'] == $usrnm) {
                    // NOTE: Strongly recommend moving to password_verify() later
                    $isPasswordCorrect = false;

                    // 1. Check if password matches hash
                    if (password_verify($pwrd, $row['Password'])) {
                        $isPasswordCorrect = true;
                    } 
                    // 2. Fallback: Check plain text (Legacy) - Skip migration to avoid column size issue
                    elseif ($pwrd == $row['Password']) {
                        $isPasswordCorrect = true;
                        // TODO: Increase Password column size to VARCHAR(255) before enabling migration
                    }

                    if ($isPasswordCorrect) {
                        if ($row['Status'] == "ENABLED") {
                            
                            // --- SESSION VARIABLES ---
                            $_SESSION['AUTHENTICATED'] = true;
                            $_SESSION['EMPNO'] = $row['EmpNo'];
                            $_SESSION['USERNAME'] = $row['Username'];
                            $_SESSION['FULLNAME'] = $row['Owner'];
                            
                            // *** CRITICAL FIX: Save the ID ***
                            $_SESSION['ID'] = $row['ID']; 
                            
                            // *** RBAC Integration ***
                            $_SESSION['role_id'] = 1; // Default role ID for permissions
                            $_SESSION['role_name'] = $row['Role'] ?? 'USER'; // Store actual role name
                            // ************************

                            // --- CSRF PROTECTION ---
                            if (empty($_SESSION['csrf_token'])) {
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                            }
                            // -----------------------

                            // DEBUG: Log session creation
                            error_log("Auth.process.php - Session created successfully");
                            error_log("Auth.process.php - Session ID: " . session_id());
                            error_log("Auth.process.php - Session data: " . json_encode($_SESSION));

                            // OPTIONAL: Log the Login Event immediately
                            // Module ID 0 usually represents 'System' or you can find your Auth module ID
                            $this->LogActivity($row['ID'], "LOGIN", 0, "User logged in successfully.");

                            $status = "SUCCESS";
                            $message = "Sign In Successful";
                        } else {
                            $message = "User account is INACTIVE, Contact Administrator.";
                        }        
                    } else {
                        $message = "Invalid password";
                    }
                } else {
                    $message = "Invalid username.";
                }
            }
        } 

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message
        ));
    }

    public function Logout($return) {
        if (session_destroy()) {
            $status = "LOGOUT_SUCCESS";
        } else {
            $status = "LOGOUT_UNSUCCESSFUL";
        }

        if ($return){
            echo json_encode(array(
                "STATUS" => $status,
            ));
        }
    }
}