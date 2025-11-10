@extends('layouts.application')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <!-- Header -->
    <div class="page-header" style="margin-top: 0; border-bottom: 2px solid #337ab7;">
        <h3 style="margin: 0; padding-bottom: 10px;"><i class="fa fa-barcode"></i> Staff Clock In/Out Scanner</h3>
        <p style="color: #666; margin: 5px 0 0 0; font-size: 1.05em;">Scan staff barcode to clock in/out - {{ \Carbon\Carbon::today()->format('F j, Y') }}</p>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade in">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> {{ session('error') }}
    </div>
    @endif

    <div class="row">
        <!-- Barcode Scanner Section -->
        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading" style="background-color: #337ab7; border-color: #2e6da4;">
                    <h4 class="panel-title"><i class="fa fa-barcode"></i> Scan Barcode</h4>
                </div>
                <div class="panel-body" style="padding: 30px;">
                    <form id="scanForm">
                        <div class="form-group">
                            <label for="barcode" style="font-size: 1.1em;">Barcode Number</label>
                            <input type="text" 
                                   id="barcode" 
                                   name="barcode" 
                                   class="form-control input-lg" 
                                   placeholder="Scan or enter barcode..." 
                                   autocomplete="off"
                                   autofocus
                                   style="font-size: 1.3em; padding: 15px;">
                        </div>
                        <div id="scanMessage" style="margin-top: 20px; display: none;"></div>
                    </form>

                    <!-- Recent Scans Display -->
                    <div id="recentScans" style="margin-top: 30px;">
                        <h4 style="border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 15px;">
                            <i class="fa fa-history"></i> Recent Scans
                        </h4>
                        <div id="scansList"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Records Section -->
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading" style="background-color: #f5f5f5;">
                    <h4 class="panel-title"><i class="fa fa-list"></i> Today's Clock Records</h4>
                </div>
                <div class="panel-body" style="max-height: 600px; overflow-y: auto;">
                    <div id="todayRecordsList">
                        @if($todayRecords->count() > 0)
                            @foreach($todayRecords as $record)
                            <div class="record-item" style="padding: 12px; border-bottom: 1px solid #eee; background-color: {{ $record->status == 'active' ? '#f0f8ff' : '#fff' }};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong style="color: #337ab7; font-size: 1.1em;">{{ $record->user->username }}</strong>
                                        <div style="margin-top: 5px;">
                                            <span style="color: #5cb85c;">
                                                <i class="fa fa-sign-in"></i> {{ $record->clock_in_time->format('h:i A') }}
                                            </span>
                                            @if($record->clock_out_time)
                                                <span style="color: #d9534f; margin-left: 15px;">
                                                    <i class="fa fa-sign-out"></i> {{ $record->clock_out_time->format('h:i A') }}
                                                </span>
                                            @else
                                                <span class="label label-info" style="margin-left: 10px;">Active</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($record->total_hours)
                                    <div style="text-align: right;">
                                        <strong style="color: #5cb85c; font-size: 1.2em;">{{ number_format($record->total_hours, 2) }}</strong>
                                        <div style="font-size: 0.9em; color: #666;">hours</div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div style="text-align: center; padding: 40px; color: #999;">
                                <i class="fa fa-clock-o fa-3x" style="opacity: 0.3;"></i>
                                <p style="margin-top: 15px;">No records for today yet</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.scan-success {
    padding: 15px;
    background-color: #dff0d8;
    border: 1px solid #d6e9c6;
    border-radius: 4px;
    color: #3c763d;
}

.scan-error {
    padding: 15px;
    background-color: #f2dede;
    border: 1px solid #ebccd1;
    border-radius: 4px;
    color: #a94442;
}

.scan-item {
    padding: 12px;
    margin-bottom: 10px;
    border-left: 4px solid #337ab7;
    background-color: #f9f9f9;
    border-radius: 3px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.clock-in-item {
    border-left-color: #5cb85c;
}

.clock-out-item {
    border-left-color: #d9534f;
}
</style>

<script>
$(document).ready(function() {
    let recentScans = [];
    
    // Auto-focus on barcode input
    $('#barcode').focus();
    
    // Handle form submission (Enter key or barcode scanner)
    $('#scanForm').on('submit', function(e) {
        e.preventDefault();
        processScan();
    });
    
    // Also trigger on input change (for barcode scanners that don't press Enter)
    $('#barcode').on('change', function() {
        if ($(this).val().trim() !== '') {
            processScan();
        }
    });
    
    function processScan() {
        const barcode = $('#barcode').val().trim();
        
        if (!barcode) {
            showMessage('Please scan or enter a barcode', 'error');
            return;
        }
        
        // Disable input during processing
        $('#barcode').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("attendance.process") }}',
            method: 'POST',
            data: {
                barcode: barcode,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.message, 'success');
                    addRecentScan(response);
                    refreshTodayRecords();
                    
                    // Play success sound (optional)
                    playBeep();
                } else {
                    showMessage(response.message, 'error');
                }
            },
            error: function(xhr) {
                let message = 'Error processing scan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showMessage(message, 'error');
            },
            complete: function() {
                // Clear input and re-enable
                $('#barcode').val('').prop('disabled', false).focus();
            }
        });
    }
    
    function showMessage(message, type) {
        const messageDiv = $('#scanMessage');
        const className = type === 'success' ? 'scan-success' : 'scan-error';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        messageDiv.html('<i class="fa ' + icon + '"></i> ' + message)
                  .removeClass('scan-success scan-error')
                  .addClass(className)
                  .fadeIn();
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 3000);
    }
    
    function addRecentScan(scanData) {
        const scanItem = $('<div>')
            .addClass('scan-item')
            .addClass(scanData.action === 'clock_in' ? 'clock-in-item' : 'clock-out-item')
            .html(
                '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                    '<div>' +
                        '<strong style="font-size: 1.1em;">' + scanData.staff_name + '</strong>' +
                        '<div style="margin-top: 3px; color: #666;">' +
                            '<i class="fa fa-' + (scanData.action === 'clock_in' ? 'sign-in' : 'sign-out') + '"></i> ' +
                            (scanData.action === 'clock_in' ? 'Clocked In' : 'Clocked Out') +
                        '</div>' +
                    '</div>' +
                    '<div style="text-align: right;">' +
                        '<strong style="font-size: 1.2em;">' + scanData.time + '</strong>' +
                        (scanData.total_hours ? '<div style="font-size: 0.9em; color: #666;">' + scanData.total_hours + ' hrs</div>' : '') +
                    '</div>' +
                '</div>'
            );
        
        $('#scansList').prepend(scanItem);
        
        // Keep only last 5 scans
        if ($('#scansList .scan-item').length > 5) {
            $('#scansList .scan-item:last').remove();
        }
    }
    
    function refreshTodayRecords() {
        $.ajax({
            url: '{{ route("attendance.today") }}',
            method: 'GET',
            success: function(response) {
                if (response.success && response.records) {
                    updateTodayRecordsList(response.records);
                }
            }
        });
    }
    
    function updateTodayRecordsList(records) {
        const listDiv = $('#todayRecordsList');
        listDiv.empty();
        
        if (records.length === 0) {
            listDiv.html(
                '<div style="text-align: center; padding: 40px; color: #999;">' +
                    '<i class="fa fa-clock-o fa-3x" style="opacity: 0.3;"></i>' +
                    '<p style="margin-top: 15px;">No records for today yet</p>' +
                '</div>'
            );
            return;
        }
        
        records.forEach(function(record) {
            const clockInTime = new Date(record.clock_in_time);
            const clockOutTime = record.clock_out_time ? new Date(record.clock_out_time) : null;
            
            const recordItem = $('<div>')
                .addClass('record-item')
                .css({
                    'padding': '12px',
                    'border-bottom': '1px solid #eee',
                    'background-color': record.status === 'active' ? '#f0f8ff' : '#fff'
                })
                .html(
                    '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                        '<div>' +
                            '<strong style="color: #337ab7; font-size: 1.1em;">' + record.user.username + '</strong>' +
                            '<div style="margin-top: 5px;">' +
                                '<span style="color: #5cb85c;">' +
                                    '<i class="fa fa-sign-in"></i> ' + formatTime(clockInTime) +
                                '</span>' +
                                (clockOutTime ? 
                                    '<span style="color: #d9534f; margin-left: 15px;">' +
                                        '<i class="fa fa-sign-out"></i> ' + formatTime(clockOutTime) +
                                    '</span>' : 
                                    '<span class="label label-info" style="margin-left: 10px;">Active</span>'
                                ) +
                            '</div>' +
                        '</div>' +
                        (record.total_hours ? 
                            '<div style="text-align: right;">' +
                                '<strong style="color: #5cb85c; font-size: 1.2em;">' + parseFloat(record.total_hours).toFixed(2) + '</strong>' +
                                '<div style="font-size: 0.9em; color: #666;">hours</div>' +
                            '</div>' : ''
                        ) +
                    '</div>'
                );
            
            listDiv.append(recordItem);
        });
    }
    
    function formatTime(date) {
        let hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const minutesStr = minutes < 10 ? '0' + minutes : minutes;
        return hours + ':' + minutesStr + ' ' + ampm;
    }
    
    function playBeep() {
        // Optional: Play a beep sound on successful scan
        // You can add an audio element if needed
    }
    
    // Refresh records every 30 seconds
    setInterval(refreshTodayRecords, 30000);
});
</script>
@endsection
