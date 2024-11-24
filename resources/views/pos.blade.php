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

    .delivery {
        display: none;
    }

    /* HTML: <div class="loader"></div> */
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

    /* HTML: <div class="loader"></div> */
    .order-loader {
        width: 45px;
        aspect-ratio: 1;
        display: grid;
        display: none;
        margin: auto;
    }

    .order-loader::before,
    .order-loader::after {
        content: "";
        grid-area: 1/1;
        --c: no-repeat radial-gradient(farthest-side, #25b09b 92%, #0000);
        background:
            var(--c) 50% 0,
            var(--c) 50% 100%,
            var(--c) 100% 50%,
            var(--c) 0 50%;
        background-size: 12px 12px;
        animation: l12 1s infinite;
    }

    .order-loader::before {
        margin: 4px;
        filter: hue-rotate(45deg);
        background-size: 8px 8px;
        animation-timing-function: linear
    }

    @keyframes l12 {
        100% {
            transform: rotate(.5turn)
        }
    }
</style>
@endsection
@section('content')
<!-- Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 left-side">
            <div class="col-xs-3 table-header">
                <h3>{{ __('messages.product') }}</h3>
            </div>
            <div class="col-xs-1 table-header">
                <h3 class="text-left">{{ __('messages.price') }}</h3>
            </div>
            <div class="col-xs-2 table-header nopadding">
                <h3 class="text-left">{{ __('messages.quantity') }}</h3>
            </div>
            <div class="col-xs-2 table-header">
                <h3>{{ __('messages.discount_percent') }}</h3>
            </div>
            <div class="col-xs-2 table-header">
                <h3>{{ __('messages.discount_usd') }}</h3>
            </div>
            <div class="col-xs-2 table-header nopadding">
                <h3>{{ __('messages.total') }}</h3>
            </div>
            <div id="productList">

            </div>
            <div class="footer-section">
                <div class="table-responsive col-sm-12 totalTab">
                    <table class="table cashier-section">
                        <tr>
                            <td class="active" width="40%">{{ __('messages.total_items') }}</td>
                            <td class="whiteBg" width="60%"><span id="Subtot"></span>
                                <span class="float-right"><b id="ItemsNum"><span></span> {{ __('messages.items') }}</b></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="active" width="40%">{{ __('messages.discount_all') }}</td>
                            <td class="whiteBg" width="60%"><span id="Subtot"></span>
                                <input type="text" class="form-control discount-input overall-discount" value="" placeholder="0" maxlength="3" onkeyup="handleProductOverallDiscount(this)">
                            </td>
                        </tr>
                        <tr>
                            <td class="active">{{ __('messages.total_in_usd') }}</td>
                            <td class="whiteBg light-blue text-bold"><span id="total-usd" total-usd-data="">$</td>
                        </tr>
                        <tr>
                            <td class="active">{{ __('messages.total_in_khr') }}</td>
                            <td class="whiteBg red text-bold"><span id="total-riel" total-riel-data="">៛</td>
                        </tr>
                    </table>
                </div>
                <button type="button" onclick="cancelPOS()" class="btn btn-red col-md-6 flat-box-btn">
                    <h5 class="text-bold">{{ __('messages.cancel') }}</h5>
                </button>
                <button type="button" class="btn btn-green col-md-6 flat-box-btn" data-toggle="modal" id="Order">
                    <h5 class="text-bold">{{ __('messages.order') }}</h5>
                </button>
                <div class="order-loader"></div>
            </div>


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
                        <input type="text" id="searchBrand" class="form-control" placeholder="{{ __('messages.search_brand') }}">
                        <span class="input-group-addon">
                            <button type="submit">
                                <span class="glyphicon glyphicon-search"></span>
                            </button>
                        </span>
                    </div>
                    <div class="input-group stylish-input-group">
                        <input type="text" id="searchProd" class="form-control" placeholder="{{ __('messages.search_product_name') }}">
                        <span class="input-group-addon">
                            <button type="submit">
                                <span class="glyphicon glyphicon-search"></span>
                            </button>
                        </span>
                    </div>
                    <div class="input-group stylish-input-group">
                        <input type="text" id="searchBarcode" class="form-control" placeholder="{{ __('messages.search_product_barcode') }}">
                        <span class="input-group-addon">
                            <button type="submit">
                                <span class="glyphicon glyphicon-search"></span>
                            </button>
                        </span>
                    </div>
                </div>
            </div> <!-- product list section -->
            <div id="productList2">
                @foreach ( $products as $product)
                <div class="col-xs-4 div">
                    <a href="javascript:void(0)" class="addPct" id="product-{{$product->product_code}}" data-id="{{ $product->id }}" style="display: block;">
                        <div class="product color06 flat-box" data-id="{{ $product->id }}">
                            <h3 id="proname" data-id="{{ $product->id }}">{{ $product->name }} <br><br> ({{ $product->stock }})</h3>
                            <input type="hidden" id="idname-{{ $product->id }}" name="name" value="{{$product->name}}" />
                            <input type="hidden" id="idprice-{{$product->id}}" name="price" value="{{$product->price}}" />
                            <input type="hidden" id="idcost-{{$product->id}}" name="cost" value="{{$product->cost}}" />
                            <input type="hidden" id="category" name="category" value="{{$product->category->id}}" />
                            <input type="hidden" id="barcode" name="barcode" value="{{$product->product_barcode}}" />
                            <input type="hidden" id="temp-barcode" name="temp-barcode" value="" />
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
    let addDelivery = 0;
    let timeoutId = null;

    $(document).ready(function() {

        e = $.Event('keypress');
        e.keyCode = 13; // enter
        $('#searchBrand').trigger(e);

        // Payment type selection handler
        $('#payment-type').on('change', function() {
            let selectedType = $(this).val();
            $('.payment-type-cash, .custom-split, .delivery').hide();
            if (selectedType === 'cash') {
                $('.payment-type-cash').show();
            } else if (selectedType === 'custom') {
                $('.custom-split').show();
            } else if (selectedType === 'delivery') {
                $('.delivery').show();
            }
        });

        // Initialize payment type section
        $('#payment-type').trigger('change');

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
            let card = $(element).parents('.div');
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

        };

        document.addEventListener('keypress', (e) => {
            //usually scanners throw an 'Enter' key at the end of read
            if (e.keyCode === 13) {
                if (code.length > 10) {
                    element = getProductElementByBarcode(code);
                    // console.log('code:: '+code);
                    addProduct(element);
                    code = "";
                }
            } else {
                code += e.key; //while this is not an 'enter' it stores the every key            
            }

            //run a timeout of 200ms at the first read and clear everything
            if (!reading) {
                reading = true;
                setTimeout(() => {
                    code = "";
                    reading = false;
                }, 400); //200 works fine for me but you can adjust it
            }
        });

        function getProductElement(id) {

            let cards = $('#productList').children();


            for (let i = 0; i < cards.length; i++) {
                if ($(cards[i]).find('.product-id').val() == id) {
                    return $(cards[i]).find('.product-name-content');
                }
            }
        }

        function getProductElementByBarcode(barcode) {
            let cards = $('#productList2').children();


            for (let i = 0; i < cards.length; i++) {
                if ($(cards[i]).find('#barcode').val() == barcode) {
                    return $(cards[i]).find('.addPct');
                }
            }
        }

        $("#searchProd").keyup(function() {
            // Retrieve the input field text
            var filter = $(this).val();
            // Loop through the list
            $("#productList2 #proname").each(function() {
                // If the list item does not contain the text phrase fade it out
                if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                    $(this).parent().parent().parent().hide();
                    // Show the list item if the phrase matches
                } else {
                    $(this).parent().parent().parent().show();
                }
            });

        });

        $("#searchBrand").keyup(function() {
            // Retrieve the input field text
            var filter = $(this).val();

            $(".categories-name").each(function() {
                if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                    $(this).hide();

                } else {
                    $(this).show();
                }
            })
        });

        $("#searchBarcode").keyup(function() {
            // Retrieve the input field text
            var filter = $(this).val();

            $("#productList2 #barcode").each(function() {
                // If the list item does not contain the text phrase fade it out
                if ($(this).val().search(new RegExp(filter, "i")) < 0) {
                    $(this).parent().parent().parent().hide();
                    // Show the list item if the phrase matches
                } else {
                    $(this).parent().parent().parent().show();
                }
            });
        });

        $('#payment-type').on('change', function() {
            let type = $(this).val();
            resetReceivedCashAndChange();
            if (type === 'aba' || type === 'acleda' || type === 'delivery') {
                $('.payment-type-cash').css('display', 'none');
                $('.custom-split').css('display', 'none');
            } else if (type === 'custom') {
                $('.custom-split').css('display', 'block');
                $('.delivery').css('display', 'none');
                // Reset the cash input and percentages
                $('#cash-in-usd').val('');
                $('#cash-percentage').val('0');
                $('#aba-percentage').val('0');
            } else {
                $('.custom-split').css('display', 'none');
                $('.delivery').css('display', 'none');
            }

            if (type === 'delivery') {
                $('.delivery').css('display', 'block');
                depositDeliveryFee();
            } else {
                $('.delivery').css('display', 'none');
                withdrawDeliveryFee();
            }
        });

        $('#cash-in-usd').on('input', function() {
            let cashAmount = parseFloat($(this).val()) || 0;
            let totalAmount = parseFloat($('#total-in-usd-final').val()) || 0;
            
            if (totalAmount > 0 && cashAmount >= 0) {
                // Ensure cash amount doesn't exceed total
                cashAmount = Math.min(cashAmount, totalAmount);
                
                // Calculate ABA amount directly
                let abaAmount = totalAmount - cashAmount;
                
                // Calculate percentages for display
                let cashPercentage = (cashAmount / totalAmount) * 100;
                let abaPercentage = (abaAmount / totalAmount) * 100;
                
                // Format with up to 6 decimal places, removing trailing zeros
                $('#cash-percentage').val(Number(cashPercentage.toFixed(6)).toString());
                $('#aba-percentage').val(Number(abaPercentage.toFixed(6)).toString());
                
                // Set ABA amount directly without percentage calculation
                $('#aba-amount-usd').val(abaAmount.toFixed(2));
                
                // Store the exact amounts for form submission
                $('#cash-in-usd').attr('data-exact-amount', cashAmount.toFixed(2));
                $('#aba-amount-usd').attr('data-exact-amount', abaAmount.toFixed(2));
            } else {
                $('#cash-percentage').val('0');
                $('#aba-percentage').val('0');
                $('#aba-amount-usd').val('0');
                $('#cash-in-usd').attr('data-exact-amount', '0');
                $('#aba-amount-usd').attr('data-exact-amount', '0');
            }
        });

        function handleCashPercentage(value) {
            let cashPercentage = parseInt(value) || 0;
            if (cashPercentage > 100) {
                $('#cash-percentage').val(100);
                cashPercentage = 100;
            }
            if (cashPercentage < 0) {
                $('#cash-percentage').val(0);
                cashPercentage = 0;
            }
            $('#aba-percentage').val(100 - cashPercentage);

            if (cashPercentage > 0) {
                $('.payment-type-cash').css('display', 'block');
            } else {
                $('.payment-type-cash').css('display', 'none');
            }
        }

        $('#cash-percentage').on('input', function() {
            handleCashPercentage($(this).val());
        });

        $('#received-cash-in-usd').on('input', function(e) {
            let value = $(this).val();
            let totalUSD = $('#total-usd').attr('total-usd-data');
            let paymentType = $('#payment-type').val();

            if (paymentType === 'custom') {
                let cashPercentage = parseInt($('#cash-percentage').val()) || 0;
                let cashAmount = (totalUSD * (cashPercentage / 100)).toFixed(2);
                let changeInUSD = value - cashAmount;
                let changeInRiel = handleExchangeToRielCurrency(changeInUSD);
                $('#change-in-riel').val(`${changeInRiel} ៛`);
                $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);
            } else {
                let changeInUSD = value - totalUSD;
                let changeInRiel = handleExchangeToRielCurrency(changeInUSD);
                $('#change-in-riel').val(`${changeInRiel} ៛`);
                $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);
            }

            if (value != '') {
                $("#received-cash-in-riel").prop('disabled', true);
            } else {
                $("#received-cash-in-riel").prop('disabled', false);
                $('#change-in-riel').val('');
                $('#change-in-usd').val('');
            }
        });

        $('#received-cash-in-riel').on('input', function(e) {
            let value = $(this).val();
            let totalUSD = $('#total-usd').attr('total-usd-data');
            let totalRiel = handleRemoveCommafromRielCurrency($('#total-riel').attr('total-riel-data'));
            let paymentType = $('#payment-type').val();

            if (paymentType === 'custom') {
                let cashPercentage = parseInt($('#cash-percentage').val()) || 0;
                let cashAmountRiel = (totalRiel * (cashPercentage / 100));
                let changeInRiel = addComma(value - cashAmountRiel);
                $('#change-in-riel').val(`${changeInRiel} ៛`);
            } else {
                let changeInRiel = addComma(value - totalRiel);
                $('#change-in-riel').val(`${changeInRiel} ៛`);
            }

            if (value != '') {
                $("#received-cash-in-usd").prop('disabled', true);
            } else {
                $("#received-cash-in-usd").prop('disabled', false);
                $('#change-in-riel').val('');
                $('#change-in-usd').val('');
            }
        });


        $('#posOrder').on('submit', (e) => {
            e.preventDefault();
            let data = [];
            let temp_data = [];
            let invoice = [];
            let cards = $('#productList').children();

            // Get the exact amounts for custom split
            let cashAmount = $('#cash-in-usd').attr('data-exact-amount') || '0';
            let abaAmount = $('#aba-amount-usd').attr('data-exact-amount') || '0';
            let cashPercentage = $('#cash-percentage').val() || '0';
            let abaPercentage = $('#aba-percentage').val() || '0';

            for (let i = 0; i < cards.length; i++) {
                totalProduct(cards[i]);

                let cost = $(cards[i]).find('.product-cost').val(),
                    quantity = parseInt($(cards[i]).find('.quantity').val()),
                    totalCost = (cost * quantity).toFixed(2),
                    totalPrice = parseFloat(($(cards[i]).find('.subtotal').text()).replace('$', '')).toFixed(2),
                    profit = parseFloat(totalPrice - totalCost).toFixed(2),
                    discount = 0;

                if ($(cards[i]).find('.discount').val()) {
                    discount = `${$(cards[i]).find('.discount').val()}%`;
                } else if ($(cards[i]).find('.discount-in-usd').val()) {
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
                if (i < 1) {
                    temp_data.push(item);
                }
            }

            for (let i = 0; i < cards.length; i++) {
                totalProduct(cards[i]);

                let price = handleRemoveZeroDecimal($(cards[i]).find('.product-price').val()),
                    quantity = parseInt($(cards[i]).find('.quantity').val()),
                    totalPrice = handleRemoveZeroDecimal(parseFloat(($(cards[i]).find('.subtotal').text()).replace('$', '')).toFixed(2)),
                    discount = 0;

                if ($(cards[i]).find('.discount').val()) {
                    discount = `${$(cards[i]).find('.discount').val()}%`;
                } else if ($(cards[i]).find('.discount-in-usd').val()) {
                    discount = `${$(cards[i]).find('.discount-in-usd').val()}$`;
                }

                item = {
                    product_name: $(cards[i]).find('.product-name').val(),
                    quantity: `${quantity}`,
                    price: price,
                    discount: discount,
                    total: totalPrice
                };
                invoice.push(item);
            }

            if (data.length == 0) return;

            let formData = {
                "data": data,
                "temp_data": temp_data,
                "invoice": invoice,
                "invoice_no": $('#invoice-no').val(),
                "total": $('#total-usd').attr('total-usd-data'),
                "total_riel": $('#total-riel').attr('total-riel-data'),
                "payment_type": $('#payment-type').val(),
                "totalDiscount": $('.overall-discount').val() === '' ? 0 : $('.overall-discount').val(),
                "receivedInUSD": $('#received-cash-in-usd').val() === '' ? 0 : $('#received-cash-in-usd').val(),
                "receivedInRiel": $('#received-cash-in-riel').val() === '' ? 0 : $('#received-cash-in-riel').val(),
                "changeInUSD": $('#change-in-usd').val() === '' ? 0 : $('#change-in-usd').val(),
                "changeInRiel": $('#change-in-riel').val() === '' ? 0 : $('#change-in-riel').val()
            };

            // Add percentage data for custom split payment
            if ($('#payment-type').val() === 'custom') {
                formData.cashPercentage = cashPercentage;
                formData.abaPercentage = abaPercentage;

                // Calculate the actual amounts for each payment method
                let totalAmount = parseFloat($('#total-usd').attr('total-usd-data').replace('$', ''));
                formData.cashAmount = (totalAmount * (cashPercentage / 100)).toFixed(2);
                formData.abaAmount = (totalAmount * ((100 - cashPercentage) / 100)).toFixed(2);
            }

            showSpinner();
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
                    hideSpinner();
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
        });
    });

    function depositDeliveryFee() {
        let totalInUSD = parseFloat($('#total-in-usd-final').val());

        if (totalInUSD < 50.00 && addDelivery == 0) {
            addDelivery = 1;
            totalInUSD += 1.50;
            let totalRiel = handleExchangeToRielCurrency(totalInUSD);
            $('#total-in-usd-modal').val(`${totalInUSD.toFixed(2)} $`);
            $('#total-in-usd-final').val(totalInUSD);
            $('#total-in-riel-modal').val(`${totalRiel} ៛`);
            $('#total-in-riel-final').val(handleRemoveCommafromRielCurrency(totalRiel));
        }
    }

    function withdrawDeliveryFee() {
        let totalInUSD = parseFloat($('#total-in-usd-final').val());

        if (addDelivery == 1) {
            addDelivery = 0;
            totalInUSD -= 1.50;
            let totalRiel = handleExchangeToRielCurrency(totalInUSD);
            $('#total-in-usd-modal').val(`${totalInUSD.toFixed(2)} $`);
            $('#total-in-usd-final').val(totalInUSD);
            $('#total-in-riel-modal').val(`${totalRiel} ៛`);
            $('#total-in-riel-final').val(handleRemoveCommafromRielCurrency(totalRiel));
        }

    }

    function resetReceivedCashAndChange() {
        $('#received-cash-in-usd').val('');
        $('#received-cash-in-riel').val('');
        $('#change-in-riel').val('');
        $('#change-in-usd').val('');
        $("#received-cash-in-usd").prop('disabled', false);
        $("#received-cash-in-riel").prop('disabled', false);
    }


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
        } else {
            $(e).parents('.product-card').find('.discount-in-usd').prop('disabled', true);
        }

        if (discount > 100) {
            return;
        }

        discountPrice = (totalPrice - ((totalPrice * discount) / 100)).toFixed(2);
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
        } else {
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
        let totalDiscountPrice = (total - ((total * totalDiscount) / 100)).toFixed(2);
        totalRielDiscountPrice = handleExchangeToRielCurrency(totalDiscountPrice);

        $('#total-usd').text(`$ ${totalDiscountPrice}`)
        $('#total-usd').attr('total-usd-data', totalDiscountPrice);
        $('#total-riel').text(`៛ ${totalRielDiscountPrice}`)
        $('#total-riel').attr('total-riel-data', totalRielDiscountPrice);
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
        } else {
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

    function showSpinner() {

        $('.loader').css('display', 'block');
        $('#posOrder .btn-add').prop('disabled', true);
    }

    function hideSpinner() {
        $('.loader').css('display', 'none');
        $('#posOrder .btn-add').prop('disabled', false);
    }

    function showOrderSpinner() {

        $('.order-loader').css('display', 'grid');
        $('#Order').css('display', 'none');
    }

    function hideOrderSpinner() {
        $('.order-loader').css('display', 'none');
        $('#Order').css('display', 'block');
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

    $('#Order').on("click", (e) => {
        e.preventDefault();
        let totalUSD = $('#total-usd').attr('total-usd-data');
        let totalDiscount = $('.overall-discount').val();

        if ((parseInt(totalUSD) == 0 || totalUSD == '') && totalDiscount != '100') {
            return;
        }
        let totalRiel = $('#total-riel').attr('total-riel-data');
        showOrderSpinner();
        $.ajax({
            url: '/pos/get-invoice-no',
            type: "GET",
            contentType: false,
            processData: false,
            success: function(res) {
                hideOrderSpinner();
                const str = '000000';
                let invoiceNo = '';

                if (res.data === 0) {
                    invoiceNo = ('0' + (+str + 1)).padStart(str.length, '0');
                } else {
                    invoiceNo = ('0' + (+str + (res.data + 1))).padStart(str.length, '0');
                }

                hideSpinner();
                addDelivery = 0;
                $('#payment-type').val('aba');
                $('.payment-type-cash').css('display', 'none');
                $('#invoice-no').val(invoiceNo);
                $('#total-in-usd-modal').val(`${totalUSD} $`);
                $('#total-in-usd-final').val(totalUSD);
                $('#total-in-riel-modal').val(`${totalRiel} ៛`);
                $('#total-in-riel-final').val(handleRemoveCommafromRielCurrency(totalRiel));
                $('#OrderModal').modal('show');

            },
            error: function(err) {
                console.log(err);
            }
        });


    });

    $('#cash-percentage').on('input', function() {
        let cashPercentage = parseInt($(this).val()) || 0;
        if (cashPercentage > 100) {
            $(this).val(100);
            cashPercentage = 100;
        }
        if (cashPercentage < 0) {
            $(this).val(0);
            cashPercentage = 0;
        }
        $('#aba-percentage').val(100 - cashPercentage);

        if (cashPercentage > 0) {
            $('.payment-type-cash').css('display', 'block');
        } else {
            $('.payment-type-cash').css('display', 'none');
        }
    });

    $('#payment-type').on('change', function() {
        let selectedType = $(this).val();
        
        if (selectedType === 'custom') {
            $('.custom-split').show();
            $('.delivery').hide();
            // Reset the cash input and percentages
            $('#cash-in-usd').val('');
            $('#cash-percentage').val('0');
            $('#aba-percentage').val('0');
            $('#aba-amount-usd').val('0');
        } else if (selectedType === 'delivery') {
            $('.delivery').show();
            $('.custom-split').hide();
        } else {
            $('.custom-split').hide();
            $('.delivery').hide();
        }
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
                    <h4 class="modal-title" id="myModalLabel">{{ __('messages.payment') }}</h4>
                </div>
                <div class="modal-body modal-body-pos">
                    <div class="form-group form-group-flex">
                        <label for="invoice-no">{{ __('messages.invoice_no') }}</label>
                        <input type="text" name="invoice-no" maxlength="100" class="form-control" id="invoice-no" disabled>
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="total-in-usd-modal">{{ __('messages.total_in_usd') }}</label>
                        <input type="text" name="total-in-usd-modal" maxlength="100" class="form-control" id="total-in-usd-modal" disabled>
                        <input type="hidden" name="total-in-usd-final" maxlength="100" class="form-control" id="total-in-usd-final">
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="total-in-riel-moda">{{ __('messages.total_in_khr') }}</label>
                        <input type="text" name="total-in-riel-modal" maxlength="100" class="form-control" id="total-in-riel-modal" disabled>
                        <input type="hidden" name="total-in-riel-final" maxlength="100" class="form-control" id="total-in-riel-final">
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="payment-type">{{ __('messages.payment_type') }}</label>
                        <select class="form-control" id="payment-type" name="filtertype">
                            <option value="aba" selected>{{ __('messages.aba') }}</option>
                            <option value="cash">{{ __('messages.cash') }}</option>
                            <option value="acleda">{{ __('messages.acleda') }}</option>
                            <option value="custom">{{ __('messages.custom_split') }}</option>
                            <option value="delivery">{{ __('messages.delivery') }}</option>
                        </select>
                    </div>
                    <div class="delivery">
                        <div class="form-group form-group-flex">
                            <label for="delivery-type">{{ __('messages.delivery_type') }}</label>
                            <select class="form-control" id="delivery-type" name="filtertype">
                                @foreach (App\Delivery::orderBy('name', 'ASC')->get() as $delivery)
                                <option value="{{ $delivery->name }}">{{ $delivery->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-body modal-body-pos custom-split" style="display: none;">
                    <div class="form-group form-group-flex">
                        <label for="cash-in-usd">{{ __('messages.cash_amount_usd') }}</label>
                        <input type="number" name="cash-in-usd" class="form-control" id="cash-in-usd" min="0" step="0.01">
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="aba-amount-usd">{{ __('messages.aba_amount_usd') }}</label>
                        <input type="number" name="aba-amount-usd" class="form-control" id="aba-amount-usd" readonly>
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="cash-percentage">{{ __('messages.cash_percentage') }}</label>
                        <input type="text" name="cash-percentage" class="form-control" id="cash-percentage" readonly>
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="aba-percentage">{{ __('messages.aba_percentage') }}</label>
                        <input type="text" name="aba-percentage" class="form-control" id="aba-percentage" readonly>
                    </div>
                </div>
                <div class="modal-body modal-body-pos payment-type-cash">
                    <div class="form-group form-group-flex">
                        <label for="received-cash-in-usd">{{ __('messages.received_cash_in_usd') }}</label>
                        <input type="number" name="received-cash-in-usd" class="form-control" id="received-cash-in-usd">
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="received-cash-in-riel">{{ __('messages.received_cash_in_riel') }}</label>
                        <input type="number" name="received-cash-in-riel" class="form-control" id="received-cash-in-riel">
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="change-in-usd">{{ __('messages.change_in_usd') }}</label>
                        <input type="text" name="change-in-usd" maxlength="100" class="form-control" id="change-in-usd" disabled>
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="change-in-riel">{{ __('messages.change_in_riel') }}</label>
                        <input type="text" name="change-in-riel" maxlength="100" class="form-control" id="change-in-riel" disabled>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default cancel-pos" data-dismiss="modal">{{ __('messages.close') }}</button>
                    <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
                    <div class="loader"></div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /.Modal -->
@endsection