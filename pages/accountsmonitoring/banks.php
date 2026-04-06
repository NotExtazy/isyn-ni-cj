<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once(dirname(__DIR__, 2) . '/includes/permissions.php');
        $permissions = new Permissions();
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: ../../dashboard");
            exit;
        }
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="../../assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="../../assets/select2/css/select2.min.css">

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

            <style>
                label,
                thead {
                    color: #090909;
                }

                main {
                background-color: #EAEAF6;
                min-height: 100vh; /* Ensure main takes up at least 100% of the viewport height */
                display: flex;
                flex-direction: column;
                }
            </style>

            <div class="container mt-4">
                <div class="p-3 rounded-2" style="background-color: white;">
                    <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Banks</p>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <div class="shadow p-3 rounded-3" style="background-color: white;">
                            <div class="mb-3"></div>
                            <p class="fw-medium fs-5" style="color: #090909;">Banks</p>
                            <hr style="height: 1px">
                            <table class="text-center table" style="background-color: white;" id="bankTbl">
                                <thead>
                                    <tr>
                                        <th style="color:#090909;width:50%;text-align:center">SL Number</th>
                                        <th style="color:#090909;width:25%;text-align:center">Bank Accounts</th>
                                    </tr>
                                </thead>
                                <tbody id="bankList">
                                        
                                        </tbody>
                                
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-2 shadow" style="background-color: white;">
                            <div class="mb-3">
                                                    <button class="btn btn-danger mx-1 float-end" type="button" name="cancel" id="cancel"><i class="fa-solid fa-times-circle"></i> Cancel</button>
                                                <button class="btn btn-primary float-end mx-2"  id="save" name="save" type="button"><i class="fa fa-save"></i> Save</button>
                                                <button class="btn btn-success float-end mx-1" name="add" id="add"><i class="fa fa-plus"></i> New</button>

                            </div>

                            <p class="fw-medium fs-5" style="color: #090909;">Bank Details</p>
                            <hr style="height: 1px">
                            <div>
                                <form id="banksForm">
                                    <div class="form-group row mb-12">
                                    <input type="hidden" class="form-control form-control-sm" name="id" id="id" readonly>
                                        <label for="bankName" class="col-sm-5 col-form-label">Bank Name:</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" id="name" name = "name" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-3">
                                        <label for="bankInstitution" class="col-sm-5 col-form-label">Bank Institution:</label>
                                        <div class="col-sm-7">
                                            <select class="form-select" aria-label="Default select example" id="bankinst" name = "bankinst" disabled >
                                                <option value="">Select</option>
                                            
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-3">
                                        <label for="bankAccounts" class="col-sm-5 col-form-label">Bank Accounts:</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" id="acctno" name="acctno" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-3">
                                        <label for="subCode" class="col-sm-5 col-form-label">Sub Code:</label>
                                        <div class="col-sm-7">
                                            <input type="text" class="form-control" id="subcode" name="subcode" readonly>
                                        </div>
                                    </div>

                                    <!-- Add other fields as needed -->

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <!-- <script src="../../js/generalledger/posting.js?<?= time() ?>"></script> -->

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login"; </script>';
  }
?>

<script>
    $(document).ready(function () {

        var banksTbl, bankCode;

        LoadBanksTbl();

        function LoadBanksTbl () {
        $.ajax({
            url:"bankaccounts.process.php",
            type:"POST",
            data:{loadBanksTbl:"ON"},
            dataType:"JSON",
            beforeSend:function(e){
                $("#bankList").empty();
                $("#bankList").append("<tr><td colspan='2'>Loading..</td></tr>");
            },
            success:function(response){
                if ( $.fn.DataTable.isDataTable( '#bankTbl' ) ) {
                    $('#bankTbl').DataTable().clear();
                    $('#bankTbl').DataTable().destroy();
                }

                $("#bankList").empty();
                $.each(response.LIST,function(key,value){
                    $("#bankList").append("<tr><td>" + value["BankCode"] + "</td><td>" + value["BankName"] + "</td></tr>");
                })

                banksTbl = $('#bankTbl').DataTable({
                    scrollY: '50vh',
                    scrollX: true,
                    scrollCollapse: true,
                    paging: false,
                    bFilter:false,
                    info:false,
                })
            }
        })
        };

        LoadBankIns('#bankinst');

        async function LoadBankIns(selector){
            await $.ajax({
                url: "bankaccounts.process.php",
                type: "POST",
                data: {loadBankIns: "ON"},
                dataType: "JSON",
                beforeSend: function() {
                    console.log('loading types...')
                },
                success: function(response) {
                    $(selector).empty().append(`<option value="" selected></option>`);
                    response.LIST.forEach(element => {
                        $(selector).append(`<option value="` + element.BankName + `">` + element.BankName + `</option>`);
                    });
                },
                error: function(err) {
                    console.log(err)
                }
            });
        }

        // ===================================================================================
        // Button Actions

        $('#add').on('click', function() {
            $('#id').val("");
            $('#name').val("");
            $('#bankinst').val("");
            $('#acctno').val("");
            $('#subcode').val("");
            $('#name').prop('readonly', false);
            $('#bankinst').prop('disabled', false);
            $('#acctno').prop('readonly', false);
            $("#add").prop('disabled', true);
            $("#edit").prop('disabled', true);
            $('#save').prop('disabled', false);
            $('#cancel').prop('disabled', false);
        });
        $('#edit').on('click', function() {
            $('#bankinst').prop('disabled', false);
            $('#acctno').prop('readonly', false);
            $("#add").prop('disabled', true);
            $('#save').prop('disabled', false);
            $('#cancel').prop('disabled', false);
        });

        $('#cancel').on('click', function() {
            $('#id').val("");
            $('#name').val("");
            $('#bankinst').val("");
            $('#acctno').val("");
            $('#subcode').val("");
            $('#name').prop('readonly', true);
            $('#bankinst').prop('disabled', true);
            $('#acctno').prop('readonly', true);
            $("#add").prop('disabled', false);
            $("#edit").prop('disabled', true);
            $('#save').prop('disabled', true);
            $('#cancel').prop('disabled', true);
        });

        $('#bankinst').on('change', function() {
            var bankname = $(this).val();

            if (bankname === '') {
                // Handle case when no option is selected
                Swal.fire({
                    icon: "warning",
                    text: "Select Bank Institution",
                });
            } else {
            $.ajax({
                url: "bankaccounts.process.php",
                type: "POST",
                data: {getSubCode: "ON",  bankname:bankname},
                dataType: "JSON",
                beforeSend: function() {
                    console.log('loading types...')
                },
                success: function(response) {
                    if (response.Status == "EMPTY"){
                    Swal.fire({
                        icon: "warning",
                        title: "Selected Bank have no Subsidiary Code, Check Subsidiary Code List.",
                    });
                    $('#name').val("");
                    $('#bankinst').val("");
                    } else {
                    bankCode = response.Bankcode.BankCode+"-";
                    $('#name').val(response.Bankcode.BankCode+'-');
                    $('#subcode').val(response.Subcode.SUBCODE);
                    }
                },
                error: function(err) {
                    console.log(err)
                }
            });
            }
        });

        $('#acctno').on('input', function() {
            var acctno = $('#acctno').val();
            var bankcodewithacctno = bankCode + acctno;
            $('#name').val(bankcodewithacctno);
        });

        // ===================================================================================
        // Table Select

        $('#bankTbl tbody').on('click', 'tr', function() {
            // Remove the 'selected' class from all rows
            $('#bankTbl tbody tr').removeClass('selected');

            // Add the 'selected' class to the clicked row
            $(this).addClass('selected');
            $('#edit').show();
            $('#add').prop('disabled', false);
            $('#edit').prop('disabled', false);
            $('#save').prop('disabled', true);
            $('#cancel').prop('disabled', true);

            var rowData = banksTbl.row(this).data();
            var sl = rowData[0];
            var name = rowData[1];
            $('#name').val(name);

            // Make an AJAX request to fetch data based on the row ID
            $.ajax({
                url: 'bankaccounts.process.php',
                method: 'POST',
                data: { getBankDetails: "ON", sl: sl},
                dataType: 'JSON',
                success: function(response) {
                console.log(response.LIST.ID);
                    // $('#id').val(response.LIST.ID);
                    // $('#name').val(response.LIST.BankName);
                }
            });
        });
        // =====================================================================================
        // Save
    
        $(document).on('click', '#save', function() {
        var form = $('#banksForm')[0];
        var formData = new FormData(form);
        formData.append('saveBankAccount', true);


        if (formData.get('bankinst') == "") {
            Swal.fire({
                icon: "warning",
                text: "Please Select Bank Institution",
            });
        } else if (formData.get('acctno') == "") {
            Swal.fire({
                icon: "warning",
                text: "Please enter account number",
            });
        } else if (formData.get('subcode') == "") {
            Swal.fire({
                icon: "warning",
                text: "Please enter sub code for this subsidiary",
            });
        } else {
            Swal.fire({
                title: 'Are you sure?',
                icon: 'question',
                text: 'Save Bank Details?.',
                showCancelButton: true,
                showLoaderOnConfirm: true,
                confirmButtonColor: '#435ebe',
                confirmButtonText: 'Yes, proceed!',
                allowOutsideClick: false,
                preConfirm: function() {
                    return $.ajax({
                        url: "bankaccounts.process.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'JSON',
                        beforeSend: function() {
                            console.log('Processing Request...')
                        },
                        success: function(response) {
                            if (response.STATUS == 'SUCCESS') {
                            console.log('Request Processed...')
                            $('#id').val("");
                            $('#name').val("");
                            $('#bankinst').val("");
                            $('#bankinst').prop('disabled', true);
                            $('#acctno').prop('readonly', true);
                            $("#add").prop('disabled', false);
                            $("#edit").prop('disabled', true);
                            $('#save').prop('disabled', true);
                            $('#cancel').prop('disabled', true);
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
                        text: result.value.MESSAGE,
                    });
                    } else if (result.value.STATUS != 'SUCCESS') {
                        Swal.fire({
                            icon: "warning",
                            text: result.value.MESSAGE,
                        });
                    }
                    LoadBanksTbl();
                }
            });
        }
        });
    });
</script>