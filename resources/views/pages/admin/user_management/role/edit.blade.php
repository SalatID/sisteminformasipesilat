@extends('layout.index_admin')
@section("title","Edit Role")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    {{-- <li class="breadcrumb-item"><a href="#">User Management</a></li> --}}
    <li class="breadcrumb-item active">User Management</li>
    <li class="breadcrumb-item active">Edit Role</li>
  </ol>
@endsection
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card-body">
            <form method="POST" action="{{ route("roles.update", [\Illuminate\Support\Facades\Crypt::encryptString($role->id)]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="form-group">
                    <label class="required" for="name">Name</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" value="{{ old('name', $role->name) }}" required>
                    @if($errors->has('name'))
                        <span class="text-danger">{{ $errors->first('name') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label class="required" for="permissions">Permission</label>
                    {{-- <div style="padding-bottom: 4px">
                        <span class="btn btn-info btn-xs select-all" style="border-radius: 0">{{ trans('global.select_all') }}</span>
                        <span class="btn btn-info btn-xs deselect-all" style="border-radius: 0">{{ trans('global.deselect_all') }}</span>
                    </div> --}}
                    <select class="form-control select2 {{ $errors->has('permissions') ? 'is-invalid' : '' }}" name="permissions[]" id="permissions" multiple required>
                        @foreach($permissions as $id => $permission)
                            <option value="{{ $id }}" {{ (in_array($id, old('permissions', [])) || $role->permissions->contains($id)) ? 'selected' : '' }}>{{ $permission }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('permissions'))
                        <span class="text-danger">{{ $errors->first('permissions') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.permissions_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <button class="btn btn-success" type="submit">
                        Save
                    </button>
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