<?php
/**
 * Loanable Products Configuration
 * 
 * This file defines which products can be loaned out without requiring
 * database schema changes. Simply add or remove products from the array.
 * 
 * Usage:
 * - Add product names to the $loanableProducts array to allow them to be loaned
 * - Remove product names to disallow loaning
 * - Use wildcards (*) for pattern matching
 */

// List of products that can be loaned out
$loanableProducts = [
    // Regular Loan Products
    'REGULAR LOAN',
    'EMERGENCY LOAN',
    'BUSINESS LOAN',
    'AGRICULTURAL LOAN',
    'EDUCATIONAL LOAN',
    'HOUSING LOAN',
    'MEDICAL LOAN',
    'SALARY LOAN',
    
    // Special Products
    'MICROFINANCE',
    'SME LOAN',
    'GROUP LOAN',
    
    // Add more products here as needed
    // 'NEW PRODUCT NAME',
];

// Alternative: Use product codes instead of names
$loanableProductCodes = [
    'REG',
    'EMER',
    'BUS',
    'AGRI',
    'EDU',
    'HOUS',
    'MED',
    'SAL',
    'MICRO',
    'SME',
    'GRP',
];

// Product categories that are loanable
$loanableCategories = [
    'LOAN',
    'CREDIT',
    'FINANCING',
];

/**
 * Check if a product is loanable
 * 
 * @param string $productName The product name to check
 * @param string $productCode Optional product code
 * @param string $category Optional product category
 * @return bool True if product can be loaned, false otherwise
 */
function isProductLoanable($productName, $productCode = '', $category = '') {
    global $loanableProducts, $loanableProductCodes, $loanableCategories;
    
    // Check by product name (case-insensitive)
    if (in_array(strtoupper($productName), array_map('strtoupper', $loanableProducts))) {
        return true;
    }
    
    // Check by product code
    if (!empty($productCode) && in_array(strtoupper($productCode), array_map('strtoupper', $loanableProductCodes))) {
        return true;
    }
    
    // Check by category
    if (!empty($category) && in_array(strtoupper($category), array_map('strtoupper', $loanableCategories))) {
        return true;
    }
    
    // Check for wildcard patterns
    foreach ($loanableProducts as $pattern) {
        if (strpos($pattern, '*') !== false) {
            $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/i';
            if (preg_match($regex, $productName)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Get all loanable products
 * 
 * @return array List of loanable products
 */
function getLoanableProducts() {
    global $loanableProducts;
    return $loanableProducts;
}

/**
 * Add a product to the loanable list (runtime only, not persistent)
 * 
 * @param string $productName Product name to add
 */
function addLoanableProduct($productName) {
    global $loanableProducts;
    if (!in_array($productName, $loanableProducts)) {
        $loanableProducts[] = $productName;
    }
}

/**
 * Remove a product from the loanable list (runtime only, not persistent)
 * 
 * @param string $productName Product name to remove
 */
function removeLoanableProduct($productName) {
    global $loanableProducts;
    $key = array_search($productName, $loanableProducts);
    if ($key !== false) {
        unset($loanableProducts[$key]);
        $loanableProducts = array_values($loanableProducts); // Re-index array
    }
}

// Return the configuration for external use
return [
    'products' => $loanableProducts,
    'codes' => $loanableProductCodes,
    'categories' => $loanableCategories,
    'isLoanable' => 'isProductLoanable',
    'getAll' => 'getLoanableProducts',
    'add' => 'addLoanableProduct',
    'remove' => 'removeLoanableProduct',
];
?>
