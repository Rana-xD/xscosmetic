<!-- Page Content -->
@extends('layouts/application')
@section('head')
<meta name="csrf_token" content="{{ csrf_token() }}" />
@endsection
@section('content')
<div class="container">
   <div class="row" style="margin-top:100px;">
      <table  class="table table-striped table-bordered product-income-table" cellspacing="0" width="100%">
          <thead>
              <tr>
                  <th class="hidden-xs">No</th>
                  <th>Name</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th>Cost</th>
                  <th>Profit</th>
              </tr>
          </thead>

          <tbody>
              @foreach ( $products as $product)
              
              <tr class="unit-data">
                 <td class="hidden-xs productcode">{{ $loop->index + 1 }}</td>
                 <td class="unit-name">{{ $product->product_name }}</td>
                 <td>{{ $product->quantity }}</td>
                 <td>$ {{ $product->total_price }}</td>
                 <td>$ {{ $product->total_cost  }}</td>
                 <td>$ {{ $product->profit}}</td>
              </tr>
              @endforeach
          </tbody>
      </table>
   </div>
</div>
<!-- /.container -->


<script src="js/jquery-ui.min.js"></script>

<script type="text/javascript">
  $(document).ready(function() {
    $('.product-income-table').DataTable( {
        scrollY: 300,
        paging: false
    } );
      
  });
</script>
<!-- Add Modal -->
<div class="modal fade" id="Addunit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <form id="addUnit" action="/unit/add" method="POST" enctype="multipart/form-data">
        @csrf
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Add Unit</h4>
      </div>
      <div class="modal-body">
           <div class="form-group">
             <label for="UnitName">Unit Name</label>
             <input type="text" name="name" maxlength="100" Required class="form-control" id="UnitName" placeholder="Unit Name">
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
<div class="modal fade" id="Updateunit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
     <div class="modal-content">
       <form id="editUnit" action="/unit/edit" method="POST" enctype="multipart/form-data">
         @csrf
       <div class="modal-header">
         <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel">Edit Unit</h4>
       </div>
       <div class="modal-body">
            <div class="form-group">
              <label for="UnitName">Unit Name</label>
              <input type="text" name="name" maxlength="100" Required class="form-control" id="UnitNameEdit" placeholder="Unit Name">
              <input type="hidden"  name="unit-id" id="UnitId">
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




@endsection