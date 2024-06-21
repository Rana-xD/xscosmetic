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
  .table {
    margin-bottom: 30px;
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
            <p class="label-text">Start Date: </p>
            <div class="input-group date"data-provide="datepicker" data-date-format="yyyy-mm-dd">
              <input type="text" class="form-control start-date datepicker" value="{{ empty($start_date) ? '' : $start_date }}">
              <div class="input-group-addon">
                  <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="calendar">
            <p class="label-text">End Date: </p>
            <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
              <input type="text" class="form-control end-date datepicker" value="{{ empty($end_date) ? '' : $end_date }}">
              <div class="input-group-addon">
                 <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <button type="button" class="btn btn-add" id="handleCustomInvoiceSearch">Search</button>
        </div>
      </div>
    </div>  
  
  
    <div>
      @foreach ($orders as $order)
      <p class="cashier-name">Cashier: {{$order->cashier}}</p>
      <div class="invoice-info" style="display: flex; justify-content: space-between">
        <span style="font-weight: bold;">Order: {{$order->order_no}}</span>
        <span style="font-weight: bold;">Date: {{  date("d M Y", strtotime($order->created_at)) }} | {{$order->time}}</span> 
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
    @endforeach
    </div>
    {{ $orders->links() }}
  </div>

  <script type="text/javascript">
    $(document).ready(function() {
      $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            startDate: 'today'
      });

      $('#handleCustomInvoiceSearch').on('click',(e) =>{
        e.preventDefault();
        let startDate = $('.start-date').val();
        let endDate = $('.end-date').val();

        if(startDate === ''){
          return;
        }
        let filter = `/invoice/filter?start_date=${startDate}&end_date=${endDate}`;

        window.location = filter;
      })
    });
  </script>
 @endsection