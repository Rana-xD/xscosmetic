  @extends('layouts/application')
@section('content')
<style>
  .table .thead-dark th {
    color: #fff;
    background-color: #059DC0;
    border-color: #059DC0;
  }

  .cashier-name {
    font-weight: bold;
    font-size: 18px;
    color: black;
  }

  .calendar {
    display: flex;
    justify-content: space-between;
    align-items: center
  }

  .container-fluid {
    margin-bottom: -50px;
  }

  .label-text {
    margin-bottom: 0;
    font-size: 16px;
    font-weight: bold;
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
  }

  .delete-invoice i {
    font-size: 14px;
    margin-right: 5px;
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
</style>
<div class="container">
  <h3>Invoice</h3>
  <br />
  <br />

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4" style="padding-left: 0">
        <div class="calendar">
          <p class="label-text">Date:</p>
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
        <button type="button" class="btn btn-add" id="handleCustomInvoiceSearch">Search</button>
      </div>
    </div>
  </div>


  <div class="invoice-container">
    @foreach ($orders as $order)
    <p class="cashier-name">Cashier: {{$order->cashier}}</p>
    <div class="invoice-info" style="display: flex; justify-content: space-between">
      <span style="font-weight: bold;">Order: {{$order->order_no}}</span>
      <span style="font-weight: bold;">Date: {{ date("d M Y", strtotime($order->created_at)) }} | {{$order->time}}</span>
      <span style="font-weight: bold;">Payment Type: {{ $order->payment_type === 'aba' || $order->payment_type === 'acleda' ? strtoupper($order->payment_type) : ucfirst($order->payment_type)}}</span>
    </div>
    <table class="table table-striped">
      <thead class="thead-dark">
        <tr>
          <th scope="col">#</th>
          <th scope="col">Name</th>
          <th scope="col">Qty</th>
          <th scope="col">Price</th>
          <th scope="col">Discount</th>
          <th scope="col">Total</th>
          <th scope="col">Cost</th>
          <th scope="col">Profit</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($order->items as $index => $item)
        <tr>
          <th scope="row">{{ ($index + 1) }}</th>
          <td>{{$item["product_name"]}}</td>
          <td>{{$item["quantity"]}}</td>
          <td>{{$item["price"]}}</td>
          <td>{{$item["discount"]}}</td>
          <td>{{$item["total"]}}</td>
          <td>{{$item["cost"]}}</td>
          <td>{{$item["profit"]}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div class="delete-button-container">
      <button class="btn btn-danger delete-invoice" data-invoice="{{ $order->id }}" data-order-no="{{ $order->order_no }}">
        <i class="fa fa-trash"></i> Delete Invoice
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
  });
</script>
@endsection