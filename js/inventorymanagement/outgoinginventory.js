var itemsTbl, itemsTblValue = "", SelectedFromItems = "", isConsign = "No", serialProductList = "", productSINoList = "", selectBy = "", mark = 0, Tmark = 0, Area = "-", Warranty = "-";
var isAdding = false;

// Base path for AJAX requests
var BASE_PATH = window.BASE_PATH || '';
var ROUTE_URL = BASE_PATH + '/routes/inventorymanagement/outgoinginventory.route.php';

var branchConsignTbl, branchConsignTblValue = "", SelectedFromBranchConsign = "", ConsignBranchList, totalAddedConsignItem = 0;

var listTbl;
var productTbl, SelectedFromTransProd = "";

// Select2 removed - using standard dropdowns

$("#customerNameInput").on("input", function() {
    $(this).val($(this).val().replace(/[^a-zA-Z\s]/g, ''));
});

SetTransactionDate();

Initialize();
LoadTransaction();

// Load initial Regions on page load
LoadRegions();

function LoadRegions() {
    $.ajax({
        url: ROUTE_URL,
        type: "POST",
        data: { action: "LoadRegion" },
        dataType: "JSON",
        success: function(response) {
            $("#addrRegion").empty().append('<option value="" selected disabled>Select Region</option>');
            $.each(response.REGION, function(key, value) {
                $("#addrRegion").append(`<option value="${value.region}">${value.region}</option>`);
            });
        }
    });
}

function LoadProvince(region) {
    $("#addrProvince").empty().append('<option value="" selected disabled>Select Province</option>');
    $("#addrCity").empty().append('<option value="" selected disabled>Select City</option>');
    $("#addrBarangay").empty().append('<option value="" selected disabled>Select Barangay</option>');
    
    if(!region) return;

    $.ajax({
        url: ROUTE_URL,
        type: "POST",
        data: { action: "LoadProvince", region: region },
        dataType: "JSON",
        success: function(response) {
            $.each(response.PROVINCE, function(key, value) {
                $("#addrProvince").append(`<option value="${value.province}">${value.province}</option>`);
            });
        }
    });
    UpdateFullAddress();
}

function LoadCity(province) {
    $("#addrCity").empty().append('<option value="" selected disabled>Select City</option>');
    $("#addrBarangay").empty().append('<option value="" selected disabled>Select Barangay</option>');

    if(!province) return;

    $.ajax({
        url: ROUTE_URL,
        type: "POST",
        data: { action: "LoadCity", province: province },
        dataType: "JSON",
        success: function(response) {
            $.each(response.CITY, function(key, value) {
                $("#addrCity").append(`<option value="${value.municipality}">${value.municipality}</option>`);
            });
        }
    });
    UpdateFullAddress();
}

function LoadBarangay(city) {
    $("#addrBarangay").empty().append('<option value="" selected disabled>Select Barangay</option>');

    if(!city) return;

    $.ajax({
        url: ROUTE_URL,
        type: "POST",
        data: { action: "LoadBarangay", city: city },
        dataType: "JSON",
        success: function(response) {
            $.each(response.BARANGAY, function(key, value) {
                $("#addrBarangay").append(`<option value="${value.baranggay}">${value.baranggay}</option>`);
            });
        }
    });
    UpdateFullAddress();
}

// Update the hidden fullAddress input whenever a dropdown changes
$('#addrRegion, #addrProvince, #addrCity, #addrBarangay, #addrStreet').on('change keyup', function() {
    UpdateFullAddress();
});

function UpdateFullAddress() {
    var region = $("#addrRegion").val() || '';
    var province = $("#addrProvince").val() || '';
    var city = $("#addrCity").val() || '';
    var barangay = $("#addrBarangay").val() || '';
    var street = $("#addrStreet").val() || '';

    // Construct address: Street, Barangay, City, Province, Region
    var parts = [];
    if(street) parts.push(street);
    if(barangay) parts.push(barangay);
    if(city) parts.push(city);
    if(province) parts.push(province);
    if(region) parts.push(region);

    $("#fullAddress").val(parts.join(', '));
}

function SetTransactionDate(){
    var options = {
        value: new Date(),
        rtl: false,
        format: 'm/d/Y',
        timepicker: false,
        datepicker: true,
        startDate: false,
        closeOnDateSelect: false,
        closeOnTimeSelect: true,
        closeOnWithoutClick: true,
        closeOnInputClick: true,
        openOnFocus: true,
        mask: '99/99/9999',
        maxDate: new Date(),
    };

    $('#transactionDate').datetimepicker(options);
    $('#transmittalDateFrom').datetimepicker(options);
    $('#transmittalDateTo').datetimepicker(options);

    var date = new Date();
    var formattedDate = ((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear();

    $("#transactionDate").val(formattedDate);
    $("#transmittalDateFrom").val(formattedDate);
    $("#transmittalDateTo").val(formattedDate);
    $('#transactionDate').on('change', function(){
        var val = $(this).val();
        if (val) {
            var d = new Date(val);
            var t = new Date();
            if (d > t) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Future date not allowed',
                });
                var date = t;
                $(this).val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
            }
        }
    });
}

function Initialize(){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#userSINo").val(response.NEXTSI);
            $("#currentSI").text(response.NEXTSI);

            $("#isynBranch").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.ISYNBRANCH,function(key,value){
                $("#isynBranch").append(`
                    <option value="${value["Stock"]}">
                        ${value["Stock"]}
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
            var typesRequired = ["With VAT","Non-VAT"];
            typesRequired.forEach(function(t){
                if ($("#type option").filter(function(){ return $(this).text() === t; }).length === 0) {
                    $("#type").append(`<option value="${t}">${t}</option>`);
                }
            });

            $("#customerType").empty().append(`<option value="" disabled selected>Select</option>`);
            var requiredCustomerTypes = ["OTHER CLIENT","BUSINESS UNIT","EXTERNAL CLIENT","MFI BRANCHES","MFI HO","OTHERS","STAFF"];
            requiredCustomerTypes.forEach(function(ct){
                $("#customerType").append(`<option value="${ct}">${ct}</option>`);
            });
            $.each(response.CUSTOMERTYPE,function(key,value){
                var ct = value["Type"];
                if ($("#customerType option").filter(function(){ return $(this).text() === ct; }).length === 0) {
                    $("#customerType").append(`<option value="${ct}">${ct}</option>`);
                }
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
        }, 
    })
}

function isConsignmentBox() {
    if ($("#isConsignment").is(":checked")) {
        isConsign = "Yes";
        $('#consignmentBranchContainer').show();
    } else {
        isConsign = "No";
        $('#consignmentBranchContainer').hide();
    }
    $("#isynBranch").val("");
    $("#consignmentBranch").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#type").val("");
    $("#category").empty();
    $("#serialNo").prop("checked", false)
    $("#productName").prop("checked", false)
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
            //var requiredCategories = ["Battery","Cable","Cartridge","Connector"];
            requiredCategories.forEach(function(c){
                if ($("#category option").filter(function(){ return $(this).text() === c; }).length === 0) {
                    $("#category").append(`<option value="${c}">${c}</option>`);
                }
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
            Warranty = info["Warranty"];
        }, 
    })
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
}

function toggleCustomerNameInput (customerType){
    $("#staffCheckbox").prop("checked", false);
    $("#branchUsed").prop("checked", false);
    $("mfiUsed").prop("checked", false);
    $("#serialNo").prop("checked", false);

    if (customerType === "OTHER CLIENT") {
        $('#customerNameInputDiv').show();
        $('#customerNameInput').prop('readonly', false);
        $('#customerNameSelectDiv').hide();
        $('#customerNameInput').val("");
        $('#tinNoinput').val("").prop('disabled', false);
        $('#fullAddress').val("").prop('disabled', false);
    } else {
        $('#customerNameInputDiv').hide();
        $('#customerNameInput').prop('readonly', true);
        $('#customerNameSelectDiv').show();
        $('#customerNameInput').val("");
        $('#tinNoinput').val("").prop('disabled', true);
        $('#fullAddress').val("").prop('disabled', true);
        
        $.ajax({
            url: ROUTE_URL,
            type:"POST",
            data:{action:"LoadCustomerName", customerType:customerType},
            dataType:"JSON",
            beforeSend:function(){
                $("#customerNameSelect").empty();
            },
            success:function(response){
                $("#customerNameSelect").empty().append(`
                    <option value="" disabled selected>Select</option>`);
                $.each(response.CUSTOMERNAMELIST,function(key,value){
                    $("#customerNameSelect").append(`
                        <option value="${value["Name"]}">
                            ${value["Name"]}
                        </option>
                    `);
                });
            }, 
        })
    }
    
    if (customerType === "MFI BRANCHES") {
        $('#mfiCheckboxDiv').show();
        $('#staffCheckboxDiv').hide();
    } else if (customerType === "STAFF") {
        $('#mfiCheckboxDiv').hide();
        $('#staffCheckboxDiv').show();
    } else {
        $('#mfiCheckboxDiv').hide();
        $('#staffCheckboxDiv').hide();
    }
}

function LoadCustomerNameInfo(name){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadCustomerNameInfo", customerName:name},
        dataType:"JSON",
        beforeSend:function(){
            $("#tinNoinput").val("");
            $("#fullAddress").val("");
        },
        success:function(response){
            var info = response.CUSTOMERINFO[0];
            $("#tinNoinput").val(info["tin_no"]);
            $("#fullAddress").val(info["FullAddress"]);
            Area = info["Area"];
        }, 
    })
}

function ComputeMerchandise(){
   var srpMS = parseFloat($("#srpMerchSales").val().replace(/,/g, ''));
   var dealerPricePS = parseFloat($("#psDealerPrice").val().replace(/,/g, ''));
   var srpPS = $('#psSRP').val().replace(/,/g, '');

   var qtyPS = $("#psQuantity").val();
   var qtyMS = $("#quantityMerchSales").val();

   if (qtyMS == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Enter valid number.',
        })
        ClearMS();
        ClearMSDiscount();
        return;
    } else if (qtyMS == 0){
        Swal.fire({
           icon: 'warning',
           title: 'Zero is not allowed',
        })
        ClearMS();
        ClearMSDiscount();
        return;
    } else if (qtyMS < 0){
        Swal.fire({
           icon: 'warning',
           title: 'Negative number is not allowed.',
        })
        ClearMS();
        ClearMSDiscount();
        return;
    } else if (qtyPS == ""){
        Swal.fire({
           icon: 'warning',
           title: 'Select a Product First.',
        })
        ClearMS();
        ClearMSDiscount();
        return;
    } else if (Number(qtyPS) < Number(qtyMS)){
        Swal.fire({
           icon: 'warning',
           title: 'Entered quantity is more than the available quantity of the product.',
        })
        ClearMS();
        ClearMSDiscount();
        return;
    }
    
    var totalDP = dealerPricePS * qtyMS;
    var totalCost = srpMS * qtyMS;

    mark = srpPS - dealerPricePS;
    Tmark = totalCost - totalDP;

    // console.log(totalDP + "|" + totalCost);
    // console.log(mark + "|" + Tmark);

    $("#vatMerchSales").val(formatAmtVal(totalDP));
    $("#totalCostMerchSales").val(formatAmtVal(totalCost));

    if ($('#addDiscount').is(':checked')){
        computeWithDiscount();
    }
    
}

function AddDiscount(){
    var totalCost = $("#totalCostMerchSales").val();
    if (totalCost == "" || totalCost == 0 || totalCost == 0.00){
        Swal.fire({
            icon: 'warning',
            title: 'Please enter a product.',
        })
        $("#addDiscount").prop("checked", false);
    } else {
        $("#addDiscount").prop("checked", false);
        Swal.fire({
            title: 'Enter password.',
            html: '<input id="enterPassword" class="swal2-input">',
            confirmButtonText: 'Set',
            showLoaderOnConfirm: false,
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) {
                var password = $("#enterPassword").val();
                if(password == ""){
                    AddDiscount();
                }

                
            }
        })

        // if ($('#addDiscount').is(':checked')){
        //     $('#discInterestMerchSales').prop('disabled', false);
        //     $('#discInterestMerchSales').val("");
        //     ClearMSDiscount();
        // } else {
        //     $('#discInterestMerchSales').prop('disabled', true);
        //     $('#discInterestMerchSales').val("");
        //     ClearMSDiscount();
        // }
    }
}

function ClearMS(){
    $("#quantityMerchSales").val("");
    $("#vatMerchSales").val("");
    $("#totalCostMerchSales").val("");
}

function ClearMSDiscount(){
    $('#discountAmountMerchSales').val("");
    $('#newSRPMerchSales').val("");
    $('#totalDiscountMerchSales').val("");
}

function computeWithDiscount(){
    var vatMS = $("#vatMerchSales").val().replace(/,/g, '');
    var srpMS = $("#srpMerchSales").val().replace(/,/g, '');
    var qtyMS = $("#quantityMerchSales").val();
    var totalCost = $("#totalCostMerchSales").val().replace(/,/g, '');

    var discInterest = $("#discInterestMerchSales").val();

    if (discInterest == 0){
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Discount Interest Percentage.',
        })
        $("#discInterestMerchSales").val("")
        ClearMSDiscount();
        return;
    }

    if (discInterest > 100 || discInterest < 0){
        Swal.fire({
            icon: 'warning',
            title: 'Discount interest should only be within the range of 1 - 100.',
        })
        $("#discInterestMerchSales").val(0)
        ClearMSDiscount();
        return;
    }

    // Discounted Amount
    var discAmount = (srpMS / 1.12) * (discInterest / 100);
        discAmount = discAmount * qtyMS;
        $("#discountAmountMerchSales").val(formatAmtVal(discAmount))
        
    //New SRP
    var newSRP = srpMS - (srpMS / 1.12) * (discInterest / 100);
        $("#newSRPMerchSales").val(formatAmtVal(newSRP))

    //New Total Cost
    var newTotalCost = totalCost - discAmount;
        $("#totalDiscountMerchSales").val(formatAmtVal(newTotalCost));

    Tmark = newTotalCost - vatMS;
}

// =======================================================================================

function AddToList () {
    if (isAdding) return;
    let transactionDate = $('#transactionDate').val();
    let isConsignment = "-";
    let consignBranch = "-";
    if ($('#isConsignment').is(':checked')){
        isConsignment = $('#isConsignment').val();
        consignBranch = $('#consignmentBranch').val();
    }
    let isynBranch = $('#isynBranch').val();
    let type = $('#type').val();
    let categ = $('#category').val();
    let serialProduct = $('#SerialProduct').val();
    let SINo = $('#SINo').val();

    let supplierSI = $('#psSupplierSI').val();
    let serialNo = $('#psSerialNo').val();
    let productName = $('#psProduct').val();
    let supplierName = $('#psSupplier').val();
    let psSRP = $('#psSRP').val();
    let psQty = $('#psQuantity').val();
    let psDealerPrice = $('#psDealerPrice').val();
    let psTotalPrice = $('#psTotalPrice').val();
    
    let customerName = "";
    let customerType = $('#customerType').val();
    if (customerType == "OTHER CLIENT"){
        customerName = $('#customerNameInput').val();
    } else {
        customerName = $('#customerNameSelect').val();
    }

    let staffLoan = "-";
    if ($('#staffCheckbox').is(':checked')){
        staffLoan = $('#staffCheckbox').val();
    }
    let branchUsed = "-";
    if ($('#branchUsed').is(':checked')){
        branchUsed = $('#branchUsed').val();
    }
    let mfiUsed = "-";
    if ($('#mfiUsed').is(':checked')){
        mfiUsed = $('#mfiUsed').val();
    }

    let tin = $('#tinNoinput').val();
    let address = $('#fullAddress').val();
    let status = $('#status').val();

    let srpMS = $('#srpMerchSales').val();
    let qtyMS = $('#quantityMerchSales').val();
    let vatMS = $('#vatMerchSales').val();
    let totalCostMS = $('#totalCostMerchSales').val();

    let addDiscount = "No";
    let discInterest = "0";
    let discAmtMS = "0";
    let newSRPMS = "0";
    let totalDiscountMS = "0";
    if ($('#addDiscount').is(':checked')){
        addDiscount = $('#addDiscount').val();
        discInterest = $('#discInterestMerchSales').val();
        discAmtMS = $('#discountAmountMerchSales').val();
        newSRPMS = $('#newSRPMerchSales').val();
        totalDiscountMS = $('#totalDiscountMerchSales').val();
    }

    if (itemsTbl.rows().count() === 9){
        Swal.fire({
            icon: 'warning',
            text: 'You have reached the max number of items (9). Create a new SI for more items.',
        });
        return;
    }

    if (isynBranch == null || type == null || categ == null || serialProduct == null || SINo == null) {
        Swal.fire({
            icon: 'warning',
            text: 'Please complete Product Information.',
        });
        return;
    }

    if (customerType == null){
        Swal.fire({
            icon: 'warning',
            text: 'Please select Customer Type.',
        });
        return;
    } else if (customerType == "OTHER CLIENT") {
        if (customerName == "") {
            Swal.fire({
                icon: 'warning',
                text: 'Please enter Customer Name.',
            });
            return;
        }
    } else {
        if(customerName == null) {
            Swal.fire({
                icon: 'warning',
                text: 'Please select Customer Name.',
            });
            return;
        }

        if (customerType == "MFI BRANCH") {
            if ($('input[name="mfiCheckbox"]:checked').length === 0){
                Swal.fire({
                    icon: 'warning',
                    text: 'Please select either BRANCH USED or MFI USED.',
                });
                return;
            }
        }
    }

    if (tin == "" || address == "") {
        Swal.fire({
            icon: 'warning',
            text: 'Please complete Purchase By Details.',
        });
        return;
    }
    
    if (status == null) {
        if (categ == "FREEBIES") {
    
        } else {
            Swal.fire({
                icon: 'warning',
                text: 'Please select a status either PAID or UNPAID.',
            });
            return;
        }
    }

    if (srpMS == "" || qtyMS == "" || vatMS == "" || totalCostMS == "") {
        Swal.fire({
            icon: 'warning',
            text: 'Please complete the merchandise sales details.',
        });
        return;
    }

    isAdding = true;
    $('#addToList').prop('disabled', true);
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"AddToItems", isConsignment:isConsignment,isynBranch:isynBranch,consignBranch:consignBranch,type:type,categ:categ,serialProduct:serialProduct,SINo:SINo,supplierSI:supplierSI,serialNo:serialNo,productName:productName,supplierName:supplierName,psSRP:psSRP,psQty:psQty,psDealerPrice:psDealerPrice,psTotalPrice:psTotalPrice,customerType:customerType,customerName:customerName,staffLoan:staffLoan,branchUsed:branchUsed,mfiUsed:mfiUsed,tin:tin,address:address,status:status,srpMS:srpMS,qtyMS:qtyMS,vatMS:vatMS,totalCostMS:totalCostMS,addDiscount:addDiscount,discInterest:discInterest,discAmtMS:discAmtMS,newSRPMS:newSRPMS,totalDiscountMS:totalDiscountMS,Area:Area,mark:mark,Tmark:Tmark,Warranty:Warranty,transactionDate:transactionDate},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            if (response.STATUS == "SUCCESS") {
                // Swal.fire({
                //     icon: 'success',
                //     title: response.MESSAGE,
                // })
                LoadTransaction();
                ClearAllFields();

                $('#customerType').prop('disabled', true);
                $('#customerNameInput').prop('disabled', true);
                $('#customerNameSelect').prop('disabled', true);

                $("#staffCheckbox").prop("disabled", true);
                $("#branchUsed").prop("disabled", true);
                $("mfiUsed").prop("disabled", true);

                $('#tinNoinput').prop('disabled', true);
                $('#fullAddress').prop('disabled', true);
                $('#status').prop('disabled', true);
                $('#saveBtn').prop('disabled', false);
            } else  if (response.STATUS == "WARNING"){
                Swal.fire({
                    icon: 'warning',
                    title: response.MESSAGE,
                    showCancelButton: true,
                    // allowOutsideClick: false,
                }).then(function(result) {
                    if (result.isConfirmed) {
                        if ( $.fn.DataTable.isDataTable( '#branchConsignTbl' ) ) {
                            $('#branchConsignTbl').DataTable().clear();
                            $('#branchConsignTbl').DataTable().destroy(); 
                        }
                        ConsignBranchList = response.ConsignList;
                        totalAddedConsignItem = 0;

                        $("#branchConsign").empty().append(`<option value="" disabled selected>Select</option>`);
                        $.each(response.ConsignList,function(key,value){
                            $("#branchConsign").append(`
                                <option value="${value["Stock"]}">
                                    ${value["Stock"]}
                                </option>
                            `);
                        });
                        
                        $("#availableQty").val("");
                        $("#enteredQty").val("");
                        $('#needQty').val(response.needQty);

                        $("#PanelConsignMDL").modal("show");

                        branchConsignTbl = $('#branchConsignTbl').DataTable({
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
                    } else {
                        LoadTransaction();
                        ClearAllFields();
                        ClearPurchasedBy();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: response.MESSAGE,
                })
            }
            isAdding = false;
            $('#addToList').prop('disabled', false);
        }, 
        error:function(xhr,status,error){
            isAdding = false;
            $('#addToList').prop('disabled', false);
            Swal.fire({icon:'error',title:'Add failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        }
    })

};

function LoadBranchConsign(consignBranch){
    $.each(ConsignBranchList,function(key,value){
        if (value["Stock"] == consignBranch) {
            $('#availableQty').val(value["Quantity"]);
        } else {
            $('#availableQty').val("");
        }
    });
}

function AddBranchConsign(){
    var branchConsign = $("#branchConsign").val();
    var availQty = $("#availableQty").val();
    var enteredQty = $("#enteredQty").val();
    var needQty = $("#needQty").val();

    if (branchConsign == "" || branchConsign == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Select a branch.',
        })
        return;
    }
    
    if (enteredQty == "" || enteredQty == 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid entered quantity.',
        })
        return;
    }
    
    if (enteredQty == "" || enteredQty == 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid entered quantity.',
        })
        return;
    }

    if (enteredQty > needQty) {
        Swal.fire({
            icon: 'warning',
            title: 'Entered quantity is higher than the needed quantity.',
        })
        return;
    }
    
    if (enteredQty < needQty) {
        Swal.fire({
            icon: 'warning',
            title: 'Entered quantity is lower than the needed quantity.',
        })
        return;
    }

    if (totalAddedConsignItem == needQty) {
        Swal.fire({
            icon: 'info',
            title: 'You have already added the required quantity.',
        })
        $("#enteredQty").val("");
        return;
    } else {
        totalAddedConsignItem++;
    
        $("#branchConsign").val();
        $("#availableQty").val();
        $("#enteredQty").val("");
    
        branchConsignTbl.row.add([
            branchConsign,
            enteredQty,
        ]).draw(false);
    }
}

$('#branchConsignTbl tbody').on('click', 'tr',function(e){
    if(branchConsignTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#deleteBranchConsign").attr("disabled",true);
            SelectedFromBranchConsign = "";
        } else {
            branchConsignTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $("#deleteBranchConsign").attr("disabled",false);
            branchConsignTblValue = $('#branchConsignTbl').DataTable().row(this).data();
            SelectedFromBranchConsign = this;
        }
    }
});

function DeleteFromConsignQty(){
    if(branchConsignTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'No Quantity to delete.',
        })
        return;
    }

    if (SelectedFromBranchConsign != "") {
        totalAddedConsignItem--;
        branchConsignTbl.row(SelectedFromBranchConsign).remove().draw(false);
        SelectedFromBranchConsign = "";
        $("#deleteBranchConsign").attr("disabled",true);
    } else {
        SelectedFromBranchConsign = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select a Quantity to delete.',
        })
    }
}

function ConfirmBranchConsignQty(){
    let supplierSI = $('#psSupplierSI').val();
    let serialNo = $('#psSerialNo').val();
    let productName = $('#psProduct').val();
    let categ = $('#category').val();

    let needQty = $("#needQty").val();

    if(branchConsignTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'No Quantity to confirm.',
        })
        return;
    }

    if (totalAddedConsignItem > needQty) {
        Swal.fire({
            icon: 'warning',
            title: 'Total added quantity is higher than the needed quantity. Please delete some.',
        })
        return;
    }
    
    if (totalAddedConsignItem < needQty) {
        Swal.fire({
            icon: 'warning',
            title: 'Total added quantity is lower than the needed quantity. Please add more.',
        })
        return;
    }

    if (totalAddedConsignItem == needQty){

        let Data = branchConsignTbl.rows().data().toArray();
        let formdata = new FormData();
        formdata.append("action","UseQtyFromBranchConsign");
        formdata.append("DATA",JSON.stringify(Data));
        formdata.append("supplierSI",supplierSI);
        formdata.append("serialNo",serialNo);
        formdata.append("productName",productName);
        formdata.append("categ",categ);

        $.ajax({
            url: ROUTE_URL,
            type: "POST",
            data:formdata,
            processData:false,
            cache:false,
            contentType:false,
            dataType:"JSON",
            beforeSend:function(){
                $("#PanelConsignMDL").modal("hide");
            },
            success:function(response){
                if (response.STATUS == "SUCCESS") {
                    AddToList ();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Something went wrong.',
                    })
                }
            }, 
        })
    }

}

function LoadTransaction(){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadTransaction",},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#itemsTbl' ) ) {
                $('#itemsTbl').DataTable().clear();
                $('#itemsTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            $("#itemsList").empty();
            $.each(response.TRANSACTIONLIST,function(key,value){
                let unit = "";
                let article = "";
                if (value["Category"] == "CABLE"){
                    if (value["Quantity"] > 1){
                        unit = "meters";
                    } else {
                        unit = "meter";
                    }
                } else {
                    if (value["Quantity"] > 1){
                        unit = "pcs";
                    } else {
                        unit = "pc";
                    }
                }

                if (value["Serialno"].trim() == "" || value["Serialno"].trim() == "-"){
                    article = value["Product"];
                } else {
                    article = value["Product"] + "/" + value["Serialno"];
                }

                $("#itemsList").append(`
                    <tr>
                        <td>${value["SupplierSI"]||''}</td>
                        <td>${value["Serialno"]||''}</td>
                        <td>${value["Product"]||''}</td>
                        <td>${value["Soldto"]||''}</td>
                        <td>${value["Quantity"]||''}</td>
                        <td>${unit}</td>
                        <td>${article}</td>
                        <td>${value["SRP"]||0}</td>
                        <td>${value["TotalSRP"]||0}</td>
                        <td>${value["VatSales"]||0}</td>
                        <td>${value["VAT"]||0}</td>
                        <td>${value["AmountDue"]||0}</td>
                        <td>${value["DiscProduct"]||''}</td>
                        <td>${value["DiscAmount"]||0}</td>
                        <td>${value["DiscNewSRP"]||0}</td>
                        <td>${value["DiscNewTotalSRP"]||0}</td>
                        <td>${value["Category"]||''}</td>
                        <td>${value["Supplier"]||''}</td>
                        <td>${value["Warranty"]||''}</td>
                        <td>${value["TIN"]||''}</td>
                        <td>${value["Address"]||''}</td>
                    </tr>
                `);
            });

            itemsTbl = $('#itemsTbl').DataTable({
                destroy:true,
                searching:false,
                ordering:false,
                info:false,
                paging:false,
                lengthChange:false,
                scrollY: '230px',
                scrollX: true,  
                scrollCollapse: true,
                responsive:false,
                columnDefs: [
                    { targets: [ 0,1,2 ], visible:false, searchable:false }
                ],
            });

            if(itemsTbl.rows().count() !== 0){
                $('#saveBtn').prop("disabled", false);
            } else {
                $('#saveBtn').prop("disabled", true);
                if ( $.fn.DataTable.isDataTable( '#itemsTbl' ) ) {
                    $('#itemsTbl').DataTable().clear();
                    $('#itemsTbl').DataTable().destroy(); 
                }
                $("#itemsList").empty();
            }
        }, 
    })
}

function LoadInventoryOut(){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadInventoryOut"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#itemsTbl' ) ) {
                $('#itemsTbl').DataTable().clear();
                $('#itemsTbl').DataTable().destroy(); 
            }
            $("#itemsList").empty();
        },
        success:function(response){
            $("#itemsList").empty();
            $.each(response.INVENTORYOUT,function(key,value){
                let unit = "";
                let article = "";
                if ((value["Category"]||"").toUpperCase() == "CABLE"){
                    unit = (Number(value["Quantity"]) > 1) ? "meters" : "meter";
                } else {
                    unit = (Number(value["Quantity"]) > 1) ? "pcs" : "pc";
                }
                if ((value["Serialno"]||"").trim() == "" || (value["Serialno"]||"").trim() == "-"){
                    article = value["Product"];
                } else {
                    article = value["Product"] + "/" + value["Serialno"];
                }
                $("#itemsList").append(`
                    <tr>
                        <td>${value["SupplierSI"]||''}</td>
                        <td>${value["Serialno"]||''}</td>
                        <td>${value["Product"]||''}</td>
                        <td>${value["Soldto"]||''}</td>
                        <td>${value["Quantity"]||''}</td>
                        <td>${unit}</td>
                        <td>${article}</td>
                        <td>${formatAmtVal(value["SRP"]||0)}</td>
                        <td>${formatAmtVal(value["TotalSRP"]||0)}</td>
                        <td>${formatAmtVal(value["VatSales"]||0)}</td>
                        <td>${formatAmtVal(value["VAT"]||0)}</td>
                        <td>${formatAmtVal(value["AmountDue"]||0)}</td>
                        <td>${value["DiscProduct"]||''}</td>
                        <td>${formatAmtVal(value["DiscAmount"]||0)}</td>
                        <td>${formatAmtVal(value["DiscNewSRP"]||0)}</td>
                        <td>${formatAmtVal(value["DiscNewTotalSRP"]||0)}</td>
                        <td>${value["Category"]||''}</td>
                        <td>${value["Supplier"]||''}</td>
                        <td>${value["Warranty"]||''}</td>
                        <td>${value["TIN"]||''}</td>
                        <td>${value["Address"]||''}</td>
                    </tr>
                `);
            });
            itemsTbl = $('#itemsTbl').DataTable({
                destroy:true,
                searching:false,
                ordering:false,
                info:false,
                paging:false,
                lengthChange:false,
                scrollY: '230px',
                scrollX: true,  
                scrollCollapse: true,
                responsive:false,
                columnDefs: [
                    { targets: [ 0,1,2 ], visible:false, searchable:false }
                ],
            });
            $('#saveBtn').prop("disabled", itemsTbl.rows().count() === 0);
        },
        error:function(xhr, status, error){
            Swal.fire({icon:'error',title:'Load failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        }
    })
}

function PrintRecentOut(){
    if (!$.fn.DataTable.isDataTable('#itemsTbl')) {
        Swal.fire({icon:'warning',title:'No table loaded'});
        return;
    }
    var dt = $('#itemsTbl').DataTable();
    if (dt.rows().count() === 0) {
        Swal.fire({icon:'warning',title:'No outgoing records to print'});
        return;
    }

    // Print ONLY the selected row (not the whole table)
    if (!SelectedFromItems) {
        Swal.fire({icon:'warning',title:'Please select an item row to print'});
        return;
    }
    var rowData = dt.row(SelectedFromItems).data() || itemsTblValue || null;
    if (!rowData || !Array.isArray(rowData) || rowData.length === 0) {
        Swal.fire({icon:'error',title:'Selected row not found. Please reselect and try again.'});
        return;
    }
    var Data = [rowData];
    var formdata = new FormData();
    formdata.append("action","PrintRecentOut");
    formdata.append("DATA",JSON.stringify(Data));
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:formdata,
        processData:false,
        contentType:false,
        dataType:"JSON",
        success:function(response){
            if (response && response.STATUS === 'success') {
                window.open(ROUTE_URL + "?type=PrintRecentOut");
            } else {
                Swal.fire({icon:'error',title:'Print failed',text:(response && response.MESSAGE) ? response.MESSAGE : 'Unknown error'});
            }
        },
        error:function(xhr, status, error){
            Swal.fire({icon:'error',title:'Print failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        }
    });
}
function ClearAllFields(){
    isConsign = "No";
    
    $("#isConsignment").prop("checked", false)
    $('#consignmentBranchContainer').hide();
    $("#isynBranch").val("");
    $("#consignmentBranch").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#type").val("");
    $("#category").empty();
    $("#serialNo").prop("checked", false)
    $("#productName").prop("checked", false)
    $("#SerialProduct").empty();
    $("#SINo").empty();
    ClearProductSummary();

    $('#srpMerchSales').val("");
    $('#quantityMerchSales').val("");
    $('#vatMerchSales').val("");
    $('#totalCostMerchSales').val("");

    $("#addDiscount").prop("checked", false);
    $('#discInterestMerchSales').prop('disabled', true);
    $('#discInterestMerchSales').val("");
    ClearMSDiscount();
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

    // Clear Address Fields
    $("#addrRegion").val("");
    $("#addrProvince").empty().append('<option value="" selected disabled>Select Province</option>');
    $("#addrCity").empty().append('<option value="" selected disabled>Select City</option>');
    $("#addrBarangay").empty().append('<option value="" selected disabled>Select Barangay</option>');
    $("#addrStreet").val("");
}

$('#itemsTbl tbody').on('click', 'tr',function(e){
    if(itemsTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#DeleteFromListBtn").attr("disabled",true);
            itemTblValue = "";
            SelectedFromItems = "";
        } else {
            itemsTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $("#DeleteFromListBtn").attr("disabled",false);
            itemsTblValue = $('#itemsTbl').DataTable().row(this).data();
            SelectedFromItems = this;
        }
    }
});

function DeleteFromItems(){
    if(itemsTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'No items to delete.',
        })
        return;
    }
    if  (SelectedFromItems != "") {
        var SINo = itemsTblValue[0];
        var SerialNo = itemsTblValue[1];
        var Product = itemsTblValue[2];
        Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: 'This action will delete the selected transaction. Are you sure you want to proceed?',
            showCancelButton: true,
            allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    data: {action:"DeleteFromItems", SINo:SINo, SerialNo:SerialNo, Product:Product},
                    dataType: 'JSON',
                    beforeSend: function() {
                        console.log('Processing Request...')
                    },
                    success: function(response) {
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
                    if (SelectedFromItems){
                        $('#itemsTbl').DataTable().row(SelectedFromItems).remove().draw(false);
                        SelectedFromItems = "";
                        $("#DeleteFromListBtn").attr("disabled",true);
                    }
                    LoadTransaction();
                    SelectedFromItems = "";
                    $("#DeleteFromListBtn").attr("disabled",true);
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
        SelectedFromDataInv = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select from the Items table to delete',
        })
    }
}

function parseDate(dateStr) {
    let [month, day, year] = dateStr.split("/").map(Number);
    return new Date(year, month - 1, day); // Month is 0-based in JS
}

function SearchTransmittal(){
    var customerType = $("#customerType").val();
    var customerName = "";
    var tin = $("#tinNoinput").val();
    var address = $("#fullAddress").val();
    var status = $("#status").val();
    
    if (customerType === "OTHER CLIENT") {
        var customerName = $("#customerNameInput").val();
    } else {
        var customerName = $("#customerNameSelect").val();
    }

    if (customerType == null){
        Swal.fire({
            icon: 'warning',
            title: 'Select Customer Type.',
        })
        return;
    }

    if (customerType == "OTHER CLIENT") {
        if (customerName.trim() == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Enter customer name.',
            })
            $("#customerNameInput").val("")
            return;
        }
    }

    if (customerType != "OTHER CLIENT") {
        if (customerName == null) {
            Swal.fire({
                icon: 'warning',
                title: 'Select Customer Name.',
            })
            return;
        }
    }

    if (tin == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Enter TIN.',
        })
        return;
    }

    if (address.trim() == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Enter address.',
        })
        $("#fullAddress").val("")
        return;
    }

    if (status == null){
        Swal.fire({
            icon: 'warning',
            title: 'Select the status as either \'paid\' or \'unpaid\'.',
        })
        return;
    }
    
    if (customerType == "MFI BRANCHES") {
        if (!$('#mfiUsed').is(':checked') && !$('#branchUsed').is(':checked')){
            Swal.fire({
                icon: 'warning',
                title: 'Please select either Branch Used o MFI Client.',
            })
            return;
        }
    }
    
    $("#listList").empty();

    $("#productList").empty();

    $("#loadToList").prop("disabled", true);

    $("#SearchTransmittalMDL").modal("show");
}

function TransmittalSearch(){
    var dateFrom = $("#transmittalDateFrom").val();
    var dateTo = $("#transmittalDateTo").val();

    if (dateFrom == "" || dateTo == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Please select date from and date to.',
        })
        return;
    }

    if (parseDate(dateTo) < parseDate(dateFrom)) {
        Swal.fire({
            icon: 'warning',
            title: 'Date To is lower than Date From.',
        })
        return;
    }

    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"TransmittalSearch",dateFrom:dateFrom,dateTo:dateTo},
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
                $("#listList").append(`
                    <tr>
                        <td>${value["TransmittalNO"]}</td>
                        <td>${value["NameTO"]}</td>
                        <td>${value["DatePrepared"]}</td>
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

            productTbl = $('#productTbl').DataTable({
                searching: false,
                ordering: false,
                info: false,
                paging: false,
                lengthChange: false,
                scrollY: '230px',
                scrollX: true,
                scrollCollapse: true,
                responsive: false,
                columnDefs: [
                    { targets: [ 1,2,3,4 ], className: 'dt-center' },
                    { targets: [ 5,6,7,8,9,10,11,12,13 ], visible:false, searchable:false },
                ],
            });
        }, 
    })
}

$('#listTbl tbody').on('click', 'tr',function(e){
    if(listTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#loadToList").prop("disabled", true);
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
            $("#loadToList").prop("disabled", false);
            
            $.ajax({
                url: ROUTE_URL,
                type:"POST",
                data:{action:"fetchProducts",transNo:transNo,clientName:clientName,date:date},
                dataType:"JSON",
                beforeSend:function(){
                    if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
                        $('#productTbl').DataTable().clear();
                        $('#productTbl').DataTable().destroy(); 
                    }
                },
                success:function(response){
                    $("#productList").empty();
                    $.each(response.PRODUCTLIST,function(key,value){
                        $("#productList").append(`
                            <tr class="${value['Avail'] !== 'YES' ? 'row-alert' : ''}">
                                <td>${value["Product"]}</td>
                                <td>${value["TQty"]}</td>
                                <td>${value["CIQty"]}</td>
                                <td>${value["Avail"]}</td>
                                <td>${value["Consign"]}</td>
                                <td>${value["Amount"]}</td>
                                <td>${value["SIno"]}</td>
                                <td>${value["Serial"]}</td>
                                <td>${value["Product"]}</td>
                                <td>${value["Supplier"]}</td>
                                <td>${value["Category"]}</td>
                                <td>${value["Type"]}</td>
                                <td>${value["Stock"]}</td>
                                <td>${value["Branch"]}</td>
                            </tr>
                        `);
                    });
        
                    productTbl = $('#productTbl').DataTable({
                        searching: false,
                        ordering: false,
                        info: false,
                        paging: false,
                        lengthChange: false,
                        scrollY: '230px',
                        scrollX: true,
                        scrollCollapse: true,
                        responsive: false,
                        columnDefs: [
                            { targets: [ 1,2,3,4 ], className: 'dt-center' },
                            // { targets: [ 5,6,7,8,9,10,11,12,13 ], visible:false, searchable:false },
                        ],
                    });
                }, 
            })
        }
    }
});

function LoadtoList(){
    let hasNonAvail = false;
    let hasNo = false;
    let hasYes = false;

    if(productTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'Nothing to load.',
        });
    } else {
        productTbl.rows().every(function(rowIdx, tableLoop, rowLoop) {
            let data = this.data();
            let availability = data[3]; // Assuming Avail is 4th column (index 3)
    
            if (availability !== "YES" && availability !== "NO") {
                // $(this.node()).addClass('selected');
                // $(this.node()).remove();
                hasNonAvail = true;
            } else if (availability === "NO"){
                hasNo = true;  
            } else {
                hasYes = true;  
            }
        });
    
        if (hasYes || hasNo){
            if (hasNonAvail){
                Swal.fire({
                    icon: 'warning',
                    title: 'Some product quantities are not available. Please remove them to proceed.',
                    allowOutsideClick: false,
                });
            } else {
                let transactionDate = $('#transactionDate').val();
        
                let customerName = "";
                let customerType = $('#customerType').val();
                if (customerType == "OTHER CLIENT"){
                    customerName = $('#customerNameInput').val();
                } else {
                    customerName = $('#customerNameSelect').val();
                }
        
                let staffLoan = "-";
                if ($('#staffCheckbox').is(':checked')){
                    staffLoan = $('#staffCheckbox').val();
                }
                let branchUsed = "-";
                if ($('#branchUsed').is(':checked')){
                    branchUsed = $('#branchUsed').val();
                }
                let mfiUsed = "-";
                if ($('#mfiUsed').is(':checked')){
                    mfiUsed = $('#mfiUsed').val();
                }
        
                let tin = $('#tinNoinput').val();
                let address = $('#fullAddress').val();
                let status = $('#status').val();
                
                let Data = productTbl.rows().data().toArray();
                let formdata = new FormData();
                formdata.append("action","LoadtoList");
                formdata.append("DATA",JSON.stringify(Data));
                formdata.append("transactionDate",transactionDate);
                formdata.append("customerName",customerName);
                formdata.append("customerType",customerType);
                formdata.append("staffLoan",staffLoan);
                formdata.append("branchUsed",branchUsed);
                formdata.append("mfiUsed",mfiUsed);
                formdata.append("tin",tin);
                formdata.append("address",address);
                formdata.append("status",status);
                formdata.append("Area",Area);

                const statTitle = hasNo
                    ? "Some product has less available quantity, Do you wish to proceed the transaction on products with available quantity?"
                    : "Proceed transaction of products.";
        
                Swal.fire({
                    icon: 'warning',
                    title: statTitle,
                    showCancelButton: true,
                    // allowOutsideClick: false,
                    preConfirm: function() {
                        return $.ajax({
                            url: ROUTE_URL,
                            type: "POST",
                            data: formdata,
                            processData: false,
                            cache: false,
                            contentType: false,
                            dataType: "JSON",
                            timeout: 30000,
                            beforeSend: function() {
                                console.log('Processing Request...');
                            },
                            success: function(response) {
                                if (response.STATUS != "success") {
                                    Swal.showValidationMessage(response.MESSAGE);
                                }
                            },
                            error: function(xhr, status, error) {
                                var msg = (xhr && xhr.responseText) ? xhr.responseText : (error || status || 'Unknown error');
                                Swal.showValidationMessage('Load failed: ' + msg);
                            }
                        });
                    }
                }).then(function(result) {
                    if (result && result.isConfirmed) {
                        var resp = result.value || null;
                        if (resp && resp.STATUS === 'success') {
                            LoadTransaction();
                            $("#SearchTransmittalMDL").modal("hide");
                        } else if (resp && resp.STATUS !== 'success') {
                            Swal.fire({icon:'error',title:'Load to list failed',text: resp.MESSAGE || 'Unknown error'});
                        }
                    }
                });
            }
    
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'No quantity available for all products.',
                allowOutsideClick: false
            });
        }

    }

}

$('#productTbl tbody').on('click', 'tr',function(e){
    if(productTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#deleteFromTProdList").prop("disabled", true);
        } else {
            productTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            SelectedFromTransProd = this;

            $("#deleteFromTProdList").prop("disabled", false);
        }
    }
});

function DeleteFromTransProdList (){
    if(productTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'No products to delete.',
        })
        return;
    }

    if  (SelectedFromTransProd != "") {
        productTbl.row(SelectedFromTransProd).remove().draw(false);
        SelectedFromTransProd = "";
        $("#deleteFromTProdList").attr("disabled",true);
    } else {
        SelectedFromTransProd = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select from the products to delete',
        })
    }
}

function Save(){
    if(itemsTbl.rows().count() !== 0){
        // Submit/print ONE invoice per client: require a selected row, then submit only that client's rows
        if (!SelectedFromItems) {
            Swal.fire({icon:'warning',title:'Please select a row (client) to submit/print'});
            return;
        }
        let selectedRow = itemsTbl.row(SelectedFromItems).data() || itemsTblValue || null;
        if (!selectedRow || !Array.isArray(selectedRow) || selectedRow.length === 0) {
            Swal.fire({icon:'error',title:'Selected row not found. Please reselect and try again.'});
            return;
        }
        let selectedClient = String(selectedRow[3] || '').trim();
        if (!selectedClient) {
            Swal.fire({icon:'warning',title:'Selected row has no client name (Sold To).'});
            return;
        }
        const norm = (v) => String(v || '').trim().toUpperCase();
        let Data = itemsTbl.rows().data().toArray().filter(r => norm(r[3]) === norm(selectedClient));
        if (Data.length === 0) {
            Swal.fire({icon:'error',title:'No rows found for selected client.'});
            return;
        }
        let formdata = new FormData();
        formdata.append("action","SubmitInvOut");
        formdata.append("DATA",JSON.stringify(Data));
        formdata.append("soldto", selectedClient);

        $('#saveBtn').prop('disabled', true);
        Swal.fire({
            icon: 'info',
            title: 'Proceed to submit entries for ' + selectedClient + '?',
            showCancelButton: true,
            allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    data:formdata,
                    processData:false,
                    cache:false,
                    contentType:false,
                    dataType:"JSON",
                    timeout: 30000,
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
                    error: function(xhr, status, error) {
                        var msg = (xhr && xhr.responseText) ? xhr.responseText : (error || status || 'Unknown error');
                        Swal.showValidationMessage('Submit failed: ' + msg);
                    },
                });
            },
        }).then(function(result) {
            $('#saveBtn').prop('disabled', false);
            if (result && result.isConfirmed) {
                var resp = result.value || null;
                if (resp && resp.STATUS === 'success') {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: resp.MESSAGE,
                    });
                    window.open(ROUTE_URL + "?type=PrintSalesInvoice");
                    LoadTransaction();
                    ClearAllFields();
                    ClearPurchasedBy();
                    Initialize();
                } else if (resp && resp.STATUS !== 'success') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: resp.MESSAGE || 'Unknown error',
                    });
                }                
            }
        });

    } else {
        Swal.fire({
            icon: 'error',
            title: 'No Entries available.',
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
    