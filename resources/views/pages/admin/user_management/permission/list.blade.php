@extends('layout.index_admin')
@section("title","Permission List")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    {{-- <li class="breadcrumb-item"><a href="#">User Management</a></li> --}}
    <li class="breadcrumb-item active">User Management</li>
    <li class="breadcrumb-item active">Permission List</li>
  </ol>
@endsection
@section('content')
<div class="row">
    @can('permission_create')
    <div class=" col-12 d-flex justify-content-end mb-3">
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addPermissionModal"> <i class="fas fa-plus"></i> Add Permission</button>
    </div>
    @endcan
    <div class="col-md-12">
        <table class="table table-striped" id="permissionsTable">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">Permission Name</th>
                    <th class="text-center">Created Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @php($i=1)
                @foreach ($permissions as $item)
                <tr>
                    <td class="text-center">{{$i++}}</td>
                    <td class="text-left">{{$item->name}}</td>
                    <td class="text-center">{{$item->created_at}}</td>
                    <td class="text-center">
                        @include('partials.button_action',["permision"=>"permission","params"=>$item->id,"target"=>"permissions"])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="addPermissionModal" tabindex="-1" role="dialog" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
          <form action="{{route('permissions.store')}}" method="POST">
              @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="addPermissionModalLabel">Add Permission</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="all">
                    <label class="form-check-label">All</label>
                </div>
                <div class="form-group">
                    <label class="required" for="name">Name</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" placeholder="Permission Name" required>
                    @if($errors->has('name'))
                        <span class="text-danger">{{ $errors->first('name') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editPermissionModal" tabindex="-1" role="dialog" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
          <form action="{{route('permissions.update',[0])}}" id="editPermissionForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="id">
            <div class="modal-header">
                <h5 class="modal-title" id="editPermissionModalLabel">Edit Permission</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required" for="name">Name</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" placeholder="Permission Name" required>
                    @if($errors->has('name'))
                        <span class="text-danger">{{ $errors->first('name') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
      </div>
    </div>
  </div>
@endsection
@section('script')
    <script>
        $('#permissionsTable').DataTable();
        function editData(e){
            console.log($(e).data('target'))
            $.get($(e).data('target'),function(data){
                id=$(e).data('id')
                $('input[name="name"]',$('#editPermissionModal')).val(data.name)
                action = $('#editPermissionForm').attr('action')
                $('#editPermissionForm').attr('action',action.substring(0, (action.length-1))+id)
                $('#editPermissionModal').modal('show')
            })
        }
    </script>
@endsection