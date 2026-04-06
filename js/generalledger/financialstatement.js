$(document).ready(function () {

    // ── Init ──────────────────────────────────────────────────────────────────
    $('#fundSelect').select2({ width: '100%' });
    
    // Populate year dropdown (last 10 years)
    var currentYear = new Date().getFullYear();
    var yearOpts = '<option value="">Select Year</option>';
    for (var i = 0; i < 10; i++) {
        var year = currentYear - i;
        yearOpts += '<option value="' + year + '">' + year + '</option>';
    }
    $('#yearSelect').html(yearOpts);
    
    loadPage();

    // ── Events ────────────────────────────────────────────────────────────────
    // Date filter radio buttons
    $('input[name="dateFilter"]').on('change', function () {
        if ($(this).val() === 'monthrange') {
            $('#fromMonth').prop('disabled', false);
            $('#toMonth').prop('disabled', false);
            $('#yearSelect').prop('disabled', true);
        } else {
            $('#fromMonth').prop('disabled', true);
            $('#toMonth').prop('disabled', true);
            $('#yearSelect').prop('disabled', false);
        }
    });

    // Auto-fill To Date when From Date is selected (for convenience)
    $('#fromMonth').on('change', function () {
        var fromDate = $(this).val();
        if (fromDate && !$('#toMonth').val()) {
            $('#toMonth').val(fromDate);
        }
    });

    $('#btnRetrieve').on('click', function () {
        retrieve();
    });

    $('#btnClear').on('click', function () {
        clearAll();
    });

    $('#btnPrint').on('click', function () {
        printStatement();
    });

    // ── Functions ─────────────────────────────────────────────────────────────
    function loadPage() {
        $.ajax({
            url: '/iSynApp-main/generalledger/financialstatement',
            type: 'POST',
            data: { action: 'LoadPage' },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    var opts = '<option value="">All Funds</option>';
                    $.each(res.FUNDS || [], function (i, f) {
                        opts += '<option value="' + f.fundname + '">' + f.fundname + '</option>';
                    });
                    $('#fundSelect').html(opts).trigger('change');
                }
            },
            error: function(xhr, status, error) {
                console.error('LoadPage error:', error);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load funds' });
            }
        });
    }

    function retrieve() {
        var reportType = $('input[name="reportType"]:checked').val();
        var dateFilter = $('input[name="dateFilter"]:checked').val();
        var fund = $('#fundSelect').val();

        if (!reportType) {
            Swal.fire({ icon: 'warning', title: 'Select Report', text: 'Please select a report type' });
            return;
        }

        if (!dateFilter) {
            Swal.fire({ icon: 'warning', title: 'Select Date Filter', text: 'Please select a date filter' });
            return;
        }

        var action = '';
        var data = { fund: fund };

        if (reportType === 'income') {
            action = 'GetIncomeStatement';
            
            if (dateFilter === 'year') {
                var year = $('#yearSelect').val();
                if (!year) {
                    Swal.fire({ icon: 'warning', title: 'Missing Year', text: 'Please select a fiscal year' });
                    return;
                }
                data.startDate = year + '-01-01';
                data.endDate = year + '-12-31';
            } else if (dateFilter === 'monthrange') {
                var fromDate = $('#fromMonth').val();
                var toDate = $('#toMonth').val();
                if (!fromDate) {
                    Swal.fire({ icon: 'warning', title: 'Missing Date', text: 'Please select at least the From Date' });
                    return;
                }
                // If To Date is empty, use From Date (single day)
                if (!toDate) {
                    toDate = fromDate;
                }
                data.startDate = fromDate;
                data.endDate = toDate;
            }
        } else if (reportType === 'balance') {
            action = 'GetBalanceSheet';
            
            if (dateFilter === 'year') {
                var year = $('#yearSelect').val();
                if (!year) {
                    Swal.fire({ icon: 'warning', title: 'Missing Year', text: 'Please select a fiscal year' });
                    return;
                }
                data.asOfDate = year + '-12-31';
            } else if (dateFilter === 'monthrange') {
                var toDate = $('#toMonth').val();
                var fromDate = $('#fromMonth').val();
                
                // If To Date is empty, use From Date
                if (!toDate && fromDate) {
                    toDate = fromDate;
                }
                
                if (!toDate) {
                    Swal.fire({ icon: 'warning', title: 'Missing Date', text: 'Please select at least the From Date for Balance Sheet' });
                    return;
                }
                // Balance sheet uses the "To Date"
                data.asOfDate = toDate;
            }
        } else if (reportType === 'cashflow') {
            action = 'GetCashFlowStatement';
            
            if (dateFilter === 'year') {
                var year = $('#yearSelect').val();
                if (!year) {
                    Swal.fire({ icon: 'warning', title: 'Missing Year', text: 'Please select a fiscal year' });
                    return;
                }
                data.startDate = year + '-01-01';
                data.endDate = year + '-12-31';
            } else if (dateFilter === 'monthrange') {
                var fromDate = $('#fromMonth').val();
                var toDate = $('#toMonth').val();
                if (!fromDate) {
                    Swal.fire({ icon: 'warning', title: 'Missing Date', text: 'Please select at least the From Date' });
                    return;
                }
                // If To Date is empty, use From Date (single day)
                if (!toDate) {
                    toDate = fromDate;
                }
                data.startDate = fromDate;
                data.endDate = toDate;
            }
        }

        data.action = action;

        $('#loading').show();

        $.ajax({
            url: '/iSynApp-main/generalledger/financialstatement',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (res) {
                $('#loading').hide();

                if (res.STATUS !== 'SUCCESS') {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE || 'Failed to generate statement' });
                    return;
                }

                displayStatement(res);
            },
            error: function () {
                $('#loading').hide();
                Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while generating the statement' });
            }
        });
    }

    function displayStatement(data) {
        var html = '';

        if (data.STATEMENT_TYPE === 'income') {
            html = buildIncomeStatement(data);
        } else if (data.STATEMENT_TYPE === 'balance') {
            html = buildBalanceSheet(data);
        } else if (data.STATEMENT_TYPE === 'cashflow') {
            html = buildCashFlowStatement(data);
        }

        // Hide placeholder and show statement content
        $('#resultContainer > .text-center').hide();
        $('#statementContent').html(html).show();
    }

    function buildIncomeStatement(data) {
        var html = '<div class="statement-header">';
        html += '<h3>iSynergies Inc.</h3>';
        html += '<h4>Income Statement</h4>';
        html += '<p>Fund: ' + data.FUND + '</p>';
        html += '<p>For the Period: ' + formatDate(data.START_DATE) + ' to ' + formatDate(data.END_DATE) + '</p>';
        html += '</div>';

        html += '<table class="table table-sm table-hover">';
        
        // Revenue Section
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">REVENUE</th></tr></thead>';
        html += '<tbody>';
        if (data.REVENUE && data.REVENUE.length > 0) {
            $.each(data.REVENUE, function(i, row) {
                html += '<tr>';
                html += '<td class="ps-4">' + row.acctno + ' - ' + row.accttitle + '</td>';
                html += '<td class="text-end" style="width: 200px;">' + fmt(row.balance) + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="2" class="text-center text-muted">No revenue accounts</td></tr>';
        }
        html += '<tr class="table-light fw-bold border-top border-2">';
        html += '<td class="ps-4">Total Revenue</td>';
        html += '<td class="text-end">' + fmt(data.TOTAL_REVENUE) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        // Expenses Section
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">EXPENSES</th></tr></thead>';
        html += '<tbody>';
        if (data.EXPENSES && data.EXPENSES.length > 0) {
            $.each(data.EXPENSES, function(i, row) {
                html += '<tr>';
                html += '<td class="ps-4">' + row.acctno + ' - ' + row.accttitle + '</td>';
                html += '<td class="text-end">' + fmt(row.balance) + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="2" class="text-center text-muted">No expense accounts</td></tr>';
        }
        html += '<tr class="table-light fw-bold border-top border-2">';
        html += '<td class="ps-4">Total Expenses</td>';
        html += '<td class="text-end">' + fmt(data.TOTAL_EXPENSES) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        // Net Income
        html += '<tfoot>';
        html += '<tr class="table-success fw-bold fs-5 border-top border-3">';
        html += '<td class="ps-3">NET INCOME</td>';
        html += '<td class="text-end">' + fmt(data.NET_INCOME) + '</td>';
        html += '</tr>';
        html += '</tfoot>';
        
        html += '</table>';
        return html;
    }

    function buildBalanceSheet(data) {
        var html = '<div class="statement-header">';
        html += '<h3>iSynergies Inc.</h3>';
        html += '<h4>Balance Sheet</h4>';
        html += '<p>Fund: ' + data.FUND + '</p>';
        html += '<p>As of: ' + formatDate(data.AS_OF_DATE) + '</p>';
        html += '</div>';

        html += '<table class="table table-sm table-hover">';
        
        // Assets
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">ASSETS</th></tr></thead>';
        html += '<tbody>';
        if (data.ASSETS && data.ASSETS.length > 0) {
            $.each(data.ASSETS, function(i, row) {
                html += '<tr>';
                html += '<td class="ps-4">' + row.acctno + ' - ' + row.accttitle + '</td>';
                html += '<td class="text-end" style="width: 200px;">' + fmt(row.balance) + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="2" class="text-center text-muted">No asset accounts</td></tr>';
        }
        html += '<tr class="table-light fw-bold border-top border-2">';
        html += '<td class="ps-4">Total Assets</td>';
        html += '<td class="text-end">' + fmt(data.TOTAL_ASSETS) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        // Liabilities
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">LIABILITIES</th></tr></thead>';
        html += '<tbody>';
        if (data.LIABILITIES && data.LIABILITIES.length > 0) {
            $.each(data.LIABILITIES, function(i, row) {
                html += '<tr>';
                html += '<td class="ps-4">' + row.acctno + ' - ' + row.accttitle + '</td>';
                html += '<td class="text-end">' + fmt(row.balance) + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="2" class="text-center text-muted">No liability accounts</td></tr>';
        }
        html += '<tr class="table-light fw-bold border-top border-2">';
        html += '<td class="ps-4">Total Liabilities</td>';
        html += '<td class="text-end">' + fmt(data.TOTAL_LIABILITIES) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        // Equity
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">EQUITY</th></tr></thead>';
        html += '<tbody>';
        if (data.EQUITY && data.EQUITY.length > 0) {
            $.each(data.EQUITY, function(i, row) {
                html += '<tr>';
                html += '<td class="ps-4">' + row.acctno + ' - ' + row.accttitle + '</td>';
                html += '<td class="text-end">' + fmt(row.balance) + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="2" class="text-center text-muted">No equity accounts</td></tr>';
        }
        html += '<tr class="table-light fw-bold border-top border-2">';
        html += '<td class="ps-4">Total Equity</td>';
        html += '<td class="text-end">' + fmt(data.TOTAL_EQUITY) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        // Total Liabilities + Equity
        html += '<tfoot>';
        html += '<tr class="table-success fw-bold fs-5 border-top border-3">';
        html += '<td class="ps-3">Total Liabilities + Equity</td>';
        html += '<td class="text-end">' + fmt(data.TOTAL_LIABILITIES + data.TOTAL_EQUITY) + '</td>';
        html += '</tr>';
        html += '</tfoot>';
        
        html += '</table>';
        
        if (!data.BALANCE_CHECK) {
            html += '<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Warning: Assets do not equal Liabilities + Equity</div>';
        }
        
        return html;
    }

    function buildCashFlowStatement(data) {
        var html = '<div class="statement-header">';
        html += '<h3>iSynergies Inc.</h3>';
        html += '<h4>Cash Flow Statement</h4>';
        html += '<p>Fund: ' + data.FUND + '</p>';
        html += '<p>For the Period: ' + formatDate(data.START_DATE) + ' to ' + formatDate(data.END_DATE) + '</p>';
        html += '</div>';

        html += '<table class="table table-sm table-hover">';
        
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">OPERATING ACTIVITIES</th></tr></thead>';
        html += '<tbody>';
        html += '<tr><td class="ps-4">Net Income</td><td class="text-end" style="width: 200px;">' + fmt(data.NET_INCOME) + '</td></tr>';
        html += '<tr class="table-light fw-bold border-top border-2">';
        html += '<td class="ps-4">Cash from Operating Activities</td>';
        html += '<td class="text-end">' + fmt(data.OPERATING_CASH) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">INVESTING ACTIVITIES</th></tr></thead>';
        html += '<tbody>';
        html += '<tr class="table-light fw-bold">';
        html += '<td class="ps-4">Cash from Investing Activities</td>';
        html += '<td class="text-end">' + fmt(data.INVESTING_CASH) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        html += '<thead><tr class="table-primary"><th colspan="2" class="fs-6">FINANCING ACTIVITIES</th></tr></thead>';
        html += '<tbody>';
        html += '<tr class="table-light fw-bold">';
        html += '<td class="ps-4">Cash from Financing Activities</td>';
        html += '<td class="text-end">' + fmt(data.FINANCING_CASH) + '</td>';
        html += '</tr>';
        html += '</tbody>';
        
        html += '<tfoot>';
        html += '<tr class="table-info fw-bold border-top border-2">';
        html += '<td class="ps-3">Net Change in Cash</td>';
        html += '<td class="text-end">' + fmt(data.NET_CASH_CHANGE) + '</td>';
        html += '</tr>';
        html += '<tr><td class="ps-4">Beginning Cash Balance</td><td class="text-end">' + fmt(data.BEGINNING_CASH) + '</td></tr>';
        html += '<tr class="table-success fw-bold fs-5 border-top border-3">';
        html += '<td class="ps-3">Ending Cash Balance</td>';
        html += '<td class="text-end">' + fmt(data.ENDING_CASH) + '</td>';
        html += '</tr>';
        html += '</tfoot>';
        
        html += '</table>';
        return html;
    }

    function printStatement() {
        if ($('#statementContent').is(':visible') && $('#statementContent').html().trim() !== '') {
            var printContent = '<html><head><title>Financial Statement</title>';
            printContent += '<style>';
            printContent += 'body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }';
            printContent += '.statement-header { text-align: center; margin-bottom: 20px; }';
            printContent += '.statement-header h3 { margin: 5px 0; font-size: 16px; }';
            printContent += '.statement-header h4 { margin: 5px 0; font-size: 14px; }';
            printContent += '.statement-header p { margin: 3px 0; color: #666; }';
            printContent += 'table { width: 100%; border-collapse: collapse; }';
            printContent += 'th, td { border: 1px solid #333; padding: 6px 8px; }';
            printContent += '.table-primary td { background: #e7f3ff; font-weight: bold; }';
            printContent += '.table-success td { background: #d4edda; font-weight: bold; }';
            printContent += '.table-info td { background: #d1ecf1; font-weight: bold; }';
            printContent += '.fw-bold td { font-weight: bold; }';
            printContent += '.text-end { text-align: right; }';
            printContent += '</style></head><body>';
            printContent += $('#statementContent').html();
            printContent += '</body></html>';

            var w = window.open('', '_blank', 'width=1000,height=800');
            w.document.write(printContent);
            w.document.close();
            w.focus();
            setTimeout(function() { w.print(); }, 300);
        } else {
            Swal.fire({ icon: 'info', title: 'No Data', text: 'Please retrieve a statement first' });
        }
    }

    function fmt(val) {
        var n = parseFloat(val) || 0;
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function clearAll() {
        $('#fundSelect').val('').trigger('change');
        $('input[name="dateFilter"]').prop('checked', false);
        $('#radioYear').prop('checked', true).trigger('change');
        $('input[name="reportType"]').prop('checked', false);
        $('#fromMonth').val('');
        $('#toMonth').val('');
        $('#yearSelect').val('');
        
        // Show placeholder and hide statement content
        $('#statementContent').hide();
        $('#resultContainer > .text-center').show();
    }
});
