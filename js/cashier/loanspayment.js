var transactClientNameTbl, transactTypeData = "", transactClientNameTblValue = "";
var paymentTbl, paymentTblValue = "";

var ListTtlPrincipal = 0, ListTtlInterest = 0, ListTtlPenalty = 0, ListFnlTotal = 0;

var itemsTblValue = "", SelectedFromItems = "";

// Suppress DataTables warnings globally
$.fn.dataTable.ext.errMode = 'none';

// Add error handling for Select2 initialization
$(document).ready(function() {
    console.log('Initializing loans payment page...');
    
    try {
        // Initialize Select2 with error handling
        if (typeof $.fn.select2 !== 'undefined') {
            $("#orFrom").select2({
                width: '100%',
            });

            $("#clientType").select2({
                width: '100%',
                dropdownParent: $('#otherDetailsMDL')
            });

            $("#clientName").select2({
                width: '100%',
                dropdownParent: $('#otherDetailsMDL')
            });
            
            console.log('Select2 initialized successfully');
        } else {
            console.warn('Select2 not available, using regular selects');
        }
        
        // Initialize the page with error handling
        try {
            Initialize();
        } catch (initError) {
            console.error('Error during Initialize:', initError);
            // Try to continue with basic functionality
            InitPaymentTbl();
        }
        
    } catch (error) {
        console.error('Error during page initialization:', error);
        // Continue with minimal functionality
        try {
            InitPaymentTbl();
        } catch (fallbackError) {
            console.error('Fallback initialization also failed:', fallbackError);
        }
    }
});

function Initialize(){
    console.log('Starting Initialize function...');
    
    $.ajax({
        url: baseUrl + "/routes/cashier/loanspayment.route.php",
        type: "POST",
        data: { action: "Initialize" },
        dataType: "JSON",
        timeout: 10000, // 10 second timeout
        beforeSend: function(){
            console.log('Sending Initialize request...');
        },
        success: function(response){
            console.log('Initialize response received:', response);
            
            try {
                $("#orFrom").empty().append("<option value='' selected> Select OR</option>");
                
                if (response.ORSERIES && Array.isArray(response.ORSERIES)) {
                    $.each(response.ORSERIES, function(key, value){
                        $("#orFrom").append("<option value='"+value["NAME"]+"'>"+value["NAME"]+"</option>");
                    });
                    console.log('OR Series loaded successfully');
                } else {
                    console.warn('No OR Series data received');
                }
                
            } catch (error) {
                console.error('Error processing Initialize response:', error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Initialize AJAX failed:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            // Add fallback data
            $("#orFrom").empty().append("<option value='' selected> Select OR</option>");
            $("#orFrom").append("<option value='OR-001'>OR-001</option>");
            $("#orFrom").append("<option value='OR-002'>OR-002</option>");
        }
    });
    
    InitPaymentTbl();
}

// Function to safely clear payment table while preserving payment data
function ClearPaymentTable() {
    try {
        // CRITICAL FIX: Preserve rows that have payment data (has-payment class)
        var paymentRows = $('#paymentTbl tbody tr.has-payment').clone(true);
        var hasPaymentData = paymentRows.length > 0;
        
        if (hasPaymentData) {
            console.log('Preserving', paymentRows.length, 'payment rows with data');
        }
        
        if (window.paymentTbl) {
            if (typeof window.paymentTbl.clear === 'function') {
                window.paymentTbl.clear().draw();
            } else {
                // Fallback for non-DataTable implementation
                $('#paymentList').empty();
            }
        } else {
            // Direct DOM manipulation
            $('#paymentList').empty();
        }
        
        // CRITICAL FIX: Restore payment rows after clearing
        if (hasPaymentData) {
            $('#paymentList').append(paymentRows);
            console.log('Restored', paymentRows.length, 'payment rows with payment data');
            
            // Recalculate totals for the preserved payment rows
            calculateAndDisplayTotals();
        } else {
            // Reset footer totals only if no payment data to preserve
            $('#totalPrincipal').text('₱0.00');
            $('#totalInterest').text('₱0.00');
            $('#totalPenalty').text('₱0.00');
            $('#totalBalance').text('₱0.00');
            $('#totalAmount').text('₱0.00');
            $('#paidCount').text('0');
            $('#unpaidCount').text('0');
            
            // Reset totals
            ListTtlPrincipal = 0;
            ListTtlInterest = 0;
            ListTtlPenalty = 0;
            ListFnlTotal = 0;
        }
        
        // Cancel any active edit mode
        cancelEditMode();
        
        // Clear stored row data only if no payments to preserve
        if (!hasPaymentData) {
            window.currentSelectedRow = null;
            window.currentEditingRow = null;
        }
        
        // Adjust table size
        setTimeout(function() {
            adjustTableSize();
        }, 100);
        
        console.log('Payment table cleared successfully' + (hasPaymentData ? ' (payment data preserved)' : ''));
    } catch (error) {
        console.error('Error clearing payment table:', error);
    }
}

// Function to completely clear all payment data (use when user wants to start fresh)
function ClearAllPaymentData() {
    try {
        if (window.paymentTbl) {
            if (typeof window.paymentTbl.clear === 'function') {
                window.paymentTbl.clear().draw();
            } else {
                $('#paymentList').empty();
            }
        } else {
            $('#paymentList').empty();
        }
        
        // Reset footer totals
        $('#totalPrincipal').text('₱0.00');
        $('#totalInterest').text('₱0.00');
        $('#totalPenalty').text('₱0.00');
        $('#totalBalance').text('₱0.00');
        $('#totalAmount').text('₱0.00');
        $('#paidCount').text('0');
        $('#unpaidCount').text('0');
        
        // Cancel any active edit mode
        cancelEditMode();
        
        // Clear stored row data
        window.currentSelectedRow = null;
        window.currentEditingRow = null;
        
        // Reset totals
        ListTtlPrincipal = 0;
        ListTtlInterest = 0;
        ListTtlPenalty = 0;
        ListFnlTotal = 0;
        
        console.log('All payment data cleared completely');
    } catch (error) {
        console.error('Error clearing all payment data:', error);
    }
}

function InitPaymentTbl(){
    console.log('Initializing payment table...');
    
    try {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#paymentTbl')) {
            $('#paymentTbl').DataTable().destroy();
            console.log('Destroyed existing payment table');
        }
        
        // Clear any DataTable classes and attributes
        $('#paymentTbl').removeClass('dataTable').removeAttr('role').removeAttr('aria-describedby');
        
        // Ensure table structure is clean and valid
        var table = $('#paymentTbl');
        if (table.length === 0) {
            console.error('Payment table element not found');
            return;
        }
        
        // Verify table structure
        var thead = table.find('thead');
        var tbody = table.find('tbody');
        var tfoot = table.find('tfoot');
        
        if (thead.length === 0 || tbody.length === 0) {
            console.error('Invalid table structure - missing thead or tbody');
            return;
        }
        
        // Count columns in header
        var headerCols = thead.find('tr:first th').length;
        console.log('Header columns:', headerCols);
        
        // Ensure footer matches header structure
        if (tfoot.length > 0) {
            var footerCols = tfoot.find('tr:first th, tr:first td').length;
            console.log('Footer columns:', footerCols);
            
            if (footerCols !== headerCols) {
                console.warn('Header/Footer column mismatch. Header:', headerCols, 'Footer:', footerCols);
                // Try to fix footer structure
                var footerRow = tfoot.find('tr:first');
                var currentFooterCells = footerRow.find('th, td').length;
                
                if (currentFooterCells < headerCols) {
                    // Add missing cells
                    for (var i = currentFooterCells; i < headerCols; i++) {
                        footerRow.append('<th></th>');
                    }
                    console.log('Added missing footer cells');
                }
            }
        }
        
        // Clear tbody to ensure clean state
        tbody.empty();
        tbody.append('<tr><td colspan="5" class="text-center text-muted">Please select a client to view payment details</td></tr>');
        
        // Initialize DataTable with minimal configuration (no footer since it's separate)
        console.log('Attempting to initialize DataTable...');
        paymentTbl = table.DataTable({
            searching: false,
            ordering: false,
            info: false,
            paging: false,
            lengthChange: false,
            scrollY: '300px',
            scrollX: true,  
            scrollCollapse: true,
            responsive: false,
            autoWidth: false,
            destroy: true, // Allow re-initialization
            columnDefs: [
                { targets: [1, 2, 3, 4, 5], className: 'dt-right' }
            ],
            language: {
                emptyTable: "Please select a client to view payment details"
            },
            drawCallback: function(settings) {
                console.log('DataTable draw completed');
                // Update footer totals after each draw
                updatePaymentFooterTotals();
            }
        });
        
        console.log('Payment table initialized successfully');
        
        // Verify the DataTable is working
        if (paymentTbl && typeof paymentTbl.rows === 'function') {
            console.log('DataTable API is functional');
        } else {
            console.error('DataTable API is not functional');
        }
        
    } catch (error) {
        console.error('Error initializing payment table:', error);
        
        // Complete fallback: disable DataTables and use simple table
        console.log('Attempting complete fallback - disabling DataTables...');
        try {
            // Destroy any partial DataTable
            if ($.fn.DataTable.isDataTable('#paymentTbl')) {
                $('#paymentTbl').DataTable().destroy();
            }
            
            // Remove all DataTable classes and attributes
            $('#paymentTbl').removeClass('dataTable').removeAttr('role').removeAttr('aria-describedby');
            $('#paymentTbl_wrapper').remove(); // Remove wrapper if it exists
            
            // Just ensure the table is visible and functional
            $('#paymentTbl').show().addClass('table table-bordered table-hover');
            
            // Set up manual totals calculation with data attributes
            window.paymentTbl = {
                // Mock DataTable API for compatibility
                rows: function() {
                    return {
                        data: function() {
                            var data = [];
                            $('#paymentTbl tbody tr.payment-row').each(function() {
                                var $row = $(this);
                                
                                // Extract data from data attributes and cell values
                                var clientName = $row.data('client-name') || $row.find('td:eq(0)').text().trim();
                                var principal = parseFloat($row.data('principal')) || 0;
                                var interest = parseFloat($row.data('interest')) || 0;
                                var penalty = parseFloat($row.data('penalty')) || 0;
                                var balance = parseFloat($row.data('balance')) || 0;
                                var total = parseFloat($row.data('total')) || 0;
                                var clientNo = $row.data('client-no') || '';
                                var loanId = $row.data('loan-id') || '';
                                var fund = $row.data('fund') || 'GENERAL';
                                
                                // Create array in the expected format: [clientName, principal, interest, penalty, balance, total, clientNo, loanId, fund]
                                var row = [
                                    clientName,     // [0]
                                    principal,      // [1]
                                    interest,       // [2]
                                    penalty,        // [3]
                                    balance,        // [4]
                                    total,          // [5]
                                    clientNo,       // [6]
                                    loanId,         // [7]
                                    fund            // [8]
                                ];
                                
                                if (row.length > 0 && loanId && clientNo) {
                                    data.push(row);
                                }
                            });
                            return data;
                        }
                    };
                },
                clear: function() {
                    $('#paymentTbl tbody').empty();
                    return this;
                },
                destroy: function() {
                    return this;
                },
                draw: function() {
                    // Update footer totals when draw is called
                    updatePaymentFooterTotals();
                    return this;
                }
            };
            
            console.log('Fallback initialization successful');
        } catch (fallbackError) {
            console.error('Fallback initialization failed:', fallbackError);
        }
    }
}

function InitTransctClientNameTBL(){
    // Use simple table without DataTables to avoid initialization issues
    console.log('Using simple table for client names - DataTables disabled for stability');
    
    // Ensure table is visible and properly structured
    try {
        var table = $('#transactClientNameTbl');
        if (table.length === 0) {
            console.error('Client table not found');
            return;
        }
        
        // Make sure table is visible and larger
        table.show();
        
        // Add basic styling for functionality with increased size
        table.addClass('table table-bordered table-hover');
        
        // Adjust table container for better visibility
        var tableContainer = table.closest('.table-responsive-custom');
        if (tableContainer.length > 0) {
            tableContainer.css({
                'max-height': '550px',
                'overflow-y': 'auto',
                'min-height': '550px'
            });
        }
        
        // Make table rows more spacious
        table.find('tbody tr').css({
            'height': '60px',
            'min-height': '60px'
        });
        
        console.log('Simple client table initialized successfully with increased size');
        
    } catch (error) {
        console.error('Error setting up client table:', error);
    }
    
    return; // Exit early - no DataTables initialization
    
    /* DISABLED - DataTables causing issues with dynamic content
    try {
        var headerCount = $('#transactClientNameTbl thead tr th').length;
        if (headerCount === 0) {
            console.error('Cannot initialize DataTable: No headers found');
            return;
        }

        var rowCount = $('#transactClientNameTbl tbody tr').length;
        console.log('Initializing DataTable with', headerCount, 'headers and', rowCount, 'rows');

        if ($.fn.DataTable.isDataTable('#transactClientNameTbl')) {
            console.log('DataTable already initialized, destroying completely');
            try {
                $('#transactClientNameTbl').DataTable().clear().destroy(true);
                $('#transactClientNameTbl').removeClass('dataTable').removeAttr('role').removeAttr('aria-describedby');
            } catch(e) {
                console.warn('Destroy failed, forcing cleanup:', e);
            }
        }

        transactClientNameTbl = $('#transactClientNameTbl').DataTable({
            ordering:false,
            info:false,
            paging:false,
            lengthChange:false,
            scrollY: '400px',
            scrollX: true,  
            scrollCollapse: true,
            responsive:false,
            searching:false,
            autoWidth: false,
            deferRender: true
        });
        
        console.log('DataTable initialized successfully');
    } catch (error) {
        console.error('Error initializing transactClientNameTbl:', error);
        
        try {
            if ($.fn.DataTable.isDataTable('#transactClientNameTbl')) {
                $('#transactClientNameTbl').DataTable().destroy(true);
            }
            $('#transactClientNameTbl').removeClass('dataTable').removeAttr('role').removeAttr('aria-describedby');
        } catch(e) {
            console.warn('Cleanup failed:', e);
        }
        
        console.error('Table initialization failed, but continuing...');
    }
    */
}

function BuildReportTable(listViewName,ListName){
    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"BuildReportTable", listViewName:listViewName, ListName:ListName},
        dataType:"JSON",
        beforeSend:function(){
            $("#transactClientNameList").empty();
            if ($.fn.DataTable.isDataTable('#transactClientNameTbl')) {
                $('#transactClientNameTbl').DataTable().clear();
                $('#transactClientNameTbl').DataTable().destroy();
            }
        },
        success:function(response){
            var headerData = response.TBLHEADER

            var headerRow = "";
            for (var i = 0; i < headerData.length; i++) { 
                var headerName = headerData[i].ColumnName;
                
                // Skip Product and Date Release columns
                if (headerName === 'Product' || headerName === 'Date Release') {
                    continue;
                }

                headerRow += (headerRow === "") ? "<tr>" : "";
                headerRow += "<th>" + headerName + "</th>";
            }

            headerRow += "</tr>";
            $("#transactClientNameTbl thead").html(headerRow);
            InitTransctClientNameTBL();
        }, 
    })
}

// Store original function for search compatibility
window.originalLoadTransactClientName = function(transactType){
    console.log('Loading transaction client names for type:', transactType);
    
    if (!transactType) {
        console.warn('No transaction type provided');
        $("#transactClientNameList").html('<tr><td colspan="4" class="text-center">Please select a transaction type</td></tr>');
        return;
    }
    
    var listViewName = "lstAccounts";
    $.ajax({
        url: baseUrl + "/routes/cashier/loanspayment.route.php",
        type: "POST",
        data: { action: "LoadTransactClientName", listViewName: listViewName, transactType: transactType },
        dataType: "JSON",
        timeout: 10000,
        beforeSend: function(){
            console.log('Loading clients...');
            $("#transactClientNameList").html('<tr><td colspan="4" class="text-center">Loading clients...</td></tr>');

            ClearPaymentTable();
            ClearPrimaryAccountDetails();

            $("#depositoryBank").empty();
        },
        success: function(response){
            console.log('LoadTransactClientName response:', response);
            
            try {
                // ==============
                transactTypeData = transactType;

                // Debug: Log the response
                console.log('=== LOANS PAYMENT DEBUG START ===');
                console.log('Transaction Type:', transactType);
                console.log('Response:', response);
                console.log('TBLHEADER:', response.TBLHEADER);
                console.log('TBLHEADER length:', response.TBLHEADER ? response.TBLHEADER.length : 'undefined');
            console.log('ACCOUNTS:', response.ACCOUNTS);
            console.log('ACCOUNTS length:', response.ACCOUNTS ? response.ACCOUNTS.length : 'undefined');
            console.log('=== DEBUG END ===');

            // BuildReportTable("lstAccounts",transactType);

            // Destroy existing DataTable first - AGGRESSIVE CLEANUP
            if ($.fn.DataTable.isDataTable('#transactClientNameTbl')) {
                try {
                    var table = $('#transactClientNameTbl').DataTable();
                    table.clear().draw();
                    table.destroy(true); // true = remove all DataTable enhancements completely
                    console.log('DataTable destroyed successfully');
                } catch(e) {
                    console.warn('Error destroying DataTable:', e);
                }
            }
            
            // Remove all DataTable classes and attributes
            $('#transactClientNameTbl').removeClass('dataTable').removeAttr('role').removeAttr('aria-describedby');
            
            // Ensure table structure exists
            if ($('#transactClientNameTbl thead').length === 0) {
                $('#transactClientNameTbl').prepend('<thead></thead>');
            }
            if ($('#transactClientNameTbl tbody').length === 0) {
                $('#transactClientNameTbl').append('<tbody id="transactClientNameList"></tbody>');
            }
            
            // Clear existing content
            $('#transactClientNameTbl thead').empty();
            $('#transactClientNameTbl tbody').empty();

            // Build Dynamic Table Header
            var headerData = response.TBLHEADER;

            // Check if headerData exists and has columns
            if (!headerData || headerData.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Configuration Error',
                    html: 'Table columns are not configured in the database.<br><br>' +
                          'Please run the <b>SAFE_INSERT_ONLY.sql</b> script to configure the columns for transaction type: <b>' + transactType + '</b><br><br>' +
                          'Missing entry: <b>lstAccounts-' + transactType + '</b>',
                });
                return;
            }

            console.log('Building headers for', headerData.length, 'columns');

            var headerRow = "";
            var visibleColumnCount = 0;
            for (var i = 0; i < headerData.length; i++) { 
                var headerName = headerData[i].ColumnName;
                
                // Skip Product and Date Release columns
                if (headerName === 'Product' || headerName === 'Date Release') {
                    continue;
                }

                headerRow += (headerRow === "") ? "<tr>" : "";
                headerRow += "<th>" + headerName + "</th>";
                visibleColumnCount++;
            }
            
            // Add Release Status column header at the end (only for INDIVIDUAL transactions)
            if (response.TRANSTYPE === 'SINGLE') {
                headerRow += "<th class='text-center'>Loan Status</th>";
                headerRow += "<th class='text-center'>Release Status</th>";
                headerRow += "<th class='text-center'>Payment Status</th>";
                visibleColumnCount += 3;
            }

            headerRow += "</tr>";
            $("#transactClientNameTbl thead").html(headerRow);

            console.log('Headers built:', headerRow);

            // Clear and build table body
            $("#transactClientNameList").empty();

            // Build Dynamic Table Body - FULLY DYNAMIC based on ColumnField from TBL_DYNALISTS
            if (response.ACCOUNTS && response.ACCOUNTS.length > 0) {
                console.log('Building', response.ACCOUNTS.length, 'rows');
                
                $.each(response.ACCOUNTS, function(key, value) {
                    var row = "<tr data-full-record='" + JSON.stringify(value).replace(/'/g, '&apos;') + "'>";
                    var cellCount = 0;
                    
                    // Loop through header data to build columns dynamically
                    for (var i = 0; i < headerData.length; i++) {
                        var headerName = headerData[i].ColumnName;
                        var fieldName = headerData[i].ColumnField || headerData[i].ColumnName;
                        
                        // Skip Product and Date Release columns
                        if (headerName === 'Product' || headerName === 'Date Release') {
                            continue;
                        }
                        
                        var cellValue = value[fieldName] || '-';
                        row += "<td>" + cellValue + "</td>";
                        cellCount++;
                    }
                    
                    // Add Loan Status and Release Status columns at the end (only for INDIVIDUAL transactions)
                    if (response.TRANSTYPE === 'SINGLE') {
                        var balance = parseFloat(value['Balance'] || value['BALANCE'] || 0);
                        var loanAmount = parseFloat(value['LoanAmount'] || value['LOANAMOUNT'] || 0);
                        
                        // Debug: Log balance for first few rows
                        if (key < 3) {
                            console.log('Row', key, '- Balance:', balance, 'LoanAmount:', loanAmount, 'Raw Balance:', value['Balance'] || value['BALANCE']);
                        }
                        
                        // Determine loan status based on balance
                        // Use threshold to handle floating point precision issues
                        var loanStatus = '';
                        var loanStatusBadge = '';
                        if (Math.abs(balance) < 0.01) { // Balance is effectively zero (less than 1 cent)
                            loanStatus = 'PAID';
                            loanStatusBadge = '<span class="badge bg-success" style="font-size: 0.75rem;">✓ Paid</span>';
                        } else if (balance > 0 && balance < loanAmount) {
                            loanStatus = 'PARTIAL';
                            loanStatusBadge = '<span class="badge bg-info text-white" style="font-size: 0.75rem;">◐ Partial</span>';
                        } else {
                            loanStatus = 'ACTIVE';
                            loanStatusBadge = '<span class="badge bg-primary" style="font-size: 0.75rem;">● Active</span>';
                        }
                        
                        // Add Loan Status column
                        row += "<td class='text-center'>" + loanStatusBadge + "</td>";
                        cellCount++;
                        
                        // Check release status
                        var checkPrinted = value['checkprinted'] || value['CHECKPRINTED'] || value['CheckPrinted'];
                        var isReleased = checkPrinted && checkPrinted.toString().toUpperCase() === 'YES';
                        
                        // Add data attributes to track status
                        if (!isReleased) {
                            // Mark row as not released
                            row = row.replace("<tr ", "<tr class='loan-not-released' data-released='false' data-loan-status='" + loanStatus + "' ");
                        } else if (loanStatus === 'PAID') {
                            // Mark paid loans differently
                            row = row.replace("<tr ", "<tr class='loan-paid' data-released='true' data-loan-status='PAID' ");
                        } else {
                            row = row.replace("<tr ", "<tr data-released='true' data-loan-status='" + loanStatus + "' ");
                        }
                        
                        // Add Release Status column
                        var releaseStatusBadge = '';
                        if (isReleased) {
                            releaseStatusBadge = '<span class="badge bg-success" style="font-size: 0.65rem; padding: 4px 8px;">✓ Released</span>';
                        } else {
                            releaseStatusBadge = '<span class="badge bg-danger text-white" style="font-size: 0.65rem; padding: 4px 8px;">🚫 Not Released</span>';
                        }
                        
                        row += "<td class='text-center'>" + releaseStatusBadge + "</td>";
                        cellCount++;
                        
                        // Add Payment Status column (check if late and show next due date)
                        var dateRelease = value['DateRelease'] || value['DATERELEASE'];
                        var lastPaymentDate = value['LastPaymentDate'] || value['LASTPAYMENTDATE'];
                        var mode = value['Mode'] || value['MODE'];
                        var paymentStatusBadge = '<span class="badge bg-secondary" style="font-size: 0.7rem; padding: 4px 10px; font-weight: 600;">⏳ Checking...</span>';
                        
                        // Calculate next due date based on last payment or release date
                        if (balance > 0.01 && dateRelease && mode) { // Balance must be more than 1 cent to be considered active
                            var today = new Date();
                            var releaseDate = new Date(dateRelease);
                            
                            // Calculate the original due date from release date
                            var originalDueDate = new Date(releaseDate);
                            if (mode === 'WEEKLY') {
                                originalDueDate.setDate(originalDueDate.getDate() + 7);
                            } else if (mode === 'SEMI-MONTHLY') {
                                originalDueDate.setDate(originalDueDate.getDate() + 15);
                            } else if (mode === 'MONTHLY') {
                                originalDueDate.setMonth(originalDueDate.getMonth() + 1);
                            } else if (mode === 'BI-MONTHLY') {
                                originalDueDate.setMonth(originalDueDate.getMonth() + 2);
                            } else if (mode === 'QUARTERLY') {
                                originalDueDate.setMonth(originalDueDate.getMonth() + 3);
                            } else if (mode === 'SEMI-ANNUAL') {
                                originalDueDate.setMonth(originalDueDate.getMonth() + 6);
                            } else if (mode === 'ANNUAL') {
                                originalDueDate.setMonth(originalDueDate.getMonth() + 12);
                            } else if (mode === 'LUMPSUM') {
                                var maturityDate = value['DateMature'] || value['DATEMATURE'];
                                if (maturityDate) {
                                    originalDueDate = new Date(maturityDate);
                                }
                            }
                            
                            // Calculate next due date from last payment if available
                            var nextDueDate = new Date(originalDueDate);
                            if (lastPaymentDate) {
                                var lastPayment = new Date(lastPaymentDate);
                                nextDueDate = new Date(lastPayment);
                                
                                if (mode === 'WEEKLY') {
                                    nextDueDate.setDate(nextDueDate.getDate() + 7);
                                } else if (mode === 'SEMI-MONTHLY') {
                                    nextDueDate.setDate(nextDueDate.getDate() + 15);
                                } else if (mode === 'MONTHLY') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 1);
                                } else if (mode === 'BI-MONTHLY') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 2);
                                } else if (mode === 'QUARTERLY') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 3);
                                } else if (mode === 'SEMI-ANNUAL') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 6);
                                } else if (mode === 'ANNUAL') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 12);
                                } else if (mode === 'LUMPSUM') {
                                    var maturityDate = value['DateMature'] || value['DATEMATURE'];
                                    if (maturityDate) {
                                        nextDueDate = new Date(maturityDate);
                                    }
                                }
                            }
                            
                            // Format next due date
                            var nextDueDateStr = nextDueDate.toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            });
                            
                            // Check if currently overdue OR if last payment was made while overdue
                            var isCurrentlyLate = today > originalDueDate;
                            var wasPaymentLate = lastPaymentDate && new Date(lastPaymentDate) > originalDueDate;
                            
                            if (isCurrentlyLate || wasPaymentLate) {
                                var daysLate = Math.floor((today - originalDueDate) / (1000 * 60 * 60 * 24));
                                if (lastPaymentDate && wasPaymentLate) {
                                    // Show PAID LATE with next due date
                                    var paidDateStr = new Date(lastPaymentDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                    paymentStatusBadge = '<span class="badge" style="background: #ff9800; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(255,152,0,0.3); line-height: 1.5;">⚠️ PAID LATE<br><small style="font-size: 0.6rem; opacity: 0.95;">Last: ' + paidDateStr + ' | Next: ' + nextDueDateStr + '</small></span>';
                                } else {
                                    // Currently late, no payment yet or payment was on time but now late again
                                    paymentStatusBadge = '<span class="badge" style="background: #ff9800; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(255,152,0,0.3); line-height: 1.5;">⚠️ LATE ' + daysLate + 'd<br><small style="font-size: 0.6rem; opacity: 0.95;">Due: ' + nextDueDateStr + '</small></span>';
                                }
                            } else {
                                // Payment is on time or early - both show as green
                                if (lastPaymentDate) {
                                    var lastPayment = new Date(lastPaymentDate);
                                    var daysEarly = Math.floor((originalDueDate - lastPayment) / (1000 * 60 * 60 * 24));
                                    
                                    if (daysEarly > 0) {
                                        // Advance payment (paid before due date) - GREEN
                                        var paidDateStr = lastPayment.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                        paymentStatusBadge = '<span class="badge" style="background: #4caf50; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); line-height: 1.5;">⚡ ADVANCE PAYMENT<br><small style="font-size: 0.6rem; opacity: 0.95;">Last: ' + paidDateStr + ' | Next: ' + nextDueDateStr + '</small></span>';
                                    } else {
                                        // Paid exactly on due date - GREEN
                                        var paidDateStr = lastPayment.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                        paymentStatusBadge = '<span class="badge" style="background: #4caf50; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); line-height: 1.5;">✓ ON TIME<br><small style="font-size: 0.6rem; opacity: 0.95;">Last: ' + paidDateStr + ' | Next: ' + nextDueDateStr + '</small></span>';
                                    }
                                } else {
                                    // No payment yet, but not late - GREEN
                                    var daysUntilDue = Math.floor((nextDueDate - today) / (1000 * 60 * 60 * 24));
                                    paymentStatusBadge = '<span class="badge" style="background: #4caf50; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); line-height: 1.5;">✓ ON TIME<br><small style="font-size: 0.6rem; opacity: 0.95;">Next: ' + nextDueDateStr + '</small></span>';
                                }
                            }
                        } else if (Math.abs(balance) < 0.01) { // Balance is effectively zero (less than 1 cent)
                            // For paid loans, check if it was paid while overdue (past due date)
                            if (lastPaymentDate && dateRelease && mode) {
                                var lastPayment = new Date(lastPaymentDate);
                                var baseDate = lastPaymentDate ? new Date(lastPaymentDate) : new Date(dateRelease);
                                var today = new Date(lastPaymentDate); // Use payment date as "today" for calculation
                                var nextDueDate = new Date(dateRelease);
                                
                                // Calculate what the due date was at the time of payment
                                // We need to find the most recent due date before the payment
                                if (mode === 'WEEKLY') {
                                    nextDueDate.setDate(nextDueDate.getDate() + 7);
                                } else if (mode === 'SEMI-MONTHLY') {
                                    nextDueDate.setDate(nextDueDate.getDate() + 15);
                                } else if (mode === 'MONTHLY') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 1);
                                } else if (mode === 'BI-MONTHLY') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 2);
                                } else if (mode === 'QUARTERLY') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 3);
                                } else if (mode === 'SEMI-ANNUAL') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 6);
                                } else if (mode === 'ANNUAL') {
                                    nextDueDate.setMonth(nextDueDate.getMonth() + 12);
                                } else if (mode === 'LUMPSUM') {
                                    // For lumpsum, use maturity date if available
                                    var maturityDate = value['DateMature'] || value['DATEMATURE'];
                                    if (maturityDate) {
                                        nextDueDate = new Date(maturityDate);
                                    }
                                }
                                
                                // Check if payment was made after the due date (loan was overdue when paid)
                                if (lastPayment > nextDueDate) {
                                    var daysLate = Math.floor((lastPayment - nextDueDate) / (1000 * 60 * 60 * 24));
                                    var paidDateStr = lastPayment.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                    paymentStatusBadge = '<span class="badge" style="background: #ff9800; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(255,152,0,0.3); line-height: 1.5;">⚠️ PAID LATE<br><small style="font-size: 0.6rem; opacity: 0.95;">Paid: ' + paidDateStr + ' (' + daysLate + 'd late)</small></span>';
                                } else {
                                    // Paid on time or early - both GREEN
                                    var daysEarly = Math.floor((nextDueDate - lastPayment) / (1000 * 60 * 60 * 24));
                                    var paidDateStr = lastPayment.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                                    
                                    if (daysEarly > 0) {
                                        // Advance payment (paid before due date) - GREEN
                                        paymentStatusBadge = '<span class="badge" style="background: #4caf50; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); line-height: 1.5;">⚡ ADVANCE PAYMENT<br><small style="font-size: 0.6rem; opacity: 0.95;">Paid: ' + paidDateStr + ' (' + daysEarly + 'd early)</small></span>';
                                    } else {
                                        // Paid exactly on due date - GREEN
                                        paymentStatusBadge = '<span class="badge" style="background: #4caf50; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); line-height: 1.5;">✓ PAID ON TIME<br><small style="font-size: 0.6rem; opacity: 0.95;">Paid: ' + paidDateStr + '</small></span>';
                                    }
                                }
                            } else {
                                // Fallback if we don't have enough data to determine if late - GREEN
                                var completionMsg = lastPaymentDate ? 'Paid: ' + new Date(lastPaymentDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'PAID';
                                paymentStatusBadge = '<span class="badge" style="background: #4caf50; color: white; font-size: 0.7rem; padding: 4px 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(76,175,80,0.3); line-height: 1.5;">✓ PAID<br><small style="font-size: 0.6rem; opacity: 0.95;">' + completionMsg + '</small></span>';
                            }
                        }
                        
                        row += "<td class='text-center'>" + paymentStatusBadge + "</td>";
                        cellCount++;
                    }
                    
                    row += "</tr>";
                    
                    // Debug: Log first row details
                    if (key === 0) {
                        console.log('First row HTML:', row);
                        console.log('Cells in first row:', cellCount);
                        console.log('Headers count:', headerData.length);
                        console.log('First account data:', value);
                    }
                    
                    $("#transactClientNameList").append(row);
                });
                
                // Final verification
                var actualHeaderCount = $('#transactClientNameTbl thead tr th').length;
                var actualCellCount = $('#transactClientNameTbl tbody tr:first td').length;
                console.log('Final check - Headers:', actualHeaderCount, 'Cells:', actualCellCount);
                
                if (actualHeaderCount !== actualCellCount && actualHeaderCount > 0) {
                    console.error('MISMATCH! Headers:', actualHeaderCount, 'Cells:', actualCellCount);
                    // Don't show error to user, just log it
                    console.warn('Column mismatch detected but continuing...');
                }
            } else {
                console.log('No accounts data, showing empty message');
                $("#transactClientNameList").append(`
                    <tr>
                        <td colspan="${visibleColumnCount || 4}">No Data to Display..</td>
                    </tr>
                `);
            }

            // Initialize table (simple version, no DataTables)
            console.log('Setting up simple table...');
            InitTransctClientNameTBL();
            console.log('Table setup completed successfully');
            
            } catch (error) {
                console.error('Error processing LoadTransactClientName response:', error);
                $("#transactClientNameList").html('<tr><td colspan="4" class="text-center text-danger">Error processing data</td></tr>');
            }
        },
        error: function(xhr, status, error){
            console.error('LoadTransactClientName AJAX failed:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            $("#transactClientNameList").html('<tr><td colspan="4" class="text-center text-danger">Failed to load clients: ' + error + '</td></tr>');
            
            // Show user-friendly error
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error Loading Data',
                    text: 'Failed to load transaction data: ' + error,
                });
            } else {
                alert('Failed to load transaction data: ' + error);
            }
        }
    })
}

$('#transactClientNameTbl tbody').on('click', 'tr',function(e){
    // Simple table without DataTables
    let classList = e.currentTarget.classList;
    let $row = $(this);
    
    // Check if row has data (not the "No Data" row)
    if ($row.find('td').length === 0 || $row.find('td[colspan]').length > 0) {
        return; // Skip empty or "No Data" rows
    }
    
    // CRITICAL: Check if loan is released before allowing selection
    var isReleased = $row.attr('data-released');
    var loanStatus = $row.attr('data-loan-status');
    
    if (isReleased === 'false') {
        Swal.fire({
            icon: 'error',
            title: '<span style="color: #dc3545; font-weight: 700; font-size: 1.5rem;">🚫 Loan Not Released</span>',
            html: `
                <div style="text-align: left; padding: 1rem; background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%); border-radius: 12px; margin: 1rem 0;">
                    <div style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1rem;">
                        <div style="flex-shrink: 0; width: 48px; height: 48px; background: #dc3545; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);">
                            <i class="fa-solid fa-lock" style="color: white; font-size: 1.5rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #721c24; font-size: 1.1rem; font-weight: 600;">Payment Blocked</h4>
                            <p style="margin: 0; color: #721c24; font-size: 0.95rem; line-height: 1.6;">
                                This loan has not been released yet and is not available for payment processing.
                            </p>
                        </div>
                    </div>
                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc3545;">
                        <p style="margin: 0 0 0.5rem 0; color: #495057; font-size: 0.9rem; font-weight: 600;">
                            <i class="fa-solid fa-circle-info" style="color: #dc3545; margin-right: 0.5rem;"></i>What to do:
                        </p>
                        <p style="margin: 0; color: #6c757d; font-size: 0.9rem; line-height: 1.5;">
                            Please release this loan first in <strong>Pending Releases</strong> before accepting payments.
                        </p>
                    </div>
                </div>
            `,
            confirmButtonText: '<i class="fa-solid fa-check"></i> Got It',
            confirmButtonColor: '#dc3545',
            customClass: {
                popup: 'swal-modern-popup',
                title: 'swal-modern-title',
                htmlContainer: 'swal-modern-html',
                confirmButton: 'swal-modern-button'
            },
            showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
        });
        return; // Prevent selection
    }
    
    if (loanStatus === 'PAID') {
        Swal.fire({
            icon: 'warning',
            title: '<span style="color: #ff9800; font-weight: 700; font-size: 1.5rem;">✓ Loan Already Paid</span>',
            html: `
                <div style="text-align: left; padding: 1rem; background: linear-gradient(135deg, #fffbf0 0%, #fff3e0 100%); border-radius: 12px; margin: 1rem 0;">
                    <div style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1rem;">
                        <div style="flex-shrink: 0; width: 48px; height: 48px; background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);">
                            <i class="fa-solid fa-circle-check" style="color: white; font-size: 1.5rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #e65100; font-size: 1.1rem; font-weight: 600;">Loan Completed</h4>
                            <p style="margin: 0; color: #e65100; font-size: 0.95rem; line-height: 1.6;">
                                This loan has been fully paid with a balance of <strong>₱0.00</strong>.
                            </p>
                        </div>
                    </div>
                    <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ff9800;">
                        <p style="margin: 0 0 0.5rem 0; color: #495057; font-size: 0.9rem; font-weight: 600;">
                            <i class="fa-solid fa-circle-info" style="color: #ff9800; margin-right: 0.5rem;"></i>Status:
                        </p>
                        <p style="margin: 0; color: #6c757d; font-size: 0.9rem; line-height: 1.5;">
                            No further payments are needed. If this is incorrect, please verify the loan balance in the system.
                        </p>
                    </div>
                </div>
            `,
            confirmButtonText: '<i class="fa-solid fa-check"></i> Understood',
            confirmButtonColor: '#ff9800',
            customClass: {
                popup: 'swal-modern-popup',
                title: 'swal-modern-title',
                htmlContainer: 'swal-modern-html',
                confirmButton: 'swal-modern-button'
            },
            showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
        });
        return; // Prevent selection
    }
    
    if (classList.contains('selected')) {
        classList.remove('selected');
        ClearPaymentTable();
        if (transactTypeData == "INDIVIDUAL" || transactTypeData == "WRITEOFF") {
            ClearPrimaryAccountDetails();
            ClearEditAmounts();
            $("#depositoryBank").empty();
        }
    } else {
        // Remove selected from all rows
        $('#transactClientNameTbl tbody tr').removeClass('selected');
        classList.add('selected');

        // Get full record data from data attribute
        var fullRecordJson = $row.attr('data-full-record');
        var fullRecord = null;
        
        try {
            if (fullRecordJson) {
                fullRecord = JSON.parse(fullRecordJson.replace(/&apos;/g, "'"));
                console.log('Full record data:', fullRecord);
            }
        } catch (error) {
            console.error('Error parsing full record data:', error);
        }
        
        // Fallback to cell data if full record is not available
        if (!fullRecord) {
            console.warn('Full record not available, using cell data as fallback');
            var cells = $row.find('td');
            var rowData = [];
            cells.each(function() {
                rowData.push($(this).text());
            });
            transactClientNameTblValue = rowData;
        } else {
            transactClientNameTblValue = fullRecord;
        }
        
        var id = "";
        var filter = "";
        
        if (transactTypeData == "CENTER") {
            if (fullRecord) {
                id = fullRecord.CENTERNAME || fullRecord.centername || '';
                filter = "CENTERNAME = '" + id + "'";
            } else {
                id = transactClientNameTblValue[0];
                filter = "CENTERNAME = '" + id + "'";
            }
            LoadAccountDetails(filter,transactTypeData);
        } else if (transactTypeData == "GROUP") {
            if (fullRecord) {
                var centerName = fullRecord.CENTERNAME || fullRecord.centername || '';
                var groupName = fullRecord.GROUPNAME || fullRecord.groupname || '';
                filter = "CENTERNAME = '" + centerName + "' AND GROUPNAME = '" + groupName + "'";
                id = groupName;
            } else {
                filter = "CENTERNAME = '" + transactClientNameTblValue[1] + "' AND GROUPNAME = '" + transactClientNameTblValue[0] + "'";
                id = transactClientNameTblValue[0];
            }
            LoadAccountDetails(filter,transactTypeData);
        } else if (transactTypeData == "WRITEOFF") {
            if (fullRecord) {
                var centerName = fullRecord.CENTERNAME || fullRecord.centername || '';
                var groupName = fullRecord.GROUPNAME || fullRecord.groupname || '';
                filter = "CENTERNAME = '" + centerName + "' AND GROUPNAME = '" + groupName + "'";
                id = groupName;
            } else {
                filter = "CENTERNAME = '" + transactClientNameTblValue[1] + "' AND GROUPNAME = '" + transactClientNameTblValue[0] + "'";
                id = transactClientNameTblValue[0];
            }
            LoadAccountDetails(filter,transactTypeData);
        } else {
            // INDIVIDUAL - Use full record data for accurate filter construction
            if (fullRecord) {
                var clientNo = fullRecord.ClientNo || fullRecord.clientno || '';
                var loanId = fullRecord.LoanID || fullRecord.loanid || '';
                console.log('INDIVIDUAL filter construction:', {clientNo: clientNo, loanId: loanId});
                filter = "CLIENTNO = '" + clientNo + "' AND LOANID = '" + loanId + "'";
                id = clientNo;
            } else {
                // Fallback - but this might be wrong due to column filtering
                console.warn('Using fallback cell data for INDIVIDUAL - this might be incorrect');
                id = transactClientNameTblValue[1];
                filter = "CLIENTNO = '" + transactClientNameTblValue[1] + "' AND LOANID = '" + transactClientNameTblValue[2] + "'";
            }
            console.log('Final INDIVIDUAL filter:', filter);
            LoadAccountDetails(filter,transactTypeData);
        }
    }
});

function LoadAccountDetails(filter,transtype){
    console.log('LoadAccountDetails called with:', {filter: filter, transtype: transtype});
    
    // Validate filter format
    if (!filter || filter.trim() === '') {
        console.error('Empty filter provided to LoadAccountDetails');
        alert('Invalid client selection. Please try selecting the client again.');
        return;
    }
    
    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"LoadAccountDetails", filter:filter, transtype:transtype},
        dataType:"JSON",
        timeout: 10000, // 10 second timeout
        beforeSend:function(){
            console.log('LoadAccountDetails: Clearing previous data...');
            ClearPaymentTable();
            ClearPrimaryAccountDetails();
        },
        success:function(response){
            console.log('LoadAccountDetails response:', response);
            
            try {
                // Check if response indicates an error
                if (response && response.STATUS === "ERROR") {
                    console.error('Backend error:', response.MESSAGE);
                    alert('Error loading account details: ' + response.MESSAGE);
                    return;
                }
                
                // Check for ACCTDETAILS in response
                if (response && response.ACCTDETAILS && response.ACCTDETAILS.length > 0) {
                    console.log('Found', response.ACCTDETAILS.length, 'account details');
                    
                    if (transtype == "INDIVIDUAL" || transtype == "WRITEOFF") {
                        var detail = response.ACCTDETAILS[0];
                        console.log('Populating Primary Details with:', detail);
                        
                        $("#productNamePD").val(detail["PRODUCT"] || 'Not Specified');
                        $("#poPD").val(detail["PO"] || 'Not Assigned');
                        $("#fundPD").val(detail["FUND"] || 'General Fund');
                        $("#modePD").val(detail["MODE"] || 'Monthly');
                        $("#dateReleasePD").val(detail["DateRelease"] || 'Not Set');
                        $("#loanAD").val(formatAmtVal(detail["LOANAMOUNT"] || 0));
                        $("#balanceAD").val(formatAmtVal(detail["Balance"] || 0));
                        $("#arrearsAD").val(formatAmtVal(detail["TotalArrears"] || 0));
                        
                        // Load depository bank based on fund
                        if (detail["FUND"]) {
                            console.log('Loading depository bank for fund:', detail["FUND"]);
                            LoadDepositoryBank(detail["FUND"]);
                        }
                    } else {
                        // For CENTER and GROUP transactions, try to get fund from first record
                        var firstDetail = response.ACCTDETAILS[0];
                        if (firstDetail && firstDetail["FUND"]) {
                            console.log('Loading depository bank for CENTER/GROUP fund:', firstDetail["FUND"]);
                            LoadDepositoryBank(firstDetail["FUND"]);
                        }
                    }

                    $.each(response.ACCTDETAILS,function(key,value){
                        console.log('Processing payment row:', key, value);
                        
                        var balance = parseFloat(value["Balance"]) || 0;
                        var interestDue = parseFloat(value["InterestDueAsOf"]) || 0;
                        var penaltyDue = parseFloat(value["PenaltyDue"]) || 0;
                        var loanAmount = parseFloat(value["LOANAMOUNT"]) || 0;
                        var remainingBalance = parseFloat(value["Balance"]) || 0; // This is the actual remaining balance

                        var total = balance + interestDue + penaltyDue;
                        
                        // Determine loan status
                        var isPaid = remainingBalance <= 0;
                        var statusText = isPaid ? 'PAID' : 'OUTSTANDING';
                        var statusClass = isPaid ? 'badge bg-success' : 'badge bg-warning';
                        var statusIcon = isPaid ? 'fa-check-circle' : 'fa-clock';
                        
                        console.log('Payment amounts:', {
                            balance: balance,
                            interestDue: interestDue,
                            penaltyDue: penaltyDue,
                            remainingBalance: remainingBalance,
                            loanAmount: loanAmount,
                            total: total,
                            status: statusText
                        });
                        
                        $("#paymentList").append(`
                            <tr class="payment-row clickable-row ${isPaid ? 'loan-paid' : 'loan-outstanding'}" 
                                data-client-no="${value["ClientNo"] || ''}" 
                                data-loan-id="${value["LoanID"] || ''}" 
                                data-fund="${value["FUND"] || ''}"
                                data-client-name="${value["FULLNAME"] || 'Unknown Client'}"
                                data-principal="${balance}"
                                data-interest="${interestDue}"
                                data-penalty="${penaltyDue}"
                                data-balance="${remainingBalance}"
                                data-total="${total}"
                                data-loan-amount="${loanAmount}"
                                data-interest-rate="${value["InterestRate"] || 0}"
                                data-term="${value["Term"] || 0}"
                                data-is-paid="${isPaid}"
                                style="cursor: pointer;">
                                <td>${value["FULLNAME"] || 'Unknown Client'}</td>
                                <td class="text-end">${formatAmtVal(balance)}</td>
                                <td class="text-end">${formatAmtVal(interestDue)}</td>
                                <td class="text-end">${formatAmtVal(penaltyDue)}</td>
                                <td class="text-end fw-bold ${remainingBalance <= 0 ? 'text-success' : 'text-danger'}">${formatAmtVal(remainingBalance)}</td>
                                <td class="text-end">${formatAmtVal(total)}</td>
                            </tr>
                        `);
                    });

                    InitPaymentTbl();
                    
                    // Calculate and display totals in footer
                    calculateAndDisplayTotals();
                    
                    // Adjust table size after content is loaded
                    setTimeout(function() {
                        adjustTableSize();
                    }, 100);
                    
                    console.log('LoadAccountDetails completed successfully');
                    
                    // Add click handlers for payment rows after table is populated
                    addPaymentRowClickHandlers();
                } else {
                    console.warn('No account details found in response');
                    console.log('Full response structure:', {
                        response: response,
                        hasACCTDETAILS: response ? 'ACCTDETAILS' in response : 'No response',
                        ACCTDETAILSLength: response && response.ACCTDETAILS ? response.ACCTDETAILS.length : 'N/A',
                        ACCTDETAILSContent: response ? response.ACCTDETAILS : 'N/A'
                    });
                    
                    // More detailed error message based on the actual issue
                    if (!response) {
                        alert('No response received from server. Please check your connection and try again.');
                    } else if (!response.ACCTDETAILS) {
                        alert('Server response missing account details. Filter used: ' + filter);
                    } else if (response.ACCTDETAILS.length === 0) {
                        alert('No loan records found for the selected client. The client may not have any outstanding loans or the loan may already be fully paid.');
                    } else {
                        alert('Unexpected response format. Please contact support.');
                    }
                }
            } catch (error) {
                console.error('Error processing LoadAccountDetails response:', error);
                console.error('Response that caused error:', response);
                alert('Error processing loan details: ' + error.message + '\n\nPlease check the browser console for more details.');
            }
        },
        error: function(xhr, status, error) {
            console.error('LoadAccountDetails AJAX failed:', {
                status: status,
                error: error,
                response: xhr.responseText,
                filter: filter,
                transtype: transtype
            });
            
            // Try to parse error response for more details
            var errorMessage = 'Failed to load account details.';
            try {
                var errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse && errorResponse.MESSAGE) {
                    errorMessage = errorResponse.MESSAGE;
                }
            } catch (e) {
                // If response is not JSON, use the raw response text
                if (xhr.responseText && xhr.responseText.length > 0) {
                    errorMessage += ' Server response: ' + xhr.responseText.substring(0, 200);
                }
            }
            
            alert(errorMessage + '\n\nFilter: ' + filter + '\nTransaction Type: ' + transtype);
        }
    })
}

// Add click handlers for payment table rows
function addPaymentRowClickHandlers() {
    console.log('Adding payment row double-click handlers...');
    
    // Remove existing handlers to prevent duplicates
    $('#paymentTbl tbody').off('click', '.payment-row');
    $('#paymentTbl tbody').off('dblclick', '.payment-row');
    
    // Add single click handler for row selection (visual feedback only)
    $('#paymentTbl tbody').on('click', '.payment-row', function(e) {
        e.preventDefault();
        
        // Remove selected class from all rows
        $('#paymentTbl tbody .payment-row').removeClass('selected');
        
        // Add selected class to clicked row
        $(this).addClass('selected');
        
        console.log('Row selected (single click)');
    });
    
    // Add double-click handler for edit mode toggle
    $('#paymentTbl tbody').on('dblclick', '.payment-row', function(e) {
        e.preventDefault();
        
        // Get data from the row
        var rowData = {
            clientNo: $(this).data('client-no'),
            loanId: $(this).data('loan-id'),
            fund: $(this).data('fund'),
            clientName: $(this).data('client-name'),
            principal: $(this).data('principal'),
            interest: $(this).data('interest'),
            penalty: $(this).data('penalty'),
            balance: $(this).data('balance'),
            total: $(this).data('total'),
            loanAmount: $(this).data('loan-amount'),
            interestRate: $(this).data('interest-rate'),
            term: $(this).data('term'),
            isPaid: $(this).data('is-paid')
        };
        
        console.log('Payment row double-clicked:', rowData);
        
        // Check if this row is already in edit mode
        var isCurrentlyEditing = window.currentEditingRow && 
                                window.currentEditingRow.loanId === rowData.loanId;
        
        if (isCurrentlyEditing) {
            // Second double-click - cancel edit mode
            cancelEditMode();
            console.log('Edit mode cancelled');
        } else {
            // First double-click - enter edit mode
            enterEditMode(rowData);
            console.log('Edit mode activated for:', rowData.clientName);
        }
    });
    
    console.log('Payment row double-click handlers added successfully');
}

function enterEditMode(rowData) {
    try {
        // Check if loan is fully paid
        if (rowData.isPaid) {
            alert('This loan is already fully paid and cannot be edited.');
            console.log('Edit mode blocked: Loan is fully paid');
            return;
        }
        
        // Store the row data
        window.currentSelectedRow = rowData;
        window.currentEditingRow = rowData;
        
        // Store original values for reset functionality
        window.originalEditValues = {
            payment: '0.00',
            principal: formatAmtVal(rowData.principal),
            interest: formatAmtVal(rowData.interest),
            penalty: formatAmtVal(rowData.penalty),
            total: formatAmtVal(rowData.principal + rowData.interest + rowData.penalty)
        };
        
        // Enable edit amount fields - start with zero payment amount
        $('#edit-payment').prop('disabled', false).val('0.00');
        $('#edit-principal').prop('disabled', false).val(formatAmtVal(rowData.principal));
        $('#edit-interest').prop('disabled', true).val(formatAmtVal(rowData.interest)); // Interest always disabled
        $('#edit-penalty').prop('disabled', true).val(formatAmtVal(rowData.penalty)); // Penalty disabled by default
        
        // Calculate and display the total based on current field values
        RecomputeAmountTotalsWithAutoInterest(false); // Don't auto-calculate on initial load
        
        // Validate Done button state (should be enabled since payment starts at 0)
        validateDoneButton();

        // Enable edit control buttons
        $('#btnFullPayment').prop('disabled', false);
        $('#btnWaivePenaltyEdit').prop('disabled', false);
        $('#btnReset').prop('disabled', false);
        $('#btnDone').prop('disabled', false);
        $('#btnCancel').prop('disabled', false);
        
        console.log('Edit control buttons enabled:');
        console.log('- Full Payment button disabled:', $('#btnFullPayment').prop('disabled'));
        console.log('- Waive Penalty Edit button disabled:', $('#btnWaivePenaltyEdit').prop('disabled'));
        console.log('- Reset button disabled:', $('#btnReset').prop('disabled'));
        console.log('- Done button disabled:', $('#btnDone').prop('disabled'));
        console.log('- Cancel button disabled:', $('#btnCancel').prop('disabled'));
        
        // Add visual indicator that row is in edit mode
        $('#paymentTbl tbody .payment-row').removeClass('editing');
        $(`#paymentTbl tbody .payment-row[data-loan-id="${rowData.loanId}"]`).addClass('editing');
        
        console.log('Edit mode activated - payment starts at 0.00:');
        console.log('- Principal:', formatAmtVal(rowData.principal));
        console.log('- Interest:', formatAmtVal(rowData.interest));
        console.log('- Penalty:', formatAmtVal(rowData.penalty));
        console.log('- Balance:', formatAmtVal(rowData.balance));
        console.log('- Available Total:', formatAmtVal(rowData.total));
        console.log('- Loan Amount:', formatAmtVal(rowData.loanAmount));
        console.log('- Status:', rowData.isPaid ? 'PAID' : 'OUTSTANDING');
        console.log('- Original values stored for reset:', window.originalEditValues);
        
        // Test Full Payment button functionality
        setTimeout(function() {
            console.log('Testing Full Payment button after 1 second...');
            console.log('Full Payment button exists:', $('#btnFullPayment').length > 0);
            console.log('Full Payment button disabled:', $('#btnFullPayment').prop('disabled'));
            console.log('Full Payment button visible:', $('#btnFullPayment').is(':visible'));
        }, 1000);
        
    } catch (error) {
        console.error('Error entering edit mode:', error);
    }
}

function cancelEditMode() {
    try {
        // Clear and disable edit amount fields
        $('#edit-payment').prop('disabled', true).val('');
        $('#edit-principal').prop('disabled', true).val('');
        $('#edit-interest').prop('disabled', true).val('');
        $('#edit-penalty').prop('disabled', true).val('');
        $('#edit-total').val('');

        // Disable all edit control buttons
        $('#btnFullPayment').prop('disabled', true);
        $('#btnWaivePenaltyEdit').prop('disabled', true);
        $('#btnReset').prop('disabled', true);
        $('#btnDone').prop('disabled', true);
        $('#btnCancel').prop('disabled', true);
        
        // Remove editing visual indicator
        $('#paymentTbl tbody .payment-row').removeClass('editing');
        
        // Clear stored editing data but keep selected row
        window.currentEditingRow = null;
        window.originalEditValues = null; // Clear stored original values
        
        console.log('Edit mode cancelled, fields cleared');
        
    } catch (error) {
        console.error('Error cancelling edit mode:', error);
    }
}

// Alias function for DataTable drawCallback compatibility
function updatePaymentFooterTotals() {
    calculateAndDisplayTotals();
}

function calculateAndDisplayTotals() {
    try {
        var totalPrincipal = 0;
        var totalInterest = 0;
        var totalPenalty = 0;
        var totalBalance = 0;
        var totalAmount = 0;
        var paidCount = 0;
        var unpaidCount = 0;
        
        // Calculate totals from all payment rows
        $('#paymentTbl tbody .payment-row').each(function() {
            var principal = parseFloat($(this).data('principal')) || 0;
            var interest = parseFloat($(this).data('interest')) || 0;
            var penalty = parseFloat($(this).data('penalty')) || 0;
            var balance = parseFloat($(this).data('balance')) || 0;
            var total = parseFloat($(this).data('total')) || 0;
            var isPaid = $(this).data('is-paid');
            
            totalPrincipal += principal;
            totalInterest += interest;
            totalPenalty += penalty;
            totalBalance += balance;
            totalAmount += total;
            
            if (isPaid) {
                paidCount++;
            } else {
                unpaidCount++;
            }
        });
        
        // Update footer totals
        $('#totalPrincipal').text('₱' + formatAmtVal(totalPrincipal));
        $('#totalInterest').text('₱' + formatAmtVal(totalInterest));
        $('#totalPenalty').text('₱' + formatAmtVal(totalPenalty));
        $('#totalBalance').text('₱' + formatAmtVal(totalBalance));
        $('#totalAmount').text('₱' + formatAmtVal(totalAmount));
        $('#paidCount').text(paidCount);
        $('#unpaidCount').text(unpaidCount);
        
        console.log('Totals calculated:', {
            principal: totalPrincipal,
            interest: totalInterest,
            penalty: totalPenalty,
            balance: totalBalance,
            total: totalAmount,
            paid: paidCount,
            unpaid: unpaidCount
        });
        
    } catch (error) {
        console.error('Error calculating totals:', error);
    }
}

function adjustTableSize() {
    try {
        var rowCount = $('#paymentTbl tbody .payment-row').length;
        
        if (rowCount === 0) {
            // Empty state - minimal height
            $('.table-container-fixed').css('height', '150px');
            return;
        }
        
        // Calculate height based on actual content
        var tableHeight = $('#paymentTbl')[0].offsetHeight;
        var footerHeight = 50;
        var padding = 20;
        
        // Set container height based on actual table content
        var containerHeight = Math.min(Math.max(tableHeight + footerHeight + padding, 150), 400);
        $('.table-container-fixed').css('height', containerHeight + 'px');
        
        console.log('Table size adjusted:', {
            rowCount: rowCount,
            tableHeight: tableHeight,
            containerHeight: containerHeight
        });
        
    } catch (error) {
        console.error('Error adjusting table size:', error);
    }
}

// Function to populate edit amounts section
// populateEditAmounts function removed - now using double-click functionality

// Function to enable only the Edit button when a row is selected
// Old button-based functions removed - now using double-click functionality

// Function to enable all edit buttons and fields when Edit is clicked
function enableAllEditControls() {
    // Enable control buttons
    $('#btnFullPayment').prop('disabled', false);
    $('#btnWaivePenaltyEdit').prop('disabled', false);
    $('#btnReset').prop('disabled', false);
    $('#btnDone').prop('disabled', false);
    $('#btnCancel').prop('disabled', false);
    
    // Enable edit amount fields with restrictions
    $('#edit-payment').prop('disabled', false);
    $('#edit-principal').prop('disabled', false);
    $('#edit-interest').prop('disabled', true); // Interest always disabled
    $('#edit-penalty').prop('disabled', true); // Penalty disabled by default
    
    console.log('Edit controls enabled with field restrictions');
}



function ClearPrimaryAccountDetails(){
    $("#productNamePD").val("");
    $("#poPD").val("");
    $("#fundPD").val("");
    $("#modePD").val("");
    $("#dateReleasePD").val("");
    $("#loanAD").val("");
    $("#balanceAD").val("");
    $("#arrearsAD").val("");
}

$('#paymentTbl tbody').on('click', 'tr',function(e){
    console.log('Payment table row clicked');
    
    try {
        if(paymentTbl && paymentTbl.rows().count() !== 0){
            let classList = e.currentTarget.classList;
            console.log('Row classes:', classList);
            
            if (classList.contains('selected')) {
                console.log('Deselecting row');
                classList.remove('selected');

                ClearPrimaryAccountDetails();
                ClearEditAmounts();
                $("#depositoryBank").empty();
            } else {
                console.log('Selecting new row');
                
                // Remove selection from other rows
                paymentTbl.rows('.selected').nodes().each((row) => {
                    row.classList.remove('selected');
                });
                
                // Select this row
                classList.add('selected');

                // Get row data
                paymentTblValue = $('#paymentTbl').DataTable().row(this).data();
                console.log('Selected row data:', paymentTblValue);
                
                if (paymentTblValue && paymentTblValue.length >= 7) {
                    var filter = "CLIENTNO = '" + paymentTblValue[5] + "' AND LOANID = '" + paymentTblValue[6] + "'";
                    console.log('Loading payment details with filter:', filter);
                    
                    LoadPaymentDetails(filter, transactTypeData);
                    LoadDepositoryBank(paymentTblValue[7]);
                    
                    $("#editAmountBtn").attr("disabled", false);
                    $("#clearEditAmountBtn").attr("disabled", false);
                    $("#clearAllEditAmountBtn").attr("disabled", false);
                } else {
                    console.error('Invalid row data structure:', paymentTblValue);
                    alert('Invalid row data. Please try again.');
                }
            }
        } else {
            console.warn('Payment table not ready or empty');
        }
    } catch (error) {
        console.error('Error in payment table row click:', error);
        alert('Error selecting payment row: ' + error.message);
    }
});

function LoadPaymentDetails(filter,transtype){
    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"LoadAccountDetails", filter:filter, transtype:transtype},
        dataType:"JSON",
        beforeSend:function(){
            ClearPrimaryAccountDetails();
            ClearEditAmounts();
        },
        success:function(response){
            var detail = response.ACCTDETAILS[0];
            $("#productNamePD").val(detail["PRODUCT"]);
            $("#poPD").val(detail["PO"]);
            $("#fundPD").val(detail["FUND"]);
            $("#modePD").val(detail["MODE"]);
            $("#dateReleasePD").val(detail["DateRelease"]);
            $("#loanAD").val(detail["LOANAMOUNT"]);
            $("#balanceAD").val(detail["Balance"]);
            $("#arrearsAD").val(detail["TotalArrears"]);

            $("#edit-principal").val(formatAmtVal(detail["AmountDueAsOf"]));
            $("#edit-interest").val(formatAmtVal(detail["InterestDueAsOf"]));
            $("#edit-penalty").val(formatAmtVal(detail["PenaltyDue"]));
            // $("#edit-total").val(formatAmtVal(ListFnlTotal));

            RecomputeAmountTotalsWithAutoInterest(false); // Don't auto-calculate during distribution
        }, 
    })
}

function LoadDepositoryBank(Fund){
    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"LoadDepositoryBank", Fund:Fund},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#depositoryBank").empty().append("<option value='' selected disabled> Select Bank</option>");
            $.each(response.BANK, function(key,value){
                $("#depositoryBank").append("<option value='"+value["BANK"]+"'>"+value["BANK"]+"</option>");
            })
        }, 
    })
}

function ClearEditAmounts(){
    $("#editAmountBtn").attr("disabled",true);
    $("#clearEditAmountBtn").attr("disabled",true);
    $("#clearAllEditAmountBtn").attr("disabled",true);

    $("#edit-payment").val("");
    $("#edit-payment").prop("disabled", true);
    $("#edit-principal").val("");
    $("#edit-principal").prop("disabled", true);
    $("#edit-interest").val("");
    $("#edit-interest").prop("disabled", true);
    $("#edit-penalty").val("");
    $("#edit-penalty").prop("disabled", true);
    $("#edit-total").val("");

    $("#btnFullPayment").prop("disabled", true);
    $("#btnWaivePenaltyEdit").prop("disabled", true);
    // Removed btnEditPayment reference
    $("#btnReset").prop("disabled", true);
    $("#btnDone").prop("disabled", true);
    $("#btnCancel").prop("disabled", true);
}

// Old toggle functions removed - now using double-click functionality

function isNumeric(value) {
    return !isNaN(value) && isFinite(value);
}

function DistributeAmounts(){
    var balance = parseFloat($("#balanceAD").val().replace(/,/g, '')) || 0;
    var vAmount = parseFloat($("#edit-payment").val().replace(/,/g, '')) || 0;
    var principal = parseFloat($("#edit-principal").val().replace(/,/g, '')) || 0;
    var interest = parseFloat($("#edit-interest").val().replace(/,/g, '')) || 0;
    var penalty = parseFloat($("#edit-penalty").val().replace(/,/g, '')) || 0;

    var txtPrincipal = 0;
    var txtInterest = 0;
    var txtPenalty = 0;

    while (vAmount > 0) {
        if (vAmount >= penalty) {
            vAmount -= penalty;
            txtPenalty = penalty;

            if (vAmount >= interest) {
                vAmount -= interest;
                txtInterest = interest;

                if (vAmount >= principal) {
                    vAmount -= principal;
                    txtPrincipal = principal;

                    if (vAmount > 0) {
                        txtPrincipal += vAmount;
                        
                        if (txtPrincipal > balance) {
                            txtPrincipal = balance;
                        }
                        vAmount = 0;
                    }
                } else {
                    txtPrincipal = vAmount;
                    vAmount = 0;
                }
            } else {
                txtInterest = vAmount;
                vAmount = 0;
            }
        } else {
            txtPenalty = vAmount;
            vAmount = 0;
        }
    }

    $("#edit-principal").val(formatAmtVal(txtPrincipal));
    $("#edit-interest").val(formatAmtVal(txtInterest));
    $("#edit-penalty").val(formatAmtVal(txtPenalty));

    RecomputeAmountTotalsWithAutoInterest(false);
    validateDoneButton();
}

function RecomputeAmountTotals(){
    var principal = parseFloat($("#edit-principal").val().replace(/,/g, '')) || 0;
    var interest = parseFloat($("#edit-interest").val().replace(/,/g, '')) || 0;
    var penalty = parseFloat($("#edit-penalty").val().replace(/,/g, '')) || 0;
    var total = 0;

    total = principal + interest + penalty;
    $("#edit-total").val(formatAmtVal(total));

    console.log('RecomputeAmountTotals called:', {
        principal: principal,
        interest: interest,
        penalty: penalty,
        total: total,
        formatted: formatAmtVal(total)
    });

    // Check if Done button should be enabled/disabled
    validateDoneButton();
}

// Function to automatically calculate interest based on principal change
// Uses the same formula as loan transactions for consistency
function calculateInterestFromPrincipal(newPrincipal) {
    try {
        // Check if we have the necessary loan data
        if (!window.currentEditingRow) {
            console.log('No editing row data available for interest calculation');
            return null;
        }

        var loanData = window.currentEditingRow;
        var interestRate = parseFloat(loanData.interestRate || loanData.INTERESTRATE) || 0;
        var term = parseFloat(loanData.term || loanData.TERM) || 0;

        console.log('Interest calculation data check:', {
            loanData: loanData,
            interestRate: interestRate,
            term: term,
            newPrincipal: newPrincipal
        });

        if (interestRate <= 0 || term <= 0) {
            console.log('Invalid interest rate or term for calculation:', {
                interestRate: interestRate,
                term: term
            });
            return null;
        }

        // Use the same formula as loan transactions:
        // interest = principal * (rate / 100) * term
        var calculatedInterest = newPrincipal * (interestRate / 100) * term;

        // Apply the same rounding logic as loan transactions
        if ((calculatedInterest - Math.floor(calculatedInterest)) >= 0.5) {
            calculatedInterest = Math.ceil(calculatedInterest);
        } else {
            calculatedInterest = Math.floor(calculatedInterest);
        }

        console.log('Interest calculation:', {
            principal: newPrincipal,
            interestRate: interestRate,
            term: term,
            calculatedInterest: calculatedInterest,
            formula: 'principal * (rate / 100) * term'
        });

        return calculatedInterest;

    } catch (error) {
        console.error('Error calculating interest:', error);
        return null;
    }
}

// Enhanced RecomputeAmountTotals with optional auto-interest calculation
function RecomputeAmountTotalsWithAutoInterest(autoCalculateInterest = false) {
    var principal = parseFloat($("#edit-principal").val().replace(/,/g, '')) || 0;
    var interest = parseFloat($("#edit-interest").val().replace(/,/g, '')) || 0;
    var penalty = parseFloat($("#edit-penalty").val().replace(/,/g, '')) || 0;

    // Auto-calculate interest if requested and conditions are met
    if (autoCalculateInterest && principal > 0) {
        var calculatedInterest = calculateInterestFromPrincipal(principal);
        if (calculatedInterest !== null) {
            interest = calculatedInterest;
            $("#edit-interest").val(formatAmtVal(interest));
            
            // Add visual feedback for auto-calculation
            $("#edit-interest").addClass('auto-calculated');
            setTimeout(() => {
                $("#edit-interest").removeClass('auto-calculated');
            }, 1000);
            
            console.log('Auto-calculated interest:', interest);
        }
    }

    var total = principal + interest + penalty;
    $("#edit-total").val(formatAmtVal(total));

    console.log('RecomputeAmountTotalsWithAutoInterest called:', {
        principal: principal,
        interest: interest,
        penalty: penalty,
        total: total,
        autoCalculateInterest: autoCalculateInterest,
        formatted: formatAmtVal(total)
    });

    // Check if Done button should be enabled/disabled
    validateDoneButton();
}

// Function to validate if Add Payment button should be enabled
function validateDoneButton() {
    var paymentAmount = parseFloat($("#edit-payment").val().replace(/,/g, '')) || 0;
    
    if (paymentAmount > 0) {
        // Enable Add Payment button if there's a payment amount
        $("#btnDone").prop('disabled', false).addClass('btn-success').removeClass('btn-secondary');
        console.log('Add Payment button enabled - payment amount:', paymentAmount);
    } else {
        // Disable Add Payment button if no payment amount
        $("#btnDone").prop('disabled', true).addClass('btn-secondary').removeClass('btn-success');
        console.log('Add Payment button disabled - no payment amount');
    }
}

function SetPaymentType (type){

    if (type == "CASH") {
        $("#checkdate").prop("disabled", true);
        $("#checkNo").prop("disabled", true);
        $("#bankname").prop("disabled", true);
        $("#bankbranch").prop("disabled", true);
    } else {
        $("#checkdate").prop("disabled", false);
        $("#checkNo").prop("disabled", false);
        $("#bankname").prop("disabled", false);
        $("#bankbranch").prop("disabled", false);
    }

}

function LoadORSeries(SeriesName){
    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"LoadORSeries", SeriesName:SeriesName},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            var seriesData = response.SERIESDATA;
            $("#ORNo").val(seriesData["NEXTOR"]);
            $("#ORLeft").val(seriesData["ORLEFT"]);
        }, 
    })
}

function SetOR(){
    var paymentType = $('#paymentType').val();

    var checkDate = $("#checkdate").val();
    var checkNo = $("#checkNo").val();
    var bankName = $("#bankname").val();
    var bankBranch = $("#bankbranch").val();

    var orFrom = $('#orFrom').val();
    var orno = $('#ORNo').val();
    var depositorybank = $('#depositoryBank').val();

    if(paymentType == "" || paymentType == null){
        Swal.fire({
            icon: 'warning',
            title: 'Select Payment Type.',
        })
        return;
    }
    
    if (paymentType == "CHECK") {
        if (checkDate == "" || checkDate == null) {
            Swal.fire({
                icon: 'warning',
                title: 'Select Check Date.',
            })
            return;
        }

        if (checkNo == "" || checkNo == null) {
            Swal.fire({
                icon: 'warning',
                title: 'Enter Check No.',
            })
            return;
        }

        if (bankName == "" || bankName == null) {
            Swal.fire({
                icon: 'warning',
                title: 'Enter Bank Name.',
            })
            return;
        }

        if (bankBranch == "" || bankBranch == null) {
            Swal.fire({
                icon: 'warning',
                title: 'Enter Bank Branch',
            })
            return;
        }
    }

    if(orno == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Select OR From.',
        })
        return;
    }

    if(depositorybank == "" || depositorybank == null){   
        Swal.fire({
            icon: 'warning',
            title: 'Select Depository Bank.',
        })
        return;
    }

    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"LoadClientType"},
        dataType:"JSON",
        beforeSend:function(){
            console.log('Loading client types...');
        },
        success:function(response){
            console.log('LoadClientType response:', response);
            
            $("#clientType").empty().append("<option value='' selected disabled> Select Client Type</option> <option value='OTHERS'> OTHERS</option> <option value='CUSTOMER'> CUSTOMER</option>");
            
            if (response && response.CLIENTTYPE && Array.isArray(response.CLIENTTYPE)) {
                $.each(response.CLIENTTYPE, function(key,value){
                    console.log('Adding client type:', value.TYPE);
                    $("#clientType").append("<option value='"+value["TYPE"]+"'>"+value["TYPE"]+"</option>");
                });
                console.log('Client types loaded successfully');
            } else {
                console.warn('No client types in response or invalid format:', response);
            }

            $('#otherDetailsMDL').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('LoadClientType AJAX failed:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            // Still show the modal with default options
            $("#clientType").empty().append("<option value='' selected disabled> Select Client Type</option> <option value='OTHERS'> OTHERS</option> <option value='CUSTOMER'> CUSTOMER</option>");
            $('#otherDetailsMDL').modal('show');
            
            // Show user-friendly error
            alert('Could not load client types from database. Using default options.');
        }
    })
}

function GetClientName(clientType){
    if (clientType == "OTHER"){
        $('#clientNameSelDiv').hide();
        $('#clientName').val("");
        $('#clientNameTxtDiv').show();
        $('#clientNameTxt').val("");
        $('#clientAddress').prop('disabled', false);
        $('#clientAddress').val("");
        $('#clientTIN').prop('disabled', false);
        $('#clientTIN').val("");
    } else {
        $('#clientNameSelDiv').show();
        $('#clientName').val("");
        $('#clientNameTxtDiv').hide();
        $('#clientNameTxt').val("");
        $('#clientAddress').prop('disabled', true);
        $('#clientAddress').val("");
        $('#clientTIN').prop('disabled', true);
        $('#clientTIN').val("");

        var transactType = $("#TransactionType").val();

        $.ajax({
            url:baseUrl + "/routes/cashier/loanspayment.route.php",
            type:"POST",
            data:{action:"LoadClientName", clientType:clientType,transactType:transactType},
            dataType:"JSON",
            beforeSend:function(){
            },
            success:function(response){
                $("#clientName").empty().append("<option value='' selected disabled> Select Client Name</option>");
                $.each(response.CLIENTNAMELIST, function(key,value){
                    $("#clientName").append("<option value='"+value["NAME"]+"'>"+value["NAME"]+"</option>");
                })
            }, 
        })
    }    
}

function GetClientInfo(name){
    var clientType = $("#clientType").val();
    var transactType = $("#TransactionType").val();

    $.ajax({
        url:baseUrl + "/routes/cashier/loanspayment.route.php",
        type:"POST",
        data:{action:"GetClientInfo", clientType:clientType,transactType:transactType,name:name},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            var data = response.CLIENTINFOLIST[0];
            if (clientType == "CUSTOMER") {
                 $("#clientAddress").val(data["FULLADDRESS"]);
                 $("#clientTIN").val("-");
            } else {
                $("#clientAddress").val(data["FullAddress"]);
                $("#clientTIN").val(data["tin_no"]);
            }
        }, 
    })
}

function SaveTransaction(){
    var clientType = $("#clientType").val();
    var clientNameSel = $("#clientName").val();
    var clientNameTxt = $("#clientNameTxt").val().trim();
    var clientAddress = $("#clientAddress").val().trim();
    var clientTIN = $("#clientTIN").val().trim();
    var particulars = $("#particulars").val().trim();
    
    var paymentType = $('#paymentType').val();
    var checkDate = $("#checkdate").val();
    var checkNo = $("#checkNo").val();
    var bankName = $("#bankname").val();
    var bankBranch = $("#bankbranch").val();
    var orFrom = $('#orFrom').val();
    var orno = $('#ORNo').val();
    var depositorybank = $('#depositoryBank').val();

    var clientNameFNL = "";

    if (clientType == "OTHER") {
        clientNameFNL = clientNameTxt;
    } else {
        clientNameFNL = clientNameSel;
    }

    // Validations
    if (clientType == "" || clientType == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Select a Client Type.',
        })
        return;
    }

    if (clientType == "OTHER") {
        if (clientNameTxt == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Enter Client Name.',
            })
            $("#clientNameTxt").focus();
            return;
        }
    } else {
        if (clientNameSel == "" || clientNameSel == null){
            Swal.fire({
                icon: 'warning',
                title: 'Select Client Name.',
            })
            return;
        }
    }

    if (clientAddress == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Client Address cannot be empty.',
        })
        $("#clientAddress").focus();
        return;
    }

    if (clientTIN == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Client TIN cannot be empty.',
        })
        $("#clientTIN").focus();
        return;
    }

    if (particulars == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Enter particulars.',
        })
        $("#particulars").focus();
        return;
    }

    // FIXED: Get payment data from table rows that have payments (not form fields)
    var paymentData = [];
    
    // Look for rows that have payment data (marked with has-payment class)
    $('#paymentTbl tbody tr.has-payment').each(function() {
        var $row = $(this);
        
        // Get data from the row attributes (set by Done button)
        var clientName = $row.find('td:eq(0)').text().trim();
        var paymentAmount = parseFloat($row.attr('data-payment-amount')) || 0;  // ACTUAL PAYMENT AMOUNT
        var principal = parseFloat($row.attr('data-principal')) || 0;
        var interest = parseFloat($row.attr('data-interest')) || 0;
        var penalty = parseFloat($row.attr('data-penalty')) || 0;
        var clientNo = $row.data('client-no') || '';
        var loanId = $row.data('loan-id') || '';
        var fund = $row.data('fund') || 'GENERAL';
        
        // EMERGENCY DEBUG: Log row data collection
        console.log("=== ROW DATA COLLECTION DEBUG ===");
        console.log("Row found:", $row);
        console.log("Client Name:", clientName);
        console.log("Payment Amount (from data-payment-amount):", paymentAmount);
        console.log("Principal (from data-principal):", principal);
        console.log("Interest (from data-interest):", interest);
        console.log("Penalty (from data-penalty):", penalty);
        console.log("Client No:", clientNo);
        console.log("Loan ID:", loanId);
        console.log("Fund:", fund);
        console.log("=== END ROW DEBUG ===");
        
        if (clientNo && loanId && paymentAmount > 0) {
            // Array: [clientName, paymentAmount, principal, interest, penalty, clientNo, loanId, fund]
            var paymentRecord = [
                clientName,     // [0]
                paymentAmount,  // [1] - THE ACTUAL PAYMENT AMOUNT (from data-payment-amount)
                principal,      // [2] - Principal breakdown (from data-principal)
                interest,       // [3] - Interest breakdown (from data-interest)
                penalty,        // [4] - Penalty breakdown (from data-penalty)
                clientNo,       // [5]
                loanId,         // [6]
                fund           // [7]
            ];
            
            // EMERGENCY DEBUG: Log the payment record being created
            console.log("=== PAYMENT RECORD DEBUG ===");
            console.log("Payment Record Array:", paymentRecord);
            console.log("Array breakdown:");
            console.log("  [0] clientName:", paymentRecord[0]);
            console.log("  [1] paymentAmount:", paymentRecord[1], "(from data-payment-amount)");
            console.log("  [2] principal:", paymentRecord[2], "(from data-principal)");
            console.log("  [3] interest:", paymentRecord[3], "(from data-interest)");
            console.log("  [4] penalty:", paymentRecord[4], "(from data-penalty)");
            console.log("  [5] clientNo:", paymentRecord[5]);
            console.log("  [6] loanId:", paymentRecord[6]);
            console.log("  [7] fund:", paymentRecord[7]);
            console.log("=== END PAYMENT RECORD DEBUG ===");
            
            paymentData.push(paymentRecord);
        }
    });
    
    // Debug: Log what we're about to send
    console.log('=== FIXED PAYMENT DATA DEBUG ===');
    console.log('Payment data collected from table rows with has-payment class');
    console.log('Payment data to be sent:', paymentData);
    console.log('Payment data length:', paymentData.length);
    console.log('Payment data JSON:', JSON.stringify(paymentData));
    
    if (paymentData.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No payment data to save.',
            text: 'Please enter a payment amount and click "Add Payment" button first.',
        })
        return;
    }

    // Validate that we have the required data
    var firstPayment = paymentData[0];
    if (!firstPayment || firstPayment.length < 8) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid payment data',
            text: 'Missing required payment information. Please try again.',
        });
        console.error('Invalid payment data structure:', firstPayment);
        return;
    }

    // Debug: Log what we're about to send
    console.log('=== PAYMENT DATA DEBUG ===');
    console.log('Payment data to be sent:', paymentData);
    console.log('Payment data length:', paymentData.length);
    console.log('Payment data JSON:', JSON.stringify(paymentData));
    
    // Debug: Check HTML table directly
    console.log('HTML table rows:', $('#paymentTbl tbody tr').length);
    console.log('Payment rows:', $('#paymentTbl tbody .payment-row').length);
    
    // Debug: Show what's in the HTML table
    $('#paymentTbl tbody tr').each(function(index) {
        console.log('Row ' + index + ':', $(this).html());
    });

    // Validate payment data before submission - TEMPORARILY DISABLED FOR DEBUGGING
    // var validation = validatePaymentData(paymentData);
    // if (!validation.valid) {
    //     Swal.fire({
    //         icon: 'error',
    //         title: 'Invalid Payment Data',
    //         text: validation.message,
    //     });
    //     return;
    // }

    // Log payment data for debugging
    console.log('Payment data being submitted:', paymentData);

    Swal.fire({
        title: 'Confirm Transaction',
        text: "Are you sure you want to save this transaction?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, save it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url:baseUrl + "/routes/cashier/loanspayment.route.php",
                type:"POST",
                data:{
                    action:"SaveTransaction", 
                    clientType:clientType,
                    clientName:clientNameFNL,
                    clientAddress:clientAddress,
                    clientTIN:clientTIN,
                    particulars:particulars,
                    paymentType:paymentType,
                    checkDate:checkDate,
                    checkNo:checkNo,
                    bankName:bankName,
                    bankBranch:bankBranch,
                    orFrom:orFrom,
                    orno:orno,
                    orLeft:0, // Add missing orLeft field
                    depositorybank:depositorybank,
                    transactType:transactTypeData,
                    paymentData:JSON.stringify(paymentData)
                },
                dataType:"JSON",
                beforeSend:function(){
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we save your transaction',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success:function(response){
                    console.log('SaveTransaction response received:', response);
                    console.log('Response type:', typeof response);
                    console.log('Response STATUS:', response ? response.STATUS : 'undefined');
                    console.log('Response RECEIPT_DATA:', response ? response.RECEIPT_DATA : 'undefined');
                    
                    // Additional debugging
                    if (response) {
                        console.log('Full response object:', JSON.stringify(response, null, 2));
                    }
                    
                    if(response && response.STATUS == "SUCCESS"){
                        // Validate receipt data before showing success
                        if (response.RECEIPT_DATA && response.RECEIPT_DATA.ORNO) {
                            console.log('Receipt data found, generating receipt directly');
                            
                            // Show brief success message first
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Processed Successfully!',
                                text: 'OR Number: ' + response.RECEIPT_DATA.ORNO,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Generate receipt directly after success message
                                generateReceipt(response.RECEIPT_DATA);
                                $('#otherDetailsMDL').modal('hide');
                                // Don't reload immediately - let user see receipt first
                                setTimeout(() => {
                                    location.reload();
                                }, 5000);
                            });
                            
                        } else {
                            // Success but no receipt data - show detailed debugging
                            console.warn('Transaction saved but no receipt data received:', response);
                            
                            var debugInfo = '';
                            if (response) {
                                debugInfo = '<br><br><strong>Debug Info:</strong><br>';
                                debugInfo += 'STATUS: ' + (response.STATUS || 'undefined') + '<br>';
                                debugInfo += 'MESSAGE: ' + (response.MESSAGE || 'undefined') + '<br>';
                                debugInfo += 'RECEIPT_DATA present: ' + (response.RECEIPT_DATA ? 'YES' : 'NO') + '<br>';
                                if (response.RECEIPT_DATA) {
                                    debugInfo += 'ORNO in receipt: ' + (response.RECEIPT_DATA.ORNO ? 'YES' : 'NO') + '<br>';
                                }
                                debugInfo += 'Full response: <pre>' + JSON.stringify(response, null, 2) + '</pre>';
                            }
                            
                            Swal.fire({
                                icon: 'warning',
                                title: 'Transaction Saved - Receipt Issue',
                                html: 'Payment processed successfully but receipt data is missing.' + debugInfo,
                                showCancelButton: true,
                                confirmButtonText: 'View Sample Receipt',
                                cancelButtonText: 'Close',
                                width: '600px'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    openSampleReceipt();
                                }
                                $('#otherDetailsMDL').modal('hide');
                                location.reload();
                            });
                        }
                    } else {
                        console.error('SaveTransaction failed:', response);
                        
                        var errorMsg = 'Failed to save transaction';
                        if (response && response.MESSAGE) {
                            errorMsg = response.MESSAGE;
                        } else if (!response) {
                            errorMsg = 'No response received from server';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Transaction Failed',
                            html: errorMsg + '<br><br><strong>Debug:</strong><br>' + 
                                  'Response: ' + (response ? JSON.stringify(response) : 'null'),
                            width: '500px'
                        });
                    }
                },
                error:function(xhr, status, error){
                    console.error('SaveTransaction AJAX failed:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    
                    handlePaymentError(xhr, 'SaveTransaction');
                }
            });
        }
    });
}
    //     url:baseUrl + "/routes/cashier/loanspayment.route.php",
    //     type:"POST",
    //     data:{action:"SaveTransaction", clientType:clientType,clientNameFNL:clientNameFNL,clientAddress:clientAddress,clientTIN:clientTIN,particulars:particulars},
    //     dataType:"JSON",
    //     beforeSend:function(){
    //     },
    //     success:function(response){
    //     }, 
    // })



// ================================

function PrintSalesInvoice(){
    if(dataInvTbl.rows().count() === 0){
        // When Data Inv List is empty, this will halt the printing
        Swal.fire({
            icon:'warning',
            title: 'Nothing to print!',
        });
        return;
    } else {
        let Data = dataInvTbl.rows().data().toArray();
        let formdata = new FormData();
        formdata.append("action","PrintSupplierSalesInvoice");
        formdata.append("DATA",JSON.stringify(Data));

        $.ajax({
            url: baseUrl + "/routes/inventorymanagement/incominginventory.route.php",
            type: "POST",
            data:formdata,
            processData:false,
            cache:false,
            contentType:false,
            dataType:"JSON",
            beforeSend: function() {
                console.log('Processing Request...')
            },
            success: function(response) {
                window.open(baseUrl + "/routes/inventorymanagement/incominginventory.route.php?type=PrintSuppRcpt");
                LoadDataInventory();
                $('#printBtn').prop('disabled', true);
            },
        });
    }
}


function formatInput(input) {
    // Get the value from the input field and remove invalid characters
    let cleanValue = input.value.replace(/[^0-9.,-]/g, '');

    // Check for negative values
    if (cleanValue.includes('-')) {
        // Show SweetAlert message
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Negative amounts are not allowed.',
            confirmButtonText: 'OK'
        });

        // Reset the input field
        input.value = '0.00';
        return;
    }

    // Remove commas for numeric processing
    cleanValue = cleanValue.replace(/,/g, '');

    if (cleanValue !== '') {
        // Parse the cleaned value to a float and ensure two decimal places
        let formattedValue = parseFloat(cleanValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        // Set the formatted value back to the input field
        input.value = formattedValue;
    } else {
        input.value = '0.00'; // If empty or invalid, set input to empty
    }
}


// ================================
// Utility Functions
// ================================
function formatAmtVal(value) {
    if (value === null || value === undefined || value === '') return '0.00';
    let num = parseFloat(value);
    if (isNaN(num)) return '0.00';
    return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function formatInput(input) {
    let value = input.value.replace(/,/g, '');
    if (value === '' || isNaN(value)) {
        input.value = '0.00';
        return;
    }
    input.value = formatAmtVal(value);
}

// ================================
// Button Click Handlers
// ================================
$("#btnFullPayment").on('click', function() {
    console.log('Full Payment button clicked');
    
    try {
        // Check if button is disabled
        if ($(this).prop('disabled')) {
            console.log('Full Payment button is disabled, cannot proceed');
            alert('Please select a loan first by double-clicking on a loan row.');
            return;
        }
        
        var principal = parseFloat($("#edit-principal").val()?.replace(/,/g, '') || '0') || 0;
        var interest = parseFloat($("#edit-interest").val()?.replace(/,/g, '') || '0') || 0;
        var penalty = parseFloat($("#edit-penalty").val()?.replace(/,/g, '') || '0') || 0;
        
        console.log('Current amounts for Full Payment:');
        console.log('- Principal:', principal);
        console.log('- Interest:', interest);
        console.log('- Penalty:', penalty);
        
        var total = principal + interest + penalty;
        console.log('Calculated total payment:', total);
        
        if (total <= 0) {
            alert('No amount to pay. Please check the loan details.');
            return;
        }
        
        // Set the payment field to the total amount - DO NOT auto-distribute
        $("#edit-payment").val(formatAmtVal(total));
        console.log('Set payment field to:', formatAmtVal(total));
        
        // Update the Done button state (should be disabled since payment > 0)
        validateDoneButton();
        
        console.log('Full Payment amount set successfully. Use "Distribute" or enter amounts manually.');
        
    } catch (error) {
        console.error('Error in Full Payment button:', error);
        alert('Error calculating full payment amount: ' + error.message);
    }
});

$("#btnDistributePayment").on('click', function() {
    console.log('Distribute Payment button clicked');
    
    try {
        // Check if button is disabled
        if ($(this).prop('disabled')) {
            console.log('Distribute Payment button is disabled, cannot proceed');
            alert('Please select a loan first by double-clicking on a loan row.');
            return;
        }
        
        var paymentAmount = parseFloat($("#edit-payment").val().replace(/,/g, '')) || 0;
        
        if (paymentAmount <= 0) {
            alert('Please enter a payment amount first. You can use "Full Payment" button or enter an amount manually.');
            $("#edit-payment").focus();
            return;
        }
        
        console.log('Distributing payment amount:', paymentAmount);
        
        // Call the distribution function
        DistributeAmounts();
        
        // Clear payment field after distribution to enable Done button
        $("#edit-payment").val('0.00');
        console.log('Cleared payment field to 0.00 after distribution');
        
        validateDoneButton();
        
        console.log('Payment distribution completed successfully');
        
        // Show success feedback
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Payment Distributed',
                text: 'Payment of ₱' + formatAmtVal(paymentAmount) + ' has been distributed to Principal, Interest, and Penalty.',
                timer: 2000,
                showConfirmButton: false
            });
        }
        
    } catch (error) {
        console.error('Error in Distribute Payment button:', error);
        alert('Error distributing payment: ' + error.message);
    }
});

// Waive Penalty button in Edit Amounts section - sets penalty to 0
$("#btnWaivePenaltyEdit").on('click', function() {
    console.log('Waive Penalty Edit button clicked');
    
    // Enable penalty field and set to 0
    $("#edit-penalty").prop('disabled', false).val('0.00');
    
    // Highlight the field to show it was auto-filled
    $('#edit-penalty').addClass('auto-calculated');
    setTimeout(function() {
        $('#edit-penalty').removeClass('auto-calculated');
    }, 2000);
    
    // Recalculate totals
    RecomputeAmountTotalsWithAutoInterest(false);
    
    console.log('Penalty set to 0.00');
});

// OLD WAIVE PENALTY BUTTON REMOVED - Now using late payment warning buttons instead
// The late payment warning has its own "Waive Penalty" button that automatically sets penalty to 0

// Edit Payment button handler removed - now using table's Edit button (editAmountBtn)

$("#btnReset").on('click', function() {
    console.log('Reset button clicked');
    
    try {
        // Check if we have original values stored
        if (!window.originalEditValues) {
            console.error('No original values stored for reset');
            alert('Cannot reset - no original values found. Please select the loan row again.');
            return;
        }
        
        // Restore original values
        $("#edit-payment").val(window.originalEditValues.payment);
        $("#edit-principal").val(window.originalEditValues.principal);
        $("#edit-interest").val(window.originalEditValues.interest);
        $("#edit-penalty").val(window.originalEditValues.penalty);
        
        // Reset field states to original (penalty disabled, interest disabled)
        $("#edit-payment").prop("disabled", false);
        $("#edit-principal").prop("disabled", false);
        $("#edit-interest").prop("disabled", true); // Interest always disabled
        $("#edit-penalty").prop("disabled", true); // Penalty disabled by default
        
        // Recalculate total and validate Done button
        RecomputeAmountTotalsWithAutoInterest(false); // Don't auto-calculate during reset
        validateDoneButton();
        
        console.log('Reset completed - restored to original values:', window.originalEditValues);
        
    } catch (error) {
        console.error('Error in Reset button:', error);
        alert('Error resetting values');
    }
});

$("#btnDone").on('click', function() {
    console.log('Add Payment button clicked');
    
    try {
        // Get the payment amount from the PAYMENT field
        var paymentAmount = parseFloat($("#edit-payment").val().replace(/,/g, '')) || 0;
        
        // Get the total owed (Principal + Interest + Penalty)
        var principal = parseFloat($("#edit-principal").val().replace(/,/g, '')) || 0;
        var interest = parseFloat($("#edit-interest").val().replace(/,/g, '')) || 0;
        var penalty = parseFloat($("#edit-penalty").val().replace(/,/g, '')) || 0;
        var total = principal + interest + penalty;
        
        // Payment amount must be > 0
        if (paymentAmount <= 0) {
            alert('Please enter a payment amount in the PAYMENT field.');
            $("#edit-payment").focus();
            return;
        }
        
        // Check if we have editing row data
        if (!window.currentEditingRow) {
            console.error('No editing row selected');
            alert('Please select a loan row first by double-clicking it.');
            return;
        }
        
        var loanId = window.currentEditingRow.loanId;
        var clientNo = window.currentEditingRow.clientNo;
        var clientName = window.currentEditingRow.clientName;
        
        console.log('Adding payment to table:', {
            clientName: clientName,
            paymentAmount: paymentAmount,
            principal: principal,
            interest: interest,
            penalty: penalty,
            total: total,
            loanId: loanId,
            clientNo: clientNo
        });
        
        // Show loading state
        $("#btnDone").prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Processing...');
        
        // Update the payment table row
        var $row = $(`#paymentTbl tbody .payment-row[data-loan-id="${loanId}"]`);
        
        if ($row.length > 0) {
            // Store payment amount and breakdown in row data attributes
            $row.attr('data-payment-amount', paymentAmount);
            $row.attr('data-principal', principal);
            $row.attr('data-interest', interest);
            $row.attr('data-penalty', penalty);
            $row.attr('data-total', total);
            
            // Update the table row display cells
            var cells = $row.find('td');
            $(cells[1]).text(formatAmtVal(principal)); // Principal column
            $(cells[2]).text(formatAmtVal(interest));  // Interest column
            $(cells[3]).text(formatAmtVal(penalty));   // Penalty column
            $(cells[4]).text(formatAmtVal(total));     // Total column
            
            // Mark this row as having a payment
            $row.addClass('has-payment');
            
            // Update the stored row data
            window.currentEditingRow.paymentAmount = paymentAmount;
            window.currentEditingRow.principal = principal;
            window.currentEditingRow.interest = interest;
            window.currentEditingRow.penalty = penalty;
            window.currentEditingRow.total = total;
            
            // Update the table totals in footer
            calculateAndDisplayTotals();
            
            // Keep edit mode open but reset payment field for next payment
            $("#edit-payment").val('0.00');
            validateDoneButton();
            
            console.log('Payment added to table successfully - edit mode remains open');
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Added!',
                    html: `Payment of ₱${formatAmtVal(paymentAmount)} has been added to the payment table.<br><br>` +
                          `You can add another payment or click "Save Transaction" to complete.`,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                alert(`Payment of ₱${formatAmtVal(paymentAmount)} added successfully!`);
            }
            
        } else {
            console.error('Payment table row not found for loan ID:', loanId);
            alert('Error: Could not find the loan row in the payment table.');
        }
        
    } catch (error) {
        console.error('Error in Add Payment button:', error);
        alert('Error processing payment: ' + error.message);
    } finally {
        // Restore button state
        $("#btnDone").prop('disabled', false).html('<i class="fa-solid fa-circle-check"></i> Add Payment');
    }
});

$("#btnCancel").on('click', function() {
    console.log('Cancel button clicked');
    
    // Use the new cancel edit mode function
    cancelEditMode();
    
    console.log('Edit mode cancelled via Cancel button');
});

// Payment field change handler to validate Done button
$("#edit-payment").on('input keyup change', function() {
    validateDoneButton();
});

// Numeric input validation for edit amount fields
function validateNumericInput(event) {
    var key = event.which || event.keyCode;
    var value = event.target.value;
    var char = String.fromCharCode(key);
    
    // Allow: backspace, delete, tab, escape, enter
    if ([8, 9, 27, 13, 46].indexOf(key) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z
        (key === 65 && event.ctrlKey === true) ||
        (key === 67 && event.ctrlKey === true) ||
        (key === 86 && event.ctrlKey === true) ||
        (key === 88 && event.ctrlKey === true) ||
        (key === 90 && event.ctrlKey === true) ||
        // Allow: home, end, left, right, down, up
        (key >= 35 && key <= 40)) {
        return;
    }
    
    // Allow only digits and one decimal point
    if ((char < '0' || char > '9') && char !== '.') {
        event.preventDefault();
        return false;
    }
    
    // Allow only one decimal point
    if (char === '.' && value.indexOf('.') !== -1) {
        event.preventDefault();
        return false;
    }
    
    // Limit to 2 decimal places
    if (value.indexOf('.') !== -1) {
        var decimalPart = value.split('.')[1];
        if (decimalPart && decimalPart.length >= 2 && key !== 8 && key !== 46) {
            event.preventDefault();
            return false;
        }
    }
}

// Function to clean and format numeric input
function cleanNumericInput(input) {
    var value = input.value;
    
    // Remove any non-numeric characters except decimal point
    value = value.replace(/[^0-9.]/g, '');
    
    // Ensure only one decimal point
    var parts = value.split('.');
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Limit to 2 decimal places
    if (parts[1] && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2);
    }
    
    input.value = value;
}

// Add numeric validation to edit amount fields
$("#edit-payment").on('keydown', validateNumericInput);
$("#edit-payment").on('input paste', function() {
    setTimeout(() => cleanNumericInput(this), 0);
});

$("#edit-principal").on('keydown', validateNumericInput);
$("#edit-principal").on('input paste', function() {
    setTimeout(() => {
        cleanNumericInput(this);
        // Auto-calculate interest when principal changes
        RecomputeAmountTotalsWithAutoInterest(true);
    }, 0);
});

// Also add change event for when user finishes editing principal
$("#edit-principal").on('change', function() {
    RecomputeAmountTotalsWithAutoInterest(true);
});

$("#edit-penalty").on('keydown', validateNumericInput);
$("#edit-penalty").on('input paste', function() {
    setTimeout(() => cleanNumericInput(this), 0);
});

// Helper function to format loan status with proper styling
function formatLoanStatus(status, balance, loanAmount) {
    if (!status) {
        // Calculate status if not provided
        balance = parseFloat(balance) || 0;
        loanAmount = parseFloat(loanAmount) || 0;
        
        // Use threshold to handle floating point precision issues
        if (Math.abs(balance) < 0.01) { // Balance is effectively zero (less than 1 cent)
            status = 'PAID';
        } else if (balance > 0 && balance < loanAmount) {
            status = 'PARTIAL';
        } else if (balance >= loanAmount) {
            status = 'ACTIVE';
        } else {
            status = 'UNKNOWN';
        }
    }
    
    // Return formatted status with appropriate styling
    switch (status.toUpperCase()) {
        case 'PAID':
            return '<span class="badge bg-success">PAID</span>';
        case 'PARTIAL':
            return '<span class="badge bg-warning">PARTIAL</span>';
        case 'ACTIVE':
            return '<span class="badge bg-primary">ACTIVE</span>';
        case 'WRITEOFF':
            return '<span class="badge bg-danger">WRITEOFF</span>';
        case 'OVERDUE':
            return '<span class="badge bg-danger">OVERDUE</span>';
        default:
            return '<span class="badge bg-secondary">UNKNOWN</span>';
    }
}

// Helper function to format currency
function formatCurrency(amount) {
    const num = parseFloat(amount) || 0;
    return '₱' + num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Helper function to calculate payment progress
function calculatePaymentProgress(balance, loanAmount) {
    const bal = parseFloat(balance) || 0;
    const loan = parseFloat(loanAmount) || 0;
    
    if (loan === 0) return 0;
    
    const paid = loan - bal;
    const progress = (paid / loan) * 100;
    
    return Math.max(0, Math.min(100, progress));
}

// Helper function to determine if a loan is eligible for payment
function isLoanEligibleForPayment(status, balance) {
    const statusUpper = (status || '').toUpperCase();
    const bal = parseFloat(balance) || 0;
    
    // Only active and partial loans can receive payments
    return (statusUpper === 'ACTIVE' || statusUpper === 'PARTIAL') && bal > 0;
}

// Helper function to get status color class
function getStatusColorClass(status) {
    switch ((status || '').toUpperCase()) {
        case 'PAID':
            return 'text-success';
        case 'PARTIAL':
            return 'text-warning';
        case 'ACTIVE':
            return 'text-primary';
        case 'WRITEOFF':
        case 'OVERDUE':
            return 'text-danger';
        default:
            return 'text-secondary';
    }
}


// Generate and display receipt
// Generate and display receipt
// Generate and display receipt
// Generate and display receipt - Direct to print version
// Generate and display receipt - Direct to print version
function generateReceipt(receiptData) {
    console.log('Generating receipt with data:', receiptData);

    try {
        // Validate receipt data
        if (!receiptData) {
            throw new Error('No receipt data provided');
        }

        if (!receiptData.ORNO) {
            throw new Error('Missing OR Number in receipt data');
        }

        // Store receipt data globally
        window.currentReceiptData = receiptData;

        // Go directly to PDF receipt generation (as requested by user)
        console.log('Generating PDF receipt directly...');
        
        // Create form to send to reports system
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = baseUrl + '/routes/cashier/loanspayment.route.php';
        form.target = '_blank';

        // Add action
        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'PrintReceipt';
        form.appendChild(actionInput);

        // Add receipt data
        var dataInput = document.createElement('input');
        dataInput.type = 'hidden';
        dataInput.name = 'receiptData';
        dataInput.value = JSON.stringify(receiptData);
        form.appendChild(dataInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Receipt Generated!',
            html: `
                <p>OR Number: <strong>${receiptData.ORNO}</strong></p>
                <p>Total Amount: <strong>₱${receiptData.TOTAL_AMOUNT ? receiptData.TOTAL_AMOUNT.toLocaleString('en-US', {minimumFractionDigits: 2}) : '0.00'}</strong></p>
                <p>PDF receipt opened in new window for printing</p>
            `,
            timer: 4000,
            showConfirmButton: true,
            confirmButtonText: 'OK'
        });

        console.log('Receipt generated successfully');

    } catch (error) {
        console.error('Error generating receipt:', error);

        // Show detailed error with debugging info
        Swal.fire({
            icon: 'error',
            title: 'Receipt Generation Error',
            html: `
                <p>Failed to generate receipt: ${error.message}</p>
                <hr>
                <p><strong>Debug Info:</strong></p>
                <p>Receipt Data: ${receiptData ? 'Present' : 'Missing'}</p>
                <p>OR Number: ${receiptData && receiptData.ORNO ? receiptData.ORNO : 'Missing'}</p>
                <p>Total Amount: ${receiptData && receiptData.TOTAL_AMOUNT ? receiptData.TOTAL_AMOUNT : 'Missing'}</p>
                <hr>
                <button onclick="openSampleReceipt()" class="btn btn-info btn-sm">View Sample Receipt</button>
            `,
            showConfirmButton: true
        });
    }
}

// Function to print PDF receipt using reports system
function printPDFReceipt() {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    // Create form to send to reports system
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/routes/cashier/loanspayment.route.php';
    form.target = '_blank';

    // Add action
    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'PrintReceipt';
    form.appendChild(actionInput);

    // Add receipt data
    var dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'receiptData';
    dataInput.value = JSON.stringify(receiptData);
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    Swal.close();

    // Show success message
    Swal.fire({
        icon: 'success',
        title: 'Receipt Generated!',
        text: 'PDF receipt opened in new window for printing',
        timer: 3000,
        showConfirmButton: false
    });
}

// Function to print web receipt (fallback)
function printWebReceipt() {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/pages/cashier/receipt_print.php';
    form.target = '_blank';

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'receiptData';
    input.value = JSON.stringify(receiptData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    Swal.close();
}

// Function to print receipt by OR number (for reprinting)
function printReceiptByOR() {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    var orNumber = receiptData.ORNO;
    var url = baseUrl + '/routes/cashier/loanspayment.route.php?action=PrintReceipt&orno=' + encodeURIComponent(orNumber);
    window.open(url, '_blank');

    Swal.close();
}

// Function to open PDF receipt (for the optional PDF button)
function openPDFReceipt(orNumber) {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/pages/cashier/receipt_pdf.php';
    form.target = '_blank';

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'receiptData';
    input.value = JSON.stringify(receiptData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    Swal.close();
}

// Function to open print-optimized receipt
function openPrintReceipt() {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/pages/cashier/receipt_print.php';
    form.target = '_blank';

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'receiptData';
    input.value = JSON.stringify(receiptData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    Swal.close();
}

// Function to open PDF receipt
function openPDFReceipt() {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/pages/cashier/receipt_pdf.php';
    form.target = '_blank';

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'receiptData';
    input.value = JSON.stringify(receiptData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    Swal.close();
}

// Function to open basic receipt (original)
function openBasicReceipt() {
    var receiptData = window.currentReceiptData;
    if (!receiptData) {
        alert('No receipt data available');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/pages/cashier/payment_receipt.php';
    form.target = '_blank';

    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'receiptData';
    input.value = JSON.stringify(receiptData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    Swal.close();
}

// Function to open sample receipt for testing
function openSampleReceipt() {
    var sampleUrl = baseUrl + '/pages/cashier/payment_receipt.php?test=1';
    window.open(sampleUrl, '_blank');
}

// Helper function to update loan balance functions (if needed for future enhancements)
function updateLoanBalanceDisplay(loanId, newBalance) {
    // Find the row in payment table and update balance display
    var table = $('#paymentTbl').DataTable();
    table.rows().every(function(rowIdx, tableLoop, rowLoop) {
        var data = this.data();
        if (data[6] === loanId) { // LoanID is at index 6
            // Update the balance in the row (index 1 is Principal/Balance)
            data[1] = formatAmtVal(newBalance);
            this.data(data).draw();
        }
    });
}

// Enhanced error handling for payment processing
function handlePaymentError(error, context) {
    console.error('Payment processing error in ' + context + ':', error);

    var errorMessage = 'An error occurred during payment processing.';

    if (error.responseJSON && error.responseJSON.MESSAGE) {
        errorMessage = error.responseJSON.MESSAGE;
    } else if (error.responseText) {
        try {
            var errorData = JSON.parse(error.responseText);
            errorMessage = errorData.MESSAGE || errorMessage;
        } catch (e) {
            errorMessage = 'Server error: ' + error.status + ' - ' + error.statusText;
        }
    }

    Swal.fire({
        icon: 'error',
        title: 'Payment Processing Error',
        text: errorMessage,
        footer: 'Context: ' + context
    });
}

// Validate payment data before submission
// Validate payment data before submission
function validatePaymentData(paymentData) {
    if (!paymentData || paymentData.length === 0) {
        return { valid: false, message: 'No payment data to process' };
    }

    for (var i = 0; i < paymentData.length; i++) {
        var payment = paymentData[i];

        // Check if payment has required fields (after CBU/MBA removal)
        if (!payment[5] || !payment[6]) { // ClientNo and LoanID
            return {
                valid: false,
                message: 'Missing client or loan information in payment ' + (i + 1)
            };
        }

        // Helper function to parse formatted amounts (removes commas)
        function parseAmount(value) {
            if (typeof value === 'string') {
                return parseFloat(value.replace(/[,\s]/g, '')) || 0;
            }
            return parseFloat(value) || 0;
        }

        // Check if payment amounts are valid - handle formatted numbers
        var principal = parseAmount(payment[1]);
        var interest = parseAmount(payment[2]);
        var penalty = parseAmount(payment[3]);
        var total = parseAmount(payment[4]);

        console.log('Payment validation debug:', {
            paymentIndex: i + 1,
            rawData: {
                principal: payment[1],
                interest: payment[2],
                penalty: payment[3],
                total: payment[4]
            },
            parsedData: {
                principal: principal,
                interest: interest,
                penalty: penalty,
                total: total
            }
        });

        if (total <= 0) {
            return {
                valid: false,
                message: 'Invalid payment amount in payment ' + (i + 1)
            };
        }

        // Verify total calculation (allowing small floating point differences)
        var calculatedTotal = principal + interest + penalty;
        if (Math.abs(calculatedTotal - total) > 0.01) {
            return {
                valid: false,
                message: 'Payment calculation error in payment ' + (i + 1) + '. Expected: ' + calculatedTotal.toFixed(2) + ', Got: ' + total.toFixed(2) +
                        '\nRaw values: P=' + payment[1] + ', I=' + payment[2] + ', Pen=' + payment[3] + ', T=' + payment[4]
            };
        }
    }

    return { valid: true, message: 'Payment data is valid' };
}
// Debug function to analyze payment data structure
function debugPaymentData() {
    if (typeof paymentTbl !== 'undefined' && paymentTbl) {
        var data = paymentTbl.rows().data().toArray();
        console.log('=== PAYMENT DATA DEBUG ===');
        console.log('Number of payments:', data.length);

        data.forEach(function(payment, index) {
            console.log('Payment ' + (index + 1) + ':', {
                fullArray: payment,
                clientName: payment[0],
                principal: payment[1],
                interest: payment[2],
                penalty: payment[3],
                total: payment[4],
                clientNo: payment[5],
                loanId: payment[6],
                fund: payment[7]
            });

            // Test parsing
            var principal = parseFloat(payment[1].toString().replace(/[,\s]/g, '')) || 0;
            var interest = parseFloat(payment[2].toString().replace(/[,\s]/g, '')) || 0;
            var penalty = parseFloat(payment[3].toString().replace(/[,\s]/g, '')) || 0;
            var total = parseFloat(payment[4].toString().replace(/[,\s]/g, '')) || 0;
            var calculated = principal + interest + penalty;

            console.log('Parsed amounts:', {
                principal: principal,
                interest: interest,
                penalty: penalty,
                total: total,
                calculated: calculated,
                difference: Math.abs(calculated - total)
            });
        });
        console.log('=== END DEBUG ===');
    } else {
        console.log('Payment table not initialized');
    }
}

// Add debug button to console (call debugPaymentData() in browser console)
window.debugPaymentData = debugPaymentData;

// Debug function to check payment table status
function debugPaymentTable() {
    console.log('=== Payment Table Debug ===');
    console.log('paymentTbl variable:', paymentTbl);
    console.log('paymentTbl type:', typeof paymentTbl);
    
    if (paymentTbl) {
        console.log('DataTable exists');
        console.log('Has rows method:', typeof paymentTbl.rows === 'function');
        
        if (typeof paymentTbl.rows === 'function') {
            try {
                var data = paymentTbl.rows().data().toArray();
                console.log('DataTable data:', data);
                console.log('Row count:', data.length);
            } catch (error) {
                console.error('Error accessing DataTable data:', error);
            }
        }
    } else {
        console.log('DataTable not initialized');
    }
    
    // Check HTML table
    var htmlTable = $('#paymentTbl');
    console.log('HTML table exists:', htmlTable.length > 0);
    console.log('HTML table rows:', htmlTable.find('tbody tr').length);
    
    // Check if it's a DataTable
    console.log('Is DataTable:', $.fn.DataTable.isDataTable('#paymentTbl'));
}

// Test function to manually collect payment data
function testPaymentDataCollection() {
    console.log('=== Manual Payment Data Collection Test ===');
    
    var manualData = [];
    $('#paymentTbl tbody .payment-row').each(function() {
        var row = [];
        $(this).find('td').each(function() {
            row.push($(this).text().trim());
        });
        if (row.length > 0) {
            manualData.push(row);
        }
    });
    
    console.log('Manually collected data:', manualData);
    console.log('Manual data count:', manualData.length);
    
    return manualData;
}


// ============================================
// LATE PAYMENT DETECTION SYSTEM
// ============================================

var latePaymentData = null; // Store late payment info globally

/**
 * Check if a loan payment is late
 */
function checkLatePayment(loanID, dateRelease, mode) {
    console.log('Checking late payment for loan:', loanID);
    
    // Hide warning by default
    $('#latePaymentWarning').hide();
    latePaymentData = null;
    
    if (!loanID) {
        console.warn('No loan ID provided for late payment check');
        return;
    }
    
    $.ajax({
        url: baseUrl + "/routes/cashier/loanspayment.route.php",
        type: "POST",
        data: {
            action: "CheckLatePayment",
            loanID: loanID,
            paymentDate: new Date().toISOString().split('T')[0] // Today's date
        },
        dataType: "JSON",
        success: function(response) {
            console.log('Late payment check response:', response);
            
            if (response && response.isLate) {
                // Store late payment data
                latePaymentData = response;
                
                // Show warning
                showLatePaymentWarning(response);
            } else {
                console.log('Payment is on time or loan not found');
            }
        },
        error: function(xhr, status, error) {
            console.error('Late payment check failed:', error);
            // Don't show error to user - just log it
            // Payment can still proceed without late payment check
        }
    });
}

/**
 * Display the late payment warning
 */
function showLatePaymentWarning(data) {
    var message = `
        <strong>Due Date:</strong> ${data.dueDate}<br>
        <strong>Days Late:</strong> ${data.daysLate} days<br>
        <strong>Suggested Penalty:</strong> ₱${parseFloat(data.suggestedPenalty).toFixed(2)}<br>
        <strong>Current Balance:</strong> ₱${parseFloat(data.balance).toFixed(2)}
    `;
    
    $('#latePaymentMessage').html(message);
    $('#latePaymentWarning').slideDown(300);
    $('#btnAddPenalty').show();
    $('#btnWaivePenalty').show();
}

/**
 * Add penalty to payment - AUTOMATIC
 */
$(document).on('click', '#btnAddPenalty', function() {
    if (!latePaymentData) {
        alert('No late payment data available');
        return;
    }
    
    var penalty = parseFloat(latePaymentData.suggestedPenalty) || 0;
    
    // Automatically add penalty to the edit-penalty field
    var currentPenalty = parseFloat($('#edit-penalty').val().replace(/,/g, '')) || 0;
    var newPenalty = currentPenalty + penalty;
    
    // Enable penalty field and set the value
    $('#edit-penalty').prop('disabled', false).val(formatAmtVal(newPenalty));
    
    // Highlight the field to show it was auto-filled
    $('#edit-penalty').addClass('auto-calculated');
    setTimeout(function() {
        $('#edit-penalty').removeClass('auto-calculated');
    }, 2000);
    
    // Recalculate totals
    RecomputeAmountTotalsWithAutoInterest(false);
    
    // Update button states
    $(this).prop('disabled', true).html('<i class="fa-solid fa-check"></i> Penalty Added');
    $('#btnWaivePenalty').hide();
    
    // Show success message
    Swal.fire({
        icon: 'success',
        title: 'Penalty Added',
        html: `<p>₱${penalty.toFixed(2)} penalty has been automatically added</p>
               <p style="font-size: 0.9rem; color: #666;">You can still edit the penalty amount if needed</p>`,
        timer: 3000,
        showConfirmButton: false
    });
    
    console.log('Penalty automatically added:', penalty);
});

/**
 * Waive penalty - AUTOMATIC
 */
$(document).on('click', '#btnWaivePenalty', function() {
    if (!latePaymentData) {
        alert('No late payment data available');
        return;
    }
    
    // Automatically set penalty to 0
    $('#edit-penalty').prop('disabled', false).val('0.00');
    
    // Highlight the field to show it was auto-filled
    $('#edit-penalty').addClass('auto-calculated');
    setTimeout(function() {
        $('#edit-penalty').removeClass('auto-calculated');
    }, 2000);
    
    // Recalculate totals
    RecomputeAmountTotalsWithAutoInterest(false);
    
    // Show note input
    $('#penaltyWaiverNote').slideDown(200);
    
    // Update button states
    $(this).prop('disabled', true).html('<i class="fa-solid fa-check"></i> Penalty Waived');
    $('#btnAddPenalty').hide();
    
    // Show success message
    Swal.fire({
        icon: 'info',
        title: 'Penalty Waived',
        html: `<p>The late payment penalty has been waived (set to ₱0.00)</p>
               <p style="font-size: 0.9rem; color: #666;">Please add a note explaining why the penalty was waived</p>`,
        timer: 3000,
        showConfirmButton: false
    });
    
    console.log('Penalty waived - set to 0');
});

/**
 * Get late payment data for saving with transaction
 */
function getLatePaymentDataForSave() {
    if (!latePaymentData) {
        return null;
    }
    
    var isPenaltyWaived = $('#btnWaivePenalty').prop('disabled');
    var notes = $('#latePaymentNotes').val();
    
    return {
        dueDate: latePaymentData.dueDate,
        daysLate: latePaymentData.daysLate,
        isLate: true,
        penaltyWaived: isPenaltyWaived ? 1 : 0,
        latePaymentNotes: notes || ''
    };
}
