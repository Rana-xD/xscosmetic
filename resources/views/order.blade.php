@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
<script>
  // Enable pusher logging - don't include this in production
  window.Echo.channel('orders')
           .listen('NewOrder', data =>{
               console.log("New Update");
               addNewOrder(data.message);
           });
  
</script>

@endsection
@section('content')
<style>
  .table .thead-dark th {
    color: #fff;
    background-color: #901FDE;
    border-color: #901FDE;
    }
  .table-individual{
    margin-bottom: 50px;
  }
 .table th {
      font-size: 20px;
      font-weight: bold;
  }
  .table td {
      font-size: 20px;
  }
  .order-no{
      font-size: 20px;
      font-weight: bold;
  }
</style>
<div class="container">
  <h3>Orders</h3>
  <br />
  <br />
  <div class="tables-content">
    
    @foreach ($orders as $order)
    <div class="table-individual">
    <input type="hidden" value="{{ $order->id }}" id="order-id">
    <span class="order-no">Order: {{$order->order_no}}</span>
    <table class="table table-striped">
      <thead class="thead-dark">
        <tr>
          <th scope="col">#</th>
          <th scope="col">ProductName</th>
          <th scope="col">Size</th>
          <th scope="col">Extra</th>
          <th scope="col">Quantity</th>
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
        </tr>
        @endforeach
      </tbody>
    </table>
      <div>
        <button class="btn btn-success complete-order" onclick="removeOrder(this);">DONE</button>
      </div>
    </div>
    @endforeach
    
  </div>
</div>

<script>

  function addNewOrder(order){
      console.log(order);
      let data = `
      <div class="table-individual">
      <input type="hidden" value="${order.id}" id="order-id">
      <span class="order-no">Order: ${order.order_no}</span>
      <table class="table table-striped">
        <thead class="thead-dark">
        <tr>
          <th scope="col">#</th>
          <th scope="col">ProductName</th>
          <th scope="col">Size</th>
          <th scope="col">Extra</th>
          <th scope="col">Quantity</th>
        </tr>
      </thead>
      <tbody>
        <tbody>
        ${order.items.map((item,i)=>`
          <tr>
          <th scope="row">${i + 1}</th>
          <td>${item.product_name}</td>
          <td>${item.size}</td>
          <td>${item.extra == null ? '' : item.extra}</td>
          <td>${item.quantity}</td>
          </tr>
        `).join('')}
      </tbody>
    </table>
    <div>
      <a class="btn btn-success complete-order" onclick="removeOrder(this);">DONE</a>
    </div>
    </div>
      `
    $('.tables-content').prepend(data);
  }        

  function removeOrder(e){
    let table = $(e).parents('.table-individual');
    
    let formData = {
      "id" : $(table).find('#order-id').val()
    };
    $.ajax({
          url: '/order/update',
          type: "GET", 
          data: formData,
          contentType: false,
          processData: true,
          success: function(res){
            $(table).remove();
          },
          error: function(err){
            console.log(err);
          } 
        });
  }
  // $('.complete-order').on('click',(e)=>{
    
    
  // })
</script>
@endsection