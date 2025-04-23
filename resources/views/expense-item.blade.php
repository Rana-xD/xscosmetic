@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <div class="table-responsive">
      <table class="table table-striped table-bordered">
          <thead>
              <tr>
                  <th class="hidden-xs">{{ __('messages.no') }}</th>
                  <th>{{ __('messages.name') }}</th>
                  <th>{{ __('messages.cost') }}</th>
                  <th>{{ __('messages.action') }}</th>
              </tr>
          </thead>

          <tbody>
              @foreach($expenseItems as $index => $item)
              <tr class="expense-item-data" id="item-{{ $item->id }}">
                 <td class="hidden-xs">{{ $index + 1 }}</td>
                 <td class="item-name">{{ $item->name }}</td>
                 <td class="item-cost">${{ $item->cost }}</td>
                 <td>
                    <div class="btn-group">
                      <a class="btn btn-default delete-btn delete-expense-item" data-id="{{ $item->id }}">
                        <i class="fa fa-times" data-id="{{ $item->id }}"></i>
                      </a>
                    </div>
                    <div class="btn-group">
                      <a class="btn btn-default edit-expense-item" data-id="{{ $item->id }}">
                        <i class="fa fa-pencil-square-o" data-id="{{ $item->id }}"></i>
                      </a>
                    </div>
                 </td>
              </tr>
              @endforeach
              @if(count($expenseItems) == 0)
              <tr>
                <td colspan="4" class="text-center">{{ __('messages.no_items_found') }}</td>
              </tr>
              @endif
          </tbody>
      </table>
      </div>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#AddExpenseItem">{{ __('messages.add_expense_item') }}</button>
</div>

<!-- Add Modal -->
<div class="modal fade" id="AddExpenseItem" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addExpenseItemForm" action="/expense-item/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">{{ __('messages.add_expense_item') }}</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="name">{{ __('messages.name') }}</label>
             <input type="text" name="name" maxlength="100" class="form-control" id="name" placeholder="{{ __('messages.enter_name') }}">
           </div>
           <div class="form-group">
             <label for="cost">{{ __('messages.cost') }}</label>
             <input type="number" step="0.01" name="cost" class="form-control" id="cost" placeholder="{{ __('messages.enter_cost') }}">
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
<div class="modal fade" id="UpdateExpenseItem" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editExpenseItemForm" action="/expense-item/update" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">{{ __('messages.edit_expense_item') }}</h4>
       </div>
       <div class="modal-body">
            <div class="form-group">
              <label for="edit-name">{{ __('messages.name') }}</label>
              <input type="text" name="name" maxlength="100" class="form-control" id="edit-name" placeholder="{{ __('messages.enter_name') }}">
              <input type="hidden" name="item-id" id="edit-item-id">
            </div>
            <div class="form-group">
              <label for="edit-cost">{{ __('messages.cost') }}</label>
              <input type="number" step="0.01" name="cost" class="form-control" id="edit-cost" placeholder="{{ __('messages.enter_cost') }}">
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

<script type="text/javascript">
  $(document).ready(function() {
      // DataTables is already initialized elsewhere in the application
      // No need to initialize it here
      
      $("#addExpenseItemForm").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#name").val());
        formData.append('cost',$("#cost").val());
        $.ajax({
          url: '/expense-item/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            $('#name').val('');
            $('#cost').val('');
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-expense-item").on("click",(e)=>{
        swal({   
            title: '{{ __('messages.are_you_sure') }}',
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: '{{ __('messages.yes') }}',
            cancelButtonText: '{{ __('messages.cancel') }}',
            closeOnConfirm: true,
        }, function(confirmed) {
            if (confirmed) {
                let id = e.target.getAttribute('data-id');
                let formData = {
                  "id" : id
                };
                $.ajax({
                  url: '/expense-item/delete/' + id,
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

      $(".edit-expense-item").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            itemRow = $(self).closest('.expense-item-data'),
            itemName = itemRow.find('.item-name').text(),
            itemCost = itemRow.find('.item-cost').text().replace('$', '');

            $('#edit-item-id').val(id);
            $('#edit-name').val(itemName);
            $('#edit-cost').val(itemCost);
            $('#UpdateExpenseItem').modal('show');
      });

      $('#editExpenseItemForm').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('name',$("#edit-name").val());
        formData.append('cost',$("#edit-cost").val());
        formData.append('id',$("#edit-item-id").val());
        $.ajax({
          url: '/expense-item/update/' + $("#edit-item-id").val(),
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

@endsection
