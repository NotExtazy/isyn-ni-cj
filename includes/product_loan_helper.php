<?php
/**
 * Product Loan Helper
 * 
 * Helper functions to check if products can be loaned
 * Uses configuration file instead of database columns
 */

// Load the configuration
$configPath = dirname(__DIR__) . '/config/loanable_products.php';
if (file_exists($configPath)) {
    require_once($configPath);
} else {
    // Fallback: Allow all products if config doesn't exist
    function isProductLoanable($productName, $productCode = '', $category = '') {
        return true; // Default: allow all
    }
}

/**
 * Check if a product from database can be loaned
 * 
 * @param array $product Product data from database
 * @return bool True if loanable
 */
function canProductBeLoan($product) {
    $productName = $product['Product'] ?? $product['ProductName'] ?? '';
    $productCode = $product['ProductCode'] ?? '';
    $category = $product['Category'] ?? '';
    
    return isProductLoanable($productName, $productCode, $category);
}

/**
 * Filter products to show only loanable ones
 * 
 * @param array $products Array of products from database
 * @return array Filtered array of loanable products
 */
function filterLoanableProducts($products) {
    return array_filter($products, function($product) {
        return canProductBeLoan($product);
    });
}

/**
 * Get loanable products from database
 * 
 * @param mysqli $conn Database connection
 * @return array Array of loanable products
 */
function getLoanableProductsFromDB($conn) {
    $query = "SELECT * FROM tbl_loansetup ORDER BY Product ASC";
    $result = $conn->query($query);
    
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (canProductBeLoan($row)) {
                $products[] = $row;
            }
        }
    }
    
    return $products;
}

/**
 * Validate if a loan transaction can proceed with this product
 * 
 * @param string $productName Product name
 * @return array ['valid' => bool, 'message' => string]
 */
function validateProductForLoan($productName) {
    if (isProductLoanable($productName)) {
        return [
            'valid' => true,
            'message' => 'Product is eligible for loan'
        ];
    } else {
        return [
            'valid' => false,
            'message' => 'This product is not eligible for loan. Please contact administrator.'
        ];
    }
}

/**
 * Get product eligibility message for UI
 * 
 * @param string $productName Product name
 * @return string HTML badge or message
 */
function getProductEligibilityBadge($productName) {
    if (isProductLoanable($productName)) {
        return '<span class="badge bg-success">✓ Loanable</span>';
    } else {
        return '<span class="badge bg-secondary">Not Loanable</span>';
    }
}

/**
 * Check if product is loanable and return JSON response
 * 
 * @param string $productName Product name
 * @return string JSON response
 */
function checkProductLoanEligibilityJSON($productName) {
    $validation = validateProductForLoan($productName);
    return json_encode([
        'eligible' => $validation['valid'],
        'message' => $validation['message'],
        'product' => $productName
    ]);
}
?>
