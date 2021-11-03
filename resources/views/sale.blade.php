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
  </style>
  <div class="container">
    <h3>Sales</h3>
    <br />
    <br />
    <div>
      @foreach ($orders as $order)
      <p class="cashier-name">Cashier: {{$order->cashier}}</p>
      <span style="font-weight: bold;">Order: {{$order->order_no}}</span>
      <span style="float: right; font-weight: bold;">Date: {{  date("d M Y", strtotime($order->created_at)) }} | {{$order->time}}</span> 
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
          <td>${{$item["discount"]}}</td>
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
 @endsection