<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <table id="Table" class="table table-striped table-bordered w-100">
          <thead>
              <tr>
                  <th class="hidden-xs">{{ __('messages.sale_table_number') }}</th>
                  <th>{{ __('messages.delivery_name') }}</th>
                  <th>{{ __('messages.location') }}</th>
                  <th>{{ __('messages.delivery_cost') }}</th>
                  <th>{{ __('messages.edit') }}</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $deliveries as $delivery)
              
              <tr class="delivery-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="delivery-name">{{ $delivery->name }}</td>
                 <td class="delivery-location">{{ $delivery->location }}</td>
                 <td class="delivery-cost">${{ number_format($delivery->cost, 2) }}</td>
                 <td><div class="btn-group">
                  <a class="btn btn-default delete-btn delete-delivery" data-id="{{ $delivery->id }}" ><i class="fa fa-times" data-id="{{ $delivery->id }}"></i></a>
                  
                     </div>
                     <div class="btn-group">
                      <a class="btn btn-default edit-delivery" data-id="{{ $delivery->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $delivery->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#addNewDeliveryModal">{{ __('messages.add_delivery') }}</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<style>
  .radio-options {
    display: flex;
    align-items: center;
  }
  
  .radio-inline {
    display: inline-block;
    margin-right: 10px;
    padding-left: 0;
  }
  
  .radio-option {
    display: block;
    padding: 8px 20px;
    border-radius: 4px;
    cursor: pointer;
    background-color: #f8f8f8;
    border: 1px solid #ddd;
    text-align: center;
    transition: all 0.2s;
    min-width: 100px;
  }
  
  .radio-option:hover {
    background-color: #f0f0f0;
  }
  
  .radio-inline input[type="radio"] {
    display: none;
  }
  
  .radio-inline input[type="radio"]:checked + .radio-option {
    background-color: #25b09b;
    color: white;
    border-color: #25b09b;
    font-weight: bold;
  }
</style>

<script type="text/javascript">
  $(document).ready(function() {
      
      // Initialize radio buttons
      $(document).on('click', '.radio-option', function() {
        let radio = $(this).prev('input[type="radio"]');
        radio.prop('checked', true);
        
        // Set suggested cost based on location (user can still modify)
        if (radio.attr('name') === 'location') {
          if (radio.val() === 'Phnom Penh') {
            $('#delivery-cost').attr('placeholder', '$1.50');
          } else {
            $('#delivery-cost').attr('placeholder', '$2.00');
          }
        } else if (radio.attr('name') === 'location-edit') {
          if (radio.val() === 'Phnom Penh') {
            $('#delivery-cost-edit').attr('placeholder', '$1.50');
          } else {
            $('#delivery-cost-edit').attr('placeholder', '$2.00');
          }
        }
      });
      
      $("#add-delivery").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#delivery-name").val());
        formData.append('location', $('input[name="location"]:checked').val());
        // Get cost value without $ sign
        let costValue = $("#delivery-cost").val().replace('$', '').trim();
        formData.append('cost', costValue);
        $.ajax({
          url: '/delivery/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            $('#delivery-name').val('');
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-delivery").on("click",(e)=>{
        swal({   
            title: '{{ __("messages.are_you_sure") }}',
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: '{{ __("messages.yes") }}',
            cancelButtonText: '{{ __("messages.cancel") }}',
            closeOnConfirm: true,
        }, function(confirmed) {
            if (confirmed) {
                let id = e.target.getAttribute('data-id');
                let formData = {
                  "id" : id
                };
                $.ajax({
                  url: '/delivery/delete',
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
            }
        });
      });

      $(".edit-delivery").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            deliveryName = $(self).parents('.delivery-data').find('.delivery-name').text(),
            deliveryLocation = $(self).parents('.delivery-data').find('.delivery-location').text(),
            deliveryCost = $(self).parents('.delivery-data').find('.delivery-cost').text();

            $('#DeliveryId').val(id);
            $('#delivery-name-edit').val(deliveryName);
            
            // Set the cost value - remove the $ sign and any formatting
            let costValue = deliveryCost.replace('$', '').trim();
            $('#delivery-cost-edit').val(costValue);
            
            // Set the correct radio button based on the location
            if (deliveryLocation === 'Phnom Penh') {
                $('#location-phnom-penh-edit').prop('checked', true);
            } else {
                $('#location-province-edit').prop('checked', true);
            }
            
            $('#Updatedelivery').modal('show');
      });

      $('#editDelivery').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#delivery-name-edit").val());
        formData.append('id',$("#DeliveryId").val());
        formData.append('location', $('input[name="location-edit"]:checked').val());
        // Get cost value without $ sign
        let costValue = $("#delivery-cost-edit").val().replace('$', '').trim();
        formData.append('cost', costValue);
        formData.append('id',$("#DeliveryId").val());
        $.ajax({
          url: '/delivery/update',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
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
<div class="modal fade" id="addNewDeliveryModal" tabindex="-1" aria-labelledby="addNewDeliveryModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="add-delivery" action="/delivery/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_delivery') }}</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="delivery-name">{{ __('messages.delivery_name') }}</label>
             <input type="text" name="name" Required class="form-control" id="delivery-name" placeholder="{{ __('messages.delivery_name') }}">
           </div>
           <style>
           .form-group label {
             display: block;
             margin-bottom: 5px;
           }
           </style>
           <div class="form-group">
             <label for="location">{{ __('messages.location') }}</label>
             <div class="radio-options">
               <div class="radio-inline">
                 <input type="radio" name="location" id="location-phnom-penh" value="Phnom Penh" checked>
                 <label class="radio-option" for="location-phnom-penh">Phnom Penh</label>
               </div>
               <div class="radio-inline">
                 <input type="radio" name="location" id="location-province" value="Province">
                 <label class="radio-option" for="location-province">Province</label>
               </div>
             </div>
           </div>
           <div class="form-group">
             <label for="delivery-cost">{{ __('messages.delivery_cost') }}</label>
             <input type="text" class="form-control" id="delivery-cost" placeholder="$0.00">
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
<!-- /.Modal -->

<!-- Edit Modal -->
<div class="modal fade" id="Updatedelivery" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
     <div class="modal-content">
       <form id="editDelivery" action="/delivery/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">{{ __('messages.edit_delivery') }}</h4>
       </div>
       <div class="modal-body">
            <div class="form-group">
              <label for="delivery-name-edit">{{ __('messages.delivery_name') }}</label>
              <input type="text" name="name" Required class="form-control" id="delivery-name-edit" placeholder="{{ __('messages.delivery_name') }}">
              <input type="hidden" name="delivery-id" id="DeliveryId">
            </div>
            <div class="form-group">
              <label for="location-edit">{{ __('messages.location') }}</label>
              <div class="radio-options">
                <div class="radio-inline">
                  <input type="radio" name="location-edit" id="location-phnom-penh-edit" value="Phnom Penh">
                  <label class="radio-option" for="location-phnom-penh-edit">Phnom Penh</label>
                </div>
                <div class="radio-inline">
                  <input type="radio" name="location-edit" id="location-province-edit" value="Province">
                  <label class="radio-option" for="location-province-edit">Province</label>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label for="delivery-cost-edit">{{ __('messages.delivery_cost') }}</label>
              <input type="text" class="form-control" id="delivery-cost-edit" placeholder="$0.00">
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
 <!-- /.Modal -->




@endsection