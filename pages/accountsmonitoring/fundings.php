<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once('../../includes/permissions.php');
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
                  height: 100vh;
              }
          </style>

          <div class="modal fade" id="setJV" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog" style = "margin-left:600px;margin-top:200px" role="document">
                <div class="modal-content" style="width:600px;">
                  <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLongTitle"><b>Set JV Series</b><h6>
                    <button type="button" data-dismiss="modal" class="close" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body" >
                    <form method="POST" id="setJVNo">
                    <input type="hidden" name="teacherno" />
                    <div class="row">
                            <div class="col-md-3 col-sm-3  form-group">
                              <b><label for="track" >Fund Type </label></b>
                            </div>
                            <div class=" col-md-9 col-sm-9 form-group">
                            <select name="fundtype", id="fundtype", class="form-select" >
                <option value=""></option>
                
            </select>
            </div>
            </div>
            <div class="row">
                            <div class="col-md-3 col-sm-3form-group">
                              <b><label for="track">Next JV No.</label></b>
                            </div>
                            <div class="inputHeight col-md-9 col-sm-9 form-group">
                            <input oninput="this.value=this.value.toUpperCase()" id="jvNo" required name="jvNo" type="text" class="inputHeight form-control" />
                            <input oninput="this.value=this.value.toUpperCase()" id="bnkID" required name="bnkID" type="hidden" class="inputHeight form-control" />

                            </div>
                          </div>   
                          <button type="button" class="btn btn-secondary btn-block" name="saveJV" id = "saveJV" Title="Save" style="float:right">Save</button>
            </form>

            </div>
            </div>
            </div>
          </div>



          <div class="modal fade" id="bank" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog" style = "margin-left:600px;margin-top:200px" role="document">
              <div class="modal-content" style="width:600px;">
                <div class="modal-header">
                  <h6 class="modal-title" id="exampleModalLongTitle"><b>Bank Institution</b><h6>
                  <button type="button" data-dismiss="modal" class="close" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body" >
                  <form method="POST" id="setbnkCode">
                    <input type="hidden" name="teacherno" />
                    <div class="row">
                          <div class="col-sm-12">
                        
                            <div class="row">
                        
                          
                          <div class="col-sm-0">
          <br/>
                          
          </div>
          
          
          <div style="display: flex; gap: 10px; flex-wrap: wrap;">
              <button type="button" name="add_bnk" id="add_bnk" title="Add" class="btn btn-secondary text-white" form="option">
                  <i class="fas fa-plus-square"> Add</i>
              </button>
              <button title="Cancel Add" type="button" form="option" name="cancel_bnk" id="cancel_bnk" class="btn btn-secondary" style="display:none">
                  <i class="fas fa-times"> Cancel</i>
              </button>
              <button title="Save" id="save_bnk" disabled type="button" form="filter" class="btn btn-secondary text-white">
                  <i class="fas fa-floppy-o"> Save</i>
              </button>
              <button title="Update" id="save_edit" type="button" form="filter" class="btn btn-secondary text-white" style="display:none">
                  <i class="fas fa-floppy-o"> Save</i>
              </button>
              <button title="Edit" type="button" disabled class="btn btn-secondary" name="update" id="update_bnk" form="option">
                  <i class="fas fa-pen-square"> Edit</i>
              </button>
              <button title="Cancel Update" type="button" form="option" name="cancel_bnk1" id="cancel_bnk1" class="btn btn-secondary" style="display:none">
                  <i class="fas fa-times"> Cancel</i>
              </button>
              <button title="Delete" type="button" disabled form="option" name="delete_bnk" id="delete_bnk" class="btn btn-secondary">
                  <i class="fas fa-trash"> Delete</i>
              </button>
          </div>
    

                  </div>
                </div>
                
</div>
<br/>

<table id="bnkCode"  name = "example" style="width:100%" class="text-center table">
            <thead>
              <tr>
                
                <th style="color:#090909;width:45%;text-align:center">Bank</th>
                <th style="color:#090909;width:45%;text-align:center">Bank Code</th>
                <th style="display:none;width:10%">ID</th>
                
              </tr>
            </thead>
            <tbody id="bnkNames">
           
            </tbody>
           
          </table>
       
          <hr></hr>
          <h6><label>Details</label></h6>
          
          <div class="row">
                <div class="col-md-4 col-sm-4 form-group">
                  <b><label for="track">Full Bank Name</label></b>
                </div>
                <div class="inputHeight col-md-8 col-sm-8 form-group">
                <input oninput="this.value=this.value.toUpperCase()" id="bnknm" required name="bnknm" disabled type="text" class="inputHeight form-control" />
                <br>
               
                </div>
                <input oninput="this.value=this.value.toUpperCase()" id="bnk_id" required name="bnk_id" type="hidden" class="inputHeight form-control" />

              </div> 
              <div class="row">
                <div class="col-md-4 col-sm-4 form-group">
                  <b><label for="track">Bank Code</label></b>
                </div>
                <div class="inputHeight col-md-8 col-sm-8 form-group">
                <input oninput="this.value=this.value.toUpperCase()" id="bnkcd" required name="bnkcd" disabled type="text" class="inputHeight form-control" />

                </div>
              </div> 
          <!-- <button type="submit" class="btn btn-secondary btn-block" name="save" Title="Save">Save</button> -->
        </form>
      </div>
    </div>
  </div>
</div>



<div class="container mt-4">
    <div class="p-3 rounded-2" style="background-color: white;">
        <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Funding Accounts</p>
    </div>


    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <div class=" shadow p-3 rounded-3  " style="background-color: white;">
                 <button class="btn btn-danger mx-1 float-end" type="button" disabled name="deletebank" id="deletebank"><i class="fa-solid fa-trash-can"></i> Delete</button>
                 <button class="btn btn-primary mx-1 float-end" type="button" disabled name="editbank" id="editbank"><i class="fa-solid fa-pen-to-square"></i> Edit</button>

                <button class="btn btn-success mx-1 float-end" type="button"  name="addbank" id="addbank"><i class="fa-solid fa-plus"></i> Add</button>

                <p class="fw-medium fs-5" style="color: #090909;">Accounts</p>
                <hr style="height:1px">
                <div action="" class="col-md-4 d-flex mt-3 mb-3" role="search">
                <button type="button" name="save" title="Save" form="option" data-toggle="modal" data-target="#setJV" style ="border:none;background:none;color:blue"><i><u> JV Configurations</i></u></button>

                </div>
                <  <table id="bnkTbl"  name = "example" style="width:100%" class="text-center table">
            <thead>
              <tr>
                <th>Bank Account</th>
                <th>Fund</th>
              <th style="display:none">ID</th>
                
               
                
              </tr>
            </thead>
                    <tbody id="bnkList">
                       
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md mb-3">
        <form method = "POST" id="fundAccts">
          <div class=" shadow p-3 rounded-3  " style="background-color: white;">
                
                 <button class="btn btn-primary mx-1 float-end" type="button" disabled name="cancelbank" id="cancelbnk"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                <button class="btn btn-danger mx-1 float-end" type="button" disabled name="savebnk" id="savebnk"><i class="fa-solid fa-save"></i> Save</button>
                <button class="btn btn-success mx-1 float-end" type="button"  name="savebnk1" id="savebnk1" title="Update" style="display:none"><i class="fa-solid fa-save"></i> Save</button>

                <p class="fw-medium fs-5" style="color: #090909;">Fund Details</p>
    
                <hr style="height:1px">
                    <div class="form-group row mb-2">
                        <label for="date" class="col-sm-4 col-form-label">Bank:</label>
                        <input oninput="this.value=this.value.toUpperCase()" id="fundID" name="fundID" type="hidden" class="inputHeight form-control" readonly>
                        <input oninput="this.value=this.value.toUpperCase()" id="bank_code" name="bank_code" type="hidden" class="inputHeight form-control" readonly>
                        <div class="col-sm-8">
                            <select class="form-select " id="bankname" required name="bankname" aria-label="Default select example" disabled>
                              <option></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row mb-2">
                        <label for="date" class="col-sm-4 col-form-label">Fund/Tag:</label>
                        <div class="col-sm-8">
                            <select class="form-select" id="fund" required name="fund"  aria-label="Default select example" disabled>
                                <option value=""></option>
                                
                            </select>
                        </div>
                    </div>
                    <hr style="height: 1px">

                    <div class="form-group row mb-2">
                        <label for="date" class="col-sm-4 col-form-label">Last CV No.</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" disabled id="cvNo" name="cvNo">
                        </div>
                    </div>

                    <div class="form-group row mb-2">
                        <label for="date" class="col-sm-4 col-form-label">Next Check No.</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="chkNo" required name="chkNo" disabled>
                        </div>
                    </div>

                    <hr style="height: 1px">

                    <div class="form-group row mb-2">
                        <label for="date" class="col-sm-4 col-form-label" >Check Series</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" disabled id="ck1" required name="ck1">
                        </div>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" id="ck2" disabled required name="ck2">
                        </div>
                    </div>
                    <hr>
                    </hr>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input">
                        <label class="form-check-label">OTC Disbursement (Over the Counter)</label>
                    </div>
                    <hr>
                </hr>
                    <div class="buttons text-end">
                        <button type="button" form="option" data-toggle="modal" data-target="#bank" style ="border:none;background:none;color:blue;"> <i><u>Bank Institution</i></u> </button>
                    </div>
                </div>
        </form>
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

  <script  type="text/javascript">
    $("#credit").click(function(){
        if($("#credit").prop("checked")) {
          $("#gl").prop('disabled', false);
          $("#sl").prop('disabled', false);
          $("#slName").prop('disabled', false);
        } else {
          $("#gl").prop('disabled', true);
          $("#sl").prop('disabled', true);
          $("#slName").prop('disabled', true);
        }
    });
  </script>

  <script>
    $(document).ready(function () {
        $('table.display').DataTable({
            scrollY: '200px',
            scrollCollapse: true,
            paging: false,
            bFilter:false,
            info:false,
        });
    });

    $('#ck1').on('input', function() {
      var acctno = $('#ck1').val();
      $('#chkNo').val(acctno);
    });


  </script>
  <script  type="text/javascript">
  $(document).ready(function () {

    var bnkTbl;

    LoadNamesTbl();

    function LoadNamesTbl () {
      $.ajax({
          url:"fundings.process.php",
          type:"POST",
          data:{loadNamesTbl:"ON"},
          dataType:"JSON",
          beforeSend:function(e){
            $("#bnkList").empty();
            $("#bnkList").append("<tr><td colspan='10'>Loading..</td></tr>");
          },
          success:function(response){
              if ( $.fn.DataTable.isDataTable( '#bnkTbl' ) ) {
                  $('#bnkTbl').DataTable().clear();
                  $('#bnkTbl').DataTable().destroy();
              }

              $("#bnkList").empty();
              $.each(response.LIST,function(key,value){
                  $("#bnkList").append("<tr><td>" + value["Bank"] + "</td>" + "<td>" + value["Fund"] + "</td>" +  "<td style='display:none'>" + value["ID"] + "</td>" + "</tr>");
                  
              })

              bnkTbl = $('#bnkTbl').DataTable({
                scrollY: '20vh',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                bFilter:false,
                info:false,
            })
          }
      })
     
    };
  });
</script>
<script  type="text/javascript">
  $(document).ready(function () {

    var bnkTbl;

    LoadNamesTbl();

    function LoadNamesTbl () {
      $.ajax({
          url:"fundings.process.php",
          type:"POST",
          data:{loadNamesTbl:"ON"},
          dataType:"JSON",
          beforeSend:function(e){
            $("#bnkList").empty();
            $("#bnkList").append("<tr><td colspan='10'>Loading..</td></tr>");
          },
          success:function(response){
              if ( $.fn.DataTable.isDataTable( '#bnkTbl' ) ) {
                  $('#bnkTbl').DataTable().clear();
                  $('#bnkTbl').DataTable().destroy();
              }

              $("#bnkList").empty();
              $.each(response.LIST,function(key,value){
                  $("#bnkList").append("<tr><td>" + value["Bank"] + "</td>" + "<td>" + value["Fund"] + "</td>" + "<td style='display:none'>" + value["ID"] + "</td>" + "</tr>");
                  
              })

              bnkTbl = $('#bnkTbl').DataTable({
                scrollY: '20vh',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                bFilter:false,
                info:false,
            })
          }
      })
     
    };

    var bnkCode;

LoadNamesTbl1();

function LoadNamesTbl1 () {
  $.ajax({
      url:"fundings.process.php",
      type:"POST",
      data:{loadNamesTbl1:"ON"},
      dataType:"JSON",
      beforeSend:function(e){
        $("#bnkNames").empty();
        $("#bnkNames").append("<tr><td colspan='10'>Loading..</td></tr>");
      },
      success:function(response){
          if ( $.fn.DataTable.isDataTable( '#bnkCode' ) ) {
              $('#bnkCode').DataTable().clear();
              $('#bnkCode').DataTable().destroy();
          }

          $("#bnkNames").empty();
          $.each(response.LIST,function(key,value){
              $("#bnkNames").append("<tr><td>" + value["BankName"] + "</td>" + "<td>" + value["BankCode"] + "</td>" +  "<td style='display:none'>" + value["ID"] + "</td>" + "</tr>");
              
          })

          bnkCode = $('#bnkCode').DataTable({
            scrollY: '20vh',
            scrollX: true,
            scrollCollapse: true,
            paging: false,
            bFilter:false,
            info:false,
        })
      }
  })
 
};
  
    $('#addbank').on('click', function() {
      $('#bankname').prop('disabled', false);
        $('#fund').prop('disabled', false);
        $('#cvNo').prop('disabled', false);
        $('#chkNo').prop('disabled', false);
        $('#chkNo').prop('readonly', true);
        $('#ck1').prop('disabled', false);
        $('#ck2').prop('disabled', false);
        $('#cancelbnk').prop('disabled', false);
        $('#editcode').prop('disabled', true);
        $('#deletecode').prop('disabled', true);
        $('#savebnk').prop('disabled', false);
        
        $('#bankname').val("");
        $('#fund').val("");
        $('#cvNo').val("");
        $('#chkNo').val("");
        $('#ck1').val("");
        $('#ck2').val("");
       

       
    });
    $('#cancelbnk').on('click', function() {
      $('#bankname').prop('disabled', true);
        $('#fund').prop('disabled', true);
        $('#cvNo').prop('disabled', true);
        $('#chkNo').prop('disabled', true);
        $('#ck1').prop('disabled', true);
        $('#ck2').prop('disabled', true);
        $('#cancelbnk').prop('disabled', true);
        $('#editbank').prop('disabled', false);
        $('#deletebank').prop('disabled', false);
        $('#addbank').prop('disabled', false);
        $('#savebnk1').hide();
        $('#savebnk').show();
        $('#savebnk').prop('disabled', true);
        
        $('#bankname').val("");
        $('#fundID').val("");
        $('#bank_code').val("");
        $('#fund').val("");
        $('#cvNo').val("");
        $('#chkNo').val("");
        $('#ck1').val("");
        $('#ck2').val("");
       

       
    });

    $('#add_bnk').on('click', function() {
      $('#bnknm').prop('disabled', false);
        $('#bnkcd').prop('disabled', false);
        $('#update_bnk').prop('disabled', true);
        $('#save_bnk').prop('disabled', false);
        $('#delete_bnk').prop('disabled', true);
        // $('#ck1').prop('disabled', true);
        // $('#ck2').prop('disabled', true);
        $('#cancel_bnk').show();
        $('#add_bnk').hide();
        $('#savebnk1').hide();
        $('#savebnk').show();
        $('#savebnk').prop('disabled', true);
        
        $('#bnknm').val("");
        $('#bnkcd').val("");
        
       

       
    }); 

    $('#cancel_bnk').on('click', function() {
      $('#bnknm').prop('disabled', true);
        $('#bnkcd').prop('disabled', true);
        $('#save_bnk').prop('disabled', true);
        // $('#ck1').prop('disabled', true);
        // $('#ck2').prop('disabled', true);
        $('#cancel_bnk').hide();
        $('#add_bnk').show();
        $('#savebnk1').hide();
        $('#savebnk').show();
        $('#savebnk').prop('disabled', true);
        
        $('#bnknm').val("");
        $('#bnkcd').val("");
        
       

       
    }); 

    $('#update_bnk').on('click', function() {
      $('#bnknm').prop('disabled', false);
        $('#bnkcd').prop('disabled', false);
        $('#save_bnk1').prop('disabled', false);
        $('#delete_bnk').prop('disabled', true);
        // $('#ck1').prop('disabled', true);
        // $('#ck2').prop('disabled', true);
        $('#cancel_bnk1').show();
        $('#update_bnk').hide();
        $('#save_edit').show();
        $('#save_bnk').hide();

        $('#savebnk').show();
        $('#savebnk').prop('disabled', true);
        
       
        
       

       
    }); 

    $('#cancel_bnk1').on('click', function() {
      $('#bnknm').prop('disabled', false);
        $('#bnkcd').prop('disabled', false);
        
        $('#delete_bnk').prop('disabled', false);
        // $('#ck1').prop('disabled', true);
        // $('#ck2').prop('disabled', true);
        $('#cancel_bnk1').hide();
        $('#update_bnk').show();
        $('#save_edit').hide();
        $('#save_bnk').show();

     
        
        $('#bnknm').val("");
        $('#bnkcd').val("");
        
       

       
    }); 
    $('#editbank').on('click', function() {
      $('#bankname').prop('disabled', false);
        $('#fund').prop('disabled', false);
        $('#cvNo').prop('disabled', false);
        $('#chkNo').prop('disabled', false);
        $('#ck1').prop('disabled', false);
        $('#ck2').prop('disabled', false);
        $('#cancelbnk').prop('disabled', false);
        $('#editbank').prop('disabled', true);
        $('#deletebank').prop('disabled', true);
        $('#savebnk').hide();
        $('#savebnk1').show();
        $('#addbank').prop('disabled', true);
        
       
       

       
    });
    $('#bnkTbl tbody').on('click', 'tr', function() {
        // Remove the 'selected' class from all rows
        $('#bnkTbl tbody tr').removeClass('selected');

        // Add the 'selected' class to the clicked row
        $(this).addClass('selected');
        $('#bankname').prop('disabled', true);
        $('#fund').prop('disabled', true);
        $('#cvNo').prop('disabled', true);
        $('#chkNo').prop('disabled', true);
        $('#ck1').prop('disabled', true);
        $('#ck2').prop('disabled', true);
        $('#editbank').prop('disabled', false);
        $('#deletebank').prop('disabled', false);
        $('#addbank').prop('disabled', false);
        $('#savebnk1').hide();
       
        

        var rowData = bnkTbl.row(this).data();
       
        var acct_codes = rowData[2];
        
        // var type = rowData[2];
        
        
     
         // Get the value from the first column
        $('#bankname').val(bankname);
        // $('#chkNo').val(acct_title);
        // $('#ck1').val(type);
        // $('#ck2').val(type);
       
        
        // Make an AJAX request to fetch data based on the row ID
        $.ajax({
            url: 'fundings.process.php',
            method: 'POST',
            data: { getAcctCodes: "ON", acct_codes : acct_codes },
            dataType: 'JSON',
            success: function(response) {
              // console.log(response.LIST.ID);
                $('#bankname').val(response.LIST.Bank);
                $('#bank_code').val(response.LIST.BankCode);
                $('#fund').val(response.LIST.Fund);
                $('#cvNo').val(response.LIST.LastCV);
                $('#chkNo').val(response.LIST.NextCheck);
                $('#ck1').val(response.LIST.SeriesFrom);
                $('#ck2').val(response.LIST.SeriesTo);
                $('#fundtype').val(response.LIST.Fund);
                $('#jvNo').val(response.LIST.JVNo);
                $('#bnkID').val(response.LIST.ID);
                $('#fundID').val(response.LIST.ID);
               
               
            }
            
        });
      });
      $('#bnkCode tbody').on('click', 'tr', function() {
        // Remove the 'selected' class from all rows
        $('#bnkCode tbody tr').removeClass('selected');

        // Add the 'selected' class to the clicked row
        $(this).addClass('selected');
        $('#bnknm').prop('disabled', true);
        $('#bnkcd').prop('disabled', true);
        $('#update_bnk').prop('disabled', false);
        $('#delete_bnk').prop('disabled', false);
        // $('#ck1').prop('disabled', true);
        // $('#ck2').prop('disabled', true);
        // $('#editbank').prop('disabled', false);
        // $('#deletebank').prop('disabled', false);
        // $('#addbank').prop('disabled', false);
        // $('#savebnk1').hide();
       
        

        var rowData = bnkCode.row(this).data();
       
        var bnkcode = rowData[0];
        var bnkcode1 = rowData[1];
        var id = rowData[2];
        
        
     
         // Get the value from the first column
        $('#bnknm').val(bnkcode);
        $('#bnkcd').val(bnkcode1);
        $('#bnk_id').val(id);
        // $('#chkNo').val(acct_title);
        // $('#ck1').val(type);
        // $('#ck2').val(type);
       
        
        // Make an AJAX request to fetch data based on the row ID
        $.ajax({
            url: 'fundings.process.php',
            method: 'POST',
            data: { getBnkCode: "ON", bnkcode : bnkcode },
            dataType: 'JSON',
            success: function(response) {
              console.log(response.LIST.ID);
                $('#bnknm').val(response.LIST.BankName);
                $('#bnkcd').val(response.LIST.BankCode);
                $('#bnk_id').val(response.LIST.ID);
                // $('#cvNo').val(response.LIST.LastCV);
                // $('#chkNo').val(response.LIST.NextCheck);
                // $('#ck1').val(response.LIST.SeriesFrom);
                // $('#ck2').val(response.LIST.SeriesTo);
                // $('#fundtype').val(response.LIST.Fund);
                // $('#jvNo').val(response.LIST.JVNo);
                // $('#bnkID').val(response.LIST.ID);
                // $('#fundID').val(response.LIST.ID);
               
               
            }
            
        });
      });
  
$('#savebnk'). click(function(e) {


var form = $('#fundAccts')[0];
var formData = new FormData(form);
formData.append('savefundAccts', true);

// updateCalculatedFields();

Swal.fire({
    title: 'Are you sure?',
    icon: 'question',
    text: 'Save Fund Account Setting.',
    showCancelButton: true,
    showLoaderOnConfirm: true,
    confirmButtonColor: '#435ebe',
    confirmButtonText: 'Yes, proceed!',
    allowOutsideClick: false,
    preConfirm: function() {
        return $.ajax({
            url: "fundings.process.php",
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
                  $('#bankname').prop('disabled', true);
                  $('#fund').prop('disabled', true);
                  $('#cvNo').prop('disabled', true);
                  $('#chkNo').prop('disabled', true);
                  $('#normal').prop('disabled', true);
                  $('#ck1').prop('disabled', true);
                  $('#ck2').prop('disabled', true);
                
                  // $('#glTbl')[0].reset();
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
       LoadNamesTbl()
    }
  });
});
 
  $('#saveJV'). click(function() {
  
  

var form = $('#setJVNo')[0];
var formData = new FormData(form);
formData.append('saveJVNo', true);

// updateCalculatedFields();

Swal.fire({
    title: 'Are you sure?',
    icon: 'question',
    text: 'Save Fund Account Setting.',
    showCancelButton: true,
    showLoaderOnConfirm: true,
    confirmButtonColor: '#435ebe',
    confirmButtonText: 'Yes, proceed!',
    allowOutsideClick: false,
    preConfirm: function() {
        return $.ajax({
            url: "fundings.process.php",
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
                  $('#bankname').prop('disabled', true);
                  $('#fund').prop('disabled', true);
                  $('#cvNo').prop('disabled', true);
                  $('#chkNo').prop('disabled', true);
                  $('#normal').prop('disabled', true);
                  $('#ck1').prop('disabled', true);
                  $('#ck2').prop('disabled', true);
                 
                  $('#savebnk').prop('disbaled', true);
                  $('#cancelbnk').prop('disabled', true);
                  // $('#glTbl')[0].reset();
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
       $('#setJV').modal('hide');
       LoadNamesTbl()
    }
  });
});
$('#savebnk1'). click(function() {
  var form = $('#fundAccts')[0];
  var formData = new FormData(form);
  formData.append('updatefundAccts', true);

  // updateCalculatedFields();

  Swal.fire({
      title: 'Are you sure?',
      icon: 'question',
      text: 'Save Fund Account Setting.',
      showCancelButton: true,
      showLoaderOnConfirm: true,
      confirmButtonColor: '#435ebe',
      confirmButtonText: 'Yes, proceed!',
      allowOutsideClick: false,
      preConfirm: function() {
          return $.ajax({
              url: "fundings.process.php",
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
                    $('#bankname').prop('disabled', true);
                    $('#fund').prop('disabled', true);
                    $('#cvNo').prop('disabled', true);
                    $('#chkNo').prop('disabled', true);
                    $('#normal').prop('disabled', true);
                    $('#ck1').prop('disabled', true);
                    $('#ck2').prop('disabled', true);

                  
                    $('#savebnk1').prop('disabled', true);
                    $('#savebnk').prop('disabled', true);
                    $('#cancelbnk').prop('disabled', true);
                    $('#editbank').prop('disabled', false);
                    $('#deletebank').prop('disabled', false);
                    $('#addbank').prop('disabled', false);
                    // $('#glTbl')[0].reset();
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
      LoadNamesTbl()
    }
  });
});

$('#save_bnk').click(function() {


var form = $('#setbnkCode')[0];
var formData = new FormData(form);
formData.append('AddBankCode', true);

// updateCalculatedFields();

Swal.fire({
    title: 'Are you sure?',
    icon: 'question',
    text: 'Save Bank Account Setting.',
    showCancelButton: true,
    showLoaderOnConfirm: true,
    confirmButtonColor: '#435ebe',
    confirmButtonText: 'Yes, proceed!',
    allowOutsideClick: false,
    preConfirm: function() {
        return $.ajax({
            url: "fundings.process.php",
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
                  $('#bnknm').prop('disabled', true);
                  $('#bnkcode').prop('disabled', true);
                   
                  $('#save_bnk').prop('disabled', true);
                  $('#cancel_bnk').hide();
                  $('#add_bnk').show();

                  $('#bnknm').val("");
                  $('#bnkcd').val("");
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
        // $('#bank').modal('hide');
       LoadNamesTbl1()
    }
  });
});
$('#save_edit').click(function() {
  


var form = $('#setbnkCode')[0];
var formData = new FormData(form);
formData.append('UpdateBankCode', true);

// updateCalculatedFields();

Swal.fire({
    title: 'Are you sure?',
    icon: 'question',
    text: 'Save Bank Account Setting.',
    showCancelButton: true,
    showLoaderOnConfirm: true,
    confirmButtonColor: '#435ebe',
    confirmButtonText: 'Yes, proceed!',
    allowOutsideClick: false,
    preConfirm: function() {
        return $.ajax({
            url: "fundings.process.php",
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
                  $('#bnknm').prop('disabled', true);
                  $('#bnkcd').prop('disabled', true);
                 
                 
                  $('#save_bnk').prop('disabled', true);
                  $('#save_bnk').show();
                  $('#save_edit').hide();
                  $('#cancel_bnk1').hide();
                  $('#update_bnk').show();
                  
                  $('#bnknm').val("");
                  $('#bnkcd').val("");
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
        // $('#bank').modal('hide');
       LoadNamesTbl1()
    }
  });
});
$('#deletebank').click(function() {

  var form = $('#fundAccts')[0];
  var formData = new FormData(form);
  formData.append('deleteFundAccts', true);

  // updateCalculatedFields();

  Swal.fire({
      title: 'Are you sure?',
      icon: 'question',
    text: 'Delete Fund Account Setting.',
      showCancelButton: true,
      showLoaderOnConfirm: true,
      confirmButtonColor: '#435ebe',
      confirmButtonText: 'Yes, proceed!',
      allowOutsideClick: false,
      preConfirm: function() {
          return $.ajax({
              url: "fundings.process.php",
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
                    
                    $('#bankname').val("");
                    $('#fund').val("");
                    $('#cvNo').val("");
                    $('#chkNo').val("");
                    $('#ck1').val("");
                    $('#ck2').val("");
                   
                    $('#editbank').prop('disabled', true);
                   
                   
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
          
       LoadNamesTbl()
      }
  });
});
$('#delete_bnk').click(function() {
  

  var form = $('#setbnkCode')[0];
  var formData = new FormData(form);
  formData.append('deleteBankCode', true);

  // updateCalculatedFields();

  Swal.fire({
      title: 'Are you sure?',
      icon: 'question',
      text: 'Delete Bank Code Setting.',
      showCancelButton: true,
      showLoaderOnConfirm: true,
      confirmButtonColor: '#435ebe',
      confirmButtonText: 'Yes, proceed!',
      allowOutsideClick: false,
      preConfirm: function() {
          return $.ajax({
              url: "fundings.process.php",
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
                    
                    $('#bnknm').val("");
                    $('#bnkcd').val("");
                    
                   
                    $('#update_bnk').prop('disabled', true);
                   
                   
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
          // $('#bank').modal('hide');
       LoadNamesTbl1()
      }
  });
});
});

    </script>
<script>
function getBankCode(bankname) {
  $.ajax({
    url:"fundings.process.php",
method:"GET",
data:{bankcodes : "ON", bankname:bankname},
dataType:"json",
success:function(response){
      var len = response.length;
      for(var i=0; i<len; i++) {
        var bankcode = response[i].bankcode;
        $("#bank_code").val(bankcode);
      }
    }
  });
}

</script>