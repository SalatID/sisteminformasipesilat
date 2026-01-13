@extends('layout.index_admin')
@section("title","User List")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    {{-- <li class="breadcrumb-item"><a href="#">User Management</a></li> --}}
    <li class="breadcrumb-item active">User Management</li>
    <li class="breadcrumb-item active">Users List</li>
  </ol>
@endsection
@section('content')
<div class="row">
    @can('user_create')
        <div class=" col-12 d-flex justify-content-end my-3">
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUserModal"> <i class="fas fa-plus"></i> Add User</button>
        </div>
    @endcan
    <div class="col-md-12">
        <table class="table table-striped" id="usersTable">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">Full Name</th>
                    <th class="text-center">Email</th>
                    <th class="text-center">Role</th>
                    <th class="text-center">Nama Pelatih</th>
                    <th class="text-center">Email Verified Date</th>
                    <th class="text-center">Created Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @php($i=1)
                @foreach ($users as $item)
                <tr>
                    <td class="text-center">{{$i++}}</td>
                    <td class="text-center">{{$item->fullname}}</td>
                    <td class="text-center">{{$item->email}}</td>
                    <td class="text-center">{{$item->role}}</td>
                    <td class="text-center">{{$item->coach->name??''}}</td>
                    <td class="text-center">{{$item->email_verified_at}}</td>
                    <td class="text-center">{{$item->created_at}}</td>
                    <td class="text-center">
                       @include('partials.button_action',[
                        "permision"=>"user",
                        "params" => $item->id,
                        "target"=>"users"])
                       @can("user_activation_link")
                            @if ( $item->email_verified_at==null)
                                <a href="{{route('users.resend.activation.link',[\Illuminate\Support\Facades\Crypt::encryptString($item->id??'')])}}"><i class="fas fa-envelope text-secondary"></i></a>
                            @endif
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
          <form action="{{route('users.store')}}" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required" for="fullname">Fullname</label>
                    <input class="form-control {{ $errors->has('fullname') ? 'is-invalid' : '' }}" type="text" name="fullname" id="fullname" placeholder="Fullname" required>
                    @if($errors->has('fullname'))
                        <span class="text-danger">{{ $errors->first('fullname') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label class="required" for="email">Email</label>
                    <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" id="email" placeholder="Email" required>
                    @if($errors->has('email'))
                        <span class="text-danger">{{ $errors->first('email') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label class="required" for="role">Role</label>
                    <select class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}" name="role" id="role" required>
                        <option value="">Choose Role</option>
                        @foreach($roles as  $item)
                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('role'))
                        <span class="text-danger">{{ $errors->first('role') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label for="coach_id">Coach</label>
                    <select class="form-control {{ $errors->has('coach_id') ? 'is-invalid' : '' }}" name="coach_id" id="coach_id">
                        <option value="">Choose Coach</option>
                        @foreach(App\Models\Coach::orderBy('name')->get() as $coach)
                            <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('coach_id'))
                        <span class="text-danger">{{ $errors->first('coach_id') }}</span>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                @include('partials.button_save')
            </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
          <form action="{{route('users.update',[0])}}" id="editUserForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="id">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required" for="fullname">Fullname</label>
                    <input class="form-control {{ $errors->has('fullname') ? 'is-invalid' : '' }}" type="text" name="fullname" id="fullname" placeholder="Fullname" required>
                    @if($errors->has('fullname'))
                        <span class="text-danger">{{ $errors->first('fullname') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label class="required" for="email">Email</label>
                    <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email" name="email" id="email" placeholder="Email" required>
                    @if($errors->has('email'))
                        <span class="text-danger">{{ $errors->first('email') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label class="required" for="role">Role</label>
                    <select class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}" name="role" id="role" required>
                        <option value="">Choose Role</option>
                        @foreach($roles as  $item)
                            <option value="{{ $item->name }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('role'))
                        <span class="text-danger">{{ $errors->first('role') }}</span>
                    @endif
                    {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                </div>
                <div class="form-group">
                    <label for="coach_id">Coach</label>
                    <select class="form-control {{ $errors->has('coach_id') ? 'is-invalid' : '' }}" name="coach_id" id="coach_id">
                        <option value="">Choose Coach</option>
                        @foreach(App\Models\Coach::orderBy('name')->get() as $coach)
                            <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('coach_id'))
                        <span class="text-danger">{{ $errors->first('coach_id') }}</span>
                    @endif
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
        $('#usersTable').DataTable();
        function editData(e){
            console.log($(e).data('target'))
            $.get($(e).data('target'),function(data){
                id=$(e).data('id')
                $('input[name="fullname"]',$('#editUserModal')).val(data.fullname)
                $('input[name="email"]',$('#editUserModal')).val(data.email)
                $('select[name="role"]',$('#editUserModal')).val(data.role)
                action = $('#editUserForm').attr('action')
                $('#editUserForm').attr('action',action.substring(0, (action.length-1))+id)
                $('select[name="coach_id"]',$('#editUserModal')).val(data.coach_id)
                $('#editUserModal').modal('show')
            })
        }
    </script>
@endsection