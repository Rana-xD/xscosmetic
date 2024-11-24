<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
<style>
  .loader {
  display: none;
  width: 40px;
  aspect-ratio: 1;
  --c:no-repeat radial-gradient(farthest-side,#514b82 92%,#0000);
  background: 
    var(--c) 50%  0, 
    var(--c) 50%  100%, 
    var(--c) 100% 50%, 
    var(--c) 0    50%;
  background-size: 10px 10px;
  animation: l18 1s infinite;
  position: relative;
}
.loader::before {    
  content:"";
  position: absolute;
  inset:0;
  margin: 3px;
  background: repeating-conic-gradient(#0000 0 35deg,#514b82 0 90deg);
  -webkit-mask: radial-gradient(farthest-side,#0000 calc(100% - 3px),#000 0);
  border-radius: 50%;
}
@keyframes l18 { 
  100%{transform: rotate(.5turn)}
}
</style>
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <table id="Table" class="table table-striped table-bordered" cellspacing="0" width="100%">
          <thead>
              <tr>
                  <th class="hidden-xs">{{ __('messages.sale_table_number') }}</th>
                  <th>{{ __('messages.date') }}</th>
                  <th>{{ __('messages.usd_amount') }}</th>
                  <th>{{ __('messages.riel_amount') }}</th>
                  <th>{{ __('messages.edit') }}</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $change_lists as $change)
              
              <tr class="change-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="change-date">{{ $change->date }}</td>
                 <td class="change-usd">{{ $change->usd }} $</td>
                 <td class="change-riel">{{ number_format($change->riel) }} ៛</td>
                 <td><div class="btn-group">
                  <a class="btn btn-default delete-btn delete-change" data-id="{{ $change->id }}" ><i class="fa fa-times" data-id="{{ $change->id }}"></i></a>
                  
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default edit-change" data-id="{{ $change->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $change->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Addchange">{{ __('messages.add_change_log') }}</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      
    function showSpinner() {

      $('.loader').css('display', 'block');
      $('.modal-btn').prop('disabled', true);

    }

    function hideSpinner() {
      $('.loader').css('display', 'none');
      $('.modal-btn').prop('disabled', false);
    }

      $("#addChange").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('usd',$("#usdCurrency").val());
        formData.append('riel',$("#rielCurrency").val());
        showSpinner();
        $.ajax({
          url: '/change/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            hideSpinner();
            if(res.code === 400){
              swal({
                title: '{{ __("messages.are_you_sure") }}',
                type: "error",
                text: '{{ __("messages.delete_confirm") }}',
                timer: 2500,
                showCancelButton: false,
                showConfirmButton: false
            }, function(data) {
                location.reload(true);
            });
            }else {
              $('#usdCurrency').val('');
              $('#usdCurrency').val('');
              location.reload();
            }

            
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-change").on("click",(e)=>{
        swal({   title: '{{ __("messages.are_you_sure") }}',
          text: '{{ __("messages.delete_confirm") }}',
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: 'YES',
          closeOnConfirm: false },
          function(){
            console.log(e.target)
            let id = e.target.getAttribute('data-id');
            let formData = {
              "id" : id
            };
            console.log(formData);
            $.ajax({
              url: '/change/delete',
              type: "GET", 
              data: formData,
              contentType: false,
              processData: true,
              success: function(res){
                location.reload();
              },
              error: function(err){
                console.log(err);
              } 
            });
          })
      });

      $(".edit-change").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            usdCurrency = parseFloat($(self).parents('.change-data').find('.change-usd').text().replace('$', "").trim()),
            rielCurrency = parseInt($(self).parents('.change-data').find('.change-riel').text().replace(/,/g, "").replace('៛', "").trim());
            
            hideSpinner();
            $('#ChangeId').val(id);
            $('#usdCurrencyEdit').val(usdCurrency);
            $('#rielCurrencyEdit').val(rielCurrency);
            $('#Updatechange').modal('show');
      });

      $('#editChange').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('id',$("#ChangeId").val());
        formData.append('usd',$("#usdCurrencyEdit").val());
        formData.append('riel',$("#rielCurrencyEdit").val());
        showSpinner();
        $.ajax({
          url: '/change/update',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            hideSpinner();
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      })
  });
</script>
<!-- Add Modal -->
<div class="modal fade" id="Addchange" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addChange" action="/change/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_change_log') }}</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="usdCurrency">{{ __('messages.usd_amount') }}</label>
             <input type="number" name="usd" Required class="form-control" id="usdCurrency" placeholder="{{ __('messages.usd_amount') }}">
           </div>
           <div class="form-group">
             <label for="rielCurrency">{{ __('messages.riel_amount') }}</label>
             <input type="number" name="riel" Required class="form-control" id="rielCurrency" placeholder="{{ __('messages.riel_amount') }}">
           </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
        <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
        <div class="loader"></div>
      </div>
    </form>
    </div>
 </div>
</div>
<!-- /.Modal -->

<!-- Edit Modal -->
<div class="modal fade" id="Updatechange" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editChange" action="/change/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">{{ __('messages.edit_change_log') }}</h4>
       </div>
       <div class="modal-body">
       <div class="form-group">
             <label for="usdCurrency">{{ __('messages.usd_amount') }}</label>
             <input type="number" name="usd" Required class="form-control" id="usdCurrencyEdit" placeholder="{{ __('messages.usd_amount') }}">
           </div>
           <div class="form-group">
             <label for="rielCurrency">{{ __('messages.riel_amount') }}</label>
             <input type="number" name="riel" Required class="form-control" id="rielCurrencyEdit" placeholder="{{ __('messages.riel_amount') }}">
           </div>
           <input type="hidden"  name="change-id" id="ChangeId">
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
         <button type="submit" class="btn btn-add">{{ __('messages.submit') }}</button>
         <div class="loader"></div>
       </div>
     </form>
     </div>
  </div>
 </div>
 <!-- /.Modal -->




@endsection