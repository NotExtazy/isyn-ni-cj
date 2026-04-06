var inventoryTbl, inventoryTblValue = "", SelectedFrominventoryTbl = "";
var productTbl, productTblValue = "", SelectedFromproductTbl = "";

$("#category").select2({
    width: '100%',
});

Initialize();

function Initialize(){
    $.ajax({
        url:"../../routes/inventorymanagement/changeproductsrpdp.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#inventoryTbl' ) ) {
                $('#inventoryTbl').DataTable().clear();
                $('#inventoryTbl').DataTable().destroy(); 
            }   
            if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
                $('#productTbl').DataTable().clear();
                $('#productTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            $("#isynBranch").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.ISYNBRANCH,function(key,value){
                $("#isynBranch").append(`
                    <option value="${value["Stock"]}">
                        ${value["Stock"]}
                    </option>
                `);
            });

            $("#updateType").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.UPDATETYPE,function(key,value){
                $("#updateType").append(`
                    <option value="${value["module"]}">
                        ${value["module"]}
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

            inventoryTbl = $('#inventoryTbl').DataTable({
                searching:true,
                ordering:true,
                info:true,
                paging:true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                lengthChange:true,
                autoWidth: false,
                responsive:false,
                columnDefs: [
                    { targets: [ 6,7,8,9 ], visible:false, searchable:false }
                ],
                language: {
                    paginate: {
                        next: '<i class="fa-solid fa-chevron-right"></i>',
                        previous: '<i class="fa-solid fa-chevron-left"></i>'
                    }
                }
            });

            productTbl = $('#productTbl').DataTable({
                searching:true,
                ordering:true,
                info:true,
                paging:true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                lengthChange:true,
                autoWidth: false,
                responsive:false,
                columnDefs: [
                    { targets: [ 6,7,8,9,10 ], visible:false, searchable:false }
                ],
                language: {
                    paginate: {
                        next: '<i class="fa-solid fa-chevron-right"></i>',
                        previous: '<i class="fa-solid fa-chevron-left"></i>'
                    }
                }
            });
        }, 
    })
}

function GenTableHeader(val){
    if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
        $('#productTbl').DataTable().clear();
        $('#productTbl').DataTable().destroy(); 
    }
    
    var headerRow = "";
    if (val == "SRP"){
        headerRow = `<tr>
                            <th style="width: 36%">Product</th>
                            <th style="width: 13%">SI No.</th>
                            <th style="width: 15%">Serial No.</th>
                            <th style="width: 12%">Quantity</th>
                            <th style="width: 12%">Old SRP</th>
                            <th style="width: 12%">Updated SRP</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Other Price</th>
                        </tr>`;
    } else if (val == "DP" || val == "Dealer's Price"){
        headerRow = `<tr>
                            <th style="width: 36%">Product</th>
                            <th style="width: 13%">SI No.</th>
                            <th style="width: 15%">Serial No.</th>
                            <th style="width: 12%">Quantity</th>
                            <th style="width: 12%">Old Dealer's Price</th>
                            <th style="width: 12%">Updated Dealer's Price</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Other Price</th>
                        </tr>`;
    } else {
        headerRow = `<tr>
                            <th style="width: 36%">Product</th>
                            <th style="width: 13%">SI No.</th>
                            <th style="width: 15%">Serial No.</th>
                            <th style="width: 12%">Quantity</th>
                            <th style="width: 12%">Old SRP</th>
                            <th style="width: 12%">Updated SRP</th>
                            <th>Supplier</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Branch</th>
                            <th>Other Price</th>
                        </tr>`;
    }

    $("#productTbl thead").html(headerRow);

    productTbl = $('#productTbl').DataTable({
        searching:true,
        ordering:true,
        info:true,
        paging:true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        lengthChange:true,
        autoWidth: false,
        responsive:false,
        columnDefs: [
            { targets: [ 6,7,8,9,10 ], visible:false, searchable:false }
        ],
        language: {
            paginate: {
                next: '<i class="fa-solid fa-chevron-right"></i>',
                previous: '<i class="fa-solid fa-chevron-left"></i>'
            }
        }
    });
}

function LoadCategory(type){
    var isynBranch = $("#isynBranch").val();
    var consignBranch = $("#consignmentBranch").val();

    $.ajax({
        url:"../../routes/inventorymanagement/changeproductsrpdp.route.php",
        type:"POST",
        data:{action:"LoadCategory", type:type, isynBranch:isynBranch},
        dataType:"JSON",
        beforeSend:function(){
            ClearDetails();
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
}

function SearchInventory(){
    var updateType = $('#updateType').val();
    var isynBranch = $('#isynBranch').val();
    var type = $('#type').val();
    var category = $('#category').val();

    if (updateType == null || updateType == ''){
        Swal.fire({
            icon: 'warning', 
            title: 'Select a Update Type.', 
        })
        return;
    }
    if (isynBranch == null || isynBranch == ''){
        Swal.fire({
            icon: 'warning', 
            title: 'Select a Branch.', 
        })
        return;
    }
    if (type == null || type == ''){
        Swal.fire({
            icon: 'warning', 
            title: 'Select a Type.', 
        })
        return;
    }
    if (category == null || category == ''){
        Swal.fire({
            icon: 'warning', 
            title: 'Select a Category.', 
        })
        return;
    }

    $.ajax({
        url:"../../routes/inventorymanagement/changeproductsrpdp.route.php",
        type:"POST",
        data:{action:"SearchInventory", isynBranch:isynBranch,type:type,category:category},
        dataType:"JSON",
        beforeSend:function(){
            ClearDetails();
            if ( $.fn.DataTable.isDataTable( '#inventoryTbl' ) ) {
                $('#inventoryTbl').DataTable().clear();
                $('#inventoryTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            $("#inventoryList").empty();
            $.each(response.INVLIST,function(key,value){
                $("#inventoryList").append(`
                    <tr>
                        <td>${value["Product"]}</td>
                        <td>${value["SIno"]}</td>
                        <td>${value["Serialno"]}</td>
                        <td>${value["Quantity"]}</td>
                        <td>${formatAmtVal(value["DealerPrice"])}</td>
                        <td>${formatAmtVal(value["SRP"])}</td>
                        <td>${value["Supplier"]}</td>
                        <td>${value["Category"]}</td>
                        <td>${value["Type"]}</td>
                        <td>${value["Branch"]}</td>
                    </tr>
                `);
            });

            inventoryTbl = $('#inventoryTbl').DataTable({
                searching:true,
                ordering:true,
                info:true,
                paging:true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                lengthChange:true,
                autoWidth: false,
                responsive:false,
                columnDefs: [
                    { targets: [ 6,7,8,9 ], visible:false, searchable:false }
                ],
                language: {
                    paginate: {
                        next: '<i class="fa-solid fa-chevron-right"></i>',
                        previous: '<i class="fa-solid fa-chevron-left"></i>'
                    }
                }
            });
        }, 
    })
}

$('#inventoryTbl tbody').on('click', 'tr',function(e){
    if(inventoryTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            inventoryTblValue = "";
            SelectedFrominventoryTbl = "";
            $("#newPrice").val("");
            $("#newPrice").attr("disabled",true);

            $("#sinoDisplay").val("");
            $("#serialNoDisplay").val("");
            $("#productDisplay").val("");
            $("#supplierDisplay").val("");
            $("#categoryDisplay").val("");
            $("#typeDisplay").val("");
            $("#branchDisplay").val("");
            $("#dealerspriceDisplay").val("");
            $("#srpDisplay").val("");
            $("#quantityDisplay").val("");

            $("#cancelBtn").attr("disabled",true);
            $("#addProduct").attr("disabled",true);
        } else {
            inventoryTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            inventoryTblValue = $('#inventoryTbl').DataTable().row(this).data();
            SelectedFrominventoryTbl = this;
            
            var product = inventoryTblValue[0].replace(/&amp;/g, "&");
            var sino = inventoryTblValue[1];
            var serialno = inventoryTblValue[2];
            var quantity = inventoryTblValue[3];
            var dealerprice = inventoryTblValue[4].replace(/,/g, '');
            var srp = inventoryTblValue[5].replace(/,/g, '');
            var supplier = inventoryTblValue[6];
            var category = inventoryTblValue[7];
            var type = inventoryTblValue[8];
            var branch = inventoryTblValue[9];
            $("#newPrice").val("");
            $("#newPrice").attr("disabled",false);

            $("#sinoDisplay").val(sino);
            $("#serialNoDisplay").val(serialno);
            $("#productDisplay").val(product);
            $("#supplierDisplay").val(supplier);
            $("#categoryDisplay").val(category);
            $("#typeDisplay").val(type);
            $("#branchDisplay").val(branch);
            $("#dealerspriceDisplay").val(formatAmtVal(dealerprice));
            $("#srpDisplay").val(formatAmtVal(srp));
            $("#quantityDisplay").val(quantity);

            $("#cancelBtn").attr("disabled",false);
            $("#addProduct").attr("disabled",false);
        }
    }
});

function ClearDetails(){
    if ( $.fn.DataTable.isDataTable( '#inventoryTbl' ) ) {
        $('#inventoryTbl').DataTable().clear();
        $('#inventoryTbl').DataTable().destroy(); 
    }
    inventoryTbl = $('#inventoryTbl').DataTable({
        searching:true,
        ordering:true,
        info:true,
        paging:true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        lengthChange:true,
        autoWidth: false,
        responsive:false,
        columnDefs: [
            { targets: [ 6,7,8,9 ], visible:false, searchable:false }
        ],
        language: {
            paginate: {
                next: '<i class="fa-solid fa-chevron-right"></i>',
                previous: '<i class="fa-solid fa-chevron-left"></i>'
            }
        }
    });

    $("#newPrice").val("");
    $("#newPrice").attr("disabled",true);
    $("#sinoDisplay").val("");
    $("#serialNoDisplay").val("");
    $("#productDisplay").val("");
    $("#supplierDisplay").val("");
    $("#categoryDisplay").val("");
    $("#typeDisplay").val("");
    $("#branchDisplay").val("");
    $("#dealerspriceDisplay").val("");
    $("#srpDisplay").val("");
    $("#quantityDisplay").val("");
}

function ClearProductList(){
    if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
        $('#productTbl').DataTable().clear();
        $('#productTbl').DataTable().destroy(); 
    }
    productTbl = $('#productTbl').DataTable({
        searching:true,
        ordering:true,
        info:true,
        paging:true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        lengthChange:true,
        autoWidth: false,
        responsive:false,
        columnDefs: [
            { targets: [ 6,7,8,9,10 ], visible:false, searchable:false }
        ],
        language: {
            paginate: {
                next: '<i class="fa-solid fa-chevron-right"></i>',
                previous: '<i class="fa-solid fa-chevron-left"></i>'
            }
        }
    });
}

function CancelData(){
    $("#cancelBtn").attr("disabled",true);
    $("#addProduct").attr("disabled",true);

    $("#isynBranch").val("");
    $("#type").val("");
    $("#category").empty().append(`<option value="" selected disabled>Select</option>`);
    ClearDetails();
}

function AddProductData(){
    var oldprice = 0;
    var otherprice = 0;
    var updatetype = $("#updateType").val();
    var newprice = $("#newPrice").val().replace(/,/g, '');
    var sino = $("#sinoDisplay").val();
    var serialno = $("#serialNoDisplay").val();
    var product = $("#productDisplay").val();
    var quantity = $("#quantityDisplay").val();
    var supplier = $("#supplierDisplay").val();
    var category = $("#categoryDisplay").val();
    var type = $("#typeDisplay").val();
    var branch = $("#branchDisplay").val();
    if (updatetype == "SRP") {
        oldprice = $("#srpDisplay").val().replace(/,/g, '');
        otherprice = $("#dealerspriceDisplay").val().replace(/,/g, '');
    } else if (updatetype == "DP" || updatetype == "Dealer's Price") {
        oldprice = $("#dealerspriceDisplay").val().replace(/,/g, '');
        otherprice = $("#srpDisplay").val().replace(/,/g, '');
    }

    if (newprice == oldprice) {
        Swal.fire({
            icon: 'warning',
            title: 'Entered new price is the same as the old price.',
        })
        return;
    }

    if (newprice == "" || newprice == null || newprice == 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Enter new price.',
        })
        return;
    }

    productTbl.row.add([product,sino,serialno,quantity,formatAmtVal(oldprice),formatAmtVal(newprice),supplier,category,type,branch,otherprice]).draw(false);

    $("#isynBranch").val("");
    $("#type").val("");
    $("#category").empty().append(`<option value="" selected disabled>Select</option>`);
    ClearDetails();
    $("#updateType").attr("disabled",true);
}

$('#productTbl tbody').on('click', 'tr',function(e){
    if(productTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            productTblValue = "";
            SelectedFromproductTbl = "";

            $("#removeButton").attr("disabled",false);
        } else {
            productTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            productTblValue = $('#productTbl').DataTable().row(this).data();
            SelectedFromproductTbl = this;
            $("#removeButton").attr("disabled",false);
        }
    }
});

function RemoveProduct () {
    if(productTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'No product to remove.',
        })
        return;
    }

    if (SelectedFromproductTbl != "") {
        productTbl.row(SelectedFromproductTbl).remove().draw(false);
        SelectedFromproductTbl = "";
        $("#removeButton").attr("disabled",true);
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Select a product to remove.',
        })
    }
}

function UpdateProduct () {
    if(productTbl.rows().count() === 0){
        Swal.fire({
            icon: 'error',
            title: 'No products to update.',
        })
        return;
    } else {
        var updatemsg = "";
        var updatetype = $("#updateType").val();
        if (updatetype == "SRP") {
            updatemsg = "SRP";
        } else if (updatetype == "DP" || updatetype == "Dealer's Price") {
            updatemsg = "Dealer's Price";
        }

        let Data = productTbl.rows().data().toArray();
        let formdata = new FormData();
        formdata.append("action","UpdateProduct");
        formdata.append("DATA",JSON.stringify(Data));
        formdata.append("UPDATETYPE",updatetype);

        Swal.fire({
            icon: 'info',
            title: 'Do you wish to proceed with the update of New ' + updatemsg + '?',
            showCancelButton: true,
            allowOutsideClick: true,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/inventorymanagement/changeproductsrpdp.route.php",
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
                        if(response.STATUS == "ERROR"){
                            Swal.showValidationMessage(
                                response.MESSAGE,
                            )
                        }
                    },
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'SUCCESS') {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: result.value.MESSAGE,
                    });

                    $("#updateType").val("");
                    $("#updateType").attr("disabled",false);
                    $("#isynBranch").val("");
                    $("#type").val("");
                    $("#category").empty().append(`<option value="" selected disabled>Select</option>`);
                    ClearDetails();
                    ClearProductList();
                } else if (result.value.STATUS == 'ERROR') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: result.value.MESSAGE,
                    });
                } else {
                    Swal.fire({
                        icon: "warning",
                        title: "Warning",
                        text: result.value.MESSAGE,
                    });
                }
            }
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