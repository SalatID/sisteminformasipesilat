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
        <div class="col-md-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form action="{{ route('attendance.coach.update', $attendance->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAttendanceModalLabel">Edit Absensi</h5>
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
                                        <option value="{{ $unit->id }}"
                                            {{ $attendance->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('unit_id'))
                                    <span class="text-danger">{{ $errors->first('unit_id') }}</span>
                                @endif
                                {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                            </div>
                            <div class="form-group">
                                <label class="required" for="attendance_date">Tanggal Latihan</label>
                                <input class="form-control" type="date" name="attendance_date" id="attendance_date"
                                    required value="{{ $attendance->attendance_date->format('Y-m-d') }}">
                                @if ($errors->has('attendance_date'))
                                    <span class="text-danger">{{ $errors->first('attendance_date') }}</span>
                                @endif
                            </div>
                            <div class="form-group">
                                <label class="required" for="attendance_status">Status Latihan</label>
                                <select class="form-control" name="attendance_status" id="attendance_status" required>
                                    <option value="">Pilih Status Latihan</option>
                                    @foreach (App\Models\Attendance::$attendanceStatusMap as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ $attendance->attendance_status == $key ? 'selected' : '' }}>
                                            {{ $value }}</option>
                                    @endforeach
                                </select>
                                @if ($errors->has('attendance_status'))
                                    <span class="text-danger">{{ $errors->first('attendance_status') }}</span>
                                @endif
                                {{-- <span class="help-block">{{ trans('cruds.role.fields.title_helper') }}</span> --}}
                            </div>

                            <div class="form-group" id="reasonFormGroup"
                                style="display: {{ $attendance->attendance_status != 'training' ? 'block' : 'none' }};">
                                <label for="reason">Alasan</label>
                                <input class="form-control" type="text" name="reason" id="reason"
                                    placeholder="Masukkan alasan">
                                @if ($errors->has('reason'))
                                    <span class="text-danger">{{ $errors->first('reason') }}</span>
                                @endif
                            </div>


                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('attendance.coach.index') }}" class="btn btn-secondary"
                                data-dismiss="modal">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
