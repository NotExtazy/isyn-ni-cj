let SelectedDate;
var undeptodayTbl, undepprevTbl;
var selrowdatepaid, selrowdsno, selrowtype, selrowamount, selrowlastdsno, selrowbank, selrowfun;

initundepTdyTbl();
initundepPrevTbl();

Swal.fire({
    title: 'Please Select Date of Transaction',
    html: '<input id="DateTransaction" readonly class="swal2-input">',
    confirmButtonText: 'Set',
    didOpen:function(e){
        $('#DateTransaction').datetimepicker({
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
        });
    },
    allowOutsideClick: false,
}).then((result) => {
    if (result.isConfirmed) {

        if($("#DateTransaction").val() == ""){
            location.reload();
        }

        var date = new Date($("#DateTransaction").val());
        SelectedDate = ((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear();

        Loadfund();
        loadUndepPrev();
    }
})

function initundepTdyTbl () {
    undeptodayTbl = $('#undeptodayTbl').DataTable({
    scrollY: '200px',
    scrollX: true,
    scrollCollapse: true,
    paging: false,
    bFilter:false,
    info:true,
    });
}

function initundepPrevTbl () {
    undepprevTbl = $('#undepprevTbl').DataTable({
    scrollY: '200px',
    scrollX: true,
    scrollCollapse: true,
    paging: false,
    bFilter:false,
    info:true,
    });
}

function loadUndepPrev (){
    $.ajax({
        url: "../../routes/cashier/depositslip.route.php",
        type: "POST",
        data: {action:"LoadUndepPrev", SelectedDate:SelectedDate},
        dataType: "JSON",
        beforeSend: function() {
            console.log('loading Undepprev...')
            // undepprevTbl.clear().draw();
            $("#undepprevList").empty();
            $("#undepprevList").append("<tr><td class='text-center' colspan='7'>Loading..</td></tr>");
        },
        success: function(response) {

            if ( $.fn.DataTable.isDataTable( '#undepprevTbl' ) ) {
                $('#undepprevTbl').DataTable().clear();
                $('#undepprevTbl').DataTable().destroy();
            }

            $.each(response.UNDEPPREVLIST,function(key,value){
                $("#undepprevList").append(`
                    <tr>
                        <td>${value["CDATE"]}</td>
                        <td>${value["PARTICULARS"]}</td>
                        <td>${value["PAYMENTTYPE"]}</td>
                        <td>${formatAmtVal(value["TOTALAMOUNT"])}</td>
                        <td>${value["STATUS"]}</td>
                        <td>${value["FUND"]}</td>
                        <td>${value["BANK"]}</td>
                        <td>${value["LASTDSNO"]}</td>
                    </tr>
                `);
            });

            if (response.UNDEPPREVLIST.length === 0) {
                $('#depositPrevAll').prop('disabled', true);
            } else {
                $('#depositPrevAll').prop('disabled', false);
            }

            initundepPrevTbl();
        },
        error: function(err) {
            console.log(err)
        }
    });
}

$('#undepprevTbl tbody').on('click', 'tr', function(e) {
    if(undepprevTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $('#depositPrev').prop('disabled', true);
            selrowdatepaid = "";
            selrowdsno = "";
            selrowtype = "";
            selrowamount = "";
            selrowfund = "";
            selrowbank = "";
            selrowlastdsno = "";
        } else {
            undepprevTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $('#depositPrev').prop('disabled', false);
            var rowData = undepprevTbl.row(this).data();
            selrowdatepaid = rowData[0];
            selrowdsno = rowData[1];
            selrowtype = rowData[2];
            selrowamount = rowData[3];
            selrowfund = rowData[5];
            selrowbank = rowData[6];
            selrowlastdsno = rowData[7];
        }
    }
});

function Loadfund(){
    $.ajax({
        url: "../../routes/cashier/depositslip.route.php",
        type: "POST",
        data: {action:"LoadFund", SelectedDate:SelectedDate},
        dataType: "JSON",
        beforeSend: function() {
            console.log('loading types...')
        },
        success: function(response) {
            $("#fund").empty().append(`<option value="" selected></option>`);
            $.each(response.FUND, function(key,value){
                $("#fund").append("<option value='"+value["FUND"]+"'>"+value["FUND"]+"</option>");
            })
        },
        error: function(err) {
            console.log(err)
        }
    });
}

function GetBanks(fund){
    $.ajax({
        url: "../../routes/cashier/depositslip.route.php",
        type: "POST",
        data: {action:"GetBanks", Fund:fund,SelectedDate:SelectedDate},
        dataType: "JSON",
        beforeSend: function() {
            console.log('loading banks...')
        },
        success: function(response) {
            const bankCount = response.BANKS.length;

            if (bankCount === 1){
                $("#bank").empty();
                $("#bank").prop("disabled", true);
                $.each(response.BANKS, function(key,value){
                    console.log(value["BANK"]);
                    $("#bank").append("<option value='"+value["BANK"]+"'>"+value["BANK"]+"</option>");
                })
            } else {
                $("#bank").empty().append(`<option value="" selected></option>`);
                $("#bank").prop("disabled", false);
                $.each(response.BANKS, function(key,value){
                    $("#bank").append("<option value='"+value["BANK"]+"'>"+value["BANK"]+"</option>");
                })
            }
        },
        error: function(err) {
            console.log(err)
        }
    });
}

function LoadDepositDetails(type){
    let fund = $("#fund").val();
    let bank = $("#bank").val();

    if (fund == "" || fund == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Select a Fund',
        })
        $("#type").val("")
        return;
    } else if (bank == "" || bank == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Bank',
        })
        $("#type").val("")
        return;
    } else {
        $.ajax({
            url: "../../routes/cashier/depositslip.route.php",
            type: "POST",
            data: {action:"LoadDepositDetails", Type:type,Fund:fund,Bank:bank,SelectedDate:SelectedDate},
            dataType: "JSON",
            beforeSend: function() {
                $("#undeptodayList").empty();
                $("#undeptodayList").append("<tr><td class='text-center' colspan='3'>Loading..</td></tr>");
            },
            success: function(response) {
                if ( $.fn.DataTable.isDataTable( '#undeptodayTbl' ) ) {
                    $('#undeptodayTbl').DataTable().clear();
                    $('#undeptodayTbl').DataTable().destroy();
                }

                $.each(response.UNDEPTODAY,function(key,value){
                    $("#undeptodayList").append(`
                        <tr>
                            <td>${value["ORNO"]}</td>
                            <td>${value["PAYEE"]}</td>
                            <td>${formatAmtVal(value["DROTHER"])}</td>
                        </tr>
                    `);
                });

                const total = response.UNDEPTODAYTOTAL.TOTAL;

                if (total > 0) {
                    $("#amount").val(formatAmtVal(total));
                    SetDSNO();
                } else {
                    $("#amount").val("");
                }


                initundepTdyTbl();
            },
            error: function(err) {
                console.log(err)
            }
        });
    }
}

function SetDSNO () {
    let fund = $("#fund").val();
    let bank = $("#bank").val();
    let type = $("#type").val();
    $.ajax({
            url: "../../routes/cashier/depositslip.route.php",
            type: "POST",
            data: {action:"SetDSNO", Type:type,Fund:fund,Bank:bank,SelectedDate:SelectedDate},
            dataType: "JSON",
            beforeSend: function() {
            },
            success: function(response) {
                if (response.STATUS == "YES") {
                } else {
                }
                $("#lastdsno").val(response.LASTDSNO);
                $("#depositSlipNo").val(response.DSNOENTRY);

            },
            error: function(err) {
                console.log(err)
            }
        });
}

 function DepositToday () {
    let lastdsno = $("#lastdsno").val();
    let fund = $("#fund").val();
    let bank = $("#bank").val();
    let type = $("#type").val();
    let depositslipno = $("#depositSlipNo").val();
    let amount = $("#amount").val();

    if (fund == "" || fund == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Select a Fund',
        })
        return;
    } else if (type == "" || type == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Select a Type',
        })
        return;
    } else if (bank == "" || bank == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Bank',
        })
        return;
    } else if (depositslipno == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Deposit Slip No',
        })
        return;
    } else if (amount <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Amount',
        })
        return;
    } else {
        Swal.fire({
            icon: 'question',
            title: 'Perform DS now?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Yes, proceed!',
            // allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/cashier/depositslip.route.php",
                    type: "POST",
                    data: {action:"SaveDepositSlip", lastdsno:lastdsno,type:type,fund:fund,bank:bank,depositslipno:depositslipno,amount:amount,selectedDate:SelectedDate},
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
                      text: "success",
                  });
                } else if (result.value.STATUS != 'SUCCESS') {
                    Swal.fire({
                        icon: "warning",
                        text: "warning",
                    });
                }
                loadUndepPrev();
            }
        });
    }

 }

 function DepositPrev() {
    if(undepprevTbl.rows().count() !== 0){
        Swal.fire({
          title: 'Are you sure?',
          icon: 'question',
          text: 'Save Deposit Slip?',
          showCancelButton: true,
          showLoaderOnConfirm: true,
          confirmButtonColor: '#435ebe',
          confirmButtonText: 'Yes, proceed!',
        //   allowOutsideClick: false,
          preConfirm: function() {
            return $.ajax({
              url: "../../routes/cashier/depositslip.route.php",
              type: "POST",
              data: {action:"DSUndepPrevious", setdatepaid:selrowdatepaid, dsentry:selrowdsno, type:selrowtype, amount:selrowamount, lastdsno:selrowlastdsno, bank:selrowbank, fund:selrowfund, SelectedDate:SelectedDate},
              dataType: 'JSON',
              beforeSend: function() {
                  console.log('Processing Request...')
              },
              success: function(response) {
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
                      text: "success",
                  });
                } else if (result.value.STATUS != 'SUCCESS') {
                    Swal.fire({
                        icon: "warning",
                        text: "warning",
                    });
                }
                loadUndepPrev();
                $('#depositPrev').prop("disabled", true);
                $('#depositPrevAll').prop("disabled", true);
            }
        });
    } else {
        Swal.fire({
            icon: "warning",
            title: 'No available previous undeposited transactions',
        })
        $('#depositPrev').prop("disabled", true);
        $('#depositPrevAll').prop("disabled", true);
    }
 }

 function DepositPrevAll() {
    //  Swal.fire({
    //      icon: "info",
    //      title: 'Working in progress...',
    //  })
    //  return false;
     
    if(undepprevTbl.rows().count() !== 0){


        Swal.fire({
          title: 'Are you sure?',
          icon: 'question',
          text: 'Set all previous UNDEPOSITED transactions as DEPOSITED?',
          showCancelButton: true,
          showLoaderOnConfirm: true,
          confirmButtonColor: '#435ebe',
          confirmButtonText: 'Yes, proceed!',
          allowOutsideClick: false,
          preConfirm: function() {
            var tableData = $('#undepprevTbl').DataTable().data().toArray();
            var tblData = JSON.stringify(tableData);
            
            return $.ajax({
              url: "../../routes/cashier/depositslip.route.php",
              type: "POST",
              data: {action: "DSUndepPreviousALL", dstype: "ALL", tblData:tblData, SelectedDate:SelectedDate},
              dataType: 'JSON',
              beforeSend: function() {
                  console.log('Processing Request...')
              },
              success: function(response) {
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
                      text: result.value.MESSAGE,
                  });
                } else if (result.value.STATUS != 'SUCCESS') {
                    Swal.fire({
                        icon: "warning",
                        text: result.value.MESSAGE,
                    });
                }
                loadUndepPrev();
                $('#depositPrev').prop("disabled", true);
                $('#depositPrevAll').prop("disabled", true);
            }
        });
    } else {
        Swal.fire({
            icon: "warning",
            title: 'No available previous undeposited transactions',
        })
        $('#depositPrev').prop("disabled", true);
        $('#depositPrevAll').prop("disabled", true);
    }
 }

 function resetform (){
    $('#lastdsno').val("");
    $('#fund').val("");
    $('#bank').val("");
    $('#type').val("");
    $('#depositSlipNo').val("");
    $('#amount').val("");
    undeptodayTbl.clear().draw();
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
    let cleanValue = input.value.replace(/[^0-9.,]/g, '');

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