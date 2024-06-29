<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <table id="Table" class="table table-striped table-bordered" cellspacing="0" width="100%">
          <thead>
              <tr>
                  <th class="hidden-xs">No</th>
                  <th>User Name</th>
                  <th>Role</th>
                  <th>Action</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $users as $user)
              
              <tr class="user-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="user-name">{{ $user->username }}</td>
                 <td class="user-role">{{ $user->role }}</td>
                 <td>
                   @if (Auth::user()->username != $user->username)
                    <div class="btn-group">
                      <a class="btn btn-default delete-btn delete-user" data-id="{{ $user->id }}" ><i class="fa fa-times" data-id="{{ $user->id }}"></i></a>
                     </div>
                    @endif
                     <div class="btn-group">
                      <a class="btn btn-default edit-user" data-id="{{ $user->id }}" ><i class="fa fa-pencil-square-o" data-id="{{ $user->id }}"></i></a>
                     </div>
                  </td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
   <!-- Button trigger modal -->
   <button type="button" class="btn btn-add btn-lg" data-toggle="modal" data-target="#Adduser">Add User</button>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
      
      $("#Adduser").on("submit",(e)=>{
        e.preventDefault();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('username',$("#username").val());
        formData.append('password',$("#user-password").val());
        formData.append('role',$("#role").val());
        $.ajax({
          url: '/user/add',
          type: "POST", 
          data: formData,
          contentType: false,
          processData: false,
          success: function(res){
            $('#username').val('');
            $('#password').val('');
            $("#role").val('ADMIN');
            location.reload();
          },
          error: function(err){
            console.log(err);
          } 
        });
      });

      $(".delete-user").on("click",(e)=>{
        swal({   title: 'Are you sure?',
          text: 'Delete Product',
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
              url: '/user/delete',
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

      $(".edit-user").on("click",(e)=>{
        e.preventDefault();
        let self = e.target,
            id = $(self).attr('data-id'),
            username = $(self).parents('.user-data').find('.user-name').text(),
            role = $(self).parents('.user-data').find('.user-role').text();

            $('#user-id-edit').val(id);
            $('#username-edit').val(username);
            $('#role-edit').val(role);
            $('#UpdateUser').modal('show');
      });

      $('#editUser').on("submit",(e) => {
        e.preventDefault();
        e.stopPropagation();
        let formData = new FormData();
        formData.append("_token",$('meta[name="csrf_token"]').attr('content'));
        formData.append('id',$("#user-id-edit").val());
        formData.append('username',$("#username-edit").val());
        formData.append('password',$("#user-password-edit").val() === '' || $("#user-password-edit").val() === undefined ? '' : $("#user-password-edit").val());
        formData.append('role',$("#role-edit").val());
     
        $.ajax({
          url: '/user/update',
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
<div class="modal fade" id="Adduser" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="Adduser" action="/unit/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Add User</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="UnitName">User Name</label>
             <input type="text" name="username" maxlength="100" Required class="form-control" id="username" placeholder="User Name">
           </div>
           <div class="form-group">
             <label for="UnitName">Password</label>
             <input type="text" name="user-password" maxlength="100" Required class="form-control" id="user-password" placeholder="Password">
           </div>
           <div class="form-group">
             <label for="role">Role</label>
              <select class="form-control" id="role" name="filtertype">
                  <option value="ADMIN" selected>Admin</option>
                  <option value="MANAGER">Manager</option>
                  <option value="STAFF">Staff</option>
              </select>
           </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-add">Submit</button>
      </div>
    </form>
    </div>
 </div>
</div>
<!-- /.Modal -->

<!-- Edit Modal -->
<div class="modal fade" id="UpdateUser" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editUser" action="/user/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">Edit User</h4>
       </div>
       <div class="modal-body">
          <div class="form-group">
             <label for="UnitName">User Name</label>
             <input type="text" name="username-edit" maxlength="100" Required class="form-control" id="username-edit" placeholder="User Name">
           </div>
           <div class="form-group">
             <label for="UnitName">Password</label>
             <input type="text" name="user-password-edit" maxlength="100" class="form-control" id="user-password-edit" placeholder="Password">
           </div>
           <div class="form-group">
             <label for="role">Role</label>
              <select class="form-control" id="role-edit" name="filtertype">
                  <option value="ADMIN" selected>Admin</option>
                  <option value="MANAGER">Manager</option>
                  <option value="STAFF">Staff</option>
              </select>
           </div>
           <input type="hidden"  name="user-id-edit" id="user-id-edit">
       </div>
       <div class="modal-footer">
         <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
         <button type="submit" class="btn btn-add">Submit</button>
       </div>
     </form>
     </div>
  </div>
 </div>
 <!-- /.Modal -->




@endsection