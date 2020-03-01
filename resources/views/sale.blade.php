  @extends('layouts/application')
  @section('content')
  <style>
    .table .thead-dark th {
  color: #fff;
  background-color: #059DC0;
  border-color: #059DC0;
}
  </style>
  <div class="container">
    <h3>Sales</h3>
    <br />
    <br />
    <div>
      @foreach ($orders as $order)
      <span style="font-weight: bold;">Order: {{$order->order_no}}</span>
      <span style="float: right; font-weight: bold;">Date: {{  date("d M Y", strtotime($order->created_at)) }}</span> 
    <table class="table table-striped">
      <thead class="thead-dark">
        <tr>
          <th scope="col">#</th>
          <th scope="col">ProductName</th>
          <th scope="col">Size</th>
          <th scope="col">Extra</th>
          <th scope="col">Quantity</th>
          <th scope="col">Total</th>
          
        </tr>
      </thead>
      <tbody>
        @foreach ($order->items as $index => $item)
        <tr>
        <th scope="row">{{ ($index + 1) }}</th>
          <td>{{$item["product_name"]}}</td>
          <td>{{$item["size"]}}</td>
          <td>{{$item["extra"]}}</td>
          <td>{{$item["quantity"]}}</td>
          <td>{{$item["total"]}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @endforeach
    </div>
    {{ $orders->links() }}
  </div>
 @endsection