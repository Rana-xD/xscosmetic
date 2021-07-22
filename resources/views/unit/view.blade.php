<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <table id="Table" class="table table-striped table-bordered" cellspacing="0" width="100%">
          <thead>
              <tr>
                  <th class="hidden-xs">No</th>
                  <th>Unit Name</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $units as $unit)
              
              <tr class="unit-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="unit-name">{{ $unit->name }}</td>
                 <td><div class="btn-group">
                  <a class="btn btn-default delete-btn delete-unit" data-id="{{ $unit->id }}" ><i class="fa fa-times" data-id="{{ $unit->id }}"></i></a>
                  
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default edit-unit" data-id="{{ $unit->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $unit->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Addunit">Add Unit</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      
      $("#addUnit").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#UnitName").val());
        $.ajax({
          url: '/unit/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            $('#UnitName').val('');
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-unit").on("click",(e)=>{
        swal({   title: 'Are you sure?',
          text: 'Delete Product',
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: 'YES',
          closeOnConfirm: false },
          function(){
            console.log(e.target)
            let id = e.target.getAttribute('data-id');
            let formData = {
              "id" : id
            };
            console.log(formData);
            $.ajax({
              url: '/unit/delete',
              type: "GET", 
              data: formData,
              contentType: false,
              processData: true,
              success: function(res){
                location.reload();
              },
              error: function(err){
                console.log(err);
              } 
            });
          })
      });

      $(".edit-unit").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            unitName = $(self).parents('.unit-data').find('.unit-name').text();

            $('#UnitId').val(id);
            $('#UnitNameEdit').val(unitName);
            $('#Updateunit').modal('show');
      });

      $('#editUnit').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#UnitNameEdit").val());
        formData.append('id',$("#UnitId").val());
        $.ajax({
          url: '/unit/update',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      })
  });
</script>
<!-- Add Modal -->
<div class="modal fade" id="Addunit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addUnit" action="/unit/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Add Unit</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="UnitName">Unit Name</label>
             <input type="text" name="name" maxlength="100" Required class="form-control" id="UnitName" placeholder="Unit Name">
           </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-add">Submit</button>
      </div>
    </form>
    </div>
 </div>
</div>
<!-- /.Modal -->

<!-- Edit Modal -->
<div class="modal fade" id="Updateunit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editUnit" action="/unit/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">Edit Unit</h4>
       </div>
       <div class="modal-body">
            <div class="form-group">
              <label for="UnitName">Unit Name</label>
              <input type="text" name="name" maxlength="100" Required class="form-control" id="UnitNameEdit" placeholder="Unit Name">
              <input type="hidden"  name="unit-id" id="UnitId">
            </div>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
         <button type="submit" class="btn btn-add">Submit</button>
       </div>
     </form>
     </div>
  </div>
 </div>
 <!-- /.Modal -->




@endsection