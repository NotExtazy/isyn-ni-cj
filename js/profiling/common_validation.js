/**
 * Common Validation Logic for Profiling Modules
 * Specifically for TIN (Tax Identification Number) Formatting and Validation
 */

const CommonValidation = {
    /**
     * Formats a TIN string into 000-000-000-000 format
     * @param {string} val - The raw input value
     * @returns {string} - The formatted TIN
     */
    formatTIN: (val) => {
        if (!val) return '';
        // Remove non-digits
        let x = val.replace(/\D/g, '').substring(0, 12); // Max 12 digits
        let formatted = '';
        
        // Add dashes after every 3 digits
        for (let i = 0; i < x.length; i++) {
            if (i > 0 && i % 3 === 0) {
                formatted += '-';
            }
            formatted += x[i];
        }
        return formatted;
    },

    /**
     * Binds the formatting logic to a specific input selector
     * @param {string} selector - jQuery selector (e.g., '#tin')
     */
    bindTINFormatting: (selector) => {
        $(selector).on('input', function() {
            let val = $(this).val();
            let formatted = CommonValidation.formatTIN(val);
            $(this).val(formatted);
        });
    },

    /**
     * Validates if a TIN is valid (Length check mainly)
     * @param {string} val - The formatted or raw TIN
     * @returns {boolean}
     */
    isValidTIN: (val) => {
        if (!val) return false;
        let clean = val.replace(/\D/g, '');
        // Standard TIN is 9 or 12 digits (usually 9 + 3 branch code = 12)
        // We enforce 12 digits based on previous files, or at least 9?
        // Previous files (isynstaff.js) checked `tin.length !== 12`.
        // So we will enforce 12 digits.
        return clean.length === 12;
    }
};
