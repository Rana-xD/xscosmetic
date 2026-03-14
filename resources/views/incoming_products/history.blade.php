@extends('layouts.application')

@section('head')
<style>
    .incoming-history-layout {
        margin-top: 100px;
    }

    .history-batch-list .list-group-item {
        border-radius: 0;
        border-left: 4px solid transparent;
    }

    .history-batch-list .list-group-item.active,
    .history-batch-list .list-group-item.active:hover,
    .history-batch-list .list-group-item.active:focus {
        background: #f5f9ff;
        color: #2c3e50;
        border-color: #d9e6f5;
        border-left-color: #5cb85c;
    }

    .history-batch-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .history-batch-meta {
        font-size: 12px;
        color: #67707c;
        line-height: 1.7;
    }

    .history-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 15px;
    }

    .history-summary-box {
        border: 1px solid #d9e2ef;
        border-radius: 4px;
        padding: 12px;
        background: #fafcfe;
    }

    .history-summary-label {
        display: block;
        font-size: 12px;
        color: #6b7785;
        margin-bottom: 4px;
    }

    .history-summary-value {
        font-size: 18px;
        font-weight: 600;
        color: #23374d;
    }

    .history-empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #7c8794;
        border: 1px dashed #ced9e6;
        border-radius: 4px;
        background: #fbfcfe;
    }

    .history-note {
        white-space: pre-line;
    }

    @media (max-width: 991px) {
        .history-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid incoming-history-layout">
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
            <div class="panel panel-default history-batch-list">
                <div class="panel-heading">
                    <strong><i class="fa fa-history"></i> {{ __('messages.history_batches') }}</strong>
                </div>
                <div class="panel-body" style="padding: 0;">
                    @if($historyBatches->isEmpty())
                    <div class="history-empty-state" style="border: 0; border-radius: 0;">
                        <i class="fa fa-inbox fa-2x" style="margin-bottom: 10px;"></i>
                        <div>{{ __('messages.no_history_batches') }}</div>
                    </div>
                    @else
                    <div style="padding: 10px 12px; border-bottom: 1px solid #e7edf5;">
                        <input type="text"
                               id="historyBatchSearch"
                               class="form-control input-sm"
                               placeholder="{{ __('messages.search') }} {{ __('messages.batch') }}">
                    </div>
                    <div id="historyBatchNoMatch" class="text-center text-muted" style="display: none; padding: 15px 10px;">
                        {{ __('messages.no_batch_match') }}
                    </div>
                    <div class="list-group" id="historyBatchList" style="margin-bottom: 0;">
                        @foreach($historyBatches as $batch)
                        <a href="{{ route('incoming-products.history', ['shipment_id' => $batch->id]) }}"
                           class="list-group-item history-batch-item {{ $selectedBatch && $selectedBatch->id === $batch->id ? 'active' : '' }}">
                            <div class="history-batch-title">
                                <span>{{ $batch->title ?: $batch->reference_no }}</span>
                                <span class="label label-{{ $batch->status_badge_class }}">{{ $batch->status_label }}</span>
                            </div>
                            <div class="history-batch-meta">
                                <div>{{ __('messages.confirmed_items_count') }}: <strong>{{ $batch->confirmed_items_count }}</strong></div>
                                <div>{{ __('messages.pending_items_count') }}: <strong>{{ $batch->pending_items_count }}</strong></div>
                                <div>{{ __('messages.total_items_count') }}: <strong>{{ $batch->total_items_count }}</strong></div>
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
                <div class="panel-heading">
                    <strong><i class="fa fa-check-circle"></i> {{ __('messages.incoming_history') }}</strong>
                </div>
                <div class="panel-body">
                    @if($selectedBatch)
                    <div class="clearfix">
                        <h3 style="margin-top: 0; margin-bottom: 6px;">{{ $selectedBatch->title ?: $selectedBatch->reference_no }}</h3>
                        <span class="label label-{{ $selectedBatch->status_badge_class }}">{{ $selectedBatch->status_label }}</span>
                    </div>

                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-6">
                            <p><strong>{{ __('messages.created_by') }}:</strong> {{ $selectedBatch->creator_name }}</p>
                            <p><strong>{{ __('messages.created_at') }}:</strong> {{ $selectedBatch->created_at_display }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('messages.sent_at') }}:</strong> {{ $selectedBatch->sent_at_display ?: '-' }}</p>
                            <p><strong>{{ __('messages.incoming_batch_notes') }}:</strong> <span class="history-note">{{ $selectedBatch->notes ?: '-' }}</span></p>
                        </div>
                    </div>

                    <div class="history-summary-grid">
                        <div class="history-summary-box">
                            <span class="history-summary-label">{{ __('messages.confirmed_items_count') }}</span>
                            <span class="history-summary-value">{{ $selectedBatch->confirmed_items_count }}</span>
                        </div>
                        <div class="history-summary-box">
                            <span class="history-summary-label">{{ __('messages.pending_items_count') }}</span>
                            <span class="history-summary-value">{{ $selectedBatch->pending_items_count }}</span>
                        </div>
                        <div class="history-summary-box">
                            <span class="history-summary-label">{{ __('messages.total_items_count') }}</span>
                            <span class="history-summary-value">{{ $selectedBatch->total_items_count }}</span>
                        </div>
                    </div>

                    <div class="table-responsive" style="margin-top: 20px;">
                        <table id="IncomingHistoryTable" class="table table-striped table-bordered" width="100%">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.confirmed_at') }}</th>
                                    <th>{{ __('messages.name') }}</th>
                                    <th>{{ __('messages.barcode') }}</th>
                                    <th>{{ __('messages.quantity') }}</th>
                                    @if($canSeeCost)
                                    <th>{{ __('messages.cost') }}</th>
                                    @endif
                                    <th>{{ __('messages.price') }}</th>
                                    <th>{{ __('messages.confirmed_by') }}</th>
                                    <th>{{ __('messages.linked_product') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @else
                    <div class="history-empty-state">
                        <i class="fa fa-archive fa-2x" style="margin-bottom: 10px;"></i>
                        <div>{{ __('messages.no_history_batches') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    const selectedShipmentId = @json($selectedShipmentId);
    const canSeeCost = @json($canSeeCost);
    const $historyBatchSearch = $('#historyBatchSearch');

    if ($historyBatchSearch.length) {
        $historyBatchSearch.on('input', function () {
            const keyword = $.trim($(this).val()).toLowerCase();
            let visibleCount = 0;

            $('.history-batch-item').each(function () {
                const $item = $(this);
                const matches = keyword === '' || $item.text().toLowerCase().indexOf(keyword) !== -1;
                $item.toggle(matches);

                if (matches) {
                    visibleCount += 1;
                }
            });

            $('#historyBatchNoMatch').toggle(visibleCount === 0);
        });
    }

    if (!$('#IncomingHistoryTable').length) {
        return;
    }

    const columns = [
        { data: 'confirmed_at_display' },
        { data: 'name' },
        { data: 'barcode' },
        { data: 'qty' }
    ];

    if (canSeeCost) {
        columns.push({ data: 'cost_display' });
    }

    columns.push({ data: 'price_display' });
    columns.push({ data: 'confirmed_by_name' });
    columns.push({ data: 'product_name' });

    $('#IncomingHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        searchDelay: 350,
        ajax: {
            url: '{{ route("incoming-products.history.data") }}',
            type: 'GET',
            data: function (d) {
                d.shipment_id = selectedShipmentId;
            }
        },
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        columns: columns
    });
});
</script>
@endsection
