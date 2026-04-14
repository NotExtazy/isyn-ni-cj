$(document).ready(function () {
    
    // ══════════════════════════════════════════════════════════════════════
    // GLOBAL VARIABLES
    // ══════════════════════════════════════════════════════════════════════
    let currentFunds = [];
    let currentYear = new Date().getFullYear();
    let currentTab = 'beginning-balance';

    // ══════════════════════════════════════════════════════════════════════
    // INITIALIZATION
    // ══════════════════════════════════════════════════════════════════════
    loadPage();

    // ══════════════════════════════════════════════════════════════════════
    // TAB SWITCHING
    // ══════════════════════════════════════════════════════════════════════
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        currentTab = $(e.target).attr('id').replace('-tab', '');
        console.log('Switched to tab:', currentTab);
    });

    // ══════════════════════════════════════════════════════════════════════
    // LOAD PAGE DATA
    // ══════════════════════════════════════════════════════════════════════
    function loadPage() {
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: { action: 'LoadPage' },
            dataType: 'json',
            success: function (res) {
                console.log('LoadPage response:', res);
                if (res.STATUS === 'SUCCESS') {
                    currentFunds = res.FUNDS || [];
                    currentYear = res.CURRENT_YEAR;
                    populateFundDropdowns();
                    loadPESOData(); // Load PESO data on page load
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE || 'Failed to load page data' });
                }
            },
            error: function (xhr, status, error) {
                console.error('LoadPage error:', xhr.responseText);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load page data: ' + error });
            }
        });
    }

    function populateFundDropdowns() {
        let opts = '<option value="">Select Fund</option>';
        $.each(currentFunds, function (i, f) {
            opts += '<option value="' + f.fundname + '">' + f.fundname + '</option>';
        });
        $('#bbFundSelect, #slFundSelect, #yeFundSelect, #budgetFundSelect').html(opts);
        console.log('Fund dropdowns populated with', currentFunds.length, 'funds');
        console.log('Budget dropdown options:', $('#budgetFundSelect option').length);
    }

    // ══════════════════════════════════════════════════════════════════════
    // TAB 1: BEGINNING BALANCE DATA
    // ══════════════════════════════════════════════════════════════════════
    $('#bbFundSelect').on('change', function () {
        let fund = $(this).val();
        if (fund) {
            loadBeginningBalances(fund);
        } else {
            $('#bbTableBody').html('<tr><td colspan="3" class="text-center text-muted">Please select a fund</td></tr>');
        }
    });

    function loadBeginningBalances(fund) {
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: { 
                action: 'GetBeginningBalances',
                fund: fund,
                fiscalYear: currentYear
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    displayBeginningBalances(res.ACCOUNTS);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    }

    function displayBeginningBalances(accounts) {
        let html = '';
        if (accounts && accounts.length > 0) {
            $.each(accounts, function (i, acc) {
                html += '<tr class="account-row" style="cursor: pointer;" data-acctno="' + acc.acctno + '" data-accttitle="' + acc.accttitle + '" data-category="' + acc.category + '" data-balance="' + acc.selected_fund_balance + '">';
                html += '<td>' + acc.acctno + '</td>';
                html += '<td>' + acc.accttitle + '</td>';
                html += '<td>' + acc.category + '</td>';
                html += '<td class="text-end">' + parseFloat(acc.selected_fund_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="4" class="text-center text-muted">No accounts found</td></tr>';
        }
        $('#bbTableBody').html(html);
        
        // Add click event to rows
        $('.account-row').on('click', function() {
            let acctno = $(this).data('acctno');
            let accttitle = $(this).data('accttitle');
            let balance = $(this).data('balance');
            
            // Populate edit form
            $('#bbAccountNo').val(acctno);
            $('#bbAccountTitle').val(accttitle);
            $('#bbBeginningBalance').val(balance);
            
            // Highlight selected row
            $('.account-row').removeClass('table-active');
            $(this).addClass('table-active');
            
            // Scroll to edit form
            $('html, body').animate({
                scrollTop: $('#bbAccountNo').offset().top - 100
            }, 500);
        });
    }

    // Cancel editing
    $('#btnCancelBB').on('click', function () {
        // Clear form fields
        $('#bbAccountNo, #bbAccountTitle, #bbBeginningBalance').val('');
        
        // Remove highlight from selected row
        $('.account-row').removeClass('table-active');
    });

    // Edit single beginning balance
    $('#btnEditBB').on('click', function () {
        let fund = $('#bbFundSelect').val();
        let acctno = $('#bbAccountNo').val();
        let accttitle = $('#bbAccountTitle').val();
        let balance = $('#bbBeginningBalance').val();

        if (!fund || !acctno) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please fill in all fields' });
            return;
        }

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'SaveBeginningBalance',
                fund: fund,
                acctno: acctno,
                accttitle: accttitle,
                balance: balance,
                fiscalYear: currentYear
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.MESSAGE });
                    loadBeginningBalances(fund);
                    $('#bbAccountNo, #bbAccountTitle, #bbBeginningBalance').val('');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB 2: SL BALANCE
    // ══════════════════════════════════════════════════════════════════════
    $('#slFundSelect').on('change', function () {
        let fund = $(this).val();
        if (fund) {
            loadAccountCodes();
        }
    });

    function loadAccountCodes() {
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: { action: 'GetAccountCodes' },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    let opts = '<option value="">Select Account Code</option>';
                    $.each(res.ACCOUNTS || [], function (i, acc) {
                        opts += '<option value="' + acc.acctno + '">' + acc.acctno + ' - ' + acc.accttitle + '</option>';
                    });
                    $('#slAccountCode').html(opts);
                }
            }
        });
    }

    $('#slAccountCode').on('change', function () {
        let fund = $('#slFundSelect').val();
        let acctno = $(this).val();
        if (fund && acctno) {
            loadSLBalances(fund, acctno);
        }
    });

    function loadSLBalances(fund, acctno) {
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'GetSLBalances',
                fund: fund,
                acctno: acctno,
                fiscalYear: currentYear
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    displaySLBalances(res.SL_BALANCES);
                }
            }
        });
    }

    function displaySLBalances(slBalances) {
        let html = '';
        if (slBalances && slBalances.length > 0) {
            $.each(slBalances, function (i, sl) {
                html += '<tr class="sl-row" style="cursor: pointer;" data-slno="' + sl.sl_no + '" data-slname="' + sl.sl_name + '" data-balance="' + sl.balance + '">';
                html += '<td>' + sl.sl_no + '</td>';
                html += '<td>' + sl.sl_name + '</td>';
                html += '<td class="text-end">' + parseFloat(sl.balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="3" class="text-center text-muted">No SL balances found</td></tr>';
        }
        $('#slTableBody').html(html);
        
        // Add click event to rows
        $('.sl-row').on('click', function() {
            let slno = $(this).data('slno');
            let slname = $(this).data('slname');
            let balance = $(this).data('balance');
            
            // Populate edit form
            $('#slNo').val(slno);
            $('#slName').val(slname);
            $('#slBalance').val(balance);
            
            // Highlight selected row
            $('.sl-row').removeClass('table-active');
            $(this).addClass('table-active');
            
            // Scroll to edit form
            $('html, body').animate({
                scrollTop: $('#slNo').offset().top - 100
            }, 500);
        });
    }

    $('#btnSaveSL').on('click', function () {
        let fund = $('#slFundSelect').val();
        let acctno = $('#slAccountCode').val();
        let slNo = $('#slNo').val();
        let slName = $('#slName').val();
        let balance = $('#slBalance').val() || 0;

        if (!fund || !acctno || !slNo || !slName) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please fill in all fields' });
            return;
        }

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'SaveSLBalance',
                fund: fund,
                acctno: acctno,
                slNo: slNo,
                slName: slName,
                balance: balance,
                fiscalYear: currentYear
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.MESSAGE });
                    loadSLBalances(fund, acctno);
                    $('#slNo, #slName, #slBalance').val('');
                    $('.sl-row').removeClass('table-active');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    });

    // Cancel SL editing
    $('#btnCancelSL').on('click', function () {
        // Clear form fields
        $('#slNo, #slName, #slBalance').val('');
        
        // Remove highlight from selected row
        $('.sl-row').removeClass('table-active');
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB 3: YEAR END BALANCE DATA
    // ══════════════════════════════════════════════════════════════════════
    $('#btnLoadYE').on('click', function () {
        let fund = $('#yeFundSelect').val();
        let yearendDate = $('#yeMonth').val();

        if (!fund || !yearendDate) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please select fund and date' });
            return;
        }

        // Show loading
        Swal.fire({
            title: 'Calculating...',
            text: 'Please wait while we calculate year-end balances',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        loadYearEndBalances(fund, yearendDate);
    });

    function loadYearEndBalances(fund, yearendDate) {
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'GetYearEndBalances',
                fund: fund,
                yearendDate: yearendDate
            },
            dataType: 'json',
            success: function (res) {
                Swal.close();
                if (res.STATUS === 'SUCCESS') {
                    displayYearEndBalances(res.ACCOUNTS);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load year-end balances' });
            }
        });
    }

    function displayYearEndBalances(accounts) {
        let html = '';
        if (accounts && accounts.length > 0) {
            $.each(accounts, function (i, acc) {
                let locked = acc.is_locked == 1;
                let rowClass = locked ? 'table-secondary' : 'ye-row';
                let statusBadge = locked ? '<span class="badge bg-danger">Locked</span>' : '<span class="badge bg-success">Unlocked</span>';
                
                html += '<tr class="' + rowClass + '" style="cursor: pointer;" ';
                html += 'data-acctno="' + acc.acctno + '" ';
                html += 'data-accttitle="' + acc.accttitle + '" ';
                html += 'data-balance="' + acc.final_balance + '" ';
                html += 'data-locked="' + locked + '">';
                html += '<td>' + acc.acctno + '</td>';
                html += '<td>' + acc.accttitle + '</td>';
                html += '<td>' + acc.category + '</td>';
                html += '<td class="text-end">' + parseFloat(acc.beginning_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td class="text-end">' + parseFloat(acc.total_debits).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td class="text-end">' + parseFloat(acc.total_credits).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td class="text-end fw-bold">' + parseFloat(acc.final_balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="8" class="text-center text-muted">No accounts found</td></tr>';
        }
        $('#yeTableBody').html(html);
        
        // Add click event to unlocked rows
        $('.ye-row').on('click', function() {
            let acctno = $(this).data('acctno');
            let accttitle = $(this).data('accttitle');
            let balance = $(this).data('balance');
            let locked = $(this).data('locked');
            
            if (locked) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Locked', 
                    text: 'This year-end period is locked. Please unlock before editing.' 
                });
                return;
            }
            
            // Populate edit form
            $('#yeAccountNo').val(acctno);
            $('#yeAccountTitle').val(accttitle);
            $('#yeBeginningBalance').val(balance);
            
            // Highlight selected row
            $('.ye-row').removeClass('table-active');
            $(this).addClass('table-active');
            
            // Scroll to edit form
            $('html, body').animate({
                scrollTop: $('#yeAccountNo').offset().top - 100
            }, 500);
        });
    }

    $('#btnSaveYE').on('click', function () {
        let fund = $('#yeFundSelect').val();
        let yearendDate = $('#yeMonth').val();
        let acctno = $('#yeAccountNo').val();
        let accttitle = $('#yeAccountTitle').val();
        let balance = $('#yeBeginningBalance').val();

        if (!fund || !yearendDate || !acctno) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please fill in all fields' });
            return;
        }

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'SaveYearEndBalance',
                fund: fund,
                acctno: acctno,
                accttitle: accttitle,
                balance: balance,
                yearendDate: yearendDate
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.MESSAGE });
                    loadYearEndBalances(fund, yearendDate);
                    $('#yeAccountNo, #yeAccountTitle, #yeBeginningBalance').val('');
                    $('.ye-row').removeClass('table-active');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    });

    $('#btnCancelYE').on('click', function () {
        $('#yeAccountNo, #yeAccountTitle, #yeBeginningBalance').val('');
        $('.ye-row').removeClass('table-active');
    });

    $('#btnLockYE').on('click', function () {
        let fund = $('#yeFundSelect').val();
        let yearendDate = $('#yeMonth').val();

        if (!fund || !yearendDate) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please select fund and date' });
            return;
        }

        Swal.fire({
            title: 'Lock Year-End?',
            text: 'This will prevent any changes to year-end balances for this period.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, lock it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/iSynApp-main/generalledger/dataconfiguration',
                    type: 'POST',
                    data: {
                        action: 'LockYearEnd',
                        fund: fund,
                        yearendDate: yearendDate
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.STATUS === 'SUCCESS') {
                            Swal.fire({ icon: 'success', title: 'Locked', text: res.MESSAGE });
                            loadYearEndBalances(fund, yearendDate);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                        }
                    }
                });
            }
        });
    });

    $('#btnUnlockYE').on('click', function () {
        let fund = $('#yeFundSelect').val();
        let yearendDate = $('#yeMonth').val();

        if (!fund || !yearendDate) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please select fund and date' });
            return;
        }

        Swal.fire({
            title: 'Unlock Year-End?',
            text: 'This will allow changes to year-end balances for this period.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, unlock it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/iSynApp-main/generalledger/dataconfiguration',
                    type: 'POST',
                    data: {
                        action: 'UnlockYearEnd',
                        fund: fund,
                        yearendDate: yearendDate
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.STATUS === 'SUCCESS') {
                            Swal.fire({ icon: 'success', title: 'Unlocked', text: res.MESSAGE });
                            loadYearEndBalances(fund, yearendDate);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                        }
                    }
                });
            }
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB 4: BUDGET VARIANCE DATA
    // ══════════════════════════════════════════════════════════════════════
    $('#btnLoadBudget').on('click', function () {
        let fund = $('#budgetFundSelect').val();
        let budgetMonth = $('#budgetMonth').val();

        if (!fund || !budgetMonth) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please select fund and month' });
            return;
        }

        loadBudgetData(fund, budgetMonth);
    });

    function loadBudgetData(fund, budgetMonth) {
        Swal.fire({
            title: 'Loading...',
            text: 'Calculating budget variance',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'GetBudgetData',
                fund: fund,
                budgetMonth: budgetMonth + '-01'
            },
            dataType: 'json',
            success: function (res) {
                Swal.close();
                if (res.STATUS === 'SUCCESS') {
                    displayBudgetData(res.ACCOUNTS);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load budget data' });
            }
        });
    }

    function displayBudgetData(accounts) {
        let html = '';
        if (accounts && accounts.length > 0) {
            $.each(accounts, function (i, acc) {
                let variance = parseFloat(acc.variance);
                let variancePercent = parseFloat(acc.variance_percent);
                let statusClass = '';
                let statusBadge = '';
                
                // Determine row color and badge based on status
                if (acc.status === 'Over Budget') {
                    statusClass = 'table-danger';
                    statusBadge = '<span class="badge bg-danger">Over Budget</span>';
                } else if (acc.status === 'Under Budget') {
                    statusClass = 'table-success';
                    statusBadge = '<span class="badge bg-success">Under Budget</span>';
                } else if (acc.status === 'On Track') {
                    statusClass = '';
                    statusBadge = '<span class="badge bg-info">On Track</span>';
                } else {
                    statusClass = 'table-secondary';
                    statusBadge = '<span class="badge bg-secondary">No Budget</span>';
                }
                
                html += '<tr class="budget-row ' + statusClass + '" style="cursor: pointer;" ';
                html += 'data-acctno="' + acc.acctno + '" ';
                html += 'data-accttitle="' + acc.accttitle + '" ';
                html += 'data-budget="' + acc.budget_amount + '">';
                html += '<td>' + acc.acctno + '</td>';
                html += '<td>' + acc.accttitle + '</td>';
                html += '<td>' + acc.category + '</td>';
                html += '<td class="text-end">' + parseFloat(acc.budget_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td class="text-end">' + parseFloat(acc.actual_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td class="text-end ' + (variance > 0 ? 'text-danger' : variance < 0 ? 'text-success' : '') + '">' + variance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>';
                html += '<td class="text-end ' + (variancePercent > 5 ? 'text-danger' : variancePercent < -5 ? 'text-success' : '') + '">' + variancePercent.toFixed(2) + '%</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="8" class="text-center text-muted">No accounts found</td></tr>';
        }
        $('#budgetTableBody').html(html);
        
        // Add click event to rows
        $('.budget-row').on('click', function() {
            let acctno = $(this).data('acctno');
            let accttitle = $(this).data('accttitle');
            let budget = $(this).data('budget');
            
            // Populate edit form
            $('#budgetAccountNo').val(acctno);
            $('#budgetAccountTitle').val(accttitle);
            $('#budgetAmount').val(budget);
            
            // Highlight selected row
            $('.budget-row').removeClass('table-active');
            $(this).addClass('table-active');
            
            // Scroll to edit form
            $('html, body').animate({
                scrollTop: $('#budgetAccountNo').offset().top - 100
            }, 500);
        });
    }

    $('#btnSaveBudget').on('click', function () {
        let fund = $('#budgetFundSelect').val();
        let budgetMonth = $('#budgetMonth').val();
        let acctno = $('#budgetAccountNo').val();
        let accttitle = $('#budgetAccountTitle').val();
        let budgetAmount = $('#budgetAmount').val();

        if (!fund || !budgetMonth || !acctno) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please fill in all fields' });
            return;
        }

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'SaveBudgetAmount',
                fund: fund,
                acctno: acctno,
                accttitle: accttitle,
                budgetAmount: budgetAmount,
                budgetMonth: budgetMonth + '-01'
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.MESSAGE });
                    loadBudgetData(fund, budgetMonth);
                    $('#budgetAccountNo, #budgetAccountTitle, #budgetAmount').val('');
                    $('.budget-row').removeClass('table-active');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    });

    $('#btnCancelBudget').on('click', function () {
        $('#budgetAccountNo, #budgetAccountTitle, #budgetAmount').val('');
        $('.budget-row').removeClass('table-active');
    });

    $('#btnCopyBudget').on('click', function () {
        let fund = $('#budgetFundSelect').val();
        let targetMonth = $('#budgetMonth').val();

        if (!fund || !targetMonth) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please select fund and target month first' });
            return;
        }

        Swal.fire({
            title: 'Copy Budget',
            html: '<label for="sourceMonth" class="form-label">Copy from Month:</label>' +
                  '<input type="month" class="form-control" id="sourceMonth">',
            showCancelButton: true,
            confirmButtonText: 'Copy',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const sourceMonth = document.getElementById('sourceMonth').value;
                if (!sourceMonth) {
                    Swal.showValidationMessage('Please select source month');
                    return false;
                }
                return sourceMonth;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let sourceMonth = result.value;
                
                $.ajax({
                    url: '/iSynApp-main/generalledger/dataconfiguration',
                    type: 'POST',
                    data: {
                        action: 'CopyBudgetToMonth',
                        fund: fund,
                        sourceMonth: sourceMonth + '-01',
                        targetMonth: targetMonth + '-01'
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.STATUS === 'SUCCESS') {
                            Swal.fire({ icon: 'success', title: 'Copied', text: res.MESSAGE });
                            loadBudgetData(fund, targetMonth);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                        }
                    }
                });
            }
        });
    });

    // ══════════════════════════════════════════════════════════════════════
    // TAB 5: PESO DATA
    // ══════════════════════════════════════════════════════════════════════
    function loadPESOData() {
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: { action: 'GetPESOData' },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    displayPESOData(res.PESO_DATA);
                } else {
                    $('#pesoTableBody').html('<tr><td colspan="3" class="text-center text-danger">Failed to load data</td></tr>');
                }
            },
            error: function() {
                $('#pesoTableBody').html('<tr><td colspan="3" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    function displayPESOData(pesoData) {
        let html = '';
        if (pesoData && pesoData.length > 0) {
            $.each(pesoData, function (i, item) {
                html += '<tr>';
                html += '<td>' + item.item_name + '</td>';
                html += '<td>';
                html += '<input type="number" class="form-control form-control-sm peso-value" ';
                html += 'data-item="' + item.item_name + '" ';
                html += 'value="' + parseFloat(item.item_value).toFixed(2) + '" ';
                html += 'step="0.01" style="max-width: 200px;">';
                html += '</td>';
                html += '<td>';
                html += '<button type="button" class="btn btn-sm btn-danger delete-peso" data-item="' + item.item_name + '">';
                html += '<i class="fa-solid fa-trash"></i>';
                html += '</button>';
                html += '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="3" class="text-center text-muted">No PESO data found. Add items below.</td></tr>';
        }
        $('#pesoTableBody').html(html);
    }

    $('#btnSavePESO').on('click', function () {
        let itemName = $('#pesoItem').val().trim();
        let itemValue = $('#pesoAmount').val();

        if (!itemName || !itemValue) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please fill in both item name and value' });
            return;
        }

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'SavePESOData',
                itemName: itemName,
                itemValue: itemValue
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: res.MESSAGE });
                    loadPESOData();
                    $('#pesoItem, #pesoAmount').val('');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    });

    // Quick save from table - update value on change
    $(document).on('change', '.peso-value', function () {
        let itemName = $(this).data('item');
        let itemValue = $(this).val();
        let $input = $(this);

        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'SavePESOData',
                itemName: itemName,
                itemValue: itemValue
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    $input.addClass('border-success');
                    setTimeout(() => $input.removeClass('border-success'), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    });

    // Delete PESO item
    $(document).on('click', '.delete-peso', function (e) {
        e.preventDefault(); // Prevent form submission
        e.stopPropagation(); // Stop event bubbling
        
        let itemName = $(this).data('item');

        Swal.fire({
            title: 'Delete Item?',
            text: 'Are you sure you want to delete "' + itemName + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/iSynApp-main/generalledger/dataconfiguration',
                    type: 'POST',
                    data: {
                        action: 'DeletePESOData',
                        itemName: itemName
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.STATUS === 'SUCCESS') {
                            Swal.fire({ icon: 'success', title: 'Deleted', text: res.MESSAGE });
                            loadPESOData();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                        }
                    }
                });
            }
        });
    });
});
