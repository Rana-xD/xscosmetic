@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
@section('content')
<style>
  .table .thead-dark th {
    color: #fff;
    background-color: #059DC0;
    border-color: #059DC0;
  }

  .cashier-name {
    font-weight: bold;
    font-size: 18px;
    color: black;
  }

  .calendar {
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

  .date {
    width: 70%;
  }

  .table-payment-type-income,
  .table-product_log {
    table-layout: fixed;
  }

  .table-product_log {
    margin-bottom: 100px;
  }

  .table-payment-type-income td,
  .table-payment-type-income th,
  .table-product_log td,
  .table-product_log th {
    text-align: center;

  }

  .table-payment-type-income {
    margin-bottom: 50px;
  }

  .table .light-green th {
    color: #fff;
    background-color: #386641;
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

  .total-product_log {
    font-size: 26px;
    font-weight: bold;
    color: #1fe01f;
    margin-bottom: 30px;
  }

  .item-no {
    width: 10%;
  }

  .product_log-container {
    display: flex;
    justify-content: flex-end;
  }

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

  .date-nav {
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .date-nav:hover {
    background-color: #f0f0f0;
  }

  .input-group-addon {
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .input-group-addon:hover {
    background-color: #f0f0f0;
  }

  .input-group.date {
    width: 100%;
  }

  .input-group.date .form-control {
    padding: 6px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
  }

  .input-group.date .form-control:hover {
    background-color: #f0f0f0;
  }

  .input-group {
    display: flex;
    gap: 10px;
    margin-left: 10px;
  }
</style>
<div class="container">
  <h3>Product Daily Log</h3>
  <br />
  <br />

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4" style="padding-left: 0">
        <div class="calendar">
          <p class="label-text">Date:</p>
          <div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
            <div class="input-group-prepend">
              <button class="btn btn-outline-secondary date-nav" type="button" id="date-prev">
                <i class="fa fa-chevron-left"></i>
              </button>
            </div>
            <input type="text" class="form-control selected-date datepicker" value="{{ empty($date) ? '' : $date }}">
            <div class="input-group-append">
              <button class="btn btn-outline-secondary date-nav" type="button" id="date-next">
                <i class="fa fa-chevron-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <button type="button" class="btn btn-add" id="handleCustomProductLogSearch">Search</button>
      </div>
    </div>
  </div>
  <div>
    <div class="income-info">
      <p class="date-text">{{ empty($date) ? Carbon\Carbon::now()->format('Y-m-d') : $date }}</p>
      <p class="total-product_log">{{empty($total) ? '' : "{$total}$"}}</p>
    </div>

    <table class="table table-striped table-hover table-product_log">
      <thead class="light-green">
        <tr>
          <th scope="col" class="item-no">#</th>
          <th scope="col">Product Name</th>
          <th scope="col">Creator</th>
          <th scope="col">Time</th>
        </tr>
      </thead>
      <tbody>
        @if (!empty($product_log))
        @foreach ($product_log->items as $index => $item)
        <tr id="product_log-row-{{ $index }}" class="product_log-item">
          <th scope="row">{{ ($index + 1) }}</th>
          <td class="product_log-name">{{$item["name"]}}</td>
          <td class="product_log-cost">{{$item["creator"]}}</td>
          <td class="product_log-cost">{{$item["time"]}}</td>
        </tr>
        @endforeach
        @endif
      </tbody>
    </table>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {

    $('.product_log-btn').toggle($('.selected-date').val() === new Date().toISOString().split('T')[0]);

    // Get the datepicker input element
    const datepickerInput = document.getElementById('datepicker-input');

    // Get the date navigation buttons
    const datePrevButton = document.getElementById('date-prev');
    const dateNextButton = document.getElementById('date-next');
    const dateFormat = 'MM/DD/YYYY';
    $('.datepicker').datepicker({
      format: 'mm/dd/yyyy',
      startDate: 'today'
    });

    // Function to change the date based on the click event of the datepicker
    function changeDate(direction) {

      const currentDate = new Date($('.selected-date').val());
      const newDate = new Date(currentDate);

      if (direction === 'prev') {
        newDate.setDate(newDate.getDate() - 1);
      } else if (direction === 'next') {
        newDate.setDate(newDate.getDate() + 1);
      }

      const formattedDate = moment(newDate).format(dateFormat);
      const convertedDate = moment(formattedDate, 'MM/DD/YYYY').format('YYYY-MM-DD');
      $('.selected-date').val(convertedDate);


      // Change the URL when the date is changed
      const date = $('.selected-date').val();
      let filter = `/product-log/filter?date=${date}`;

      window.location = filter;
    }

    // Add event listeners to the date navigation buttons
    datePrevButton.addEventListener('click', () => {
      changeDate('prev');
    });

    dateNextButton.addEventListener('click', () => {
      changeDate('next');
    });

    $('.date-nav').click(function(event) {
      event.preventDefault();
    });


    $('#handleCustomProductLogSearch').on('click', (e) => {
      e.preventDefault();
      let date = $('.selected-date').val();

      if (date === '') {
        return;
      }
      let filter = `/product_log/filter?date=${date}`;

      window.location = filter;
    })
  });
</script>


</div>
</div>
@endsection