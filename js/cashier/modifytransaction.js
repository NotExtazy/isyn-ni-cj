const ROUTE_URL = (window.BASE_PATH || '') + '/routes/cashier/modifytransaction.route.php';

let SelectedDate;
let transactionTbl;
let clientno, loanid, nature, fund, cdate;

// ── Auto-open date picker on load ─────────────────────────────────────────
$(document).ready(function () {
    PickDate();
});

function PickDate() {
    Swal.fire({
        title: 'Select Date of Transaction',
        html: '<input id="DateTransaction" readonly class="swal2-input" placeholder="MM/DD/YYYY">',
        confirmButtonText: 'Set',
        showCancelButton: !!SelectedDate,
        cancelButtonText: 'Cancel',
        didOpen: function () {
            $('#DateTransaction').datetimepicker({
                value: new Date(),
                rtl: false,
                format: 'm/d/Y',
                timepicker: false,
                datepicker: true,
                closeOnDateSelect: false,
                closeOnWithoutClick: true,
                closeOnInputClick: true,
                openOnFocus: true,
                mask: '99/99/9999',
            });
        },
        allowOutsideClick: !!SelectedDate,
    }).then(function (result) {
        if (result.isConfirmed) {
            var val = $('#DateTransaction').val();
            if (!val) { PickDate(); return; }
            var date = new Date(val);
            SelectedDate = ((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1)))
                + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate()))
                + '/' + date.getFullYear();
            $('#selectedDateLabel').text(SelectedDate);
            LoadInit();
        }
    });
}

function ChangeDate() {
    PickDate();
}

// ── Load OR Types ─────────────────────────────────────────────────────────
function LoadInit() {
    console.log('[modifytransaction] LoadInit called, ROUTE_URL=', ROUTE_URL);
    $.ajax({
        url: ROUTE_URL,
        type: 'POST',
        data: { action: 'LoadORTypes' },
        success: function (raw) {
            console.log('[modifytransaction] LoadORTypes raw response:', raw);
            var response;
            try { response = (typeof raw === 'string') ? JSON.parse(raw) : raw; }
            catch(e) { console.error('JSON parse error:', e, raw); return; }
            $('#orTypes').empty().append("<option value='' selected disabled> Select OR Type</option>");
            if (response.ORTYPES && response.ORTYPES.length > 0) {
                $.each(response.ORTYPES, function (_k, v) {
                    var ortype = v['ORType'] || v['ORTYPE'] || Object.values(v)[0];
                    $('#orTypes').append(`<option value="${ortype}">${ortype}</option>`);
                });
            } else {
                console.warn('[modifytransaction] No OR types returned');
            }
        },
        error: function (xhr, status, err) { console.error('LoadORTypes error', status, err, xhr.responseText); }
    });
}

// ── Load Transactions ─────────────────────────────────────────────────────
function LoadTransactions(type) {
    $.ajax({
        url: ROUTE_URL,
        type: 'POST',
        data: { action: 'LoadTransactions', type: type, SelectedDate: SelectedDate },
        dataType: 'JSON',
        beforeSend: function () {
            $('#transactionList').html("<tr><td colspan='7'>Loading...</td></tr>");
        },
        success: function (response) {
            if ($.fn.DataTable.isDataTable('#transactionTbl')) {
                $('#transactionTbl').DataTable().clear().destroy();
            }
            $('#transactionList').empty();
            $.each(response.ORLIST, function (_k, v) {
                $('#transactionList').append(`
                    <tr>
                        <td>${v['ORNo'] || v['ORNO'] || ''}</td>
                        <td>${v['Payee'] || v['PAYEE'] || ''}</td>
                        <td>${v['ClientNo'] || v['CLIENTNO'] || ''}</td>
                        <td>${v['LoanID'] || v['LOANID'] || ''}</td>
                        <td>${v['Nature'] || v['NATURE'] || ''}</td>
                        <td>${v['Fund'] || v['FUND'] || ''}</td>
                        <td>${v['CDate'] || v['CDATE'] || ''}</td>
                    </tr>`);
            });
            resetform();
            transactionTbl = $('#transactionTbl').DataTable({
                scrollY: '250px', scrollX: true, scrollCollapse: true,
                paging: false, bFilter: false, info: true,
            });
        },
        error: function (err) { console.error('LoadTransactions error', err); }
    });
}

// ── Row click ─────────────────────────────────────────────────────────────
$('#transactionTbl tbody').on('click', 'tr', function (e) {
    if (!transactionTbl || transactionTbl.rows().count() === 0) return;
    var classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
        $('#deleteTransaction, #cancelTransaction').prop('disabled', true);
        resetform();
    } else {
        transactionTbl.rows('.selected').nodes().each(function (row) { row.classList.remove('selected'); });
        classList.add('selected');

        var rowData = transactionTbl.row(this).data();
        var orno = rowData[0];
        clientno = rowData[2];
        loanid   = rowData[3];
        nature   = rowData[4];
        fund     = rowData[5];
        cdate    = rowData[6];

        var isLoanAmort = (nature === 'LOAN AMORTIZATION');
        $('#deleteTransaction').prop('disabled', isLoanAmort);
        $('#cancelTransaction').prop('disabled', isLoanAmort);

        $.ajax({
            url: ROUTE_URL,
            type: 'POST',
            data: { action: 'GetORData', orno: orno, cdate: cdate },
            dataType: 'JSON',
            success: function (response) {
                $('#orno').val(orno);
                $('#fund').val(response.FUND);
                $('#po').val(response.PO);
                $('#nature').val(response.NATURE);
                $('#principal').val(response.PRINCIPAL);
                $('#interest').val(response.INTEREST);
                $('#cbu').val(response.CBU);
                $('#penalty').val(response.PENALTY);
                $('#mba').val(response.MBA);
                $('#total').val(response.TOTAL);
            },
            error: function (err) { console.error('GetORData error', err); }
        });
    }
});

// ── Cancel Transaction ────────────────────────────────────────────────────
function CancelTransaction() {
    var type = $('#orTypes').val(), orno = $('#orno').val(),
        f = $('#fund').val(), po = $('#po').val(), nat = $('#nature').val();
    if (!orno || !f || !po || !nat) {
        Swal.fire({ icon: 'warning', title: 'Missing Payment Details.' }); return;
    }
    Swal.fire({
        icon: 'question', title: 'Cancel this payment transaction?',
        showCancelButton: true, confirmButtonColor: '#435ebe',
        confirmButtonText: 'Yes, proceed!', showLoaderOnConfirm: true,
        preConfirm: function () {
            return $.ajax({
                url: ROUTE_URL, type: 'POST', dataType: 'JSON',
                data: { action: 'CancelTransaction', orno, fund: f, po, nature: nat, clientno, loanid, cdate },
                success: function (r) { if (r.STATUS === 'SUCCESS') { LoadTransactions(type); resetform(); } }
            });
        }
    }).then(function (result) {
        if (result.isConfirmed) {
            Swal.fire(result.value.STATUS === 'SUCCESS'
                ? { icon: 'success', text: 'Transaction cancelled.' }
                : { icon: 'warning', text: 'Failed to cancel transaction.' });
        }
    });
}

// ── Archive Transaction ───────────────────────────────────────────────────
function ArchiveTransaction() {
    var type = $('#orTypes').val(), orno = $('#orno').val(),
        f = $('#fund').val(), po = $('#po').val(), nat = $('#nature').val();
    if (!orno || !f || !po || !nat) {
        Swal.fire({ icon: 'warning', title: 'Missing Payment Details.' }); return;
    }
    Swal.fire({
        icon: 'question', title: 'Archive this payment transaction?',
        showCancelButton: true, confirmButtonColor: '#435ebe',
        confirmButtonText: 'Yes, proceed!', showLoaderOnConfirm: true,
        preConfirm: function () {
            return $.ajax({
                url: ROUTE_URL, type: 'POST', dataType: 'JSON',
                data: { action: 'ArchiveTransaction', orno, cdate, fund: f, po },
                success: function (r) { if (r.STATUS === 'SUCCESS') { LoadTransactions(type); resetform(); } }
            });
        }
    }).then(function (result) {
        if (result.isConfirmed) {
            Swal.fire(result.value.STATUS === 'SUCCESS'
                ? { icon: 'success', text: 'Transaction archived.' }
                : { icon: 'warning', text: 'Failed to archive transaction.' });
        }
    });
}

function resetform() {
    $('#orno,#fund,#po,#nature,#principal,#interest,#cbu,#penalty,#mba,#total').val('');
    $('#deleteTransaction,#cancelTransaction').prop('disabled', true);
}
