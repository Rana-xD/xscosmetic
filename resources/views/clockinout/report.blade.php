@extends('layouts.application')

@section('content')
<div class="container-fluid" style="padding: 20px;">
    <!-- Header -->
    <div class="page-header" style="margin-top: 0; border-bottom: 2px solid #337ab7;">
        <h3 style="margin: 0; padding-bottom: 10px;"><i class="fa fa-bar-chart"></i> Clock In/Out Reports</h3>
        <p style="color: #666; margin: 5px 0 0 0; font-size: 1.05em;">{{ $title }}</p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade in">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong><i class="fa fa-exclamation-circle"></i> Error!</strong> {{ session('error') }}
        </div>
    @endif

    <!-- Filter Section -->
    <div class="panel panel-primary">
        <div class="panel-heading" style="background-color: #337ab7; border-color: #2e6da4;">
            <h4 class="panel-title"><i class="fa fa-filter"></i> Filter Options</h4>
        </div>
        <div class="panel-body" style="padding: 20px;">
            <form method="GET" action="{{ route('clockreport.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="report_type">Report Type</label>
                            <select name="report_type" id="report_type" class="form-control" onchange="toggleDateFields()">
                                <option value="daily" {{ $reportType == 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="monthly" {{ $reportType == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3" id="date_group">
                        <div class="form-group">
                            <label for="date" id="date_label">Date</label>
                            <input type="date" name="date" id="date" class="form-control" value="{{ $date }}">
                            <input type="month" name="month" id="month" class="form-control" value="{{ $date ? date('Y-m', strtotime($date)) : '' }}" style="display: none;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="user_id">Employee</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="all">All Employees</option>
                                @foreach($staffUsers as $user)
                                    <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>{{ $user->username }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg" style="padding: 10px 30px;">
                            <i class="fa fa-search"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Records Table -->
    @if($records->count() > 0)
        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #f5f5f5;">
                <h4 class="panel-title"><i class="fa fa-table"></i> Clock Records <span class="badge" style="background-color: #337ab7;">{{ $records->count() }}</span></h4>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead style="background-color: #f5f5f5;">
                            <tr>
                                <th style="width: 15%;"><i class="fa fa-user"></i> Employee</th>
                                <th style="width: 20%;"><i class="fa fa-sign-in"></i> Clock In</th>
                                <th style="width: 20%;"><i class="fa fa-sign-out"></i> Clock Out</th>
                                <th style="width: 12%;"><i class="fa fa-clock-o"></i> Total Hours</th>
                                <th style="width: 10%;"><i class="fa fa-info-circle"></i> Status</th>
                                <th style="width: 23%;"><i class="fa fa-comment"></i> Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr>
                                    <td><strong style="color: #337ab7;">{{ $record->user->username }}</strong></td>
                                    <td>
                                        <strong>{{ $record->clock_in_time->format('M j, Y') }}</strong><br>
                                        <small class="text-muted"><i class="fa fa-clock-o"></i> {{ $record->clock_in_time->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($record->clock_out_time)
                                            <strong>{{ $record->clock_out_time->format('M j, Y') }}</strong><br>
                                            <small class="text-muted"><i class="fa fa-clock-o"></i> {{ $record->clock_out_time->format('h:i A') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->total_hours)
                                            <strong style="color: #5cb85c; font-size: 1.1em;">{{ number_format($record->total_hours, 2) }}</strong> hrs
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->status == 'active')
                                            <span class="label label-info"><i class="fa fa-circle"></i> Active</span>
                                        @else
                                            <span class="label label-success"><i class="fa fa-check"></i> Completed</span>
                                        @endif
                                    </td>
                                    <td style="color: #666;">{{ $record->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <div class="col-md-6">
                        <p style="margin: 0;"><i class="fa fa-info-circle text-muted"></i> Showing <strong>{{ $records->count() }}</strong> records</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p style="margin: 0;"><i class="fa fa-clock-o text-muted"></i> Total Hours: <strong style="color: #5cb85c; font-size: 1.2em;">{{ number_format($stats['total_hours'], 2) }}</strong> hrs</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="panel panel-default">
            <div class="panel-body text-center" style="padding: 50px;">
                <i class="fa fa-clock-o fa-4x text-muted" style="opacity: 0.3;"></i>
                <h4 style="margin-top: 20px; color: #666;">No clock records found</h4>
                <p class="text-muted">No clock in/out records found for the selected period.<br>Try adjusting the filters or date range.</p>
            </div>
        </div>
    @endif
</div>

<script>
function toggleDateFields() {
    var reportType = document.getElementById('report_type').value;
    var dateInput = document.getElementById('date');
    var monthInput = document.getElementById('month');
    var dateLabel = document.getElementById('date_label');
    
    if (reportType === 'monthly') {
        // Show month picker, hide date picker
        dateInput.style.display = 'none';
        monthInput.style.display = 'block';
        dateLabel.textContent = 'Month';
        
        // Disable date input and enable month input
        dateInput.disabled = true;
        monthInput.disabled = false;
    } else {
        // Show date picker, hide month picker
        dateInput.style.display = 'block';
        monthInput.style.display = 'none';
        dateLabel.textContent = 'Date';
        
        // Enable date input and disable month input
        dateInput.disabled = false;
        monthInput.disabled = true;
    }
}

// Initialize on page load
toggleDateFields();
</script>
@endsection
