var itemsTbl, itemsTblValue = "", SelectedFromItems = "", isConsign = "No", serialProductList = "", productSINoList = "", selectBy = "", mark = 0, Tmark = 0, Area = "-", Warranty = "-";

var branchConsignTbl, branchConsignTblValue = "", SelectedFromBranchConsign = "", ConsignBranchList, totalAddedConsignItem = 0;

var listTbl;

var productTbl, SelectedFromTransProd = "";
var SelectedTransNo = "";

// Base path for AJAX requests
var BASE_PATH = window.BASE_PATH || '';
var ROUTE_URL = BASE_PATH + '/routes/inventorymanagement/transmittalreceipt.route.php';

// Use native dropdowns (no select2)

SetTransactionDate();

Initialize();

// Auto-capitalize all text inputs and textareas
$(document).on('input', 'input[type="text"], textarea', function() {
    this.value = this.value.toUpperCase();
});

function SetTransactionDate(){
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

    $('#dateCarrier').datetimepicker(options);

    // Date Received: Allow only future dates (including today)
    var futureDateOptions = Object.assign({}, options);
    futureDateOptions.minDate = new Date();
    futureDateOptions.maxDate = false; // Remove the maxDate restriction for receipt
    
    $('#dateReceivedBy').datetimepicker(futureDateOptions);

    $('#dateReceivedBy').on('blur', function() {
        var val = $(this).val();
        if (!val) return;
        var p = val.split('/');
        if (p.length === 3) {
            var m = parseInt(p[0], 10), d = parseInt(p[1], 10), y = parseInt(p[2], 10);
            var dt = new Date(y, m - 1, d);
            dt.setHours(0, 0, 0, 0);
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            if (dt < today) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Date',
                    text: 'Past dates are not allowed for the Date Received field.'
                });
                $(this).val('');
            }
        }
    });

    $('#transmittalDateFrom').datetimepicker(options);
    $('#transmittalDateTo').datetimepicker(options);
    $('#transmittalDateFrom').val('');
    $('#transmittalDateTo').val('');
    $('#transmittalDateFrom,#transmittalDateTo').on('blur',function(){
        var val = $(this).val();
        if (!val) return;
        var p = val.split('/');
        if (p.length===3){
            var m = parseInt(p[0],10), d = parseInt(p[1],10), y = parseInt(p[2],10);
            var dt = new Date(y, m-1, d);
            var today = new Date(); today.setHours(0,0,0,0);
            if (dt > today){
                Swal.fire({icon:'warning',title:'Invalid date',text:'Future dates are not allowed'});
                $(this).val('');
            }
        }
    });

    // Swal.fire({
    //     title: 'Please Select Transaction Date.',
    //     html: '<input id="DateTransaction" readonly class="swal2-input">',
    //     confirmButtonText: 'Set',
    //     showLoaderOnConfirm: false,
    //     didOpen:function(e){
    //         $('#DateTransaction').datetimepicker(options);
    //     },
    //     allowOutsideClick: false,
    // }).then((result) => {
    //     if (result.isConfirmed) {

    //         if($("#DateTransaction").val() == ""){
    //             location.reload();
    //         }

    //         var date = new Date($("#DateTransaction").val());

    //         $("#transactionDate").val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());

    //         $("#transmittalDateFrom").val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());

    //         $("#transmittalDateTo").val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
    //     }
    // })
}

function Initialize(){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        success:function(response){
            $("#transmittalNo").val(response.TRANSMITNO);
            $("#fromRep").val(response.ORGNAME);

            // Populate branch dropdown
            if (response.ISYNBRANCH) {
                $("#isynBranch").empty().append(`<option value="" disabled selected>Select</option>`);
                $.each(response.ISYNBRANCH, function(key, value){
                    $("#isynBranch").append(`<option value="${value["Stock"]}">${value["Stock"]}</option>`);
                });
            }

            // Initialize DataTable if not already done
            if (!itemsTbl || !$.fn.DataTable.isDataTable('#itemsTbl')) {
                itemsTbl = $('#itemsTbl').DataTable({
                    searching:false, ordering:false, info:false, paging:false,
                    scrollY: '230px', scrollX: true, scrollCollapse: true, responsive:false
                });
            }
        }, 
    });
}

function isConsignmentBox() {
    if ($("#isConsignment").is(":checked")) {
        isConsign = "Yes";
        $('#consignmentBranchContainer').show();
        $('#isynBranchContainer').hide();
    } else {
        isConsign = "No";
        $('#consignmentBranchContainer').hide();
        $('#isynBranchContainer').show();
    }
    $("#isynBranch").val("");
    $("#consignmentBranch").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#type").val("");
    $("#category").empty();
    $("#serialNo").prop("checked", false);
    $("#productName").prop("checked", false);
    $("#SerialProduct").empty();
    $("#SINo").empty();
    ClearProductSummary();
}

function LoadBranch(value){
    if ($("#isConsignment").is(":checked")) {
        $.ajax({
            url: ROUTE_URL,
            type:"POST",
            data:{action:"LoadBranch", value:value},
            dataType:"JSON",
            beforeSend:function(){
            },
            success:function(response){
                $("#consignmentBranch").empty().append(`<option value="" disabled selected>Select</option>`);
                $.each(response.BRANCH,function(key,value){
                    $("#consignmentBranch").append(`
                        <option value="${value["Stock"]}">
                            ${value["Stock"]}
                        </option>
                    `);
                });
    
            }, 
        })
    }
    $("#type").val("");
    $("#category").empty();
    $("#serialNo").prop("checked", false)
    $("#productName").prop("checked", false)
    $("#SerialProduct").empty();
    $("#SINo").empty();
    ClearProductSummary();
}

function forBranchClear(){
    $("#type").val("");
    $("#category").empty();
    $("#serialNo").prop("checked", false)
    $("#productName").prop("checked", false)
}

function LoadCategory(type){
    var isynBranch = $("#isynBranch").val();
    var consignBranch = $("#consignmentBranch").val();

    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadCategory", isConsign:isConsign, type:type, isynBranch:isynBranch, consignBranch:consignBranch},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#category").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.CATEG,function(key,value){
                $("#category").append(`
                    <option value="${value["Category"]}">
                        ${value["Category"]}
                    </option>
                `);
            });
        }, 
    })
    $("#serialNo").prop("checked", false)
    $("#productName").prop("checked", false)
    $("#SerialProduct").empty();
    $("#SINo").empty();
    ClearProductSummary();
}

function LoadSerialProduct(category){
    var type = $("#type").val();
    var isynBranch = $("#isynBranch").val();
    var consignBranch = $("#consignmentBranch").val();
    $("#productName").prop("checked", true)
    selectBy = "productName";

    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadSerialProduct", isConsign:isConsign, type:type, category:category, isynBranch:isynBranch, consignBranch:consignBranch},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            serialProductList = response.SRKPRDT;
            productSINoList = response.PRDTSINO;

            $("#SerialProduct").empty().append(`<option value="" disabled selected>Select</option>`);
            $("#SINo").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.SRKPRDT,function(key,value){
                $("#SerialProduct").append(
                    $('<option></option>')
                        .val(value["Product"])
                        .text(value["Product"])
                );
            });
        }, 
    })
    ClearProductSummary();
}

$("input[name='selectBy']").change(function(){
    $("#SerialProduct").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#SINo").empty().append(`<option value="" disabled selected>Select</option>`);
    selectBy = $(this).val(); 
    if($(this).val() == "serial"){
        $.each(serialProductList,function(key,value){
            $("#SerialProduct").append(`
                <option value="${value["Serialno"]}">
                    ${value["Serialno"]}
                </option>
            `);
        });
    }else if($(this).val() == "productName"){
        $.each(serialProductList,function(key,value){
            $("#SerialProduct").append(
                $('<option></option>')
                    .val(value["Product"])
                    .text(value["Product"])
            );
        });
    }
    ClearProductSummary();
})

function LoadProductSINo (SerialProduct){
    $("#SINo").empty().append(`<option value="" disabled selected>Select</option>`);
    $.each(serialProductList,function(key,value){
        if(value["Serialno"] == SerialProduct || value["Product"] == SerialProduct){
            $("#SINo").append(`
                <option value="${value["SIno"]}">
                    ${value["SIno"]}
                </option>
            `);
        }
    });
    ClearProductSummary();
}

function LoadProductSummary(){
    var isynBranch = $("#isynBranch").val();
    var consignBranch = $("#consignmentBranch").val();
    var type = $("#type").val();
    var category = $("#category").val();
    var serialProduct = $("#SerialProduct").val();
    var SINo = $("#SINo").val();

    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadProductSummary", isConsign:isConsign, selectBy:selectBy, type:type, category:category, serialProduct:serialProduct, SINo:SINo, isynBranch:isynBranch, consignBranch:consignBranch},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            let info =  response.PSUMMARY;
            $('#psSupplierSI').val(info["SIno"]);
            $('#psSerialNo').val(info["Serialno"]);
            $('#psProduct').val(info["Product"]);
            $('#psSupplier').val(info["Supplier"]);
            $('#psSRP').val(formatAmtVal(info["SRP"]));
            $('#srpMerchSales').val(formatAmtVal(info["SRP"]));
            $('#psQuantity').val(info["Quantity"]);
            $('#psDealerPrice').val(formatAmtVal(info["DealerPrice"]));
            $('#psTotalPrice').val(formatAmtVal(info["TotalPrice"]));
            $('#finalSRP').val(formatAmtVal(info["SRP"]));
            Warranty = info["Warranty"];
        }, 
    })
}

function ComputeAvailQty(qty){
    var psQty = $('#psQuantity').val();

    if (qty == 0 || qty == null || qty == "") {
        Swal.fire({
            icon: "warning",
            title: "Please enter desired quantity.",
        });
        return false;
    }

    if (parseFloat(qty) > parseFloat(psQty)) {  
        Swal.fire({
            icon: "warning",
            title: "Insufficient available product quantity.",
        });
        $('#saleQty').val("");
        return false;
    }
}

function isEditSRPBTN() {
    if ($("#isEditSRP").is(":checked")) {
        $('#finalSRP').prop("disabled", false);
    } else {
        let  actualSRP = $('#psSRP').val();
        $('#finalSRP').prop("disabled", true);
        $('#finalSRP').val(actualSRP);
    }
}

function ClearProductSummary(){
    $('#psSupplierSI').val("");
    $('#psSerialNo').val("");
    $('#psProduct').val("");
    $('#psSupplier').val("");
    $('#psSRP').val("");
    $('#psQuantity').val("");
    $('#psDealerPrice').val("");
    $('#psTotalPrice').val("");
    $('#saleQty').val("");
    $('#finalSRP').val("");
}

function ClearAllFields(){
    // 1. Header Fields
    $("#toRep").val("");
    $("#remarks").val("");

    // 2. Branch/Consignment
    isConsign = "No";    
    $("#isConsignment").prop("checked", false);
    $('#consignmentBranchContainer').hide();
    $('#isynBranchContainer').show();
    $("#isynBranch").val("");
    $("#consignmentBranch").empty().append(`<option value="" disabled selected>Select</option>`);

    // 3. Product Selection
    $("#type").val("");
    $("#category").empty();
    $("#serialNo").prop("checked", false);
    $("#productName").prop("checked", false);
    $("#SerialProduct").empty();
    $("#SINo").empty();
    $("#saleQty").val("");
    $("#isEditSRP").prop("checked", false);
    $("#finalSRP").val("").prop("disabled", true);

    // 4. Other Details
    $("#isOtherDetails").prop("checked", false);
    $("#carrier").val("").prop("disabled", true);
    $("#dateCarrier").val("").prop("disabled", true);
    $("#receivedBy").val("").prop("disabled", true);
    $("#dateReceivedBy").val("").prop("disabled", true);

    // 5. Table
    if (itemsTbl) {
        itemsTbl.clear().draw();
    }

    ClearProductSummary();
}

// =======================================================================================

function addItem() {
    // 1. Get values from the form
    let branch = $('#isConsignment').is(':checked') ? $('#consignmentBranch').val() : $('#isynBranch').val();
    let type = $('#type').val();
    let categ = $('#category').val();
    let serialProduct = $('#SerialProduct').val();
    let SINo = $('#SINo').val();
    let saleQty = $('#saleQty').val();
    
    // Values from Product Summary (hidden/disabled inputs)
    let psSupplierSI = $('#psSupplierSI').val();
    let psSerialNo = $('#psSerialNo').val();
    let psProduct = $('#psProduct').val();
    let psSupplier = $('#psSupplier').val();
    let psSRP = ($('#psSRP').val() || "0").replace(/,/g, '');
    let editSRP = ($('#finalSRP').val() || "0").replace(/,/g, '');

    // 2. Validation
    if (!branch || !type || !categ || !serialProduct || !SINo || !saleQty || parseFloat(saleQty) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Details',
            text: 'Please ensure all product information and quantity are filled out.',
        });
        return;
    }

    // 3. Check if DataTable is ready
    if (!itemsTbl || !$.fn.DataTable.isDataTable('#itemsTbl')) {
        itemsTbl = $('#itemsTbl').DataTable({
            searching: false, ordering: false, info: false, paging: false,
            scrollY: '230px', scrollX: true, scrollCollapse: true
        });
    }

    // 4. Calculate Total
    let unitPrice = $('#isEditSRP').is(':checked') ? parseFloat(editSRP) : parseFloat(psSRP);
    if (isNaN(unitPrice)) unitPrice = 0;
    let total = parseFloat(saleQty) * unitPrice;

    // 5. Construct Display Name
    let displayProduct = psProduct;
    if (psSerialNo && psSerialNo !== "-") {
        displayProduct += " (S/N: " + psSerialNo + ")";
    }

    // 6. Duplicate Check
    let isDuplicate = false;
    itemsTbl.rows().every(function() {
        let data = this.data();
        if (data[3] === psSupplierSI && data[4] === psSerialNo && data[9] === branch) {
            isDuplicate = true;
        }
    });

    if (isDuplicate) {
        Swal.fire({
            icon: 'warning',
            title: 'Duplicate Item',
            text: 'This item is already in your list.',
        });
        return;
    }

    // 7. Add to Table
    try {
        itemsTbl.row.add([
            displayProduct.toUpperCase(),
            saleQty,
            formatAmtVal(total),
            psSupplierSI.toUpperCase(),
            psSerialNo.toUpperCase(),
            psProduct.toUpperCase(),
            psSupplier.toUpperCase(),
            categ.toUpperCase(),
            type.toUpperCase(),
            branch.toUpperCase()
        ]).draw(false);

        // 8. Success and Clear
        Swal.fire({
            icon: 'success',
            title: 'Item Added',
            timer: 1000,
            showConfirmButton: false
        });

        // Only clear product-specific fields
        $("#type").val("");
        $("#category").empty();
        $("#SerialProduct").empty();
        $("#SINo").empty();
        $("#saleQty").val("");
        $("#isEditSRP").prop("checked", false);
        $("#finalSRP").prop("disabled", true).val("");
        ClearProductSummary();

    } catch (err) {
        console.error("Error adding row:", err);
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Failed to add item to the list. Please try again.',
        });
    }
};

$('#itemsTbl tbody').on('click', 'tr',function(e){
    if(itemsTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#cancelProduct").attr("disabled",true);
            itemTblValue = "";
            SelectedFromItems = "";
        } else {
            itemsTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $("#cancelProduct").attr("disabled",false);
            itemsTblValue = $('#itemsTbl').DataTable().row(this).data();
            SelectedFromItems = this;
        }
    }
});

function cancelProduct(){
    if  (SelectedFromItems != "") {
        itemsTbl.row(SelectedFromItems).remove().draw(false);
        SelectedFromItems = "";
        $("#cancelProduct").attr("disabled",true);
    } else {
        SelectedFromItems = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select from the Items to delete',
        })
    }
}

function ClearPurchasedBy(){
    $('#customerType').val("").prop('disabled', false);
    $('#customerNameInputDiv').show();
    $('#customerNameInput').prop('disabled', false);
    $('#customerNameInput').prop('readonly', true);
    $('#customerNameInput').val("");
    $('#customerNameSelectDiv').hide();
    $('#customerNameSelect').val("").prop('disabled', false);

    $("#staffCheckbox").prop("checked", false);
    $("#branchUsed").prop("checked", false);
    $("mfiUsed").prop("checked", false);
    $("#staffCheckbox").prop("disabled", false);
    $("#branchUsed").prop("disabled", false);
    $("mfiUsed").prop("disabled", false);

    $('#tinNoinput').val("").prop('disabled', false);
    $('#fullAddress').val("").prop('disabled', false);
    $('#status').val("").prop('disabled', false);
}

function parseDate(dateStr) {
    let [month, day, year] = dateStr.split("/").map(Number);
    return new Date(year, month - 1, day); // Month is 0-based in JS
}

function SubmitBtn(){
    var clientTo = ($('#toRep').val() || "").toUpperCase();
    var fromRep = ($('#fromRep').val() || "").toUpperCase();
    var remarks = ($('#remarks').val() || "").toUpperCase();

    if (clientTo == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Please enter a recipient name',
        })
        return;
    }

    var carrier = "-";
    var dateCarrier = "-";
    var receivedBy = "-";
    var dateReceivedBy = "-";

    if ($('#isOtherDetails').is(':checked')){
        carrier = ($('#carrier').val() || "").toUpperCase();
        dateCarrier = $('#dateCarrier').val();
        receivedBy = ($('#receivedBy').val() || "").toUpperCase();
        dateReceivedBy = $('#dateReceivedBy').val();
        
        if (carrier == "" || receivedBy == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Please fill in the Carrier and Received By fields',
            })
            return;
        }
    }

    if(itemsTbl.rows().count() !== 0){
        let Data = itemsTbl.rows().data().toArray();
        let formdata = new FormData();
        formdata.append("action","SubmitInvOut");
        formdata.append("DATA",JSON.stringify(Data));
        formdata.append("toRep", clientTo);
        formdata.append("fromRep", fromRep);
        formdata.append("remarks", remarks);
        formdata.append("carrier", carrier);
        formdata.append("dateCarrier", dateCarrier);
        formdata.append("receivedBy", receivedBy);
        formdata.append("dateReceivedBy", dateReceivedBy);

        Swal.fire({
            icon: 'info',
            title: 'Proceed to submit entries?',
            showCancelButton: true,
            // allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/inventorymanagement/transmittalreceipt.route.php",
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
                        if(response.STATUS != "success"){
                            Swal.showValidationMessage(
                                response.MESSAGE,
                            )
                        }
                    },
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'success') {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: result.value.MESSAGE,
                    });
                    window.open("../../routes/inventorymanagement/transmittalreceipt.route.php?type=PrintSalesInvoice");
                    LoadTransaction();
                    ClearAllFields();
                    ClearPurchasedBy();
                    Initialize();
                } else if (result.value.STATUS != 'success') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: result.value.MESSAGE,
                    });
                }                
            }
        });

    } else {
        Swal.fire({
            icon: 'warning',
            title: 'No Entries available.',
        });
     }
}

// =======================================================================================

function SearchTransmittal(){    
    $("#listList").empty();
    $("#productList").empty();
    $("#rePrint").prop("disabled", true);
    
    // Clear search inputs in modal
    $("#transmittalClient").val("");
    $("#transmittalDateFrom").val("");
    $("#transmittalDateTo").val("");

    $("#SearchTransmittalMDL").modal("show");
    
    // Automatically trigger a search to show recent transmittals
    TransmittalSearch(true);
}

function TransmittalSearch(isInitial = false){
    var client = $("#transmittalClient").val();
    var fromdate = $("#transmittalDateFrom").val();
    var todate = $("#transmittalDateTo").val();
    
    if (!isInitial) {
        if (fromdate && todate){
            var fp = fromdate.split('/'), tp = todate.split('/');
            if (fp.length===3 && tp.length===3){
                var fd = new Date(parseInt(fp[2],10), parseInt(fp[0],10)-1, parseInt(fp[1],10));
                var td = new Date(parseInt(tp[2],10), parseInt(tp[0],10)-1, parseInt(tp[1],10));
                if (fd > td){
                    Swal.fire({icon:'warning',title:'Invalid range',text:'From date must be before To date'});
                    return;
                }
            }
        }

        if ((!client || client.trim() === "") && (!fromdate || !todate)) {
            Swal.fire({icon:'warning',title:'Enter search criteria',text:'Provide a client name, or a From/To date range'});
            return;
        }
    }

    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"TransmittalSearch",client:client,fromdate:fromdate,todate:todate},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#listTbl' ) ) {
                $('#listTbl').DataTable().clear();
                $('#listTbl').DataTable().destroy(); 
            }

            if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
                $('#productTbl').DataTable().clear();
                $('#productTbl').DataTable().destroy(); 
            }
            $("#productList").empty();

            $("#loadToList").prop("disabled", true);
        },
        success:function(response){
            $("#listList").empty();
            $.each(response.TRANSACTIONLIST,function(key,value){
                var date = value["DatePrepared"] || value["DateCarrier"] || value["DateReceived"] || '';
                $("#listList").append(`
                    <tr>
                        <td>${value["TransmittalNO"]}</td>
                        <td>${value["NameTO"]}</td>
                        <td>${date}</td>
                        <td>${value["isOUT"]}</td>
                        <td>${value["SalesInvoice"]}</td>
                    </tr>
                `);
            });

            listTbl = $('#listTbl').DataTable({
                searching:false,
                ordering:false,
                info:false,
                paging:false,
                lengthChange:false,
                scrollY: '230px',
                scrollX: true,
                scrollCollapse: true,
                responsive:false,
            });
        }, 
    })
}

$('#listTbl tbody').on('click', 'tr',function(e){
    if(listTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#rePrint").prop("disabled", true);
            $("#productList").empty();
        } else {
            listTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            var val = $('#listTbl').DataTable().row(this).data();
            var transNo = val[0];
            var clientName = val[1];
            var date = val[2];
            $("#rePrint").prop("disabled", false);
            SelectedTransNo = transNo;
        }
    }
});

function RePrint(){
    if (SelectedTransNo && SelectedTransNo !== "") {
        window.open(ROUTE_URL + "?type=PrintTransmittal&no=" + encodeURIComponent(SelectedTransNo));
    }
}
function TransmittalClear(){
    $("#transmittalClient").val("");
    $("#listList").empty();
    $("#transmittalDateFrom").val("");
    $("#transmittalDateTo").val("");
    if ( $.fn.DataTable.isDataTable( '#listTbl' ) ) {
        $('#listTbl').DataTable().clear();
        $('#listTbl').DataTable().destroy(); 
    }
    $("#rePrint").prop("disabled", true);
    SelectedTransNo = "";
}
// =======================================================================================

function isOtherDetailsBox() {
    if ($("#isOtherDetails").is(":checked")) {
        $("#carrier").prop("disabled", false);
        $("#dateCarrier").prop("disabled", false);
        $("#receivedBy").prop("disabled", false);
        $("#dateReceivedBy").prop("disabled", false);
    } else {
        $("#carrier").val("").prop("disabled", true);
        $("#dateCarrier").val("").prop("disabled", true);
        $("#receivedBy").val("").prop("disabled", true);
        $("#dateReceivedBy").val("").prop("disabled", true);
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
