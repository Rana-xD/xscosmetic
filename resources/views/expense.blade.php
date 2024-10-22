@extends('layouts/application')
  @section('content')
  <style>
    .table .thead-dark th {
  color: #fff;
  background-color: #059DC0;
  border-color: #059DC0;
  }
  .cashier-name{
    font-weight: bold;
    font-size: 18px;
    color: black;
  }
  .calendar{
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

  .date{
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

.item-no{
    width: 10%;
}

.expense-container {
    display: flex;
    justify-content: flex-end;
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
            <div class="input-group date"data-provide="datepicker" data-date-format="yyyy-mm-dd">
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
        </tr>
      </thead>
      <tbody>
        @if (!empty($expense))
        @foreach ($expense->items as $index => $item)
        <tr>
        <th scope="row">{{ ($index + 1) }}</th>
          <td>{{$item["name"]}}</td>
          <td>{{$item["cost"]}}$</td>
        </tr>
        @endforeach
        @else
        <tr>
          <td colspan="3">No data</td>
        </tr>
        @endif
      </tbody>
      </table>
    </div>
  </div>

  <script type="text/javascript">
    $(document).ready(function() {
      $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            startDate: 'today'
      });

      $('#handleCustomExpenseSearch').on('click',(e) =>{
        e.preventDefault();
        let date = $('.selected-date').val();

        if(date === ''){
          return;
        }
        let filter = `/expense/filter?date=${date}`;

        window.location = filter;
      })

      $('#addExpenseForm').on("submit",(e) => {
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
          success: function(res){
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      })
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
             <input type="text" name="name" maxlength="100" Required class="form-control" id="ExpenseName" placeholder="Name">
           </div>
           <div class="form-group">
             <label for="UnitName">Cost</label>
             <input type="number" name="cost" maxlength="100" Required class="form-control" id="ExpenseCost" placeholder="Cost">
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