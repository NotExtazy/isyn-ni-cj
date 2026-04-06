var AcashInfoTbl;
var EcpayTxnTbl;

LoadAcashInfo();
LoadEcpayTransactions();

function LoadAcashInfo(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadAcashInfo", limit: 50},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#AcashInfoTbl' ) ) {
                $('#AcashInfoTbl').DataTable().clear();
                $('#AcashInfoTbl').DataTable().destroy();
            }
        },
        success:function(response){
            $("#AcashInfoList").empty();
            if(response && response.ACASHINFO){
                $.each(response.ACASHINFO,function(key,value){
                    $("#AcashInfoList").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Fund"] || ""}</td>
                            <td>${value["AcctNo"] || ""}</td>
                            <td>${value["AcctTitle"] || ""}</td>
                        </tr>
                    `);
                });
            }

            if ($.fn.DataTable) {
                AcashInfoTbl = $('#AcashInfoTbl').DataTable({
                    pageLength: 5,
                    searching: true,
                    ordering: true,
                    lengthChange: false,
                    info: false,
                    paging: true,
                    responsive: true,
                });
            }
        },
        error:function(xhr){
            console.error("LoadAcashInfo error", xhr);
        }
    });
}

function LoadEcpayTransactions(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadEcpayTransactions", limit: 50},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#EcpayTxnTbl' ) ) {
                $('#EcpayTxnTbl').DataTable().clear();
                $('#EcpayTxnTbl').DataTable().destroy();
            }
        },
        success:function(response){
            $("#EcpayTxnList").empty();
            if(response && response.ECPAYTXNS){
                $.each(response.ECPAYTXNS,function(key,value){
                    var amount = "";
                    // Prefer debit amount if present, else credit amount
                    if (value["DrOther"] && value["DrOther"] !== "0" && value["DrOther"] !== 0) {
                        amount = value["DrOther"];
                    } else if (value["CrOther"] && value["CrOther"] !== "0" && value["CrOther"] !== 0) {
                        amount = value["CrOther"];
                    }
                    $("#EcpayTxnList").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Payee"] || ""}</td>
                            <td class="explanation-cell" data-full-text="${value["Explanation"] || ""}">${value["Explanation"] || ""}</td>
                            <td>${amount}</td>
                        </tr>
                    `);
                });
            }

            if ($.fn.DataTable) {
                EcpayTxnTbl = $('#EcpayTxnTbl').DataTable({
                    pageLength: 5,
                    searching: true,
                    ordering: true,
                    lengthChange: false,
                    info: false,
                    paging: true,
                    responsive: true,
                });
            }
        },
        error:function(xhr){
            console.error("LoadEcpayTransactions error", xhr);
        }
    });
}
