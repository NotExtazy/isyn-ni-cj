var ttlprincipal, ttlinterest,ttlcbu,ttlpenalty,ttlmba,grandtotal;
var transactionTbl;

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

LoadInit();
LoadTable();

function LoadInit (){
    $.ajax({
        url: "../../routes/cashier/collectionreport.route.php",
        type: "POST",
        data: {action:"LoadPOs"},
        dataType: "JSON",
        beforeSend: function() {
        },
        success: function(response) {
            $("#encodedBy").empty();
            $("#encodedBy").append("<option value='ALL' selected>ALL</option>");
            $.each(response.ENCODERLIST, function(key,value){
                $("#encodedBy").append("<option value='"+value["PONICK"]+"'>"+value["PONICK"]+"</option>");
            })
        },
        error: function(err) {
            console.log(err)
        }
    });
}

function LoadTable (){
    transactionTbl = $('#transactionTbl').DataTable({
        searching:false,
        ordering:false,
        lengthChange:false,
        info:false,
        paging:false,
        scrollY: '500px',   
        scrollCollapse: true,
        responsive:true,
        columnDefs: [
            { targets: [ 1 ], className: 'dt-center' },
            { targets: [ 2,3,4,5,6,7 ], className: 'dt-right' },
            // { targets: [ 4,5,6,7,8,9,10,11,12 ], visible:false, searchable:false }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            ttlprincipal = api.column(2).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            ttlinterest = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            ttlcbu = api.column(4).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            ttlpenalty = api.column(5).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            ttlmba = api.column(6).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            grandtotal = api.column(7).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(1).footer()).html("Other Payments: " + formatAmtVal(grandtotal));
            $(api.column(2).footer()).html(formatAmtVal(ttlprincipal));
            $(api.column(3).footer()).html(formatAmtVal(ttlinterest));
            $(api.column(4).footer()).html(formatAmtVal(ttlcbu));
            $(api.column(5).footer()).html(formatAmtVal(ttlpenalty));
            $(api.column(6).footer()).html(formatAmtVal(ttlmba));
            $(api.column(7).footer()).html(formatAmtVal(grandtotal));
        },
    });
}

function Search () {
    let cdate =$("#transactionDate").val();
    let encodedBy =$("#encodedBy").val();
    let from = $("#from").val();
    let to = $("#to").val();

    if (from == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing From series.',
        })
        return;
    } else if (to == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing To series',
        })
        return;
    } else if (from > to) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Series. From cannot be greater than To.',
        })
        return;
    } else {
        $.ajax({
            url: "../../routes/cashier/collectionreport.route.php",
            type: "POST",
            data: {action:"SearchTransactions", cdate:cdate,encodedBy:encodedBy,from:from,to:to},
            dataType: 'JSON',
            beforeSend: function() {
                console.log('Processing Request...')
                $("#transactionList").empty();
                $("#transactionList").append("<tr><td class='text-center' colspan='8'>Loading..</td></tr>");
            },
            success: function(response) {
                if ( $.fn.DataTable.isDataTable( '#transactionTbl' ) ) {
                    $('#transactionTbl').DataTable().clear();
                    $('#transactionTbl').DataTable().destroy();
                }

                $("#transactionList").empty();
                $.each(response.LIST,function(key,value){
                    $("#transactionList").append(
                        `
                        <tr>
                            <td>${value["ORNO"]}</td>
                            <td>${value["PAYEE"]}</td>
                            <td>${formatAmtVal(value["PRINCIPAL"])}</td>
                            <td>${formatAmtVal(value["INTEREST"])}</td>
                            <td>${formatAmtVal(value["CBU"])}</td>
                            <td>${formatAmtVal(value["PENALTY"])}</td>
                            <td>${formatAmtVal(value["MBA"])}</td>
                            <td>${formatAmtVal(value["TOTAL"])}</td>
                        </tr>
                        `
                    );
                })

                LoadTable();
            },
            error: function(err) {
                console.log(err);
            }
        });
    }
}

function PrintReport() {
    if (transactionTbl.rows().count() === 0) {
        Swal.fire({
            icon: "warning",
            text: "No Data to Print.",
        });
        return false;
    } else {
        let cdate =$("#transactionDate").val();
        let encodedby =$("#encodedBy").val();
        let from = $("#from").val();
        let to = $("#to").val();

        var formData = new FormData();
        formData.append('action', 'ToSession');

        let transact = transactionTbl.rows().data().toArray();
        formData.append('transactTbl', JSON.stringify(transact));
        formData.append('cdate', cdate);
        formData.append('encodedby', encodedby);
        formData.append('from', from);
        formData.append('to', to);

        $.ajax({
            url: "../../routes/cashier/collectionreport.route.php",
            type:"POST",
            data:formData,
            processData: false,
            contentType: false,
            dataType:"JSON",
            success: function(response) {
                if (response.STATUS == "PRINT READY"){
                    window.open("../../routes/cashier/collectionreport.route.php?type=CollectionReport");
                }
            },
            error: function(err) {
            console.log(err);
            }          
        });
    }
}

function Reset() {
    $("#encodedBy").val("ALL");
    $("#from").val("");
    $("#to").val("");
    transactionTbl.clear().draw();
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