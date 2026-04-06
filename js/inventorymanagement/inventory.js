var itemsTblValue = "", SelectedFromItems = "";

var itemsTbl, isConsign = "No", serialProductList = "", productSINoList = "", selectBy = "", mark = 0, Tmark = 0, Area = "-", Warranty = "-";

var particularsTbl, tableCustomVal = "", headerData = "", reportType = "";

Initialize();

function getValByColumn(obj, colName){
    if (obj[colName] !== undefined && obj[colName] !== null) return obj[colName];
    var lc = String(colName).toLowerCase();
    for (var k in obj) {
        if (Object.prototype.hasOwnProperty.call(obj,k)) {
            if (String(k).toLowerCase() === lc) return obj[k];
        }
    }
    var aliasMap = {
        "SIno": ["SI"],
        "SI": ["SIno"],
        "Vat": ["VAT"],
        "VAT": ["Vat"],
        "SupplierSI": ["Supplier SI","Supplier_SI"],
        "Serialno": ["Serial No","Serial_No"],
        "DatePurchase": ["Date Purchased","DatePurchased"],
        "DateAdded": ["Date Added","Date_Added"],
        "TotalSRP": ["Total SRP","Total_SRP"],
        "DiscProduct": ["Disc Product","DiscountedProduct"],
        "DiscAmount": ["Disc Amount","DiscountAmount"],
        "DiscNewSRP": ["Disc New SRP","DiscountNewSRP"],
        "DiscNewTotalSRP": ["Disc New Total SRP","DiscountNewTotalSRP"],
        "Branch": ["BRANCH"],
        "Category": ["CATEGORY"],
    };
    var aliases = aliasMap[colName] || aliasMap[lc] || [];
    for (var i=0;i<aliases.length;i++){
        var a = aliases[i];
        if (obj[a] !== undefined && obj[a] !== null) return obj[a];
        var al = String(a).toLowerCase();
        for (var kk in obj) {
            if (Object.prototype.hasOwnProperty.call(obj,kk)) {
                if (String(kk).toLowerCase() === al) return obj[kk];
            }
        }
    }
    return '';
}
function Initialize(){
    $.ajax({
        url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#isynBranch").empty().append(`
                <option value="" disabled selected>Select</option>
                <option value="OVERALL">OVERALL</option>
            `);
            $.each(response.ISYNBRANCH,function(key,value){
                $("#isynBranch").append(`
                    <option value="${value["ItemName"]}">
                        ${value["ItemName"]}
                    </option>
                `);
            });

            $("#type").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.PRODTYPE,function(key,value){
                $("#type").append(`
                    <option value="${value["Type"]}">
                        ${value["Type"]}
                    </option>
                `);
            });

            $("#customerType").empty().append(`
                <option value="" disabled selected>Select</option>
                <option value="OTHER CLIENT">OTHER CLIENT</option>
            `);
            $.each(response.CUSTOMERTYPE,function(key,value){
                $("#customerType").append(`
                    <option value="${value["Type"]}">
                        ${value["Type"]}
                    </option>
                `);
            });

            // itemsTbl = $('#itemsTbl').DataTable({
            //     searching:false,
            //     ordering:false,
            //     info:false,
            //     paging:false,
            //     lengthChange:false,
            //     scrollY: '230px',
            //     scrollX: true,  
            //     scrollCollapse: true,
            //     responsive:false,
            //     columnDefs: [
            //         { targets: [ 0,1,2 ], visible:false, searchable:false }
            //     ],
            // });

            var options = {
                rtl: false,
                format: 'm/d/Y',
                timepicker: false,
                datepicker: true,
                startDate: false,
                maxDate: new Date(),
                closeOnDateSelect: false,
                closeOnTimeSelect: true,
                closeOnWithoutClick: true,
                closeOnInputClick: true,
                openOnFocus: true,
                mask: '99/99/9999',
            };
        
            $('.Date').datetimepicker(options);
            $('#fromDate').val('');
            $('#toDate').val('');
            $('.Date').on('blur', function(){
                var val = $(this).val();
                if (!val) return;
                var parts = val.split('/');
                if (parts.length === 3) {
                    var m = parseInt(parts[0],10), d = parseInt(parts[1],10), y = parseInt(parts[2],10);
                    var inputDate = new Date(y, m-1, d);
                    var today = new Date();
                    today.setHours(0,0,0,0);
                    if (inputDate > today) {
                        Swal.fire({icon:'warning',title:'Invalid date',text:'Future dates are not allowed'});
                        $(this).val('');
                    }
                }
            });
        }, 
        error:function(xhr,status,error){
            Swal.fire({icon:'error',title:'Initialize failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        }
    })
}

function InitiateTable(){
    if ($.fn.DataTable.isDataTable('#particularsTbl')) {
        particularsTbl = $('#particularsTbl').DataTable();
        return;
    }
    particularsTbl = $('#particularsTbl').DataTable({
        searching:true,
        ordering:false,
        info:true,
        paging:false,
        lengthChange:false,
        deferRender:true,
        processing:true,
        scrollY: '500px',
        scrollX: true,  
        scrollCollapse: true,
        responsive:false,
        retrieve:true,
    });
}

function BuildReportTable(listViewName,ListName){
    $.ajax({
        url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
        type:"POST",
        data:{action:"BuildReportTable", listViewName:listViewName, ListName:ListName},
        dataType:"JSON",
        beforeSend:function(){
            $("#particularsList").empty();
            if ($.fn.DataTable.isDataTable('#particularsTbl')) {
                $('#particularsTbl').DataTable().clear();
                $('#particularsTbl').DataTable().destroy();
            }
        },
        success:function(response){
                headerData = response.TBLHEADER
                if (!headerData || headerData.length === 0) {
                    if (ListName === "OUTGOING INVENTORY") {
                        headerData = [
                            { ColumnName: "DateAdded" },{ ColumnName: "SupplierSI" },{ ColumnName: "Soldto" },{ ColumnName: "SI" },
                            { ColumnName: "Serialno" },{ ColumnName: "Product" },{ ColumnName: "Supplier" },{ ColumnName: "Quantity" },
                            { ColumnName: "DealerPrice" },{ ColumnName: "TotalPrice" },{ ColumnName: "SRP" },{ ColumnName: "TotalSRP" },
                            { ColumnName: "VatSales" },{ ColumnName: "VAT" },{ ColumnName: "Stock" },{ ColumnName: "Branch" },
                            { ColumnName: "Category" },{ ColumnName: "DiscInterest" },{ ColumnName: "DiscAmount" },{ ColumnName: "DiscNewSRP" },{ ColumnName: "DiscNewTotalSRP" }
                        ];
                    } else {
                        headerData = [
                            { ColumnName: "DateAdded" },{ ColumnName: "DatePurchase" },{ ColumnName: "SIno" },{ ColumnName: "Serialno" },
                            { ColumnName: "Product" },{ ColumnName: "Supplier" },{ ColumnName: "Quantity" },{ ColumnName: "DealerPrice" },
                            { ColumnName: "TotalPrice" },{ ColumnName: "SRP" },{ ColumnName: "TotalSRP" },{ ColumnName: "VatSales" },
                            { ColumnName: "Vat" },{ ColumnName: "Stock" },{ ColumnName: "Branch" },{ ColumnName: "Category" }
                        ];
                    }
                }
                var existLC = [];
                for (var i=0;i<headerData.length;i++){ existLC.push(String(headerData[i].ColumnName).toLowerCase()); }
                function hasAny(names){
                    for (var j=0;j<names.length;j++){
                        if (existLC.indexOf(String(names[j]).toLowerCase()) !== -1) return true;
                    }
                    return false;
                }
                var requiredSets = [
                    ["DateAdded"],
                    ["DatePurchase"],
                    ["SIno","SI"],
                    ["Serialno"],
                    ["Product"]
                ];
                for (var r=requiredSets.length-1;r>=0;r--){
                    var set = requiredSets[r];
                    if (!hasAny(set)){
                        headerData.unshift({ ColumnName: set[0] });
                        existLC.unshift(String(set[0]).toLowerCase());
                    }
                }

                var preferred = ["SIno","Serialno","Product","Supplier","Stock","Branch","Category","Quantity","DateAdded","SRP","Vat","VatSales"];
                var mapIndex = {};
                for (var i=0;i<headerData.length;i++){
                    var lc = String(headerData[i].ColumnName).toLowerCase();
                    mapIndex[lc] = i;
                }
                var ordered = [];
                for (var i=0;i<preferred.length;i++){
                    var idx = mapIndex[String(preferred[i]).toLowerCase()];
                    if (idx !== undefined) { ordered.push(headerData[idx]); }
                }
                for (var i=0;i<headerData.length;i++){
                    var lc = String(headerData[i].ColumnName).toLowerCase();
                    if (preferred.map(function(x){return String(x).toLowerCase();}).indexOf(lc) === -1) {
                        ordered.push(headerData[i]);
                    }
                }
                headerData = ordered;

            var headerRow = "";
            for (var i = 0; i < headerData.length; i++) { 
                var headerName = headerData[i].ColumnName; // Access second element of each header entry

                headerRow += (i > 0) ? "," : "<tr>";
                headerRow += "<th>" + headerName + "</th>";
            }

            headerRow += "</tr>";
            $("#particularsTbl thead").html(headerRow);
            InitiateTable();
        }, 
        error:function(xhr,status,error){
            Swal.fire({icon:'error',title:'Load header failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        }
    })
}
function isPresetSelect() {
    $("#particularsTbl thead").html("");
    if ($("#isPreset").is(":checked")) {
        $('#presetSelect').val("");
        $('#presetSelect').prop("disabled", false);
        $("#WithoutConsignment").prop("disabled", false);
        $("#onlyConsignment").prop("disabled", false);
        $("#noFreebies").prop("disabled", false);
        $("#onlyFreebies").prop("disabled", false);
        ClearCustomSelect();
    } else {
        $('#presetSelect').prop("disabled", true);
    }
}

function PresetSelectVal(val){
    if (val == "INCOMING INVENTORY"){
        $("#TransProdDiv").show();
        $("#incTransProd").prop("checked", false);
        $("#DiscProdDiv").hide();
        $("#discProd").prop("checked", false);
    } else if (val == "OUTGOING INVENTORY"){
        $("#TransProdDiv").hide();
        $("#incTransProd").prop("checked", false);
        $("#DiscProdDiv").show();
        $("#discProd").prop("checked", false);
    } else {
        $("#TransProdDiv").hide();
        $("#incTransProd").prop("checked", false);
        $("#DiscProdDiv").hide();
        $("#discProd").prop("checked", false);
    }

    if (val == "CURRENT INVENTORY" || val == "ENDING INVENTORY" || val == "INCOMING INVENTORY" || val == "PREVIOUS INVENTORY") {
        BuildReportTable("lstMCList","CURRENT INVENTORY");
    } else if (val == "OUTGOING INVENTORY") {
        BuildReportTable("lstMCList","OUTGOING INVENTORY");
    } else {
        $("#particularsTbl thead").html("");
    }
}

function WithoutConsign(){
    if ($("#WithoutConsignment").is(":checked")) {
        $("#onlyConsignment").prop("checked", false);
    } else {
        $("#WithoutConsignment").prop("checked", false);
    }
}

function ConsignOnly() {
    if ($("#onlyConsignment").is(":checked")) {
        $("#WithoutConsignment").prop("checked", false);
    } else {
        $("#onlyConsignment").prop("checked", false);
    }
}

function NoFreebies(){
    if ($("#noFreebies").is(":checked")) {
        $("#onlyFreebies").prop("checked", false);
    } else {
        $("#noFreebies").prop("checked", false);
    }
}

function FreebiesOnly() {
    if ($("#onlyFreebies").is(":checked")) {
        $("#noFreebies").prop("checked", false);
    } else {
        $("#onlyFreebies").prop("checked", false);
    }
}

function isCustomSelect() {
    $("#particularsTbl thead").html("");
    if ($("#isCustom").is(":checked")) {
        $('#customSelect').val("");
        $('#customSelect').prop("disabled", false);
        $('#customColumns').val("");
        $('#customColumns').prop("disabled", false);
        $('#customValues').val("");
        $('#customValues').prop("disabled", false);
        ClearPresetSelect ();
    } else {
        $('#customSelect').prop("disabled", false);
    }
}

function PresetCustomVal(val){
    if (val == "CURRENT INVENTORY" || val == "PREVIOUS INVENTORY") {
        BuildReportTable("lstMCList","CURRENT INVENTORY");
    } else if (val == "OUTGOING INVENTORY") {
        BuildReportTable("lstMCList","OUTGOING INVENTORY");
    } else {
        $("#particularsTbl thead").html("");
    }

    LoadCustomColumnNames(val);
}

function LoadCustomColumnNames(table){
    $.ajax({
        url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
        type:"POST",
        data:{action:"LoadCustomColumnNames", table:table},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            tableCustomVal = response.TBLCUSTOM;
            $("#customColumns").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.COLUMNS,function(key,value){
                $("#customColumns").append(`
                    <option value="${value["Columns"]}">
                        ${value["Columns"]}
                    </option>
                `);
            });
        }, 
    })
}

function LoadCustomColumnValue(column){
    let table = $('#customSelect').val();

    $.ajax({
        url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
        type:"POST",
        data:{action:"LoadCustomColumnValue", column:column, table:table},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#customValues").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.VALUES,function(key,value){
                $("#customValues").append(`
                    <option value="${value["ColVal"]}">
                        ${value["ColVal"]}
                    </option>
                `);
            });
        }, 
    })
}

function ClearPresetSelect(){
    $('#presetSelect').val("");
    $('#presetSelect').prop("disabled", true);
    $("#WithoutConsignment").prop("checked", false);
    $("#WithoutConsignment").prop("disabled", true);
    $("#onlyConsignment").prop("checked", false);
    $("#onlyConsignment").prop("disabled", true);
    $("#noFreebies").prop("checked", false);
    $("#noFreebies").prop("disabled", true);
    $("#onlyFreebies").prop("checked", false);
    $("#onlyFreebies").prop("disabled", true);
    $("#TransProdDiv").hide();
    $("#incTransProd").prop("checked", false);
    $("#DiscProdDiv").hide();
    $("#discProd").prop("checked", false);
}

function ClearCustomSelect(){
    $('#customSelect').val("");
    $('#customSelect').prop("disabled", true);
    $('#customColumns').val("");
    $('#customColumns').prop("disabled", true);
    $('#customValues').val("");
    $('#customValues').prop("disabled", true);
}

function SearchBtn(){
    var isynBranch = $('#isynBranch').val();

    var ispreset = "";
    var presetSelect = "";
    var withoutconsign = "";
    var onlyconsign = "";
    var nofreebies = "";
    var onlyfreebies = "";
    var inctransferprod = "";
    var discprod = "";
    if ($("#isPreset").is(":checked")) {
        ispreset = $('#isPreset').val();
        presetSelect = $('#presetSelect').val();
        withoutconsign = $('#WithoutConsignment').prop('checked') ? $('#WithoutConsignment').val() : "";
        onlyconsign = $('#onlyConsignment').prop('checked') ? $('#onlyConsignment').val() : "";
        nofreebies = $('#noFreebies').prop('checked') ? $('#noFreebies').val() : "";
        onlyfreebies = $('#onlyFreebies').prop('checked') ? $('#onlyFreebies').val() : "";
        inctransferprod = $('#incTransProd').prop('checked') ? $('#incTransProd').val() : "";
        discprod = $('#discProd').prop('checked') ? $('#discProd').val() : "";
    }

    var iscustom = "";
    var customSelect = "";
    var customColumn = "";
    var customValue = "";
    if ($("#isCustom").is(":checked")) {
        iscustom = $('#isCustom').val();
        customSelect = $('#customSelect').val();
        customColumn = $('#customColumns').val();
        customValue = $('#customValues').val();
    }

    var fromdate = $('#fromDate').val();
    var todate = $('#toDate').val();
    (function enforceNoFuture(){
        function clamp(val){ 
            if (!val) return '';
            var p = val.split('/'); 
            if (p.length!==3) return val;
            var m = parseInt(p[0],10), d = parseInt(p[1],10), y = parseInt(p[2],10);
            var dt = new Date(y, m-1, d);
            var today = new Date(); today.setHours(0,0,0,0);
            if (dt > today) return '';
            return val;
        }
        fromdate = clamp(fromdate);
        todate = clamp(todate);
        $('#fromDate').val(fromdate);
        $('#toDate').val(todate);
    })();

    if (isynBranch == "" || isynBranch == null) {
        isynBranch = "OVERALL";
        $('#isynBranch').val('OVERALL');
    }

    $.ajax({
        url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
        type:"POST",
        data:{action:"GenerateInventoryReport", ISYNBRANCH:isynBranch, ispreset:ispreset, presetSelect:presetSelect, withoutconsign:withoutconsign, onlyconsign:onlyconsign, nofreebies:nofreebies, onlyfreebies:onlyfreebies, inctransferprod:inctransferprod, discprod:discprod, iscustom:iscustom, tableCustomVal:tableCustomVal, customSelect:customSelect, customColumn:customColumn, customValue:customValue, fromdate:fromdate, todate:todate, REPORTTYPE:reportType, HEADERDATA:JSON.stringify(headerData)},
        dataType:"JSON",
        beforeSend:function(){
            $("#particularsList").empty();
            if ($.fn.DataTable.isDataTable('#particularsTbl')) {
                $('#particularsTbl').DataTable().clear();
                $('#particularsTbl').DataTable().destroy();
            }
        },
        success:function(response){
            var sTableName = response.TABLECUSTOMVAL;
                reportType = response.REPORTTYPE;

            var tpQuantity = 0, tpDprice = 0, tpPrice = 0, tpSRP = 0, tpTSRP = 0, tpMarkup = 0, tpTMarkup = 0, tpVatSales = 0, tpVat = 0;

            var myTotalQty_transfer = 0, myTotalDP_transfer = 0, myTotalQty_received = 0, myTotalDP_received = 0;
            var colNames = [];
            if (Array.isArray(headerData) && headerData.length > 0) {
                for (var i = 0; i < headerData.length; i++) {
                    colNames.push(headerData[i].ColumnName);
                }
            }
            var normalized = function(s){ return String(s||'').toLowerCase().replace(/\s+/g,''); };
            var seen = {};
            var prunedCols = [];
            // Determine which columns have at least one non-empty value
            for (var i=0;i<colNames.length;i++){
                var cname = colNames[i];
                var key = normalized(cname);
                if (seen[key]) continue;
                var hasData = false;
                if (response.REPORTDATA && response.REPORTDATA.length > 0) {
                    for (var r=0;r<response.REPORTDATA.length;r++){
                        var v = getValByColumn(response.REPORTDATA[r], cname);
                        if (v !== '' && v !== null && v !== undefined) { hasData = true; break; }
                    }
                }
                if (String(cname).toLowerCase() === 'datepurchase') { hasData = true; }
                if (hasData) { prunedCols.push(cname); seen[key] = true; }
            }
            // If pruning changes header, rewrite the header before rendering
            if (prunedCols.length > 0) {
                var preferred = ["SIno","SI","Serialno","Product","Supplier","Stock","Branch","Category","Quantity","DateAdded","DatePurchase","SRP","Vat","VatSales"];
                var lowerPref = preferred.map(function(x){ return String(x).toLowerCase(); });
                var indexMap = {};
                for (var i=0;i<prunedCols.length;i++){ indexMap[String(prunedCols[i]).toLowerCase()] = prunedCols[i]; }
                var orderedCols = [];
                for (var i=0;i<lowerPref.length;i++){
                    var key = lowerPref[i];
                    if (indexMap[key] !== undefined) { orderedCols.push(indexMap[key]); delete indexMap[key]; }
                }
                for (var k in indexMap){ if (Object.prototype.hasOwnProperty.call(indexMap,k)) { orderedCols.push(indexMap[k]); } }
                var headerRow = "<tr>";
                for (var i=0;i<orderedCols.length;i++){ headerRow += "<th>"+orderedCols[i]+"</th>"; }
                headerRow += "</tr>";
                $("#particularsTbl thead").html(headerRow);
                colNames = orderedCols;
            }

            if (sTableName == "tbl_invlist" || sTableName == "tbl_inventorychecking" || reportType == "CURRENT INVENTORY" || reportType == "PREVIOUS INVENTORY" || reportType == "ENDING INVENTORY" || reportType == "INCOMING INVENTORY"){
                if (response.REPORTDATA && response.REPORTDATA.length > 0) {
                    $.each(response.REPORTDATA,function(key,value){
                        var rowHtml = "<tr>";
                        for (var c = 0; c < colNames.length; c++) {
                            var col = colNames[c];
                            var v = getValByColumn(value, col);
                            rowHtml += "<td>" + v + "</td>";
                        }
                        rowHtml += "</tr>";
                        $("#particularsList").append(rowHtml);
                        if (value["Quantity"] !== undefined) { tpQuantity += Number(value["Quantity"]) || 0; }
                        if (value["DealerPrice"] !== undefined) { tpDprice += Number(String(value["DealerPrice"]).replace(/,/g,'')) || 0; }
                        if (value["TotalPrice"] !== undefined) { tpPrice += Number(String(value["TotalPrice"]).replace(/,/g,'')) || 0; }
                        if (value["SRP"] !== undefined) { tpSRP += Number(String(value["SRP"]).replace(/,/g,'')) || 0; }
                        if (value["TotalSRP"] !== undefined) { tpTSRP += Number(String(value["TotalSRP"]).replace(/,/g,'')) || 0; }
                        if (value["VatSales"] !== undefined) { tpVatSales += Number(String(value["VatSales"]).replace(/,/g,'')) || 0; }
                        if (value["Vat"] !== undefined) { tpVat += Number(String(value["Vat"]).replace(/,/g,'')) || 0; }
                    })
                }
                InitiateTable();

                $.each(response.TRANSFERPRODRPT,function(key,value){
                    if (value["TransferType"] == "TRANSFER"){
                        myTotalQty_transfer += value["TotalQuantity"];
                        myTotalDP_transfer += value["TotalDP"];
                    } else {
                        myTotalQty_received += value["TotalQuantity"];
                        myTotalDP_received += value["TotalDP"];
                    }

                    tpQuantity += myTotalQty_received;
                    tpPrice += myTotalDP_received;

                    tpQuantity -= myTotalQty_transfer;
                    tpPrice -= myTotalDP_transfer;
                })             
                
                $("#totalQuantity").val(formatAmtVal(tpQuantity));
                $("#dealersPrice").val(formatAmtVal(tpDprice));
                $("#totalDP").val(formatAmtVal(tpPrice));
                $("#srp").val(formatAmtVal(tpSRP));
                $("#totalsrp").val(formatAmtVal(tpTSRP));
                $("#vatSales").val(formatAmtVal(tpVatSales));
                $("#vat").val(formatAmtVal(tpVat));
            } else {
                if (response.REPORTDATA && response.REPORTDATA.length > 0) {
                    $.each(response.REPORTDATA,function(key,value){
                        var rowHtml = "<tr>";
                        for (var c = 0; c < colNames.length; c++) {
                            var col = colNames[c];
                            var v = getValByColumn(value, col);
                            rowHtml += "<td>" + v + "</td>";
                        }
                        rowHtml += "</tr>";
                        $("#particularsList").append(rowHtml);
                        if (value["Quantity"] !== undefined) { tpQuantity += Number(value["Quantity"]) || 0; }
                        if (value["DealerPrice"] !== undefined) { tpDprice += Number(String(value["DealerPrice"]).replace(/,/g,'')) || 0; }
                        if (value["TotalPrice"] !== undefined) { tpPrice += Number(String(value["TotalPrice"]).replace(/,/g,'')) || 0; }
                        if (value["DiscProduct"] == "YES") {
                            if (value["DiscNewSRP"] !== undefined) { tpSRP += Number(String(value["DiscNewSRP"]).replace(/,/g,'')) || 0; }
                            if (value["DiscNewTotalSRP"] !== undefined) { tpTSRP += Number(String(value["DiscNewTotalSRP"]).replace(/,/g,'')) || 0; }
                        } else {
                            if (value["SRP"] !== undefined) { tpSRP += Number(String(value["SRP"]).replace(/,/g,'')) || 0; }
                            if (value["TotalSRP"] !== undefined) { tpTSRP += Number(String(value["TotalSRP"]).replace(/,/g,'')) || 0; }
                        }
                        if (value["VatSales"] !== undefined) { tpVatSales += Number(String(value["VatSales"]).replace(/,/g,'')) || 0; }
                        if (value["VAT"] !== undefined) { tpVat += Number(String(value["VAT"]).replace(/,/g,'')) || 0; }
                    })
                }
                InitiateTable();
                $("#totalQuantity").val(formatAmtVal(tpQuantity));
                $("#dealersPrice").val(formatAmtVal(tpDprice));
                $("#totalDP").val(formatAmtVal(tpPrice));
                $("#srp").val(formatAmtVal(tpSRP));
                $("#totalsrp").val(formatAmtVal(tpTSRP));
                $("#vatSales").val(formatAmtVal(tpVatSales));
                $("#vat").val(formatAmtVal(tpVat));
            }
        }, 
        error:function(xhr,status,error){
            Swal.fire({icon:'error',title:'Search failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        }
    })
}

function PrintInventoryReport(){
    if(particularsTbl.rows().count() === 0){
        // When Data Inv List is empty, this will halt the printing
        Swal.fire({
            icon:'warning',
            title: 'Nothing to print!',
        });
        return;
    } else {
        var isynBranch = $('#isynBranch').val();
        var prevLen = particularsTbl.page.len();
        var prevPage = particularsTbl.page();
        particularsTbl.page.len(-1).draw();
        let Data = particularsTbl.rows({search:'applied'}).data().toArray();
        particularsTbl.page.len(prevLen).draw(false);
        particularsTbl.page(prevPage).draw(false);
        var visibleHeader = [];
        $('#particularsTbl thead th').each(function(){ visibleHeader.push({ ColumnName: $(this).text() }); });
        let formdata = new FormData();
        formdata.append("action","GenerateInventoryReport");
        formdata.append("HEADERDATA",JSON.stringify(visibleHeader.length ? visibleHeader : headerData));
        formdata.append("DATA",JSON.stringify(Data));
        formdata.append("ISYNBRANCH",isynBranch);
        formdata.append("REPORTTYPE",reportType);

        $.ajax({
            url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
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
                window.open(window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php?type=PrintInventoryReport");
                // $('#printBtn').prop('disabled', true);
            },
        });
    }
}

// =======================================================================================

function formatAmtVal(value) {
    // Remove any characters that are not digits, commas, or periods
    let cleanValue = value.toString().replace(/[^0-9.,]/g, '');
    // Remove commas for formatting purposes
    cleanValue = cleanValue.replace(/,/g, '');
    if (cleanValue !== '') {
        // Parse the cleaned value to a float and ensure two decimal places
        let formattedValue = parseFloat(cleanValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return formattedValue; // Return the formatted value
    }    
    return '0.00'; // Return an empty string if input is invalid or empty
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

function PrintInventoryReportDB(){
    if(!$.fn.DataTable.isDataTable('#particularsTbl') || particularsTbl.rows().count() === 0){
        Swal.fire({icon:'warning',title:'Nothing to print!'});
        return;
    }
    var isynBranch = $('#isynBranch').val();
    var ispreset = $("#isPreset").is(":checked") ? $('#isPreset').val() : "";
    var presetSelect = $('#presetSelect').val();
    var withoutconsign = $('#WithoutConsignment').prop('checked') ? $('#WithoutConsignment').val() : "";
    var onlyconsign = $('#onlyConsignment').prop('checked') ? $('#onlyConsignment').val() : "";
    var nofreebies = $('#noFreebies').prop('checked') ? $('#noFreebies').val() : "";
    var onlyfreebies = $('#onlyFreebies').prop('checked') ? $('#onlyFreebies').val() : "";
    var inctransferprod = $('#incTransProd').prop('checked') ? $('#incTransProd').val() : "";
    var discprod = $('#discProd').prop('checked') ? $('#discProd').val() : "";
    var iscustom = $("#isCustom").is(":checked") ? $('#isCustom').val() : "";
    var customSelect = $('#customSelect').val();
    var customColumn = $('#customColumns').val();
    var customValue = $('#customValues').val();
    var fromdate = $('#fromDate').val();
    var todate = $('#toDate').val();
    var prevLen = particularsTbl.page.len();
    var prevPage = particularsTbl.page();
    particularsTbl.page.len(-1).draw();
    var Data = particularsTbl.rows({search:'applied'}).data().toArray();
    particularsTbl.page.len(prevLen).draw(false);
    particularsTbl.page(prevPage).draw(false);
    var visibleHeader = [];
    $('#particularsTbl thead th').each(function(){ visibleHeader.push({ ColumnName: $(this).text() }); });
    let formdata = new FormData();
    formdata.append("action","GenerateInventoryReport");
    formdata.append("HEADERDATA",JSON.stringify(visibleHeader.length ? visibleHeader : headerData));
    formdata.append("ISYNBRANCH",isynBranch);
    formdata.append("REPORTTYPE",reportType);
    formdata.append("ispreset",ispreset);
    formdata.append("presetSelect",presetSelect);
    formdata.append("withoutconsign",withoutconsign);
    formdata.append("onlyconsign",onlyconsign);
    formdata.append("nofreebies",nofreebies);
    formdata.append("onlyfreebies",onlyfreebies);
    formdata.append("inctransferprod",inctransferprod);
    formdata.append("discprod",discprod);
    formdata.append("iscustom",iscustom);
    formdata.append("tableCustomVal",tableCustomVal);
    formdata.append("customSelect",customSelect);
    formdata.append("customColumn",customColumn);
    formdata.append("customValue",customValue);
    formdata.append("fromdate",fromdate);
    formdata.append("todate",todate);
    formdata.append("DATA",JSON.stringify(Data));
    $.ajax({
        url: window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php",
        type: "POST",
        data:formdata,
        processData:false,
        cache:false,
        contentType:false,
        dataType:"JSON",
        success: function() {
            window.open(window.BASE_PATH + "/routes/inventorymanagement/inventory.route.php?type=PrintInventoryReport");
        }
    });
}