<?php
/**
 * Encryption Utility Class
 * Provides AES-256-CBC encryption/decryption for sensitive PII data
 * 
 * Usage:
 *   Encryption::init(); // Call once at start
 *   $encrypted = Encryption::encrypt($plaintext);
 *   $decrypted = Encryption::decrypt($encrypted);
 */

class Encryption {
    private static $key;
    private static $method = 'AES-256-CBC';
    
    /**
     * Initialize encryption with key from environment
     * @throws Exception if encryption key is not configured
     */
    public static function init() {
        if (!defined('ENCRYPTION_KEY')) {
            throw new Exception("ENCRYPTION_KEY constant not defined. Check constants.php");
        }
        
        self::$key = ENCRYPTION_KEY;
        
        if (empty(self::$key)) {
            throw new Exception("Encryption key not configured in .env file");
        }
        
        // Validate key length (should be 64 hex chars = 32 bytes)
        if (strlen(self::$key) !== 64) {
            throw new Exception("Invalid encryption key length. Expected 64 hex characters.");
        }
    }
    
    /**
     * Encrypt plaintext data
     * @param string $data Data to encrypt
     * @return string Base64-encoded encrypted data with IV prepended
     */
    public static function encrypt($data) {
        // Return empty/null as-is
        if (empty($data) || $data === null || $data === '') {
            return $data;
        }
        
        try {
            // Ensure key is initialized
            if (empty(self::$key)) {
                self::init();
            }
            
            // Convert hex key to binary
            $keyBinary = hex2bin(self::$key);
            
            // Generate random IV
            $ivLength = openssl_cipher_iv_length(self::$method);
            $iv = openssl_random_pseudo_bytes($ivLength);
            
            // Encrypt data
            $encrypted = openssl_encrypt(
                $data, 
                self::$method, 
                $keyBinary, 
                OPENSSL_RAW_DATA, 
                $iv
            );
            
            if ($encrypted === false) {
                throw new Exception("Encryption failed: " . openssl_error_string());
            }
            
            // Prepend IV to encrypted data and base64 encode
            return base64_encode($iv . $encrypted);
            
        } catch (Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            return $data; // Fallback: return original data
        }
    }
    
    /**
     * Decrypt encrypted data
     * @param string $data Base64-encoded encrypted data with IV
     * @return string Decrypted plaintext
     */
    public static function decrypt($data) {
        // Return empty/null as-is
        if (empty($data) || $data === null || $data === '') {
            return $data;
        }
        
        try {
            // Ensure key is initialized
            if (empty(self::$key)) {
                self::init();
            }
            
            // Convert hex key to binary
            $keyBinary = hex2bin(self::$key);
            
            // Decode base64
            $decoded = base64_decode($data, true);
            
            if ($decoded === false) {
                // Not base64 - might be plaintext (for migration compatibility)
                return $data;
            }
            
            // Extract IV and encrypted data
            $ivLength = openssl_cipher_iv_length(self::$method);
            
            if (strlen($decoded) < $ivLength) {
                // Invalid data length
                return $data;
            }
            
            $iv = substr($decoded, 0, $ivLength);
            $encrypted = substr($decoded, $ivLength);
            
            // Decrypt
            $decrypted = openssl_decrypt(
                $encrypted, 
                self::$method, 
                $keyBinary, 
                OPENSSL_RAW_DATA, 
                $iv
            );
            
            if ($decrypted === false) {
                throw new Exception("Decryption failed: " . openssl_error_string());
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            return $data; // Fallback: return original data
        }
    }
}
?>
