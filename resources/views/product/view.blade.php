<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
<style>
    #openFileInput,
    #openFileInputEdit {
      cursor: pointer;
    }
    #ProductImage,
    #ProductImageEdit
    {
      width:100%;
      /* height: 230px; */
    }
    
</style>
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <table id="Table" class="table table-striped table-bordered" cellspacing="0" width="100%">
          <thead>
              <tr>
                  <th class="hidden-xs">No</th>
                  <th>Name</th>
                  <th>Code</th>
                  <th>Total Stock</th>
                  <th>Sell Price</th>
                  @if (Auth::user()->role == "ADMIN")
                  <th>Cost</th>
                  @endif
                  <th>Product Type</th>
                  <th>Expire Date</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $products as $product)
              
              <tr class="product-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="name">{{ $product->name }}</td>
                 <td class="barcode">{{ $product->product_barcode }}</td>
                  <td class="product-stock">{{ $product->stock }}</td>
                  <td class="product-price" price-data="{{ $product->price }}">{{ $product->price }}$</td>
                  @if (Auth::user()->role == "ADMIN")
                  <td class="product-cost" cost-data="{{ $product->cost }}">{{ $product->cost }}$</td>
                  @endif
                  <td class="product-category" category-id="{{ $product->category->id}}">{{ $product->category->name }}</td>
                  <td class="product-expire-date" expire-date-data="{{ $product->expire_date }}">{{ $product->expire_date }}</td>
                  <input type="hidden" class="product-barcode" value="{{ $product->product_barcode }}">
                  <td><div class="btn-group">
                  <a class="btn btn-default delete-btn delete-product" data-id="{{ $product->id }}" ><i class="fa fa-times" data-id="{{ $product->id }}"></i></a>
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default edit-product" data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}" ><i class="fa fa-pencil-square-o" data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"></i></a>
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default view-product-image" data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"><i class="fa fa-picture-o " data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" id="handleAddProduct">Add Product</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      let isImageUpdate = 0;
      $("#openFileInput").on("click",(e)=>{
        $('#Image').click();
      })
      
      $("body").on("change", "#Image", function(e){
        var self = e.target;
        if(self.files[0].size/1024/1024 > 5){
          html = `<h2 class="text-danger">*Image Should not bigger than 5MB</h2>`
          $('.image-content').append(html);
          $('#Image').val(null);
          return;
        }
        if (self.files && self.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
            $('#ProductImage').attr('src', e.target.result);
          };

            reader.readAsDataURL(self.files[0]);
        } 

      });

      $("#openFileInputEdit").on("click",(e)=>{
        $('#ImageEdit').click();
      })
      
      $("body").on("change", "#ImageEdit", function(e){
        var self = e.target;
        
        if (self.files && self.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
            $('#ProductImageEdit').attr('src', e.target.result);
          };

            reader.readAsDataURL(self.files[0]);
            isImageUpdate = 1;
        } 

      });
      
      $('#handleAddProduct').on("click",(e)=>{
        $('#addProducts')[0].reset();
        $('#Addproduct').modal('show');
      })

      $("#addProducts").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#ProductName").val());
        formData.append('product_barcode',$("#ProductBarcode").val() === undefined ? '' : $("#ProductBarcode").val());
        formData.append('category_id',$("#Category").val());
        // formData.append('unit_id',$("#Unit").val());
        formData.append('stock',$("#stock").val());
        formData.append('expire_date',$("#expire-data").val());
        formData.append('price',$("#price").val() === '' || $("#price").val() === undefined ? 0 : $("#price").val());
        formData.append('cost',$("#cost").val() === '' || $("#cost").val() === undefined ? 0 : $("#cost").val());
        formData.append('photo',$("#Image")[0].files[0]);
    
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
              productName = $(parentDiv).find('.name').text(),
              productBarcode = $(parentDiv).find('.barcode').text()
              productStock = $(parentDiv).find('.product-stock').text(),
              // unitId = $(parentDiv).find('.product-unit').attr('unit-id'),
              size = $(parentDiv).find('.product-size').text(),
              price = $(parentDiv).find('.product-price').attr('price-data'),
              cost = $(parentDiv).find('.product-cost').attr('cost-data'),
              categoryId = $(parentDiv).find('.product-category').attr('category-id'),
              expireDate = $(parentDiv).find('.product-expire-date').attr('expire-date-data'),
              image = $(self).attr('image-data');

              $('#ProductName-edit').val(productName);
              $('#ProductBarcode-edit').val(productBarcode);
              $('#productID').val(id);
              $('#stock-edit').val(productStock);
              $('#size-edit').val(size);
              $('#price-edit').val(price);
              $('#cost-edit').val(cost);
              $('Category-edit').val(categoryId);
              // $('Unit-edit').val(unitId);
              $("#ProductImageEdit").attr('src',image);
              $('#expire-date-edit').val(expireDate);

              $('#Editproduct').modal('show');

      })

      $("#editProduct").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('id',$("#productID").val());
        formData.append('name',$("#ProductName-edit").val());
        formData.append('product_barcode',$("#ProductBarcode-edit").val() === '' ? '' : $("#ProductBarcode-edit").val());
        formData.append('category_id',$("#Category-edit").val());
        // formData.append('unit_id',$("#Unit-edit").val());
        formData.append('stock',$("#stock-edit").val());
        formData.append('expire_date',$("#expire-date-edit").val());
        formData.append('price',$("#price-edit").val() === '' || $("#price-edit").val() === undefined ? 0 : $("#price-edit").val());
        formData.append('cost',$("#cost-edit").val() === '' || $("#cost-edit").val() === undefined ? 0 : $("#cost-edit").val());
        if(isImageUpdate){
          formData.append('photo',$("#ImageEdit")[0].files[0]);
        }

        $.ajax({
          url: '/product/update',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            isImageUpdate = 0;
            // console.log(res)
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      })

      $(".view-product-image").on("click",(e)=>{
        let self = e.target,
            image = $(self).attr('image-data');

            $("#ProductimageView").attr('src',image);
            $("#ImageModal").modal('show');
      })

      $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            startDate: 'today'
      });

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
            <label for="ProductName">Product Barcode</label>
            <input type="text" name="barcode" maxlength="100" class="form-control" id="ProductBarcode" placeholder="ProductBarcode">
          </div>
           <div class="form-group">
            <label for="ProductName">Stock</label>
            <input type="number" name="stock" maxlength="100" Required class="form-control" id="stock" placeholder="Stock" >
          </div>
          @if (Auth::user()->role == "ADMIN")
          <div class="form-group">
            <label for="ProductName">Sell Price</label>
            <input type="text" name="price" maxlength="100" class="form-control" id="price" placeholder="price" >
          </div>
          <div class="form-group">
            <label for="ProductName">Cost</label>
            <input type="text" name="cost" maxlength="100" class="form-control" id="cost" placeholder="cost" >
          </div>
          @endif
           <div class="form-group">
             <label for="Category">Brand</label>
              <select class="form-control selectpicker" id="Category" name="filtertype" data-live-search="true">
                @foreach (App\Category::all() as $category)
                  <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
              </select>
           </div>
           <!-- <div class="form-group">
            <label for="Category">Unit</label>
             <select class="form-control" id="Unit" name="filtertype">
               @foreach (App\Unit::all() as $unit)
                 <option value="{{ $unit->id }}">{{ $unit->name }}</option>
               @endforeach
             </select>
          </div> -->
          <div class="form-group">
            <label for="Category">Expired Date</label>
             <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
              <input type="text" class="form-control expired-date datepicker" id="expire-data">
              <div class="input-group-addon">
                  <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="Image">Image</label>
              <a id="openFileInput">Browse</a>
              <input type="file" name="logo" id="Image" style="display:none">
              <input type="hidden" name="crop_image" id="crop_image">
          </div>
          <div class="form-group">
            <div class="text-center image-content">
              <img class="img-fluid" src="" alt="" id="ProductImage"/>
            </div>
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
              <label for="ProductName">Product Barcode</label>
              <input type="text" name="barcode" maxlength="100" class="form-control" id="ProductBarcode-edit" placeholder="ProductBarcode" >
            </div>
            <div class="form-group">
             <label for="ProductName">Stock</label>
             <input type="number" name="stock" maxlength="100" Required class="form-control" id="stock-edit" placeholder="Stock" >
           </div>
           @if (Auth::user()->role == "ADMIN")
           <div class="form-group">
             <label for="ProductName">Sell Price</label>
             <input type="text" name="price" maxlength="100" class="form-control" id="price-edit" placeholder="price" >
           </div>
           <div class="form-group">
             <label for="ProductName">Cost</label>
             <input type="text" name="cost" maxlength="100" class="form-control" id="cost-edit" placeholder="cost" >
           </div>
           @endif
            <div class="form-group">
              <label for="Category">Brand</label>
               <select class="form-control" id="Category-edit" name="filtertype">
                 @foreach (App\Category::all() as $category)
                   <option value="{{ $category->id }}">{{ $category->name }}</option>
                 @endforeach
               </select>
            </div>
            <!-- <div class="form-group">
             <label for="Category">Unit</label>
              <select class="form-control" id="Unit-edit" name="filtertype">
                @foreach (App\Unit::all() as $unit)
                  <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
              </select>
           </div> -->
           <div class="form-group">
            <label for="Category">Expired Date</label>
             <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
              <input type="text" class="form-control expired-date datepicker" id="expire-date-edit">
              <div class="input-group-addon">
                  <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
          </div>
           <div class="form-group">
            <label for="Image">Image</label>
              <a id="openFileInputEdit">Browse</a>
              <input type="file" name="logo" id="ImageEdit" style="display:none">
          </div>
          <div class="form-group">
            <div class="text-center">
              <img class="img-fluid" src="" alt="" id="ProductImageEdit"/>
            </div>
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


 <div class="modal fade" id="ImageModal" tabindex="-1" role="dialog" aria-labelledby="myModal">
  <div class="modal-dialog" role="document">
     <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="myModalLabel">Product Image</h4>
        </div>
        <div class="modal-body">
          <img id="ProductimageView" src="" class="img-responsive center" alt="" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
     </div>
  </div>
</div>




@endsection