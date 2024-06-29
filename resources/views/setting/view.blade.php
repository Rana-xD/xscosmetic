<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
<style>
  #handleUpdateSetting {
    margin-top: 40px;
  }
</style>
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
   <div class="col-md-4" style="padding-left: 0">
          <div class="calendar">
            <p class="label-text">Exchange Rate 1$:</p>
            <div class="input-group">
              <input type="text" class="form-control exchange-rate" value="{{ $setting->exchange_rate == null ? 0 : $setting->exchange_rate }}" placeholder="42000">
            </div>
            <button type="button" class="btn btn-add" id="handleUpdateSetting">Update</button>
          </div>
        </div>
        
   </div>
   
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
    $('#handleUpdateSetting').on('click',function(e){
      e.preventDefault();
      let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('exchange_rate',$(".exchange-rate").val());
        $.ajax({
          url: '/setting/update',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            swal({
                title: 'DONE',
                type: "success",
                timer: 1500,
                showCancelButton: false,
                showConfirmButton: false
            }, function(data) {
                location.reload(true);
            });
          },
          error: function(err){
            console.log(err);
          } 
        });
      });
  });
  </script>

@endsection