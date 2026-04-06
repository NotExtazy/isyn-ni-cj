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
                if (res.STATUS === 'SUCCESS') {
                    currentFunds = res.FUNDS || [];
                    currentYear = res.CURRENT_YEAR;
                    populateFundDropdowns();
                    loadPESOData(); // Load PESO data on page load
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load page data' });
            }
        });
    }

    function populateFundDropdowns() {
        let opts = '<option value="">Select Fund</option>';
        $.each(currentFunds, function (i, f) {
            opts += '<option value="' + f.fundname + '">' + f.fundname + '</option>';
        });
        $('#bbFundSelect, #slFundSelect, #yeFundSelect, #budgetFundSelect').html(opts);
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
                if (res.STATUS === 'SUCCESS') {
                    displayYearEndBalances(res.ACCOUNTS);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    }

    function displayYearEndBalances(accounts) {
        let html = '';
        if (accounts && accounts.length > 0) {
            $.each(accounts, function (i, acc) {
                let locked = acc.is_locked == 1;
                html += '<tr' + (locked ? ' class="table-secondary"' : '') + '>';
                html += '<td>' + acc.acctno + '</td>';
                html += '<td>' + acc.accttitle + '</td>';
                html += '<td><input type="number" class="form-control form-control-sm ye-balance" data-acctno="' + acc.acctno + '" data-accttitle="' + acc.accttitle + '" value="' + acc.yearend_balance + '" step="0.01"' + (locked ? ' disabled' : '') + '></td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="3" class="text-center text-muted">No accounts found</td></tr>';
        }
        $('#yeTableBody').html(html);
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
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
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
        $.ajax({
            url: '/iSynApp-main/generalledger/dataconfiguration',
            type: 'POST',
            data: {
                action: 'GetBudgetData',
                fund: fund,
                budgetMonth: budgetMonth
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    displayBudgetData(res.ACCOUNTS);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
            }
        });
    }

    function displayBudgetData(accounts) {
        let html = '';
        if (accounts && accounts.length > 0) {
            $.each(accounts, function (i, acc) {
                html += '<tr>';
                html += '<td>' + acc.acctno + '</td>';
                html += '<td>' + acc.accttitle + '</td>';
                html += '<td><input type="number" class="form-control form-control-sm budget-amount" data-acctno="' + acc.acctno + '" data-accttitle="' + acc.accttitle + '" value="' + acc.budget_amount + '" step="0.01"></td>';
                html += '<td>' + parseFloat(acc.actual_amount).toFixed(2) + '</td>';
                html += '<td>' + parseFloat(acc.variance).toFixed(2) + '</td>';
                html += '</tr>';
            });
        } else {
            html = '<tr><td colspan="5" class="text-center text-muted">No accounts found</td></tr>';
        }
        $('#budgetTableBody').html(html);
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
                action: 'SaveBudgetData',
                fund: fund,
                acctno: acctno,
                accttitle: accttitle,
                budgetAmount: budgetAmount,
                budgetMonth: budgetMonth
            },
            dataType: 'json',
            success: function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Saved', 
                        html: res.MESSAGE + '<br>Actual: ' + parseFloat(res.ACTUAL_AMOUNT).toFixed(2) + '<br>Variance: ' + parseFloat(res.VARIANCE).toFixed(2)
                    });
                    loadBudgetData(fund, budgetMonth);
                    $('#budgetAccountNo, #budgetAccountTitle, #budgetAmount').val('');
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.MESSAGE });
                }
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
                }
            }
        });
    }

    function displayPESOData(pesoData) {
        let html = '';
        if (pesoData && pesoData.length > 0) {
            $.each(pesoData, function (i, item) {
                html += '<tr>';
                html += '<td>' + item.item_name + '</td>';
                html += '<td><input type="number" class="form-control form-control-sm peso-value" data-item="' + item.item_name + '" value="' + item.item_value + '" step="0.01"></td>';
                html += '</tr>';
            });
        }
        $('#pesoTableBody').html(html);
    }

    $('#btnSavePESO').on('click', function () {
        let itemName = $('#pesoItem').val();
        let itemValue = $('#pesoAmount').val();

        if (!itemName || !itemValue) {
            Swal.fire({ icon: 'warning', title: 'Missing Data', text: 'Please fill in all fields' });
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

    // Quick save from table
    $(document).on('change', '.peso-value', function () {
        let itemName = $(this).data('item');
        let itemValue = $(this).val();

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
                    $(this).addClass('border-success');
                    setTimeout(() => $(this).removeClass('border-success'), 1000);
                }
            }.bind(this)
        });
    });
});
