@extends('layouts/application')
@section('content')
<!-- Page Content -->
<div class="container-fluid">
   <div class="row">
      <div class="col-md-6 left-side">
         <div class="col-xs-3 table-header">
            <h3>Product</h3>
         </div>
         <div class="col-xs-2 table-header">
            <h3 class="text-left">Size</h3>
         </div>
         <div class="col-xs-2 table-header nopadding">
            <h3 class="text-left">Quantity</h3>
         </div>
         <div class="col-xs-3 table-header">
            <h3 class="text-left">Extra</h3>
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
      <div class="col-md-1 right-side nopadding">              <!-- product list section -->
               <div class="col-sm-12 col-xs-12 div">
                     <a href="javascript:void(0)" class="size" data-id="S" style="display: block;">
                        <div class="product color01 flat-box" data-id="S">
                        <h3 id="proname" data-id="S">S</h3>
                        </div>
                     </a>
               </div>
               <div class="col-sm-12 col-xs-12 div">
                  <a href="javascript:void(0)" class="size" id="M" data-id="S" style="display: block;">
                  <div class="product color02 flat-box" data-id="M">
                  <h3 id="proname" data-id="M">M</h3>
                  </div>
               </a>
         </div>
         <div class="col-sm-12 col-xs-12 div">
            <a href="javascript:void(0)" class="size" data-id="L" style="display: block;">
            <div class="product color03 flat-box" data-id="L">
            <h3 id="proname" data-id="L">L</h3>
            </div>
         </a>
         </div>
         <div class="col-sm-12 col-xs-12 div">
            <a href="javascript:void(0)" class="size" data-id="XL" style="display: block;">
            <div class="product color04 flat-box" data-id="XL">
            <h3 id="proname" data-id="XL">XL</h3>
            </div>
         </a>
   </div>
      </div>
      <div class="col-md-5 right-side nopadding">  
         <div class="row row-horizon">
            <span class="categories selectedGat" id=""><i class="fa fa-home"></i></span>
               @foreach (App\Category::all() as $category)
               <span class="categories" id="{{$category->id}}">{{$category->name}}</span>
               @endforeach
               
            
         </div>            <!-- product list section -->
         <div id="productList2">
            @foreach ( $products as $product)
               <div class="col-sm-3 col-xs-4 div">
                     <a href="javascript:void(0)" class="addPct" id="product-{{$product->product_code}}" data-id="{{ $product->id }}" style="display: block;">
                        <div class="product color06 flat-box" data-id="{{ $product->id }}">
                        <h3 id="proname" data-id="{{ $product->id }}">{{ $product->product_no }}. {{ $product->product_name }} </h3>
                           <input type="hidden" id="idname-{{ $product->id }}"  name="name" value="{{$product->product_name}}" />
                           <input type="hidden" id="idprice-{{$product->id}}" name="price" value="{{$product->price}}" />
                           <input type="hidden" id="category" name="category" value="{{$product->category->id}}" />
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
         var categoryID = $(card).find('#category').val();

         if(categoryID==3){
            addExtra(name);
            return;
         }
         var qt = 1;
         var price = 0
         
         let string = name.split(",");
         let productName = '';
         for(let i=0; i < string.length; i++){
            productName += `<span class="textPD product-name" onclick="removeProduct(this)">${string[i]} </span> \n`
         }

         let row = `<div class="col-xs-12 product-card">
                        <div class="panel panel-default product-details">
                           <div class="panel-body" style="">
                              <div class="col-xs-3 nopadding">
                                 <div class="col-xs-3 nopadding">
                                    <a onclick="delete_posale(this);">
                                       <span class="fa-stack fa-sm productD">
                                          <i class="fa fa-circle fa-stack-2x delete-product"></i>
                                          <i class="fa fa-times fa-stack-1x fa-fw fa-inverse"></i>
                                       </span>
                                    </a>
                                 </div>
                              <div class="col-xs-9 nopadding">
                                 <input type="hidden" class="category-id"  name="category-id" value="${categoryID}" />
                                 <input type="hidden" class="product-price"  name="product-price" value="${price}" />
                                 <input type="hidden" class="extra-price"  name="extra-price" value="0" />
                                 <input type="hidden" class="product-name-final"  name="product-name-final" value="" />
                                 <div class="product-name-content">
                                    
                                 </div>
                              </div>
                              </div>
                              <div class="col-xs-2 nopadding">
                                 <span class="size textPD"></span>
                              </div>
                              <div class="col-xs-2 nopadding productNum">
                                 <a onclick="minusQuantity(this)">
                                    <span class="fa-stack fa-sm decbutton">
                                       <i class="fa fa-square fa-stack-2x light-grey"></i>
                                       <i class="fa fa-minus fa-stack-1x fa-inverse white"></i>
                                    </span>
                                 </a>
                              <input type="text" id="qt-${id}"class="form-control quantity" value="${qt}" placeholder="0" maxlength="2">
                               <a onclick="addQuantity(this)">
                                 <span class="fa-stack fa-sm incbutton">
                                    <i class="fa fa-square fa-stack-2x light-grey"></i>
                                    <i class="fa fa-plus fa-stack-1x fa-inverse white"></i>
                                 </span>
                              </a>
                           </div>
                           <div class="col-xs-3 nopadding ">
                              <span class="extra textPD"></span>
                            </div>
                         <div class="col-xs-2 nopadding ">
                        <span class="subtotal textPD">${ (parseFloat(price * qt).toFixed(2))}$</span>
                        </div>
                        </div>
                     </div>
                  </div>`;
         let html = $.parseHTML(row);
         $(html).find('.product-name-content').append(productName);
         $('#productList').append(html);
         

         totalItem();
         totalCash();
         
      });

   });

   function totalProduct(html){
      let card = $(html).find('.product-name-content').children();
      let productName = '';
      for(let i=0;i<card.length;i++){
         card.length - i == 1 ? productName += `${$(card[i]).text()} `  : productName += `${$(card[i]).text()}, `;  
      }
      
      $(html).find('.product-name-final').val(productName.trim());
   }

   function productPrice(size,categoriesID){
      switch (size){
         case 'S':
            return categoriesID == 1 ? 3.00 : 4.00;
         break;
         case 'M':
            return categoriesID == 1 ? 3.50 : 4.50;
         break;
         case 'L':
            return categoriesID == 1 ? 4.00 : 5.00;
         break;
         case 'XL':
            return categoriesID == 1 ? 6.00 : 7.00;
         break;
      }
   }

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
         total += parseFloat($(cards[i]).find('.subtotal').text());
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
         totalProduct(cards[i]);
         let item = {product_name: $(cards[i]).find('.product-name-final').val(), quantity: $(cards[i]).find('.quantity').val(), size: $(cards[i]).find('.size').text(), extra: $(cards[i]).find('.extra').text(), total: $(cards[i]).find('.subtotal').text()};
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

  function addExtra(name){
     if($('#productList').children().length==0) return;
     let card = $('#productList').children().last();
     $(card).find('.extra').append(`${name}, `);
     let price = parseFloat($(card).find('.extra-price').val()) + 1.00;
     $(card).find('.extra-price').val(price);
     editQuantity(card);
  }

  function editQuantity(e){
    
     let price = parseFloat($(e).find('.product-price').val()) ;
     let quantity = parseInt($(e).find('.quantity').val());
     let extra = parseFloat($(e).find('.extra-price').val());
     $(e).find('.subtotal').text(`${parseFloat((price + extra) * quantity).toFixed(2)}$`);
     totalItem();
     totalCash();
     
  }

  function removeProduct(e){
     let self = $(e);
     $(self).remove();
     let html = $(e).parents('.product-card');
  }
  
  $(".categories").on("click", function () {

   //   console.log("HELLOI");
   //Retrieve the input field text
   var filter = $(this).attr('id');
   $(this).parent().children().removeClass('selectedGat');

   $(this).addClass('selectedGat');
   // Loop through the list
   $("#productList2 #category").each(function(){
      // If the list item does not contain the text phrase fade it out
      if ($(this).val().search(new RegExp(filter, "i")) < 0) {
         $(this).parent().parent().parent().hide();
         // Show the list item if the phrase matches
      } else {
         $(this).parent().parent().parent().show();
      }
      });
   });

  $('.size').on('click',function(e){

   if($('#productList').children().length==0) return;
     let size = $(e.target).attr('data-id');
     let card = $('#productList').children().last();
     let categoriesID = $(card).find('.category-id').val();
     $(card).find('.size').text(size);
     let price = productPrice(size,categoriesID);
     $(card).find('.product-price').val(price);
     editQuantity(card);
     
  });

  

</script>
@endsection

