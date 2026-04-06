const ROUTE_URL = (window.BASE_PATH || '') + '/routes/accountsmonitoring/depreciation.route.php';

var equipmentTable = null;
var editingId = null;

function numberFmt(n) {
    return parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function f2(n) { return parseFloat(n || 0).toFixed(2); }

// Success message function
function showSuccessMessage(message) {
    var successMsg = document.createElement('div');
    successMsg.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:#fff;padding:15px 25px;border-radius:8px;z-index:99999;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.3);animation:slideIn 0.3s ease-out';
    successMsg.innerHTML = '<i class="fa fa-check-circle me-2"></i>' + message;
    
    // Add animation
    var style = document.createElement('style');
    style.textContent = '@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
    document.head.appendChild(style);
    
    document.body.appendChild(successMsg);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        successMsg.style.transition = 'opacity 0.3s ease-out';
        successMsg.style.opacity = '0';
        setTimeout(function() {
            if (successMsg.parentNode) {
                document.body.removeChild(successMsg);
            }
        }, 300);
    }, 3000);
}

// ── Init ─────────────────────────────────────────────────────────────────
$(document).ready(function () {
    LoadPage();
    setAssetFilter('Fur Fix Equip');
});

function LoadPage() {
    $.post(ROUTE_URL, { action: 'LoadPage' }, function (res) {
        if (res.DB_ERROR) console.error('DB Error:', res.DB_ERROR);
        $('#selBranch, #filterBranch').empty().append('<option value="">All</option>');
        $.each(res.BRANCHES || [], function (i, v) {
            $('#selBranch').append('<option>' + (v.Value || v.Branch || '') + '</option>');
        });
        $('#selFund, #jvFund').empty().append('<option value="">Select</option>');
        $.each(res.FUNDS || [], function (i, v) {
            $('#selFund, #jvFund').append('<option>' + v.Fund + '</option>');
        });
    }, 'json').fail(function (xhr) {
        console.error('LoadPage error:', xhr.responseText);
    });
}

// ── Equipment List ────────────────────────────────────────────────────────
// Global store for print/export — holds all DB rows regardless of pagination
var _printData = { list: [], table: '' };

function LoadEquipmentList() {
    var assetType = $('#filterAssets').val() || '';
    var status    = $('#selType').val() || '';
    $.post(ROUTE_URL, { action: 'LoadEquipmentList', AssetType: assetType, Status: status }, function (res) {
        if (equipmentTable && $.fn.DataTable.isDataTable('#equipmentTbl')) {
            equipmentTable.clear().destroy();
        }
        $('#equipmentBody').empty();
        var tbl = res.TABLE;

        // Store all data globally for full export
        _printData.list  = res.LIST || [];
        _printData.table = tbl;
        
        // Calculate AccumDeprPrevYear and DeprThisYear if they don't exist
        _printData.list.forEach(function(item) {
            // If fields don't exist, calculate them
            if (typeof item.AccumDeprPrevYear === 'undefined' || item.AccumDeprPrevYear === null) {
                // AccumDeprPrevYear = AccumDeprAsOfDate - LapTotal (this year's depreciation)
                var lapTotal = parseFloat(item.LapTotal || 0);
                var accumDepr = parseFloat(item.AccumDeprAsOfDate || 0);
                item.AccumDeprPrevYear = accumDepr - lapTotal;
            }
            if (typeof item.DeprThisYear === 'undefined' || item.DeprThisYear === null) {
                // DeprThisYear = LapTotal (sum of all lapsing months this year)
                item.DeprThisYear = parseFloat(item.LapTotal || 0);
            }
        });

        var thead = '';
        if (tbl === 'furniture') {
            thead = '<tr><th></th><th>Description</th><th>Property No</th><th>Date Acquired</th><th>Ref No</th><th>No of Units</th><th>Est Useful Life</th><th>No of Months</th><th>Total Cost/Transferred</th><th>Total Cost</th><th>Monthly Dep</th><th>Net Book Value</th><th>Status</th><th>Depr This Year</th><th>Accum Dep As Of Date</th></tr>';
        } else if (tbl === 'transpo') {
            thead = '<tr><th></th><th>Description</th><th>Property No</th><th>Date Acquired</th><th>Ref No</th><th>No of Units</th><th>Est Useful Life</th><th>No of Months</th><th>Acquisition Cost</th><th>Total Cost</th><th>Monthly Dep</th><th>Net Book Value</th><th>Status</th><th>Depr This Year</th><th>Accum Dep As Of Date</th></tr>';
        } else if (tbl === 'leasehold') {
            thead = '<tr><th></th><th>Description</th><th>Property No</th><th>Date Acquired</th><th>Ref No</th><th>No of Units</th><th>Est Useful Life</th><th>No of Months</th><th>Acquisition Cost</th><th>Total Cost</th><th>Monthly Dep</th><th>Net Book Value</th><th>Status</th><th>Depr This Year</th><th>Accum Dep As Of Date</th></tr>';
        } else {
            thead = '<tr><th></th><th>Description</th><th>Property No</th><th>Asset Type</th><th>Date Acquired</th><th>Est Useful Life</th><th>No of Months</th><th>Total Cost</th><th>Monthly Dep</th><th>Net Book Value</th><th>Status</th><th>Depr This Year</th><th>Accum Dep As Of Date</th></tr>';
        }
        $('#equipmentTbl thead').html(thead);

        $.each(res.LIST, function (i, v) {
            var eyeBtn = '<button class="btn btn-sm btn-outline-info py-0 px-1 view-btn" title="View Details"><i class="fa-solid fa-eye"></i></button>';
            var row = '';
            if (tbl === 'furniture') {
                row = '<tr data-id="' + v.ID + '">' +
                    '<td>' + eyeBtn + '</td>' +
                    '<td>' + (v.Description||'-') + '</td><td>' + (v.PropertyNo||'-') + '</td>' +
                    '<td>' + (v.DateAcquired||'-') + '</td><td>' + (v.RefNo||'-') + '</td>' +
                    '<td>' + (v.NoOfUnits||'-') + '</td>' +
                    '<td>' + (v.EstUsefulLife||'-') + '</td><td>' + (v.NoOfMonths||'-') + '</td>' +
                    '<td>' + numberFmt(v.TotalCostTransferred) + '</td><td>' + numberFmt(v.TotalCost) + '</td>' +
                    '<td>' + numberFmt(v.MonthlyDepr) + '</td><td>' + numberFmt(v.NetBookValue) + '</td>' +
                    '<td>' + (v.Status||'-') + '</td><td>' + numberFmt(v.DeprThisYear) + '</td>' +
                    '<td>' + numberFmt(v.AccumDeprAsOfDate) + '</td></tr>';
            } else if (tbl === 'transpo') {
                row = '<tr data-id="' + v.ID + '">' +
                    '<td>' + eyeBtn + '</td>' +
                    '<td>' + (v.Description||'-') + '</td><td>' + (v.PropertyNo||'-') + '</td>' +
                    '<td>' + (v.DateAcquired||'-') + '</td><td>' + (v.RefNo||'-') + '</td>' +
                    '<td>' + (v.NoOfUnits||'-') + '</td>' +
                    '<td>' + (v.EstUsefulLife||'-') + '</td><td>' + (v.NoOfMonths||'-') + '</td>' +
                    '<td>' + numberFmt(v.AcquisitionCost) + '</td>' +
                    '<td>' + numberFmt(v.TotalCost) + '</td><td>' + numberFmt(v.MonthlyDepr) + '</td>' +
                    '<td>' + numberFmt(v.NetBookValue) + '</td><td>' + (v.Status||'-') + '</td>' +
                    '<td>' + numberFmt(v.DeprThisYear) + '</td><td>' + numberFmt(v.AccumDeprAsOfDate) + '</td></tr>';
            } else if (tbl === 'leasehold') {
                row = '<tr data-id="' + v.ID + '">' +
                    '<td>' + eyeBtn + '</td>' +
                    '<td>' + (v.Description||'-') + '</td><td>' + (v.PropertyNo||'-') + '</td>' +
                    '<td>' + (v.DateAcquired||'-') + '</td><td>' + (v.RefNo||'-') + '</td>' +
                    '<td>' + (v.NoOfUnits||'-') + '</td>' +
                    '<td>' + (v.EstUsefulLife||'-') + '</td><td>' + (v.NoOfMonths||'-') + '</td>' +
                    '<td>' + numberFmt(v.AcquisitionCost) + '</td><td>' + numberFmt(v.TotalCost) + '</td>' +
                    '<td>' + numberFmt(v.MonthlyDepr) + '</td><td>' + numberFmt(v.NetBookValue) + '</td>' +
                    '<td>' + (v.Status||'-') + '</td><td>' + numberFmt(v.DeprThisYear) + '</td>' +
                    '<td>' + numberFmt(v.AccumDeprAsOfDate) + '</td></tr>';
            } else {
                row = '<tr data-id="' + v.ID + '">' +
                    '<td>' + eyeBtn + '</td>' +
                    '<td>' + (v.AssetName||v.Description||'-') + '</td><td>' + (v.PropertyNo||'-') + '</td>' +
                    '<td>' + (v.AssetType||'-') + '</td><td>' + (v.DateAcquired||'-') + '</td>' +
                    '<td>' + (v.EstUsefulLife||'-') + '</td><td>' + (v.NoOfMonths||'-') + '</td>' +
                    '<td>' + numberFmt(v.TotalCost) + '</td>' +
                    '<td>' + numberFmt(v.MonthlyDep||v.MonthlyDepr) + '</td>' +
                    '<td>' + numberFmt(v.NetBookValue) + '</td><td>' + (v.Status||'-') + '</td>' +
                    '<td>' + numberFmt(v.DeprThisYear) + '</td><td>' + numberFmt(v.AccumDeprAsOfDate) + '</td></tr>';
            }
            var $tr = $(row);
            $tr.data('row', v);  // store object directly — safe from quote issues
            $('#equipmentBody').append($tr);
        });

        equipmentTable = $('#equipmentTbl').DataTable({
            paging: true, pageLength: 10, lengthChange: false, info: true, dom: 'tip'
        });

        $('#jvEquipmentList').empty();
        $.each(res.LIST, function (i, v) {
            var name = v.AssetName || v.Description || '-';
            var dep  = v.MonthlyDep || v.MonthlyDepr || 0;
            $('#jvEquipmentList').append(
                '<tr><td><input type="checkbox" class="jv-check" value="' + v.ID + '"></td>' +
                '<td>' + name + '</td><td>' + numberFmt(dep) + '</td></tr>');
        });
    }, 'json');
}

$('#equipmentTbl tbody').on('click', 'tr', function (e) {
    if ($(e.target).closest('.view-btn').length) return;
    $('#equipmentTbl tbody tr').removeClass('selected');
    $(this).addClass('selected');
});

$(document).on('click', '.view-btn', function (e) {
    e.stopPropagation();
    viewRow(this);
});

// ── Add / Edit Modal ──────────────────────────────────────────────────────
function openAddModal() {
    new bootstrap.Modal(document.getElementById('typePickerModal')).show();
}

function pickType(assetType) {
    var modalMap = {
        'Fur Fix Equip':     'modalFurniture',
        'Transpo Equipment': 'modalTranspo',
        'Leasehold Imp':     'modalLeasehold'
    };
    var modalId = modalMap[assetType];
    if (!modalId) return;

    $('#' + modalId + ' input[type=text], #' + modalId + ' input[type=date]').val('');
    $('#' + modalId + ' input[type=number]').each(function () {
        $(this).val($(this).attr('value') || 0);
    });
    if (assetType === 'Fur Fix Equip')     $('#ff_id').val('');
    if (assetType === 'Transpo Equipment') $('#tr_id').val('');
    if (assetType === 'Leasehold Imp')     $('#lh_id').val('');

    var typeMap = { 'Fur Fix Equip': 'furniture', 'Transpo Equipment': 'transpo', 'Leasehold Imp': 'leasehold' };
    $.post(ROUTE_URL, { action: 'GetNextTransactionID', PPEType: typeMap[assetType] }, function (res) {
        if (res.STATUS === 'SUCCESS') {
            if (assetType === 'Fur Fix Equip')     $('#ff_transactionid').val(res.TransactionID);
            if (assetType === 'Transpo Equipment') $('#tr_transactionid').val(res.TransactionID);
            if (assetType === 'Leasehold Imp')     $('#lh_transactionid').val(res.TransactionID);
        }
    }, 'json');

    var pickerEl = document.getElementById('typePickerModal');
    var pickerInstance = bootstrap.Modal.getInstance(pickerEl);
    if (document.activeElement) document.activeElement.blur();

    $(pickerEl).one('hidden.bs.modal', function () {
        var targetEl = document.getElementById(modalId);
        if (targetEl) new bootstrap.Modal(targetEl).show();
    });

    if (pickerInstance) {
        pickerInstance.hide();
    } else {
        var targetEl = document.getElementById(modalId);
        if (targetEl) new bootstrap.Modal(targetEl).show();
    }
}

// ── Auto-compute wiring (jQuery input events) ────────────────────────────
$(document).on('input change', '#ff_dateacquired', function () {
    autoSetMonthStarted('ff_', this.value);
    calcFF();
});
$(document).on('input change', '#tr_dateacquired', function () {
    autoSetMonthStarted('tr_', this.value);
    calcTR();
});
$(document).on('input change', '#lh_dateacquired', function () {
    autoSetMonthStarted('lh_', this.value);
    calcLH();
});

function autoSetMonthStarted(prefix, dateVal) {
    if (!dateVal) return;
    var d = new Date(dateVal);
    d.setMonth(d.getMonth() + 1);
    var yyyy = d.getFullYear();
    var mm   = String(d.getMonth() + 1).padStart(2, '0');
    $('#' + prefix + 'monthstarteddepr').val(yyyy + '-' + mm);
}

$(document).on('input change',
    '#ff_totalcosttransferred, #ff_noofunits, #ff_estusefullife, ' +
    '#ff_disposaltransferout, #ff_disposalreclass, #ff_accumdeprprevyear, #ff_monthstarteddepr',
    function() { calcFF(); }
);
$(document).on('input change',
    '#tr_acquisitioncost, #tr_noofunits, #tr_estusefullife, #tr_accumdeprprevyear, #tr_monthstarteddepr',
    function() { calcTR(); }
);
$(document).on('input change',
    '#lh_acquisitioncost, #lh_noofunits, #lh_estusefullife, #lh_accumdeprprevyear, #lh_monthstarteddepr',
    function() { calcLH(); }
);

// Returns true if monthstarteddepr (YYYY-MM) is strictly in the future (next month or later)
function isDeprNotStarted(prefix) {
    var val = $('#' + prefix + 'monthstarteddepr').val(); // YYYY-MM
    if (!val) return false;
    var now = new Date();
    // Use next month as threshold — if field month > current month, depr hasn't started
    var nextMonth = new Date(now.getFullYear(), now.getMonth() + 1, 1);
    var nextYM = nextMonth.getFullYear() * 100 + (nextMonth.getMonth() + 1);
    var parts = val.split('-');
    var fieldYM = parseInt(parts[0]) * 100 + parseInt(parts[1]);
    return fieldYM >= nextYM;
}
// Returns how many months have elapsed from monthstarteddepr to today,
// capped at maxMonths (total useful life). Returns 0 if not started yet.
function elapsedMonths(prefix, maxMonths) {
    var val = $('#' + prefix + 'monthstarteddepr').val(); // YYYY-MM
    if (!val || isDeprNotStarted(prefix)) return 0;
    var parts  = val.split('-');
    var startY = parseInt(parts[0]);
    var startM = parseInt(parts[1]);
    var now    = new Date();
    var elapsed = (now.getFullYear() - startY) * 12 + (now.getMonth() + 1 - startM) + 1;
    return Math.min(Math.max(elapsed, 0), maxMonths);
}
function calcFF() {
    var p = 'ff_';
    var units     = parseFloat($('#'+p+'noofunits').val())             || 1;
    var tct       = parseFloat($('#'+p+'totalcosttransferred').val())  || 0;
    var reclass   = parseFloat($('#'+p+'disposalreclass').val())       || 0;
    var life      = Math.max(1, parseFloat($('#'+p+'estusefullife').val()) || 5);
    var prevAccum = parseFloat($('#'+p+'accumdeprprevyear').val())     || 0;

    var totalCost = tct * units;
    $('#'+p+'totalcost').val(f2(totalCost));

    var months = life * 12;
    $('#'+p+'noofmonths').val(months);

    var monthly = months > 0 ? (totalCost - units) / months : 0;
    $('#'+p+'monthlydepr').val(f2(monthly));

    var msd = $('#'+p+'monthstarteddepr').val();
    var laps = buildLapsing(monthly, months, msd);
    var lapKeys = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
    lapKeys.forEach(function(m, i) { $('#'+p+'lap'+m).val(f2(laps[i])); });

    // LapTotal = monthly × all elapsed months from start to today (capped at NoOfMonths)
    var lapTotal = monthly * elapsedSinceStart(msd, months);
    $('#'+p+'laptotal').val(f2(lapTotal));

    var deprThisYear = calcDeprThisYear(msd, monthly, months);
    $('#'+p+'deprthisyear').val(f2(deprThisYear));

    var accumAsOf = lapTotal - reclass;
    $('#'+p+'accumdeprasofdate').val(f2(accumAsOf));
    $('#'+p+'netbookvalue').val(f2(totalCost - accumAsOf));
}

function calcTR() {
    var p = 'tr_';
    var acq       = parseFloat($('#'+p+'acquisitioncost').val())   || 0;
    var units     = parseFloat($('#'+p+'noofunits').val())         || 1;
    var life      = Math.max(1, parseFloat($('#'+p+'estusefullife').val()) || 5);
    var prevAccum = parseFloat($('#'+p+'accumdeprprevyear').val()) || 0;

    var totalCost = acq * units;
    $('#'+p+'totalcost').val(f2(totalCost));

    var months = life * 12;
    $('#'+p+'noofmonths').val(months);

    var monthly = months > 0 ? (totalCost - units) / months : 0;
    $('#'+p+'monthlydepr').val(f2(monthly));

    var msd = $('#'+p+'monthstarteddepr').val();
    var laps = buildLapsing(monthly, months, msd);
    var lapKeys = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
    lapKeys.forEach(function(m, i) { $('#'+p+'lap'+m).val(f2(laps[i])); });

    var lapTotal = monthly * elapsedSinceStart(msd, months);
    $('#'+p+'laptotal').val(f2(lapTotal));

    var deprThisYear = calcDeprThisYear(msd, monthly, months);
    $('#'+p+'deprthisyear').val(f2(deprThisYear));

    var accumAsOf = lapTotal;
    $('#'+p+'accumdeprasofdate').val(f2(accumAsOf));
    $('#'+p+'netbookvalue').val(f2(totalCost - accumAsOf));
}

function calcLH() {
    var p = 'lh_';
    var acq       = parseFloat($('#'+p+'acquisitioncost').val())   || 0;
    var units     = parseFloat($('#'+p+'noofunits').val())         || 1;
    var life      = Math.max(1, parseFloat($('#'+p+'estusefullife').val()) || 5);
    var prevAccum = parseFloat($('#'+p+'accumdeprprevyear').val()) || 0;

    var totalCost = acq * units;
    $('#'+p+'totalcost').val(f2(totalCost));

    var months = life * 12;
    $('#'+p+'noofmonths').val(months);

    var monthly = months > 0 ? (totalCost - units) / months : 0;
    $('#'+p+'monthlydepr').val(f2(monthly));

    var msd = $('#'+p+'monthstarteddepr').val();
    var laps = buildLapsing(monthly, months, msd);
    var lapKeys = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];
    lapKeys.forEach(function(m, i) { $('#'+p+'lap'+m).val(f2(laps[i])); });

    var lapTotal = monthly * elapsedSinceStart(msd, months);
    $('#'+p+'laptotal').val(f2(lapTotal));

    var deprThisYear = calcDeprThisYear(msd, monthly, months);
    $('#'+p+'deprthisyear').val(f2(deprThisYear));

    var accumAsOf = lapTotal;
    $('#'+p+'accumdeprasofdate').val(f2(accumAsOf));
    $('#'+p+'netbookvalue').val(f2(totalCost - accumAsOf));
}

// Count months elapsed from MonthStartedDepr up to today, capped at noOfMonths.
// Returns 0 if not started yet (present/future).
function elapsedSinceStart(msd, noOfMonths) {
    if (!msd) return 0;
    var now   = new Date();
    var curYM = now.getFullYear() * 100 + (now.getMonth() + 1);
    var parts = msd.split('-');
    var sYear = parseInt(parts[0]), sMon = parseInt(parts[1]);
    var startYM = sYear * 100 + sMon;
    if (startYM >= curYM) return 0;
    // Completed months = from startMon up to last month (current month is still in progress)
    // now.getMonth()+1 = current month (1-based); subtract 1 to get last completed month
    var lastCompletedMon = now.getMonth(); // 0-based = last completed month number (1-based)
    var elapsed = (now.getFullYear() - sYear) * 12 + (lastCompletedMon - sMon + 1);
    return Math.min(Math.max(elapsed, 0), parseInt(noOfMonths) || 0);
}

// Depr This Year = monthly × completed months in current year where depr was active, capped at noOfMonths
function calcDeprThisYear(msd, monthly, noOfMonths) {
    if (!msd) return 0;
    var now      = new Date();
    var curYear  = now.getFullYear();
    var lastMon  = now.getMonth(); // 0-based; last completed month = lastMon (1-based)
    var parts    = msd.split('-');
    var sYear    = parseInt(parts[0]), sMon = parseInt(parts[1]);
    var startYM  = sYear * 100 + sMon;
    var curYM    = curYear * 100 + (now.getMonth() + 1);
    if (startYM >= curYM) return 0;
    var maxMonths = parseInt(noOfMonths) || 0;
    var count = 0;
    // Jan(1) through lastMon (1-based) of current year
    for (var m = 1; m <= lastMon; m++) {
        // monthsFromStart=1 means this is the 1st depr month (sMon of sYear)
        var monthsFromStart = (curYear - sYear) * 12 + (m - sMon) + 1;
        if (monthsFromStart >= 1 && monthsFromStart <= maxMonths) count++;
    }
    return monthly * count;
}

// Build lapsing display row (start-year months only, zeros for present/future)
function buildLapsing(monthly, noOfMonths, msd) {
    var laps = [];
    var now   = new Date();
    var curYM = now.getFullYear() * 100 + (now.getMonth() + 1);

    if (!msd) {
        for (var i = 0; i < 12; i++) laps.push(0);
        return laps;
    }

    var parts     = msd.split('-');
    var startYear = parseInt(parts[0]);
    var startMon  = parseInt(parts[1]);
    var startYM   = startYear * 100 + startMon;

    // Present or future start → all zeros (Update button handles it)
    if (startYM >= curYM) {
        for (var i = 0; i < 12; i++) laps.push(0);
        return laps;
    }

    var totalMonths = parseInt(noOfMonths) || 0;

    // Fill months of the start year: active from startMon up to (but not including) current month
    var curMon = (startYear === now.getFullYear()) ? now.getMonth() : 12; // getMonth() = 0-based, so this is last completed month
    for (var m = 1; m <= 12; m++) {
        var monthsFromStart = m - startMon;
        if (m >= startMon && m <= curMon && monthsFromStart < totalMonths) {
            laps.push(monthly);
        } else {
            laps.push(0);
        }
    }
    return laps;
}

// ── Save PPE (all 3 types) ────────────────────────────────────────────────
function SavePPE(type) {
    var data = { action: 'SavePPE', PPEType: type };

    if (type === 'furniture') {
        data.ID                  = $('#ff_id').val();
        data.TransactionID       = $('#ff_transactionid').val();
        data.Description         = $('#ff_description').val();
        data.DateAcquired        = $('#ff_dateacquired').val();
        data.RefNo               = $('#ff_refno').val();
        data.NoOfUnits           = $('#ff_noofunits').val();
        data.TotalCost           = $('#ff_totalcost').val();
        data.TotalCostTransferred= $('#ff_totalcosttransferred').val();
        data.DisposalTransferOut = $('#ff_disposaltransferout').val();
        data.DisposalReclass     = $('#ff_disposalreclass').val();
        data.EstUsefulLife       = $('#ff_estusefullife').val();
        data.NoOfMonths          = $('#ff_noofmonths').val();
        data.MonthStartedDepr    = $('#ff_monthstarteddepr').val();
        data.MonthlyDepr         = $('#ff_monthlydepr').val();
        data.AccumDeprPrevYear   = $('#ff_accumdeprprevyear').val();
        data.DeprThisYear        = $('#ff_deprthisyear').val();
        data.AccumDeprAsOfDate   = $('#ff_accumdeprasofdate').val();
        data.NetBookValue        = $('#ff_netbookvalue').val();
        data.LapJan = $('#ff_lapjan').val(); data.LapFeb = $('#ff_lapfeb').val();
        data.LapMar = $('#ff_lapmar').val(); data.LapApr = $('#ff_lapapr').val();
        data.LapMay = $('#ff_lapmay').val(); data.LapJun = $('#ff_lapjun').val();
        data.LapJul = $('#ff_lapjul').val(); data.LapAug = $('#ff_lapaug').val();
        data.LapSep = $('#ff_lapsep').val(); data.LapOct = $('#ff_lapoct').val();
        data.LapNov = $('#ff_lapnov').val(); data.LapDec = $('#ff_lapdec').val();
        data.LapTotal = $('#ff_laptotal').val();
    } else if (type === 'transpo') {
        data.ID                  = $('#tr_id').val();
        data.TransactionID       = $('#tr_transactionid').val();
        data.Description         = $('#tr_description').val();
        data.DateAcquired        = $('#tr_dateacquired').val();
        data.RefNo               = $('#tr_refno').val();
        data.AcquisitionCost     = $('#tr_acquisitioncost').val();
        data.NoOfUnits           = $('#tr_noofunits').val();
        data.TotalCost           = $('#tr_totalcost').val();
        data.EstUsefulLife       = $('#tr_estusefullife').val();
        data.NoOfMonths          = $('#tr_noofmonths').val();
        data.MonthStartedDepr    = $('#tr_monthstarteddepr').val();
        data.MonthlyDepr         = $('#tr_monthlydepr').val();
        data.AccumDeprPrevYear   = $('#tr_accumdeprprevyear').val();
        data.DeprThisYear        = $('#tr_deprthisyear').val();
        data.AccumDeprAsOfDate   = $('#tr_accumdeprasofdate').val();
        data.NetBookValue        = $('#tr_netbookvalue').val();
        data.LapJan = $('#tr_lapjan').val(); data.LapFeb = $('#tr_lapfeb').val();
        data.LapMar = $('#tr_lapmar').val(); data.LapApr = $('#tr_lapapr').val();
        data.LapMay = $('#tr_lapmay').val(); data.LapJun = $('#tr_lapjun').val();
        data.LapJul = $('#tr_lapjul').val(); data.LapAug = $('#tr_lapaug').val();
        data.LapSep = $('#tr_lapsep').val(); data.LapOct = $('#tr_lapoct').val();
        data.LapNov = $('#tr_lapnov').val(); data.LapDec = $('#tr_lapdec').val();
        data.LapTotal = $('#tr_laptotal').val();
    } else if (type === 'leasehold') {
        data.ID                  = $('#lh_id').val();
        data.TransactionID       = $('#lh_transactionid').val();
        data.Description         = $('#lh_description').val();
        data.DateAcquired        = $('#lh_dateacquired').val();
        data.RefNo               = $('#lh_refno').val();
        data.AcquisitionCost     = $('#lh_acquisitioncost').val();
        data.NoOfUnits           = $('#lh_noofunits').val();
        data.TotalCost           = $('#lh_totalcost').val();
        data.EstUsefulLife       = $('#lh_estusefullife').val();
        data.NoOfMonths          = $('#lh_noofmonths').val();
        data.MonthStartedDepr    = $('#lh_monthstarteddepr').val();
        data.MonthlyDepr         = $('#lh_monthlydepr').val();
        data.AccumDeprPrevYear   = $('#lh_accumdeprprevyear').val();
        data.DeprThisYear        = $('#lh_deprthisyear').val();
        data.AccumDeprAsOfDate   = $('#lh_accumdeprasofdate').val();
        data.NetBookValue        = $('#lh_netbookvalue').val();
        data.LapJan = $('#lh_lapjan').val(); data.LapFeb = $('#lh_lapfeb').val();
        data.LapMar = $('#lh_lapmar').val(); data.LapApr = $('#lh_lapapr').val();
        data.LapMay = $('#lh_lapmay').val(); data.LapJun = $('#lh_lapjun').val();
        data.LapJul = $('#lh_lapjul').val(); data.LapAug = $('#lh_lapaug').val();
        data.LapSep = $('#lh_lapsep').val(); data.LapOct = $('#lh_lapoct').val();
        data.LapNov = $('#lh_lapnov').val(); data.LapDec = $('#lh_lapdec').val();
        data.LapTotal = $('#lh_laptotal').val();
    }

    $.post(ROUTE_URL, data, function (res) {
        if (res.STATUS === 'SUCCESS') {
            var modalIds = { furniture: 'modalFurniture', transpo: 'modalTranspo', leasehold: 'modalLeasehold' };
            bootstrap.Modal.getInstance(document.getElementById(modalIds[type])).hide();
            Swal.fire({ icon: 'success', title: 'Saved successfully.', timer: 1500, showConfirmButton: false });
            LoadEquipmentList();
        } else {
            Swal.fire({ icon: 'error', title: 'Failed to save.', text: res.MESSAGE || '' });
        }
    }, 'json');
}

function openEditModal() {
    var selected = $('#equipmentTbl tbody tr.selected');
    if (!selected.length) { Swal.fire({ icon: 'warning', title: 'Select a row first.' }); return; }
    var data = selected.data('row');
    editingId = data.ID;
    // TODO: populate the correct type modal based on data.AssetType
}

function viewRow(btn) {
    var $tr = $(btn).closest('tr');
    var d = $tr.data('row');
    if (!d) return;

    var fmt = function(v) { return v !== undefined && v !== null && v !== '' ? v : '-'; };
    var num = function(v) { return (parseFloat(v)||0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); };

    var fields = [
        ['Transaction ID',      d.TransactionID],
        ['Asset Type',          d.AssetType],
        ['Description',         d.Description],
        ['Property No',         d.PropertyNo],
        ['Date Acquired',       d.DateAcquired],
        ['Ref No',              d.RefNo],
        ['No of Units',         d.NoOfUnits],
        ['Acquisition Cost',    d.AcquisitionCost !== undefined ? num(d.AcquisitionCost) : null],
        ['Total Cost Transferred', d.TotalCostTransferred !== undefined ? num(d.TotalCostTransferred) : null],
        ['Disposal / Transfer Out', d.DisposalTransferOut !== undefined ? num(d.DisposalTransferOut) : null],
        ['Disposal Reclass',    d.DisposalReclass !== undefined ? num(d.DisposalReclass) : null],
        ['Total Cost',          num(d.TotalCost)],
        ['Est Useful Life',     d.EstUsefulLife ? d.EstUsefulLife + ' yr(s)' : null],
        ['No of Months',        d.NoOfMonths],
        ['Month Started Depr',  d.MonthStartedDepr],
        ['Monthly Depr',        num(d.MonthlyDepr || d.MonthlyDep)],
        ['Accum Depr (Prev Year)', num(d.AccumDeprPrevYear)],
        ['Depr This Year',      num(d.DeprThisYear)],
        ['Accum Depr (As of Date)', num(d.AccumDeprAsOfDate || d.AccumulatedDep)],
        ['Net Book Value',      num(d.NetBookValue)],
        ['Lapsing Total',       num(d.LapTotal)],
        ['Status',              d.Status],
    ];

    var lapMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var lapKeys   = ['LapJan','LapFeb','LapMar','LapApr','LapMay','LapJun','LapJul','LapAug','LapSep','LapOct','LapNov','LapDec'];

    var rows = '';
    fields.forEach(function(f) {
        if (f[1] === null || f[1] === undefined) return;
        rows += '<tr><td class="fw-semibold text-muted" style="width:45%">' + f[0] + '</td><td>' + fmt(f[1]) + '</td></tr>';
    });

    var lapRow = '';
    lapKeys.forEach(function(k) {
        if (d[k] !== undefined) lapRow += '<td>' + num(d[k]) + '</td>';
    });
    if (lapRow) {
        rows += '<tr><td class="fw-semibold text-muted">Lapsing Schedule</td><td><table class="table table-sm table-bordered mb-0 mt-1"><thead><tr>';
        lapMonths.forEach(function(m) { rows += '<th>' + m + '</th>'; });
        rows += '</tr></thead><tbody><tr>' + lapRow + '</tr></tbody></table></td></tr>';
    }

    $('#viewDetailsTitle').text((d.Description || d.AssetName || 'Asset') + ' — Details');
    $('#viewDetailsBody').html('<table class="table table-sm table-striped mb-0">' + rows + '</table>');

    var el = document.getElementById('modalViewDetails');
    if (!el) { console.error('modalViewDetails not found'); return; }
    var modal = bootstrap.Modal.getOrCreateInstance(el);
    modal.show();
}

function DisposeEquipment() {
    var selected = $('#equipmentTbl tbody tr.selected');
    if (!selected.length) { Swal.fire({ icon: 'warning', title: 'Select a row first.' }); return; }
    var row      = selected.data('row');
    var id       = selected.data('id');
    var assetType = row.AssetType || $('#filterAssets').val();

    if (row.Status === 'Disposed') { Swal.fire({ icon: 'info', title: 'Already disposed.' }); return; }

    Swal.fire({
        icon: 'warning', title: 'Dispose this equipment?',
        text: 'Status will be set to Disposed.',
        showCancelButton: true, confirmButtonText: 'Yes, dispose!', confirmButtonColor: '#dc3545'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(ROUTE_URL, { action: 'DisposeEquipment', ID: id, AssetType: assetType }, function (res) {
                if (res.STATUS === 'SUCCESS') {
                    Swal.fire({ icon: 'success', title: 'Disposed.', timer: 1200, showConfirmButton: false });
                    LoadEquipmentList();
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed to dispose.', text: res.MESSAGE || '' });
                }
            }, 'json');
        }
    });
}

// ── Generate JV ───────────────────────────────────────────────────────────
function GenerateJV() {
    var fund   = $('#jvFund').val();
    var jvDate = $('#jvDate').val();
    var jvNo   = $('#jvNo').val();
    var ids    = [];
    $('.jv-check:checked').each(function () { ids.push($(this).val()); });

    if (!fund || !jvDate || !jvNo) { Swal.fire({ icon: 'warning', title: 'Please fill in Fund, Date, and JV No.' }); return; }
    if (!ids.length) { Swal.fire({ icon: 'warning', title: 'Select at least one equipment.' }); return; }

    Swal.fire({
        icon: 'question', title: 'Generate JV for selected equipment?',
        showCancelButton: true, confirmButtonText: 'Yes, generate!'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(ROUTE_URL, {
                action: 'GenerateJV', Fund: fund, JVDate: jvDate, JVNo: jvNo,
                EquipmentIDs: JSON.stringify(ids.map(Number))
            }, function (res) {
                if (res.STATUS === 'SUCCESS') {
                    bootstrap.Modal.getInstance(document.getElementById('generateJV')).hide();
                    Swal.fire({ icon: 'success', title: 'JV Generated!', text: 'JV No: ' + res.JVNO + ' | Total Dep: ' + res.TOTAL });
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: res.MESSAGE });
                }
            }, 'json');
        }
    });
}

$(document).on('change', '#jvSelectAll', function () { $('.jv-check').prop('checked', $(this).is(':checked')); });
$(document).on('change', '#selType', function () { filterTable(); });

function setAssetFilter(val) {
    $('#btnFurniture, #btnTranspo, #btnLeasehold').removeClass('active');
    var map = { 'Fur Fix Equip': '#btnFurniture', 'Transpo Equipment': '#btnTranspo', 'Leasehold Imp': '#btnLeasehold' };
    if (map[val]) $(map[val]).addClass('active');
    $('#filterAssets').val(val);
    filterTable();
}

function setStatusFilter(val) {
    $('#btnActive, #btnDisposed').removeClass('active');
    var map = { 'Active': '#btnActive', 'Disposed': '#btnDisposed' };
    if (map[val]) $(map[val]).addClass('active');
    $('#selType').val(val);
    filterTable();
}

function filterTable() {
    LoadEquipmentList();
}

function printEquipment(id) {
    var list  = _printData.list;
    var tbl   = _printData.table;
    if (!list || !list.length) { alert('No data to print.'); return; }

    // Show loading message
    var loadingMsg = document.createElement('div');
    loadingMsg.id = 'printLoadingMsg';
    loadingMsg.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#1a3c5e;color:#fff;padding:20px 40px;border-radius:8px;z-index:99999;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.3)';
    loadingMsg.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Preparing print preview...';
    document.body.appendChild(loadingMsg);

    // Use setTimeout to allow UI to update
    setTimeout(function() {
        try {
            var now        = new Date().toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
            var curYear    = new Date().getFullYear();
            var assetLabel = ($('#filterAssets option:selected').text() || 'All Equipment');
            var cols       = _getExportCols(tbl);

            var lapKeys = ['LapJan','LapFeb','LapMar','LapApr','LapMay','LapJun','LapJul','LapAug','LapSep','LapOct','LapNov','LapDec'];
            var lapLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

            // Build main table header (WITHOUT lapsing schedule)
            var mainHeaderHtml = '<tr>';
            cols.forEach(function(c) {
                // Skip ALL lapsing columns (including LapTotal)
                if (lapKeys.indexOf(c.key) !== -1 || c.key === 'LapTotal') return;
                
                // For spacer columns, add empty header with minimal width
                if (c.spacer) {
                    mainHeaderHtml += '<th style="width:15px;padding:0;border:none;background:white;"></th>';
                    return;
                }
                
                mainHeaderHtml += '<th>' + (c.pdfLabel || c.label) + '</th>';
            });
            mainHeaderHtml += '</tr>';

            // Build lapsing schedule header (Description + 12 months + Total)
            var lapHeaderHtml = '<tr><th>Description</th>';
            lapLabels.forEach(function(m) { lapHeaderHtml += '<th>' + m + '</th>'; });
            lapHeaderHtml += '<th>Total</th></tr>';

            var active   = list.filter(function(v) { return (v.Status||'').toLowerCase() !== 'disposed'; });
            var disposed = list.filter(function(v) { return (v.Status||'').toLowerCase() === 'disposed'; });

            // Group equipment by AssetType
            function groupByType(items) {
                var groups = {};
                items.forEach(function(item) {
                    var type = item.AssetType || 'Other';
                    if (!groups[type]) groups[type] = [];
                    groups[type].push(item);
                });
                return groups;
            }

            var activeGroups = groupByType(active);
            var disposedGroups = groupByType(disposed);

            // Build SEPARATE pages for each equipment type
            var allTypes = new Set([...Object.keys(activeGroups), ...Object.keys(disposedGroups)]);
            var allTypesArray = Array.from(allTypes);
            
            // Sort to ensure consistent order: Fur Fix Equip, Transpo Equipment, Leasehold Imp
            var typeOrder = ['Fur Fix Equip', 'Transpo Equipment', 'Leasehold Imp'];
            allTypesArray.sort(function(a, b) {
                return typeOrder.indexOf(a) - typeOrder.indexOf(b);
            });
            
            var pages = [];
            
            allTypesArray.forEach(function(assetType) {
                // Get type-specific columns based on AssetType
                var typeTable = 'furniture'; // default
                if (assetType === 'Transpo Equipment') {
                    typeTable = 'transpo';
                } else if (assetType === 'Leasehold Imp') {
                    typeTable = 'leasehold';
                }
                
                // Get columns for this specific type
                var typeCols = _getExportCols(typeTable);
                
                // Build type-specific header
                var typeHeaderHtml = '<tr>';
                typeCols.forEach(function(c) {
                    if (lapKeys.indexOf(c.key) !== -1 || c.key === 'LapTotal') return;
                    if (c.spacer) {
                        typeHeaderHtml += '<th style="width:15px;padding:0;border:none;background:white;"></th>';
                        return;
                    }
                    typeHeaderHtml += '<th>' + (c.pdfLabel || c.label) + '</th>';
                });
                typeHeaderHtml += '</tr>';
                
                // PAGE 1: Active equipment main table
                if (activeGroups[assetType]) {
                    var items = activeGroups[assetType];
                    var rows = '';
                    
                    items.forEach(function(eq) {
                        var cells = '';
                        typeCols.forEach(function(c) {
                            // Skip lapsing columns
                            if (lapKeys.indexOf(c.key) !== -1 || c.key === 'LapTotal') return;
                            
                            // For spacer columns, add empty cell with minimal width
                            if (c.spacer) {
                                cells += '<td style="width:15px;padding:0;border:none;background:white;"></td>';
                                return;
                            }
                            
                            var val = c.num ? parseFloat(eq[c.key] || 0) : (eq[c.key] || '-');
                            // Format integers without decimals
                            if (c.num) {
                                var formatted = (c.key === 'NoOfUnits' || c.key === 'EstUsefulLife' || c.key === 'NoOfMonths') 
                                    ? Math.round(val).toLocaleString('en-PH')
                                    : numberFmt(val);
                                cells += '<td style="text-align:right">' + formatted + '</td>';
                            } else {
                                cells += '<td>' + val + '</td>';
                            }
                        });
                        rows += '<tr>' + cells + '</tr>';
                    });
                    
                    var pageContent = '<div class="eq-page">';
                    pageContent += '<h3 style="text-align:center;margin:0 0 20px;font-size:13px;font-weight:bold;color:#1a3c5e;text-transform:uppercase;letter-spacing:1px">' + assetType + ' - Active</h3>';
                    pageContent += '<div class="eq-counter">Active Equipment (' + items.length + ' items)</div>' +
                                   '<table><thead>' + typeHeaderHtml + '</thead><tbody>' + rows + '</tbody></table>';
                    pageContent += '</div>';
                    pages.push(pageContent);
                }

                // PAGE 2: Active equipment lapsing schedule
                if (activeGroups[assetType]) {
                    var items = activeGroups[assetType];
                    var lapRows = '';
                    
                    items.forEach(function(eq) {
                        lapRows += '<tr><td>' + (eq.Description || '-') + '</td>';
                        lapKeys.forEach(function(k) {
                            var val = parseFloat(eq[k] || 0);
                            lapRows += '<td style="text-align:right">' + numberFmt(val) + '</td>';
                        });
                        lapRows += '<td style="text-align:right;font-weight:bold">' + numberFmt(eq.LapTotal || 0) + '</td></tr>';
                    });
                    
                    var lapPageContent = '<div class="eq-page">';
                    lapPageContent += '<h3 style="text-align:center;margin:0 0 20px;font-size:13px;font-weight:bold;color:#1a3c5e;text-transform:uppercase;letter-spacing:1px">' + assetType + ' - Active Lapsing Schedule</h3>';
                    lapPageContent += '<div class="eq-counter">Active Equipment (' + items.length + ' items)</div>' +
                                      '<table><thead>' + lapHeaderHtml + '</thead><tbody>' + lapRows + '</tbody></table>';
                    lapPageContent += '</div>';
                    pages.push(lapPageContent);
                }
                
                // PAGE 3: Disposed equipment main table
                if (disposedGroups[assetType]) {
                    var items = disposedGroups[assetType];
                    var rows = '';
                    
                    items.forEach(function(eq) {
                        var cells = '';
                        typeCols.forEach(function(c) {
                            // Skip lapsing columns
                            if (lapKeys.indexOf(c.key) !== -1 || c.key === 'LapTotal') return;
                            
                            // For spacer columns, add empty cell with minimal width
                            if (c.spacer) {
                                cells += '<td style="width:15px;padding:0;border:none;background:white;"></td>';
                                return;
                            }
                            
                            var val = c.num ? parseFloat(eq[c.key] || 0) : (eq[c.key] || '-');
                            // Format integers without decimals
                            if (c.num) {
                                var formatted = (c.key === 'NoOfUnits' || c.key === 'EstUsefulLife' || c.key === 'NoOfMonths') 
                                    ? Math.round(val).toLocaleString('en-PH')
                                    : numberFmt(val);
                                cells += '<td style="text-align:right">' + formatted + '</td>';
                            } else {
                                cells += '<td>' + val + '</td>';
                            }
                        });
                        rows += '<tr>' + cells + '</tr>';
                    });
                    
                    var pageContent = '<div class="eq-page">';
                    pageContent += '<h3 style="text-align:center;margin:0 0 20px;font-size:13px;font-weight:bold;color:#1a3c5e;text-transform:uppercase;letter-spacing:1px">' + assetType + ' - Disposed</h3>';
                    pageContent += '<div class="eq-counter">Disposed Equipment (' + items.length + ' items)</div>' +
                                   '<table><thead>' + typeHeaderHtml + '</thead><tbody>' + rows + '</tbody></table>';
                    pageContent += '</div>';
                    pages.push(pageContent);
                }
                
                // PAGE 4: Disposed equipment lapsing schedule
                if (disposedGroups[assetType]) {
                    var items = disposedGroups[assetType];
                    var lapRows = '';
                    
                    items.forEach(function(eq) {
                        lapRows += '<tr><td>' + (eq.Description || '-') + '</td>';
                        lapKeys.forEach(function(k) {
                            var val = parseFloat(eq[k] || 0);
                            lapRows += '<td style="text-align:right">' + numberFmt(val) + '</td>';
                        });
                        lapRows += '<td style="text-align:right;font-weight:bold">' + numberFmt(eq.LapTotal || 0) + '</td></tr>';
                    });
                    
                    var lapPageContent = '<div class="eq-page">';
                    lapPageContent += '<h3 style="text-align:center;margin:0 0 20px;font-size:13px;font-weight:bold;color:#1a3c5e;text-transform:uppercase;letter-spacing:1px">' + assetType + ' - Disposed Lapsing Schedule</h3>';
                    lapPageContent += '<div class="eq-counter">Disposed Equipment (' + items.length + ' items)</div>' +
                                      '<table><thead>' + lapHeaderHtml + '</thead><tbody>' + lapRows + '</tbody></table>';
                    lapPageContent += '</div>';
                    pages.push(lapPageContent);
                }
            });

            // Determine logo path - use BASE_PATH if available
            var basePath = window.BASE_PATH || '';
            var logoPath = basePath + '/assets/images/complete-logo.png';

            var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title></title>'
                + '<style>'
                + 'body{font-family:Arial,sans-serif;font-size:10px;margin:0;padding:0;color:#222}'
                + '.header-section{text-align:center;margin-bottom:20px;padding:24px}'
                + '.logo-container{margin:0 auto 15px;}'
                + '.logo-container img{height:60px;width:auto;display:block;margin:0 auto}'
                + '.title-container{text-align:center}'
                + 'h2{text-align:center;margin:0 0 4px;font-size:14px;text-transform:uppercase;letter-spacing:1px}'
                + 'h4{text-align:center;margin:4px 0;font-size:11px;color:#444}'
                + '.meta{text-align:center;font-size:9px;color:#666;margin-top:4px}'
                + '.eq-page{page-break-after:always;padding:24px}'
                + '.eq-page:last-child{page-break-after:auto}'
                + '.eq-counter{text-align:right;font-size:9px;color:#666;margin-bottom:6px}'
                + 'table{width:100%;border-collapse:collapse}'
                + 'thead tr{background:#1a3c5e;color:#fff}'
                + 'th{padding:5px 7px;text-align:center;font-size:9px;border:1px solid #aaa;white-space:nowrap}'
                + 'td{padding:4px 7px;border:1px solid #ddd;font-size:9px}'
                + '@media print{@page{size:landscape;margin:0.5in}.eq-page{padding:0;margin:0}}'
                + '</style></head><body>'
                + '<div class="header-section">'
                + '<div class="logo-container"><img src="' + logoPath + '" alt="Logo"></div>'
                + '<div class="title-container">'
                + '<h2>Equipment Depreciation Schedule</h2>'
                + '<h4>' + assetLabel + '</h4>'
                + '<div class="meta">As of ' + now + '</div>'
                + '</div>'
                + '</div>'
                + '<hr style="margin:0 24px 20px;border:none;border-top:2px solid #1a3c5e">'
                + pages.join('')
                + '</body></html>';

            var win = window.open('', '', 'width=1200,height=750');
            if (!win) {
                document.body.removeChild(loadingMsg);
                alert('Pop-up blocked! Please allow pop-ups for this site.');
                return;
            }
            
            win.document.write(html);
            win.document.close();
            
            // Remove loading message
            document.body.removeChild(loadingMsg);
            
            win.focus();
            setTimeout(function() { 
                win.print(); 
                // Show success message after print dialog opens
                setTimeout(function() {
                    showSuccessMessage('PDF print dialog opened successfully!');
                }, 500);
            }, 500);
        } catch(e) {
            console.error('Print error:', e);
            if (document.getElementById('printLoadingMsg')) {
                document.body.removeChild(document.getElementById('printLoadingMsg'));
            }
            alert('Error preparing print: ' + e.message);
        }
    }, 150);
}

// Global variable to store print scope
var _printScope = 'all';

function openPrintModal() {
    // Open the first modal (equipment type selection)
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPrintScope')).show();
}

function selectPrintScope(scope) {
    _printScope = scope;
    // Close first modal
    bootstrap.Modal.getInstance(document.getElementById('modalPrintScope')).hide();
    // Open second modal (format selection)
    setTimeout(function() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPrintOptions')).show();
    }, 300);
}

function executePrint(format) {
    // Close the modal
    bootstrap.Modal.getInstance(document.getElementById('modalPrintOptions')).hide();
    
    // Execute based on scope and format
    if (_printScope === 'all') {
        // Load all equipment types
        if (format === 'pdf') {
            printList('equipmentTbl');
        } else {
            loadAllForExcel();
        }
    } else {
        // Load specific equipment type
        var assetTypeMap = {
            'furniture': 'Fur Fix Equip',
            'transpo': 'Transpo Equipment',
            'leasehold': 'Leasehold Imp'
        };
        var assetType = assetTypeMap[_printScope] || '';
        
        // Load specific type
        $.post(ROUTE_URL, { action: 'LoadEquipmentList', AssetType: assetType, Status: '' }, function (res) {
            _printData.list  = res.LIST || [];
            _printData.table = _printScope;
            
            // Calculate AccumDeprPrevYear and DeprThisYear
            _printData.list.forEach(function(item) {
                if (typeof item.AccumDeprPrevYear === 'undefined' || item.AccumDeprPrevYear === null) {
                    var lapTotal = parseFloat(item.LapTotal || 0);
                    var accumDepr = parseFloat(item.AccumDeprAsOfDate || 0);
                    item.AccumDeprPrevYear = accumDepr - lapTotal;
                }
                if (typeof item.DeprThisYear === 'undefined' || item.DeprThisYear === null) {
                    item.DeprThisYear = parseFloat(item.LapTotal || 0);
                }
            });
            
            if (format === 'pdf') {
                printEquipment('equipmentTbl');
            } else {
                exportExcel();
            }
        }, 'json').fail(function (xhr) {
            alert('Error loading equipment data: ' + xhr.responseText);
        });
    }
}

function printList(id) {
    // Load ALL equipment types before printing
    $.post(ROUTE_URL, { action: 'LoadEquipmentList', AssetType: '', Status: '' }, function (res) {
        _printData.list  = res.LIST || [];
        _printData.table = 'all';
        
        // Calculate AccumDeprPrevYear and DeprThisYear if they don't exist
        _printData.list.forEach(function(item) {
            if (typeof item.AccumDeprPrevYear === 'undefined' || item.AccumDeprPrevYear === null) {
                var lapTotal = parseFloat(item.LapTotal || 0);
                var accumDepr = parseFloat(item.AccumDeprAsOfDate || 0);
                item.AccumDeprPrevYear = accumDepr - lapTotal;
            }
            if (typeof item.DeprThisYear === 'undefined' || item.DeprThisYear === null) {
                item.DeprThisYear = parseFloat(item.LapTotal || 0);
            }
        });
        
        printEquipment('equipmentTbl');
    }, 'json').fail(function (xhr) {
        alert('Error loading equipment data for print: ' + xhr.responseText);
    });
}

function loadAllForExcel() {
    // Load ALL equipment types before exporting to Excel
    $.post(ROUTE_URL, { action: 'LoadEquipmentList', AssetType: '', Status: '' }, function (res) {
        _printData.list  = res.LIST || [];
        _printData.table = 'all';
        
        // Calculate AccumDeprPrevYear and DeprThisYear if they don't exist
        _printData.list.forEach(function(item) {
            if (typeof item.AccumDeprPrevYear === 'undefined' || item.AccumDeprPrevYear === null) {
                var lapTotal = parseFloat(item.LapTotal || 0);
                var accumDepr = parseFloat(item.AccumDeprAsOfDate || 0);
                item.AccumDeprPrevYear = accumDepr - lapTotal;
            }
            if (typeof item.DeprThisYear === 'undefined' || item.DeprThisYear === null) {
                item.DeprThisYear = parseFloat(item.LapTotal || 0);
            }
        });
        
        exportExcel();
    }, 'json').fail(function (xhr) {
        alert('Error loading equipment data for export: ' + xhr.responseText);
    });
}

function exportExcel() {
    // Check if ExcelJS is loaded
    if (typeof ExcelJS === 'undefined') {
        alert('ExcelJS library is not loaded. Please refresh the page (Ctrl+F5) and try again.');
        console.error('ExcelJS is not defined. Check if the CDN script is loading properly.');
        return;
    }
    
    var list  = _printData.list;
    var tbl   = _printData.table;
    if (!list || !list.length) { alert('No data to export.'); return; }

    // Show loading message
    var loadingMsg = document.createElement('div');
    loadingMsg.id = 'excelLoadingMsg';
    loadingMsg.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#1a3c5e;color:#fff;padding:20px 40px;border-radius:8px;z-index:99999;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.3)';
    loadingMsg.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Generating Excel file...';
    document.body.appendChild(loadingMsg);

    // Fetch logo image first - use BASE_PATH for correct path
    var basePath = window.BASE_PATH || '';
    var logoPath = basePath + '/assets/images/complete-logo.png';
    
    fetch(logoPath)
        .then(function(response) {
            if (!response.ok) throw new Error('Logo not found');
            return response.arrayBuffer();
        })
        .then(function(logoBuffer) {
            generateExcelWithLogo(logoBuffer, loadingMsg);
        })
        .catch(function(error) {
            console.warn('Could not load logo:', error);
            generateExcelWithLogo(null, loadingMsg);
        });
}

function generateExcelWithLogo(logoBuffer, loadingMsg) {
    // Get data from _printData
    var list  = _printData.list;
    var tbl   = _printData.table;
    
    // Use setTimeout to allow UI to update
    setTimeout(function() {
        try {
            var now        = new Date().toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
            var curYear    = new Date().getFullYear();
            var assetLabel = ($('#filterAssets option:selected').text() || 'All Equipment');
            var cols       = _getExportCols(tbl);

            var lapKeys    = ['LapJan','LapFeb','LapMar','LapApr','LapMay','LapJun','LapJul','LapAug','LapSep','LapOct','LapNov','LapDec'];
            var lapLabels  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

            var active   = list.filter(function(v) { return (v.Status||'').toLowerCase() !== 'disposed'; });
            var disposed = list.filter(function(v) { return (v.Status||'').toLowerCase() === 'disposed'; });

            // Group by AssetType
            function groupByType(items) {
                var groups = {};
                items.forEach(function(item) {
                    var type = item.AssetType || 'Other';
                    if (!groups[type]) groups[type] = [];
                    groups[type].push(item);
                });
                return groups;
            }

            var activeGroups = groupByType(active);
            var disposedGroups = groupByType(disposed);
            
            // Get all unique equipment types
            var allTypes = new Set([...Object.keys(activeGroups), ...Object.keys(disposedGroups)]);
            var allTypesArray = Array.from(allTypes);
            
            // Sort to ensure consistent order: Fur Fix Equip, Transpo Equipment, Leasehold Imp
            var typeOrder = ['Fur Fix Equip', 'Transpo Equipment', 'Leasehold Imp'];
            allTypesArray.sort(function(a, b) {
                return typeOrder.indexOf(a) - typeOrder.indexOf(b);
            });

            // Create workbook
            var workbook = new ExcelJS.Workbook();
            workbook.creator = 'System';
            workbook.created = new Date();

            function buildSheetWithCols(activeRows, disposedRows, sheetTitle, sheetCols) {
                var worksheet = workbook.addWorksheet(sheetTitle);
                
                // Find the index of the first lap month column
                var lapStartIdx = -1;
                sheetCols.forEach(function(c, i) { if (c.key === 'LapJan' && lapStartIdx === -1) lapStartIdx = i; });

                // Add logo image if available (Row 1)
                if (logoBuffer) {
                    try {
                        var logoId = workbook.addImage({
                            buffer: logoBuffer,
                            extension: 'png',
                        });
                        worksheet.addImage(logoId, {
                            tl: { col: 0, row: 0 },
                            ext: { width: 300, height: 80 }
                        });
                    } catch(logoErr) {
                        console.warn('Could not add logo to worksheet:', logoErr);
                    }
                }
                worksheet.getRow(1).height = 60;

                // Row 2: Title
                worksheet.mergeCells(2, 1, 2, sheetCols.length);
                var titleCell = worksheet.getCell(2, 1);
                titleCell.value = 'Equipment Depreciation Schedule';
                titleCell.font = { bold: true, size: 14 };
                titleCell.alignment = { horizontal: 'left', vertical: 'middle' };

                // Row 3: Subtitle
                worksheet.mergeCells(3, 1, 3, sheetCols.length);
                var subtitleCell = worksheet.getCell(3, 1);
                subtitleCell.value = sheetTitle;
                subtitleCell.font = { bold: true, size: 12 };
                subtitleCell.alignment = { horizontal: 'left', vertical: 'middle' };

                // Row 4: Date
                worksheet.mergeCells(4, 1, 4, sheetCols.length);
                var dateCell = worksheet.getCell(4, 1);
                dateCell.value = 'As of ' + now;
                dateCell.font = { size: 10 };
                dateCell.alignment = { horizontal: 'left', vertical: 'middle' };

                // Row 5: Blank
                worksheet.getRow(5).height = 15;

                // Row 6: Header row 1 (with Lapsing Schedule grouped)
                var headerRow1 = worksheet.getRow(6);
                headerRow1.height = 30;
                var colIdx = 1;
                var lapScheduleAdded = false;
                
                sheetCols.forEach(function(c, i) {
                    var isLapMonth = lapKeys.indexOf(c.key) !== -1;
                    
                    // Add "LAPSING SCHEDULE" title once when we hit the first lap month
                    if (i === lapStartIdx && !lapScheduleAdded) {
                        worksheet.mergeCells(6, colIdx, 6, colIdx + 11);
                        var cell = headerRow1.getCell(colIdx);
                        cell.value = 'LAPSING SCHEDULE';
                        cell.font = { bold: true, size: 10, color: { argb: 'FFFFFFFF' } };
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F4E78' } };
                        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                        cell.border = {
                            top: { style: 'thin' }, bottom: { style: 'thin' },
                            left: { style: 'thin' }, right: { style: 'thin' }
                        };
                        lapScheduleAdded = true;
                    }
                    
                    // For lap month columns, just increment colIdx
                    if (isLapMonth) {
                        colIdx++;
                        return;
                    }
                    
                    // For non-lap columns
                    var cell = headerRow1.getCell(colIdx);
                    
                    // Skip styling for spacer columns - leave them white/blank
                    if (c.spacer) {
                        colIdx++;
                        return;
                    }
                    
                    if (c.key === 'LapTotal' || c.key === 'Status') {
                        worksheet.mergeCells(6, colIdx, 7, colIdx);
                        cell.value = c.label;
                    } else {
                        worksheet.mergeCells(6, colIdx, 7, colIdx);
                        cell.value = c.label;
                    }
                    
                    cell.font = { bold: true, size: 10, color: { argb: 'FFFFFFFF' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F4E78' } };
                    cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
                    cell.border = {
                        top: { style: 'thin' }, bottom: { style: 'thin' },
                        left: { style: 'thin' }, right: { style: 'thin' }
                    };
                    colIdx++;
                });

                // Row 7: Header row 2 (month names)
                var headerRow2 = worksheet.getRow(7);
                headerRow2.height = 20;
                colIdx = 1;
                sheetCols.forEach(function(c) {
                    var lapIdx = lapKeys.indexOf(c.key);
                    if (lapIdx !== -1) {
                        var cell = headerRow2.getCell(colIdx);
                        cell.value = lapLabels[lapIdx];
                        cell.font = { bold: true, size: 10, color: { argb: 'FFFFFFFF' } };
                        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F4E78' } };
                        cell.alignment = { horizontal: 'center', vertical: 'middle' };
                        cell.border = {
                            top: { style: 'thin' }, bottom: { style: 'thin' },
                            left: { style: 'thin' }, right: { style: 'thin' }
                        };
                        colIdx++;
                    } else if (c.key !== 'LapTotal' && c.key !== 'Status') {
                        colIdx++;
                    }
                });

                var currentRow = 8;
                
                // Active equipment data rows
                var activeTotals = {};
                sheetCols.forEach(function(c) { if (c.num) activeTotals[c.key] = 0; });

                activeRows.forEach(function(v) {
                    var dataRow = worksheet.getRow(currentRow);
                    dataRow.height = 18;
                    
                    sheetCols.forEach(function(c, colIndex) {
                        var cell = dataRow.getCell(colIndex + 1);
                        
                        // Skip spacer columns - leave them completely empty
                        if (c.spacer) {
                            cell.value = '';
                            return;
                        }
                        
                        var val = c.num ? parseFloat(v[c.key] || 0) : (v[c.key] || '-');
                        
                        if (c.num) {
                            activeTotals[c.key] += val;
                            cell.value = val;
                            // Format integers without decimals
                            if (c.key === 'NoOfUnits' || c.key === 'EstUsefulLife' || c.key === 'NoOfMonths') {
                                cell.numFmt = '#,##0';
                            } else {
                                cell.numFmt = '#,##0.00';
                            }
                            cell.alignment = { horizontal: 'right', vertical: 'middle' };
                        } else {
                            cell.value = val;
                            cell.alignment = { horizontal: 'left', vertical: 'middle' };
                        }
                        
                        cell.font = { size: 10 };
                        cell.border = {
                            top: { style: 'thin', color: { argb: 'FFD3D3D3' } },
                            bottom: { style: 'thin', color: { argb: 'FFD3D3D3' } },
                            left: { style: 'thin', color: { argb: 'FFD3D3D3' } },
                            right: { style: 'thin', color: { argb: 'FFD3D3D3' } }
                        };
                    });
                    currentRow++;
                });

                // Active Total row
                var totalRow = worksheet.getRow(currentRow);
                totalRow.height = 20;
                
                sheetCols.forEach(function(c, colIndex) {
                    var cell = totalRow.getCell(colIndex + 1);
                    
                    // Skip spacer columns - leave them completely empty
                    if (c.spacer) {
                        cell.value = '';
                        return;
                    }
                    
                    if (c.num) {
                        cell.value = activeTotals[c.key];
                        // Format integers without decimals
                        if (c.key === 'NoOfUnits' || c.key === 'EstUsefulLife' || c.key === 'NoOfMonths') {
                            cell.numFmt = '#,##0';
                        } else {
                            cell.numFmt = '#,##0.00';
                        }
                        cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    } else {
                        cell.value = colIndex === 0 ? 'TOTAL' : '';
                        cell.alignment = { horizontal: 'left', vertical: 'middle' };
                    }
                    
                    cell.font = { bold: true, size: 10, color: { argb: 'FFFFFFFF' } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF1F4E78' } };
                    cell.border = {
                        top: { style: 'thin' }, bottom: { style: 'thin' },
                        left: { style: 'thin' }, right: { style: 'thin' }
                    };
                });
                currentRow++;

                // Add disposed equipment if exists
                if (disposedRows && disposedRows.length > 0) {
                    // Blank row
                    currentRow++;
                    
                    // Disposed section label
                    worksheet.mergeCells(currentRow, 1, currentRow, sheetCols.length);
                    var disposedLabel = worksheet.getCell(currentRow, 1);
                    disposedLabel.value = 'DISPOSED EQUIPMENT';
                    disposedLabel.font = { bold: true, size: 11, color: { argb: 'FF666666' } };
                    disposedLabel.alignment = { horizontal: 'center', vertical: 'middle' };
                    disposedLabel.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE8E8E8' } };
                    currentRow++;
                    
                    // Disposed data rows
                    disposedRows.forEach(function(v) {
                        var dataRow = worksheet.getRow(currentRow);
                        dataRow.height = 18;
                        
                        sheetCols.forEach(function(c, colIndex) {
                            var cell = dataRow.getCell(colIndex + 1);
                            
                            // Skip spacer columns - leave them completely empty
                            if (c.spacer) {
                                cell.value = '';
                                return;
                            }
                            
                            var val = c.num ? parseFloat(v[c.key] || 0) : (v[c.key] || '-');
                            
                            if (c.num) {
                                cell.value = val;
                                // Format integers without decimals
                                if (c.key === 'NoOfUnits' || c.key === 'EstUsefulLife' || c.key === 'NoOfMonths') {
                                    cell.numFmt = '#,##0';
                                } else {
                                    cell.numFmt = '#,##0.00';
                                }
                                cell.alignment = { horizontal: 'right', vertical: 'middle' };
                            } else {
                                cell.value = val;
                                cell.alignment = { horizontal: 'left', vertical: 'middle' };
                            }
                            
                            cell.font = { size: 10, color: { argb: 'FF666666' } };
                            cell.border = {
                                top: { style: 'thin', color: { argb: 'FFD3D3D3' } },
                                bottom: { style: 'thin', color: { argb: 'FFD3D3D3' } },
                                left: { style: 'thin', color: { argb: 'FFD3D3D3' } },
                                right: { style: 'thin', color: { argb: 'FFD3D3D3' } }
                            };
                        });
                        currentRow++;
                    });
                }

                // Set column widths - make Description column extra wide
                sheetCols.forEach(function(c, i) {
                    if (c.key === 'Description') {
                        worksheet.getColumn(i + 1).width = 50; // Wide column for long descriptions
                    } else if (c.spacer) {
                        worksheet.getColumn(i + 1).width = 2; // Narrow spacer column
                    } else if (c.key === 'AccumDeprPrevYear') {
                        worksheet.getColumn(i + 1).width = 15; // Reduced width
                    } else {
                        worksheet.getColumn(i + 1).width = Math.max(c.label.length + 4, 12);
                    }
                });
            }

            // Create separate sheets for each equipment type
            allTypesArray.forEach(function(assetType) {
                var activeItems = activeGroups[assetType] || [];
                var disposedItems = disposedGroups[assetType] || [];
                
                if (activeItems.length > 0 || disposedItems.length > 0) {
                    // Get type-specific columns based on AssetType
                    var typeTable = 'furniture'; // default
                    if (assetType === 'Transpo Equipment') {
                        typeTable = 'transpo';
                    } else if (assetType === 'Leasehold Imp') {
                        typeTable = 'leasehold';
                    }
                    
                    // Get columns for this specific type
                    var typeCols = _getExportCols(typeTable);
                    
                    buildSheetWithCols(activeItems, disposedItems, assetType, typeCols);
                }
            });
            
            if (allTypesArray.length === 0) {
                document.body.removeChild(loadingMsg);
                alert('No data to export.');
                return;
            }

            // Generate and download
            workbook.xlsx.writeBuffer().then(function(buffer) {
                var blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                saveAs(blob, 'EquipmentDepreciationSchedule.xlsx');
                if (loadingMsg && loadingMsg.parentNode) {
                    document.body.removeChild(loadingMsg);
                }
                
                // Show success message
                showSuccessMessage('Excel file generated successfully!');
            }).catch(function(err) {
                console.error('Excel generation error:', err);
                if (loadingMsg && loadingMsg.parentNode) {
                    document.body.removeChild(loadingMsg);
                }
                alert('Error generating Excel file: ' + err.message);
            });
        } catch(e) {
            console.error('Excel export error:', e);
            if (loadingMsg && loadingMsg.parentNode) {
                document.body.removeChild(loadingMsg);
            }
            alert('Error exporting to Excel: ' + e.message);
        }
    }, 100);
}

// Returns column definitions for each asset type - using existing data fields
function _getExportCols(tbl) {
    if (tbl === 'furniture') {
        return [
            { key:'Description',        label:'TYPE OF ASSET/DESCRIPTION',      pdfLabel:'TYPE OF ASSET/DESCRIPTION',           num:false },
            { key:'PropertyNo',         label:'PROPERTY NO.',                   pdfLabel:'PROPERTY NO.',                        num:false },
            { key:'DateAcquired',       label:'DATE ACQUIRED',                  pdfLabel:'DATE ACQUIRED',                       num:false },
            { key:'RefNo',              label:'REF NO.',                        pdfLabel:'REF NO.',                             num:false },
            { key:'TotalCostTransferred',label:'TOTAL COST/TRANSFERRED',        pdfLabel:'TOTAL COST/<br>TRANSFERRED',          num:true  },
            { key:'NoOfUnits',          label:'NO. OF UNITS',                   pdfLabel:'NO. OF<br>UNITS',                     num:true  },
            { key:'TotalCost',          label:'UNIT COST',                      pdfLabel:'UNIT COST',                           num:true  },
            { key:'EstUsefulLife',      label:'EST. USEFUL LIFE',               pdfLabel:'EST. USEFUL<br>LIFE',                 num:true  },
            { key:'NoOfMonths',         label:'NO. OF MONTHS',                  pdfLabel:'NO. OF<br>MONTHS',                    num:true  },
            { key:'MonthStartedDepr',   label:'MONTH STARTED DEPR\'N.',         pdfLabel:'MONTH STARTED<br>DEPR\'N.',           num:false },
            { key:'AccumDeprPrevYear',  label:'ACCUM. DEPR\'N. PREV YEAR',      pdfLabel:'ACCUM.<br>DEPR\'N.<br>PREV YEAR',     num:true  },
            { key:'_spacer1',           label:'',                               pdfLabel:'',                                    num:false, spacer:true },
            { key:'DeprThisYear',       label:'DEPR\'N. THIS YEAR',             pdfLabel:'DEPR\'N.<br>THIS YEAR',               num:true  },
            { key:'AccumDeprAsOfDate',  label:'ACCUM. DEPR\'N. (AS OF DATE)',   pdfLabel:'ACCUM.<br>DEPR\'N.<br>(AS OF DATE)',  num:true  },
            { key:'_spacer2',           label:'',                               pdfLabel:'',                                    num:false, spacer:true },
            { key:'NetBookValue',       label:'NET BOOK VALUE',                 pdfLabel:'NET BOOK<br>VALUE',                   num:true  },
            { key:'LapJan',label:'JAN',num:true },{ key:'LapFeb',label:'FEB',num:true },
            { key:'LapMar',label:'MARCH',num:true },{ key:'LapApr',label:'APRIL',num:true },
            { key:'LapMay',label:'MAY',num:true },{ key:'LapJun',label:'JUNE',num:true },
            { key:'LapJul',label:'JULY',num:true },{ key:'LapAug',label:'AUGUST',num:true },
            { key:'LapSep',label:'SEPT',num:true },{ key:'LapOct',label:'OCT',num:true },
            { key:'LapNov',label:'NOV',num:true },{ key:'LapDec',label:'DEC',num:true },
            { key:'LapTotal',           label:'TOTAL',                          num:true  },
            { key:'Status',             label:'STATUS',                         num:false }
        ];
    } else if (tbl === 'transpo') {
        return [
            { key:'Description',        label:'TYPE OF ASSET/DESCRIPTION',      pdfLabel:'TYPE OF ASSET/DESCRIPTION',           num:false },
            { key:'PropertyNo',         label:'PROPERTY NO.',                   pdfLabel:'PROPERTY NO.',                        num:false },
            { key:'DateAcquired',       label:'DATE ACQUIRED',                  pdfLabel:'DATE ACQUIRED',                       num:false },
            { key:'RefNo',              label:'REF NO.',                        pdfLabel:'REF NO.',                             num:false },
            { key:'AcquisitionCost',    label:'ACQUISITION COST',               pdfLabel:'ACQUISITION COST',                    num:true  },
            { key:'NoOfUnits',          label:'NO. OF UNITS',                   pdfLabel:'NO. OF<br>UNITS',                     num:true  },
            { key:'TotalCost',          label:'UNIT COST',                      pdfLabel:'UNIT COST',                           num:true  },
            { key:'EstUsefulLife',      label:'EST. USEFUL LIFE',               pdfLabel:'EST. USEFUL<br>LIFE',                 num:true  },
            { key:'NoOfMonths',         label:'NO. OF MONTHS',                  pdfLabel:'NO. OF<br>MONTHS',                    num:true  },
            { key:'MonthStartedDepr',   label:'MONTH STARTED DEPR\'N.',         pdfLabel:'MONTH STARTED<br>DEPR\'N.',           num:false },
            { key:'AccumDeprPrevYear',  label:'ACCUM. DEPR\'N. PREV YEAR',      pdfLabel:'ACCUM.<br>DEPR\'N.<br>PREV YEAR',     num:true  },
            { key:'_spacer1',           label:'',                               pdfLabel:'',                                    num:false, spacer:true },
            { key:'DeprThisYear',       label:'DEPR\'N. THIS YEAR',             pdfLabel:'DEPR\'N.<br>THIS YEAR',               num:true  },
            { key:'AccumDeprAsOfDate',  label:'ACCUM. DEPR\'N. (AS OF DATE)',   pdfLabel:'ACCUM.<br>DEPR\'N.<br>(AS OF DATE)',  num:true  },
            { key:'_spacer2',           label:'',                               pdfLabel:'',                                    num:false, spacer:true },
            { key:'NetBookValue',       label:'NET BOOK VALUE',                 pdfLabel:'NET BOOK<br>VALUE',                   num:true  },
            { key:'LapJan',label:'JAN',num:true },{ key:'LapFeb',label:'FEB',num:true },
            { key:'LapMar',label:'MARCH',num:true },{ key:'LapApr',label:'APRIL',num:true },
            { key:'LapMay',label:'MAY',num:true },{ key:'LapJun',label:'JUNE',num:true },
            { key:'LapJul',label:'JULY',num:true },{ key:'LapAug',label:'AUGUST',num:true },
            { key:'LapSep',label:'SEPT',num:true },{ key:'LapOct',label:'OCT',num:true },
            { key:'LapNov',label:'NOV',num:true },{ key:'LapDec',label:'DEC',num:true },
            { key:'LapTotal',           label:'TOTAL',                          num:true  },
            { key:'Status',             label:'STATUS',                         num:false }
        ];
    } else if (tbl === 'leasehold') {
        return [
            { key:'Description',        label:'TYPE OF ASSET/DESCRIPTION',      pdfLabel:'TYPE OF ASSET/DESCRIPTION',           num:false },
            { key:'PropertyNo',         label:'PROPERTY NO.',                   pdfLabel:'PROPERTY NO.',                        num:false },
            { key:'DateAcquired',       label:'DATE ACQUIRED',                  pdfLabel:'DATE ACQUIRED',                       num:false },
            { key:'RefNo',              label:'REF NO.',                        pdfLabel:'REF NO.',                             num:false },
            { key:'AcquisitionCost',    label:'ACQUISITION COST',               pdfLabel:'ACQUISITION COST',                    num:true  },
            { key:'NoOfUnits',          label:'NO. OF UNITS',                   pdfLabel:'NO. OF<br>UNITS',                     num:true  },
            { key:'TotalCost',          label:'UNIT COST',                      pdfLabel:'UNIT COST',                           num:true  },
            { key:'EstUsefulLife',      label:'EST. USEFUL LIFE',               pdfLabel:'EST. USEFUL<br>LIFE',                 num:true  },
            { key:'NoOfMonths',         label:'NO. OF MONTHS',                  pdfLabel:'NO. OF<br>MONTHS',                    num:true  },
            { key:'MonthStartedDepr',   label:'MONTH STARTED DEPR\'N.',         pdfLabel:'MONTH STARTED<br>DEPR\'N.',           num:false },
            { key:'AccumDeprPrevYear',  label:'ACCUM. DEPR\'N. PREV YEAR',      pdfLabel:'ACCUM.<br>DEPR\'N.<br>PREV YEAR',     num:true  },
            { key:'_spacer1',           label:'',                               pdfLabel:'',                                    num:false, spacer:true },
            { key:'DeprThisYear',       label:'DEPR\'N. THIS YEAR',             pdfLabel:'DEPR\'N.<br>THIS YEAR',               num:true  },
            { key:'AccumDeprAsOfDate',  label:'ACCUM. DEPR\'N. (AS OF DATE)',   pdfLabel:'ACCUM.<br>DEPR\'N.<br>(AS OF DATE)',  num:true  },
            { key:'_spacer2',           label:'',                               pdfLabel:'',                                    num:false, spacer:true },
            { key:'NetBookValue',       label:'NET BOOK VALUE',                 pdfLabel:'NET BOOK<br>VALUE',                   num:true  },
            { key:'LapJan',label:'JAN',num:true },{ key:'LapFeb',label:'FEB',num:true },
            { key:'LapMar',label:'MARCH',num:true },{ key:'LapApr',label:'APRIL',num:true },
            { key:'LapMay',label:'MAY',num:true },{ key:'LapJun',label:'JUNE',num:true },
            { key:'LapJul',label:'JULY',num:true },{ key:'LapAug',label:'AUGUST',num:true },
            { key:'LapSep',label:'SEPT',num:true },{ key:'LapOct',label:'OCT',num:true },
            { key:'LapNov',label:'NOV',num:true },{ key:'LapDec',label:'DEC',num:true },
            { key:'LapTotal',           label:'TOTAL',                          num:true  },
            { key:'Status',             label:'STATUS',                         num:false }
        ];
    } else {
        return [
            { key:'Description',  label:'Description',            num:false },
            { key:'AssetType',    label:'Asset Type',             num:false },
            { key:'DateAcquired', label:'Date Acquired',          num:false },
            { key:'EstUsefulLife',label:'Est Useful Life (yrs)',  num:true  },
            { key:'NoOfMonths',   label:'No of Months',           num:true  },
            { key:'TotalCost',    label:'Total Cost',             num:true  },
            { key:'MonthlyDepr',  label:'Monthly Dep',            num:true  },
            { key:'NetBookValue', label:'Net Book Value',         num:true  },
            { key:'LapJan',label:'Jan',num:true },{ key:'LapFeb',label:'Feb',num:true },
            { key:'LapMar',label:'Mar',num:true },{ key:'LapApr',label:'Apr',num:true },
            { key:'LapMay',label:'May',num:true },{ key:'LapJun',label:'Jun',num:true },
            { key:'LapJul',label:'Jul',num:true },{ key:'LapAug',label:'Aug',num:true },
            { key:'LapSep',label:'Sep',num:true },{ key:'LapOct',label:'Oct',num:true },
            { key:'LapNov',label:'Nov',num:true },{ key:'LapDec',label:'Dec',num:true },
            { key:'LapTotal',label:'Lap Total',num:true },
            { key:'Status',       label:'Status',                 num:false }
        ];
    }
}

function RunMonthlyDep() {
    Swal.fire({
        icon: 'question', title: 'Run Monthly Depreciation?',
        text: 'This will update Lapsing Schedule Total, Accumulated Dep and Net Book Value for all active equipment.',
        showCancelButton: true, confirmButtonText: 'Yes, run it!'
    }).then(function (result) {
        if (result.isConfirmed) {
            $.post(ROUTE_URL, { action: 'RunMonthlyDepreciation' }, function (res) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'All Depreciation Updated!', 
                    text: res.UPDATED + ' equipment records updated. Lapsing Schedule Total, Accumulated Depreciation, and Net Book Value have been recalculated.',
                    confirmButtonText: 'OK'
                });
                LoadEquipmentList();
            }, 'json');
        }
    });
}
