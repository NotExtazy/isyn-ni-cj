let Mode;

LoadInit();

function LoadInit (){
    $.ajax({
        url: "../../routes/cashier/dssetting.route.php",
        type: "POST",
        data: {action:"LoadFund"},
        dataType: "JSON",
        beforeSend: function() {
        },
        success: function(response) {
            $("#fund").empty().append("<option value='' selected disabled> Select Fund</option>");
            $.each(response.FUND,function(key,value){
                $("#fund").append(`<option value="${value["FUND"]}">${value["FUND"]}</option>`);
            });

            $("#dsPrefix").val(response.DSPREFIX);
        },
        error: function(err) {
            console.log(err)
        }
    });
}

function LoadBank (fund){
    if (fund != "") {
        $.ajax({
            url: "../../routes/cashier/dssetting.route.php",
            type: "POST",
            data: {action:"LoadBank", fund: fund},
            dataType: "JSON",
            beforeSend: function() {
            },
            success: function(response) {
                $("#editDS").prop('disabled', false)
    
                $("#bank").empty().append("<option value='' selected disabled> Select Bank</option>");
                $.each(response.BANK,function(key,value){
                    $("#bank").append(`<option value="${value["BANK"]}">${value["BANK"]}</option>`);
                });

                if (response.STATUS == "MERON"){
                    Mode = "EDIT";
                    $("#bank").val(response.DEFAULTBANK);
                    $("#lastDSNo").val(response.DSNO);
                } else {
                    Mode = "SET";
                    $("#bank").val(response.DEFAULTBANK);
                    $("#lastDSNo").val(response.DSNO);
                }
    
            },
            error: function(err) {
                console.log(err)
            }
        });
    } else {
        $("#editDS").prop('disabled', true);
        $("#cancel").prop('disabled', true);
    }
}

function EditDS(){
    $("#bank").prop('disabled', false);
    $("#lastDSNo").prop('disabled', false);
    $("#saveDS").prop('disabled', false);
    $("#editDS").prop('disabled', true);
    $("#cancel").prop('disabled', false);
}

function Cancel(){
    $("#fund").val("");
    $("#bank").prop('disabled', true).val("");
    $("#saveDS").prop('disabled', true).val("");
    $("#lastDSNo").prop('disabled', true);
    $("#editDS").prop('disabled', true);
    $("#cancel").prop('disabled', true);
}

function SaveDS () {
    let fund =$("#fund").val();
    let bank =$("#bank").val();
    let lastDSNo = $("#lastDSNo").val();
    let dsPrefix = $("#dsPrefix").val();

    if (fund == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Fund.',
        })
        return;
    } else if (bank == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing To bank',
        })
        return;
    } else if (lastDSNo == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing To Last DS No.',
        })
        return;
    } else if (dsPrefix == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing To DS Prefix',
        })
        return;
    } else {
        Swal.fire({
            icon: 'question',
            title: 'Save DS Setting?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Yes, proceed!',
            // allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/cashier/dssetting.route.php",
                    type: "POST",
                    data: {action:"SaveDSSetting", fund:fund,bank:bank,lastDSNo:lastDSNo,Mode:Mode},
                    dataType: 'JSON',
                    beforeSend: function() {
                        console.log('Processing Request...')
                    },
                    success: function(response) {
                        if (response.STATUS == 'SUCCESS') {
                            console.log('Request Processed...')
                            resetform();
                        }
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'SUCCESS') {
                    Swal.fire({
                      icon: "success",
                      text: "DS Setting Saved Successfully",
                  });
                } else if (result.value.STATUS != 'SUCCESS') {
                    Swal.fire({
                        icon: "warning",
                        text: "DS Setting Failed to Save",
                    });
                }
            }
        });
    }
 }

 function resetform (){
    Cancel();
}

function formatInput(input) {
    let cleanValue = input.value.replace(/[^0-9.,]/g, '');
    cleanValue = cleanValue.replace(/,/g, '');
    if (cleanValue !== '') {
        input.value = cleanValue;
    } else {
        input.value = '0';
    }
}