@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
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
    align-items: center;
    gap: 10px;
  }

  .calendar-wrapper {
    display: flex;
    flex: 1;
  }

  .label-text {
    margin: 0;
    font-size: 16px;
    font-weight: bold;
    min-width: max-content;
    display: flex;
    align-items: center;
  }

  .date-control-group {
    flex: 1;
    display: flex;
    align-items: center;
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
  }

  .container-fluid {
    margin-bottom: -50px;
  }

  .date {
    width: 70%;
  }

  .table-payment-type-income,
  .table-expense {
    table-layout: fixed;
  }

  .table-expense {
    margin-bottom: 100px;
  }

  .table-payment-type-income td,
  .table-payment-type-income th,
  .table-expense td,
  .table-expense th {
    text-align: center;

  }

  .table-payment-type-income {
    margin-bottom: 50px;
  }

  .table .light-pink th {
    color: #fff;
    background-color: #f5c6cb;
    border-color: #dee2e6;
    font-size: 16px;
  }

  .table .light-yellow th {
    color: #fff;
    background-color: #b8daff;
    border-color: #dee2e6;
    font-size: 16px;
  }

  .income-info {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .date-text {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 20px;
  }

  .total-expense {
    font-size: 26px;
    font-weight: bold;
    color: #1fe01f;
    margin-bottom: 30px;
  }

  .item-no {
    width: 10%;
  }

  .expense-container {
    display: flex;
    justify-content: flex-end;
  }

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

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px;
  }

  .modal-footer .btn {
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
  }

  .modal-footer .loader {
    margin-left: 10px;
  }
</style>
<div class="container">
  <h3>{{ __('messages.daily_expense') }}</h3>
  <br />
  <br />

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4" style="padding-left: 0">
        <div class="calendar">
          <p class="label-text">{{ __('messages.date') }}:</p>
          <div class="date-control-group">
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
      </div>
      <div class="col-md-4">
        <div class="search-wrapper">
          <button type="button" class="btn btn-add" id="handleCustomExpenseSearch">{{ __('messages.search') }}</button>
        </div>
      </div>
      <div class="col-md-4 expense-container">
        <button type="button" class="btn expense-btn btn-primary btn-lg" data-toggle="modal" data-target="#addExpense">{{ __('messages.add_expense') }}</button>
      </div>
    </div>
  </div>
  <div>
    <div class="income-info">
      <p class="date-text">{{ empty($date) ? Carbon\Carbon::now()->format('Y-m-d') : $date }}</p>
      <p class="total-expense">{{empty($total) ? '' : "{$total}$"}}</p>
    </div>

    <table class="table table-striped table-hover table-expense">
      <thead class="light-yellow">
        <tr>
          <th scope="col" class="item-no">#</th>
          <th scope="col">{{ __('messages.name') }}</th>
          <th scope="col">{{ __('messages.cost') }}</th>
          @if (Auth::user()->role == "SUPERADMIN")
          <th scope="col"></th>
          @endif
        </tr>
      </thead>
      <tbody>
        @if (!empty($expense))
        <input type="hidden" value="{{ $expense->id }}" id="expense-id">
        @foreach ($expense->items as $index => $item)
        <tr id="expense-row-{{ $index }}" class="expense-item">
          <th scope="row">{{ ($index + 1) }}</th>
          <td class="expense-name">{{$item["name"]}}</td>
          <td class="expense-cost">{{$item["cost"]}}$</td>
          @if (Auth::user()->role == "SUPERADMIN")
          <td>
            <div class="btn-group">
              <a class="btn btn-default delete-btn delete-expense" data-id="expense-row-{{ $index }}"><i class="fa fa-times" data-id="expense-row-{{ $index }}"></i></a>
            </div>
            <div class="btn-group">
              <a class="btn btn-default edit-expense" data-id="expense-row-{{ $index }}" data-toggle="modal" data-target="#editExpenseModal"><i class="fa fa-pencil-square-o" data-id="expense-row-{{ $index }}"></i></a>
            </div>
          </td>
          @endif
        </tr>
        @endforeach
        @endif
      </tbody>
    </table>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {

    // Get today's date in local timezone (YYYY-MM-DD format)
    const today = new Date();
    const localDate = today.getFullYear() + '-' + 
                     String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                     String(today.getDate()).padStart(2, '0');
    
    $('.expense-btn').toggle($('.selected-date').val() === localDate);

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
      let filter = `/expense/filter?date=${date}`;

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


    $('#handleCustomExpenseSearch').on('click', (e) => {
      e.preventDefault();
      let date = $('.selected-date').val();

      if (date === '') {
        return;
      }
      let filter = `/expense/filter?date=${date}`;

      window.location = filter;
    })

    // Load existing expense items when page loads
    loadExistingExpenseItems();
    
    // Toggle between new and existing expense fields
    $('#expense-type').on('change', function() {
      if ($(this).val() === 'new') {
        $('#new-expense-fields').show();
        $('#existing-expense-fields').hide();
      } else {
        $('#new-expense-fields').hide();
        $('#existing-expense-fields').show();
      }
    });
    
    // Update cost field when selecting an existing expense
    $('#existing-expense-select').on('change', function() {
      const selectedOption = $(this).find('option:selected');
      const cost = selectedOption.data('cost');
      if (cost) {
        $('#existing-expense-cost').val(cost);
      } else {
        $('#existing-expense-cost').val('');
      }
    });
    
    // Function to load existing expense items
    function loadExistingExpenseItems() {
      $.ajax({
        url: '/expense-item/get-all',
        type: 'GET',
        success: function(response) {
          if (response.items && response.items.length > 0) {
            const select = $('#existing-expense-select');
            select.empty();
            select.append(`<option value="">{{ __('messages.select_expense_item') }}</option>`);
            
            response.items.forEach(item => {
              select.append(`<option value="${item.name}" data-cost="${item.cost}">${item.name}</option>`);
            });
          } else {
            $('#existing-expense-select').html('<option value="">{{ __('messages.no_items_found') }}</option>');
          }
        },
        error: function() {
          $('#existing-expense-select').html('<option value="">{{ __('messages.error_loading_items') }}</option>');
        }
      });
    }
    
    $('#addExpenseForm').on("submit", (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      // Validate form based on selected expense type
      const expenseType = $('#expense-type').val();
      let isValid = true;
      
      if (expenseType === 'new') {
        // Validate new expense fields
        if (!$("#ExpenseName").val().trim()) {
          isValid = false;
          alert("{{ __('messages.enter_name') }}");
        } else if (!$("#ExpenseCost").val().trim()) {
          isValid = false;
          alert("{{ __('messages.enter_cost') }}");
        }
      } else {
        // Validate existing expense fields
        if (!$("#existing-expense-select").val()) {
          isValid = false;
          alert("{{ __('messages.select_expense_item') }}");
        } else if (!$("#existing-expense-cost").val().trim()) {
          isValid = false;
          alert("{{ __('messages.enter_cost') }}");
        }
      }
      
      if (!isValid) {
        return false;
      }
      
      let items = [];
      let item = {};
      
      // Get data based on selected expense type
      if (expenseType === 'new') {
        item = {
          name: $("#ExpenseName").val().trim(),
          cost: $("#ExpenseCost").val().trim()
        };
      } else {
        item = {
          name: $("#existing-expense-select").val(),
          cost: $("#existing-expense-cost").val().trim()
        };
      }
      
      items.push(item);
      
      let formData = {
        "items": items,
        "expense_type": expenseType
      };

      $.ajax({
        url: '/expense/add',
        type: "GET",
        data: formData,
        contentType: false,
        processData: true,
        beforeSend: function() {
          showSpinner();
        },
        success: function(res) {
          hideSpinner();
          location.reload();
        },
        error: function(err) {
          hideSpinner();
          console.log(err);
        }
      });
    })

    $('.edit-expense').click(function() {
      var rowId = $(this).data('id');
      var name = $('#' + rowId).find('.expense-name').text();
      var cost = $('#' + rowId).find('.expense-cost').text().replace('$', '');

      hideSpinner();
      $('#ExpenseNameEdit').val(name);
      $('#ExpenseCostEdit').val(cost);
      $('#ExpenseItemId').val(rowId);
      $('#editExpenseModal').modal('show');
    });

    $('#editExpenseForm').on("submit", (e) => {
      e.preventDefault();
      e.stopPropagation();
      let name = $("#ExpenseNameEdit").val();
      let cost = $("#ExpenseCostEdit").val();
      let id = $("#expense-id").val();
      let expenseItemId = $('#ExpenseItemId').val();
      let expenseItems = [];

      // Update the name and cost in the DOM
      $('#' + expenseItemId + " .expense-name").text(name);
      $('#' + expenseItemId + " .expense-cost").text("$" + cost);

      $('.expense-item').each(function() {
        let name = $(this).find('.expense-name').text();
        let cost = $(this).find('.expense-cost').text().replace('$', '');

        // Create an object for each expense item and add it to the array
        expenseItems.push({
          "name": name,
          "cost": cost
        });
      });

      let formData = new FormData();
      formData.append("_token", $('meta[name="csrf_token"]').attr('content'));
      formData.append('id', id);
      formData.append('items', JSON.stringify(expenseItems));

      $.ajax({
        url: '/expense/update',
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function() {
          showSpinner();
        },
        success: function(res) {
          hideSpinner();
          location.reload();
        },
        error: function(err) {
          hideSpinner();
          console.log(err);
        }
      });
    })

    $(".delete-expense").on("click", (e) => {
      swal({
          title: '{{ __('messages.delete_expense_confirm') }}',
          text: '{{ __('messages.delete_expense_text') }}',
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: '{{ __('messages.yes') }}',
          closeOnConfirm: false
        },
        function() {
          let id = $("#expense-id").val();
          let expenseItemId = e.target.getAttribute('data-id');
          $('#' + expenseItemId).remove();
          let expenseItems = [];

          $('.expense-item').each(function() {
            let name = $(this).find('.expense-name').text();
            let cost = $(this).find('.expense-cost').text().replace('$', '');

            // Create an object for each expense item and add it to the array
            expenseItems.push({
              "name": name,
              "cost": cost
            });
          });

          let formData = new FormData();
          formData.append("_token", $('meta[name="csrf_token"]').attr('content'));
          formData.append('id', id);
          formData.append('items', JSON.stringify(expenseItems));

          $.ajax({
            url: '/expense/update',
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
              location.reload();
            },
            error: function(err) {
              console.log(err);
            }
          });
        })
    })

    function showSpinner() {
      $('.loader').css('display', 'block');
      $('.modal-btn').prop('disabled', true);
    }

    function hideSpinner() {
      $('.loader').css('display', 'none');
      $('.modal-btn').prop('disabled', false);
    }
  });
</script>

<!-- Add Modal -->
<div class="modal fade" id="addExpense" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addExpenseForm" action="" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_expense') }}</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="expense-type">{{ __('messages.expense_type') }}</label>
            <select class="form-control" id="expense-type">
              <option value="new">{{ __('messages.new_expense') }}</option>
              <option value="existing">{{ __('messages.existing_expense') }}</option>
            </select>
          </div>
          
          <!-- New expense input fields -->
          <div id="new-expense-fields">
            <div class="form-group">
              <label for="ExpenseName">{{ __('messages.name') }}</label>
              <input type="text" name="name" class="form-control" id="ExpenseName" placeholder="{{ __('messages.enter_name') }}">
            </div>
            <div class="form-group">
              <label for="ExpenseCost">{{ __('messages.cost') }}</label>
              <input type="text" name="cost" class="form-control" id="ExpenseCost" placeholder="{{ __('messages.enter_cost') }}">
            </div>
          </div>
          
          <!-- Existing expense selection -->
          <div id="existing-expense-fields" style="display: none;">
            <div class="form-group">
              <label for="existing-expense-select">{{ __('messages.select_expense') }}</label>
              <select class="form-control" id="existing-expense-select">
                <option value="">{{ __('messages.loading') }}...</option>
              </select>
            </div>
            <div class="form-group">
              <label for="existing-expense-cost">{{ __('messages.cost') }}</label>
              <input type="text" class="form-control" id="existing-expense-cost" placeholder="{{ __('messages.enter_cost') }}">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
          <button type="submit" class="btn btn-add">{{ __('messages.save') }}</button>
          <div class="loader"></div>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- /.Modal -->

<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" role="dialog" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="editExpenseForm" action="" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editExpenseModalLabel">{{ __('messages.edit_expense') }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="expense-name">{{ __('messages.name') }}</label>
            <input type="text" class="form-control" id="ExpenseNameEdit" placeholder="{{ __('messages.enter_name') }}">
          </div>
          <div class="form-group">
            <label for="expense-cost">{{ __('messages.cost') }}</label>
            <input type="text" class="form-control" id="ExpenseCostEdit" placeholder="{{ __('messages.enter_cost') }}">
          </div>
    </form>
    <input type="hidden" name="expenseItemId" id="ExpenseItemId">
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
    <div class="loader"></div>
  </div>
</div>
</div>
</div>
@endsection