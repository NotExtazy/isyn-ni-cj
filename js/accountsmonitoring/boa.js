var SummaryTable, TransactionsTable1, TransactionsTable2, SelectedTransaction, SelectedBooks, TransactTbl1Data = "", ORType = "";

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

    SummaryTable = $('#SummaryTable').DataTable({
        scrollY: '200px',
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        columnDefs: [
            { targets: [ 1,2 ], className: 'text-right' },
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            // Total over all debit pages
            TotalDebit = api.column(1).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Total over all credit pages
            TotalCredit = api.column(2).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(1).footer()).html(formatAmtVal(TotalDebit).toLocaleString("en-US"));
            $(api.column(2).footer()).html(formatAmtVal(TotalCredit).toLocaleString("en-US"));
        },
    });

    TransactionsTable1 = $('#TransactionsTable1').DataTable({
        scrollY: '200px',
        scrollCollapse: true,
        scrollX: true,
        paging: false,
        bFilter:false,
        info:false,
        sorting:false,
        columnDefs: [
            { targets: [ 3,4 ], visible: false, searchable: false },
        ],
    });

    TransactionsTable2 = $('#TransactionsTable2').DataTable({
        scrollY: '200px',
        scrollCollapse: true,
        paging: false,
        bFilter:false,
        info:false,
        sorting:false,
        columnDefs: [
            { targets: [ 1 ], className:'text-center' },
            { targets: [ 1,2,3 ], className:'text-right' },
        ]
    });



    $.ajax({
        url:"../../routes/accountsmonitoring/boa.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
            console.log("Initializing.....");
        },
        success:function(response){
            $("#bookType").empty().append("<option value='' selected disabled> Select Book</option>");
            $.each(response.BOOKTYPES,function(key,value){
                $("#bookType").append(`<option value="${value["BookType"]}">${value["BookType"]}</option>`);
            });

            $("#fund").empty().append("<option value='' selected disabled> Select Fund</option>");
            $.each(response.FUNDS,function(key,value){
                $("#fund").append(`<option value="${value["Fund"]}">${value["Fund"]}</option>`);
            });
        }, 
    })
}

function LoadDataRows(BookType) {
    $.ajax({
        url:"../../routes/accountsmonitoring/boa.route.php",
        type:"POST",
        data:{action:"LoadDataRows", bookType:BookType},
        dataType:"JSON",
        beforeSend:function(){
            console.log("Initializing.....");
        },
        success:function(response){
            // $("#bookType").empty().append("<option value='' selected disabled> Select Book</option>");
            // $.each(response.BOOKTYPES,function(key,value){
            //     $("#bookType").append(`<option value="${value["BookType"]}">${value["BookType"]}</option>`);
            // });
        }, 
    })
}

function SearchBooksBtn(){
    var fromDate = $("#fromDate").val();
    var toDate = $("#toDate").val();
    var bookType = $("#bookType").val();
    var fund = $("#fund").val();

    $("#RePrintBtn").attr("disabled",false);

    if (fromDate == "" && toDate == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Please complete From and To Date.',
        });
        return;
    }

    if (bookType == "" || bookType == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Please select a Book Type.',
        });
        return;
    }

    if (fund == "" || fund == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Please select a Fund.',
        });
        return;
    }

    $.ajax({
        url:"../../routes/accountsmonitoring/boa.route.php",
        type:"POST",
        data:{action:"SearchBooks",fromDate:fromDate,toDate:toDate,bookType:bookType,fund:fund},
        dataType:"JSON",
        beforeSend:function(){
            SummaryTable.clear().draw();
            //TransactionsTable1.clear().draw();
            TransactionsTable2.clear().draw();
            SelectedTransaction = "";
            SelectedBooks = "";

            // $("#SummaryList").empty();
            // $("#SummaryList").append("<tr><td colspan='3'>Loading...</td></tr>");
			
			if ( $.fn.DataTable.isDataTable( '#TransactionsTable1' ) ) {
                $('#TransactionsTable1').DataTable().clear();
                $('#TransactionsTable1').DataTable().destroy();
            }
        },
        success:function(response){
            SelectedBooks = bookType;

            $.each(response.GroupData,function(key,value){
                SummaryTable.row.add([value["AcctTitle"].trim(),formatAmtVal(value["Debit"]),formatAmtVal(value["Credit"])]).draw(false);
            })

            $.each(response.QueryData,function(key,value){
                let Reference = (bookType == "GJ") ? "JVNo" : (bookType == "CDB" ? "CVNo" : "ORNo");
                // let subref = (booktype == "GJ") ? "-" : (booktype == "CDB" ? value["checkno"] : value["ORType"]);
                let subref = "-";
				
                $("#TransactionsTable1 tbody").append("<tr><td>"+value["CDate"]+"</td><td>"+value[Reference]+"</td><td>"+value["Explanation"]+"</td><td>"+value["Fund"]+"</td><td>"+subref+"</td></tr>");
            })
			
			TransactionsTable1 = $('#TransactionsTable1').DataTable({
				scrollY: '200px',
				scrollCollapse: true,
				scrollX: true,
				paging: false,
				bFilter:false,
				info:false,
				sorting:false,
				columnDefs: [
					// { targets: [ 3,4 ], visible: false, searchable: false },
				],
			});

            if (response.STATUS != "SUCCESS"){
                Swal.fire({
                    icon: 'error',
                    title: 'No data found. Please Try Another Parameters.',
                })
            }
        }
    })
}

$("#TransactionsTable1 tbody").on("click","tr",function(){
    SelectedTransaction = this;
    $("#TransactionsTable1 tbody tr").removeClass("bg-info text-white");
    $(this).addClass("bg-info text-white");

    TransactTbl1Data = TransactionsTable1.row(this).data();

    let CDateVal = TransactTbl1Data[0];
    let ReferenceNoVal = TransactTbl1Data[1];
    let FundVal = TransactTbl1Data[3];
        // ORType = TransactTbl1Data[4];

    $.ajax({
        url:"../../routes/accountsmonitoring/boa.route.php",
        type:"POST",
        data:{action:"SearchTransaction",SelectedBooks:SelectedBooks,CDate:CDateVal,ReferenceNo:ReferenceNoVal,Fund:FundVal},
        dataType:"JSON",
        beforeSend:function(){
            TransactionsTable2.clear().draw();
        },
        success:function(response){            
            $.each(response.DataResult,function(key,value){
                TransactionsTable2.row.add([
                    "<pre>" + value["AcctTitle"] + "</pre>",
                    "<pre>" + (value["SLDrCr"][0] === "-" ? "(" + formatAmtVal(value["SLDrCr"]) + ")" : formatAmtVal(value["SLDrCr"])) + "</pre>",
                    "<pre>" + formatAmtVal(value["Debit"]) + "</pre>",
                    "<pre>" + formatAmtVal(value["Credit"]) + "</pre>"
                ]).draw(false);
            })
        }
    })
});

function RePrint(){
    if(SelectedTransaction != ""){
        if (SelectedBooks == "GJ"){
            window.open("../../routes/accountsmonitoring/journalvoucher.route.php?type=JVReport");
        } else if (SelectedBooks == "CRB"){
            if (ORType == "OR") {
                // window.open("routes/crb.route.php?type=crb");
            } else if (ORType == "AR") {
                // window.open("routes/crb.route.php?type=ar");
            } else {
                Swal.fire({
                    icon:"warning",
                    text:"Please select a different transaction"
                })
            }
        } else if (SelectedBooks == "CDB"){
            window.open("../../routes/accountsmonitoring/otherdisb.route.php?type=CDReprintReport");
        }
    } else {
        Swal.fire({
            icon:"warning",
            text:"Please Select Transaction"
        })
    }
}

function PrintDetailedPDF(){
    let fromDate = $("#fromDate").val();
    let toDate = $("#toDate").val();
    let bookType = $("#bookType").val();
    let fund = $("#fund").val();
    let Selbank = "";

    $.ajax({
        url:"../../routes/accountsmonitoring/boa.route.php",
        type:"POST",
        data:{action:"PrintDetailedBOA",fromDate:fromDate,toDate:toDate,bookType:bookType,fund:fund,bank:Selbank},
        dataType:"JSON",
        success:function(response){
            if(response.READYPRINT == "YES"){
                window.open("../../routes/accountsmonitoring/boa.route.php?REPORTTYPE=DETAILEDPDF&BOOKTYPE="+response.TYPE);
            }
        }
    })
}

function PrintDetailedEXCEL(){
    let fromDate = $("#fromDate").val();
    let toDate = $("#toDate").val();
    let bookType = $("#bookType").val();
    let fund = $("#fund").val();
    let Selbank = "";

    $.ajax({
        url:"../../routes/accountsmonitoring/boa.route.php",
        type:"POST",
        data:{action:"PrintDetailedBOA",fromDate:fromDate,toDate:toDate,bookType:bookType,fund:fund,bank:Selbank},
        dataType:"JSON",
        success:function(response){
            if(response.READYPRINT == "YES"){
                window.open("../../routes/accountsmonitoring/boa.route.php?REPORTTYPE=DETAILEDEXCEL&BOOKTYPE="+response.TYPE);
            }
        }
    })
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