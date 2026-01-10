@extends('layout.index_admin')
@section('title', 'Absensi Pelatih')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        {{-- <li class="breadcrumb-item"><a href="#">User Management</a></li> --}}
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Absensi Pelatih</li>
    </ol>
@endsection
@section('content')
    <div class="row">
        @can('permission_create')
            <div class=" col-12 d-flex justify-content-end mb-3">
                <a class="btn btn-primary btn-sm mr-2" href="{{ route('attendance.coach.sync') }}"> <i
                        class="fas fa-plus"></i> Sync Data</a>
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addPermissionModal"> <i
                        class="fas fa-plus"></i> Tambah Absensi</button>
            </div>
        @endcan
        <div class="col-md-12 table-responsive">
            <table class="table table-striped" id="attendancesTable">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Nama Unit</th>
                        <th class="text-center">Tanggal Latihan</th>
                        <th class="text-center">Status Latihan</th>
                        <th class="text-center">Jumlah Anggota Baru</th>
                        <th class="text-center">Jumlah Anggota Lama</th>
                        <th class="text-center">Pembuat Laporan</th>
                        <th class="text-center">Total Pelatih</th>
                        <th class="text-center">Gambar Latihan</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php($i = 1)
                    @foreach ($attendances as $item)
                        <tr>
                            <td class="text-center">{{ $i++ }}</td>
                            <td class="text-left">{{ $item->unit->name }}</td>
                            <td class="text-center">{{ date('Y-m-d', strtotime($item->attendance_date)) }}</td>
                            <td class="text-center">
                                <span style="font-size:100%"
                                    class="badge {{ App\Models\Attendance::mapAttendanceStatusToClass($item->attendance_status) }}">{{ App\Models\Attendance::mapAttendanceStatus($item->attendance_status) }}</span>
                            </td>
                            <td class="text-center">{{ $item->new_member_cnt }}</td>
                            <td class="text-center">{{ $item->old_member_cnt }}</td>
                            <td class="text-center">{{ $item->reportMaker->name??'' }}</td>
                            <td class="text-center">{{ $item->attendanceDetails->count() }}</td>
                            <td class="text-center">
                                @if ($item->attendance_image)
                                    @php($images = explode(',', $item->attendance_image))
                                    @foreach ($images as $image)
                                        <a href="{{ $image }}" target="_blank">View Image</a><br>
                                    @endforeach
                                @else
                                    No Image
                                @endif
                            <td class="text-center">
                                @include('partials.button_action', [
                                    'permision' => 'attendance-coach',
                                    'params' => $item->id,
                                    'target' => 'attendance.coach',
                                ])
                                <a href="{{route('attendance.coach.resend.notif', $item->id)}}"><i class="fab fa-telegram"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="addPermissionModal" tabindex="-1" role="dialog" aria-labelledby="addPermissionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('attendance.coach.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPermissionModalLabel">Tambah Absensi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="required" for="unit_id">Nama Unit</label>
                            <select class="form-control" name="unit_id" id="unit_id">
                                <option value="">Pilih Nama Unit</option>
                                @foreach (App\Models\Unit::all() as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('unit_id'))
                                <span class="text-danger">{{ $errors->first('unit_id') }}</span>
                            @endif
                            {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                        </div>
                        <div class="form-group">
                            <label class="required" for="attendance_date">Tanggal Latihan</label>
                            <input class="form-control" type="date" name="attendance_date" id="attendance_date">
                            @if ($errors->has('attendance_date'))
                                <span class="text-danger">{{ $errors->first('attendance_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label class="required" for="unit_id">Status Latihan</label>
                            <select class="form-control" name="attendance_status" id="attendance_status">
                                <option value="">Pilih Status Latihan</option>
                                @foreach (App\Models\Attendance::$attendanceStatusMap as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('attendance_status'))
                                <span class="text-danger">{{ $errors->first('attendance_status') }}</span>
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

    <div class="modal fade" id="editPermissionModal" tabindex="-1" role="dialog"
        aria-labelledby="editPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('attendance.coach.update', [0]) }}" id="editPermissionForm" method="POST">
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
                            <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                                name="name" id="name" placeholder="Permission Name" required>
                            @if ($errors->has('name'))
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
        $('#attendancesTable').DataTable();

        function editData(e) {
            console.log($(e).data('target'))
            $.get($(e).data('target'), function(data) {
                id = $(e).data('id')
                $('input[name="name"]', $('#editPermissionModal')).val(data.name)
                action = $('#editPermissionForm').attr('action')
                $('#editPermissionForm').attr('action', action.substring(0, (action.length - 1)) + id)
                $('#editPermissionModal').modal('show')
            })
        }
    </script>
@endsection
