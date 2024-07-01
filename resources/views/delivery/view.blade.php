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
                  <th>Delivery Name</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $deliveries as $delivery)
              
              <tr class="delivery-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="delivery-name">{{ $delivery->name }}</td>
                 <td><div class="btn-group">
                  <a class="btn btn-default delete-btn delete-delivery" data-id="{{ $delivery->id }}" ><i class="fa fa-times" data-id="{{ $delivery->id }}"></i></a>
                  
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default edit-delivery" data-id="{{ $delivery->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $delivery->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Adddelivery">Add Delivery</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      
      $("#add-delivery").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#delivery-name").val());
        $.ajax({
          url: '/delivery/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            $('#delivery-name').val('');
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-delivery").on("click",(e)=>{
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
              url: '/delivery/delete',
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

      $(".edit-delivery").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            deliveryName = $(self).parents('.delivery-data').find('.delivery-name').text();

            $('#DeliveryId').val(id);
            $('#delivery-name-edit').val(deliveryName);
            $('#Updatedelivery').modal('show');
      });

      $('#editDelivery').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#delivery-name-edit").val());
        formData.append('id',$("#DeliveryId").val());
        $.ajax({
          url: '/delivery/update',
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
<div class="modal fade" id="Adddelivery" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="add-delivery" action="/delivery/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Add Delivery</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="delivery-name">Unit Name</label>
             <input type="text" name="name" maxlength="100" Required class="form-control" id="delivery-name" placeholder="Delivery Name">
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
<div class="modal fade" id="Updatedelivery" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editDelivery" action="/delivery/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">Edit Delivery</h4>
       </div>
       <div class="modal-body">
            <div class="form-group">
              <label for="delivery-name-edit">Delivery Name</label>
              <input type="text" name="name" maxlength="100" Required class="form-control" id="delivery-name-edit" placeholder="Delivery Name">
              <input type="hidden"  name="delivery-id" id="DeliveryId">
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