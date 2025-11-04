@extends('layouts.application')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <div class="page-header" style="margin-top: 0; border-bottom: 2px solid #337ab7;">
        <h3 style="margin: 0; padding-bottom: 10px;"><i class="fa fa-clock-o"></i> Clock In / Clock Out</h3>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade in">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> {{ session('error') }}
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade in">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><i class="fa fa-check-circle"></i> Success!</strong> {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
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

            <!-- Clock In/Out Actions -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-edit"></i> Actions</h4>
                </div>
                <div class="panel-body" style="padding: 20px;">
                    <div class="form-group">
                        <label for="notes" style="font-weight: 600;">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" rows="3" placeholder="Add any notes about your shift..." style="resize: vertical;"></textarea>
                    </div>
                    
                    <div class="text-center" style="margin-top: 20px;">
                        @if($activeClock)
                            <button type="button" class="btn btn-danger btn-lg" id="clock-out-btn" style="padding: 12px 40px; font-size: 18px;">
                                <i class="fa fa-sign-out"></i> Clock Out
                            </button>
                        @else
                            <button type="button" class="btn btn-success btn-lg" id="clock-in-btn" style="padding: 12px 40px; font-size: 18px;">
                                <i class="fa fa-sign-in"></i> Clock In
                            </button>
                        @endif
                    </div>
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

    // Clock In button click
    $('#clock-in-btn').click(function() {
        var notes = $('#notes').val();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Clocking In...');
        
        $.ajax({
            url: '{{ route("clockinout.clockin") }}',
            method: 'POST',
            data: {
                notes: notes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    swal({
                        title: "Success!",
                        text: response.message,
                        type: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    swal("Error", response.message, "error");
                    btn.prop('disabled', false).html('<i class="fa fa-sign-in"></i> Clock In');
                }
            },
            error: function() {
                swal("Error", "An error occurred. Please try again.", "error");
                btn.prop('disabled', false).html('<i class="fa fa-sign-in"></i> Clock In');
            }
        });
    });

    // Clock Out button click
    $('#clock-out-btn').click(function() {
        var notes = $('#notes').val();
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Clocking Out...');
        
        $.ajax({
            url: '{{ route("clockinout.clockout") }}',
            method: 'POST',
            data: {
                notes: notes,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    swal({
                        title: "Success!",
                        text: response.message + "\nTotal Hours: " + response.total_hours,
                        type: "success",
                        timer: 3000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    swal("Error", response.message, "error");
                    btn.prop('disabled', false).html('<i class="fa fa-sign-out"></i> Clock Out');
                }
            },
            error: function() {
                swal("Error", "An error occurred. Please try again.", "error");
                btn.prop('disabled', false).html('<i class="fa fa-sign-out"></i> Clock Out');
            }
        });
    });
});
</script>
@endsection
