  @extends('layouts/application')
@section('content')
<style>
  .table .thead-dark th {
    color: #fff;
    background-color: #059DC0;
    border-color: #059DC0;
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    white-space: nowrap;
  }

  .table td {
    vertical-align: middle;
    text-align: center;
    padding: 12px 8px;
  }

  .cashier-name {
    font-weight: bold;
    font-size: 18px;
    color: black;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .calendar {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .container-fluid {
    margin-bottom: -50px;
  }

  .label-text {
    margin: 0;
    font-size: 16px;
    font-weight: bold;
    min-width: max-content;
    display: flex;
    align-items: center;
  }

  .date {
    width: 70%;
  }

  .table {
    margin-bottom: 10px;
  }

  .delete-button-container {
    display: flex;
    justify-content: flex-end;
    padding-right: 0;
    margin-bottom: 30px;
  }

  .delete-invoice {
    padding: 6px 14px !important;
    font-size: 14px !important;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .delete-invoice i {
    font-size: 14px;
  }

  .date-nav {
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .date-nav:hover {
    background-color: #f0f0f0;
  }

  .input-group-addon {
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .input-group-addon:hover {
    background-color: #f0f0f0;
  }

  .input-group.date {
    width: 100%;
  }

  .input-group.date .form-control {
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .input-group.date .form-control:hover {
    background-color: #f0f0f0;
  }

  .input-group {
    display: flex;
    gap: 10px;
    margin-left: 10px;
  }

  .invoice-container {
    margin-top: 50px;
  }

  .invoice-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 15px;
  }

  .invoice-info span {
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: max-content;
  }

  .search-wrapper {
    display: flex;
    align-items: center;
    height: 100%;
  }

  .btn-add {
    padding: 8px 20px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
  }
  .print-invoice {
    margin-left: 10px;
  }
</style>
<div class="container">
  <h3>{{ __('messages.invoice_title') }}</h3>
  <br />
  <br />

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4" style="padding-left: 0">
        <div class="calendar">
          <p class="label-text">{{ __('messages.date') }}:</p>
          <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
            <div class="input-group-prepend">
              <button class="btn btn-outline-secondary date-nav" type="button" id="date-prev">
                <i class="fa fa-chevron-left"></i>
              </button>
            </div>
            <input type="text" class="form-control selected-date datepicker" value="{{ empty($date) ? '' : $date }}">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary date-nav" type="button" id="date-next">
                <i class="fa fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="search-wrapper">
          <button type="button" class="btn btn-add" id="handleCustomInvoiceSearch">{{ __('messages.search') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div class="invoice-container">
    @foreach ($orders as $order)
    <p class="cashier-name">
      <span>{{ __('messages.cashier') }}:</span>
      <span>{{$order->cashier}}</span>
    </p>
    <div class="invoice-info">
      <span>
        <span>{{ __('messages.order') }}:</span>
        <span>{{$order->order_no}}</span>
      </span>
      <span>
        <span>{{ __('messages.date') }}:</span>
        <span>{{ date("d M Y", strtotime($order->created_at)) }} | {{$order->time}}</span>
      </span>
      <span>
        <span>{{ __('messages.payment_type') }}:</span>
        <span>{{ $order->payment_type === 'aba' || $order->payment_type === 'acleda' ? strtoupper($order->payment_type) : ucfirst($order->payment_type)}}</span>
      </span>
      <span>
        <span>{{ __('messages.total') }}:</span>
        <span>${{$order->total}}</span>
      </span>
    </div>
    <table class="table table-striped">
      <thead class="thead-dark">
        <tr>
          <th scope="col">{{ __('messages.invoice_table_number') }}</th>
          <th scope="col">{{ __('messages.invoice_table_name') }}</th>
          <th scope="col">{{ __('messages.invoice_table_qty') }}</th>
          <th scope="col">{{ __('messages.invoice_table_price') }}</th>
          <th scope="col">{{ __('messages.invoice_table_discount') }}</th>
          <th scope="col">{{ __('messages.invoice_table_total') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order->items as $index => $item)
        <tr>
          <th scope="row">{{ ($index + 1) }}</th>
          <td>{{$item["product_name"]}}</td>
          <td>{{$item["quantity"]}}</td>
          <td>$ {{$item["price"]}}</td>
          <td>{{$item["discount"]}}</td>
          <td>{{$item["total"]}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="delete-button-container">
      <button class="btn btn-danger delete-invoice" data-invoice="{{ $order->id }}" data-order-no="{{ $order->order_no }}">
        <i class="fa fa-trash"></i>
        <span>{{ __('messages.delete_invoice') }}</span>
      </button>
      <button class="btn btn-primary print-invoice" data-invoice="{{ $order->id }}">
        <i class="fa fa-print"></i>
        <span>{{ __('messages.print_invoice') }}</span>
      </button>
    </div>
    @endforeach
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {

    // Get the datepicker input element
    const datepickerInput = document.getElementById('datepicker-input');

    // Get the date navigation buttons
    const datePrevButton = document.getElementById('date-prev');
    const dateNextButton = document.getElementById('date-next');
    const dateFormat = 'MM/DD/YYYY';
    $('.datepicker').datepicker({
      format: 'mm/dd/yyyy',
      startDate: 'today'
    });

    // Function to change the date based on the click event of the datepicker
    function changeDate(direction) {

      const currentDate = new Date($('.selected-date').val());
      const newDate = new Date(currentDate);

      if (direction === 'prev') {
        newDate.setDate(newDate.getDate() - 1);
      } else if (direction === 'next') {
        newDate.setDate(newDate.getDate() + 1);
      }

      const formattedDate = moment(newDate).format(dateFormat);
      const convertedDate = moment(formattedDate, 'MM/DD/YYYY').format('YYYY-MM-DD');
      $('.selected-date').val(convertedDate);


      // Change the URL when the date is changed
      const date = $('.selected-date').val();
      let filter = `/invoice/filter?date=${date}`;

      window.location = filter;
    }

    // Add event listeners to the date navigation buttons
    datePrevButton.addEventListener('click', () => {
      changeDate('prev');
    });

    dateNextButton.addEventListener('click', () => {
      changeDate('next');
    });

    $('.date-nav').click(function(event) {
      event.preventDefault();
    });

    $('#handleCustomInvoiceSearch').on('click', (e) => {
      e.preventDefault();
      let date = $('.selected-date').val();

      if (date === '') {
        return;
      }
      let filter = `/invoice/filter?date=${date}`;

      window.location = filter;
    })

    // Handle invoice deletion
    $('.delete-invoice').on('click', function(e) {
      e.preventDefault();
      const invoiceId = $(this).data('invoice');
      const orderNo = $(this).data('order-no');
      
      swal({
        title: "Are you sure?",
        text: `Delete invoice ${orderNo}? This action cannot be undone.`,
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "Yes, delete it!",
        closeOnConfirm: false
      }, function(isConfirm) {
        if (isConfirm) {
          $.ajax({
            url: '/pos/delete/' + invoiceId,
            type: 'DELETE',
            data: {
              _token: '{{ csrf_token() }}'
            },
            success: function(response) {
              if (response.success) {
                swal({
                  title: "Deleted!",
                  text: "Invoice has been deleted.",
                  type: "success",
                  timer: 2000,
                  showConfirmButton: false
                }, function() {
                  location.reload();
                });
              } else {
                swal("Error!", response.message, "error");
              }
            },
            error: function(xhr) {
              swal("Error!", "Something went wrong!", "error");
            }
          });
        }
      });
    });

    // Print invoice handler
    $('.print-invoice').on('click', function() {
      const invoiceId = $(this).data('invoice');
      
      $.ajax({
        url: '/order/print-invoice',
        method: 'GET',
        data: {
          id: invoiceId,
          _token: '{{ csrf_token() }}'
        },
        success: function(response) {
          if (response.success) {
            swal({
              title: "Success!",
              text: '{{ __("messages.print_success") }}',
              type: "success",
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            swal({
              title: "Error!",
              text: '{{ __("messages.print_error") }}',
              type: "error"
            });
          }
        },
        error: function() {
          swal({
            title: "Error!",
            text: '{{ __("messages.print_error") }}',
            type: "error"
          });
        }
      });
    });
  });
</script>
@endsection