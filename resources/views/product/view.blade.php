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
                  <th class="hidden-xs">ProductCode</th>
                  <th>ProductName</th>
                  <th>Price</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $products as $product)
              
              <tr>
                 <td class="hidden-xs productcode">{{ $product->product_code }}</td>
                 <td>{{ $product->product_name }}</td>
                  <td  data-order="{{ $product->price }}">{{ $product->price }}$</td>
                 <td><div class="btn-group">
                  <a class="btn btn-default delete-product" data-id="{{ $product->id }}" ><i class="fa fa-times" data-id="{{ $product->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Addproduct">AddProduct</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      
      $("#addProducts").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#ProductName").val());
        formData.append('category_id',$("#Category").val());
        $.ajax({
          url: '/product/add',
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
      });

      $(".delete-product").on("click",(e)=>{
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
              url: '/product/delete',
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
      })
  });
</script>
<!-- Modal -->
<div class="modal fade" id="Addproduct" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addProducts" action="/product/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">AddProduct></h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="ProductName">ProductName</label>
             <input type="text" name="name" maxlength="100" Required class="form-control" id="ProductName" placeholder="ProductName">
           </div>
           <div class="form-group">
             <label for="Category">Category</label>
          <select class="form-control" id="Category" name="filtertype">
            @foreach (App\Category::all() as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
            
         </select>
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


  <!-- Modal view -->
  <div class="modal fade" id="Viewproduct" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
   <div class="modal-dialog modal-lg" role="document" id="viewModal">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="view">Viewproduct</h4>
        </div>
        <div class="modal-body" id="modal-body">
           <div id="viewSectionProduct">
              <!-- view goes here -->
           </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default hiddenpr" data-dismiss="modal">Close</button>
        </div>
      </div>
   </div>
  </div>
  <!-- /.Modal -->

@endsection