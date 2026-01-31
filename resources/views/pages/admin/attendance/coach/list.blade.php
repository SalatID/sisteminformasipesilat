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

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('attendance.coach.index') }}">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Tanggal Latihan</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                            value="{{ request('start_date') }}" placeholder="Pilih tanggal mulai">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">&nbsp</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                            value="{{ request('end_date') }}" placeholder="Pilih tanggal akhir">
                                    </div>
                                </div>

                            </div>
                            
                            <!-- Status Latihan -->
                            <div class="col-md-4 mb-3">
                                <label for="attendance_status_filter" class="form-label">Status Latihan</label>
                                <select class="form-control" id="attendance_status_filter" name="attendance_status">
                                    <option value="">Semua Status</option>
                                    @foreach (App\Models\Attendance::$attendanceStatusMap as $key => $value)
                                        <option value="{{ $key }}" {{ request('attendance_status') == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Nama Unit -->
                            <div class="col-md-4 mb-3">
                                <label for="unit_id_filter" class="form-label">Nama Unit</label>
                                <select class="form-control" id="unit_id_filter" name="unit_id">
                                    <option value="">Semua Unit</option>
                                    @foreach (App\Models\Unit::all() as $unit)
                                        <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="col-md-8 mb-3 d-flex align-items-end">
                                <button type="submit" name="filter" value="filter" class="btn btn-sm btn-primary mr-2">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                                <button type="submit" name="export" value="json" class="btn btn-sm btn-success mr-2">
                                    <i class="fas fa-file-export"></i> Export JSON
                                </button>
                                <a href="{{ route('attendance.coach.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @can('attendance-coach_create')
            <div class=" col-12 d-flex justify-content-end mb-3">
                <a class="btn btn-primary btn-sm mr-2" href="{{ route('attendance.coach.sync') }}"> <i
                        class="fas fa-plus"></i> Sync Data</a>
                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addAttendanceModal"> <i
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
                                    class="badge {{ App\Models\Attendance::mapAttendanceStatusToClass($item->attendance_status) }}">
                                    {{ App\Models\Attendance::mapAttendanceStatus($item->attendance_status) }}
                                </span>
                                @if($item->attendance_status != 'training' && $item->reason)
                                        <br><small>({{ $item->reason }})</small>
                                @endif
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
                                    'src' => 'attendance.coach',
                                ])
                                @can('attendance-coach_notify')
                                <a href="{{route('attendance.coach.resend.notif', $item->id)}}"><i class="fab fa-telegram"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="addAttendanceModal" tabindex="-1" role="dialog" aria-labelledby="addAttendanceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('attendance.coach.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAttendanceModalLabel">Tambah Absensi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="required" for="unit_id">Nama Unit</label>
                            <select class="form-control" name="unit_id" id="unit_id" required>
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
                            <input class="form-control" type="date" name="attendance_date" id="attendance_date" required>
                            @if ($errors->has('attendance_date'))
                                <span class="text-danger">{{ $errors->first('attendance_date') }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label class="required" for="attendance_status">Status Latihan</label>
                            <select class="form-control" name="attendance_status" id="attendance_status" required>
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

                        <div class="form-group" id="reasonFormGroup" style="display: none;">
                            <label for="reason">Alasan</label>
                            <input class="form-control" type="text" name="reason" id="reason" placeholder="Masukkan alasan">
                            @if ($errors->has('reason'))
                                <span class="text-danger">{{ $errors->first('reason') }}</span>
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
        $('#attendancesTable').DataTable({
            pageLength: 25
        });

        // Show/hide reason field based on attendance_status
        $('#attendance_status').change(function() {
            var selectedValue = $(this).val();
            if (selectedValue && selectedValue != 'training') {
                $('#reasonFormGroup').show();
            } else {
                $('#reasonFormGroup').hide();
                $('#reason').val(''); // Clear the reason field when hiding
            }
        });

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
