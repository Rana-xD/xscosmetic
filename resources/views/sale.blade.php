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
  .table-total-item {
    table-layout: fixed;
  }

  .table-payment-type-income td, 
  .table-payment-type-income th,
  .table-total-item td,
  .table-total-item th {
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

.total-income {
  font-size: 26px;
  font-weight: bold;
  color: #1fe01f;
  margin-bottom: 30px;
}

  </style>
  <div class="container">
    <h3>Daily Income</h3>
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
          <button type="button" class="btn btn-add" id="handleCustomIncomeSearch">Search</button>
        </div>
      </div>
    </div>  
    <div>
    <div class="income-info">
      <p class="date-text">{{ empty($date) ? Carbon\Carbon::now()->format('Y-m-d') : $date }}</p>
      <p class="total-income">{{$data["total"] === 0 ? '' : "{$data["total"]}$"}}</p>
    </div>
      <table class="table table-payment-type-income">
      <thead class="light-pink">
        <tr>
          <th scope="col">Cash</th>
          <th scope="col">ABA</th>
          <th scope="col">ACLEDA</th>
          <th scope="col">Delivery</th>
          
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{$data["payment_type_income"]["cash"]}}$</td>
          <td>{{$data["payment_type_income"]["aba"]}}$</td>
          <td>{{$data["payment_type_income"]["acleda"]}}$</td>
          <td>{{$data["payment_type_income"]["delivery"]}}$</td>
        </tr>
      </tbody>
      </table>

      <table class="table table-striped table-total-item">
      <thead class="light-yellow">
        <tr>
          <th scope="col">#</th>
          <th scope="col">Name</th>
          <th scope="col">Quantity</th>
          <th scope="col">Total</th>
          
        </tr>
      </thead>
      <tbody>
        @foreach ($data['items'] as $index => $item)
        <tr>
        <th scope="row">{{ ($index + 1) }}</th>
          <td>{{$item["product_name"]}}</td>
          <td>{{$item["quantity"]}}</td>
          <td>{{$item["total"]}}</td>
        </tr>
        @endforeach
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

      $('#handleCustomIncomeSearch').on('click',(e) =>{
        e.preventDefault();
        let date = $('.selected-date').val();

        if(date === ''){
          return;
        }
        let filter = `/income-report/filter?date=${date}`;

        window.location = filter;
      })
    });
  </script>
 @endsection