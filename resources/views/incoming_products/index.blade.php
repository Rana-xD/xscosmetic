@extends('layouts.application')

@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
<style>
    .incoming-action-group {
        display: inline-flex;
        gap: 8px;
        align-items: center;
    }

    .incoming-action-btn {
        min-width: 135px;
        font-weight: 600;
        padding: 7px 12px;
    }
</style>
@endsection

@section('content')
<div class="container" style="margin-top: 100px;">
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

    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-8">
            <div class="input-group">
                <input type="text"
                       id="incomingBarcode"
                       class="form-control input-lg"
                       placeholder="{{ __('messages.search_product_barcode') }}"
                       autocomplete="off">
                <span class="input-group-btn">
                    <button type="button" id="scanConfirmBtn" class="btn btn-primary btn-lg">
                        <i class="fa fa-barcode"></i> {{ __('messages.confirm_arrival') }}
                    </button>
                </span>
            </div>
            <small style="display: block; margin-top: 6px; color: #777;">{{ __('messages.scan_and_confirm') }}</small>
        </div>
        <div class="col-md-4 text-right">
            @if($canManageIncoming)
            <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#AddIncomingItem">
                <i class="fa fa-plus"></i> {{ __('messages.add_incoming_item') }}
            </button>
            @endif
        </div>
    </div>

    <div class="row">
        <table id="IncomingPendingTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>{{ __('messages.input_date') }}</th>
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.barcode') }}</th>
                    <th>{{ __('messages.quantity') }}</th>
                    <th>{{ __('messages.cost') }}</th>
                    <th>{{ __('messages.price') }}</th>
                    <th>{{ __('messages.product_type') }}</th>
                    <th>{{ __('messages.expire_date') }}</th>
                    <th>{{ __('messages.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

@if($canManageIncoming)
<div class="modal fade" id="AddIncomingItem" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="addIncomingItemForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">{{ __('messages.add_incoming_item') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="incoming-name">{{ __('messages.name') }}</label>
                        <input type="text" id="incoming-name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="incoming-barcode">{{ __('messages.barcode') }}</label>
                        <input type="text" id="incoming-barcode" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="incoming-qty">{{ __('messages.quantity') }}</label>
                                <input type="number" id="incoming-qty" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="incoming-cost">{{ __('messages.cost') }}</label>
                                <input type="number" id="incoming-cost" class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="incoming-price">{{ __('messages.price') }}</label>
                                <input type="number" id="incoming-price" class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="incoming-category">{{ __('messages.product_type') }}</label>
                                <select id="incoming-category" class="form-control">
                                    <option value="">-</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="incoming-expire">{{ __('messages.expire_date') }}</label>
                                <div class="input-group date incoming-expire-picker" data-date-format="yyyy-mm-dd">
                                    <input type="text" id="incoming-expire" class="form-control incoming-expire-datepicker" placeholder="YYYY-MM-DD" autocomplete="off">
                                    <div class="input-group-addon">
                                        <span class="glyphicon glyphicon-th"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
                    <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
$(document).ready(function () {
    let lastScanTime = 0;
    const scanDebounceMs = 500;
    const canManageIncoming = @json($canManageIncoming);

    const pendingTable = $('#IncomingPendingTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: {
            url: '{{ route("incoming-products.data") }}',
            type: 'GET'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columns: [
            { data: 'created_at_display' },
            { data: 'name' },
            { data: 'barcode' },
            { data: 'qty' },
            { data: 'cost_display' },
            { data: 'price_display' },
            { data: 'category_name' },
            { data: 'expire_date' },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data) {
                    let buttons = '<div class="incoming-action-group">';
                    buttons += '<button type="button" class="btn btn-success btn-sm incoming-action-btn confirm-arrival-btn" data-id="' + data.id + '">' +
                        '<i class="fa fa-check"></i> {{ __("messages.confirm_arrival") }}</button>';

                    if (canManageIncoming) {
                        buttons += '<button type="button" class="btn btn-danger btn-sm incoming-action-btn delete-incoming-btn" data-id="' + data.id + '">' +
                            '<i class="fa fa-trash"></i> {{ __("messages.delete") }}</button>';
                    }

                    buttons += '</div>';
                    return buttons;
                }
            }
        ]
    });

    if ($.fn.datepicker) {
        $('.incoming-expire-picker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }

    function showSuccess(message) {
        swal({
            title: '{{ __("messages.success") }}',
            type: 'success',
            text: message,
            timer: 1800,
            showConfirmButton: false
        });
    }

    function showError(message) {
        swal({
            title: '{{ __("messages.error") }}',
            type: 'error',
            text: message
        });
    }

    function confirmByItemId(itemId) {
        $.ajax({
            url: '/incoming-products/' + itemId + '/confirm',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    pendingTable.ajax.reload(null, false);
                } else {
                    showError(response.message || 'Failed to confirm item.');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to confirm item.';
                showError(message);
            }
        });
    }

    function confirmByBarcode(barcodeValue) {
        $.ajax({
            url: '{{ route("incoming-products.confirm-by-barcode") }}',
            type: 'POST',
            data: {
                barcode: barcodeValue,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    pendingTable.ajax.reload(null, false);
                } else {
                    showError(response.message || 'No pending item found.');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'No pending item found.';
                showError(message);
            },
            complete: function () {
                $('#incomingBarcode').val('').focus();
            }
        });
    }

    function deleteIncomingItem(itemId) {
        $.ajax({
            url: '/incoming-products/' + itemId + '/delete',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    pendingTable.ajax.reload(null, false);
                } else {
                    showError(response.message || 'Failed to delete item.');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete item.';
                showError(message);
            }
        });
    }

    $('#scanConfirmBtn').on('click', function () {
        const barcodeValue = $.trim($('#incomingBarcode').val());
        if (!barcodeValue) {
            showError('Please scan or enter a barcode.');
            return;
        }

        const now = Date.now();
        if (now - lastScanTime < scanDebounceMs) {
            $('#incomingBarcode').val('').focus();
            return;
        }
        lastScanTime = now;
        confirmByBarcode(barcodeValue);
    });

    $('#incomingBarcode').on('keypress', function (event) {
        if (event.which === 13) {
            event.preventDefault();
            $('#scanConfirmBtn').click();
        }
    });

    $('#IncomingPendingTable tbody').on('click', '.confirm-arrival-btn', function () {
        const itemId = $(this).data('id');
        if (itemId) {
            confirmByItemId(itemId);
        }
    });

    $('#IncomingPendingTable tbody').on('click', '.delete-incoming-btn', function () {
        const itemId = $(this).data('id');
        if (!itemId) {
            return;
        }

        swal({
                title: '{{ __("messages.are_you_sure") }}',
                text: '{{ __("messages.delete_confirm") }}',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DD6B55',
                confirmButtonText: '{{ __("messages.yes") }}',
                closeOnConfirm: true
            },
            function (isConfirm) {
                if (!isConfirm) {
                    return;
                }
                deleteIncomingItem(itemId);
            });
    });

    @if($canManageIncoming)
    function resetAddIncomingForm() {
        $('#incoming-name').val('');
        $('#incoming-barcode').val('');
        $('#incoming-qty').val('');
        $('#incoming-cost').val('');
        $('#incoming-price').val('');
        $('#incoming-category').val('');
        $('#incoming-expire').val('');
    }

    $('#addIncomingItemForm').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: '{{ route("incoming-products.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                name: $('#incoming-name').val(),
                barcode: $('#incoming-barcode').val(),
                qty: $('#incoming-qty').val(),
                cost: $('#incoming-cost').val(),
                price: $('#incoming-price').val(),
                category_id: $('#incoming-category').val(),
                expire_date: $('#incoming-expire').val()
            },
            success: function (response) {
                if (response.success) {
                    $('#AddIncomingItem').modal('hide');
                    resetAddIncomingForm();
                    showSuccess(response.message);
                    pendingTable.ajax.reload(null, false);
                } else {
                    showError(response.message || 'Failed to create incoming item.');
                }
            },
            error: function (xhr) {
                let message = 'Failed to create incoming item.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    if (firstKey && xhr.responseJSON.errors[firstKey].length > 0) {
                        message = xhr.responseJSON.errors[firstKey][0];
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showError(message);
            }
        });
    });
    @endif
});
</script>
@endsection
