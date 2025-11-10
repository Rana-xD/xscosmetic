@extends('layouts.application')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="page-header" style="margin-top: 0; border-bottom: 2px solid #337ab7;">
        <h3 style="margin: 0; padding-bottom: 10px;"><i class="fa fa-clock-o"></i> Clock In / Clock Out</h3>
    </div>

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <!-- Barcode Scanning Notice -->
            <div class="panel panel-info">
                <div class="panel-heading" style="background-color: #5bc0de; border-color: #46b8da; color: white;">
                    <h4 class="panel-title"><i class="fa fa-barcode"></i> Barcode Scanning System</h4>
                </div>
                <div class="panel-body text-center" style="padding: 50px 30px;">
                    <i class="fa fa-barcode fa-5x" style="color: #5bc0de; margin-bottom: 25px;"></i>
                    <h3 style="color: #337ab7; margin-bottom: 20px;">Clock In/Out via Barcode Scanner</h3>
                    <p style="font-size: 1.2em; color: #666; line-height: 1.6; margin-bottom: 25px;">
                        The clock in/out system has been updated to use barcode scanning.<br>
                        Please present your staff card to the manager or admin for scanning.
                    </p>
                    <div style="background-color: #f9f9f9; padding: 20px; border-radius: 5px; border-left: 4px solid #5bc0de; margin-top: 30px;">
                        <h4 style="margin-top: 0; color: #337ab7;"><i class="fa fa-info-circle"></i> How It Works</h4>
                        <ul style="text-align: left; font-size: 1.1em; color: #666; line-height: 2;">
                            <li>Each staff member has a unique barcode card</li>
                            <li>Present your card to the manager/admin at the scanning station</li>
                            <li>The system will automatically clock you in or out</li>
                            <li>You'll receive confirmation on the screen</li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Current Time & Status -->
            <div class="panel panel-primary">
                <div class="panel-heading" style="background-color: #337ab7; border-color: #2e6da4;">
                    <h4 class="panel-title"><i class="fa fa-clock-o"></i> Current Time & Status</h4>
                </div>
                <div class="panel-body text-center" style="padding: 30px;">
                    <h1 id="current-time" style="font-family: 'Courier New', monospace; margin: 0; font-size: 3em; font-weight: bold; color: #337ab7;">--:-- --</h1>
                    <p id="current-date" style="color: #666; margin: 10px 0 25px 0; font-size: 1.1em;">Loading...</p>
                    
                    @if($activeClock)
                        <div class="alert alert-success" style="margin: 0;">
                            <h4 style="margin-top: 0;"><i class="fa fa-check-circle"></i> Currently Clocked In</h4>
                            <p style="margin: 10px 0 5px 0; font-size: 1.1em;">Started at: <strong>{{ $activeClock->clock_in_time->format('h:i A') }}</strong></p>
                            <p style="margin: 5px 0 0 0; font-size: 1.2em;">Current Hours: <strong style="font-size: 1.3em; color: #3c763d;"><span id="hours-display">0.00</span></strong> hrs</p>
                        </div>
                    @else
                        <div class="alert alert-info" style="margin: 0;">
                            <h4 style="margin-top: 0;"><i class="fa fa-info-circle"></i> Not Clocked In</h4>
                            <p style="margin: 0; font-size: 1.1em;">Ready to start your shift</p>
                        </div>
                    @endif
                </div>
            </div>


            <!-- Today's History -->
            @if($todayClocks->count() > 0)
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title"><i class="fa fa-history"></i> Today's Clock History</h4>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead style="background-color: #f5f5f5;">
                                    <tr>
                                        <th style="width: 25%;"><i class="fa fa-sign-in"></i> Clock In</th>
                                        <th style="width: 25%;"><i class="fa fa-sign-out"></i> Clock Out</th>
                                        <th style="width: 20%;"><i class="fa fa-clock-o"></i> Total Hours</th>
                                        <th style="width: 30%;"><i class="fa fa-comment"></i> Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayClocks as $clock)
                                        <tr>
                                            <td><strong style="color: #337ab7;">{{ $clock->clock_in_time->format('h:i A') }}</strong></td>
                                            <td><strong style="color: #d9534f;">{{ $clock->clock_out_time ? $clock->clock_out_time->format('h:i A') : '-' }}</strong></td>
                                            <td>
                                                @if($clock->total_hours)
                                                    <strong style="color: #5cb85c;">{{ number_format($clock->total_hours, 2) }}</strong> hrs
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="color: #666;">{{ $clock->notes ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update current time in HH:MM AM/PM format
    function updateCurrentTime() {
        var now = new Date();
        var hours = now.getHours();
        var minutes = now.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0' + minutes : minutes;
        
        var timeString = hours + ':' + minutes + ' ' + ampm;
        $('#current-time').html(timeString);
        
        // Update date
        var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        var dateString = now.toLocaleDateString('en-US', options);
        $('#current-date').html(dateString);
    }
    
    setInterval(updateCurrentTime, 1000);
    updateCurrentTime();

    // Update current hours if clocked in
    @if($activeClock)
        function updateCurrentHours() {
            var clockInTime = new Date('{{ $activeClock->clock_in_time->format("Y-m-d H:i:s") }}');
            var now = new Date();
            var hours = (now - clockInTime) / (1000 * 60 * 60);
            $('#hours-display').text(hours.toFixed(2));
        }
        
        setInterval(updateCurrentHours, 60000); // Update every minute
        updateCurrentHours();
    @endif

});
</script>
@endsection
