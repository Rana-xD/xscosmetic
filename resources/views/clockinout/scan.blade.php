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
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading" style="background-color: #337ab7; border-color: #2e6da4;">
                    <h4 class="panel-title"><i class="fa fa-barcode"></i> Scan Barcode</h4>
                </div>
                <div class="panel-body" style="padding: 30px;">
                    <form id="scanForm">
                        <div class="form-group">
                            <label for="barcode" style="font-size: 1.2em; font-weight: bold;">Barcode Number</label>
                            <input type="text" 
                                   id="barcode" 
                                   name="barcode" 
                                   class="form-control input-lg" 
                                   placeholder="Scan or enter barcode..." 
                                   autocomplete="off"
                                   autofocus
                                   style="font-size: 1.5em; padding: 20px; text-align: center; border: 2px solid #337ab7;">
                        </div>
                        <div id="scanMessage" style="margin-top: 20px; display: none;"></div>
                    </form>
                    
                    <div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border-radius: 5px; text-align: center;">
                        <i class="fa fa-info-circle" style="color: #337ab7; font-size: 1.2em;"></i>
                        <p style="margin: 10px 0 0 0; color: #666;">
                            Position cursor in the barcode field and scan the staff barcode to clock in/out
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Records Section -->
        <div class="col-md-8">
            <div class="panel panel-success">
                <div class="panel-heading" style="background-color: #5cb85c; border-color: #4cae4c; color: white;">
                    <h4 class="panel-title">
                        <i class="fa fa-clock-o"></i> Today's Clock Records
                        <span id="recordCount" class="badge" style="background-color: white; color: #5cb85c; margin-left: 10px;">{{ $todayRecords->count() }}</span>
                    </h4>
                </div>
                <div class="panel-body" style="max-height: 550px; overflow-y: auto; padding: 0;">
                    <div id="todayRecordsList">
                        @if($todayRecords->count() > 0)
                            @foreach($todayRecords as $record)
                            <div class="record-item" style="padding: 15px 20px; border-bottom: 1px solid #e0e0e0; background-color: {{ $record->status == 'active' ? '#e8f5e9' : '#fff' }}; transition: background-color 0.2s;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                            <strong style="color: #2c3e50; font-size: 1.2em;">{{ $record->user->username }}</strong>
                                            @if($record->status == 'active')
                                                <span class="label label-success" style="margin-left: 12px; font-size: 0.85em; padding: 4px 10px;">
                                                    <i class="fa fa-circle" style="font-size: 0.7em;"></i> Active
                                                </span>
                                            @endif
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 20px;">
                                            <span style="color: #27ae60; font-weight: 500;">
                                                <i class="fa fa-sign-in"></i> In: {{ $record->clock_in_time->format('h:i A') }}
                                            </span>
                                            @if($record->clock_out_time)
                                                <span style="color: #e74c3c; font-weight: 500;">
                                                    <i class="fa fa-sign-out"></i> Out: {{ $record->clock_out_time->format('h:i A') }}
                                                </span>
                                            @else
                                                <span style="color: #95a5a6; font-style: italic;">
                                                    <i class="fa fa-clock-o"></i> Still clocked in
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($record->total_hours)
                                    <div style="text-align: right; padding-left: 20px; border-left: 2px solid #e0e0e0;">
                                        <strong style="color: #27ae60; font-size: 1.2em; display: block;">{{ $record->total_hours_formatted }}</strong>
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
</style>

<script>
$(document).ready(function() {
    let lastScanTime = 0; // Prevent duplicate scans
    const SCAN_DEBOUNCE_MS = 500; // Minimum time between scans (same as POS)
    
    // Auto-focus on barcode input
    $('#barcode').focus();
    
    // Handle form submission (Enter key or barcode scanner)
    $('#scanForm').on('submit', function(e) {
        e.preventDefault();
        processScan();
    });
    
    function processScan() {
        const barcode = $('#barcode').val().trim();
        
        if (!barcode) {
            showMessage('Please scan or enter a barcode', 'error');
            return;
        }
        
        // Prevent duplicate scans within debounce period (same optimization as POS)
        const currentTime = Date.now();
        if (currentTime - lastScanTime < SCAN_DEBOUNCE_MS) {
            console.log('Scan ignored - too soon after last scan');
            $('#barcode').val('').focus();
            return;
        }
        lastScanTime = currentTime;
        
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
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 5000);
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
        
        // Update record count badge
        $('#recordCount').text(records.length);
        
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
                    'padding': '15px 20px',
                    'border-bottom': '1px solid #e0e0e0',
                    'background-color': record.status === 'active' ? '#e8f5e9' : '#fff',
                    'transition': 'background-color 0.2s'
                })
                .html(
                    '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                        '<div style="flex: 1;">' +
                            '<div style="display: flex; align-items: center; margin-bottom: 8px;">' +
                                '<strong style="color: #2c3e50; font-size: 1.2em;">' + record.user.username + '</strong>' +
                                (record.status === 'active' ? 
                                    '<span class="label label-success" style="margin-left: 12px; font-size: 0.85em; padding: 4px 10px;">' +
                                        '<i class="fa fa-circle" style="font-size: 0.7em;"></i> Active' +
                                    '</span>' : ''
                                ) +
                            '</div>' +
                            '<div style="display: flex; align-items: center; gap: 20px;">' +
                                '<span style="color: #27ae60; font-weight: 500;">' +
                                    '<i class="fa fa-sign-in"></i> In: ' + formatTime(clockInTime) +
                                '</span>' +
                                (clockOutTime ? 
                                    '<span style="color: #e74c3c; font-weight: 500;">' +
                                        '<i class="fa fa-sign-out"></i> Out: ' + formatTime(clockOutTime) +
                                    '</span>' : 
                                    '<span style="color: #95a5a6; font-style: italic;">' +
                                        '<i class="fa fa-clock-o"></i> Still clocked in' +
                                    '</span>'
                                ) +
                            '</div>' +
                        '</div>' +
                        (record.total_hours ? 
                            '<div style="text-align: right; padding-left: 20px; border-left: 2px solid #e0e0e0;">' +
                                '<strong style="color: #27ae60; font-size: 1.2em; display: block;">' + formatHoursMinutes(record.total_hours) + '</strong>' +
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
    
    function formatHoursMinutes(decimalHours) {
        const totalMinutes = Math.round(decimalHours * 60);
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        
        if (hours > 0 && minutes > 0) {
            return hours + ' hour' + (hours > 1 ? 's' : '') + ' ' + minutes + ' minute' + (minutes > 1 ? 's' : '');
        } else if (hours > 0) {
            return hours + ' hour' + (hours > 1 ? 's' : '');
        } else if (minutes > 0) {
            return minutes + ' minute' + (minutes > 1 ? 's' : '');
        }
        return '0 minutes';
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
