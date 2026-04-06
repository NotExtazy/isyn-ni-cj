$(document).ready(function () {

    // ── Init ──────────────────────────────────────────────────────────────────
    $('#fundSelect').select2({ width: '100%' });
    var tbTable = null;

    loadPage();

    // ── Events ────────────────────────────────────────────────────────────────
    $('input[name="reportType"]').on('change', function () {
        $('#tableContainer').hide();
        $('#placeholderMessage').show();
    });

    $('#btnRetrieve').on('click', function () {
        retrieve();
    });

    $('#btnPrint').on('click', function () {
        printPreview();
    });

    $('#btnClear').on('click', function () {
        clearAll();
    });

    // ── Functions ─────────────────────────────────────────────────────────────
    function loadPage() {
        $.ajax({
            url: '/iSynApp-main/generalledger/trialbalance',
            type: 'POST',
            data: { action: 'LoadPage' },
            dataType: 'json',
            success: function (res) {
                console.log('LoadPage response:', res);
                
                if (res.STATUS === 'ERROR') {
                    console.error('Server error:', res.MESSAGE);
                    alert('Error loading funds: ' + res.MESSAGE);
                    return;
                }
                
                var opts = '<option value="">All Funds</option>';
                $.each(res.FUNDS || [], function (i, f) {
                    opts += '<option value="' + f.fundname + '">' + f.fundname + '</option>';
                });
                $('#fundSelect').html(opts).trigger('change');
                
                console.log('Loaded ' + (res.FUNDS ? res.FUNDS.length : 0) + ' funds');
            },
            error: function(xhr, status, error) {
                console.error('LoadPage AJAX error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                var errorMsg = 'Internal Server Error';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.MESSAGE) {
                        errorMsg = response.MESSAGE;
                    }
                } catch(e) {
                    errorMsg = xhr.responseText || error;
                }
                
                alert('Error loading funds: ' + errorMsg);
            }
        });
    }

    function retrieve() {
        var reportType = $('input[name="reportType"]:checked').val();
        var fund       = $('#fundSelect').val();

        if (!reportType) {
            Swal.fire({ icon: 'warning', title: 'Select Report Type', text: 'Please select a report type before retrieving.' });
            return;
        }

        $('#loading').show();

        $.ajax({
            url: '/iSynApp-main/generalledger/trialbalance',
            type: 'POST',
            data: { action: 'Retrieve', reportType: reportType, fund: fund },
            dataType: 'json',
            success: function (res) {
                console.log('Retrieve response:', res);
                $('#loading').hide();

                if (res.STATUS !== 'SUCCESS') {
                    Swal.fire({ icon: 'error', title: 'No Data', text: res.MESSAGE || 'Failed to retrieve trial balance.' });
                    return;
                }

                buildTable(res);
                $('#placeholderMessage').hide();
                $('#tableContainer').show();
            },
            error: function (xhr, status, error) {
                console.error('Retrieve error:', {xhr: xhr, status: status, error: error});
                $('#loading').hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while retrieving data.' });
            }
        });
    }

    function buildTable(res) {
        console.log('buildTable called with:', res);
        
        var rows     = res.ROWS     || [];
        var fundCols = res.FUND_COLS || [];
        var totals   = res.TOTALS   || {};
        var reportType = res.REPORT_TYPE || 'standard';
        var selectedFund = res.SELECTED_FUND || '';
        var hasData = res.HAS_DATA !== false;

        console.log('hasData:', hasData, 'selectedFund:', selectedFund, 'rows.length:', rows.length);

        // Destroy existing DataTable if any
        if (tbTable) { 
            tbTable.destroy(); 
            tbTable = null;
        }
        
        // Always clear the table completely
        $('#tbTableHead').empty(); 
        $('#tbTableBody').empty();

        // Remove any existing report type display banner
        $('#reportTypeDisplay').remove();

        // Build header
        var thead = '<tr><th>Account No.</th><th>Account Title</th><th>Category</th>';
        $.each(fundCols, function (i, col) {
            // Format fund column name nicely
            var fundName = col.toUpperCase().replace(/([A-Z])/g, ' $1').trim();
            thead += '<th class="text-end">' + fundName + '</th>';
        });
        thead += '<th class="text-end">Consolidated</th><th>Date</th></tr>';
        $('#tbTableHead').html(thead);

        // Build body
        var tbody = '';
        
        // Check if fund has no data
        if (!hasData && selectedFund) {
            var colCount = fundCols.length + 5; // acctno, accttitle, category, consolidated, date
            tbody += '<tr><td colspan="' + colCount + '" class="text-center text-muted py-4">';
            tbody += '<em>No data available for this fund. All balances are zero.</em>';
            tbody += '</td></tr>';
        } else {
            $.each(rows, function (i, r) {
                tbody += '<tr>';
                tbody += '<td>' + (r.acctno || '') + '</td>';
                tbody += '<td>' + (r.accttitle || '') + '</td>';
                tbody += '<td>' + (r.category || '') + '</td>';
                $.each(fundCols, function (j, col) {
                    tbody += '<td class="text-end">' + fmt(r[col]) + '</td>';
                });
                tbody += '<td class="text-end fw-semibold">' + fmt(r.consolidated) + '</td>';
                tbody += '<td>' + (r.cdate || '') + '</td>';
                tbody += '</tr>';
            });

            // Totals row
            tbody += '<tr class="table-primary fw-bold">';
            tbody += '<td>TOTAL</td><td></td><td></td>';
            $.each(fundCols, function (j, col) {
                tbody += '<td class="text-end">' + fmt(totals[col]) + '</td>';
            });
            tbody += '<td class="text-end">' + fmt(totals.consolidated) + '</td>';
            tbody += '<td></td></tr>';
        }

        $('#tbTableBody').html(tbody);

        // Init DataTable only if there's actual data
        if (hasData || !selectedFund) {
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                tbTable = $('#tbTable').DataTable({
                    order:    [[0, 'asc']],
                    pageLength: 25,
                    language: { emptyTable: 'No trial balance data found. Ensure posting has been run.' },
                });
            }, 100);
        }
    }

    function printPreview() {
        var reportType = $('input[name="reportType"]:checked').val();
        var fund       = $('#fundSelect').val();

        if (!reportType) {
            Swal.fire({ icon: 'warning', title: 'Select Report Type', text: 'Please select a report type first.' });
            return;
        }

        if (!$('#tableContainer').is(':visible')) {
            Swal.fire({ icon: 'info', title: 'Retrieve First', text: 'Please retrieve data before printing.' });
            return;
        }

        // Check if there's any data to print
        var rowCount = 0;
        if (tbTable) {
            rowCount = tbTable.rows().count();
        } else {
            rowCount = $('#tbTableBody tr').length;
        }

        // Check if the only row is the "no data" message
        var hasNoDataMessage = $('#tbTableBody tr td[colspan]').length > 0;
        
        if (rowCount === 0 || (rowCount === 1 && hasNoDataMessage)) {
            Swal.fire({ 
                icon: 'warning', 
                title: 'No Data to Print', 
                text: 'There is no data available to print. Please select a fund with data.' 
            });
            return;
        }

        // Get report type name
        var reportTypeName = '';
        switch(reportType) {
            case 'standard': reportTypeName = 'Standard Trial Balance'; break;
            case 'adjusted': reportTypeName = 'Adjusted Trial Balance'; break;
            case 'postclosing': reportTypeName = 'Post-Closing Trial Balance'; break;
            default: reportTypeName = 'Trial Balance';
        }

        // Get fund name
        var fundName = fund ? fund : 'All Funds';

        // Build print content with ALL rows
        var printContent = '<html><head><title>' + reportTypeName + '</title>';
        printContent += '<style>';
        printContent += 'body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px; }';
        printContent += 'h2 { text-align: center; margin-bottom: 5px; font-size: 16px; }';
        printContent += 'h4 { text-align: center; margin-top: 5px; margin-bottom: 15px; color: #666; font-size: 12px; }';
        printContent += 'table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9px; }';
        printContent += 'th, td { border: 1px solid #333; padding: 4px 6px; }';
        printContent += 'th { background: #f0f8fc; color: #3a7ca5; font-weight: bold; text-align: left; font-size: 10px; }';
        printContent += '.text-end { text-align: right; }';
        printContent += '.fw-bold, .fw-semibold { font-weight: bold; }';
        printContent += '.table-primary { background: #e7f3ff; font-weight: bold; }';
        printContent += '@media print {';
        printContent += '  body { margin: 10px; }';
        printContent += '  table { page-break-inside: auto; }';
        printContent += '  tr { page-break-inside: avoid; page-break-after: auto; }';
        printContent += '  thead { display: table-header-group; }';
        printContent += '  tfoot { display: table-footer-group; }';
        printContent += '}';
        printContent += '</style></head><body>';
        printContent += '<h2>iSynergies Inc.</h2>';
        printContent += '<h2>' + reportTypeName + '</h2>';
        printContent += '<h4>Fund: ' + fundName + '</h4>';
        printContent += '<h4>As of ' + new Date().toLocaleDateString() + '</h4>';
        
        // Build table with ALL data
        printContent += '<table>';
        
        // Get header from the actual table
        printContent += '<thead>' + $('#tbTableHead').html() + '</thead>';
        
        // Get ALL rows - if DataTable exists, get all rows across all pages
        printContent += '<tbody>';
        if (tbTable) {
            // Get ALL rows from DataTable (not just current page)
            var allRows = tbTable.rows({ order: 'current', search: 'applied' }).nodes();
            $(allRows).each(function() {
                printContent += this.outerHTML;
            });
        } else {
            // If no DataTable, get directly from tbody
            printContent += $('#tbTableBody').html();
        }
        printContent += '</tbody>';
        
        printContent += '</table>';
        printContent += '</body></html>';

        var w = window.open('', '_blank', 'width=1200,height=800');
        w.document.write(printContent);
        w.document.close();
        w.focus();
        
        // Auto print after content loads
        setTimeout(function() {
            w.print();
        }, 300);
    }

    function fmt(val) {
        var n = parseFloat(val) || 0;
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function clearAll() {
        $('#fundSelect').val('').trigger('change');
        $('input[name="reportType"]').prop('checked', false);
        $('#tableContainer').hide();
        $('#placeholderMessage').show();
        if (tbTable) { tbTable.destroy(); tbTable = null; $('#tbTableHead').empty(); $('#tbTableBody').empty(); }
    }
});