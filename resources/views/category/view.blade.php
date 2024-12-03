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
                  <th class="hidden-xs">{{ __('messages.sale_table_number') }}</th>
                  <th>{{ __('messages.brand_name') }}</th>
                  @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
                  <th>{{ __('messages.action') }}</th>
                  @endif
              </tr>
          </thead>

          <tbody>
              @foreach ( $categories as $category)
              
              <tr class="category-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="category-name">{{ $category->name }}</td>
                 @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
                 <td>
                    <div class="btn-group">
                      <a class="btn btn-default delete-btn delete-category" data-id="{{ $category->id }}" ><i class="fa fa-times" data-id="{{ $category->id }}"></i></a>
                    </div>
                    <div class="btn-group">
                      <a class="btn btn-default edit-category" data-id="{{ $category->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $category->id }}"></i></a>
                    </div>
                  </td>
                  @endif
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Addcategory">{{ __('messages.add_brand') }}</button>
   @endif
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      
      $("#addCategory").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#CategoryName").val());
        $.ajax({
          url: '/category/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            $('#CategoryName').val('');
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-category").on("click",(e)=>{
        swal({   
            title: '{{ __("messages.are_you_sure") }}',
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: '{{ __("messages.yes") }}',
            cancelButtonText: '{{ __("messages.cancel") }}',
            closeOnConfirm: true,
        }, function(confirmed) {
            if (confirmed) {
                let id = e.target.getAttribute('data-id');
                let formData = {
                  "id" : id
                };
                $.ajax({
                  url: '/category/delete',
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
            }
        });
      });

      $(".edit-category").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            categoryName = $(self).parents('.category-data').find('.category-name').text();

            $('#CategoryId').val(id);
            $('#CategoryNameEdit').val(categoryName);
            $('#Updatecategory').modal('show');
      });

      $('#editCategory').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#CategoryNameEdit").val());
        formData.append('id',$("#CategoryId").val());
        $.ajax({
          url: '/category/update',
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
<div class="modal fade" id="Addcategory" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addCategory" action="/category/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_brand') }}</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="CategoryName">{{ __('messages.brand_name') }}</label>
             <input type="text" name="name" Required class="form-control" id="CategoryName" placeholder="{{ __('messages.enter_brand_name') }}">
           </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
      </div>
    </form>
    </div>
 </div>
</div>
<!-- /.Modal -->

<!-- Edit Modal -->
<div class="modal fade" id="Updatecategory" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editCategory" action="/category/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">{{ __('messages.edit_brand') }}</h4>
       </div>
       <div class="modal-body">
            <div class="form-group">
              <label for="CategoryName">{{ __('messages.brand_name') }}</label>
              <input type="text" name="name" Required class="form-control" id="CategoryNameEdit" placeholder="{{ __('messages.enter_brand_name') }}">
              <input type="hidden"  name="category-id" id="CategoryId">
            </div>
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
         <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
       </div>
     </form>
     </div>
  </div>
 </div>
 <!-- /.Modal -->




@endsection