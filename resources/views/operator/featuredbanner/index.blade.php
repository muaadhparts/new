@extends('layouts.operator') 

@section('content')  
          <input type="hidden" id="headerdata" value="BANNER">
          <div class="content-area">
            <div class="mr-breadcrumb">
              <div class="row">
                <div class="col-lg-12">
                    <h4 class="heading">Featured Banners</h4>
                    <ul class="links">
                      <li>
                        <a href="{{ route('operator.dashboard') }}">Dashboard </a>
                      </li>
                      <li>
                        <a href="javascript:;">Home Page Settings</a>
                      </li>
                      <li>
                        <a href="{{ route('operator-featuredbanner-index') }}">Featured Banners</a>
                      </li>
                    </ul>
                </div>
              </div>
            </div>
            <div class="catalogItem-area">
              <div class="row">
                <div class="col-lg-12">
                  <div class="mr-table allproduct">

                        @include('includes.admin.form-success')  

                    <div class="table-responsive">
                        <table id="muaadhtable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                          <thead>
                            <tr>
                                <th>Featured Image</th>
                                <th>Actions</th>
                            </tr>
                          </thead>
                        </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

{{-- ADD / EDIT MODAL --}}

                    <div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
                    
                    
                    <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="submit-loader">
                            <img  src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
                        </div>
                      <div class="modal-header">
                      <h5 class="modal-title"></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                      </button>
                      </div>
                      <div class="modal-body">

                      </div>
                      <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                    </div>
                    </div>
</div>

{{-- ADD / EDIT MODAL ENDS --}}


{{-- DELETE MODAL --}}

<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">

  <div class="modal-header d-block text-center">
    <h4 class="modal-title d-inline-block">Confirm Delete</h4>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
        
      </button>
  </div>

      <!-- Modal body -->
      <div class="modal-body">
            <p class="text-center">You are about to delete this Featured Banner.</p>
            <p class="text-center">Do you want to proceed?</p>
      </div>

      <!-- Modal footer -->
      <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <a class="btn btn-danger btn-ok">Delete</a>
      </div>

    </div>
  </div>
</div>

{{-- DELETE MODAL ENDS --}}

@endsection    

@section('scripts')


{{-- DATA TABLE --}}

    <script type="text/javascript">

(function($) {
		"use strict";

    var table = $('#muaadhtable').DataTable({
         ordering: false,
               processing: true,
               serverSide: true,
               ajax: '{{ route('operator-featuredbanner-datatables') }}',
               columns: [
                        { data: 'photo', name: 'photo' , searchable: false, orderable: false},
                  { data: 'action', searchable: false, orderable: false }

                     ],
                language : {
                  processing: '<img src="{{asset('assets/images/'.$gs->admin_loader)}}">'
                }
            });

        $(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">'+
          '<a class="add-btn" data-href="{{route('operator-featuredbanner-create')}}" id="add-data" data-bs-toggle="modal" data-bs-target="#modal1">'+
          '<i class="fas fa-plus"></i> Add New Banner'+
          '</a>'+
          '</div>');
      });                     
                  
})(jQuery);

</script>

@endsection   