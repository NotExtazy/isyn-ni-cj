var debitcreditTbl, scheduleATbl, scheduleBTbl, scheduleCTbl, todayUndepTbl;
var TotalCollections = 0, TotalUndepPrev = 0, TotalDeposit = 0, TotalUndepDayEnd = 0, undepcheckTotal = 0, undepprevTotal = 0, undeptodayTotal = 0;
var selectedDate;
var undepAmounts = 0, undepDepositedAmounts = 0; 

loadDebitCreditTbl();
initializeSchedATbl();
initializeSchedBTbl();
initializeSchedCTbl();
initializeTodayUndepTbl();

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
        selectedDate = ((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear();

        Initialize();
    }
})

function Initialize() {
    // $.ajax({
    //     url: "../../routes/cashier/tellersproofsheet.route.php",
    //     type: "POST",
    //     data: {action:"LoadCashiers", cdate:selectedDate},
    //     dataType: "JSON",
    //     beforeSend: function() {
    //     },
    //     success: function(response) {
    //         $("#cashierName").empty();
    //         $("#cashierName").append("<option value='' selected disabled>Select Cashier</option>");
    //         $.each(response.CASHIERLIST, function(key,value){
    //             $("#cashierName").append("<option value='"+value["PREPAREDBY"]+"'>"+value["PREPAREDBY"]+"</option>");
    //         })

            
    //     },
    //     error: function(err) {
    //         console.log(err)
    //     }
    // });

    LoadTotals();
    LoadScheduleA();
    LoadScheduleB();
    LoadScheduleC();
    LoadTodayUndep();
}

function loadDebitCreditTbl() {
    debitcreditTbl = $('#debitcreditTbl').DataTable({
        scrollY: '320px',
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        searching:false,
        ordering:false,
        lengthChange:false,
        responsive:true,
    })
}

function initializeSchedATbl () {
    scheduleATbl = $('#scheduleATbl').DataTable({
        scrollY: '200px',
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        columnDefs: [
            { targets: [ 1,2,3,4 ], className: 'dt-right' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            // Total over all total collections
            TotalCollections = api.column(1).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Total over all undeposited previous day
            TotalUndepPrev = api.column(2).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Total over all deposited
            TotalDeposit = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Total over all undeposited day end
            TotalUndepDayEnd = api.column(4).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(1).footer()).html(formatAmtVal(TotalCollections));
            $(api.column(2).footer()).html(formatAmtVal(TotalUndepPrev));
            $(api.column(3).footer()).html(formatAmtVal(TotalDeposit));
            $(api.column(4).footer()).html(formatAmtVal(TotalUndepDayEnd));
        },
    });
}

function initializeSchedBTbl () {

    scheduleBTbl = $('#scheduleBTbl').DataTable({
        scrollY: '200px',
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        columnDefs: [
            { targets: [ 3 ], className: 'dt-right' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            // Total over all undeposited day end
            undepcheckTotal = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(3).footer()).html(formatAmtVal(undepcheckTotal));
        },
    });
}

function initializeSchedCTbl () {

    scheduleCTbl = $('#scheduleCTbl').DataTable({
        scrollY: '200px',
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        columnDefs: [
            { targets: [ 3,4,5 ], className: 'dt-right' },
            { targets: [ 4,5], visible:false, searchable:false }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            undepAmounts = api.column(4).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            undepDepositedAmounts = api.column(5).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(2).footer()).html(formatAmtVal(undepAmounts));
            $(api.column(3).footer()).html(formatAmtVal(undepDepositedAmounts));
        },
    });
}

function initializeTodayUndepTbl () {

    todayUndepTbl = $('#todayUndepTbl').DataTable({
        scrollY: '200px',
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        columnDefs: [
            { targets: [ 3 ], className: 'dt-right' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            // Total over all undeposited day end
            undeptodayTotal = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(3).footer()).html(formatAmtVal(undeptodayTotal));
        },
    });
}

function LoadTotals() {
    $.ajax({
        url: "../../routes/cashier/tellersproofsheet.route.php",
        type: "POST",
        data: {action:"LoadTotals", cdate:selectedDate},
        dataType: "JSON",
        beforeSend: function() {
        },
        success: function(response) {
            $("#totalChecks").val(formatAmtVal(response.CHECKTOTALS, 2));
            $("#grandTotal").val(formatAmtVal(response.CHECKTOTALS, 2));
            let systemTotal = response.SYSTEMTOTALS + response.PREVUNDEPTOTALS;
            $("#systemTotal").val(formatAmtVal(systemTotal, 2));
        },
        error: function(err) {
            console.log(err)
        }
    });
}
    
function LoadScheduleA() {
    $.ajax({
        url: "../../routes/cashier/tellersproofsheet.route.php",
        type:"POST",
        data:{action: "LoadScheduleA", cdate:selectedDate},
        dataType:"JSON",
        beforeSend:function(e){
        $("#scheduleAList").empty();
        $("#scheduleAList").append("<tr><td class='text-center' colspan='4'>Loading..</td></tr>");
        },
        success:function(response){
            if ( $.fn.DataTable.isDataTable( '#scheduleATbl' ) ) {
                $('#scheduleATbl').DataTable().clear();
                $('#scheduleATbl').DataTable().destroy();
            }

            $("#scheduleAList").empty();
            $.each(response.FUNDS,function(key,value){
                $("#scheduleAList").append("<tr><td>" + value["FUND"] + "</td></tr>");
            })

            $.each(response.SCHEDATOTALCOLLECTIONDATA, function (key, value) {
                $("#scheduleAList tr:eq(" + key + ")").append("<td>" + formatAmtVal(value, 2) + "</td>");
            });

            $.each(response.SCHEDAUNDEPCOLLECTIONPREV, function (key, value) {
                $("#scheduleAList tr:eq(" + key + ")").append("<td>" + formatAmtVal(value, 2) + "</td>");
            });

            $.each(response.SCHEDADEPOSITED, function (key, value) {
                $("#scheduleAList tr:eq(" + key + ")").append("<td>" + formatAmtVal(value, 2) + "</td>");
            });

            $.each(response.SCHEDAUNDEPDAY, function (key, value) {
                $("#scheduleAList tr:eq(" + key + ")").append("<td>" + formatAmtVal(value, 2) + "</td>");
            });

            initializeSchedATbl();
            LoadDebitCredit();
        }
    })
};

function LoadScheduleB() {
    $.ajax({
        url: "../../routes/cashier/tellersproofsheet.route.php",
        type:"POST",
        data:{action: "LoadScheduleB", cdate:selectedDate},
        dataType:"JSON",
        beforeSend:function(e){
        $("#scheduleBList").empty();
        $("#scheduleBList").append("<tr><td class='text-center' colspan='4'>Loading..</td></tr>");
        },
        success:function(response){
            if ( $.fn.DataTable.isDataTable( '#scheduleBTbl' ) ) {
                $('#scheduleBTbl').DataTable().clear();
                $('#scheduleBTbl').DataTable().destroy();
            }
            
            $("#scheduleBList").empty();
            $.each(response.SCHEDBDATA,function(key,value){
                $("#scheduleBList").append(`
                    <tr>
                        <td>${value["CheckNo"]}</td>
                        <td>${value["BankName"]}</td>
                        <td>${value["BankBranch"]}</td>
                        <td>${formatAmtVal(value["DrOther"], 2)}</td>
                    </tr>
                `);
            })

            initializeSchedBTbl();
        }
    })
};

function LoadScheduleC() {
    $.ajax({
        url: "../../routes/cashier/tellersproofsheet.route.php",
        type:"POST",
        data:{action: "LoadScheduleC", cdate:selectedDate},
        dataType:"JSON",
        beforeSend:function(e){
        $("#scheduleCList").empty();
        $("#scheduleCList").append("<tr><td class='text-center' colspan='4'>Loading..</td></tr>");
        },
        success:function(response){
            if ( $.fn.DataTable.isDataTable( '#scheduleCTbl' ) ) {
                $('#scheduleCTbl').DataTable().clear();
                $('#scheduleCTbl').DataTable().destroy();
            }

            $("#scheduleCList").empty();
            $.each(response.SCHEDCDATA,function(key,value){
                let depoamt = 0;
                let undepamt = 0;
                
                if (value["Status"] == "DEPOSITED") {
                    depoamt = value["DrOther"];
                } else {
                    undepamt = value["DrOther"];
                }

                $("#scheduleCList").append(`
                    <tr>
                        <td>${value["CDate"]}</td>
                        <td>${value["Explanation"]}</td>
                        <td>${value["Status"]}</td>
                        <td>${formatAmtVal(value["DrOther"],2)}</td>
                        <td>${formatAmtVal(undepamt,2)}</td>
                        <td>${formatAmtVal(depoamt,2)}</td>
                    </tr>
                `);
            })

            initializeSchedCTbl();
        }
    })
};

function LoadTodayUndep() {
    $.ajax({
        url: "../../routes/cashier/tellersproofsheet.route.php",
        type:"POST",
        data:{action: "LoadUndepToday", cdate:selectedDate},
        dataType:"JSON",
        beforeSend:function(e){
        $("#todayUndepList").empty();
        $("#todayUndepList").append("<tr><td class='text-center' colspan='5'>Loading..</td></tr>");
        },
        success:function(response){
            if ( $.fn.DataTable.isDataTable( '#todayUndepTbl' ) ) {
                $('#todayUndepTbl').DataTable().clear();
                $('#todayUndepTbl').DataTable().destroy();
            }

            $("#todayUndepList").empty();

            $.each(response.UNDEPTODAYDATA,function(key,value){
                $("#todayUndepList").append(`
                    <tr>
                        <td>${value["Fund"]}</td>
                        <td>${value["Bank"]}</td>
                        <td>${value["PaymentType"]}</td>
                        <td>${formatAmtVal(value["DrOther"], 2)}</td>
                    </tr>
                `);
            })

            initializeTodayUndepTbl();
        }
    })  
};

function LoadDebitCredit() {
    debitcreditTbl.clear().draw();

    debitcreditTbl.row.add(["DEBITS",""]).draw(false);
    debitcreditTbl.row.add(["PREVIOUS UNDEPOSITED (See Schedule C)",formatAmtVal(TotalUndepPrev)]).draw(false);
    debitcreditTbl.row.add(["COLLECTIONS (See Schedule A below)",""]).draw(false);
    debitcreditTbl.row.add(["  Total DEBITS", formatAmtVal(TotalCollections)]).draw(false);
    debitcreditTbl.row.add(["CREDITS",""]).draw(false);
    debitcreditTbl.row.add(["DEPOSITED COLLECTIONS (See Schedule A)", formatAmtVal(TotalDeposit)]).draw(false);
    debitcreditTbl.row.add(["CASH ON HAND Undeposited",""]).draw(false);
    debitcreditTbl.row.add(["  Collection as of End day", formatAmtVal(TotalUndepDayEnd)]).draw(false);

    var debits = debitcreditTbl.row(0).node();
    $(debits).addClass('dc-highlight');
    var credits = debitcreditTbl.row(4).node();
    $(credits).addClass('dc-highlight');
};

function Confirm () {
    let grandTotal= $("#grandTotal").val().replace(/,/g, '');
    let systemTotal= $("#systemTotal").val().replace(/,/g, '');

    if (grandTotal != systemTotal) {
        Swal.fire({
            icon: 'warning',
            title: 'Grand Total not tally with System Total. Please Re-Count.',
        })
        return;
    } else {
        var form = $('#billscoinsForm')[0];
        var formData = new FormData(form);
        formData.append('action','ToSession');

        // let debitCredit = debitcreditTbl.rows().data().toArray();
        let schedA = scheduleATbl.rows().data().toArray();
        let schedB = scheduleBTbl.rows().data().toArray();
        let schedC = scheduleCTbl.rows().data().toArray();
        let todayUndep = todayUndepTbl.rows().data().toArray();

        // formData.append('debitCreditTbl', JSON.stringify(debitCredit));
        formData.append('TotalUndepPrev', JSON.stringify(TotalUndepPrev));
        formData.append('TotalCollections', JSON.stringify(TotalCollections));
        formData.append('TotalDeposit', JSON.stringify(TotalDeposit));
        formData.append('TotalUndepDayEnd', JSON.stringify(TotalUndepDayEnd));

        formData.append('scheduleA', JSON.stringify(schedA));
        formData.append('scheduleB', JSON.stringify(schedB));
        formData.append('scheduleC', JSON.stringify(schedC));
        formData.append('todayUndep', JSON.stringify(todayUndep));
        formData.append('todayUndep', JSON.stringify(todayUndep));
        
        formData.append('SelectedDate',selectedDate);

        Swal.fire({
            icon: 'info',
            title: 'Finalize transaction now?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Proceed!',
            // allowOutsideClick: true,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/cashier/tellersproofsheet.route.php",
                    type:"POST",
                    data:formData,
                    processData:false,
                    cache:false,
                    contentType:false,
                    dataType:"JSON",
                    success:function(response){
                        if (response.STATUS == "PRINT_READY"){
                            window.open("../../routes/cashier/tellersproofsheet.route.php?type=TellerProofSheetReport");
                        }
                    }
                })
            },
        });
    }
}

function Reset() {
    // $("#encodedBy").val("ALL");
    // $("#from").val("");
    // $("#to").val("");
    // transactionTbl.clear().draw();
}

function formatInput(input) {
    let cleanValue = input.value.replace(/[^0-9.,]/g, '');
    cleanValue = cleanValue.replace(/,/g, '');
    if (cleanValue !== '') {
        input.value = cleanValue;
    } else {
        input.value = '';
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