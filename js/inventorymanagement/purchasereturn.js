var dataInvTbl, dataInvTblValue = "", itemTbl, SelectedFromList = "", SelectedFromDataInv = "", DataInvSINo = "";

Initialize();
LoadDataInventory();

function Initialize(){
    $.ajax({
        url:"../../routes/inventorymanagement/incominginventory.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#branch").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.BRANCH,function(key,value){
                    $("#branch").append(`
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

            itemTbl = $('#itemTbl').DataTable({
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
                    { targets: [ 3,4,5,6,7,8,9 ], className: 'dt-right' },
                    // { targets: [ 4,5,6,7,8,9,10,11,12 ], visible:false, searchable:false }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
        
                    // Remove the formatting to get integer data for summation
                    var intVal = function (i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                    };

                    ListTtlDealerPrice = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    ListTtlSRP = api.column(4).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                    
                    ListTtlQty = api.column(5).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    ListTtlPrice = api.column(6).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    ListFnlTtlSRP = api.column(7).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    ListTtlMPI = api.column(8).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    ListFnlTtlMarkup = api.column(9).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
        
                    // Update footer
                    $(api.column(3).footer()).html(formatAmtVal(ListTtlDealerPrice));
                    $(api.column(4).footer()).html(formatAmtVal(ListTtlSRP));
                    $(api.column(5).footer()).html(formatAmtVal(ListTtlQty));
                    $(api.column(6).footer()).html(formatAmtVal(ListTtlPrice));
                    $(api.column(7).footer()).html(formatAmtVal(ListFnlTtlSRP));
                    $(api.column(8).footer()).html(formatAmtVal(ListTtlMPI));
                    $(api.column(9).footer()).html(formatAmtVal(ListFnlTtlMarkup));
                },
            });
        }, 
    })
}

function LoadDataInventory(){
    $.ajax({
        url:"../../routes/inventorymanagement/incominginventory.route.php",
        type:"POST",
        data:{action:"LoadDataInventory"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#dataInvTbl' ) ) {
                $('#dataInvTbl').DataTable().clear();
                $('#dataInvTbl').DataTable().destroy(); 
            }
        },
        success:function(response){

            if (response.DATAINVSINO.length > 0) {
                DataInvSINo = response.DATAINVSINO[0]['SINo'];
            }

            $("#dataInvList").empty();
            $.each(response.DATAINV,function(key,value){
                $("#dataInvList").append(`
                    <tr>
                        <td>${value["SIno"]}</td>
                        <td>${value["Serialno"]}</td>
                        <td>${value["Product"]}</td>
                        <td>${value["Supplier"]}</td>
                        <td>${value["DatePurchase"]}</td>
                        <td>${value["Quantity"]}</td>
                        <td>${value["DealerPrice"]}</td>
                        <td>${value["TotalPrice"]}</td>
                        <td>${value["VatSales"]}</td>
                        <td>${value["Vat"]}</td>
                        <td>${value["AmountDue"]}</td>
                    </tr>
                `);
            });

            dataInvTbl = $('#dataInvTbl').DataTable({
                searching: false,
                ordering: false,
                info: false,
                paging: false, // Disable pagination
                scrollY: '230px', // Adjust height for 5 rows
                scrollX: true, // Enable horizontal scrolling
                scrollCollapse: true, // Allow table to shrink if less data is present
                lengthChange: false,
                responsive: false, // Disable responsive behavior for fixed scrolling
            });
        }, 
    })
}

function LoadProdCateg (type){
    $.ajax({
        url:"../../routes/inventorymanagement/incominginventory.route.php",
        type:"POST",
        data:{action:"LoadProdCateg", type:type},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#category").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.PRODCATEG,function(key,value){
                    $("#category").append(`
                        <option value="${value["Category"]}">
                            ${value["Category"]}
                        </option>
                    `);
            });

            $("#product").empty().append(`<option value="" disabled selected>Select</option>`);
            $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`);
        }, 
    })
}

function LoadProdName (categ){
    $.ajax({
        url:"../../routes/inventorymanagement/incominginventory.route.php",
        type:"POST",
        data:{action:"LoadProdName", categ:categ},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#product").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.PRODUCT,function(key,value){
                    $("#product").append(`
                        <option value="${value["Product"]}">
                            ${value["Product"]}
                        </option>
                    `);
            });
            
            $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`);
        }, 
    })
}

function LoadSupplier (productname){
    $.ajax({
        url:"../../routes/inventorymanagement/incominginventory.route.php",
        type:"POST",
        data:{action:"LoadSupplier", productname:productname},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.SUPPLIER,function(key,value){
                    $("#supplier").append(`
                        <option value="${value["Supplier"]}">
                            ${value["Supplier"]}
                        </option>
                    `);
            });
        }, 
    })
}

function Compute(quantity) {
    if (Compute.timeout) clearTimeout(Compute.timeout);
        Compute.timeout = setTimeout(() => {
        
        if (quantity < 0) {
            Swal.fire({
                icon: 'warning',
                text: 'Negative amount is not allowed. Try Again!',
            });
            $('#quantity').val('');
            return;
        }

        var dealerPrice = parseFloat($('#dealersPrice').val().replace(/,/g, ''));
        var srp = parseFloat($('#srp').val().replace(/,/g, ''));

        var Total = dealerPrice * quantity;
        var TotalSRP = srp * quantity;
        $('#totalPrice').val(formatAmtVal(Total));
        $('#totalSRP').val(formatAmtVal(TotalSRP));
        var mpi  = srp - dealerPrice;
        $('#mpi').val(formatAmtVal(mpi));
        var totalMarkup = TotalSRP - Total; 
        $('#totalmarkup').val(formatAmtVal(totalMarkup));

    }, 300);
}

$('#addToList').on('click', function() {
    console.log(DataInvSINo);
    let branch = $('#branch').val();
    let type = $('#type').val();
    let categ = $('#category').val();
    let product = $('#product').val();
    let supplier = $('#supplier').val();
    let supplierSI = $('#suppliersSI').val();
    let serialNo = $('#serialNo').val(); 
    let purchaseDate = $('#purchaseDate').val();
    let warranty = $('#warranty').val();
    let imageName = $('#imageName').val();
    let dateEncoded = $('#dateEncoded').val();
    let dealerPrice = $('#dealersPrice').val();
    let srp = $('#srp').val();
    let quantity = $('#quantity').val();
    let totalPrice = $('#totalPrice').val();
    let totalSRP = $('#totalSRP').val();
    let mpi = $('#mpi').val();
    let totalMarkup = $('#totalmarkup').val();

    if (DataInvSINo != "") {
        if (supplierSI !== DataInvSINo) {
            Swal.fire({
                icon: 'warning',
                text: 'Entered Supplier(s) SI must match the SI No in Data Inventory',
            });
            return;
        }
    }

    if (branch == null || type == null || categ == null || product == null || supplier == null || supplierSI == "" || serialNo == "" || purchaseDate == "" || warranty == "" || imageName == "" || dateEncoded == "" || supplierSI == "" || dealerPrice == "" || srp == "" || quantity == "" || totalPrice == "" || totalSRP == "" || mpi == "" || totalMarkup == "") {
        Swal.fire({
            icon: 'warning',
            text: 'Please enter required details.',
        });
        return;
    }

    itemTbl.row.add([
        product,
        serialNo,
        warranty,
        dealerPrice,
        srp,
        quantity,
        totalPrice,
        totalSRP,
        mpi,
        totalMarkup,
        branch,
        type,
        categ,
        supplier,
        supplierSI,
        purchaseDate,
        imageName,
        dateEncoded,
    ]).draw(false);

});

$('#addNew').on('click', function() {
    $('#branch').prop('disabled', false);
    $('#type').prop('disabled', false);
    $('#category').prop('disabled', false);
    $('#product').prop('disabled', false);
    $('#supplier').prop('disabled', false);
    $('#suppliersSI').prop('disabled', false);
    $('#serialNo').prop('disabled', false);   
    $('#purchaseDate').prop('disabled', false);
    $('#warranty').prop('disabled', false);
    $('#imageName').prop('disabled', false);
    $('#dateEncoded').prop('disabled', false);
    $('#dealersPrice').prop('disabled', false);
    $('#srp').prop('disabled', false);
    $('#quantity').prop('disabled', false);
    $('#totalPrice').prop('readonly', true);
    $('#totalSRP').prop('readonly', true);
    $('#mpi').prop('readonly', true);
    $('#totalmarkup').prop('readonly', true);
    $('#addNew').prop('hidden', true);
    $('#addNew').prop('disabled', true);
    $('#cancel').prop('hidden', false);
    $('#cancel').prop('disabled', false);
    $('#addToList').prop('hidden', false);
    $('#addToList').prop('disabled', false);
    $('#save').prop('hidden', false);
    $('#save').prop('disabled', false);
    // $("#CustomerInfoTbl tbody tr").removeClass("selected");
});

function Cancel(){
    $('#branch').prop('disabled', true).val('');
    $('#type').prop('disabled', true).val('');
    $('#category').prop('disabled', true).val('');
    $('#product').prop('disabled', true).val('');
    $('#supplier').prop('disabled', true).val('');
    $("#category").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#product").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`);
    $('#suppliersSI').prop('disabled', true).val('');
    $('#serialNo').prop('disabled', true).val('');
    $('#purchaseDate').prop('disabled', true).val('');
    $('#warranty').prop('disabled', true).val('');
    $('#imageName').prop('disabled', true).val('');
    $('#dateEncoded').prop('disabled', true).val('');
    $('#dealersPrice').prop('disabled', true).val('');
    $('#srp').prop('disabled', true).val('');
    $('#quantity').prop('disabled', true).val('');
    $('#totalPrice').prop('readonly', true).val('');
    $('#totalSRP').prop('readonly', true).val('');
    $('#mpi').prop('readonly', true).val('');
    $('#totalmarkup').prop('readonly', true).val('');
    $('#addNew').prop('hidden', false).prop('disabled', false);
    $('#cancel').prop('hidden', true).prop('disabled', true);
    $('#addToList').prop('hidden', true).prop('disabled', true);
    $('#save').prop('hidden', true).prop('disabled', true);
    // $("#CustomerInfoTbl tbody tr").removeClass("selected");
}

$('#dataInvTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
        $("#DeleteFromDataInvBtn").attr("disabled",true);
        dataInvTblValue = "";
        SelectedFromDataInv = "";
    } else {
        dataInvTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
        $("#DeleteFromDataInvBtn").attr("disabled",false);
        dataInvTblValue = $('#dataInvTbl').DataTable().row(this).data();
        SelectedFromDataInv = this;
    }    
});

function DeleteFromDataInv(){
    if  (SelectedFromDataInv != "") {
        var SINo = dataInvTblValue[0];
        var SerialNo = dataInvTblValue[1];
        var Product = dataInvTblValue[2];
        Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: 'This action will delete the selected data from the inventory. Are you sure you want to proceed?',
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/inventorymanagement/incominginventory.route.php",
                    type: "POST",
                    data: {action:"DeleteFromDataInv", SINo:SINo, SerialNo:SerialNo, Product:Product},
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
                    LoadDataInventory();
                    // Cancel();
                    SelectedFromDataInv = "";
                    $("#DeleteFromDataInvBtn").attr("disabled",true);

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
            title: 'Please select from the Data Inventory to delete',
        })
    }
}

$('#itemTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
        $("#DeleteFromListBtn").attr("disabled",true);
        SelectedFromList = "";
    } else {
        itemTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
        $("#DeleteFromListBtn").attr("disabled",false);
        SelectedFromList = this;
    }
});

function DeleteFromList(){
    if  (SelectedFromList != "") {
        itemTbl.row(SelectedFromList).remove().draw(false);
        SelectedFromList = "";
        $("#DeleteFromListBtn").attr("disabled",true);
    } else {
        SelectedFromList = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select from the Items to delete',
        })
    }
}

function Save(){
    $('#printBtn').prop('disabled', false);
    if(itemTbl.rows().count() === 0){
        // When Item List is empty, this will encode the details in Particulars
        let branch = $('#branch').val();
        let type = $('#type').val();
        let categ = $('#category').val();
        let product = $('#product').val();
        let supplier = $('#supplier').val();
        let supplierSI = $('#suppliersSI').val();
        let serialNo = $('#serialNo').val(); 
        let purchaseDate = $('#purchaseDate').val();
        let warranty = $('#warranty').val();
        let imageName = $('#imageName').val();
        let dateEncoded = $('#dateEncoded').val();
        let dealerPrice = $('#dealersPrice').val();
        let srp = $('#srp').val();
        let quantity = $('#quantity').val();
        let totalPrice = $('#totalPrice').val();
        let totalSRP = $('#totalSRP').val();
        let mpi = $('#mpi').val();
        let totalMarkup = $('#totalmarkup').val();

        if (DataInvSINo != "") {
            if (supplierSI !== DataInvSINo) {
                Swal.fire({
                    icon: 'warning',
                    text: 'Entered Supplier(s) SI must match the SI No in Data Inventory',
                });
                return;
            }
        }

        if (branch == null || type == null || categ == null || product == null || supplier == null || supplierSI == "" || serialNo == "" || purchaseDate == "" || warranty == "" || imageName == "" || dateEncoded == "" || supplierSI == "" || dealerPrice == "" || srp == "" || quantity == "" || totalPrice == "" || totalSRP == "" || mpi == "" || totalMarkup == "") {
            Swal.fire({
                icon: 'warning',
                text: 'Please enter required details.',
            });
            return;
        } else if (dealerPrice == 0 || srp == 0 || quantity == 0 || totalPrice == 0 || totalSRP == 0) {
            Swal.fire({
                icon: 'warning',
                text: 'Amount cannot be 0.',
            });
            return;
        }

        Swal.fire({
            icon: 'info',
            title: 'Ready to save the product details?',
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/inventorymanagement/incominginventory.route.php",
                    type: "POST",
                    data: {action:"SaveSingle", branch:branch,type:type,categ:categ,product:product,supplier:supplier,supplierSI:supplierSI,serialNo:serialNo,purchaseDate:purchaseDate,warranty:warranty,imageName:imageName,dateEncoded:dateEncoded,dealerPrice:dealerPrice,srp:srp,quantity:quantity,totalPrice:totalPrice,totalSRP:totalSRP,mpi:mpi,totalMarkup:totalMarkup},
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
                    LoadDataInventory();
                    Cancel();
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
        // When Item List is not empty, this will encode the Item List and skip what's left in Particulars
        let Data = itemTbl.rows().data().toArray();
    
        let formdata = new FormData();
        formdata.append("action","SaveAll");
        formdata.append("DATA",JSON.stringify(Data));

        Swal.fire({
            icon: 'question',
            title: 'Ready to save all product details?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Yes, proceed!',
            // allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/inventorymanagement/incominginventory.route.php",
                    type: "POST",
                    data: formdata,
                    processData: false,
                    contentType: false,
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
                    LoadDataInventory();
                    Cancel();
                    itemTbl.clear().draw(false);
                } else if (result.value.STATUS != 'success') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: result.value.MESSAGE,
                    });
                }                
            }
        });

     }
}

function PrintSupplierSalesInvoice(){
    if(dataInvTbl.rows().count() === 0){
        // When Data Inv List is empty, this will halt the printing
        Swal.fire({
            icon:'warning',
            title: 'Nothing to print!',
        });
        return;
    } else {
        let Data = dataInvTbl.rows().data().toArray();
        let formdata = new FormData();
        formdata.append("action","PrintSupplierSalesInvoice");
        formdata.append("DATA",JSON.stringify(Data));

        $.ajax({
            url: "../../routes/inventorymanagement/incominginventory.route.php",
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
                window.open("../../routes/inventorymanagement/incominginventory.route.php?type=PrintSuppRcpt");
                LoadDataInventory();
                $('#printBtn').prop('disabled', true);
            },
        });
    }
}

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