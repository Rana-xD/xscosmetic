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
    margin-top: 100px;
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
    justify-content: center;
  }

  .btn-add, .btn-primary {
    padding: 8px 20px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
  }
  .print-daily-invoice {
    padding: 8px 20px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
  }

  .print-daily-invoice i {
    margin-right: 5px;
  }

  .print-invoice {
    margin-left: 10px;
  }

  .print-invoice, .update-payment {
    padding: 6px 14px !important;
    font-size: 14px !important;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .print-invoice i, .update-payment i {
    font-size: 14px;
  }
  
  .update-payment {
    margin-left: 10px;
  }
  
  .filter-wrapper {
    display: flex;
    align-items: center;
    height: 100%;
  }
  
  .filter-wrapper .label-text {
    margin-right: 12px;
    white-space: nowrap;
  }
  
  .filter-wrapper select {
    flex-grow: 1;
    min-width: 150px;
    width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background-color: #fff;
    padding: 8px 12px;
    color: #495057;
    font-weight: 500;
    font-size: 16px; /* Increased font size by 2px */
    cursor: pointer;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=utf8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'%3E%3Cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 8px 10px;
    padding-right: 2rem;
    text-overflow: ellipsis;
    height: 40px;
    line-height: 1.4;
  }
  
  .filter-wrapper select:focus {
    outline: none;
    box-shadow: none;
  }
  
  .invoice-wrapper {
    margin-bottom: 30px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
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
            <input type="text" class="form-control selected-date datepicker" value="{{ empty($date) ? '' : $date }}" id="date">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary date-nav" type="button" id="date-next">
                <i class="fa fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="search-wrapper">
          <button type="button" class="btn btn-primary" id="handleCustomInvoiceSearch">{{ __('messages.search') }}</button>
        </div>
      </div>
      <div class="col-md-3">
        <div class="filter-wrapper">
          <p class="label-text">{{ __('messages.filter_payment_type') }}:</p>
          <select class="form-control" id="payment-type-filter" style="width: auto; min-width: 160px;">
            <option value="all" selected>{{ __('messages.all_payment_types') }}</option>
            <option value="cash">{{ __('messages.cash_payment') }}</option>
            <option value="electronic">{{ __('messages.electronic_payment') }}</option>
          </select>
        </div>
      </div>
      <div class="col-md-3 text-right">
        <button type="button" class="btn btn-success print-daily-invoice" id="print-daily-invoice">
            <i class="fa fa-print"></i>
            {{ __('messages.print_daily_invoice') }}
        </button>
      </div>
    </div>
  </div>

  <div class="invoice-container">
    @foreach ($orders as $order)
    <div class="invoice-wrapper" data-payment-type="{{ $order->payment_type }}">

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
        <span>
          @if($order->payment_type === 'custom')
            {{ ucfirst($order->payment_type) }} (ABA ${{ isset($order->aba_amount) ? $order->aba_amount : '0' }} | Cash ${{ isset($order->cash_amount) ? $order->cash_amount : '0' }})
          @else
            {{ $order->payment_type === 'aba' || $order->payment_type === 'acleda' ? strtoupper($order->payment_type) : ucfirst($order->payment_type)}}
          @endif
        </span>
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
      <button class="btn btn-info update-payment" data-invoice="{{ $order->id }}" data-payment="{{ $order->payment_type }}">
        <i class="fa fa-edit"></i>
        <span>{{ __('messages.update_invoice') }}</span>
      </button>
    </div>
    </div>
    @endforeach
  </div>
</div>

<!-- Payment Type Update Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" role="dialog" aria-labelledby="updatePaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="updatePaymentModalLabel">{{ __('messages.update_payment_type') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="updatePaymentForm">
          <input type="hidden" id="invoice_id" name="invoice_id">
          <div class="form-group">
            <label for="payment_type">{{ __('messages.payment_type') }}</label>
            <select class="form-control" id="payment_type" name="payment_type">
              <option value="aba">{{ __('messages.aba') }}</option>
              <option value="cash">{{ __('messages.cash') }}</option>
              <option value="acleda">{{ __('messages.acleda') }}</option>
              <option value="custom">{{ __('messages.custom_split') }}</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="button" class="btn btn-primary" id="savePaymentUpdate">{{ __('messages.save_changes') }}</button>
      </div>
    </div>
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

    // Handle payment type update
    $('.update-payment').on('click', function() {
      const invoiceId = $(this).data('invoice');
      const currentPayment = $(this).data('payment');
      
      $('#invoice_id').val(invoiceId);
      $('#payment_type').val(currentPayment);
      $('#updatePaymentModal').modal('show');
    });
    
    $('#savePaymentUpdate').on('click', function() {
      const invoiceId = $('#invoice_id').val();
      const paymentType = $('#payment_type').val();
      
      $.ajax({
        url: '/invoice/update-payment',
        method: 'POST',
        data: {
          id: invoiceId,
          payment_type: paymentType,
          _token: '{{ csrf_token() }}'
        },
        success: function(response) {
          if (response.success) {
            $('#updatePaymentModal').modal('hide');
            swal({
              title: "Success!",
              text: '{{ __("messages.payment_updated") }}',
              type: "success",
              timer: 2000,
              showConfirmButton: false
            }, function() {
              location.reload();
            });
          } else {
            swal({
              title: "Error!",
              text: response.message || '{{ __("messages.update_error") }}',
              type: "error"
            });
          }
        },
        error: function() {
          swal({
            title: "Error!",
            text: '{{ __("messages.update_error") }}',
            type: "error"
          });
        }
      });
    });
    
    // Handle payment type filter
    $('#payment-type-filter').on('change', function() {
      const filterValue = $(this).val();
      
      if (filterValue === 'all') {
        // Show all invoices
        $('.invoice-container > div.invoice-wrapper').show();
      } else if (filterValue === 'cash') {
        // Show only cash payments
        $('.invoice-container > div.invoice-wrapper').each(function() {
          const paymentType = $(this).data('payment-type');
          if (paymentType === 'cash') {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      } else if (filterValue === 'electronic') {
        // Show only ABA and ACLEDA payments
        $('.invoice-container > div.invoice-wrapper').each(function() {
          const paymentType = $(this).data('payment-type');
          if (paymentType === 'aba' || paymentType === 'acleda') {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      }
    });
    
    // Handle print daily invoice click
    $('.print-daily-invoice').on('click', function() {
        const selectedDate = $('#date').val();
        
        if (!selectedDate) {
            swal({
                title: "{{ __('messages.warning') }}",
                text: "{{ __('messages.please_select_date') }}",
                type: "warning"
            });
            return;
        }

        swal({
            title: "{{ __('messages.confirm_print') }}",
            text: "{{ __('messages.print_daily_invoice_confirm') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "{{ __('messages.yes') }}",
            cancelButtonText: "{{ __('messages.no') }}",
            closeOnConfirm: false,
            closeOnCancel: true
        }, function(isConfirm) {
            if (isConfirm) {
                swal({
                    title: "{{ __('messages.printing') }}",
                    text: "{{ __('messages.please_wait') }}",
                    type: "info",
                    showConfirmButton: false,
                    timer: 2000
                });

                $.ajax({
                    url: '/order/print-daily-invoice',
                    type: 'GET',
                    data: {
                        date: selectedDate,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            swal({
                                title: "Success!",
                                text: '{{ __("messages.daily_summary_printed_successfully") }}',
                                type: "success",
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            swal({
                                title: "Error!",
                                text: response.message || '{{ __("messages.error_printing_daily_summary") }}',
                                type: "error"
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = '{{ __("messages.error_printing_daily_summary") }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        swal({
                            title: "Error!",
                            text: errorMessage,
                            type: "error"
                        });
                    }
                });
            }
        });
    });
  });
</script>
@endsection