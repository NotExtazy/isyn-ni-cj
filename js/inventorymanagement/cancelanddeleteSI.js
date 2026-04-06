var transactionTbl, transactionTblValue = "", SelectedFromTransactionTbl = "";

Initialize();

function Initialize(){
    $.ajax({
        url:"../../routes/inventorymanagement/cancelanddeleteSI.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#transactionTbl' ) ) {
                $('#transactionTbl').DataTable().clear();
                $('#transactionTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
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
            };
        
            $('#transactionDate').datetimepicker(options);

            $("#transactionList").empty();
            $.each(response.INVOUTLIST,function(key,value){
                $("#transactionList").append(`
                    <tr>
                        <td>${value["SI"]}</td>
                        <td>${value["Product"]}</td>
                        <td>${value["Soldto"]}</td>
                        <td>${value["DateAdded"]}</td>
                    </tr>
                `);
            });

            transactionTbl = $('#transactionTbl').DataTable({
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

function LoadTransactionsOnDate(){
    var date = $('#transactionDate').val();
    
    ClearTransactionDetails();

    if (date == null || date == ''){
        Swal.fire({
            icon: 'warning', 
            title: 'Select a transaction date.', 
        })
        return;
    }
    $.ajax({
        url:"../../routes/inventorymanagement/cancelanddeleteSI.route.php",
        type:"POST",
        data:{action:"LoadTransactionsOnDate", date:date},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#transactionTbl' ) ) {
                $('#transactionTbl').DataTable().clear();
                $('#transactionTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            $("#transactionList").empty();
            $.each(response.INVOUTLIST,function(key,value){
                $("#transactionList").append(`
                    <tr>
                        <td>${value["SI"]}</td>
                        <td>${value["Product"]}</td>
                        <td>${value["Soldto"]}</td>
                        <td>${value["DateAdded"]}</td>
                    </tr>
                `);
            });

            transactionTbl = $('#transactionTbl').DataTable({
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

$('#transactionTbl tbody').on('click', 'tr',function(e){
    if(transactionTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#deleteBtn").attr("disabled",true);
            $("#cancelBtn").attr("disabled",true);
            transactionTblValue = "";
            SelectedFromTransactionTbl = "";
            ClearTransactionDetails();
        } else {
            transactionTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            transactionTblValue = $('#transactionTbl').DataTable().row(this).data();
            SelectedFromTransactionTbl = this;
            
            var si = transactionTblValue[0];
            var productName = transactionTblValue[1].replace(/&amp;/g, "&");
            var soldto = transactionTblValue[2];
            var datesold = transactionTblValue[3];
            if (productName == "CANCELLED"){
                $("#deleteBtn").attr("disabled",true);
                $("#cancelBtn").attr("disabled",true);
            } else {
                $("#deleteBtn").attr("disabled",false);
                $("#cancelBtn").attr("disabled",false);
            }

            console.log(productName);

            $.ajax({
                url:"../../routes/inventorymanagement/cancelanddeleteSI.route.php",
                type:"POST",
                data:{action:"LoadTransactionDetails", si:si,productName:productName,soldto:soldto,datesold:datesold},
                dataType:"JSON",
                beforeSend:function(){
                },
                success:function(response){
                    var info = response.DETAILS;
                    $("#dateSold").val(info.DateAdded);
                    $("#soldTo").val(info.Soldto);
                    $("#supplierSI").val(info.SupplierSI);
                    $("#SINo").val(info.SI);
                    $("#product").val(info.Product);
                    $("#supplier").val(info.Supplier);
                    $("#quantity").val(info.Quantity);
                    $("#dealersPrice").val(info.DealerPrice);
                    $("#totalPrice").val(info.TotalPrice);
                    $("#srp").val(info.SRP);
                    $("#totalsrp").val(info.TotalSRP);
                    $("#markup").val(info.Markup);
                    $("#totalMarkup").val(info.TotalMarkup);
                }, 
            })
        }
    }
});

function ClearTransactionDetails(){
    $("#dateSold").val("");
    $("#soldTo").val("");
    $("#supplierSI").val("");
    $("#SINo").val("");
    $("#product").val("");
    $("#supplier").val("");
    $("#quantity").val("");
    $("#dealersPrice").val("");
    $("#totalPrice").val("");
    $("#srp").val("");
    $("#totalsrp").val("");
    $("#markup").val("");
    $("#totalMarkup").val("");
}

function CANCEL (){
    Swal.fire({
        icon: 'warning',
        title: 'Warning!',
        text: 'Would you like to cancel the entire transaction(s) for this SI#?',
        showCancelButton: true,
        allowOutsideClick: true,
    }).then(function(result) {
        if (result.isConfirmed) {
            $('#cancelReasonMDL').modal('show');
            $('#cancelReason').val('');
        } 
    });
}

function ProceedCancel(){
    var dateSold = $('#dateSold').val();
    var soldTo = $('#soldTo').val();
    var sino = $('#SINo').val();
    var cancelReason = $('#cancelReason').val();

    if (cancelReason == "" || cancelReason == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Enter a Cancel Reason.',
        })
        return;
    }
    
    $.ajax({
        url: "../../routes/inventorymanagement/cancelanddeleteSI.route.php",
        type: "POST",
        data: {action:"CancelSI", dateSold:dateSold,soldTo:soldTo,sino:sino,cancelReason:cancelReason},
        dataType: 'JSON',
        beforeSend: function() {
            console.log('Processing Request...')
        },
        success: function(response) {
            if (response.STATUS == 'SUCCESS') {
                Swal.fire({
                    icon: 'success',
                    title: response.MESSAGE,
                })
                $('#cancelReasonMDL').modal('hide');
                $('#cancelReason').val('');
                $("#deleteBtn").attr("disabled",true);
                $("#cancelBtn").attr("disabled",true);
                transactionTblValue = "";
                SelectedFromTransactionTbl = "";
                Initialize();
                ClearTransactionDetails();
            } else if (response.STATUS == 'ERROR') {
                Swal.fire({
                    icon: 'warning',
                    title: response.MESSAGE,
                })
            }
        },
    });
}

function DELETE(){
    var soldTo = $('#soldTo').val();
    var sino = $('#SINo').val();
    var dateSold = $('#dateSold').val();

    Swal.fire({
        icon: 'warning',
        title: 'Warning!',
        text: 'Would you like to delete the entire transaction(s) for this SI#?',
        showCancelButton: true,
        allowOutsideClick: true,
    }).then(function(result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "../../routes/inventorymanagement/cancelanddeleteSI.route.php",
                type: "POST",
                data: {action:"DeleteSI", dateSold:dateSold,soldTo:soldTo,sino:sino},
                dataType: 'JSON',
                beforeSend: function() {
                    console.log('Processing Request...')
                },
                success: function(response) {
                    if (response.STATUS == 'SUCCESS') {
                        Swal.fire({
                            icon: 'success',
                            title: response.MESSAGE,
                        })
                        $("#deleteBtn").attr("disabled",true);
                        $("#cancelBtn").attr("disabled",true);
                        transactionTblValue = "";
                        SelectedFromTransactionTbl = "";
                        Initialize();
                        ClearTransactionDetails();
                    } else if (response.STATUS == 'ERROR') {
                        Swal.fire({
                            icon: 'warning',
                            title: response.MESSAGE,
                        })
                    }
                },
            });
        } 
    });
    
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