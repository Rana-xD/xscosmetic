@extends('layouts.application')

@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
<style>
    .incoming-layout {
        margin-top: 100px;
    }

    .incoming-batch-list .list-group-item {
        border-radius: 0;
        border-left: 4px solid transparent;
    }

    .incoming-batch-list .list-group-item.active,
    .incoming-batch-list .list-group-item.active:hover,
    .incoming-batch-list .list-group-item.active:focus {
        background: #f5f9ff;
        color: #2c3e50;
        border-color: #d9e6f5;
        border-left-color: #337ab7;
    }

    .incoming-batch-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .incoming-batch-meta {
        color: #667;
        font-size: 12px;
        line-height: 1.7;
    }

    .incoming-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 15px;
    }

    .incoming-summary-box {
        border: 1px solid #d9e2ef;
        border-radius: 4px;
        padding: 12px;
        background: #fafcfe;
    }

    .incoming-summary-label {
        display: block;
        font-size: 12px;
        color: #6b7785;
        margin-bottom: 4px;
    }

    .incoming-summary-value {
        font-size: 18px;
        font-weight: 600;
        color: #23374d;
    }

    .incoming-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin: 18px 0 15px;
    }

    .incoming-toolbar .input-group {
        flex: 1 1 380px;
    }

    .incoming-toolbar .btn {
        min-width: 150px;
    }

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

    .incoming-empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #7c8794;
        border: 1px dashed #ced9e6;
        border-radius: 4px;
        background: #fbfcfe;
    }

    .incoming-note {
        white-space: pre-line;
    }

    @media (max-width: 991px) {
        .incoming-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid incoming-layout">
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

    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-default incoming-batch-list">
                <div class="panel-heading clearfix">
                    <strong><i class="fa fa-truck"></i> {{ __('messages.open_batches') }}</strong>
                    @if($canManageIncoming)
                    <button type="button" class="btn btn-primary btn-xs pull-right" data-toggle="modal" data-target="#CreateBatchModal">
                        <i class="fa fa-plus"></i> {{ __('messages.create_batch') }}
                    </button>
                    @endif
                </div>
                <div class="panel-body" style="padding: 0;">
                    @if($openBatches->isEmpty())
                    <div class="incoming-empty-state" style="border: 0; border-radius: 0;">
                        <i class="fa fa-inbox fa-2x" style="margin-bottom: 10px;"></i>
                        <div>{{ __('messages.no_open_batches') }}</div>
                    </div>
                    @else
                    <div class="list-group" style="margin-bottom: 0;">
                        @foreach($openBatches as $batch)
                        <a href="{{ route('incoming-products.index', ['shipment_id' => $batch->id]) }}"
                           class="list-group-item incoming-batch-item {{ $selectedBatch && $selectedBatch->id === $batch->id ? 'active' : '' }}"
                           data-shipment-id="{{ $batch->id }}">
                            <div class="incoming-batch-title">
                                <span>{{ $batch->title ?: $batch->reference_no }}</span>
                                <span class="label label-{{ $batch->status_badge_class }} incoming-batch-status">{{ $batch->status_label }}</span>
                            </div>
                            <div class="incoming-batch-meta">
                                <div>{{ __('messages.pending_items_count') }}: <strong class="incoming-batch-pending">{{ $batch->pending_items_count }}</strong></div>
                                <div>{{ __('messages.confirmed_items_count') }}: <strong class="incoming-batch-confirmed">{{ $batch->confirmed_items_count }}</strong></div>
                                <div>{{ __('messages.total_items_count') }}: <strong class="incoming-batch-total">{{ $batch->total_items_count }}</strong></div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="panel panel-default">
                <div class="panel-heading clearfix">
                    <strong><i class="fa fa-archive"></i> {{ __('messages.selected_batch') }}</strong>
                    @if($selectedBatch)
                    <div class="btn-group pull-right">
                        @if($selectedBatch->confirmed_items_count > 0)
                        <a href="{{ route('incoming-products.history', ['shipment_id' => $selectedBatch->id]) }}" class="btn btn-default btn-xs">
                            <i class="fa fa-history"></i> {{ __('messages.view_history') }}
                        </a>
                        @endif
                        <button type="button" id="closeBatchBtn" class="btn btn-warning btn-xs">
                            <i class="fa fa-check-square-o"></i> {{ __('messages.close_batch') }}
                        </button>
                    </div>
                    @endif
                </div>
                <div class="panel-body">
                    @if($selectedBatch)
                    <div class="clearfix">
                        <h3 id="selectedBatchTitle" style="margin-top: 0; margin-bottom: 6px;">{{ $selectedBatch->title ?: $selectedBatch->reference_no }}</h3>
                        <span id="selectedBatchStatus" class="label label-{{ $selectedBatch->status_badge_class }}">{{ $selectedBatch->status_label }}</span>
                    </div>

                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-6">
                            <p><strong>{{ __('messages.created_by') }}:</strong> <span id="selectedBatchCreator">{{ $selectedBatch->creator_name }}</span></p>
                            <p><strong>{{ __('messages.created_at') }}:</strong> <span id="selectedBatchCreatedAt">{{ $selectedBatch->created_at_display }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('messages.sent_at') }}:</strong> <span id="selectedBatchSentAt">{{ $selectedBatch->sent_at_display ?: '-' }}</span></p>
                            <p><strong>{{ __('messages.incoming_batch_notes') }}:</strong> <span id="selectedBatchNotes" class="incoming-note">{{ $selectedBatch->notes ?: '-' }}</span></p>
                        </div>
                    </div>

                    <div class="incoming-summary-grid">
                        <div class="incoming-summary-box">
                            <span class="incoming-summary-label">{{ __('messages.pending_items_count') }}</span>
                            <span class="incoming-summary-value" id="selectedBatchPendingCount">{{ $selectedBatch->pending_items_count }}</span>
                        </div>
                        <div class="incoming-summary-box">
                            <span class="incoming-summary-label">{{ __('messages.confirmed_items_count') }}</span>
                            <span class="incoming-summary-value" id="selectedBatchConfirmedCount">{{ $selectedBatch->confirmed_items_count }}</span>
                        </div>
                        <div class="incoming-summary-box">
                            <span class="incoming-summary-label">{{ __('messages.total_items_count') }}</span>
                            <span class="incoming-summary-value" id="selectedBatchTotalCount">{{ $selectedBatch->total_items_count }}</span>
                        </div>
                    </div>

                    <div class="incoming-toolbar">
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

                        @if($canManageIncoming)
                        <button type="button"
                                class="btn btn-success btn-lg"
                                data-toggle="modal"
                                data-target="#AddIncomingItem">
                            <i class="fa fa-plus"></i> {{ __('messages.add_incoming_item') }}
                        </button>
                        @endif
                    </div>
                    <small style="display: block; margin-bottom: 10px; color: #777;">{{ __('messages.scan_and_confirm') }}</small>

                    <div class="table-responsive">
                        <table id="IncomingPendingTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.input_date') }}</th>
                                    <th>{{ __('messages.name') }}</th>
                                    <th>{{ __('messages.barcode') }}</th>
                                    <th>{{ __('messages.quantity') }}</th>
                                    @if($canSeeCost)
                                    <th>{{ __('messages.cost') }}</th>
                                    @endif
                                    <th>{{ __('messages.price') }}</th>
                                    <th>{{ __('messages.product_type') }}</th>
                                    <th>{{ __('messages.expire_date') }}</th>
                                    <th>{{ __('messages.action') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @else
                    <div class="incoming-empty-state">
                        <i class="fa fa-cubes fa-2x" style="margin-bottom: 10px;"></i>
                        <div>
                            {{ $canManageIncoming ? __('messages.create_batch_first') : __('messages.select_batch_to_view_items') }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($canManageIncoming)
<div class="modal fade" id="CreateBatchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="createBatchForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">{{ __('messages.create_batch') }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="batch-title">{{ __('messages.incoming_batch_name') }}</label>
                        <input type="text" id="batch-title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="batch-notes">{{ __('messages.incoming_batch_notes') }}</label>
                        <textarea id="batch-notes" class="form-control" rows="4" placeholder="{{ __('messages.batch_notes_optional') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="AddIncomingItem" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="addIncomingItemForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">{{ __('messages.add_incoming_item') }}</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="incoming-shipment-id" value="{{ $selectedShipmentId }}">
                    @if($selectedBatch)
                    <div class="alert alert-info" style="padding: 10px 12px;">
                        <strong>{{ __('messages.batch') }}:</strong> {{ $selectedBatch->title ?: $selectedBatch->reference_no }}
                    </div>
                    @endif

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
                        @if($canSeeCost)
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="incoming-cost">{{ __('messages.cost') }}</label>
                                <input type="number" id="incoming-cost" class="form-control" min="0" step="0.01">
                            </div>
                        </div>
                        @endif
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
                    <button type="submit" class="btn btn-success">{{ __('messages.submit') }}</button>
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
    const canSeeCost = @json($canSeeCost);
    const selectedShipmentId = @json($selectedShipmentId);

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

    function buildColumns() {
        const columns = [
            { data: 'created_at_display' },
            { data: 'name' },
            { data: 'barcode' },
            { data: 'qty' }
        ];

        if (canSeeCost) {
            columns.push({ data: 'cost_display' });
        }

        columns.push({ data: 'price_display' });
        columns.push({ data: 'category_name' });
        columns.push({ data: 'expire_date' });
        columns.push({
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
        });

        return columns;
    }

    const pendingTable = $('#IncomingPendingTable').length ? $('#IncomingPendingTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: {
            url: '{{ route("incoming-products.data") }}',
            type: 'GET',
            data: function (d) {
                d.shipment_id = selectedShipmentId;
            }
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columns: buildColumns()
    }) : null;

    if ($.fn.datepicker) {
        $('.incoming-expire-picker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    }

    function setStatusLabel($element, badgeClass, label) {
        $element.removeClass('label-primary label-warning label-success')
            .addClass('label-' + badgeClass)
            .text(label);
    }

    function updateSelectedBatchSummary(batch) {
        if (!batch) {
            return;
        }

        $('#selectedBatchTitle').text(batch.title);
        $('#selectedBatchCreator').text(batch.creator_name || '-');
        $('#selectedBatchCreatedAt').text(batch.created_at_display || '-');
        $('#selectedBatchSentAt').text(batch.sent_at_display || '-');
        $('#selectedBatchNotes').text(batch.notes || '-');
        $('#selectedBatchPendingCount').text(batch.pending_items_count);
        $('#selectedBatchConfirmedCount').text(batch.confirmed_items_count);
        $('#selectedBatchTotalCount').text(batch.total_items_count);
        setStatusLabel($('#selectedBatchStatus'), batch.status_badge_class, batch.status_label);

        const $batchItem = $('.incoming-batch-item[data-shipment-id="' + batch.id + '"]');
        if ($batchItem.length) {
            $batchItem.find('.incoming-batch-pending').text(batch.pending_items_count);
            $batchItem.find('.incoming-batch-confirmed').text(batch.confirmed_items_count);
            $batchItem.find('.incoming-batch-total').text(batch.total_items_count);
            setStatusLabel($batchItem.find('.incoming-batch-status'), batch.status_badge_class, batch.status_label);
        }
    }

    function handlePendingActionSuccess(response) {
        if (response.batch) {
            updateSelectedBatchSummary(response.batch);
        }

        if (response.redirect_url) {
            window.location = response.redirect_url;
            return;
        }

        if (pendingTable) {
            pendingTable.ajax.reload(null, false);
        }
    }

    function confirmByItemId(itemId) {
        $.ajax({
            url: '/incoming-products/' + itemId + '/confirm',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                shipment_id: selectedShipmentId
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    handlePendingActionSuccess(response);
                } else {
                    showError(response.message || '{{ __("messages.pending_item_not_found") }}');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.pending_item_not_found") }}';
                showError(message);
            }
        });
    }

    function confirmByBarcode(barcodeValue) {
        $.ajax({
            url: '{{ route("incoming-products.confirm-by-barcode") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                barcode: barcodeValue,
                shipment_id: selectedShipmentId
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    handlePendingActionSuccess(response);
                } else {
                    showError(response.message || '{{ __("messages.pending_item_not_found") }}');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.pending_item_not_found") }}';
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
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    handlePendingActionSuccess(response);
                } else {
                    showError(response.message || 'Failed to delete incoming item.');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to delete incoming item.';
                showError(message);
            }
        });
    }

    function closeBatch() {
        $.ajax({
            url: '/incoming-products/batches/' + selectedShipmentId + '/close',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    if (response.redirect_url) {
                        window.location = response.redirect_url;
                        return;
                    }
                    handlePendingActionSuccess(response);
                } else {
                    showError(response.message || '{{ __("messages.batch_close_requires_no_pending") }}');
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.batch_close_requires_no_pending") }}';
                showError(message);
            }
        });
    }

    $('#scanConfirmBtn').on('click', function () {
        if (!selectedShipmentId) {
            showError('{{ __("messages.select_batch_first") }}');
            return;
        }

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
        }, function (isConfirm) {
            if (!isConfirm) {
                return;
            }
            deleteIncomingItem(itemId);
        });
    });

    $('#closeBatchBtn').on('click', function () {
        if (!selectedShipmentId) {
            showError('{{ __("messages.select_batch_first") }}');
            return;
        }

        swal({
            title: '{{ __("messages.are_you_sure") }}',
            text: '{{ __("messages.close_batch_confirm") }}',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f0ad4e',
            confirmButtonText: '{{ __("messages.yes") }}',
            closeOnConfirm: true
        }, function (isConfirm) {
            if (!isConfirm) {
                return;
            }
            closeBatch();
        });
    });

    @if($canManageIncoming)
    function resetCreateBatchForm() {
        $('#batch-title').val('');
        $('#batch-notes').val('');
    }

    function resetAddIncomingForm() {
        $('#incoming-name').val('');
        $('#incoming-barcode').val('');
        $('#incoming-qty').val('');
        $('#incoming-cost').val('');
        $('#incoming-price').val('');
        $('#incoming-category').val('');
        $('#incoming-expire').val('');
    }

    $('#createBatchForm').on('submit', function (event) {
        event.preventDefault();

        $.ajax({
            url: '{{ route("incoming-products.batches.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                title: $('#batch-title').val(),
                notes: $('#batch-notes').val()
            },
            success: function (response) {
                if (response.success && response.redirect_url) {
                    $('#CreateBatchModal').modal('hide');
                    resetCreateBatchForm();
                    window.location = response.redirect_url;
                } else {
                    showError(response.message || 'Failed to create batch.');
                }
            },
            error: function (xhr) {
                let message = 'Failed to create batch.';
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

    $('#addIncomingItemForm').on('submit', function (event) {
        event.preventDefault();

        if (!selectedShipmentId) {
            showError('{{ __("messages.create_batch_first") }}');
            return;
        }

        $.ajax({
            url: '{{ route("incoming-products.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                incoming_shipment_id: selectedShipmentId,
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
                    handlePendingActionSuccess(response);
                } else {
                    showError(response.message || 'Failed to add incoming item.');
                }
            },
            error: function (xhr) {
                let message = 'Failed to add incoming item.';
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
