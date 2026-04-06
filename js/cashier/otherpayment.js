var TransactStatus = "Allowed", BatchNo = "No", NatureAdjustment, SLTotalAmount = 0, MinusSLAmt = 0, SLTable, EntryTable, SelectedEntry,SelectedSLEntry, SelectedSavedBatchNo, HaveSL,  TotalDebit = 0, TotalCredit = 0;

$("#payeeSel").select2({
    width: '100%',
});

$("#SLName").select2({
    width: '100%',
});

SetTransactionDate();
LoadPage();

function SetTransactionDate(){
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
    $('#checkDate').datetimepicker(options);

    // Swal.fire({
    //     title: 'Please Select Transaction Date.',
    //     html: '<input id="DateTransaction" readonly class="swal2-input">',
    //     confirmButtonText: 'Set',
    //     showLoaderOnConfirm: false,
    //     didOpen:function(e){
    //         $('#DateTransaction').datetimepicker(options);
    //     },
    //     allowOutsideClick: false,
    // }).then((result) => {
    //     if (result.isConfirmed) {

    //         if($("#DateTransaction").val() == ""){
    //             location.reload();
    //         }

    //         var date = new Date($("#DateTransaction").val());

    //         $("#transactionDate").val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
    //     }
    // })
}

function LoadPage(){
    $("#SubTypeDiv").hide();

    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"LoadPage"},
        dataType:"JSON",
        beforeSend:function(){
            
        },
        success:function(response){

            $.each(response.ORSERIES,function(key,value){
                $("#orseries").append(`<option value="${value["Name"]}">${value["Name"]}</option>`);
            });

            $("#clientType").empty().append("<option value='' selected disabled> Select Client Type</option> <option value'OTHER'> OTHER</option>");
            $.each(response.CLIENTTYPE,function(key,value){
                $("#clientType").append(`<option value="${value["Type"]}">${value["Type"]}</option>`);
            });

            $.each(response.FUNDS,function(key,value){
                $("#fund").append(`<option value="${value["Fund"]}">${value["Fund"]}</option>`);
            });

            $.each(response.ACCOUNTCODES,function(key,value){
                $("#AccountCodesTbody").append(`
                    <tr>
                        <td>${value["acctcodes"]}</td>
                        <td>${value["acctitles"]}</td>
                        <td>${value["normalbal"]}</td>
                        <td>${value["fstype"]}</td>
                        <td>${value["sl"]}</td>
                        <td>${value["slname"]}</td>
                    </tr>
                `);
            });

            $('#AccountCodesTable').DataTable({
                scrollY: '15vh',
                scrollX: true,
                scrollCollapse: true,
                ordering: false,
                paging: false,
                responsive:true,
                dom: 'frtp'
            });

            EntryTable = $('#EntryTable').DataTable({
                searching:false,
                ordering:false,
                lengthChange:false,
                info:false,
                paging:false,
                scrollY: '500px',   
                scrollCollapse: true,
                responsive:true,
                columnDefs: [
                    { targets: [ 2,3 ], className: 'dt-right' },
                    // { targets: [ 4,5,6,7,8,9,10,11,12 ], visible:false, searchable:false }
                ],
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
        
                    // Remove the formatting to get integer data for summation
                    var intVal = function (i) {
                        return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                    };
        
                    // Total over all debit pages
                    TotalDebit = api.column(2).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                    // Total over all credit pages
                    TotalCredit = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
        
                    // Update footer
                    $(api.column(2).footer()).html(formatAmtVal(TotalDebit));
                    $(api.column(3).footer()).html(formatAmtVal(TotalCredit));
                },
            });

            SLTable = $('#SLTable').DataTable({
                searching: false,
                ordering: false,
                lengthChange: false,
                info: false,
                paging: false,
                scrollY: '500px',
                scrollCollapse: true,
                responsive:true,
                columnDefs: [
                    // { targets: [ 3,4,5,6,7 ], visible:false, searchable:false }
                ],
            });
        }, 
    })
}

function LoadClientName(clientType){
    if (clientType == "OTHER"){
        $('#payeeSelDiv').hide();
        $('#payeeSel').val("");
        $('#payeeTxtDiv').show();
        $('#payeeTxt').val("");
        $('#payeeTINDiv').show();
        $('#payeeTIN').val("");
        $('#payeeAddressDiv').show();
        $('#payeeAddress').val("");
    } else {
        $('#payeeSelDiv').show();
        $('#payeeSel').val("");
        $('#payeeTxtDiv').hide();
        $('#payeeTxt').val("");
        $('#payeeTINDiv').hide();
        $('#payeeTIN').val("None");
        $('#payeeAddressDiv').hide();
        $('#payeeAddress').val("None");

        $.ajax({
            url:"../../routes/cashier/otherpayment.route.php",
            type:"POST",
            data:{action:"LoadClientName", clientType:clientType},
            dataType:"JSON",
            beforeSend:function(){
            },
            success:function(response){
                $("#payeeSel").empty().append("<option value='' selected disabled> Select Name</option>");
                $.each(response.CLIENTNAMELIST, function(key,value){
                    $("#payeeSel").append("<option value='"+value["Name"]+"'>"+value["Name"]+"</option>");
                })
            }, 
        })
    }    
}

function LoadClientNameInfo(clientName){
    $('#payeeTIN').val("None");
    $('#payeeAddress').val("None");
    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"LoadClientNameInfo", clientName:clientName},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            let info = response.CLIENTINFO;
            $('#payeeTIN').val(info["tin_no"]);
            $('#payeeAddress').val(info["FullAddress"]);
        },
    })
}

function GetBank (fund) {
    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"GetBank", fund:fund},
        dataType:"JSON",
        success:function(response){
            $("#bank").empty().append("<option value='' selected disabled> Select Bank</option>");
            $.each(response.BANKLIST,function(key,value){
                $("#bank").append(`<option value="${value["Bank"]}">${value["Bank"]}</option>`);
            });
        }, 
    })
}

function toggleCheckDetails (payType) {
    if (payType === "CHECK") {
        $("#checkDate").prop("disabled", false)
        $("#checkNo").prop("disabled", false).val("")
        $("#bankName").prop("disabled", false).val("")
        $("#bankBranch").prop("disabled", false).val("")
    } else {
        $("#checkDate").prop("disabled", true)
        $("#checkNo").prop("disabled", true).val("")
        $("#bankName").prop("disabled", true).val("")
        $("#bankBranch").prop("disabled", true).val("")
    }
}

function paymentEntryAmount(input) {
    // Get the value from the input field and remove invalid characters
    let cleanValue = input.value.replace(/[^0-9.,]/g, '');
    let data2 = EntryTable.rows().data();

    // Remove commas for numeric processing
    cleanValue = cleanValue.replace(/,/g, '');

    if (cleanValue !== '') {
        // Parse the cleaned value to a float and ensure two decimal places
        let formattedValue = parseFloat(cleanValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        // Set the formatted value back to the input field
        input.value = formattedValue;
        
        // ✅ Collect indexes of rows to remove
        let rowsToRemove = [];
        data2.each(function (value, index) {
            if (value[4] == "11120") {
                // console.log(value[0] + " - " + value[4] + " - " + value[2]);
                rowsToRemove.push(index);
            }
        });

        // ✅ Remove rows in reverse order (to avoid index shift)
        rowsToRemove.reverse().forEach(function (idx) {
            EntryTable.row(idx).remove();
        });
        EntryTable.draw(false);

        if (cleanValue > 0) {
            EntryTable.row.add(["CASH ON HAND","0.00",formattedValue,"0.00","11120","DEBIT","NO","-","-","-","-","11120","GL"]).draw(false);
        }
    } else {
        input.value = '0.00'; // If empty or invalid, set input to empty
    }
}

$('#AccountCodesTable tbody').on('click', 'tr',function(){
    if (TransactStatus != "Saved"){
        $("#AccountCodesTable tbody tr").removeClass("selected");
        $(this).addClass("selected");
        
        var data = $('#AccountCodesTable').DataTable().row(this).data();
    
        $("#AcctNo").val(data[0]);
        $("#AcctTitle").val(data[1]);
        // $("#EntrySide").val(data[2]);
        HaveSL = data[4];
        $("#GlSlname").val(data[5]);    
    
        if(data[4] == "YES"){
            $("#SLType").attr("disabled",false).val("");
            $("#SLName").attr("disabled",false).val(null).empty().trigger('change');
            $("#SLAmount").attr("disabled",false).val("");
            $("#AddSLButton").attr("disabled",false);
            $("#RemoveSLButton").attr("disabled",true);
            $("#SubTypeDiv").hide();
            $("#SubType").val("");
            LoadSLTypes(data[0]);
        }else{
            $("#SLType").attr("disabled",true).val("");
            $("#SLName").attr("disabled",true).val(null).empty().trigger('change');
            $("#SLAmount").attr("disabled",true).val("");
            $("#AddSLButton").attr("disabled",true);
            $("#RemoveSLButton").attr("disabled",true);
            $("#SubTypeDiv").hide();
            $("#SubType").val("");
        }
    }
});

function LoadSLTypes(acctno){
    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"LoadSLTypes",acctno:acctno},
        dataType:"JSON",
        beforeSend:function(){
            
        },
        success:function(response){
            $("#SLType").empty().append(`<option value=""></option>`);
            $.each(response.SLTYPES,function(key,value){
                $("#SLType").append(`<option value="${value["slname"]}">${value["slname"]}</option>`);
            });

        }, 
    })
}

function LoadSL(sltype){
    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"LoadSL",sltype:sltype},
        dataType:"JSON",
        beforeSend:function(){
            $("#SubTypeDiv").hide();
        },
        success:function(response){
            $("#SLName").empty().append(`<option value=""></option>`);
            $.each(response.SLNAMES,function(key,value){
                if(sltype == "CLIENT"){
                    $("#SubTypeDiv").show();
                    $("#SLName").append(`<option value="${value["FULLNAME"]}|${value["CLIENTNO"]}|${value["LOANID"]}|${value["PROGRAM"]}|${value["PRODUCT"]}">${value[response.FIELD1]} | ${value["PROGRAM"]} 
                        | ${value["DATERELEASE"]}</option>`);
                }else{
                    $("#SLName").append(`<option value="${value[response.FIELD1]}|${value[response.FIELD2]}|-|-|-">${value[response.FIELD1]}</option>`);
                }
            });          
        }, 
    })
}

function LoadSLFromSubtype(subtype){
    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"LoadSLFromSubtype",subtype:subtype},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#SLName").empty().append(`<option value=""></option>`);
            $.each(response.SLNAMES,function(key,value){
                $("#SLName").append(`<option value="${value["FULLNAME"]}|${value["CLIENTNO"]}|${value["LOANID"]}|${value["PROGRAM"]}|${value["PRODUCT"]}">${value["FULLNAME"]} | ${value["PROGRAM"]} 
                    | ${value["DATERELEASE"]}</option>`);
            });
        }, 
    })
}

function AddSLEntry(){
    let gltotalamt = parseFloat($("#GLTotalAmount").val().replace(/,/g, ''));
    let sltotalamt = parseFloat($("#SLTotalAmount").val().replace(/,/g, ''));
    let glno = $("#AcctNo").val();
    let slname = $("#SLName").val();
    if (slname == "") {
        slname = slname;
    } else {
        slname = $("#SLName").val().split("|");
    }
    let fullname = slname[0];
    let clientno = slname[1];
    let loanid = slname[2];
    let program = slname[3];
    let product = slname[4];

    let sltype = $("#SLType").val();
    let slamount = parseFloat($("#SLAmount").val().replace(/,/g, ''));

    if (gltotalamt == "" || isNaN(gltotalamt) || gltotalamt == 0) {
        Swal.fire({
            icon:"warning",
            title:"Please Input GL Amount",
            text: "GL Details",
        })
        return;
    } else if (sltype == "") {
        Swal.fire({
            icon:"warning",
            title:"Select an SL Type",
            text:"SL Details",
        })
        return;
    } else if (slname == "") {
        Swal.fire({
            icon:"warning",
            title:"Select an SL Name",
            text:"SL Details",
        })
        return;
    } else if (slamount == "" || isNaN(slamount) || slamount == 0) {
        Swal.fire({
            icon:"warning",
            title:"Please Input SL Amount",
            text: "SL Details",
        })
        return;
    }
    
    if (slamount > gltotalamt) {
        Swal.fire({
            icon:"warning",
            title:"SL Amount should not exceed GL Total Amount",
            text: "SL Details",
        })
        return;
    }
    
    if (gltotalamt == sltotalamt) {
        Swal.fire({
            icon:"warning",
            title:"GL and  SL Total Amount are already equal.",
            text: "GL Details",
        })
        return;
    }

    SLTable.row.add([clientno,fullname,formatAmtVal(slamount),sltype,loanid,program,product,glno]).draw(false);

    $("#SLName").val(null).empty().trigger('change');
    $("#SLType").val("");   
    $("#SLAmount").val("");
    $("#SubTypeDiv").hide();
    $("#SubType").val("");

    SLTotalAmount = sltotalamt + slamount;

    $("#SLTotalAmount").val(formatAmtVal(SLTotalAmount));
}

$('#SLTable tbody').on('click', 'tr',function(){
    if (TransactStatus != "Saved"){
        $("#SLTable tbody tr").removeClass("selected");
        $(this).addClass("selected");
        SelectedSLEntry = this;
    
        var data = $('#SLTable').DataTable().row(this).data();
        MinusSLAmt = data[2];
    
        $("#RemoveSLButton").attr("disabled",false);
    }
});

function RemoveSLEntry(){
    if(SelectedSLEntry != ""){
        SLTotalAmount -= parseFloat(MinusSLAmt.replace(/,/g, ''));
        $("#SLTotalAmount").val(formatAmtVal(SLTotalAmount));
        SLTable.row(SelectedSLEntry).remove().draw(false);
        SelectedSLEntry = "";
        MinusSLAmt = 0;
        $("#RemoveSLButton").attr("disabled",true);
    } 
}

function AddGLEntry(){
    let entryside = $("#EntrySide").val();
    let accountno = $("#AcctNo").val();
    let glslname = $("#GlSlname").val();
        glslname = (glslname == "") ? "-" : glslname;
    let accounttitle = $("#AcctTitle").val();
    let gltotalamt = $("#GLTotalAmount").val().replace(/,/g, '');
    let sltotalamt = $("#SLTotalAmount").val().replace(/,/g, '');

    let dramt = entryside == "DEBIT" ? gltotalamt : "0";
    let cramt = entryside == "CREDIT" ? gltotalamt : "0";

    let sltype = $("#SLType").val();
    let slname =  $("#SLName").val();
    let slamount = $("#SLAmount").val();

    if (entryside == ""){
        Swal.fire({
            icon:"warning",
            title:"Please SELECT an Entry Side",
            text: "GL Details",
        })
        return;
    } else if (accountno == ""){
        Swal.fire({
            icon:"warning",
            title:"Please Select an Account",
            text: "GL Details",
        })
        return;
    } else if (accounttitle == ""){
        Swal.fire({
            icon:"warning",
            title:"Please Select an Account",
            text: "GL Details",
        })
        return;
    } else if (gltotalamt == "" || isNaN(gltotalamt) || gltotalamt == 0){
        Swal.fire({
            icon:"warning",
            title:"Please Input GL Amount",
            text: "GL Details",
        })
        return;
    }

    if (HaveSL == "YES") {
        if (SLTable.rows().count() == 0) {
            Swal.fire({
                icon: "warning",
                title: "Missing SL Entries",
                text: "SL Details",
            })
            return;
        }
        
        if (gltotalamt != sltotalamt) {
            Swal.fire({
                icon:"warning",
                title:"GL and  SL Total Amount should be equal.",
                text: "GL Details",
            })
            return;
        }
    }

    EntryTable.row.add([accounttitle,"0.00",formatAmtVal(dramt),formatAmtVal(cramt),accountno,entryside,HaveSL,glslname,"-","-","-",accountno,"GL"]).draw(false);

    if (HaveSL == "YES") {
        var SLData = SLTable.rows().data().toArray();
        for (let i = 0; i < SLData.length; i++) {
            let SLCode = SLData[i][0];
            let SLName = "&emsp;&emsp;&emsp;"+SLData[i][1];
            let SLAmount = ((entryside == "CREDIT") ? "(" + SLData[i][2] + ")" : SLData[i][2]);
            let SLType = SLData[i][3];
            let LoanID = SLData[i][4];
            let Program = SLData[i][5];
            let Product = SLData[i][6];
            let GLNo = SLData[i][7];
            EntryTable.row.add([SLName,SLAmount,"0.00","0.00",SLCode,entryside,HaveSL,SLType,LoanID,Program,Product,GLNo,"SL"]).draw(false);
        }
    }
    SLTable.clear().draw(false);
    ClearValueDisableInput();
}

function ClearValueDisableInput(){
    $("#EntrySide").val("");
    $("#AcctNo").val("");
    $("#AcctTitle").val("");
    $("#GLTotalAmount").val("");
    $("#SLTotalAmount").val("0.00");
    $("#SLType").attr("disabled",true).val("");
    $("#SLName").attr("disabled",true).val("");
    $("#SLAmount").attr("disabled",true).val("");
    $("#AddSLButton").attr("disabled",true);
    $("#RemoveSLButton").attr("disabled",true);
    $("#AccountCodesTable tbody tr").removeClass("selected");
}

$('#EntryTable tbody').on('click', 'tr',function(){
    if (TransactStatus != "Saved"){
        $("#EntryTable tbody tr").removeClass("selected");
        $(this).addClass("selected");
    
        // var selectedRow1 = $(this).find('td:eq(4)').text();
        // var selectedRow2 = $(this).find('td:eq(11)').text();
    
        // $("#EntryTable tbody tr").each(function() {
        //     var currentRow1 = $(this).find('td:eq(11)').text();
        //     var currentRow2 = $(this).find('td:eq(4)').text();
        //     if (currentRow1 === selectedRow1) {
        //         $(this).addClass("selected");
        //     }
        //     if (currentRow2 === selectedRow2) {
        //         $(this).addClass("selected");
        //     }
        //     if (selectedRow2 === currentRow1) {
        //         $(this).addClass("selected");
        //     }
        // });
    
        SelectedEntry = this;
        $("#DeleteEntryBtn").attr("disabled",false);
    }
});

function DeleteEntry(){
    if  (SelectedEntry != "") {

        // let data = EntryTable.row(SelectedEntry).data();
        // let data2 = EntryTable.rows().data();

        // data2.each(function (value, index) {
        //     console.log(data[4] + " - " + data[11] + " + " + value[4] + " - " + value[11]);

        //     if(data[11] == value[4]){
        //         EntryTable.row(index).remove().draw(false);
        //     }
        //     if(data[4] == value[11]){
        //         EntryTable.row(index).remove().draw(false);
        //     }
        //     if(data[11] == value[11]){
        //         EntryTable.row(index).remove().draw(false);
        //     }
        //     if(data[4] == value[4]){
        //         EntryTable.row(index).remove().draw(false);
        //     }
        // });

        EntryTable.row(SelectedEntry).remove().draw(false);
        SelectedEntry = "";
        $("#DeleteEntryBtn").attr("disabled",true);
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Please select an entry to delete',
        })
    }
}

function ClearEntries(){
    Swal.fire({
        icon: 'info',
        title: 'Do you wish to clear all entries?',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonColor: '#435ebe',
        confirmButtonText: 'Proceed!',
        // allowOutsideClick: true,
    }).then(function(result) {
        if (result.isConfirmed) {
            EntryTable.clear().draw(false);
        }
    });
}

function AssignORNo(){
    if(EntryTable.rows().count() === 0){
       Swal.fire({
            icon:"warning",
            title:"Please Set Entries First."
        })
        return false;
    }else if(parseFloat(TotalCredit).toFixed(2) != parseFloat(TotalDebit).toFixed(2)){
        Swal.fire({
            icon:"warning",
            title:"Entries doesn`t tally. Please check your entries"
        })
        return false;
    }else{
        $("#AssignORNoModal").modal("show");
    }
}

function GetORNo (name){
    $.ajax({
        url:"../../routes/cashier/otherpayment.route.php",
        type:"POST",
        data:{action:"GetORNo", name:name},
        dataType:"JSON",
        success:function(response){ 
            $("#seriesNo").val(response.ORNO);
            $("#seriesLeftNo").val(response.ORLEFT);
            $("#seriesStatus").val(response.ORSTATUS);
            if (response.ORSTATUS == "OR") {
                $("#nonTax").prop("disabled", false)
                $("#nonTax").prop("checked", false)
            } else {
                $("#nonTax").prop("disabled", true)
                $("#nonTax").prop("checked", false)
            }
        }, 
    })
}

function Save(){
    let transactionDate = $("#transactionDate").val();
    let clientType = $("#clientType").val();
    let payeeSel = $("#payeeSel").val();
    let payeeTIN = $('#payeeTIN').val();
    let payeeAddress = $('#payeeAddress').val();
    let payeeTxt = $("#payeeTxt").val();
    let payee = "";
    if (clientType == "OTHER") {
        payee = payeeTxt;
    } else {
        payee = payeeSel;
    }
    let particulars = $("#particulars").val();
    let fund = $("#fund").val();
    let bank = $("#bank").val();
    let tag = $("#tag").val();
    let paymentType = $("#paymentType").val();
    let checkNo = "-";
    let bankName = "-";
    let bankBranch = "-";

    if (paymentType == "CHECK") {
        checkNo = $("#checkNo").val();
        bankName = $("#bankName").val();
        bankBranch = $("#bankBranch").val();
    }
    let nontax = "NO"
    if ($('#nonTax').is(':checked')) {
        nontax = "YES";
    }
    let orseries = $("#orseries").val();
    let seriesNo = $("#seriesNo").val();
    let seriesLeftNo = $("#seriesLeftNo").val();
    let seriesStatus = $("#seriesStatus").val();

    let Data = EntryTable.rows().data().toArray();

    if (orseries == "" || orseries == null){
        Swal.fire({
            icon:"warning",
            title:"Select From Series"
        })
        return;
    }

    if (seriesNo == "" || seriesNo <= 0) {
        Swal.fire({
            icon:"warning",
            title:"From Series No is Invalid"
        })
        return;
    }

    if (seriesLeftNo == "" || seriesLeftNo <= 0) {
        Swal.fire({
            icon:"warning",
            title:"No available series left."
        })
        return;
    }
    
    let formdata = new FormData();
    formdata.append("action","Save");
    formdata.append("TRANSACTIONDATE",transactionDate);
    formdata.append("PAYEE",payee);
    formdata.append("PAYEETIN",payeeTIN);
    formdata.append("PAYEEADDRESS",payeeAddress);
    formdata.append("PARTICULARS",particulars);
    formdata.append("FUND",fund);
    formdata.append("BANK",bank);
    formdata.append("TAG",tag);
    formdata.append("PAYMENTTYPE",paymentType);
    formdata.append("CHECKNO",checkNo);
    formdata.append("BANKNAME",bankName);
    formdata.append("BANKBRANCH",bankBranch);
    formdata.append("ORSERIES",orseries);
    formdata.append("SERIESNO",seriesNo);
    formdata.append("SERIESLEFTNO",seriesLeftNo);
    formdata.append("NONTAX",nontax);

    let errorCount = 0;
    let errors = [];
    for (const data of formdata.entries()) {
        console.log(data[0] + " | " + data[1]);
        if(data[1] === "" || data[1] === "null" || data[1] === null){
            errors.push(data[0]);
            errorCount++;
        }
    }

    if (errors.length > 0) {
        Swal.fire({
            icon: "warning",
            title: "Required Fields Missing",
            text: "Please fill in: " + errors.join(", ")
        });
    }

    if(errorCount <= 0){
        formdata.append("DATA",JSON.stringify(Data));
        Swal.fire({
            icon: 'info',
            title: 'Save Transaction?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Proceed!',
            allowOutsideClick: true,
            preConfirm: function() {
                return $.ajax({
                            url:"../../routes/cashier/otherpayment.route.php",
                            type:"POST",
                            data:formdata,
                            processData:false,
                            cache:false,
                            contentType:false,
                            dataType:"JSON",
                            success:function(response){
                                if(response.STATUS != "SUCCESS"){
                                    Swal.showValidationMessage(
                                        response.MESSAGE,
                                    )
                                }
                            }
                        })
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'SUCCESS') {
                    if (result.value.ORPRINT == "YES"){
                        if (seriesStatus == "OR") {
                            window.open("../../routes/cashier/otherpayment.route.php?type=ORReport");
                        } else if (seriesStatus == "AR") {
                            window.open("../../routes/cashier/otherpayment.route.php?type=ARReport");
                        }
                        console.log(seriesStatus)
                    }

                    Swal.fire({
                        icon: "success",
                        title: result.value.MESSAGE,
                    });

                    ClearAll()
                    // TransactStatus = "Saved";
                }
            }
        });
    }
}

function ClearAll() {
    LoadClientName("OTHER");
    $("#particulars").val("");
    $("#fund").val("");
    $("#bank").val("");
    $("#paymentType").val("");
    $("#checkDate").prop("disabled", true)
    $("#checkNo").prop("disabled", true).val("")
    $("#bankName").prop("disabled", true).val("")
    $("#bankBranch").prop("disabled", true).val("")
    $("#paymentType").val("");

    ClearValueDisableInput()

    EntryTable.clear().draw(false);

    $("#AssignORNoModal").modal("hide");
    $("#orseries").val("");
    $("#seriesNo").val("");
    $("#seriesLeftNo").val("");
    $("#seriesStatus").val("");
    $("#nonTax").prop("disabled", true)
    $("#nonTax").prop("checked", false)
}

function DisableAll(){
    $("#Date").attr("disabled",true);
    $("#NatureAdjustment").attr("disabled",true);
    $("#VoucherExplanation").attr("disabled",true);
    $("#EntrySide").attr("disabled",true);
    $("#GLTotalAmount").attr("disabled",true);
    $("#SLType").attr("disabled",true);
    $("#SubType").attr("disabled",true);
    $("#SLName").attr("disabled",true);
    $("#SLAmount").attr("disabled",true);

    $("#AddSLButton").attr("disabled",true);
    $("#RemoveSLButton").attr("disabled",true);
    $("#AddGLEntry").attr("disabled",true);
    $("#DeleteEntryBtn").attr("disabled",true);
    $("#clearEntriesBtn").attr("disabled",true);
    $("#Fund").attr("disabled",true);
}

function Edit(){
    $("#Date").attr("disabled",true);
    $("#NatureAdjustment").attr("disabled",false);
    $("#VoucherExplanation").attr("disabled",false);
    $("#EntrySide").attr("disabled",false);
    $("#GLTotalAmount").attr("disabled",false);
    $("#SLType").attr("disabled",false);
    $("#SubType").attr("disabled",false);
    $("#SLName").attr("disabled",false);
    $("#SLAmount").attr("disabled",false);

    $("#AddSLButton").attr("disabled",false);
    $("#RemoveSLButton").attr("disabled",false);
    $("#AddGLEntry").attr("disabled",false);
    $("#DeleteEntryBtn").attr("disabled",false);
    $("#clearEntriesBtn").attr("disabled",false);
    $("#Fund").attr("disabled",true);

    $("#SaveBtn").attr("disabled",false);
    $("#EditBtn").attr("disabled",true);
    $("#PrintBtn").attr("disabled",true);
    TransactStatus = "Edit";
}

function formatDateToMMDDYYYY(cdate) {
    let parts = cdate.split("-");
    let formattedDate = parts[1] + "/" + parts[2] + "/" + parts[0];
    return formattedDate;
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