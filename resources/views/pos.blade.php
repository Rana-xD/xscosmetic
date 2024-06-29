@extends('layouts/application')
@section('head')
<style>
    #searchContainer {
        margin-top: 20px;
        display: flex;
        gap: 20px;
    }

    #searchContainer .stylish-input-group {
        flex: 1;
    }
    
</style>
@endsection
@section('content')
<!-- Page Content -->
<div class="container-fluid">
   <div class="row">
      <div class="col-md-6 left-side">
         <div class="col-xs-3 table-header">
            <h3>Product</h3>
         </div>
         <div class="col-xs-1 table-header">
            <h3 class="text-left">Price</h3>
         </div>
         <div class="col-xs-2 table-header nopadding">
            <h3 class="text-left">Quantity</h3>
         </div>
         <div class="col-xs-2 table-header">
            <h3 >Discount %</h3>
         </div>
         <div class="col-xs-2 table-header">
            <h3 >Discount USD</h3>
         </div>
         <div class="col-xs-2 table-header nopadding">
            <h3>Total</h3>
         </div>
         <div id="productList">
            
         </div>
         <div class="footer-section">
            <div class="table-responsive col-sm-12 totalTab">
               <table class="table cashier-section">
                  <tr>
                     <td class="active" width="40%">Total Items</td>
                     <td class="whiteBg" width="60%"><span id="Subtot"></span> 
                        <span class="float-right"><b id="ItemsNum"><span></span>item</b></span>
                     </td>
                  </tr>
                  <tr>
                     <td class="active" width="40%">Discount All %</td>
                     <td class="whiteBg" width="60%"><span id="Subtot"></span> 
                        <input type="text" class="form-control discount-input overall-discount" value="" placeholder="0" maxlength="3" onkeyup="handleProductOverallDiscount(this)">
                     </td>
                  </tr> 
                  <tr>
                     <td class="active">Total in USD</td>
                     <td class="whiteBg light-blue text-bold"><span id="total-usd" total-usd-data="">$</td>
                  </tr>
                  <tr>
                     <td class="active">Total in KHR</td>
                     <td class="whiteBg red text-bold"><span id="total-riel" total-riel-data="">៛</td>
                  </tr>
               </table>
            </div>
            <button type="button" onclick="cancelPOS()" class="btn btn-red col-md-6 flat-box-btn"><h5 class="text-bold">CANCEL</h5></button>
            <button type="button" class="btn btn-green col-md-6 flat-box-btn" data-toggle="modal" id="Order"><h5 class="text-bold">ORDER</h5></button>
            
         </div>
         
        <!-- <button type="button" class="btn btn-blue col-md-12 flat-box-btn print-invoice-daily-container" id="PrintDailyInvoice"><h5 class="text-bold">PRINT</h5></button> -->
         
         
      </div>
      
      <div class="col-md-6 right-side nopadding">  
         <div class="row row-horizon">
            <span class="categories selectedGat" id=""><i class="fa fa-home"></i></span>
               @foreach (App\Category::orderBy('name', 'ASC')->get() as $category)
               <span class="categories categories-name" id="{{$category->id}}">{{$category->name}}</span>
               @endforeach
               
            
         </div>    
         <div class="col-sm-12">
            <div id="searchContainer">
                <div class="input-group stylish-input-group">
                    <input type="text" id="searchBrand" class="form-control"  placeholder="Search Brand" >
                    <span class="input-group-addon">
                        <button type="submit">
                            <span class="glyphicon glyphicon-search"></span>
                        </button>
                    </span>
                </div>
                <div class="input-group stylish-input-group">
                    <input type="text" id="searchProd" class="form-control"  placeholder="Search Product Name" >
                    <span class="input-group-addon">
                        <button type="submit">
                            <span class="glyphicon glyphicon-search"></span>
                        </button>
                    </span>
                </div>
                <div class="input-group stylish-input-group">
                    <input type="text" id="searchBarcode" class="form-control"  placeholder="Search Product Barcode" >
                    <span class="input-group-addon">
                        <button type="submit">
                            <span class="glyphicon glyphicon-search"></span>
                        </button>
                    </span>
                </div>
            </div>
        </div>        <!-- product list section -->
         <div id="productList2">
            @foreach ( $products as $product)
               <div class="col-xs-4 div">
                     <a href="javascript:void(0)" class="addPct" id="product-{{$product->product_code}}" data-id="{{ $product->id }}" style="display: block;">
                        <div class="product color06 flat-box" data-id="{{ $product->id }}">
                        <h3 id="proname" data-id="{{ $product->id }}">{{ $product->name }} <br><br> ({{ $product->stock }})</h3>
                           <input type="hidden" id="idname-{{ $product->id }}"  name="name" value="{{$product->name}}" />
                           <input type="hidden" id="idprice-{{$product->id}}" name="price" value="{{$product->price}}" />
                           <input type="hidden" id="idcost-{{$product->id}}" name="cost" value="{{$product->cost}}" />
                           <input type="hidden" id="category" name="category" value="{{$product->category->id}}" />
                           <input type="hidden" id="barcode" name="barcode" value="{{$product->product_barcode}}" />
                        </div>
                     </a>
               </div>
               @endforeach
               <input type="hidden" id="exchange_rate" name="exchange_rate" value="{{$exchange_rate}}" />
         </div>
      </div>
   </div>
</div>

<script type="text/javascript">
    var items = [];
    let code = "";
    let reading = false;

$(document).ready(function() {
    
    $('.addPct').on('click', (e) => {
        let self = e.target,
            card = $(self).parents('.div');
        id = $(card).find('.addPct').attr('data-id');
        if (id == undefined) return;

        var name = $('#idname-' + id).val();
        var price = $('#idprice-' + id).val();
        var categoryID = $(card).find('#category').val();
        var cost = $('#idcost-' + id).val();
        var unit = $(card).find('#unit').val();

        if (items.includes(name)) {
            let element = getProductElement(id);
            addQuantity(element);
            return;
        }
        items.push(name);
        
        var qt = 1;
        // var price = 0

        let string = name.split(",");
        let productName = '';
        for (let i = 0; i < string.length; i++) {
            productName += `<span class="textPD product-name">${string[i]} </span> \n`
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
                                 <input type="hidden" class="product-id"  name="product-id" value="${id}" />
                                 <input type="hidden" class="category-id"  name="category-id" value="${categoryID}" />
                                 <input type="hidden" class="unit-id"  name="unit-id" value="${unit}" />
                                 <input type="hidden" class="product-cost"  name="product-cost" value="${cost}" />
                                 <input type="hidden" class="product-price"  name="product-price" value="${price}" />
                                 <input type="hidden" class="product-discount-price"  name="product-discount-price" value="${price}" />
                                 <input type="hidden" class="extra-price"  name="extra-price" value="0" />
                                 <input type="hidden" class="product-name"  name="product-name" value="${name}" />
                                 <div class="product-name-content">
                                    
                                 </div>
                              </div>
                              </div>
                              <div class="col-xs-1 nopadding">
                                 <span class="size textPD">${price}$</span>
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
                           <div class="col-xs-2">
                              <input type="text" class="form-control discount-input discount" value="" placeholder="0" maxlength="3" onblur="handleProductDiscount(this)">
                            </div>
                            <div class="col-xs-2">
                              <input type="text" class="form-control discount-input discount-in-usd" value="" placeholder="0" maxlength="3" onblur="handleProductDiscountInUSD(this)">
                            </div>
                         <div class="col-xs-2 nopadding ">
                        <span class="subtotal textPD" subtotal-data="${parseFloat(price * qt).toFixed(2)}">$ ${ (parseFloat(price * qt).toFixed(2))}</span>
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

    function addProduct(element) {
        let  card = $(element).parents('.div');
        let id = $(card).find('.addPct').attr('data-id');
        if (id == undefined) return;

        var name = $('#idname-' + id).val();
        var price = $('#idprice-' + id).val();
        var categoryID = $(card).find('#category').val();
        var cost = $('#idcost-' + id).val();
        var unit = $(card).find('#unit').val();

        if (items.includes(name)) {
            let element = getProductElement(id);
            addQuantity(element);
            return;
        }
        items.push(name);
        
        var qt = 1;
        // var price = 0

        let string = name.split(",");
        let productName = '';
        for (let i = 0; i < string.length; i++) {
            productName += `<span class="textPD product-name">${string[i]} </span> \n`
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
                                 <input type="hidden" class="product-id"  name="product-id" value="${id}" />
                                 <input type="hidden" class="category-id"  name="category-id" value="${categoryID}" />
                                 <input type="hidden" class="unit-id"  name="unit-id" value="${unit}" />
                                 <input type="hidden" class="product-cost"  name="product-cost" value="${cost}" />
                                 <input type="hidden" class="product-price"  name="product-price" value="${price}" />
                                 <input type="hidden" class="product-discount-price"  name="product-discount-price" value="${price}" />
                                 <input type="hidden" class="extra-price"  name="extra-price" value="0" />
                                 <input type="hidden" class="product-name"  name="product-name" value="${name}" />
                                 <div class="product-name-content">
                                    
                                 </div>
                              </div>
                              </div>
                              <div class="col-xs-1 nopadding">
                                 <span class="size textPD">${price}$</span>
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
                           <div class="col-xs-2">
                              <input type="text" class="form-control discount-input discount" value="" placeholder="0" maxlength="3" onblur="handleProductDiscount(this)">
                            </div>
                            <div class="col-xs-2">
                              <input type="text" class="form-control discount-input discount-in-usd" value="" placeholder="0" maxlength="3" onblur="handleProductDiscountInUSD(this)">
                            </div>
                         <div class="col-xs-2 nopadding ">
                        <span class="subtotal textPD" subtotal-data="${parseFloat(price * qt).toFixed(2)}">$ ${ (parseFloat(price * qt).toFixed(2))}</span>
                        </div>
                        </div>
                     </div>
                  </div>`;
        let html = $.parseHTML(row);
        $(html).find('.product-name-content').append(productName);
        $('#productList').append(html);


        totalItem();
        totalCash();
}

    document.addEventListener('keypress', e => {
        //usually scanners throw an 'Enter' key at the end of read
        if (e.keyCode === 13) {
            if(code.length > 10) {
                element = getProductElementByBarcode(code);
                addProduct(element);            
                code = "";
            }
        } else {
            code += e.key; //while this is not an 'enter' it stores the every key            
        }

        //run a timeout of 200ms at the first read and clear everything
        if(!reading) {
            reading = true;
            setTimeout(() => {
                code = "";
                reading = false;
            }, 400);  //200 works fine for me but you can adjust it
        }
    });

    function getProductElement(id) {

        let cards = $('#productList').children();


        for (let i = 0; i < cards.length; i++) {
            if($(cards[i]).find('.product-id').val() == id) {
                return $(cards[i]).find('.product-name-content');
            }
        }
    }

    function getProductElementByBarcode(barcode) {
        let cards = $('#productList2').children();


        for (let i = 0; i < cards.length; i++) {
            if($(cards[i]).find('#barcode').val() == barcode) {
             return $(cards[i]).find('.addPct');
            }
        }
    }

    $("#searchProd").keyup(function(){
      // Retrieve the input field text
      var filter = $(this).val();
      // Loop through the list
      $("#productList2 #proname").each(function(){
         // If the list item does not contain the text phrase fade it out
         if ($(this).text().search(new RegExp(filter, "i")) < 0) {
             $(this).parent().parent().parent().hide();
         // Show the list item if the phrase matches
         } else {
             $(this).parent().parent().parent().show();
         }
      });

   });

   $("#searchBrand").keyup(function(){
      // Retrieve the input field text
      var filter = $(this).val();

      $(".categories-name").each(function(){
        if ($(this).text().search(new RegExp(filter, "i")) < 0) {
             $(this).hide();
            
         } else {
             $(this).show();
         }
      })
   });

   $("#searchBarcode").keyup(function(){
      // Retrieve the input field text
      var filter = $(this).val();

      $("#productList2 #barcode").each(function(){
         // If the list item does not contain the text phrase fade it out
         if ($(this).val().search(new RegExp(filter, "i")) < 0) {
             $(this).parent().parent().parent().hide();
         // Show the list item if the phrase matches
         } else {
             $(this).parent().parent().parent().show();
         }
      });
   });

   $('#posOrder').on('submit', (e) => {
    e.preventDefault();
    
    let data = [];
    let invoice = [];
    let total = 0;
    let totalRiel = 0;
    let cards = $('#productList').children();
    let invoiceNo = $('#invoice-no').val();
    let paymentType = $('#payment-type').val();


    for (let i = 0; i < cards.length; i++) {
        totalProduct(cards[i]);

        let cost = $(cards[i]).find('.product-cost').val(),
        quantity = parseInt($(cards[i]).find('.quantity').val()),
            totalCost = (cost * quantity).toFixed(2),
            totalPrice = parseFloat(($(cards[i]).find('.subtotal').text()).replace('$', '')).toFixed(2),
            profit = parseFloat(totalPrice - totalCost).toFixed(2),
            discount = 0;

            if($(cards[i]).find('.discount').val()){
                discount = `${$(cards[i]).find('.discount').val()}%`;
            }else if ($(cards[i]).find('.discount-in-usd').val()) {
                discount = `${$(cards[i]).find('.discount-in-usd').val()}$`;
            }

        let item = {
            product_id: parseInt($(cards[i]).find('.product-id').val()),
            product_name: $(cards[i]).find('.product-name').val(),
            quantity: `${quantity}`,
            price: $(cards[i]).find('.product-price').val(),
            discount: discount,
            total: $(cards[i]).find('.subtotal').text(),
            cost: `$ ${totalCost}`,
            profit: `$ ${profit}`
        };
        data.push(item);
    }

    for (let i = 0; i < cards.length; i++) {
        totalProduct(cards[i]);

        let price = handleRemoveZeroDecimal($(cards[i]).find('.product-price').val()),
            quantity = parseInt($(cards[i]).find('.quantity').val()),
            totalPrice = handleRemoveZeroDecimal(parseFloat(($(cards[i]).find('.subtotal').text()).replace('$', '')).toFixed(2)),


            item = {
                product_name: $(cards[i]).find('.product-name').val(),
                quantity: `${quantity}`,
                price: price,
                discount: $(cards[i]).find('.discount').val() === '' ? `0` : `$${$(cards[i]).find('.discount').val()}`,
                total: totalPrice
            };
        invoice.push(item);
    }
    total = handleRemoveZeroDecimal($('#total-usd').attr('total-usd-data'));
    totalRiel = $('#total-riel').attr('total-riel');
    if (data.length == 0) return;
    let formData = {
        "data": data,
        "invoice": invoice,
        "invoice_no": invoiceNo,
        "total": total,
        "total_riel": totalRiel,
        "payment_type": paymentType
    };

    $.ajax({
        url: '/pos/add',
        type: "GET",
        data: formData,
        contentType: false,
        processData: true,
        success: function(res) {
            $(cards).remove();
            totalItem();
            totalCash();
            swal({
                title: 'DONE',
                text: 'Order complete',
                type: "success",
                timer: 1500,
                showCancelButton: false,
                showConfirmButton: false
            }, function(data) {
                location.reload(true);
            });


        },
        error: function(err) {
            console.log(err);
        }
    });
    })

    $('#payment-type').on('change', function() {
        let type = $(this).val();
        if(type === 'aba' || type === 'acleda' || type === 'delivery') {
            $('.payment-type-cash').css('display','none');
        }else {
            $('.payment-type-cash').css('display','block');
        }
    });

    $('#received-cash-in-usd').on('input',function(e){

        let value = $(this).val();
        
        let totalUSD = $('#total-usd').attr('total-usd-data');

        let changeInUSD = value - totalUSD;
        let changeInRiel = handleExchangeToRielCurrency(changeInUSD);
        $('#change-in-riel').val(`${changeInRiel} ៛`);
        $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);

        if(value != '') {
            $("#received-cash-in-riel").prop('disabled', true);
        }else {
            $("#received-cash-in-riel").prop('disabled', false);
            $('#change-in-riel').val('');
            $('#change-in-usd').val('');
        }
    });

    $('#received-cash-in-riel').on('input',function(e){

        let value = $(this).val();
        
        
        let totalUSD = $('#total-usd').attr('total-usd-data');
        let totalRiel = handleRemoveCommafromRielCurrency($('#total-riel').attr('total-riel-data'));

        let changeInRiel = addComma(value - totalRiel);
        
        
        $('#change-in-riel').val(`${changeInRiel} ៛`);


        if(value != '') {
            $("#received-cash-in-usd").prop('disabled', true);
        }else {
            $("#received-cash-in-usd").prop('disabled', false);
            $('#change-in-riel').val('');
            $('#change-in-usd').val('');
        }
    });

    

});


function delete_posale(e) {
    let card = $(e).parents('.product-card');
    let name = $(card).find('.product-name').val();
    items = items.filter(item => item !== name);
    $(card[0]).remove();
    totalItem();
    totalCash();
}

function totalProduct(html) {
    let card = $(html).find('.product-name-content').children();
    let productName = '';
    for (let i = 0; i < card.length; i++) {
        card.length - i == 1 ? productName += `${$(card[i]).text()} ` : productName += `${$(card[i]).text()}, `;
    }

    $(html).find('.product-name-final').val(productName.trim());
}

function productPrice(size, categoriesID) {
    switch (size) {
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



function totalItem() {
    let quantity = 0;
    let cards = $('#productList').children();
    for (let i = 0; i < cards.length; i++) {
        quantity += parseInt($(cards[i]).find('.quantity').val());
    }
    $('#ItemsNum span').text(`${quantity} `);
}

function totalCash() {
    let total = 0;
    let totalRiel = 0;
    let cards = $('#productList').children();
    for (let i = 0; i < cards.length; i++) {
        total += parseFloat($(cards[i]).find('.subtotal').text().replace('$', ''));
    }
    totalRiel = handleExchangeToRielCurrency(total);
    $('#total-usd').text(`$ ${total.toFixed(2)}`)
    $('#total-usd').attr('total-usd-data', total.toFixed(2));
    $('#total-riel').text(`៛ ${totalRiel}`);
    $('#total-riel').attr('total-riel-data', totalRiel);

}

function cancelPOS() {
    let cards = $('#productList').children();
    $(cards).remove();
    items = [];
    totalItem();
    totalCash();
}


$('#PrintDailyInvoice').on('click', (e) =>{
    e.preventDefault();
    $.ajax({
        url: '/pos/daily',
        type: "GET",
        contentType: false,
        processData: false,
        success: function(res) {
            swal({
                title: 'DONE',
                text: 'Print Complete',
                type: "success",
                timer: 1500,
                showCancelButton: false,
                showConfirmButton: false
            }, function(data) {
                location.reload(true);
            });
        },
        error: function(err) {
            console.log(err);
        }
    });
});

function handleRemoveZeroDecimal(value) {
    let intValue = parseInt(value)
    return intValue == parseFloat(value).toFixed(2) ? `$${intValue}` : `$${parseFloat(value).toFixed(2)}`;
}

function handleExchangeToRielCurrency(value) {
    let exchange_rate = $('#exchange_rate').val()
    return `${addComma(parseFloat(value).toFixed(2) * exchange_rate)}`;
}

function handleRemoveCommafromRielCurrency(value) {
    return parseInt(value.replace(/,/g, ''));
}

function addComma(num) {
    if (num === null) return;
    return num.toString().split("").reverse().map((digit, index) => index != 0 && index % 3 === 0 ? `${digit},` : digit).reverse().join("");
}

function handleProductDiscount(e) {
    let price = parseFloat($(e).parents('.product-card').find('.product-price').val()),
        quantity = parseInt($(e).parents('.product-card').find('.quantity').val()),
        initDiscountPrice = parseFloat($(e).parents('.product-card').find('.product-price').val()),
        totalPrice = parseFloat((price) * quantity).toFixed(2),
        discount = ($(e).parents('.product-card').find('.discount').val()) === '' ? 0 : parseInt($(e).parents('.product-card').find('.discount').val());

        


    if (!discount) {
        let card = $(e).parents('.product-card');
        $(e).parents('.product-card').find('.product-discount-price').val(price);
        $(e).parents('.product-card').find('.discount-in-usd').prop('disabled', false);
        editQuantity(e);
        return;
    }else{
        $(e).parents('.product-card').find('.discount-in-usd').prop('disabled', true);
    }

    if (discount > 100) {
        return;
    }

    // discontPrice = (initDiscountPrice - discount).toFixed(2);
    discountPrice = (totalPrice - ((totalPrice * discount) / 100)).toFixed(2);
    // totalDiscountPrice = (totalPrice - discountPrice).toFixed(2);
  
    $(e).parents('.product-card').find('.subtotal').text(`$ ${discountPrice}`);
    $(e).parents('.product-card').find('.product-discount-price').val(discountPrice);
    totalCash();

}

function handleProductDiscountInUSD(e) {

    console.log(`handleProductDiscountInUSD`);
    let price = parseFloat($(e).parents('.product-card').find('.product-price').val()),
        quantity = parseInt($(e).parents('.product-card').find('.quantity').val()),
        initDiscountPrice = parseFloat($(e).parents('.product-card').find('.product-price').val()),
        totalPrice = parseFloat((price) * quantity).toFixed(2),
        discountInUSD = ($(e).parents('.product-card').find('.discount-in-usd').val()) === '' ? 0 : parseInt($(e).parents('.product-card').find('.discount-in-usd').val());


    if (!discountInUSD) {
        let card = $(e).parents('.product-card');
        $(e).parents('.product-card').find('.product-discount-price').val(price);
        editQuantity(e);
        $(e).parents('.product-card').find('.discount').prop('disabled', false);
        return;
    }else{
        $(e).parents('.product-card').find('.discount').prop('disabled', true);
    }

    discountPrice = (totalPrice - discountInUSD).toFixed(2);
    $(e).parents('.product-card').find('.subtotal').text(`$ ${discountPrice}`);
    $(e).parents('.product-card').find('.product-discount-price').val(discountPrice);
    totalCash();
}   

function handleProductOverallDiscount(e) {
    let parentDiv = $(e).parents('.cashier-section');
    total = $('#total-usd').attr('') === '' ? 0 : $('#total-usd').attr('total-usd-data'),
        totalDiscount = $('.overall-discount').val() === '' ? 0 : $('.overall-discount').val();

    if (!total) {
        return;
    }

    if (!totalDiscount) {
        totalCash();
        return;
    }

    if (totalDiscount > 100) {
        return;
    }
    let totalDiscountPrice = (total - (total * (totalDiscount / 100))).toFixed(2);
    totalRielDiscountPrice = handleExchangeToRielCurrency(totalDiscountPrice);


    console.log(totalDiscountPrice);
    $('#total-usd').text(`$ ${totalDiscountPrice}`)
    $('#total-riel').text(`៛ ${totalRielDiscountPrice}`)
}

function minusQuantity(e) {
    let card = $(e).parents('.product-card');
    let quantity = parseInt($(card).find('.quantity').val());
    let result = quantity == 1 ? 1 : quantity - 1;
    $(card).find('.quantity').val(result);
    editQuantity(e);
}

function addQuantity(e) {
    let card = $(e).parents('.product-card');
    let quantity = parseInt($(card).find('.quantity').val());
    let result = quantity + 1;
    $(card).find('.quantity').val(result);
    editQuantity(e);
}

function addExtra(name) {
    if ($('#productList').children().length == 0) return;
    let card = $('#productList').children().last();
    $(card).find('.extra').append(`${name}, `);
    let price = parseFloat($(card).find('.extra-price').val()) + 1.00;
    $(card).find('.extra-price').val(price);
    editQuantity(card);
}

function editQuantity(e) {

    let price = parseFloat($(e).parents('.product-card').find('.product-price').val());
    let quantity = parseInt($(e).parents('.product-card').find('.quantity').val());
    let discount = ($(e).parents('.product-card').find('.discount').val()) === '' ? 0 : parseInt($(e).parents('.product-card').find('.discount').val());
    let discountInUSD = ($(e).parents('.product-card').find('.discount-in-usd').val()) === '' ? 0 : parseInt($(e).parents('.product-card').find('.discount-in-usd').val());
    

    if (!discount && !discountInUSD) {

        $(e).parents('.product-card').find('.subtotal').text(`$ ${parseFloat(price * quantity).toFixed(2)}`);
        $(e).parents('.product-card').find('.subtotal').attr('subtotal-data', `${parseFloat(price * quantity).toFixed(2)}`);

    } else if (!discountInUSD) {
        $(e).parents('.product-card').find('.subtotal').text(`$ ${parseFloat((price * quantity) - (((price * quantity) * discount) / 100)).toFixed(2)}`);
        $(e).parents('.product-card').find('.subtotal').attr('subtotal-data', `${parseFloat((price * quantity) - (((price * quantity) * discount) / 100)).toFixed(2)}`);
    }
    else {
        $(e).parents('.product-card').find('.subtotal').text(`$ ${parseFloat((price * quantity) - discountInUSD).toFixed(2)}`);
        $(e).parents('.product-card').find('.subtotal').attr('subtotal-data', `${parseFloat((price * quantity) - discountInUSD).toFixed(2)}`);
    }

    

    totalItem();
    totalCash();

}

function removeProduct(e) {
    let self = $(e);
    $(self).remove();
    let html = $(e).parents('.product-card');
}

$(".categories").on("click", function() {

    //   console.log("HELLOI");
    //Retrieve the input field text
    var filter = $(this).attr('id');
    $(this).parent().children().removeClass('selectedGat');

    $(this).addClass('selectedGat');
    // Loop through the list
    $("#productList2 #category").each(function() {
        // If the list item does not contain the text phrase fade it out
        if ($(this).val().search(new RegExp(filter, "i")) < 0) {
            $(this).parent().parent().parent().hide();
            // Show the list item if the phrase matches
        } else {
            $(this).parent().parent().parent().show();
        }
    });
});

$('.size').on('click', function(e) {

    if ($('#productList').children().length == 0) return;
    let size = $(e.target).attr('data-id');
    let card = $('#productList').children().last();
    let categoriesID = $(card).find('.category-id').val();
    $(card).find('.size').text(size);
    let price = productPrice(size, categoriesID);
    $(card).find('.product-price').val(price);
    editQuantity(card);

});

$('#Order').on("click",(e)=>{
    e.preventDefault();
    let totalUSD = $('#total-usd').attr('total-usd-data');
    
    if(parseInt(totalUSD) == 0 || totalUSD == ''){
        return;
    }
    let totalRiel = $('#total-riel').attr('total-riel-data');

    $.ajax({
        url: '/pos/get-invoice-no',
        type: "GET",
        contentType: false,
        processData: false,
        success: function(res) {
            const str = '000000';
            let invoiceNo = res.data === 0 ? ('0' + (+str + 1)).padStart(str.length, '0') : ('0' + (+str + res.data)).padStart(str.length, '0');

            $('#invoice-no').val(invoiceNo);
            $('#total-in-usd-modal').val(`${totalUSD} $`);
            $('#total-in-riel-modal').val(`${totalRiel} ៛`);
            $('#OrderModal').modal('show');

        },
        error: function(err) {
            console.log(err);
        }
    });

    
});
  

</script>


<!-- Add Modal -->
<div class="modal fade" id="OrderModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="posOrder" action="" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">PAYMENT</h4>
      </div>
      <div class="modal-body modal-body-pos">
           <div class="form-group form-group-flex">
             <label for="invoice-no">Invoice No</label>
             <input type="text" name="invoice-no" maxlength="100" class="form-control" id="invoice-no" disabled>
           </div>
           <div class="form-group form-group-flex">
             <label for="total-in-usd-modal">Total In USD</label>
             <input type="text" name="total-in-usd-modal" maxlength="100" class="form-control" id="total-in-usd-modal" disabled>
           </div>
           <div class="form-group form-group-flex">
             <label for="total-in-riel-moda">Total In KHR</label>
             <input type="text" name="total-in-riel-modal" maxlength="100" class="form-control" id="total-in-riel-modal" disabled>
           </div>
           <div class="form-group form-group-flex">
             <label for="payment-type">Payment Type</label>
             <select class="form-control" id="payment-type" name="filtertype">
                 <option value="cash" selected>Cash</option>
                 <option value="aba">ABA</option>
                 <option value="acleda">Acleda</option>
                 <option value="delivery">Delivery</option>
             </select>
           </div>
      </div>

      <div class="modal-body modal-body-pos payment-type-cash">
           <div class="form-group form-group-flex">
             <label for="received-cash-in-usd">Received Cash In USD</label>
             <input type="number" name="received-cash-in-usd"  class="form-control" id="received-cash-in-usd">
           </div>
           <div class="form-group form-group-flex">
             <label for="received-cash-in-riel">Received Cash In Riel</label>
             <input type="number" name="received-cash-in-riel" class="form-control" id="received-cash-in-riel">
           </div>
           <div class="form-group form-group-flex">
             <label for="change-in-usd">Change In USD</label>
             <input type="text" name="change-in-usd" maxlength="100" class="form-control" id="change-in-usd" disabled>
           </div>
           <div class="form-group form-group-flex">
             <label for="change-in-riel">Change In Riel</label>
             <input type="text" name="change-in-riel" maxlength="100" class="form-control" id="change-in-riel" disabled>
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

