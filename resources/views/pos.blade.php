@extends('layouts/application')
@section('head')
<style>
    #searchContainer {
        margin-top: 20px;
    }

    #searchContainer .stylish-input-group {
        width: 100%;
    }

    /* Hide products by default, but allow JavaScript to show them */
    #productList2 .div {
        display: none;
    }

    .delivery {
        display: none;
    }

    #change-currency-options {
        margin-bottom: 15px;
    }

    .radio-options {
        display: flex;
        align-items: center;
    }
    
    .radio-inline {
        display: inline-block;
        margin-right: 10px;
    }
    
    .radio-option {
        display: block;
        padding: 8px 20px;
        border-radius: 4px;
        cursor: pointer;
        background-color: #f8f8f8;
        border: 1px solid #ddd;
        text-align: center;
        transition: all 0.2s;
        min-width: 100px;
    }
    
    .radio-option:hover {
        background-color: #f0f0f0;
    }
    
    .radio-inline input[type="radio"] {
        position: absolute;
        opacity: 0;
    }
    
    /* Fix input height consistency */
    .form-group-flex input[type="text"],
    .form-group-flex input[type="number"] {
        height: 34px;
        line-height: 1.42857143;
    }
    
    .radio-inline input[type="radio"]:checked + .radio-option {
        background-color: #25b09b;
        color: white;
        border-color: #25b09b;
        font-weight: bold;
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

    #change-currency-label {
        width: 20.5%;
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
        <div class="col-md-9 left-side">
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

        <div class="col-md-3 right-side nopadding">
            <div class="row row-horizon">
                <span class="categories selectedGat" id=""><i class="fa fa-home"></i> All</span>
            </div>
            <div class="col-sm-12">
                <div id="searchContainer">
                    <div class="input-group stylish-input-group">
                        <input type="text" id="unifiedSearch" class="form-control" placeholder="Search by Product Name or Barcode">
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
                <div class="col-xs-6 div">
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
    let barcodeMap = {}; // Cache for faster barcode lookup
    let lastScanTime = 0; // Prevent duplicate scans
    const SCAN_DEBOUNCE_MS = 500; // Minimum time between scans

    $(document).ready(function() {

        // Build barcode map for O(1) lookup instead of O(n)
        buildBarcodeMap();

        // Initialize with no products visible
        $('#productList2 .div').hide();

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

        // Optimized barcode scanner event listener
        document.addEventListener('keypress', (e) => {
            // Ignore if user is typing in an input field
            const activeElement = document.activeElement;
            const isInputField = activeElement && (
                activeElement.tagName === 'INPUT' || 
                activeElement.tagName === 'TEXTAREA' || 
                activeElement.tagName === 'SELECT' ||
                activeElement.isContentEditable
            );
            
            if (isInputField) {
                return; // Let the user type normally in input fields
            }

            // Use modern e.key instead of deprecated e.keyCode
            if (e.key === 'Enter') {
                // Clear any existing timeout
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = null;
                }
                
                if (code.length > 10) {
                    const currentTime = Date.now();
                    
                    // Debounce: prevent duplicate scans within SCAN_DEBOUNCE_MS
                    if (currentTime - lastScanTime < SCAN_DEBOUNCE_MS) {
                        console.log('Scan ignored - too soon after last scan');
                        code = "";
                        reading = false;
                        return;
                    }
                    
                    lastScanTime = currentTime;
                    
                    // Use optimized barcode lookup with cached map
                    const element = getProductElementByBarcodeOptimized(code);
                    
                    if (element) {
                        addProduct(element);
                    } else {
                        console.warn('Product not found for barcode:', code);
                        // Optional: Show user feedback for invalid barcode
                    }
                    
                    code = "";
                }
                reading = false;
            } else {
                // Accumulate barcode characters
                code += e.key;
                
                // Clear and reset timeout on each keypress
                if (timeoutId) {
                    clearTimeout(timeoutId);
                }
                
                // Set reading flag and timeout
                reading = true;
                timeoutId = setTimeout(() => {
                    // Reset if no Enter key received within timeout
                    code = "";
                    reading = false;
                    timeoutId = null;
                }, 100); // Reduced from 400ms to 100ms for faster response
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

        // Build barcode map for O(1) lookup performance
        function buildBarcodeMap() {
            barcodeMap = {}; // Reset the map
            let cards = $('#productList2').children();
            
            for (let i = 0; i < cards.length; i++) {
                const barcode = $(cards[i]).find('#barcode').val();
                if (barcode) {
                    barcodeMap[barcode] = $(cards[i]).find('.addPct');
                }
            }
            
            console.log(`Barcode map built with ${Object.keys(barcodeMap).length} products`);
        }

        // Optimized barcode lookup using cached map - O(1) instead of O(n)
        function getProductElementByBarcodeOptimized(barcode) {
            return barcodeMap[barcode] || null;
        }

        // Unified search for product name, brand (category), and barcode with debouncing
        let searchTimeout;
        $("#unifiedSearch").on('input', function() {
            var filter = $(this).val();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // If search is empty, hide all products immediately
            if (filter === '') {
                $('#productList2 .div').hide();
                return;
            }
            
            // Debounce search to reduce lag
            searchTimeout = setTimeout(function() {
                // Create regex once for better performance
                var regex = new RegExp(filter, "i");
                
                // Search through all products
                $("#productList2 .div").each(function() {
                    var $this = $(this);
                    var productName = $this.find('#proname').text();
                    var barcode = $this.find('#barcode').val() || '';
                    
                    // Check if filter matches product name or barcode
                    if (regex.test(productName) || regex.test(barcode)) {
                        $this.show();
                    } else {
                        $this.hide();
                    }
                });
            }, 50); // 50ms debounce delay for faster response
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
        
        // Add event listener for delivery type change
        $('#delivery-type').on('change', function() {
            // If delivery is already added, remove it first
            if (addDelivery == 1) {
                withdrawDeliveryFee();
            }
            // Then add the new delivery fee
            depositDeliveryFee();
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
            let value = parseFloat($(this).val()) || 0;
            let rielValue = parseInt($('#received-cash-in-riel').val()) || 0;
            let totalUSD = parseFloat($('#total-usd').attr('total-usd-data'));
            let paymentType = $('#payment-type').val();

            // Check if both USD and Riel inputs have values
            if (value > 0 && rielValue > 0) {
                // Show radio buttons for selecting change currency
                $('#change-currency-options').show();
                
                // Check if any radio button is already selected
                let selectedCurrency = $('input[name="change-currency"]:checked').val();
                if (selectedCurrency) {
                    // Calculate change based on selected currency option
                    calculateDualCurrencyChange();
                } else {
                    // Hide both change fields when no option is selected
                    $('.form-group-flex').has('#change-in-usd').hide();
                    $('.form-group-flex').has('#change-in-riel').hide();
                }
            } else {
                // Hide radio buttons when only one currency is used
                $('#change-currency-options').hide();
                // Show change fields
                $('.form-group-flex').has('#change-in-usd').show();
                $('.form-group-flex').has('#change-in-riel').show();
                
                // Recalculate change values
                if (value > 0) {
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
                } else {
                    $('#change-in-riel').val('');
                    $('#change-in-usd').val('');
                }
            }
        });

        $('#received-cash-in-riel').on('input', function(e) {
            // Ensure Riel value is always an integer
            let value = parseInt($(this).val()) || 0;
            let usdValue = parseFloat($('#received-cash-in-usd').val()) || 0;
            let totalUSD = parseFloat($('#total-usd').attr('total-usd-data'));
            let totalRiel = handleRemoveCommafromRielCurrency($('#total-riel').attr('total-riel-data'));
            let paymentType = $('#payment-type').val();

            // Check if both USD and Riel inputs have values
            if (value > 0 && usdValue > 0) {
                // Show radio buttons for selecting change currency
                $('#change-currency-options').show();
                
                // Check if any radio button is already selected
                let selectedCurrency = $('input[name="change-currency"]:checked').val();
                if (selectedCurrency) {
                    // Calculate change based on selected currency option
                    calculateDualCurrencyChange();
                } else {
                    // Hide both change fields when no option is selected
                    $('.form-group-flex').has('#change-in-usd').hide();
                    $('.form-group-flex').has('#change-in-riel').hide();
                }
            } else {
                // Hide radio buttons when only one currency is used
                $('#change-currency-options').hide();
                // Show change fields
                $('.form-group-flex').has('#change-in-usd').show();
                $('.form-group-flex').has('#change-in-riel').show();
                
                // Recalculate change values
                if (value > 0) {
                    if (paymentType === 'custom') {
                        let cashPercentage = parseInt($('#cash-percentage').val()) || 0;
                        let cashAmountRiel = (totalRiel * (cashPercentage / 100));
                        let changeInRiel = addComma(value - cashAmountRiel);
                        $('#change-in-riel').val(`${changeInRiel} ៛`);
                        // Also calculate USD equivalent for better user experience
                        let changeInUSD = (value - cashAmountRiel) / parseFloat($('#exchange_rate').val());
                        $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);
                    } else {
                        let changeInRiel = addComma(value - totalRiel);
                        $('#change-in-riel').val(`${changeInRiel} ៛`);
                        // Also calculate USD equivalent for better user experience
                        let changeInUSD = (value - totalRiel) / parseFloat($('#exchange_rate').val());
                        $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);
                    }
                } else if (usdValue > 0) {
                    // If there's a USD value but no Riel value, let the USD handler calculate
                    // This ensures we don't clear values when one field still has a value
                    $('#received-cash-in-usd').trigger('input');
                } else {
                    $('#change-in-riel').val('');
                    $('#change-in-usd').val('');
                }
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
                "invoice_no": $('#invoice-no').val(),
                "data": data,
                "temp_data": temp_data,
                "invoice": invoice,
                "payment_type": $('#payment-type').val(),
                "total": $('#total-usd').attr('total-usd-data'),
                "total_riel": $('#total-riel').attr('total-riel-data'),
                "totalDiscount": $('.overall-discount').val() === '' ? 0 : $('.overall-discount').val(),
                "receivedInUSD": $('#received-cash-in-usd').val() === '' ? 0 : $('#received-cash-in-usd').val(),
                "receivedInRiel": $('#received-cash-in-riel').val() === '' ? 0 : $('#received-cash-in-riel').val(),
                "changeInUSD": $('#change-in-usd').val() === '' ? 0 : $('#change-in-usd').val(),
                "changeInRiel": $('#change-in-riel').val() === '' ? 0 : $('#change-in-riel').val(),
                "delivery_type": $('#payment-type').val() === 'delivery' ? $('#delivery-type').val() : null,
                "additional_info": {
                    total: $('#total-usd').attr('total-usd-data'),
                    total_riel: $('#total-riel').attr('total-riel-data'),
                    total_discount: $('.overall-discount').val() === '' ? 0 : $('.overall-discount').val(),
                    received_in_usd: $('#received-cash-in-usd').val() === '' ? 0 : $('#received-cash-in-usd').val(),
                    received_in_riel: $('#received-cash-in-riel').val() === '' ? 0 : $('#received-cash-in-riel').val(),
                    change_in_usd: $('#change-in-usd').val() === '' ? 0 : $('#change-in-usd').val(),
                    change_in_riel: $('#change-in-riel').val() === '' ? 0 : $('#change-in-riel').val(),
                    selected_change_currency: $('input[name="change-currency"]:checked').val() || ''
                }
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
        let deliveryType = $('#delivery-type').val();
        let deliveryFee = 0;

        // If delivery type is 'later', fee is 0
        if (deliveryType !== 'later' && totalInUSD < 50.00 && addDelivery == 0) {
            // Get the delivery fee from the selected option's data attribute
            if (deliveryType !== 'later') {
                // Find the selected delivery option to get its cost
                let selectedOption = $('#delivery-type option:selected');
                let deliveryId = selectedOption.val();
                
                // Use AJAX to get the delivery cost
                $.ajax({
                    url: '/api/delivery/' + deliveryId,
                    type: 'GET',
                    async: false,
                    success: function(response) {
                        deliveryFee = parseFloat(response.cost);
                    },
                    error: function() {
                        // Default to 1.50 if there's an error
                        deliveryFee = 1.50;
                    }
                });
            }
            
            addDelivery = 1;
            totalInUSD += deliveryFee;
            let totalRiel = handleExchangeToRielCurrency(totalInUSD);
            $('#total-in-usd-modal').val(`${totalInUSD.toFixed(2)} $`);
            $('#total-in-usd-final').val(totalInUSD);
            $('#total-in-riel-modal').val(`${totalRiel} ៛`);
            $('#total-in-riel-final').val(handleRemoveCommafromRielCurrency(totalRiel));
            
            // Store the delivery fee for later withdrawal
            $('#delivery-fee').data('fee', deliveryFee);
        }
    }

    function withdrawDeliveryFee() {
        let totalInUSD = parseFloat($('#total-in-usd-final').val());

        if (addDelivery == 1) {
            addDelivery = 0;
            // Get the stored delivery fee or default to 1.50
            let deliveryFee = $('#delivery-fee').data('fee') || 1.50;
            totalInUSD -= deliveryFee;
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
        $('#change-currency-options').hide();
        // Uncheck all radio buttons
        $('input[name="change-currency"]').prop('checked', false);
        // Show both change fields for next time
        $('.form-group-flex').has('#change-in-usd').show();
        $('.form-group-flex').has('#change-in-riel').show();
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
        // Handle negative numbers by preserving the minus sign
        const isNegative = num < 0;
        const absNum = Math.abs(num);
        const formattedNum = absNum.toString().split("").reverse().map((digit, index) => index != 0 && index % 3 === 0 ? `${digit},` : digit).reverse().join("");
        return isNegative ? `-${formattedNum}` : formattedNum;
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
        let price = parseFloat($(e).parents('.product-card').find('.product-price').val());
        let quantity = parseInt($(e).parents('.product-card').find('.quantity').val());
        let initDiscountPrice = parseFloat($(e).parents('.product-card').find('.product-price').val());
        let totalPrice = parseFloat((price) * quantity).toFixed(2),
            discountInUSD = ($(e).parents('.product-card').find('.discount-in-usd').val()) === '' ? 0 : parseFloat($(e).parents('.product-card').find('.discount-in-usd').val());


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
        // Get the original total from data attribute
        let originalTotal = parseFloat($('#total-usd').attr('original-total-data'));
        if (!originalTotal || isNaN(originalTotal)) {
            // If original total is not set, get it from current total and store it
            originalTotal = parseFloat($('#total-usd').attr('total-usd-data'));
            if (!originalTotal || isNaN(originalTotal)) {
                return;
            }
            $('#total-usd').attr('original-total-data', originalTotal);
        }

        // Get discount value
        let input = $('.overall-discount').val();
        if (!input || input === '') {
            $('#total-usd').text(`$ ${originalTotal.toFixed(2)}`);
            $('#total-usd').attr('total-usd-data', originalTotal.toFixed(2));
            let totalRielPrice = handleExchangeToRielCurrency(originalTotal.toFixed(2));
            $('#total-riel').text(`៛ ${totalRielPrice}`);
            $('#total-riel').attr('total-riel-data', totalRielPrice);
            return;
        }

        let totalDiscount = parseFloat(input);
        if (isNaN(totalDiscount)) {
            return;
        }

        // Handle invalid discount values
        if (totalDiscount <= 0) {
            $('#total-usd').text(`$ ${originalTotal.toFixed(2)}`);
            $('#total-usd').attr('total-usd-data', originalTotal.toFixed(2));
            let totalRielPrice = handleExchangeToRielCurrency(originalTotal.toFixed(2));
            $('#total-riel').text(`៛ ${totalRielPrice}`);
            $('#total-riel').attr('total-riel-data', totalRielPrice);
            return;
        }

        if (totalDiscount > 100) {
            $('.overall-discount').val('100');
            totalDiscount = 100;
        }

        // Calculate discounted price using original total
        let discountAmount = (originalTotal * totalDiscount) / 100;
        let totalDiscountPrice = (originalTotal - discountAmount).toFixed(2);
        let totalRielDiscountPrice = handleExchangeToRielCurrency(totalDiscountPrice);

        // Update display
        $('#total-usd').text(`$ ${totalDiscountPrice}`);
        $('#total-usd').attr('total-usd-data', totalDiscountPrice);
        $('#total-riel').text(`៛ ${totalRielDiscountPrice}`);
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
        // Clear search and hide all products
        $('#unifiedSearch').val('');
        $('#productList2 .div').hide();
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
        
        // Recalculate change if both currency inputs have values
        let usdValue = parseFloat($('#received-cash-in-usd').val()) || 0;
        let rielValue = parseInt($('#received-cash-in-riel').val()) || 0;
        if (usdValue > 0 && rielValue > 0) {
            calculateDualCurrencyChange();
        }
    });
    
    // Add event listeners for radio buttons
    $(document).on('change', 'input[name="change-currency"]', function() {
        console.log('Radio changed:', $(this).val());
        calculateDualCurrencyChange();
    });
    
    // Initialize radio buttons on document ready
    $(document).ready(function() {
        // Make sure the radio options are properly initialized
        $(document).on('click', '.radio-option', function() {
            let radio = $(this).prev('input[type="radio"]');
            radio.prop('checked', true);
            radio.trigger('change');
        });
        
        // Add event handler for modal close to reset form state
        $('#OrderModal').on('hidden.bs.modal', function () {
            resetReceivedCashAndChange();
        });
        
        // Also reset when the close button is clicked
        $('.cancel-pos').on('click', function() {
            resetReceivedCashAndChange();
        });
    });
    // Function to calculate change when both USD and Riel inputs are provided
    function calculateDualCurrencyChange() {
        let usdValue = parseFloat($('#received-cash-in-usd').val()) || 0;
        let rielValue = parseInt($('#received-cash-in-riel').val()) || 0;
        let totalUSD = parseFloat($('#total-usd').attr('total-usd-data'));
        let totalRiel = handleRemoveCommafromRielCurrency($('#total-riel').attr('total-riel-data'));
        let exchangeRate = parseFloat($('#exchange_rate').val());
        let selectedCurrency = $('input[name="change-currency"]:checked').val();
        let paymentType = $('#payment-type').val();
        
        console.log('Calculate dual currency change, selected:', selectedCurrency);
        
        // Hide both change fields by default when both currency inputs have values
        $('.form-group-flex').has('#change-in-usd').hide();
        $('.form-group-flex').has('#change-in-riel').hide();
        
        // If no radio button is selected, return early
        if (!selectedCurrency) {
            console.log('No currency selected');
            return;
        }
        
        if (selectedCurrency === 'usd') {
            // Show only USD change field
            $('.form-group-flex').has('#change-in-usd').show();
            
            // First deduct received Riel from total, then calculate USD change
            let rielValueInUSD = rielValue / exchangeRate;
            let remainingUSD = totalUSD - rielValueInUSD;
            
            // If remaining is negative, it means Riel payment covers everything
            if (remainingUSD <= 0) {
                let overpaymentInUSD = Math.abs(remainingUSD);
                let changeInUSD = usdValue + overpaymentInUSD;
                $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);
            } else {
                // Calculate change from USD payment
                let changeInUSD = usdValue - remainingUSD;
                $('#change-in-usd').val(`${changeInUSD.toFixed(2)} $`);
            }
        } else { // selectedCurrency === 'riel'
            // Show only Riel change field
            $('.form-group-flex').has('#change-in-riel').show();
            
            // First deduct received USD from total, then calculate Riel change
            let remainingUSD = totalUSD - usdValue;
            
            // If remaining is negative, it means USD payment covers everything
            if (remainingUSD <= 0) {
                let overpaymentInUSD = Math.abs(remainingUSD);
                let overpaymentInRiel = overpaymentInUSD * exchangeRate;
                let changeInRiel = addComma(rielValue + overpaymentInRiel);
                $('#change-in-riel').val(`${changeInRiel} ៛`);
            } else {
                // Calculate remaining in Riel
                let remainingRiel = remainingUSD * exchangeRate;
                let changeInRiel = addComma(rielValue - remainingRiel);
                $('#change-in-riel').val(`${changeInRiel} ៛`);
            }
        }
    }
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
                    <div class="delivery" style="display: none;">
                        <div class="form-group form-group-flex">
                            <label for="delivery-type">{{ __('messages.delivery_type') }}</label>
                            <select class="form-control" id="delivery-type" name="delivery_type">
                                <option value="later" selected>{{ __('messages.later') }}</option>
                                @foreach (App\Delivery::orderBy('name', 'ASC')->get() as $delivery)
                                <option value="{{ $delivery->id }}">{{ $delivery->name }} ({{ $delivery->location }}) - ${{ number_format($delivery->cost, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Hidden field to store delivery fee -->
                        <input type="hidden" id="delivery-fee" data-fee="0">
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
                        <input type="number" name="received-cash-in-riel" class="form-control" id="received-cash-in-riel" step="1" min="0">
                    </div>
                    <div id="change-currency-options" class="form-group form-group-flex" style="display: none;">
                        <label id="change-currency-label">{{ __('messages.show_change_in') }}</label>
                        <div class="radio-options" aria-labelledby="change-currency-label">
                            <div class="radio-inline">
                                <input type="radio" name="change-currency" id="change-in-usd-option" value="usd">
                                <label class="radio-option" for="change-in-usd-option">USD</label>
                            </div>
                            <div class="radio-inline">
                                <input type="radio" name="change-currency" id="change-in-riel-option" value="riel">
                                <label class="radio-option" for="change-in-riel-option">Riel</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="change-in-usd">{{ __('messages.change_in_usd') }}</label>
                        <input type="text" name="change-in-usd" maxlength="100" class="form-control" id="change-in-usd" disabled>
                    </div>
                    <div class="form-group form-group-flex">
                        <label for="change-in-riel">{{ __('messages.change_in_riel') }}</label>
                        <input type="text" name="change-in-riel" maxlength="100" class="form-control" id="change-in-riel" disabled style="height: 34px;">
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