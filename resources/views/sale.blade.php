  @extends('layouts/application')
@section('content')
<style>
  .table .thead-dark th {
    color: #fff;
    background-color: #059DC0;
    border-color: #059DC0;
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    white-space: nowrap;
  }

  .table td {
    vertical-align: middle;
    text-align: center;
    padding: 12px 8px;
  }

  .cashier-name {
    font-weight: bold;
    font-size: 18px;
    color: black;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .calendar {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .container-fluid {
    margin-bottom: -50px;
  }

  .label-text {
    margin: 0;
    font-size: 16px;
    font-weight: bold;
    min-width: max-content;
    display: flex;
    align-items: center;
  }

  .date {
    width: 70%;
  }

  .table-payment-type-income,
  .table-total-item {
    table-layout: fixed;
  }

  .table-total-item {
    margin-bottom: 100px;
  }

  .table-payment-type-income td,
  .table-payment-type-income th,
  .table-total-item td,
  .table-total-item th {
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
  }

  .table-payment-type-income {
    margin-bottom: 50px;
  }

  .table .light-pink th {
    color: #fff;
    background-color: #f5c6cb;
    border-color: #dee2e6;
    font-size: 16px;
    white-space: nowrap;
  }

  .table .light-yellow th {
    color: #fff;
    background-color: #b8daff;
    border-color: #dee2e6;
    font-size: 16px;
    white-space: nowrap;
  }

  .income-info {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 30px 0;
  }

  .date-text {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 20px;
    text-align: center;
  }

  .total-income {
    font-size: 26px;
    font-weight: bold;
    color: #1fe01f;
    margin-bottom: 30px;
    text-align: center;
  }

  .search-wrapper {
    display: flex;
    align-items: center;
    height: 100%;
  }

  .btn-add {
    padding: 8px 20px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
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
  <h3>{{ __('messages.daily_income') }}</h3>
  <br />
  <br />

  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4" style="padding-left: 0">
        <div class="calendar">
          <p class="label-text">{{ __('messages.date') }}:</p>
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
        <div class="search-wrapper">
          <button type="button" class="btn btn-add" id="handleCustomIncomeSearch">{{ __('messages.search') }}</button>
        </div>
      </div>
    </div>
  </div>
  <div>
    <div class="income-info">
      <p class="date-text">{{ empty($date) ? Carbon\Carbon::now()->format('Y-m-d') : $date }}</p>
      <p class="total-income">{{$data["total"] === 0 ? '' : "{$data["total"]}$"}}</p>
    </div>
    <table class="table table-payment-type-income">
      <thead class="light-pink">
        <tr>
          <th scope="col">{{ __('messages.cash') }}</th>
          <th scope="col">{{ __('messages.change_money') }}</th>
          <th scope="col">{{ __('messages.expense') }}</th>
          <th scope="col">ABA</th>
          <th scope="col">ACLEDA</th>
          <th scope="col">{{ __('messages.delivery') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{$data["payment_type_income"]["cash"]}}$</td>
          <td>{{$data["payment_type_income"]["change"]}}$</td>
          <td>{{$data["payment_type_income"]["expense"]}}$</td>
          <td>{{$data["payment_type_income"]["aba"]}}$</td>
          <td>{{$data["payment_type_income"]["acleda"]}}$</td>
          <td>{{$data["payment_type_income"]["delivery"]}}$</td>
        </tr>
      </tbody>
    </table>

    <table class="table table-striped table-total-item">
      <thead class="light-yellow">
        <tr>
          <th scope="col">{{ __('messages.sale_table_number') }}</th>
          <th scope="col">{{ __('messages.sale_table_name') }}</th>
          <th scope="col">{{ __('messages.sale_table_quantity') }}</th>
          <th scope="col">{{ __('messages.sale_table_total') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($data['items'] as $index => $item)
        <tr>
          <th scope="row">{{ ($index + 1) }}</th>
          <td>{{$item["name"]}}</td>
          <td>{{$item["quantity"]}}</td>
          <td>{{$item["total"]}}$</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function() {
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
      let filter = `/income-report/filter?date=${date}`;

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

    $('#handleCustomIncomeSearch').on('click', (e) => {
      e.preventDefault();
      let date = $('.selected-date').val();

      if (date === '') {
        return;
      }
      let filter = `/income-report/filter?date=${date}`;

      window.location = filter;
    })
  });
</script>
@endsection