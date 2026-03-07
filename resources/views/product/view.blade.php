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

  .product-toast-container {
    position: fixed;
    top: 80px;
    right: 20px;
    z-index: 1080;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
  }

  .product-toast {
    min-width: 260px;
    max-width: 360px;
    padding: 12px 16px;
    border-radius: 8px;
    color: #fff;
    background: #1f7a45;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.2s ease, transform 0.2s ease;
    pointer-events: auto;
  }

  .product-toast.show {
    opacity: 1;
    transform: translateY(0);
  }

  .product-toast__title {
    display: block;
    font-weight: 700;
    margin-bottom: 4px;
  }

  .product-toast__message {
    display: block;
    line-height: 1.4;
  }
</style>
@endsection
@section('content')
<div class="container">
  <div class="product-toast-container" id="productToastContainer" aria-live="polite" aria-atomic="true"></div>
  <div class="row" style="margin-top:100px;">
    <table id="ProductTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
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
      <tbody></tbody>
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
    const canDelete = @json(Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN");
    const canSeeCost = @json(Auth::user()->role == "ADMIN" || Auth::user()->role == "SUPERADMIN");
    const defaultCategoryId = @json(optional($categories->first())->id);
    const productToastTitle = @json(__('messages.success'));
    const productCreatedMessage = @json(__('messages.product_created'));
    const productUpdatedMessage = @json(__('messages.product_updated'));
    const productDeletedMessage = @json(__('messages.product_deleted'));

    function getProductTableLanguage() {
      const currentLang = $('html').attr('lang') || 'en';

      if (currentLang !== 'kh') {
        return {};
      }

      return {
        processing: "ដំណើរការ...",
        search: "ស្វែងរក:",
        lengthMenu: "បង្ហាញ _MENU_ ធាតុ",
        info: "បង្ហាញ _START_ ដល់ _END_ នៃ _TOTAL_ ធាតុ",
        infoEmpty: "បង្ហាញ 0 ដល់ 0 នៃ 0 ធាតុ",
        infoFiltered: "(បានចម្រាញ់ចេញពី _MAX_ ធាតុសរុប)",
        loadingRecords: "កំពុងផ្ទុកទិន្នន័យ...",
        zeroRecords: "មិនមានទិន្នន័យត្រូវបង្ហាញ",
        emptyTable: "មិនមានទិន្នន័យក្នុងតារាង",
        paginate: {
          first: "ទំព័រដំបូង",
          previous: "មុន",
          next: "បន្ទាប់",
          last: "ទំព័រចុងក្រោយ"
        },
        aria: {
          sortAscending: ": ធ្វើការតម្រៀបតាមលំដាប់ឡើង",
          sortDescending: ": ធ្វើការតម្រៀបតាមលំដាប់ចុះ"
        }
      };
    }

    function renderActionButtons() {
      let buttons = '';

      if (canDelete) {
        buttons += '<div class="btn-group"><a href="#" class="btn btn-default delete-btn delete-product"><i class="fa fa-times"></i></a></div>';
      }

      buttons += '<div class="btn-group"><a href="#" class="btn btn-default edit-product"><i class="fa fa-pencil-square-o"></i></a></div>';
      return buttons;
    }

    function refreshCategoryPicker($element, value) {
      $element.val(value);
      if ($.fn.selectpicker) {
        $element.selectpicker('refresh');
      }
    }

    function showProductToast(message) {
      const container = document.getElementById('productToastContainer');

      if (!container) {
        return;
      }

      const toast = document.createElement('div');
      toast.className = 'product-toast';
      toast.innerHTML = `
        <span class="product-toast__title">${productToastTitle}</span>
        <span class="product-toast__message">${message}</span>
      `;

      container.appendChild(toast);

      window.requestAnimationFrame(function() {
        toast.classList.add('show');
      });

      window.setTimeout(function() {
        toast.classList.remove('show');
        window.setTimeout(function() {
          toast.remove();
        }, 200);
      }, 3000);
    }

    function resetAddForm() {
      $('#addProducts')[0].reset();
      $('#Image').val(null);
      $('#ProductImage').attr('src', '');
      $('.image-content .text-danger').remove();
      if (defaultCategoryId) {
        refreshCategoryPicker($('#Category'), defaultCategoryId);
      }
    }

    function resetEditForm() {
      $('#editProduct')[0].reset();
      $('#ImageEdit').val(null);
      $('#ProductImageEdit').attr('src', '');
      $('#productID').val('');
      isImageUpdate = 0;
    }

    const productTable = $("#ProductTable").DataTable({
      processing: true,
      serverSide: true,
      searchDelay: 350,
      dom: 'T<"clear">lfrtip',
      ajax: {
        url: '{{ route("product.data") }}',
        type: 'GET'
      },
      order: [
        [1, 'desc']
      ],
      pageLength: 25,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      language: getProductTableLanguage(),
      tableTools: {
        sSwfPath: "https://cdn.datatables.net/tabletools/2.2.4/swf/copy_csv_xls_pdf.swf",
        bProcessing: true,
        aButtons: [
          "xls",
          {
            sExtends: "pdf",
            sPdfOrientation: "landscape",
            sPdfMessage: ""
          },
          "print"
        ]
      },
      columns: [{
          data: 'row_number',
          orderable: false,
          searchable: false,
          className: 'hidden-xs productcode'
        },
        {
          data: 'updated_at_display',
          className: 'input-date'
        },
        {
          data: 'name',
          className: 'name'
        },
        {
          data: 'product_barcode',
          className: 'barcode'
        },
        {
          data: 'stock',
          className: 'product-stock'
        },
        {
          data: 'price_display',
          className: 'product-price'
        },
        {
          data: 'cost_group_display',
          className: 'product-cost',
          visible: canSeeCost,
          searchable: canSeeCost
        },
        {
          data: 'category_name',
          className: 'product-category'
        },
        {
          data: 'expire_date',
          className: 'product-expire-date'
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          render: function() {
            return renderActionButtons();
          }
        }
      ]
    });

    function getRowDataFromButton(button) {
      return productTable.row($(button).closest('tr')).data();
    }

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

    hideSpinner();
    resetAddForm();
    resetEditForm();

    $('#Addproduct').on('show.bs.modal', function(event) {
      if (event.target !== this) {
        return;
      }

      resetAddForm();
    });

    $("#openFileInput").on("click", (e) => {
      $('#Image').click();
    });

    $("body").on("change", "#Image", function(e) {
      var self = e.target;
      if (self.files[0].size / 1024 / 1024 > 5) {
        $('.image-content .text-danger').remove();
        html = `<h2 class="text-danger">*{{ __('messages.image_size_error') }}</h2>`;
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
    });

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

    $("#addProducts").on("submit", (e) => {
      e.preventDefault();
      hideSpinner();
      
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
          if (res.code === 404) {
            hideSpinner();
            swal({
              title: '{{ __("messages.error") }}',
              type: "error",
              text: "{{ __('messages.product_exists') }}"
            });
          } else {
            $('#Addproduct').modal('hide');
            resetAddForm();
            productTable.ajax.reload(function() {
              hideSpinner();
              showProductToast(productCreatedMessage);
            }, true);
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

    $("#ProductTable tbody").on("click", ".delete-product", function(e) {
      e.preventDefault();
      const $button = $(this);

      // Prevent double click
      if ($button.prop('disabled')) {
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

          const rowData = getRowDataFromButton($button);

          if (!rowData) {
            return;
          }

          $button.prop('disabled', true);
          $button.html('<i class="fa fa-spinner fa-spin"></i>');

          $.ajax({
            url: '/product/delete',
            type: "GET",
            data: {
              id: rowData.id
            },
            contentType: false,
            processData: true,
            success: function() {
              swal.close();
              productTable.ajax.reload(function() {
                showProductToast(productDeletedMessage);
              }, false);
            },
            error: function(err) {
              console.log(err);
              $button.prop('disabled', false);
              $button.html('<i class="fa fa-times"></i>');
              swal({
                title: '{{ __("messages.error") }}',
                type: "error",
                text: "Failed to delete product. Please try again."
              });
            }
          });
        });
    });

    $("#ProductTable tbody").on("click", ".edit-product", function(e) {
      e.preventDefault();
      const rowData = getRowDataFromButton(this);

      if (!rowData) {
        return;
      }

      resetEditForm();
      $('#ProductName-edit').val(rowData.name);
      $('#ProductBarcode-edit').val(rowData.product_barcode);
      $('#productID').val(rowData.id);
      $('#stock-edit').val(rowData.stock);
      $('#price-edit').val(rowData.price);
      $('#cost-edit').val(rowData.cost_group_raw);
      $('#Category-edit').val(rowData.category_id);
      $("#ProductImageEdit").attr('src', rowData.photo_url);
      $('#expire-date-edit').val(rowData.expire_date);

      $('#Editproduct').modal('show');
    });

    $("#editProduct").on("submit", (e) => {
      e.preventDefault();
      hideSpinner();
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
          $('#Editproduct').modal('hide');
          isImageUpdate = 0;
          productTable.ajax.reload(function() {
            hideSpinner();
            showProductToast(productUpdatedMessage);
          }, false);
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
    });

    $("#ProductTable tbody").on("click", ".view-product-image", function(e) {
      let self = e.target,
        image = $(self).attr('image-data');

      $("#ProductimageView").attr('src', image);
      $("#ImageModal").modal('show');
    });

    $('.product-expire-datepicker').datepicker({
      format: 'yyyy-mm-dd',
      autoclose: true,
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
              @foreach ($categories as $category)
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
            <div class="input-group date product-expire-datepicker">
              <input type="text" class="form-control expired-date" id="expire-data">
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
              @foreach ($categories as $category)
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
            <div class="input-group date product-expire-datepicker">
              <input type="text" class="form-control expired-date" id="expire-date-edit">
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
