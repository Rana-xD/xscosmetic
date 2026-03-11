@extends('layouts.application')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="page-header" style="margin-top: 0; border-bottom: 2px solid #337ab7;">
        <h3 style="margin: 0; padding-bottom: 10px;">
            <i class="fa fa-history"></i> {{ __('messages.incoming_confirmed_items') }}
        </h3>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade in">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade in">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
    @endif

    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title"><i class="fa fa-check-circle"></i> {{ __('messages.incoming_history') }}</h4>
        </div>
        <div class="panel-body">
            <div class="table-responsive">
                <table id="IncomingHistoryTable" class="table table-striped table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>{{ __('messages.confirmed_at') }}</th>
                            <th>{{ __('messages.name') }}</th>
                            <th>{{ __('messages.barcode') }}</th>
                            <th>{{ __('messages.quantity') }}</th>
                            <th>{{ __('messages.confirmed_by') }}</th>
                            <th>{{ __('messages.linked_product') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#IncomingHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: {
            url: '{{ route("incoming-products.history.data") }}',
            type: 'GET'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columns: [
            { data: 'confirmed_at_display' },
            { data: 'name' },
            { data: 'barcode' },
            { data: 'qty' },
            { data: 'confirmed_by_name' },
            { data: 'product_name' }
        ]
    });
});
</script>
@endsection
