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
}

function PostGL(){
    let date = $("#postingDate").val();

    Swal.fire({
        icon: "info",
        title: "You are about to post, Date ("+date+")? This will post General Ledger.",
        showCancelButton: true,
        confirmButtonText: "Proceed",
        showLoaderOnConfirm: true,
        // allowOutsideClick: false,
        preConfirm: () => {
            if(date == ""){
                Swal.showValidationMessage(
                    `Please Select Date`
                )
            }else{
                return $.ajax({
                    url:"../../routes/generalledger/posting.route.php",
                    type:"POST",
                    data:{action:"PostGL",date:date},
                    dataType:"JSON",
                    success:function(response){
                        // if(response.STATUS != "SUCCESS" && response.STATUS != "POSTED"){
                        //     Swal.showValidationMessage(
                        //         `ERROR IN PROCESS OF POSTING GENERAL LEDGER`
                        //     )
                        // }
                    }
                })
            }
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if(result.value.STATUS == "SUCCESS"){
                Swal.fire({
                    icon:"success",
                    text:"GENERAL LEDGER HAS BEEN POSTED."
                })
                // LoadLPDandUPD();
            }else if(result.value.STATUS == "POSTED"){
                Swal.fire({
                    icon:"warning",
                    text:"GENERAL LEDGER FOR THIS DATE IS ALREADY POSTED."
                })
            }
        }
    });
}

function UnPostGL(){
    let date = $("#unpostingDate").val();

    Swal.fire({
        icon: "info",
        title: "You are about to undo post, Date ("+date+")? This will undo post general ledger.",
        showCancelButton: true,
        confirmButtonText: "Proceed",
        showLoaderOnConfirm: true,
        // allowOutsideClick: false,
        preConfirm: () => {
            if(date == ""){
                Swal.showValidationMessage(
                    `Please Select Date`
                )
            }else{
                return $.ajax({
                    url:"../../routes/generalledger/posting.route.php",
                    type:"POST",
                    data:{action:"UndoPostGL",date:date},
                    dataType:"JSON",
                    success:function(response){
                        if(response.STATUS != "SUCCESS1" && response.STATUS != "SUCCESS2" && response.STATUS != "NODATA"){
                            Swal.showValidationMessage(
                                `ERROR IN PROCESS OF UNPOSTING GENERAL LEDGER`
                            )
                        }
                    }
                })
            }
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            let datetoday = new Date();
            if(result.value.STATUS == "SUCCESS1"){
                Swal.fire({
                    icon:"success",
                    text:"POSTED Transactions from the inclusive dates (" + date + " - " + datetoday.toLocaleDateString("en-US") + "), succesfully rolled back."
                })
            }else if(result.value.STATUS == "SUCCESS2"){
                Swal.fire({
                    icon:"success",
                    text:"POSTED Transactions for the date (" + date + "), succesfully rolled back."
                })
            }else if(result.value.STATUS == "NODATA"){
                Swal.fire({
                    icon:"warning",
                    text:"No Transaction to UNDO with the date supplied."
                })
            }
        }
    });
}