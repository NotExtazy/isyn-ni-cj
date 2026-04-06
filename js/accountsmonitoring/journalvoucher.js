var TransactStatus = "Allowed", BatchNo = "No", NatureAdjustment, SLTotalAmount = 0, MinusSLAmt = 0, SLTable, EntryTable, SelectedEntry,SelectedSLEntry, SelectedSavedBatchNo, HaveSL,  TotalDebit = 0, TotalCredit = 0;

$("#NatureAdjustment").select2({
    width: '100%',
    tags: true,
    matcher: function(params, data) {
        if (!params.term || params.term.trim() === '') {
            return data;
        }
        var term = params.term.toUpperCase();
        var text = data.text ? data.text.toUpperCase() : '';
        if (text.indexOf(term) > -1) {
            return data;
        }
        return null;
    },
    createTag: function(params) {
        return {
            id: params.term,
            text: params.term.toUpperCase(),
            newTag: true
        };
    }
});

$("#SLName").select2({
    width: '100%',
});

SetJVDate();
LoadPage();
LoadBatchList();

function SetJVDate(){
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

    $('#Date').datetimepicker(options);

    Swal.fire({
        title: 'Please Select Transaction Date.',
        html: '<input id="DateTransaction" readonly class="swal2-input">',
        confirmButtonText: 'Set',
        showLoaderOnConfirm: false,
        didOpen:function(e){
            $('#DateTransaction').datetimepicker(options);
        },
        allowOutsideClick: false,
    }).then((result) => {
        if (result.isConfirmed) {

            if($("#DateTransaction").val() == ""){
                location.reload();
            }

            var date = new Date($("#DateTransaction").val());

            $("#Date").val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
        }
    })
}

function LoadPage(){
    $("#SubTypeDiv").hide();

    $.ajax({
        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
        type:"POST",
        data:{action:"LoadPage"},
        dataType:"JSON",
        beforeSend:function(){
            
        },
        success:function(response){

            NatureAdjustment = response.NATUREADJUSTMENT;

            $.each(response.NATUREADJUSTMENT,function(key,value){
                $("#NatureAdjustment").append(`<option value="${value["nature"]}">${value["nature"]}</option>`);
            });

            $.each(response.FUNDS,function(key,value){
                $("#Fund").append(`<option value="${value["Fund"]}">${value["Fund"]}</option>`);
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
                    { targets: [ 4,5,6,7,8,9,10,11,12 ], visible:false, searchable:false }
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
                    { targets: [ 3,4,5,6,7 ], visible:false, searchable:false }
                ],
            });
        }, 
    })
}

function LoadBatchList(){
    $.ajax({
        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
        type:"POST",
        data:{action:"LoadBatchList"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#SavedTransactTbl' ) ) {
                $('#SavedTransactTbl').DataTable().clear();
                $('#SavedTransactTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            let bcount = response.BATCHCOUNT[0];
            let num = bcount["BatchCount"];
            let counter = "(" + num + ")";
            $("#SavedCounter").text(counter);
            if (num > 0){
                $("#ViewSaved").attr("disabled", false);
                $("#DeleteSavedBtn").attr("disabled",true);
                $("#LoadSavedBtn").attr("disabled",true);
            } else {
                $("#ViewSaved").attr("disabled", true);
                $("#DeleteSavedBtn").attr("disabled",true);
                $("#LoadSavedBtn").attr("disabled",true);
                $("#ViewSavedMdl").modal("hide");
            }

            $("#SavedTransactList").empty();
            $.each(response.SAVEDLIST,function(key,value){
                $("#SavedTransactList").append(`
                    <tr>
                        <td>${value["BatchNo"]}</td>
                        <td>${value["explanation"]}</td>
                    </tr>
                `);
            });

            $('#SavedTransactTbl').DataTable({
                searching:false,
                ordering:false,
                lengthChange:false,
                info:false,
                paging:false,
                responsive:true,
            });
        }, 
    })
}

function ViewSaved(){
    SelectedSavedBatchNo = "";
    $("#SavedTransactTbl tbody tr").removeClass("selected");
    $("#DeleteSavedBtn").attr("disabled",true);
    $("#LoadSavedBtn").attr("disabled",true);
    $("#ViewSavedMdl").modal("show");
}

$('#SavedTransactTbl tbody').on('click', 'tr',function(){
    $("#SavedTransactTbl tbody tr").removeClass("selected");
    $(this).addClass("selected");
    SelectedSLEntry = this;

    var data = $('#SavedTransactTbl').DataTable().row(this).data();

    SelectedSavedBatchNo = data[0];

    $("#DeleteSavedBtn").attr("disabled",false);
    $("#LoadSavedBtn").attr("disabled",false);
});

function DeleteSaved(){
    if (SelectedSavedBatchNo == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Please select a transaction',
            allowOutsideClick: false,
        })
        return;
    }
    
    Swal.fire({
        icon: 'warning',
        title: 'Delete Saved Transaction Batch No. ' + SelectedSavedBatchNo,
        showCancelButton: true,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return $.ajax({
                        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
                        type:"POST",
                        data:{action:"DeleteSaveBatchNo", batchno:SelectedSavedBatchNo},
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
                Swal.fire({
                    icon: "success",
                    title: result.value.MESSAGE,
                });
                LoadBatchList();
            }
        }
    });
}

function LoadSaved(){
    if (SelectedSavedBatchNo == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Please select a transaction',
            allowOutsideClick: false,
        })
        return;
    }

    Swal.fire({
        icon: 'info',
        title: 'Load Saved Transaction Batch No. ' + SelectedSavedBatchNo,
        showCancelButton: true,
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        preConfirm: function() {
            return $.ajax({
                        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
                        type:"POST",
                        data:{action:"LoadSaveBatchNo", batchno:SelectedSavedBatchNo},
                        dataType:"JSON",
                        success:function(response){
                            if(response.STATUS != "SUCCESS"){
                                Swal.showValidationMessage(
                                    response.MESSAGE,
                                )
                            }
                            if(response.STATUS == "SUCCESS"){
                                let info = response.BATCHNODATA[0];

                                $("#Date").val(formatDateToMMDDYYYY(info.cdate));
                                $("#NatureAdjustment").val(info.Nature);
                                $("#VoucherExplanation").val(info.explanation);

                                // $.each(response.BATCHNODATA,function(key,value){
                                //     $("#EntryTblList").append(`
                                //         <tr>
                                //             <td width="30%">${value["accttitle"]}</td>
                                //             <td width="15%">${value["sldrcr"]}</td>
                                //             <td width="15%">${value["drother"]}</td>
                                //             <td width="15%">${value["crother"]}</td>
                                //             <td>${value["acctno"]}</td>
                                //             <td>${value["DrCr"]}</td>
                                //             <td>${value["slyesno"]}</td>
                                //             <td>${value["slname"]}</td>
                                //             <td>${value["loanid"]}</td>
                                //             <td>${value["program"]}</td>
                                //             <td>${value["Product"]}</td>
                                //             <td>${value["GLNo"]}</td>
                                //             <td>Lul</td>
                                //         </tr>
                                //     `);
                                // });

                                EntryTable.clear().draw(false);
                                $.each(response.BATCHNODATA,function(key,value){
                                    let acctitle = "";
                                    let sltype = "";
                                    let glno = "";
                                    let type = "";

                                    if (value["sldrcr"] == 0.00){
                                        acctitle = value["accttitle"].trimStart();
                                        glno = "-";
                                        type = "GL"
                                    } else {
                                        acctitle = "&emsp;&emsp;&emsp;"+value["accttitle"].trimStart();
                                        glno = value["GLNo"];
                                        type = "SL"
                                    }
                                    
                                    if (value["slyesno"] == "NO") {
                                        sltype = "-";
                                    } else {
                                        sltype = value["slname"];
                                    }

                                    EntryTable.row.add([
                                        acctitle,
                                        value["sldrcr"],
                                        value["drother"],
                                        value["crother"],
                                        value["acctno"],
                                        value["DrCr"],
                                        value["slyesno"],
                                        sltype,
                                        value["loanid"],
                                        value["program"],
                                        value["Product"],
                                        glno,
                                        type
                                    ]).draw(false);
                                });

                                $("#Fund").val(info.fund);
                                $("#JVNo").val(info.jvno);

                                $("#SaveBtn").attr("disabled",true);
                                $("#EditBtn").attr("disabled",false);
                                $("#PrintBtn").attr("disabled",false);
                                BatchNo = info.BatchNo;

                                $("#DeleteSavedBtn").attr("disabled",true);
                                $("#LoadSavedBtn").attr("disabled",true);
                                $("#ViewSavedMdl").modal("hide");
                                DisableAll();
                                TransactStatus = "Saved";
                            }
                        }
                    })
        },
    });
}

function SelectNature(val){
    for (let i = 0; i < NatureAdjustment.length; i++) {
        if(NatureAdjustment[i]["nature"] === val){
            var expl = NatureAdjustment[i]["explanation"];
            $("#VoucherExplanation").val(expl);
            return;
        } else {
            $("#VoucherExplanation").val("");
        }
    }
}

function GetJVNo (fund){
    $.ajax({
        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
        type:"POST",
        data:{action:"GetJVNo", fund:fund},
        dataType:"JSON",
        success:function(response){ 
            $("#JVNo").val(response.JVNo[0].JVNo);
        }, 
    })
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
        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
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
        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
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
                    $("#SLName").append(`<option value="${value[response.FIELD1]}|${value[response.FIELD2]}|-|-|-">${value[response.FIELD1]}|${value[response.FIELD2]}</option>`);
                }
            });          
        }, 
    })
}

function LoadSLFromSubtype(subtype){
    $.ajax({
        url:"../../routes/accountsmonitoring/journalvoucher.route.php",
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

    EntryTable.row.add([accounttitle,"0.00",formatAmtVal(dramt),formatAmtVal(cramt),accountno,entryside,HaveSL,glslname,"-","-","-","-","GL"]).draw(false);

    if (HaveSL == "YES") {
        var SLData = SLTable.rows().data().toArray();
        for (let i = 0; i < SLData.length; i++) {
            let SLCode = SLData[i][0];
            let SLName = "&emsp;&emsp;&emsp;"+SLData[i][1];
            let SLAmount = ((entryside == "CREDIT") ? "-" : "") + SLData[i][2];
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
    EntryTable.clear().draw(false);
}

function AssignJVNo(){
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
        $("#AssignJVNoModal").modal("show");
    }
}

function Save(){
    let DatePrepared = $("#Date").val();
    let NatureAdjustment = $("#NatureAdjustment").val();
    let VoucherExplanation = $("#VoucherExplanation").val();
    let Fund = $("#Fund").val();
    let JVNo = $("#JVNo").val();
    let Data = EntryTable.rows().data().toArray();
    
    let formdata = new FormData();
    formdata.append("action","Save");
    formdata.append("DATEPREPARED",DatePrepared);
    formdata.append("NATUREADJUSTMENT",NatureAdjustment);
    formdata.append("VOUCHEREXPLANATION",VoucherExplanation);
    formdata.append("FUND",Fund);
    formdata.append("JVNO",JVNo);
    formdata.append("BATCHNO",BatchNo);

    let errorCount = 0;
    for (const data of formdata.entries()) {
        if(data[1] == "" || data[1] == null){
            Swal.fire({
                icon:"warning",
                title:data[0] + " REQUIRED"
            })
            errorCount++;
            break;
        }
    }

    if(errorCount <= 0){
        formdata.append("DATA",JSON.stringify(Data));
        Swal.fire({
            icon: 'info',
            title: 'Save details now? This will finalize the entries and funding details assigned to this transaction.',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Proceed!',
            allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                            url:"../../routes/accountsmonitoring/journalvoucher.route.php",
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
                                if(response.STATUS == "SUCCESS"){
                                    Swal.fire({
                                        icon:"success",
                                        title:response.MESSAGE
                                    })
                                }
                            }
                        })
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'SUCCESS') {
                    Swal.fire({
                        icon: "success",
                        title: result.value.MESSAGE,
                    });

                    BatchNo = result.value.BATCHNO;

                    DisableAll();
                    $("#SaveBtn").attr("disabled",true);
                    $("#EditBtn").attr("disabled",false);
                    $("#PrintBtn").attr("disabled",false);
                    TransactStatus = "Saved";
                }
            }
        });
    }
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

function Print(){
    let DatePrepared = $("#Date").val();
    let NatureAdjustment = $("#NatureAdjustment").val();
    let VoucherExplanation = $("#VoucherExplanation").val();
    let Fund = $("#Fund").val();
    let JVNo = $("#JVNo").val();
    
    let formdata = new FormData();
    formdata.append("action","SaveToBooks");
    formdata.append("DATEPREPARED",DatePrepared);
    formdata.append("NATUREADJUSTMENT",NatureAdjustment);
    formdata.append("VOUCHEREXPLANATION",VoucherExplanation);
    formdata.append("FUND",Fund);
    formdata.append("JVNO",JVNo);
    formdata.append("BATCHNO",BatchNo);

    let errorCount = 0;
    for (const data of formdata.entries()) {
        if(data[1] == "" || data[1] == null){
            Swal.fire({
                icon:"warning",
                title:data[0] + " REQUIRED"
            })
            errorCount++;
            break;
        }
    }

    if(errorCount <= 0){
        $.ajax({
            url:"../../routes/accountsmonitoring/journalvoucher.route.php",
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
                if(response.STATUS == "SUCCESS"){
                    window.open("../../routes/accountsmonitoring/journalvoucher.route.php?type=JVReport");
                    Swal.fire({
                        icon: 'success',
                        title: response.MESSAGE,
                        allowOutsideClick: false,
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });
                }
            }
        })
    }
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