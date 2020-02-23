@extends('layouts/application')
@section('content')
<!-- Page Content -->
<div class="container-fluid">
   <div class="row">
      <div class="col-md-5 left-side">
         <div class="col-xs-5 table-header">
            <h3>Product</h3>
         </div>
         <div class="col-xs-2 table-header">
            <h3>price</h3>
         </div>
         <div class="col-xs-3 table-header nopadding">
            <h3 class="text-left">Quantity</h3>
         </div>
         <div class="col-xs-2 table-header nopadding">
            <h3>Total</h3>
         </div>
         <div id="productList">
            
         </div>
         <div class="footer-section">
            <div class="table-responsive col-sm-12 totalTab">
               <table class="table">
                  <tr>
                     <td class="active" width="40%">Total Items</td>
                     <td class="whiteBg" width="60%"><span id="Subtot"></span> 
                        <span class="float-right"><b id="ItemsNum"><span></span>item</b></span>
                     </td>
                  </tr>
                     <td class="active">Total</td>
                     <td class="whiteBg light-blue text-bold"><span id="total">$</td>
                  </tr>
               </table>
            </div>
            <button type="button" onclick="cancelPOS()" class="btn btn-red col-md-6 flat-box-btn"><h5 class="text-bold">CANCEL</h5></button>
            <button type="button" class="btn btn-green col-md-6 flat-box-btn" data-toggle="modal" data-target="#AddSale" id="Order"><h5 class="text-bold">ORDER</h5></button>
         </div>

      </div>
      <div class="col-md-7 right-side nopadding">              <!-- product list section -->
         <div id="productList2">
            @foreach ( $products as $product)
               <div class="col-sm-3 col-xs-4 div">
                     <a href="javascript:void(0)" class="addPct" id="product-{{$product->product_code}}" data-id="{{ $product->id }}" style="display: block;">
                        <div class="product color06 flat-box" data-id="{{ $product->id }}">
                        <h3 id="proname" data-id="{{ $product->id }}">{{ $product->product_name }} </h3>
                           <input type="hidden" id="idname-{{ $product->id }}"  name="name" value="{{$product->product_name}}" />
                           <input type="hidden" id="idprice-{{$product->id}}" name="price" value="{{$product->price}}" />
                           <div class="mask" >
                              <h3>{{$product->price}}$</h3>
                           </div>
                        </div>
                     </a>
               </div>
               @endforeach
         </div>
      </div>
   </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
   $('.addPct').on('click',(e)=>{
         let self = e.target,
             card = $(self).parents('.div');
             id = $(card).find('.addPct').attr('data-id');
         if(id == undefined) return;
         
         var name = $('#idname-'+id).val();
         var price = $('#idprice-'+id).val();
         var qt = 1;
         let row = '<div class="col-xs-12 product-card"><div class="panel panel-default product-details"><div class="panel-body" style=""><div class="col-xs-5 nopadding"><div class="col-xs-2 nopadding"><a onclick="delete_posale(this);"><span class="fa-stack fa-sm productD"><i class="fa fa-circle fa-stack-2x delete-product"></i><i class="fa fa-times fa-stack-1x fa-fw fa-inverse"></i></span></a></div><div class="col-xs-10 nopadding"><span class="textPD product-name">' + name + '</span></div></div><div class="col-xs-2"><span class="textPD product-price">' + price + '</span></div><div class="col-xs-3 nopadding productNum"><a onclick="minusQuantity(this)"><span class="fa-stack fa-sm decbutton"><i class="fa fa-square fa-stack-2x light-grey"></i><i class="fa fa-minus fa-stack-1x fa-inverse white"></i></span></a><input type="text" id="qt-' + id + '"class="form-control quantity" value="' + qt + '" placeholder="0" maxlength="2"><a onclick="addQuantity(this)"><span class="fa-stack fa-sm incbutton"><i class="fa fa-square fa-stack-2x light-grey"></i><i class="fa fa-plus fa-stack-1x fa-inverse white"></i></span></a></div><div class="col-xs-2 nopadding "><span class="subtotal textPD">'+ (parseFloat(price * qt).toFixed(2)) +' $</span></div></div></div></div>';
         $('#productList').append(row);
         totalItem();
         totalCash();
   });
});
  function delete_posale(e){
     let card = $(e).parents('.product-card');
     $(card[0]).remove();
     totalItem();
     totalCash();
  }

  function totalItem(){
     let quantity = 0;
     let cards = $('#productList').children();
      for(let i=0; i < cards.length;i++){
         quantity += parseInt($(cards[i]).find('.quantity').val());
      }
      $('#ItemsNum span').text(`${quantity} `);
  }

  function totalCash(){
     let total = 0;
     let cards = $('#productList').children();
      for(let i=0; i < cards.length;i++){
         total += parseFloat(parseInt($(cards[i]).find('.quantity').val()) * parseFloat($(cards[i]).find('.product-price').text()));
      }
      $('#total').text(`${total.toFixed(2)} $`)

  }

  function cancelPOS(){
      let cards = $('#productList').children();
      $(cards).remove();
      totalItem();
      totalCash();
  }

  $('#Order').on('click',(e)=>{
     let data = [];
     let cards = $('#productList').children();
     for(let i=0; i < cards.length;i++){
         let item = {product_name: $(cards[i]).find('.product-name').text(), quantity: $(cards[i]).find('.quantity').val(), price: $(cards[i]).find('.subtotal').text()};
         data.push(item);
      }
      if(data.length == 0)return;

      let formData = {
         "data" : data
      };
      
      $.ajax({
          url: '/pos/add',
          type: "GET", 
          data: formData,
          contentType: false,
          processData: true,
          success: function(res){
            $(cards).remove();
            totalItem();
            totalCash();
            swal({
               title: 'DONE',
               text: 'Order complete',
               type: "success",
            });
          },
          error: function(err){
            console.log(err);
          } 
        });
  })

  function minusQuantity(e){
      let card = $(e).parents('.product-card');
      let quantity = parseInt($(card).find('.quantity').val());
      let result = quantity == 1 ? 1 : quantity - 1;
      $(card).find('.quantity').val(result);
      editQuantity(card);
  }

  function addQuantity(e){
      let card = $(e).parents('.product-card');
      let quantity = parseInt($(card).find('.quantity').val());
      let result = quantity + 1;
      $(card).find('.quantity').val(result);
      editQuantity(card);
  }

  function editQuantity(e){
    
     let price = parseFloat($(e).find('.product-price').text());
     let quantity = parseInt($(e).find('.quantity').val());
     $(e).find('.subtotal').text(`${parseFloat(price * quantity).toFixed(2)}$`);
     totalItem();
     totalCash();
     
  }
  

</script>
@endsection

