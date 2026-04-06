var TransactionStat = "";
Initialize();

function Initialize(){    
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

    $('.Date').datetimepicker(options);

    $.ajax({
        url:baseUrl + "/routes/inventorymanagement/closetransaction.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        success:function(response){
            TransactionStat = response.STATUS;

            // REMOVED: Immediate blocking on page load.
            // We now allow the user to see the page and click the button.
            // The check will happen inside CloseTransaction() instead.
            /*
            if(response.STATUS == "CLOSED"){
                Swal.fire({
                    icon: 'error',
                    text: 'Unable to close today\'s transaction as the current inventory is unbalanced.',
                    allowOutsideClick: false,
                })
                $("#closingDate").prop("disabled",true);
                $("#closeTransactionBtn").prop("disabled",true);
            }
            */
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
                    url:baseUrl + "/routes/inventorymanagement/closetransaction.route.php",
                    type:"POST",
                    data:{action:"CloseTransaction",closingDate:date},
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