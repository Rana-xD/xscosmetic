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
  #ProductImageEdit {
    width: 100%;
    /* height: 230px; */
  }

  /* HTML: <div class="loader"></div> */
  .loader {
    display: none;
    width: 40px;
    aspect-ratio: 1;
    --c: no-repeat radial-gradient(farthest-side, #514b82 92%, #0000);
    background:
      var(--c) 50% 0,
      var(--c) 50% 100%,
      var(--c) 100% 50%,
      var(--c) 0 50%;
    background-size: 10px 10px;
    animation: l18 1s infinite;
    position: relative;
  }

  .loader::before {
    content: "";
    position: absolute;
    inset: 0;
    margin: 3px;
    background: repeating-conic-gradient(#0000 0 35deg, #514b82 0 90deg);
    -webkit-mask: radial-gradient(farthest-side, #0000 calc(100% - 3px), #000 0);
    border-radius: 50%;
  }

  @keyframes l18 {
    100% {
      transform: rotate(.5turn)
    }
  }
</style>
@endsection
@section('content')
<div class="container">
  <div class="row" style="margin-top:100px;">
    <table id="Table" class="table table-striped table-bordered" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th class="hidden-xs">{{ __('messages.sale_table_number') }}</th>
          <th>{{ __('messages.input_date') }}</th>
          <th>{{ __('messages.name') }}</th>
          <th>{{ __('messages.product_code') }}</th>
          <th>{{ __('messages.total_stock') }}</th>
          <th>{{ __('messages.sell_price') }}</th>
          @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
          <th>{{ __('messages.cost') }}</th>
          @else
          <th style="display: none;">{{ __('messages.cost') }}</th>
          @endif
          <th>{{ __('messages.product_type') }}</th>
          <th>{{ __('messages.expire_date') }}</th>
          <th>{{ __('messages.action') }}</th>
        </tr>
      </thead>

      <tbody>
        @foreach ( $products as $product)

        <tr class="product-data">
          <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
          <td class="input-date" data-order="{{ date('Y-m-d',strtotime($product->updated_at)) }}">{{ date('d-m-Y',strtotime($product->updated_at)) }}</td>
          <td class="name">{{ $product->name }}</td>
          <td class="barcode">{{ $product->product_barcode }}</td>
          <td class="product-stock">{{ $product->stock }}</td>
          <td class="product-price" price-data="{{ $product->price }}">{{ $product->price }}$</td>
          @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
          <td class="product-cost" cost-data="{{ implode(', ', $product->cost_group)  }}">{{ implode('$ , ', $product->cost_group) }}$</td>
          @else
          <td style="display: none;" class="product-cost" cost-data="{{ implode(', ', $product->cost_group)  }}">{{ implode('$ , ', $product->cost_group) }}$</td>
          @endif
          <td class="product-category" category-id="{{ $product->category->id}}">{{ $product->category->name }}</td>
          <td class="product-expire-date" expire-date-data="{{ $product->expire_date }}">{{ $product->expire_date }}</td>
          <input type="hidden" class="product-barcode" value="{{ $product->product_barcode }}">
          <td>
            <div class="btn-group">
              @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
              <a class="btn btn-default delete-btn delete-product" data-id="{{ $product->id }}"><i class="fa fa-times" data-id="{{ $product->id }}"></i></a>
              @endif
            </div>
            <div class="btn-group">
              <a class="btn btn-default edit-product" data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"><i class="fa fa-pencil-square-o" data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"></i></a>
            </div>
            <!-- <div class="btn-group">
              <a class="btn btn-default view-product-image" data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"><i class="fa fa-picture-o " data-id="{{ $product->id }}" image-data="/storage/product_images/{{$product->photo}}"></i></a>
            </div> -->
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <!-- Button trigger modal -->
  <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Addproduct">{{ __('messages.add_product') }}</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {

    let isImageUpdate = 0;
    let code = "";
    let reading = false;

    document.addEventListener('keypress', e => {
      //usually scanners throw an 'Enter' key at the end of read
      if (e.keyCode === 13) {
        if (code.length > 10) {
          // element = getProductElementByBarcode(code);
          // console.log('code:: '+code);
          if ($('#Addproduct').is(':visible')) {
            $('#ProductBarcode').val(code);
          }
          if ($('#Editproduct').is(':visible')) {
            $('#ProductBarcode-edit').val(code);
          }

          code = "";
        }
      } else {
        code += e.key; //while this is not an 'enter' it stores the every key            
      }

      //run a timeout of 200ms at the first read and clear everything
      if (!reading) {
        reading = true;
        setTimeout(() => {
          code = "";
          reading = false;
        }, 400); //200 works fine for me but you can adjust it
      }
    });

    function showSpinner() {
      $('.loader').css('display', 'block');
      $('.modal-btn').prop('disabled', true);
      $('.modal-btn').html('<i class="fa fa-spinner fa-spin"></i> Processing...');
      $('.delete-btn').prop('disabled', true);
      $('.edit-product').prop('disabled', true);
    }

    function hideSpinner() {
      $('.loader').css('display', 'none');
      $('.modal-btn').prop('disabled', false);
      $('.modal-btn').html('{{ __('messages.submit') }}');
      $('.delete-btn').prop('disabled', false);
      $('.edit-product').prop('disabled', false);
    }

    $("#openFileInput").on("click", (e) => {
      $('#Image').click();
    })

    $("body").on("change", "#Image", function(e) {
      var self = e.target;
      if (self.files[0].size / 1024 / 1024 > 5) {
        html = `<h2 class="text-danger">*{{ __('messages.image_size_error') }}</h2>`
        $('.image-content').append(html);
        $('#Image').val(null);
        return;
      }
      if (self.files && self.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
          $('#ProductImage').attr('src', e.target.result);
        };

        reader.readAsDataURL(self.files[0]);
      }

    });

    $("#openFileInputEdit").on("click", (e) => {
      $('#ImageEdit').click();
    })

    $("body").on("change", "#ImageEdit", function(e) {
      var self = e.target;

      if (self.files && self.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
          $('#ProductImageEdit').attr('src', e.target.result);
        };

        reader.readAsDataURL(self.files[0]);
        isImageUpdate = 1;
      }

    });

    $('#handleAddProduct').on("click", (e) => {
      $('#addProducts')[0].reset();
      $('#Addproduct').modal('show');
    })

    $("#addProducts").on("submit", (e) => {
      e.preventDefault();
      
      // Prevent double submission
      if ($('.modal-btn').prop('disabled')) {
        return false;
      }
      
      // Validate required fields
      if (!$("#ProductName").val() || !$("#ProductBarcode").val() || !$("#stock").val()) {
        swal({
          title: '{{ __("messages.error") }}',
          type: "error",
          text: "Please fill in all required fields",
          timer: 2000
        });
        return false;
      }
      
      let formData = new FormData();
      formData.append("_token", $('meta[name="csrf_token"]').attr('content'));
      formData.append('name', $("#ProductName").val());
      formData.append('product_barcode', $("#ProductBarcode").val() === undefined ? '' : $("#ProductBarcode").val());
      formData.append('category_id', $("#Category").val());
      // formData.append('unit_id',$("#Unit").val());
      formData.append('stock', $("#stock").val());
      formData.append('expire_date', $("#expire-data").val());
      formData.append('price', $("#price").val() === '' || $("#price").val() === undefined ? 0 : $("#price").val());
      formData.append('cost', $("#cost").val() === '' || $("#cost").val() === undefined ? 0 : $("#cost").val());
      formData.append('photo', $("#Image")[0].files[0]);
      
      showSpinner();
      $.ajax({
        url: '/product/add',
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
          hideSpinner();
          if (res.code === 404) {
            swal({
              title: '{{ __("messages.error") }}',
              type: "error",
              text: "{{ __('messages.product_exists') }}",
              timer: 2500,
              showCancelButton: false,
              showConfirmButton: false
            }, function(data) {
              location.reload(true);
            });
          } else {
            location.reload();
          }
        },
        error: function(err) {
          hideSpinner();
          console.log(err);
          swal({
            title: '{{ __('messages.error') }}',
            type: "error",
            text: "Failed to create product. Please try again.",
            timer: 2500
          });
        }
      });
    });

    $(".delete-product").on("click", (e) => {
      // Prevent double click
      if ($(e.target).prop('disabled')) {
        return false;
      }
      
      swal({
          title: '{{ __("messages.are_you_sure") }}',
          text: '{{ __("messages.delete_confirm") }}',
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: '{{ __("messages.yes") }}',
          closeOnConfirm: false
        },
        function(isConfirm) {
          if (!isConfirm) return;
          
          // Disable delete button
          $(e.target).prop('disabled', true);
          $(e.target).html('<i class="fa fa-spinner fa-spin"></i>');
          
          console.log(e.target)
          let id = e.target.getAttribute('data-id');
          let formData = {
            "id": id
          };
          console.log(formData);
          $.ajax({
            url: '/product/delete',
            type: "GET",
            data: formData,
            contentType: false,
            processData: true,
            success: function(res) {
              location.reload();
            },
            error: function(err) {
              console.log(err);
              $(e.target).prop('disabled', false);
              $(e.target).html('<i class="fa fa-times"></i>');
              swal({
                title: '{{ __("messages.error") }}',
                type: "error",
                text: "Failed to delete product. Please try again."
              });
            }
          });
        })
    })

    $(".edit-product").on("click", (e) => {
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

      console.log(categoryId);
      $('#ProductName-edit').val(productName);
      $('#ProductBarcode-edit').val(productBarcode);
      $('#productID').val(id);
      $('#stock-edit').val(productStock);
      $('#size-edit').val(size);
      $('#price-edit').val(price);
      $('#cost-edit').val(cost);
      $('#Category-edit').val(categoryId);
      // $('Unit-edit').val(unitId);
      $("#ProductImageEdit").attr('src', image);
      $('#expire-date-edit').val(expireDate);

      $('#Editproduct').modal('show');

    })

    $("#editProduct").on("submit", (e) => {
      e.preventDefault();
      
      // Prevent double submission
      if ($('.modal-btn').prop('disabled')) {
        return false;
      }
      
      // Validate required fields
      if (!$("#ProductName-edit").val() || !$("#stock-edit").val()) {
        swal({
          title: '{{ __("messages.error") }}',
          type: "error",
          text: "Please fill in all required fields",
          timer: 2000
        });
        return false;
      }
      
      let formData = new FormData();
      formData.append("_token", $('meta[name="csrf_token"]').attr('content'));
      formData.append('id', $("#productID").val());
      formData.append('name', $("#ProductName-edit").val());
      formData.append('product_barcode', $("#ProductBarcode-edit").val() === '' ? '' : $("#ProductBarcode-edit").val());
      formData.append('category_id', $("#Category-edit").val());
      // formData.append('unit_id',$("#Unit-edit").val());
      formData.append('stock', $("#stock-edit").val());
      formData.append('new_stock', $("#new-stock-edit").val() === '' || $("#new-stock-edit").val() === undefined ? 0 : $("#new-stock-edit").val());
      formData.append('expire_date', $("#expire-date-edit").val());
      formData.append('price', $("#price-edit").val() === '' || $("#price-edit").val() === undefined ? 0 : $("#price-edit").val());
      formData.append('cost', $("#cost-edit").val() === '' || $("#cost-edit").val() === undefined ? 0 : $("#cost-edit").val());
      formData.append('new_cost', $("#newCost").val() === '' || $("#newCost").val() === undefined ? 0 : $("#newCost").val());
      if (isImageUpdate) {
        formData.append('photo', $("#ImageEdit")[0].files[0]);
      }

      showSpinner();
      $.ajax({
        url: '/product/update',
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
          hideSpinner();
          isImageUpdate = 0;
          // console.log(res)
          location.reload();
        },
        error: function(err) {
          hideSpinner();
          console.log(err);
          swal({
            title: '{{ __("messages.error") }}',
            type: "error",
            text: "Failed to update product. Please try again.",
            timer: 2500
          });
        }
      });
    })

    $(".view-product-image").on("click", (e) => {
      let self = e.target,
        image = $(self).attr('image-data');

      $("#ProductimageView").attr('src', image);
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
          <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_product') }}</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="ProductName">{{ __('messages.name') }}</label>
            <input type="text" name="name" maxlength="100" Required class="form-control" id="ProductName" placeholder="{{ __('messages.name') }}">
          </div>
          <div class="form-group">
            <label for="ProductBarcode">{{ __('messages.product_barcode') }}</label>
            <input type="text" name="product_barcode" maxlength="100" Required class="form-control" id="ProductBarcode" placeholder="{{ __('messages.product_barcode') }}">
          </div>
          <div class="form-group">
            <label for="ProductName">{{ __('messages.stock') }}</label>
            <input type="number" name="stock" maxlength="100" Required class="form-control" id="stock" placeholder="{{ __('messages.stock') }}">
          </div>
          @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN" || Auth::user()->role == "MANAGER")
          <div class="form-group">
            <label for="ProductName">{{ __('messages.sell_price') }}</label>
            <input type="text" name="price" maxlength="100" class="form-control" id="price" placeholder="{{ __('messages.sell_price') }}">
          </div>
          @endif
          @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
          <div class="form-group">
            <label for="ProductName">{{ __('messages.product_cost') }}</label>
            <input type="text" name="cost" maxlength="100" class="form-control" id="cost" placeholder="{{ __('messages.product_cost') }}">
          </div>
          @endif
          <div class="form-group">
            <label for="Category">{{ __('messages.product_type') }}</label>
            <select class="form-control selectpicker" id="Category" name="filtertype" data-live-search="true">
              @foreach (App\Category::orderBy('name', 'ASC')->get() as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
              @endforeach
            </select>
          </div>
          <!-- <div class="form-group">
            <label for="Category">{{ __('messages.unit') }}</label>
             <select class="form-control" id="Unit" name="filtertype">
               @foreach (App\Unit::all() as $unit)
                 <option value="{{ $unit->id }}">{{ $unit->name }}</option>
               @endforeach
             </select>
          </div> -->
          <div class="form-group">
            <label for="Category">{{ __('messages.expire_date') }}</label>
            <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
              <input type="text" class="form-control expired-date datepicker" id="expire-data">
              <div class="input-group-addon">
                <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="Image">{{ __('messages.product_image') }}</label>
            <a id="openFileInput">Browse</a>
            <input type="file" name="photo" id="Image" style="display:none">
            <input type="hidden" name="crop_image" id="crop_image">
          </div>
          <div class="form-group">
            <div class="text-center image-content">
              <img class="img-fluid" src="" alt="" id="ProductImage" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
          <button type="submit" class="btn btn-add modal-btn">{{ __('messages.submit') }}</button>
          <div class="loader"></div>
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
          <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_product') }}</h4>
        </div>
        <div class="modal-body">
          <input type="hidden" name="" id="productID">
          <div class="form-group">
            <label for="ProductName">{{ __('messages.name') }}</label>
            <input type="text" name="name" maxlength="100" Required class="form-control" id="ProductName-edit" placeholder="{{ __('messages.name') }}">
          </div>
          <div class="form-group">
            <label for="ProductBarcode">{{ __('messages.product_barcode') }}</label>
            <input type="text" name="product_barcode" maxlength="100" class="form-control" id="ProductBarcode-edit" placeholder="{{ __('messages.product_barcode') }}">
          </div>
          <div class="form-group">
            <label for="ProductName">{{ __('messages.current_stock') }}</label>
            <input type="number" name="stock" maxlength="100" Required class="form-control" id="stock-edit" placeholder="{{ __('messages.stock') }}" readonly="readonly">
          </div>
          <div class="form-group">
            <label for="ProductName">{{ __('messages.new_stock') }}</label>
            <input type="number" name="stock" maxlength="100" class="form-control" id="new-stock-edit" placeholder="{{ __('messages.new_stock') }}">
          </div>
          @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN" || Auth::user()->role == "MANAGER")
          <div class="form-group">
            <label for="ProductName">{{ __('messages.sell_price') }}</label>
            <input type="text" name="price" maxlength="100" class="form-control" id="price-edit" placeholder="{{ __('messages.sell_price') }}">
          </div>
          @endif
          @if (Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN")
          <div class="form-group">
            <label for="ProductName">{{ __('messages.product_cost') }}</label>
            <input type="text" name="cost" maxlength="100" class="form-control" id="cost-edit" placeholder="{{ __('messages.product_cost') }}">
          </div>
          <div class="form-group">
            <label for="ProductName">{{ __('messages.new_cost') }}</label>
            <input type="text" name="new-cost" maxlength="100" class="form-control" id="newCost" placeholder="{{ __('messages.new_cost') }}">
          </div>
          @else
          <input type="hidden" name="price" maxlength="100" class="form-control" id="price-edit" placeholder="{{ __('messages.sell_price') }}">
          <input type="hidden" name="cost" maxlength="100" class="form-control" id="cost-edit" placeholder="{{ __('messages.product_cost') }}">
          <input type="hidden" name="new-cost" maxlength="100" class="form-control" id="newCost" placeholder="{{ __('messages.new_cost') }}">
          @endif
          <div class="form-group">
            <label for="Category">{{ __('messages.product_type') }}</label>
            <select class="form-control" id="Category-edit" name="filtertype">
              @foreach (App\Category::orderBy('name', 'ASC')->get() as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
              @endforeach
            </select>
          </div>
          <!-- <div class="form-group">
             <label for="Category">{{ __('messages.unit') }}</label>
              <select class="form-control" id="Unit-edit" name="filtertype">
                @foreach (App\Unit::all() as $unit)
                  <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
              </select>
           </div> -->
          <div class="form-group">
            <label for="Category">{{ __('messages.expire_date') }}</label>
            <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
              <input type="text" class="form-control expired-date datepicker" id="expire-date-edit">
              <div class="input-group-addon">
                <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="Image">{{ __('messages.product_image') }}</label>
            <a id="openFileInputEdit">Browse</a>
            <input type="file" name="logo" id="ImageEdit" style="display:none">
          </div>
          <div class="form-group">
            <div class="text-center">
              <img class="img-fluid" src="" alt="" id="ProductImageEdit" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
          <button type="submit" class="btn btn-add modal-btn">{{ __('messages.submit') }}</button>
          <div class="loader"></div>
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
        <h4 class="modal-title" id="myModalLabel">{{ __('messages.view_image') }}</h4>
      </div>
      <div class="modal-body">
        <img id="ProductimageView" src="" class="img-responsive center" alt="" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
      </div>
    </div>
  </div>
</div>




@endsection