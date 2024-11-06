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
</style>
<div class="container">
  <h3>Daily Expense</h3>
  <br />
  <br />

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4" style="padding-left: 0">
        <div class="calendar">
          <p class="label-text">Date:</p>
          <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
            <input type="text" class="form-control selected-date datepicker" value="{{ empty($date) ? '' : $date }}">
            <div class="input-group-addon">
              <span class="glyphicon glyphicon-th"></span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <button type="button" class="btn btn-add" id="handleCustomExpenseSearch">Search</button>
      </div>
      <div class="col-md-4 expense-container">
        <button type="button" class="btn expense-btn btn-primary btn-lg" data-toggle="modal" data-target="#addExpense">Add Expense</button>
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
          <th scope="col">Name</th>
          <th scope="col">Cost</th>
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

    $('.expense-btn').toggle($('.selected-date').val() === new Date().toISOString().split('T')[0]);

    $('.datepicker').datepicker({
      format: 'mm/dd/yyyy',
      startDate: 'today'
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

    $('#addExpenseForm').on("submit", (e) => {
      e.preventDefault();
      e.stopPropagation();
      let name = $("#ExpenseName").val();
      let cost = $("#ExpenseCost").val();
      let items = [];

      item = {
        name: name,
        cost: cost,
      };

      items.push(item);

      formData = {
        "items": items
      }

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
          title: 'Are you sure?',
          text: 'Delete Expense Item',
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: 'YES',
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
          <h4 class="modal-title" id="myModalLabel">New Expense</h4>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="UnitName">Name</label>
            <input type="text" name="name" Required class="form-control" id="ExpenseName" placeholder="Name">
          </div>
          <div class="form-group">
            <label for="UnitName">Cost</label>
            <input type="text" name="cost" Required class="form-control" id="ExpenseCost" placeholder="Cost">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-add">Submit</button>
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
          <h5 class="modal-title" id="editExpenseModalLabel">Edit Expense</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="expense-name">Name</label>
            <input type="text" class="form-control" id="ExpenseNameEdit" placeholder="Enter name">
          </div>
          <div class="form-group">
            <label for="expense-cost">Cost</label>
            <input type="text" class="form-control" id="ExpenseCostEdit" placeholder="Enter cost">
          </div>
    </form>
    <input type="hidden" name="expenseItemId" id="ExpenseItemId">
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary">Update</button>
    <div class="loader"></div>
  </div>
</div>
</div>
</div>
@endsection