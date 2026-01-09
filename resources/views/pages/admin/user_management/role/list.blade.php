@extends('layout.index_admin')
@section("title","Role List")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    {{-- <li class="breadcrumb-item"><a href="#">User Management</a></li> --}}
    <li class="breadcrumb-item active">User Management</li>
    <li class="breadcrumb-item active">Role List</li>
  </ol>
@endsection
@section('content')
<div class="row">
    @can('role_create')
    <div class=" col-12 d-flex justify-content-end mb-3">
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addRoleModal"> <i class="fas fa-plus"></i> Add Role</button>
    </div>
    @endcan
    <div class="col-md-12">
        <table class="table table-striped" id="rolesTable">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">Role Name</th>
                    <th class="text-center">Permisions</th>
                    <th class="text-center">Created Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @php($i=1)
                @foreach ($roles as $item)
                <tr>
                    <td class="text-center">{{$i++}}</td>
                    <td class="text-left">{{$item->name}}</td>
                    <td class="text-left">
                        @foreach($item->permissions as $itm)
                        @php($explode = explode("_",$itm->name))
                            <span class="badge badge-{{\App\Http\Controllers\Controller::label_color($explode[1])}}">{{ $itm->name }}</span>
                        @endforeach
                    </td>
                    <td class="text-center">{{$item->created_at}}</td>
                    <td class="text-center">
                        @include('partials.button_action',["permision"=>"role","src"=>"roles","params"=>$item->id])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
          <form action="{{route('roles.store')}}" method="POST">
              @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="addRoleModalLabel">Add Role</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
                <div class="form-group">
                    <label class="required" for="name">Name</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" placeholder="Role Name" required>
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
        $('#rolesTable').DataTable();
    </script>
@endsection