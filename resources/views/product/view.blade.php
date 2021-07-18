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
                  <th>Product Name</th>
                  <th>Total Stock</th>
                  <th>Type</th>
                  <th>Size</th>
                  <th>Sell Price</th>
                  <th>Cost</th>
                  <th>Product Type</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $products as $product)
              
              <tr class="product-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="product-name">{{ $product->name }}</td>
                  <td class="product-stock">{{ $product->stock }}</td>
                  <td class="product-unit" unit-id="{{ $product->unit->id }}">{{ $product->unit->name }}</td>
                  <td class="product-size">{{ $product->size }}</td>
                  <td class="product-price" price-data="{{ $product->price }}">{{ $product->price }}$</td>
                  <td class="product-cost" cost-data="{{ $product->cost }}">{{ $product->cost }}$</td>
                  <td class="product-category" category-id="{{ $product->category->id}}">{{ $product->category->name }}</td>
                 <td><div class="btn-group">
                  <a class="btn btn-default delete-btn" data-id="{{ $product->id }}" ><i class="fa fa-times" data-id="{{ $product->id }}"></i></a>
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default edit-product" data-id="{{ $product->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $product->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Addproduct">Add Product</button>
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
        formData.append('unit_id',$("#Unit").val());
        formData.append('stock',$("#stock").val());
        formData.append('size',$("#size").val());
        formData.append('price',$("#price").val());
        formData.append('cost',$("#cost").val());
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

      $(".edit-product").on("click",(e)=>{
          e.preventDefault();
          let self = e.target,
              id = $(self).attr('data-id'),
              parentDiv = $(self).parents('.product-data'),
              productName = $(parentDiv).find('.product-name').text(),
              productStock = $(parentDiv).find('.product-stock').text(),
              unitId = $(parentDiv).find('.product-unit').attr('unit-id'),
              size = $(parentDiv).find('.product-size').text(),
              price = $(parentDiv).find('.product-price').attr('price-data'),
              cost = $(parentDiv).find('.product-cost').attr('cost-data'),
              categoryId = $(parentDiv).find('.product-category').attr('category-id');

              $('#ProductName-edit').val(productName);
              $('#productID').val(id);
              $('#stock-edit').val(productStock);
              $('#size-edit').val(size);
              $('#price-edit').val(price);
              $('#cost-edit').val(cost);
              $('Category-edit').val(categoryId);
              $('Unit-edit').val(unitId);


              $('#Editproduct').modal('show');

      })

      $("#editProduct").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('id',$("#productID").val());
        formData.append('name',$("#ProductName-edit").val());
        formData.append('category_id',$("#Category-edit").val());
        formData.append('unit_id',$("#Unit-edit").val());
        formData.append('stock',$("#stock-edit").val());
        formData.append('size',$("#size-edit").val());
        formData.append('price',$("#price-edit").val());
        formData.append('cost',$("#cost-edit").val());
        $.ajax({
          url: '/product/update',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            // console.log(res)
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      })
  });
</script>
<!-- Modal Add -->
<div class="modal fade" id="Addproduct" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addProducts" action="/product/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Add Product</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="ProductName">Product Name</label>
             <input type="text" name="name" maxlength="100" Required class="form-control" id="ProductName" placeholder="ProductName">
           </div>
           <div class="form-group">
            <label for="ProductName">Stock</label>
            <input type="number" name="stock" maxlength="100" Required class="form-control" id="stock" placeholder="Stock" >
          </div>
          <div class="form-group">
            <label for="ProductName">Size</label>
            <input type="text" name="size" maxlength="100"  class="form-control" id="size" placeholder="size">
          </div>
          <div class="form-group">
            <label for="ProductName">Sell Price</label>
            <input type="text" name="price" maxlength="100" Required class="form-control" id="price" placeholder="price" >
          </div>
          <div class="form-group">
            <label for="ProductName">Cost</label>
            <input type="text" name="cost" maxlength="100" Required class="form-control" id="cost" placeholder="cost" >
          </div>
           <div class="form-group">
             <label for="Category">Category</label>
              <select class="form-control" id="Category" name="filtertype">
                @foreach (App\Category::all() as $category)
                  <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
              </select>
           </div>
           <div class="form-group">
            <label for="Category">Unit</label>
             <select class="form-control" id="Unit" name="filtertype">
               @foreach (App\Unit::all() as $unit)
                 <option value="{{ $unit->id }}">{{ $unit->name }}</option>
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


<!-- Modal Edit -->
<div class="modal fade" id="Editproduct" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editProduct" action="/product/update" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">Add Product</h4>
       </div>
       <div class="modal-body">
            <input type="hidden" name="" id="productID">
            <div class="form-group">
              <label for="ProductName">Product Name</label>
              <input type="text" name="name" maxlength="100" Required class="form-control" id="ProductName-edit" placeholder="ProductName" >
            </div>
            <div class="form-group">
             <label for="ProductName">Stock</label>
             <input type="number" name="stock" maxlength="100" Required class="form-control" id="stock-edit" placeholder="Stock" >
           </div>
           <div class="form-group">
             <label for="ProductName">Size</label>
             <input type="text" name="size" maxlength="100"  class="form-control" id="size-edit" placeholder="size">
           </div>
           <div class="form-group">
             <label for="ProductName">Sell Price</label>
             <input type="text" name="price" maxlength="100" Required class="form-control" id="price-edit" placeholder="price" >
           </div>
           <div class="form-group">
             <label for="ProductName">Cost</label>
             <input type="text" name="cost" maxlength="100" Required class="form-control" id="cost-edit" placeholder="cost" >
           </div>
            <div class="form-group">
              <label for="Category">Category</label>
               <select class="form-control" id="Category-edit" name="filtertype">
                 @foreach (App\Category::all() as $category)
                   <option value="{{ $category->id }}">{{ $category->name }}</option>
                 @endforeach
               </select>
            </div>
            <div class="form-group">
             <label for="Category">Unit</label>
              <select class="form-control" id="Unit-edit" name="filtertype">
                @foreach (App\Unit::all() as $unit)
                  <option value="{{ $unit->id }}">{{ $unit->name }}</option>
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




@endsection