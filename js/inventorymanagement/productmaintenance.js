var productTbl, State = "NONE";

Initialize();

$("#prodType").select2({
    width: '100%',
    tags: true,
});
$("#prodCateg").select2({
    width: '100%',
    tags: true,
});
$("#supplier").select2({
    width: '100%',
    tags: true,
});
$("#prodName").select2({
    width: '100%',
    tags: true,
});

function Initialize(){
    $.ajax({
        url:"../../routes/inventorymanagement/productmaintenance.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
                $('#productTbl').DataTable().clear();
                $('#productTbl').DataTable().destroy(); 
            }
        },
        success:function(response){

            $("#prodType").empty().append(`<option value="" selected disabled></option>`);
            $.each(response.PRODTYPE,function(key,value){
                let type = value["Type"].trim();
                $("#prodType").append(`
                    <option value="${type}">
                        ${type}
                    </option>
                `);
            });

            $("#prodCateg").empty().append(`<option value="" selected disabled></option>`);
            $.each(response.PRODCATEG,function(key,value){
                let category = value["Category"].trim();
                $("#prodCateg").append(`
                    <option value="${category}">
                        ${category}
                    </option>
                `);
            });
            
            $("#supplier").empty().append(`<option value="" selected disabled></option>`);
            $.each(response.SUPPLIER,function(key,value){
                let supplier = value["supplierName"].trim();
                $("#supplier").append(`
                    <option value="${supplier}">
                    ${supplier}
                    </option>
                `);
            });
            
            $("#prodName").empty().append(`<option value="" selected disabled></option>`);
            $.each(response.PRODUCT,function(key,value){
                let product = value["Product"].trim();
                $("#prodName").append(`
                    <option value="${product}">
                        ${product}
                    </option>
                `);
            });

            $("#productList").empty();
            $.each(response.LIST,function(key,value){
                $("#productList").append(`
                    <tr>
                        <td>${value["ID"]}</td>
                        <td>${value["Product"]}</td>
                        <td>${value["Supplier"]}</td>
                        <td>${value["Category"]}</td>
                        <td>${value["Type"]}</td>
                    </tr>
                `);
            });

            productTbl = $('#productTbl').DataTable({
                // searching:true,
                // ordering:true,
                // info:true,
                // paging:false,
                // lengthChange:true,
                // scrollY: '445px',
                // scrollX: true,  
                // scrollCollapse: true,
                // responsive:false,
                columnDefs: [
                    { targets: [ 0 ], visible:false, searchable:false }
                ],
            });
        }
    })
}

$('#productTbl tbody').on('click', 'tr',function(e){
    if(productTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#editBtn").attr("disabled",true);
            $('#refNo').val("");
            $('#prodType').val("").trigger('change');
            $('#prodCateg').val("").trigger('change');
            $('#supplier').val("").trigger('change');
            $('#prodName').val("").trigger('change');
        } else {
            productTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $("#editBtn").attr("disabled",false);
            var data = $('#productTbl').DataTable().row(this).data();

            $('#refNo').val(data[0]);
            $('#prodType').val(data[4]).trigger('change');
            $('#prodCateg').val(data[3]).trigger('change');
            $('#supplier').val(data[2]).trigger('change');
            $('#prodName').val(data[1]).trigger('change');
        }
    }
});

function AddNew(){
    State = "ADD";

    $("#saveBtn").attr("disabled",false);
    $("#editBtn").attr("disabled",true);
    $("#cancelBtn").attr("disabled",false);
    $('#refNo').val("");
    $('#prodType').prop("disabled", false).val("").trigger('change');
    $('#prodCateg').prop("disabled", false).val("").trigger('change');
    $('#supplier').prop("disabled", false).val("").trigger('change');
    $('#prodName').prop("disabled", false).val("").trigger('change');
}

function EditProduct(){
    State = "EDIT";

    $("#saveBtn").attr("disabled",false);
    $("#editBtn").attr("disabled",true);
    $("#cancelBtn").attr("disabled",false);
    $('#prodType').prop("disabled", false);
    $('#prodCateg').prop("disabled", false);
    $('#supplier').prop("disabled", false);
    $('#prodName').prop("disabled", false);
}

function Cancel(){
    State = "NONE";

    $("#saveBtn").attr("disabled",true);
    $("#editBtn").attr("disabled",true);
    $("#cancelBtn").attr("disabled",true);
    $('#refNo').val("");
    $('#prodType').prop("disabled", true).val("").trigger('change');
    $('#prodCateg').prop("disabled", true).val("").trigger('change');
    $('#supplier').prop("disabled", true).val("").trigger('change');
    $('#prodName').prop("disabled", true).val("").trigger('change');
}

function Save(){
    if (State == "NONE"){
        Cancel();
        return;
    }
    
    var refNo = $('#refNo').val();
    var prodType = $('#prodType').val();
    var prodCateg = $('#prodCateg').val();
    var supplier = $('#supplier').val();
    var product = $('#prodName').val();
    
    if (State == "EDIT"){
        if (refNo == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Select a Product to edit.',
            })
            return;
        }
    }
    if (prodType == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Product Type is empty.',
        })
        return;
    }
    if (prodCateg == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Product Category is empty.',
        })
        return;
    }
    if (supplier == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Supplier is empty.',
        })
        return;
    }
    if (product == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Product Name is empty.',
        })
        return;
    }

    $.ajax({
        url:"../../routes/inventorymanagement/productmaintenance.route.php",
        type:"POST",
        data:{action:"SaveProduct", state:State,refNo:refNo,prodType:prodType,prodCateg:prodCateg,supplier:supplier,product:product},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){

        }
    })
}

function CloseTransaction(){
    let date = $("#closingDate").val();
    if (TransactionStat == "CLOSED"){
        Swal.fire({
            icon: 'error',
            text: 'Unable to close today\'s transaction as the current inventory is unbalanced.',
            allowOutsideClick: false,
        })
        $("#closingDate").prop("disabled",true);
        $("#closeTransactionBtn").prop("disabled",true);
        return;
    }

    Swal.fire({
        icon: "info",
        title: "Are you sure you want to close today's transaction?.",
        showCancelButton: true,
        confirmButtonText: "Yes",
        showLoaderOnConfirm: true,
        // allowOutsideClick: false,
        preConfirm: () => {
            if(date == ""){
                Swal.showValidationMessage(
                    `Please Select a Closing Date.`
                )
            }else{
                return $.ajax({
                    url:"../../routes/inventorymanagement/closetransaction.route.php",
                    type:"POST",
                    data:{action:"CloseTransaction",closingDate:closingDate},
                    dataType:"JSON",
                    success:function(response){
                    }
                })
            }
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if(result.value.STATUS == "SUCCESS"){
                Swal.fire({
                    icon: "success",
                    text: result.value.MESSAGE,
                })
                $("#closingDate").prop("disabled",true);
                $("#closeTransactionBtn").prop("disabled",true);
            }else if(result.value.STATUS == "ERROR"){
                Swal.fire({
                    icon: "error",
                    text: result.value.MESSAGE,
                })
            }
        }
    });
}