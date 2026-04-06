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
              td {
                  font-weight: 400;
              }

              form {
                  width: 100%;
                  padding: 20px;
                  background-color: white;
                  border-radius: 10px;
              }

              label,
              th {
                  color: #090909;
              }

              main {
                  background-color: #EAEAF6;
              }

              th {
                  font-weight: bold;
                  color: #090909;
              }

              .custom-input {
                  border: none;
                  border-bottom: .1px solid gray;
                  outline: none;
                  width: 85px;
                  text-align: center;
                  margin-top: 20px;
              }

              .custom-input:focus {
                  border-bottom: 2px solid #0D6EFD;
              }
          </style>


          <!-- <script type="text/javascript" src="typeahead.js"></script> -->
          <div class="container mt-4">
              <div class=" shadow p-3 rounded-3" style="background-color: white;">
                  <p style="color: blue; font-weight: bold;" class="fs-5 my-2">Chart Of Accounts</p>
              </div>
              <div class="row mt-2">
                  <div class="container mt-3">
                      <div class="shadow p-3 rounded-3" style="background-color: white;">
                          <div class="row">
                              <div class="col-md-6">
                                  <p class="fw-medium fs-5" style="color: #090909;">General Accounts</p>
                              </div>
                              <div class="col-md-6">
                                  <div class="col-md-12 d-flex justify-content-end">
                                      <button class="btn btn-success px-3 py-2 mx-1" type="button" id ="addcode"name="addcode" title="Add" ><i class="fas fa-plus"></i> New</button>
                                      <button class="btn btn-primary float-end mx-2" type="button" name="editcode" id = "editcode" disabled><i class="fas fa-pen-to-square"></i> Edit</button>
                                      <button class="btn btn-danger px-3 py-2 mx-1" type="submit" name="deletecode" id = "deletecode" disabled><i class="fas fa-trash-can"></i> Delete</button>
                                  </div>
                              </div>
                          </div>
                          <hr style="height: 1px">
                          <div class="table-responsive">
                          <table id="glTbl" name="glTbl" style="width:100%" class="text-center table">
                      <thead>
                        <tr>
                          <th>Title</th>
                          <th>Account Number</th>
                          <th>SL Type</th>
                          <th>Normal Balance</th>
                        </tr>
                      </thead>
                      <tbody id="glList">
                    
                      </tbody>
                    </table>
                          </div>
                      </div>
                  </div>

                  <div class="container mt-4 mb-4">
                  
                      <div class="row">
                          <div class="col-md-6">
                              <div class="shadow p-3 rounded-3" style="background-color: white;">
                                  <div class="col-md-12">
                                      <p class="fw-medium fs-5" style="color: #090909;">Account Details</p>
                                      <hr style="height: 1px">
                                  </div>
                                  <div class="row">
                                  <form method = "POST" id="chartaccts">
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">Account No</label>
                                          <input type="text" id="acct_no" name="acct_no" class="inputHeight form-control" required readonly>
                                      </div>
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">Account Title</label>
                                          <input type="text" oninput="this.value=this.value.toUpperCase()" disabled id="acct_title" name="acct_title" class="inputHeight form-control" required>
                                      </div>
                                      <div class="row mt-2 mb-2">
                                          <div class="col-md-2">
                                              <label class="form-label mt-2">Has SL:</label>
                                          </div>
                                          <div class="col-md-3">
                                          <select id="sl1" name="sl1" class="form-select" required disabled>
                                <option></option>
                                <option value="YES">YES</option>
                                <option value="NO">NO</option>
                              </select>
                                          </div>
                                          <div class="col-md-4 ">
                                          <select id="type"  name="type" class="form-select" disabled>
                                <option value=""></option>
                              
                              </select>
                                          </div>
                                          <div class="col-md-3">
                                              <!-- <button class="btn btn-primary px-3 py-2 mx-1" type="button">
                                                  <i class="fa-solid fa-square-plus"></i> SLs</button> -->
                                                  <button type="button" id="addsls" name="addsls" title="Add" class="btn btn-primary px-3 py-2 mx-1" disabled><i class="fas fa-plus-square"> </i>SLs</button>
                                                  <button type="button" id="updatesls" name="updatesls" title="Update" class="btn btn-primary px-3 py-2 mx-1" style="display: none;"><i class="fas fa-plus-square"> </i>SLs</button>

                                          </div>
                                      </div>
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">Normal Balance</label>
                                          <select id="normal" name="normal" class="form-select" required disabled>
                                <option ></option>
                                <option >DEBIT</option>
                                <option >CREDIT</option>
                              </select>
                                      </div>
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">FS Type</label>
                                          <select id="fs" name="fs" class="form-select" required disabled>
                                <option ></option>
                                <option >BS</option>
                                <option >IS</option>
                              </select>
                                      </div>
                                      <div class="col-md-12 mt-4 d-flex justify-content-end ">
                                          <button class="btn btn-primary px-3 py-2 mx-1" type="submit" name="saveAcct" id="saveAcct" disabled>
                                              <i class="fa-solid fa-floppy-disk"></i> Save
                                          </button>
                                          <button class="btn btn-danger px-3 py-2 mx-1" type="button" name="cancelcode" id = "cancelcode" disabled>
                                              <i class="fa-regular fa-circle-xmark"></i> Cancel
                                          </button>
                                      </div>
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">Category</label>
                                          <select id="category" name="category" class="form-select" required disabled>
                                <option ></option>
                                
                              </select>
                                      </div>
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">Category No</label>
                                          <select id="c_no" name="c_no" class="form-select" required disabled>
                                <option ></option>
                                
                              </select>
                                      </div>
                                      <div class="col-md-12 mb-2">
                                          <label class="form-label">Category Type</label>
                                          <select id="c_type" name="c_type" class="form-select" required disabled>
                                <option></option>
                              
                              </select>
                                      </div>
                                      </form>
                                  </div>
                              </div>
                          </div>

                          <!-- Right Column for Account Details -->
                        
                          <div class="col-md-6">
                              <div class="shadow p-3 rounded-3" style="background-color: white;">
                                  <div class="col-md-12">
                                      <p class="fw-medium fs-5" style="color: #090909;">Subsidiaries</p>
                                      <hr style="height: 1px">
                                  </div>
                                  <form method = "POST" id="editslscde">
                                  <div class="row">
                                    
                                      <div class="col-md-1">
                                          <label for="" class="form-label">Type:</label>
                                      </div>
                                      <div class="col-md-4">
                                          <select class="slcodes form-select" aria-label="Default select example" id="slnm" name="slnm" required>
                                              <option value="">Select</option>
                                              
                                          </select>
                                          <input type="text" id="slsnm" name="slsnm" oninput="this.value=this.value.toUpperCase()" class="inputHeight form-control" style="display:none">
                                          <input type="text" id="slscde" name="slscde" oninput="this.value=this.value.toUpperCase()" class="inputHeight form-control" style="display:none">
                                      </div>
                                      <div class="col-md-7">
                                          <div class="col-md-12 d-flex justify-content-end">
                                              <button class="btn btn-success px-3 py-2 mx-1" type="button" id = "newsl"><i class="fas fa-plus"></i> New</button>
                                              <button class="btn btn-primary edit-row-button" type="button" name="editsl" id="editsl" disabled><i class="fas fa-pen-to-square"></i> Edit</button>
                                              <button class="btn btn-danger px-3 py-2 mx-1" type="reset" id="resetsl" name = "resetsl" disabled><i class="fas fa-circle-xmark"></i> Cancel</button>
                                          </div>
                                      </div>
                                      <table id="slTbl" name="slTbl" style="width:100%" class="text-center table">
                                      <thead>
                              <tr>
                                <th>Sub Code</th>
                                <th>Subsidiary Name</th>
                              </tr>
                            </thead>
                            <tbody id= "slList">
                            
                            </tbody>           
                          </table>

                                  <div class="col-sm-12" >
                              <!-- <button type="button" name="savesls1" id="savesls1"  title="Save" data-toggle="modal" data-target="#subs1" class="btn btn-secondary text-white" form="option" style="float:right" disabled><i class="fas fa-pen-square" > Edit</i></button> -->
                              <button type="button" name="addsub" id="addsub" title="Add" data-toggle="modal" data-target="#subs" class="btn btn-success px-3 py-2 mx-1" form="option" style="float:right" disabled><i class="fas fa-plus-square" > </i>Add</button>
                            </div>                        </div>
                              </div>
                          </div>
                      </div>
                    
                  </div>
              </div>
          </div>

          <div class="modal fade" id="subs" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
              <div class="modal-dialog" style = "margin-left:600px;margin-top:200px" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h6 class="modal-title" id="exampleModalLongTitle"><b>New SL</b><h6>
                    <button type="button" class="close" aria-label="Close" data-dismiss  = "modal">
                      <span aria-hidden="true">&times;</span>
                    </button>
                  </div>
                  <div class="modal-body" >
                    <form method="POST" id = "newSubsidiary">         
                      <div class="row">
                        <input oninput="this.value=this.value.toUpperCase()" id="sl_category" required name="sl_category" type="hidden" class="inputHeight form-control">
                        <div class="form-group">
                          <b><label for="track">Enter Subsidiary Name</label></b>
                          <br/>
                        </div>
                        <div class="inputHeight form-group">
                          <input oninput="this.value=this.value.toUpperCase()" id="subname" required name="subname" type="text" class="inputHeight form-control">                
                    
                        </div>
                      </div>
                      <div class="row">
                        <div class="form-group">
                          <b><label for="track">Enter Subsidiary Code</label></b>
                          <br/>
                        </div>
                        <div class="inputHeight form-group">
                          <input oninput="this.value=this.value.toUpperCase()" id="subslcode" required name="subslcode" type="text" class="inputHeight form-control"  />
                        </div>
                      </div>
                    </form>
                  </div>
                  <div class="modal-footer">
                    <button type="button" name="saveSLCode" id="saveSLCode"  title="Save" class="btn btn-primary text-white" form="newSubsidiary"><i class="fas fa-floppy-o"></i> Save
                    <button type="button" class="btn btn-danger" title="Cancel" data-dismiss="modal"><i class="fas fa-ban"> </i>Cancel</button>
                  </div>
                </div>
              </div>
            </div>


          <!--General Accounts -->

          <!-- Modal for adding/editing-->
          <div class="modal fade" id="allowedslsMDL" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h6 class="modal-title" id="exampleModalLongTitle"><b>Allowed1 SLs for</b><h6>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <form method="POST" id="allowedSL1">
                    <div class="row">
                      <div class="col-sm-12">
                        <div class="row">
                          <div class="col-sm-7">
                            <select id="allowedsubname" required name="allowedsubname" class="slcodes form-select" >
                              <option readonly></option>
                            </select>
                            <input type="hidden" id="allowedaccts" name="allowedaccts" class="inputHeight form-control" readonly>
                          </div>
                          <div class="col-sm-5">
                            <button type="button" id="addallowSLS" name="addallowSLS" title="Add" class="btn btn-primary btn-sm"><i class="fas fa-plus-square"> </i>Add</button>
                            <button type="button" id="deleteSLS" name="deleteSLS" title="Delete" class="btn btn-danger btn-sm" disabled><i class="fas fa-trash"> </i>Delete</button>
                          </div>
                        </div>
                      </div>
                      <div class="col-sm-12 my-1">
                        <!-- <div style="display: block;"> -->
                          <table id="slsTbl" name="slsTbl" style="width:100%" class="table text-center" >       
                            <thead>
                              <tr>
                                <th>SL List</th>
                              </tr>
                            </thead>
                            <tbody id ="slsList">
                          
                            </tbody>           
                          </table>
                        <!-- </div> -->
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

<script>
  $(document).ready(function () {
    $("#addsub").click(function () {  
        var slsnm = $("#slsnm").val(); 
        
          gnrtSID1(slsnm);
        
        $("#sl_category").val(slsnm);
    });
  });
</script> 

<script>
  $(document).ready(function(){
    $('#slsnm').typeahead({
      source: function(query, process){
        $.ajax({
          url: "chartofaccts.php",
          method: "POST",
          data: {query: query},
          dataType: "json",
          success: function(data) {
            process($.map(data, function(item) {
              return item;  // Adjust if needed to return label/value pairs.
            }));
          }, 
          error: function(xhr, status, error) {
            console.error(xhr.responseText);
          }
        });
      }
    });
  });

</script>

<script>
  function gnrtSID1(slsnm){
    $.ajax({
      url: 'chartofaccts.process.php',
      type: 'POST',
      data: {gnrtSID1: "ON", slsnm:slsnm},
      dataType: 'JSON',
      beforeSend: function() {
        console.log(`Generating Series No...`)
      },
      success: function(response) {
        $('#subslcode').val(response.IDNO);
      },
    });
  }
</script>

<script>
  function gnrtSID2(slsnm){
    $.ajax({
      url: 'chartofaccts.process.php',
      type: 'POST',
      data: {gnrtSID2: "ON", slsnm:slsnm},
      dataType: 'JSON',          
      success: function(response) {
        $('#subslcode1').show();
        $('#subslcode1').hide();
      },
    });
  }
</script>

<script type="text/javascript">
  $(document).ready(function () {

    var glTbl, slTbl, allowedslsTbl, selectedSls, selectedSlsTblData;

    var acctDetailStat;

    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });


    LoadAccountCode();
    Initialize('None');
    LoadAllowedSLSTbl();

    function Initialize (sltype) {
      $.ajax({
        url: "chartofaccts.process.php",
        type: "POST",
        data: {Initialize:"ON"},
        dataType: 'JSON',
        beforeSend: function() {
          $(".slcodes").empty();
        },
        success: function(response) {

        $(".slcodes").append("<option value=''></option>");
        $.each(response.SUBCODE, function(key,value){
        $(".slcodes").append("<option value='"+ value["CATEGORY"]+"'>"+value["CATEGORY"]+"</option>");
        });

        if (sltype != "none") {
          $('#slnm').val(sltype);
        }


        },
        error: function(err) {
            console.log(err);
        }
      });
    }

    function LoadAllowedSLSTbl (){      
      allowedslsTbl = $("#slsTbl").DataTable({
        searching: false,
        ordering: false,
        paging: false,
        bFilter: false,
        info: false,
        // scrollY: '500px',
        // scrollX: true,
        scrollCollapse: true,
        responsive: true,
      });
    }

    function LoadAccountCode () {
      $.ajax({
          url:"chartofaccts.process.php",
          type:"POST",
          data:{LoadAccountCode:"ON"},
          dataType:"JSON",
          beforeSend:function(e){
            $("#glList").empty();
            $("#glList").append("<tr><td colspan='10'>Loading..</td></tr>");
          },
          success:function(response){
              if ( $.fn.DataTable.isDataTable( '#glTbl' ) ) {
                  $('#glTbl').DataTable().clear();
                  $('#glTbl').DataTable().destroy();
              }

              $("#glList").empty();
              $.each(response.LIST,function(key,value){
                  $("#glList").append("<tr><td>" + value["acctitles"] + "</td>" + "<td>" + value["acctcodes"] + "</td>" + "<td>" + value["slname"] + "</td>" + "<td>" + value["normalbal"] + "</td></tr>");
                  
              })

              glTbl = $('#glTbl').DataTable({
                searching: true,
                scrollY: '30vh',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                bFilter:false,
                info:false,
            })
          }
      })
     
    };

    // ============================================================

    $("#addsls").click(function () {      
      $('#allowedslsMDL').modal('show');
      var slsnm = $("#acct_no").val();  
        
      $("#allowedaccts").val(slsnm);

    });

    $("#updatesls").click(function () {
      $('#allowedslsMDL').modal('show');
      var slsnm = $("#acct_no").val();  
      $("#allowedaccts").val(slsnm);        
    });

    $('#slsTbl tbody').on('click', 'tr',function(){
      $("#slsTbl tbody tr").removeClass("selected");
      $(this).addClass("selected");
      selectedSlsTblData = this;

      var rowData = allowedslsTbl.row(this).data();
      
      selectedSls = rowData[0];

      $("#deleteSLS").prop('disabled', false);
    });

    $("#addallowSLS").click(function () {      
      var allowedsubname = $("#allowedsubname").val();
      var allowedaccts = $("#allowedaccts").val();

      if (allowedaccts == "") {
        Swal.fire({
          icon: "warning",
          title: "Warning!",
          text: "You have not entered an account no., Go Back",
        });
      } else {
        allowedslsTbl.row.add([allowedsubname]).draw(false);

        $.ajax({
          url: 'chartofaccts.process.php',
          method: 'POST',
          data: { addSls: "ON", slname : allowedsubname, accts : allowedaccts},
          dataType: 'JSON',
          success: function(response) {
            if (response.STATUS == "SUCCESS") {
              Toast.fire({
                icon: "success",
                title: response.MESSAGE,
              });
            } else {
              Toast.fire({
                icon: "success",
                title: response.MESSAGE,
              });
            }
          }
        });
      }
    });

    $("#deleteSLS").click(function () {
      var allowedacctsDel = $("#allowedaccts").val();

      if (allowedacctsDel == "") {
        Swal.fire({
          icon: "warning",
          title: "Warning!",
          text: "You have not entered an account no., Go Back",
        });
      } else if (selectedSls =="") {
        Swal.fire({
            icon:"warning",
            text:"Please select SLs to delete"
        })
      } else {
        allowedslsTbl.row(selectedSlsTblData).remove().draw(false);
        $("#deleteSLS").prop('disabled', true);

        $.ajax({
          url: 'chartofaccts.process.php',
          method: 'POST',
          data: { removeSls: "ON", slname : selectedSls, accts : allowedacctsDel},
          dataType: 'JSON',
          success: function(response) {
            if (response.STATUS == "SUCCESS") {
              Toast.fire({
                icon: "success",
                title: response.MESSAGE,
              });
            } else {
              Toast.fire({
                icon: "success",
                title: response.MESSAGE,
              });
            }
          }
        });
      }
    });

    

    // ============================================================
    $('#addcode').on('click', function() {
      allowedslsTbl.clear().draw();
      acctDetailStat = "AddDetail"
      $('#acct_no').prop('readonly', false);
      $('#acct_title').prop('disabled', false);
      $('#sl1').prop('disabled', false);
      $('#type').prop('disabled', false);
      $('#normal').prop('disabled', false);
      $('#fs').prop('disabled', false);
      $('#category').prop('disabled', false);
      $('#c_no').prop('disabled', false);
      $('#c_type').prop('disabled', false);
      // $('#save').hide();
      // $('#saveSub').show();
      $('#saveAcct').prop('disabled', false);
      $('#cancelcode').prop('disabled', false);
      $('#editcode').prop('disabled', true);
      $('#deletecode').prop('disabled', true);
      $('#addsls').prop('disabled', false);
      $('#addsls').show();
      $('#updatesls').hide();
      $('#editsl').prop('disabled', true);
      
      $('#chartaccts')[0].reset();
      $('#glTbl tbody tr').removeClass('selected');

      $('#slnm').val("");
      $('#slList').empty();
    });

    $('#editcode').on('click', function() {
      acctDetailStat = "EditDetail"
      $('#acct_no').prop('readonly', true);
      $('#acct_title').prop('disabled', false);
      $('#sl1').prop('disabled', false);
      $('#type').prop('disabled', false);
      $('#normal').prop('disabled', false);
      $('#fs').prop('disabled', false);
      $('#category').prop('disabled', false);
      $('#c_no').prop('disabled', false);
      $('#c_type').prop('disabled', false);
      // $('#save').prop('disabled', false);
      $('#saveAcct').prop('disabled', false);
      $('#cancelcode').prop('disabled', false);
      $('#editcode').prop('disabled', true);
      $('#addcode').prop('disabled', true);
      $('#deletecode').prop('disabled', true);
      $('#addsls').hide();
      $('#updatesls').show();
    });

    $('#cancelcode').on('click', function() {
      allowedslsTbl.clear().draw();
      acctDetailStat = "";
      $('#acct_no').prop('readonly', true);
      $('#acct_title').prop('disabled', true);
      $('#sl1').prop('disabled', true);
      $('#type').prop('disabled', true);
      $('#normal').prop('disabled', true);
      $('#fs').prop('disabled', true);
      $('#category').prop('disabled', true);
      $('#c_no').prop('disabled', true);
      $('#c_type').prop('disabled', true);
      // $('#save').prop('disabled', true);
      $('#saveAcct').prop('disabled', true);
      $('#cancelcode').prop('disabled', true);
      $('#editcode').prop('disabled', true);
      $('#deletecode').prop('disabled', true);
      $('#addsls').prop('disabled', true);
      $('#addcode').prop('disabled', false);
      // $('#saveSub').hide();
      // $('#save').show();
      $('#updatesls').hide();
      $('#addsls').show();        
      $('#editsl').prop('disabled', true);

      $('#chartaccts')[0].reset();
      $('#glTbl tbody tr').removeClass('selected');
      
      $('#slnm').val("");
      $("#slList").empty();
    });

    $('#newsl').on('click', function() {
      $('#slsnm').show();
      $('#slnm').hide();
      $('#savesl').prop('disabled', false);
      $('#editsl').prop('disabled', true);
      // $('#deleteSubsidiaryButton').prop('disabled', false);
      $('#newsl').prop('disabled', true);
      $('#resetsl').prop('disabled', false);
      $('#addsub').prop('disabled', false);
      $('#slnm').val("");
      $("#slList").empty();
      $('#slsnm').val("");
      $('#glTbl tbody tr').removeClass('selected');
    });

    $('#resetsl').on('click', function() {
      $('#slsnm').hide();
      $('#slnm').show();
      $('#savesl').prop('disabled', true);
      $('#resetsl').prop('disabled', true);
      $('#newsl').prop('disabled', false);
      $('#editsl').prop('disabled', false);
      $('#addsub').prop('disabled', true);
       
    });

    $('#editsl').on('click', function() {
      $('#slnm').hide();
      var selSLType = $('#slnm').val();
      $('#slsnm').show();
      $('#slsnm').val(selSLType);
      $('#newsl').prop('disabled', true);
      $('#editsl').prop('disabled', true);
      $('#addsub').prop('disabled', false);
      // $('#savesl').prop('disabled', false);
      $('#resetsl').prop('disabled', false);
    });

    $('#slnm').on('change', function() {
      var slnm = $(this).val();

      $.ajax({
        url: 'chartofaccts.process.php',
        method: 'POST',
        data: { slsname: "ON", slnm : slnm},
        dataType: 'JSON',
        beforeSend:function(e){
          $("#slList").empty();
          $("#slList").append("<tr><td colspan='10'>Loading..</td></tr>");
        },
        success:function(response){
          if ($.fn.DataTable.isDataTable('#slTbl')){
              $('#slTbl').DataTable().clear();
              $('#slTbl').DataTable().destroy();
          }

          $('#editsl').prop('disabled', false);

          $("#slList").empty();
          $.each(response.LIST,function(key,value){
            $("#slList").append("<tr><td>" + value["SUBCODE"]  + "</td><td>" + value["SUBNAME"] + "</td></tr>");
          });

          slTbl = $('#slTbl').DataTable({
            
                scrollY: '20vh',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                bFilter:false,
                info:false,
          })
        }
      })
    });

    // ============================================================
      // Retrieves info for the Account Details 

    $('#glTbl tbody').on('click', 'tr', function() {
      // Remove the 'selected' class from all rows
      $('#glTbl tbody tr').removeClass('selected');

      // Add the 'selected' class to the clicked row
      $(this).addClass('selected');
      $('#acct_no').prop('readonly', true);
      $('#acct_title').prop('disabled', true);
      $('#sl1').prop('disabled', true);
      $('#type').prop('disabled', true);
      $('#normal').prop('disabled', true);
      $('#fs').prop('disabled', true);
      $('#category').prop('disabled', true);
      $('#c_no').prop('disabled', true);
      $('#c_type').prop('disabled', true);
      // $('#save').prop('disabled', true);
      $('#saveAcct').prop('disabled', true);
      $('#cancelcode').prop('disabled', true);
      $('#editcode').prop('disabled', false);
      $('#deletecode').prop('disabled', false);
      $('#deletecode').prop('disabled', false);
      $('#editsl').prop('disabled', false);
      $('#addsls').prop('disabled', true);
      $('#newsl').prop('disabled', false);
      $('#savesl').prop('disabled', true);
      $('#resetsl').prop('disabled', true);
      $('#addsub').prop('disabled', true);

      $('#updatesls').hide();
      $('#addsls').prop('disabled', true);
      $('#addsls').show();
      // $('#saveSub').hide();
      // $('#save').show();

      var rowData = glTbl.row(this).data();
      var acct_codes = rowData[1];
      var acct_title = rowData[0];
      var type = rowData[2];
      
        // Get the value from the first column
      $('#acct_no').val(acct_codes);
      $('#acct_title').val(acct_title);
      $('#type').val(type);
      
      // Make an AJAX request to fetch data based on the row ID
      $.ajax({
        url: 'chartofaccts.process.php',
        method: 'POST',
        data: { getAcctCodes: "ON", acct_codes : acct_codes },
        dataType: 'JSON',
        success: function(response) {
          console.log(response.LIST.acctcodes);
            $('#acc_title').val(response.LIST.acctitles);
            $('#acct_no').val(response.LIST.acctcodes);
            $('#sl1').val(response.LIST.sl);
            $('#slnm').val(response.LIST.slname);
            $('#type').val(response.LIST.slname);
            $('#normal').val(response.LIST.normalbal);
            $('#fs').val(response.LIST.fstype);
            $('#category').val(response.LIST.category);
            $('#c_no').val(response.LIST.categoryno);
            $('#c_type').val(response.LIST.categorytype);
        }
      });
    });
    
    // ============================================================

    $('#glTbl tbody').on('click', 'tr', function() {
      // Remove the 'selected' class from all rows
      $('#glTbl tbody tr').removeClass('selected');

      // Add thaddsube 'selected' class to the clicked row
      $(this).addClass('selected');

      var rowData = glTbl.row(this).data();
      
      var slsnm = rowData[2];
      var slscde = rowData[1];
      // var acct_code = rowData[1];
    
      // Get the value from the first column
      
      $('#type').val(slsnm);
      $('#slsnm').val(slsnm);
      $('#slscde').val(slscde);
      //  $('#acct_code').val(acct_code);
      
      // Make an AJAX request to fetch data based on the row ID
      reloadSlCodes(slsnm);

    });

    // Fetch SL Code information ===================================
    function reloadSlCodes (slsnm){
      $.ajax({
        url: 'chartofaccts.process.php',
        method: 'POST',
        data: { slCodes: "ON", slsnm : slsnm },
        dataType: 'JSON',
        beforeSend:function(e){
          $("#slList").empty();
          $("#slList").append("<tr><td colspan='10'>Loading..</td></tr>");
        },
        success:function(response){            
          if ( $.fn.DataTable.isDataTable( '#slTbl' ) ) {
              $('#slTbl').DataTable().clear();
              $('#slTbl').DataTable().destroy();
          }

          $("#slList").empty();
          if (response.EMPTY == "EMPTY")
            $("#slList").append("<tr><td colspan='2'>No Data to display...</td></tr>");
          $.each(response.LIST,function(key,value){
              $("#slList").append("<tr><td>" + value["SUBCODE"] + "</td>" + "<td>" + value["SUBNAME"] + "</td></tr>");
              // $('#acct_code').val(response.LIST.AccountCode)
          })

        }
      });
    };

    // Load Allowed sls ===========================================
    $('#glTbl tbody').on('click', 'tr', function() {
      // Remove the 'selected' class from all rows
      $('#glTbl tbody tr').removeClass('selected');

      // Add the 'selected' class to the clicked row
      $(this).addClass('selected');
      
      var rowData = glTbl.row(this).data();
      
      var acct_codes = rowData[1];
        // Get the value from the first column
      
      $('#acct_code').val(acct_codes);
      
      // Make an AJAX request to fetch data based on the row ID
      $.ajax({
        url: 'chartofaccts.process.php',
        method: 'POST',
        data: { slsnames: "ON", acct_codes : acct_codes},
        dataType: 'JSON',
        beforeSend:function(e){
          $("#slsList").empty();
          $("#slsList").append("<tr><td colspan='10'>Loading..</td></tr>");
        },
        success:function(response){

          allowedslsTbl.clear().draw();
          
          if (response.EMPTY != "EMPTY") {
            $('#acct_code').val(response.LIST.AccountCode)
  
            $("#slsList").empty();
            $.each(response.LIST,function(key,value){
              allowedslsTbl.row.add([value["SLName"]]).draw(false);
            })
          } else {
            $("#slsList").empty();
          }

        }
      });

    });

    // DO NOT DELETE
    // $('#slTbl tbody').on('click', 'tr', function() {
    //   // Remove the 'selected' class from all rows
    //   $('#slTbl tbody tr').removeClass('selected');

    //   // Add the 'selected' class to the clicked row
    //   $(this).addClass('selected');
    //   $('#savesls1').prop('disabled', false);

    //   var rowData = slTbl.row(this).data();
      
    //   var subcode = rowData[0];
    //   var subname = rowData[1];
      
    //   $('#subcode1').val(subcode);
    //   $('#subname1').val(subname);
      
    //   // Make an AJAX request to fetch data based on the row ID
    //   $.ajax({
    //     url: 'chartofaccts.process.php',
    //     method: 'POST',
    //     data: { getSubCode: "ON", subcode : subcode },
    //     dataType: 'JSON',
    //     success: function(response) {
    //       console.log(response.LIST.SUBCODE);
    //         $('#subcode1').val(response.LIST.SUBCODE);
    //         $('#subname1').val(response.LIST.SUBNAME);
    //     }
    //   });
    // });

    // =====================================================================================
    // Save
  
    // $('#save'). click(function(e) {

    //   // let allowedslsData = allowedslsTbl.rows().data().toArray();

    //   var form = $('#chartaccts')[0];
    //   var formData = new FormData(form);
    //   formData.append('saveAcctCodes', true);
    //   // formData.append('allowedsls', allowedslsData);
    
    //   Swal.fire({
    //       title: 'Are you sure?',
    //       icon: 'question',
    //       text: 'Save Account Code Setting.',
    //       showCancelButton: true,
    //       showLoaderOnConfirm: true,
    //       confirmButtonColor: '#435ebe',
    //       confirmButtonText: 'Yes, proceed!',
    //       allowOutsideClick: false,
    //       preConfirm: function() {
    //           return $.ajax({
    //               url: "chartofaccts.process.php",
    //               type: "POST",
    //               data: formData,
    //               processData: false,
    //               contentType: false,
    //               dataType: 'JSON',
    //               beforeSend: function() {
    //                   console.log('Processing Request...')
    //               },
    //               success: function(response) {
    //                   if (response.STATUS == 'SUCCESS') {
    //                     console.log('Request Processed...')
    //                     $('#acct_no').prop('readonly', true);
    //                     $('#acct_title').prop('disabled', true);
    //                     $('#sl1').prop('disabled', true);
    //                     $('#type').prop('disabled', true);
    //                     $('#normal').prop('disabled', true);
    //                     $('#fs').prop('disabled', true);
    //                     $('#category').prop('disabled', true);
    //                     $('#c_no').prop('disabled', true);
    //                     $('#c_type').prop('disabled', true);
    //                     $('#save').prop('disabled', true);
    //                     $('#save').show();
    //                     $('#saveSub').hide();
    //                     $('#editcode').prop('disabled', true);
    //                     $('#deletecode').prop('disabled', true);
    //                     $('#cancelcode').prop('disabled', true);
    //                     $('#addsls').prop('disabled', true);
    //                     $('#updatesls').hide();
    //                     $('#addsls').show();
    //                     $('#glTbl tbody tr').removeClass('selected');
    //                   }
    //               },
    //               error: function(err) {
    //                   console.log(err);
    //               }
    //           });
    //       },
    //   }).then(function(result) {
    //       if (result.isConfirmed) {
    //           if (result.value.STATUS == 'SUCCESS') {
    //               Swal.fire({
    //                 icon: "success",
    //                 text: result.value.MESSAGE,
    //             });
    //           } else if (result.value.STATUS != 'SUCCESS') {
    //               Swal.fire({
    //                   icon: "warning",
    //                   text: result.value.MESSAGE,
    //               });
    //           }
    //       LoadNamesTbl();
    //       }
    //   });
    // });

    // $('#saveSub').click(function() {

    //   // let allowedslsData = allowedslsTbl.rows().data().toArray();

    //   var form = $('#chartaccts')[0];
    //   var formData = new FormData(form);
    //   formData.append('saveSubCode', true);
    //   // formData.append('allowedsls', allowedslsData);

    //   Swal.fire({
    //       title: 'Are you sure?',
    //       icon: 'question',
    //       text: 'Save Account Code Setting.',
    //       showCancelButton: true,
    //       showLoaderOnConfirm: true,
    //       confirmButtonColor: '#435ebe',
    //       confirmButtonText: 'Yes, proceed!',
    //       allowOutsideClick: false,
    //       preConfirm: function() {
    //           return $.ajax({
    //               url: "chartofaccts.process.php",
    //               type: "POST",
    //               data: formData,
    //               processData: false,
    //               contentType: false,
    //               dataType: 'JSON',
    //               beforeSend: function() {
    //                   console.log('Processing Request...')
    //               },
    //               success: function(response) {
    //                   if (response.STATUS == 'SUCCESS') {
    //                     console.log('Request Processed...')
    //                     $('#acct_no').prop('readonly', true);
    //                     $('#acct_title').prop('disabled', true);
    //                     $('#sl1').prop('disabled', true);
    //                     $('#type').prop('disabled', true);
    //                     $('#normal').prop('disabled', true);
    //                     $('#fs').prop('disabled', true);
    //                     $('#category').prop('disabled', true);
    //                     $('#c_no').prop('disabled', true);
    //                     $('#c_type').prop('disabled', true);
    //                     $('#save').prop('disabled', true);
    //                     $('#save').show();
    //                     $('#saveSub').hide();
    //                     $('#editcode').prop('disabled', true);
    //                     $('#deletecode').prop('disabled', true);
    //                     $('#cancelcode').prop('disabled', true);
    //                     $('#addsls').prop('disabled', true);
    //                     $('#updatesls').hide();
    //                     $('#addsls').show();
    //                     $('#glTbl tbody tr').removeClass('selected');
    //                   }
    //               },
    //               error: function(err) {
    //                   console.log(err);
    //               }
    //           });
    //       },
    //   }).then(function(result) {
    //       if (result.isConfirmed) {
    //           if (result.value.STATUS == 'SUCCESS') {
    //               Swal.fire({
    //                 icon: "success",
    //                 text: result.value.MESSAGE,
    //             });
    //           } else if (result.value.STATUS != 'SUCCESS') {
    //               Swal.fire({
    //                   icon: "warning",
    //                   text: result.value.MESSAGE,
    //               });
    //           }
    //       LoadNamesTbl();
    //       }
    //   });
    // });

    $('#chartaccts').submit(function(event) {
      event.preventDefault();

      var form = $('#chartaccts')[0];
      var formData = new FormData(form);

      if (acctDetailStat == "AddDetail") {

        formData.append('saveSubCode', true);

        Swal.fire({
          title: 'Are you sure?',
          icon: 'question',
          text: 'Add New Account Code Setting.',
          showCancelButton: true,
          showLoaderOnConfirm: true,
          confirmButtonColor: '#435ebe',
          confirmButtonText: 'Yes, proceed!',
          allowOutsideClick: false,
          preConfirm: function() {
            return $.ajax({
              url: "chartofaccts.process.php",
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
                  console.log('Request Processed...');                  
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

              $('#acct_no').prop('readonly', true);
              $('#acct_title').prop('disabled', true);
              $('#sl1').prop('disabled', true);
              $('#type').prop('disabled', true);
              $('#normal').prop('disabled', true);
              $('#fs').prop('disabled', true);
              $('#category').prop('disabled', true);
              $('#c_no').prop('disabled', true);
              $('#c_type').prop('disabled', true);
              // $('#save').prop('disabled', true);
              $('#saveAcct').prop('disabled', true);
              // $('#save').show();
              // $('#saveSub').hide();
              $('#editcode').prop('disabled', true);
              $('#deletecode').prop('disabled', true);
              $('#cancelcode').prop('disabled', true);
              $('#addsls').prop('disabled', true);
              $('#updatesls').hide();
              $('#addsls').show();
              $('#glTbl tbody tr').removeClass('selected');
            } else if (result.value.STATUS != 'SUCCESS') {
              Swal.fire({
                icon: "warning",
                text: result.value.MESSAGE,
              });
            }
            LoadAccountCode();
          }
        });
        
      } else if (acctDetailStat == "EditDetail") {

        formData.append('saveAcctCodes', true);
      
        Swal.fire({
          title: 'Are you sure?',
          icon: 'question',
          text: 'Save Account Code Setting.',
          showCancelButton: true,
          showLoaderOnConfirm: true,
          confirmButtonColor: '#435ebe',
          confirmButtonText: 'Yes, proceed!',
          allowOutsideClick: false,
          preConfirm: function() {
            return $.ajax({
              url: "chartofaccts.process.php",
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

              $('#acct_no').prop('readonly', true);
              $('#acct_title').prop('disabled', true);
              $('#sl1').prop('disabled', true);
              $('#type').prop('disabled', true);
              $('#normal').prop('disabled', true);
              $('#fs').prop('disabled', true);
              $('#category').prop('disabled', true);
              $('#c_no').prop('disabled', true);
              $('#c_type').prop('disabled', true);
              // $('#save').prop('disabled', true);
              $('#saveAcct').prop('disabled', true);
              // $('#save').show();
              // $('#saveSub').hide();
              $('#editcode').prop('disabled', true);
              $('#deletecode').prop('disabled', true);
              $('#cancelcode').prop('disabled', true);
              $('#addsls').prop('disabled', true);
              $('#updatesls').hide();
              $('#addsls').show();
              $('#glTbl tbody tr').removeClass('selected');
              
            } else if (result.value.STATUS != 'SUCCESS') {
              Swal.fire({
                icon: "warning",
                text: result.value.MESSAGE,
              });
            }
            LoadAccountCode();
          }
        });

      }

    });

    $(document).on('click', '#deletecode', function() {
      // $('#deletecode').click(function() {
      var form = $('#chartaccts')[0];
      var formData = new FormData(form);
      formData.append('deleteAcctCode', true);

      Swal.fire({
          title: 'Are you sure?',
          icon: 'question',
          text: 'Delete Account Code Setting.',
          showCancelButton: true,
          showLoaderOnConfirm: true,
          confirmButtonColor: '#435ebe',
          confirmButtonText: 'Yes, proceed!',
          allowOutsideClick: false,
          preConfirm: function() {
            return $.ajax({
              url: "chartofaccts.process.php",
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
                  
                  $('#acct_no').val("");
                  $('#acct_title').val("");
                  $('#sl1').val("");
                  $('#type').val("");
                  $('#normal').val("");
                  $('#fs').val("");
                  $('#category').val("");
                  $('#c_no').val("");
                  $('#c_type').val("");
                  $('#slnm').val("");
                  $('#slsnm').val("");
                  // $('#save').prop('disabled', true);
                  $("#slList").empty();
                  $('#editsl').prop('disabled', true);
                  $('#resetsl').prop('disabled', true);
                  $('#addsub').prop('disabled', true);
                  $('#editcode').prop('disabled', true);
                  $('#deletecode').prop('disabled', true);
                  $('#saveAcct').prop('disabled', true);
                  // $('#saveSub').hide();
                  $('#cancelcode').prop('disabled', true);                    
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
              LoadAccountCode();
          }
      });
    });
    // ==================================================

    $(document).on('click', '#saveSLName', function() {
      
      var form = $('#editSubs1')[0];
      var formData = new FormData(form);
      formData.append('editslnm', true);

      Swal.fire({
          title: 'Are you sure?',
          icon: 'question',
          text: 'Save Account Code Setting.',
          showCancelButton: true,
          showLoaderOnConfirm: true,
          confirmButtonColor: '#435ebe',
          confirmButtonText: 'Yes, proceed!',
          allowOutsideClick: false,
          preConfirm: function() {
              return $.ajax({
                  url: "chartofaccts.process.php",
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
                        $('#subname1').prop('disabled', true);
                      
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
            $('#editSubs1').modal('hide');
          }
      });
    });

    //====================================================
    
    // DO NOT DELETE
    // $('#savesl').click(function(e) {
    //   e.preventDefault();

    //   var form = $('#editslscde')[0];
    //   var formData = new FormData(form);
    //   formData.append('updateSL', true);
    //   $.ajax({
    //     url: "chartofaccts.process.php",
    //     type: "POST",
    //     data: formData,
    //     processData: false,
    //     contentType: false,
    //     dataType: 'JSON',
    //     beforeSend: function() {
    //         console.log('Processing Request...')
    //     },
    //     success: function(response) {
    //       if (response.STATUS == 'SUCCESS') {
    //         Toast.fire({
    //           icon: "success",
    //           title: response.MESSAGE,
    //         });
    //       }
    //       LoadNamesTbl();
    //       Initialize('None');
    //       $('#slsnm').hide();
    //       $('#slsnm').val("");
    //       $('#slnm').show();
    //       $('#slnm').val("");
    //       $('#newsl').prop('disabled', false);
    //       $('#editsl').prop('disabled', true);
    //       $('#savesl').prop('disabled', true);
    //       $('#resetsl').prop('disabled', true);
    //     },
    //     error: function(err) {
    //         console.log(err);                  
    //     }
    //   });
    // });



    // $('#updateslCode').click(function(e) {
    //   e.preventDefault();

    //   var form = $('#allowedSL1')[0];
    //   var formData = new FormData(form);
    //   formData.append('addSubCode', true);

    //   $.ajax({
    //     url: "chartofaccts.process.php",
    //     type: "POST",
    //     data: formData,
    //     processData: false,
    //     contentType: false,
    //     dataType: 'JSON',
    //     beforeSend: function() {
    //         console.log('Processing Request...')
    //     },
    //     success: function(response) {
    //       if (response.STATUS == 'SUCCESS') {
    //         console.log('Request Processed...')
    //         $('#acct_no').prop('readonly', false);
    //         $('#acct_title').prop('disabled', false);
    //         $('#sl1').prop('disabled', false);
    //         $('#type').prop('disabled', false);
    //         $('#normal').prop('disabled', false);
    //         $('#fs').prop('disabled', false);
    //         $('#category').prop('disabled', false);
    //         $('#c_no').prop('disabled', false);
    //         $('#c_type').prop('disabled', false);
    //         $('#save').hide();
    //         $('#saveSub').show();
    //         $('#cancelcode').prop('disabled', false);
    //       }
    //       $('#allowedslsMDL').modal('hide');
    //       LoadNamesTbl();                 
    //     },
    //     error: function(err) {
    //       console.log(err);  
    //     }
    //   });
    // });

    $(document).on('click', '#saveSLCode', function(e) {
      e.preventDefault();

      var form = $('#newSubsidiary')[0];
      var formData = new FormData(form);
      formData.append('NewSLCodes', true);

      var slcateg = formData.get("sl_category");

      if (formData.get("sl_category").trim().length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text: "You Forgot to enter an SL Type, Go back to subsidiaries!)",
        });
      } else if (formData.get("subname").trim().length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text: "Enter Subsidiary Name!)",
        });
      } else if (formData.get("subslcode").trim().length === 0 || isNaN(formData.get("subslcode"))) {
        Swal.fire({
            icon: "warning",
            title: "Warning!",
            text: "Enter Subsidiary Code/Invalid Subsidiary Code)",
        });
      } else {
        $.ajax({
          url: 'chartofaccts.process.php',
          method: 'POST',
          data: { getSubCode: "ON", subcode : formData.get("subslcode") },
          dataType: 'JSON',
          success: function(response) {
            if (response.ISTHERE == "EXIST") {
              Swal.fire({
                icon: "warning",
                title: "Warning!",
                text: "Subsidiary code already in use.",
              });
            } else {

              $.ajax({
                url: 'chartofaccts.process.php',
                method: 'POST',
                data: { getSubName: "ON", subname : formData.get("subname"), subtype : formData.get("sl_category")},
                dataType: 'JSON',
                success: function(response) {
                  if (response.ISTHERE == "EXIST"){
                    Swal.fire({
                      icon: "warning",
                      title: "Warning!",
                      text: "Enter new subsidiary name.",
                    });
                  } else {
                    $.ajax({
                      url: "chartofaccts.process.php",
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
                          Toast.fire({
                            icon: "success",
                            title: response.MESSAGE,
                          });
                          $('#sl_category').val("");
                          $('#subname').val("");
                          $('#subslcode').val("");

                          Initialize(slcateg);
                          $('#slsnm').hide();
                          $('#slsnm').val("");
                          $('#slnm').show();
                          $('#newsl').prop('disabled', false);
                          $('#editsl').prop('disabled', false);
                          $('#savesl').prop('disabled', true);
                          $('#addsub').prop('disabled', true);
                          $('#resetsl').prop('disabled', true);
                          $('#subs').modal('hide');
                          reloadSlCodes(slcateg);
                        }
                      },
                      error: function(err) {
                          console.log(err);
                      }
                    });
                  }
                }
              });
            }
          }
        });
      }
    });
    
  });
</script>

<script>
  $(document).ready(function () {    
    var table = $('#example').DataTable({
      scrollY: '30vh',
      scrollX: true,
      scrollCollapse: true,
      paging: false,
      bFilter:false,
      info:false,
    });
  });
</script>