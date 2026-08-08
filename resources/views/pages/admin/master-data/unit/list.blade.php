@extends('layout.index_admin')
@section("title","Unit List")
@section('breadcrumb')
<ol class="breadcrumb float-sm-right">
    <li class="breadcrumb-item active">Master Data</li>
    <li class="breadcrumb-item active">Unit List</li>
  </ol>
@endsection
@section('content')
<div class="row">
    @can('unit_create')
        <div class=" col-12 d-flex justify-content-end my-3">
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addUnitModal"> <i class="fas fa-plus"></i> Add Unit</button>
        </div>
    @endcan
    <div class="col-md-12">
        <table class="table table-striped" id="unitsTable">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th class="text-center">Nama Unit</th>
                    <th class="text-center">Hari Training</th>
                    <th class="text-center">Jam Training</th>
                    <th class="text-center">PJ Unit</th>
                    <th class="text-center">Tipe Pembayaran</th>
                    <th class="text-center">Tanggal Bergabung</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @php($i=1)
                @php($dayTranslation = [
                    'Monday' => 'Senin',
                    'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu',
                    'Thursday' => 'Kamis',
                    'Friday' => 'Jumat',
                    'Saturday' => 'Sabtu',
                    'Sunday' => 'Minggu'
                ])
                @foreach ($units as $item)
                <tr>
                    <td class="text-center">{{$i++}}</td>
                    <td class="text-center">{{$item->name}}</td>
                    <td class="text-center">{{$dayTranslation[$item->training_day] ?? $item->training_day}}</td>
                    <td class="text-center">{{$item->training_hours_start->format('H:i')}} - {{$item->training_hours_end->format('H:i')}}</td>
                    <td class="text-center">{{$item->pj->name??''}}</td>
                    <td class="text-center">{{$item->paid_fee_type}}</td>
                    <td class="text-center">{{$item->joined_date}}</td>
                    <td class="text-center">
                       @include('partials.button_action',[
                        "permision"=>"unit",
                        "params" => $item->id,
                        "target"=>"unit"])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add Unit Modal -->
<div class="modal fade" id="addUnitModal" tabindex="-1" role="dialog" aria-labelledby="addUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <form action="{{route('unit.store')}}" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addUnitModalLabel">Add Unit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required" for="name">Nama Unit</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" placeholder="Nama Unit" required>
                    @if($errors->has('name'))
                        <span class="text-danger">{{ $errors->first('name') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="training_day">Hari Training</label>
                    <select class="form-control {{ $errors->has('training_day') ? 'is-invalid' : '' }}" name="training_day" id="training_day" required>
                        <option value="">Pilih Hari Training</option>
                        <option value="Monday">Senin</option>
                        <option value="Tuesday">Selasa</option>
                        <option value="Wednesday">Rabu</option>
                        <option value="Thursday">Kamis</option>
                        <option value="Friday">Jumat</option>
                        <option value="Saturday">Sabtu</option>
                        <option value="Sunday">Minggu</option>
                    </select>
                    @if($errors->has('training_day'))
                        <span class="text-danger">{{ $errors->first('training_day') }}</span>
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required" for="training_hours_start">Jam Training Mulai</label>
                            <input class="form-control {{ $errors->has('training_hours_start') ? 'is-invalid' : '' }}" type="time" name="training_hours_start" id="training_hours_start" required>
                            @if($errors->has('training_hours_start'))
                                <span class="text-danger">{{ $errors->first('training_hours_start') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required" for="training_hours_end">Jam Training Selesai</label>
                            <input class="form-control {{ $errors->has('training_hours_end') ? 'is-invalid' : '' }}" type="time" name="training_hours_end" id="training_hours_end" required>
                            @if($errors->has('training_hours_end'))
                                <span class="text-danger">{{ $errors->first('training_hours_end') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="required" for="pj_id">PJ Unit</label>
                    <select class="form-control {{ $errors->has('pj_id') ? 'is-invalid' : '' }}" name="pj_id" id="pj_id" required>
                        <option value="">Pilih PJ Unit</option>
                        @foreach($coaches as $coach)
                            <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('pj_id'))
                        <span class="text-danger">{{ $errors->first('pj_id') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="paid_fee_type">Tipe Pembayaran</label>
                    <input class="form-control {{ $errors->has('paid_fee_type') ? 'is-invalid' : '' }}" type="text" name="paid_fee_type" id="paid_fee_type" placeholder="Tipe Pembayaran" required>
                    @if($errors->has('paid_fee_type'))
                        <span class="text-danger">{{ $errors->first('paid_fee_type') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="paid_periode">Periode Pembayaran</label>
                    <input class="form-control {{ $errors->has('paid_periode') ? 'is-invalid' : '' }}" type="text" name="paid_periode" id="paid_periode" placeholder="Periode Pembayaran" required>
                    @if($errors->has('paid_periode'))
                        <span class="text-danger">{{ $errors->first('paid_periode') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_pic_name">Nama Penanggung Jawab Sekolah</label>
                    <input class="form-control {{ $errors->has('school_pic_name') ? 'is-invalid' : '' }}" type="text" name="school_pic_name" id="school_pic_name" placeholder="Nama PIC" required>
                    @if($errors->has('school_pic_name'))
                        <span class="text-danger">{{ $errors->first('school_pic_name') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_pic_number">No. Telepon PIC</label>
                    <input class="form-control {{ $errors->has('school_pic_number') ? 'is-invalid' : '' }}" type="text" name="school_pic_number" id="school_pic_number" placeholder="No. Telepon" required>
                    @if($errors->has('school_pic_number'))
                        <span class="text-danger">{{ $errors->first('school_pic_number') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_level">Tingkat Sekolah</label>
                    <select class="form-control {{ $errors->has('school_level') ? 'is-invalid' : '' }}" name="school_level" id="school_level" required>
                        <option value="">Pilih Tingkat Sekolah</option>
                        <option value="TK">TK</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA/K">SMA/K</option>
                        <option value="Universitas">Universitas</option>
                        <option value="Umum">Umum</option>
                    </select>
                    @if($errors->has('school_level'))
                        <span class="text-danger">{{ $errors->first('school_level') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_pic_occupation">Jabatan PIC</label>
                    <input class="form-control {{ $errors->has('school_pic_occupation') ? 'is-invalid' : '' }}" type="text" name="school_pic_occupation" id="school_pic_occupation" placeholder="Jabatan" required>
                    @if($errors->has('school_pic_occupation'))
                        <span class="text-danger">{{ $errors->first('school_pic_occupation') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="joined_date">Tanggal Bergabung</label>
                    <input class="form-control {{ $errors->has('joined_date') ? 'is-invalid' : '' }}" type="date" name="joined_date" id="joined_date" required>
                    @if($errors->has('joined_date'))
                        <span class="text-danger">{{ $errors->first('joined_date') }}</span>
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

<!-- Edit Unit Modal -->
<div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog" aria-labelledby="editUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
          <form action="{{route('unit.update',[0])}}" id="editUnitForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="id">
            <div class="modal-header">
                <h5 class="modal-title" id="editUnitModalLabel">Edit Unit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="required" for="name">Nama Unit</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" placeholder="Nama Unit" required>
                    @if($errors->has('name'))
                        <span class="text-danger">{{ $errors->first('name') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="training_day">Hari Training</label>
                    <select class="form-control {{ $errors->has('training_day') ? 'is-invalid' : '' }}" name="training_day" id="training_day" required>
                        <option value="">Pilih Hari Training</option>
                        <option value="Monday">Senin</option>
                        <option value="Tuesday">Selasa</option>
                        <option value="Wednesday">Rabu</option>
                        <option value="Thursday">Kamis</option>
                        <option value="Friday">Jumat</option>
                        <option value="Saturday">Sabtu</option>
                        <option value="Sunday">Minggu</option>
                    </select>
                    @if($errors->has('training_day'))
                        <span class="text-danger">{{ $errors->first('training_day') }}</span>
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required" for="training_hours_start">Jam Training Mulai</label>
                            <input class="form-control {{ $errors->has('training_hours_start') ? 'is-invalid' : '' }}" type="time" name="training_hours_start" id="training_hours_start" required>
                            @if($errors->has('training_hours_start'))
                                <span class="text-danger">{{ $errors->first('training_hours_start') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required" for="training_hours_end">Jam Training Selesai</label>
                            <input class="form-control {{ $errors->has('training_hours_end') ? 'is-invalid' : '' }}" type="time" name="training_hours_end" id="training_hours_end" required>
                            @if($errors->has('training_hours_end'))
                                <span class="text-danger">{{ $errors->first('training_hours_end') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="required" for="pj_id">PJ Unit</label>
                    <select class="form-control {{ $errors->has('pj_id') ? 'is-invalid' : '' }}" name="pj_id" id="pj_id" required>
                        <option value="">Pilih PJ Unit</option>
                        @foreach($coaches as $coach)
                            <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('pj_id'))
                        <span class="text-danger">{{ $errors->first('pj_id') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="paid_fee_type">Tipe Pembayaran</label>
                    <input class="form-control {{ $errors->has('paid_fee_type') ? 'is-invalid' : '' }}" type="text" name="paid_fee_type" id="paid_fee_type" placeholder="Tipe Pembayaran" required>
                    @if($errors->has('paid_fee_type'))
                        <span class="text-danger">{{ $errors->first('paid_fee_type') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="paid_periode">Periode Pembayaran</label>
                    <input class="form-control {{ $errors->has('paid_periode') ? 'is-invalid' : '' }}" type="text" name="paid_periode" id="paid_periode" placeholder="Periode Pembayaran" required>
                    @if($errors->has('paid_periode'))
                        <span class="text-danger">{{ $errors->first('paid_periode') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_pic_name">Nama Penanggung Jawab Sekolah</label>
                    <input class="form-control {{ $errors->has('school_pic_name') ? 'is-invalid' : '' }}" type="text" name="school_pic_name" id="school_pic_name" placeholder="Nama PIC" required>
                    @if($errors->has('school_pic_name'))
                        <span class="text-danger">{{ $errors->first('school_pic_name') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_pic_number">No. Telepon PIC</label>
                    <input class="form-control {{ $errors->has('school_pic_number') ? 'is-invalid' : '' }}" type="text" name="school_pic_number" id="school_pic_number" placeholder="No. Telepon" required>
                    @if($errors->has('school_pic_number'))
                        <span class="text-danger">{{ $errors->first('school_pic_number') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_level">Tingkat Sekolah</label>
                    <select class="form-control {{ $errors->has('school_level') ? 'is-invalid' : '' }}" name="school_level" id="school_level" required>
                        <option value="">Pilih Tingkat Sekolah</option>
                        <option value="TK">TK</option>
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA/K">SMA/K</option>
                        <option value="Universitas">Universitas</option>
                        <option value="Umum">Umum</option>
                    </select>
                    @if($errors->has('school_level'))
                        <span class="text-danger">{{ $errors->first('school_level') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="school_pic_occupation">Jabatan PIC</label>
                    <input class="form-control {{ $errors->has('school_pic_occupation') ? 'is-invalid' : '' }}" type="text" name="school_pic_occupation" id="school_pic_occupation" placeholder="Jabatan" required>
                    @if($errors->has('school_pic_occupation'))
                        <span class="text-danger">{{ $errors->first('school_pic_occupation') }}</span>
                    @endif
                </div>
                <div class="form-group">
                    <label class="required" for="joined_date">Tanggal Bergabung</label>
                    <input class="form-control {{ $errors->has('joined_date') ? 'is-invalid' : '' }}" type="date" name="joined_date" id="joined_date" required>
                    @if($errors->has('joined_date'))
                        <span class="text-danger">{{ $errors->first('joined_date') }}</span>
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

<!-- Detail Unit Modal -->
<div class="modal fade" id="detailUnitModal" tabindex="-1" role="dialog" aria-labelledby="detailUnitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailUnitModalLabel">Detail Unit</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Nama Unit:</strong></label>
                            <p id="detail_name"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Hari Training:</strong></label>
                            <p id="detail_training_day"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Jam Training Mulai:</strong></label>
                            <p id="detail_training_hours_start"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Jam Training Selesai:</strong></label>
                            <p id="detail_training_hours_end"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>PJ Unit:</strong></label>
                            <p id="detail_pj_name"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Tipe Pembayaran:</strong></label>
                            <p id="detail_paid_fee_type"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Periode Pembayaran:</strong></label>
                            <p id="detail_paid_periode"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Tanggal Bergabung:</strong></label>
                            <p id="detail_joined_date"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Nama PIC Sekolah:</strong></label>
                            <p id="detail_school_pic_name"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>No. Telepon PIC:</strong></label>
                            <p id="detail_school_pic_number"></p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Tingkat Sekolah:</strong></label>
                            <p id="detail_school_level"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>Jabatan PIC:</strong></label>
                            <p id="detail_school_pic_occupation"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
      </div>
    </div>
  </div>

@endsection
@section('script')
    <script>
        // Translation mapping
        const dayTranslation = {
            'Monday': 'Senin',
            'Tuesday': 'Selasa',
            'Wednesday': 'Rabu',
            'Thursday': 'Kamis',
            'Friday': 'Jumat',
            'Saturday': 'Sabtu',
            'Sunday': 'Minggu'
        };

        $('#unitsTable').DataTable();
        function editData(e){
            console.log($(e).data('target'))
            $.get($(e).data('target'),function(data){
                id=$(e).data('id')
                $('input[name="name"]',$('#editUnitModal')).val(data.name)
                $('select[name="training_day"]',$('#editUnitModal')).val(data.training_day)
                $('input[name="training_hours_start"]',$('#editUnitModal')).val(data.training_hours_start)
                $('input[name="training_hours_end"]',$('#editUnitModal')).val(data.training_hours_end)
                $('select[name="pj_id"]',$('#editUnitModal')).val(data.pj_id)
                $('input[name="paid_fee_type"]',$('#editUnitModal')).val(data.paid_fee_type)
                $('input[name="paid_periode"]',$('#editUnitModal')).val(data.paid_periode)
                $('input[name="school_pic_name"]',$('#editUnitModal')).val(data.school_pic_name)
                $('input[name="school_pic_number"]',$('#editUnitModal')).val(data.school_pic_number)
                $('select[name="school_level"]',$('#editUnitModal')).val(data.school_level)
                $('input[name="school_pic_occupation"]',$('#editUnitModal')).val(data.school_pic_occupation)
                $('input[name="joined_date"]',$('#editUnitModal')).val(data.joined_date)
                action = $('#editUnitForm').attr('action')
                $('#editUnitForm').attr('action',action.substring(0, (action.length-1))+id)
                $('#editUnitModal').modal('show')
            })
        }

        function showDetail(e){
            $.get($(e).data('target'),function(data){
                $('#detail_name').text(data.name)
                $('#detail_training_day').text(dayTranslation[data.training_day] || data.training_day)
                $('#detail_training_hours_start').text(data.training_hours_start)
                $('#detail_training_hours_end').text(data.training_hours_end)
                $('#detail_pj_name').text(data.pj?.name || '-')
                $('#detail_paid_fee_type').text(data.paid_fee_type)
                $('#detail_paid_periode').text(data.paid_periode)
                $('#detail_joined_date').text(data.joined_date)
                $('#detail_school_pic_name').text(data.school_pic_name)
                $('#detail_school_pic_number').text(data.school_pic_number)
                $('#detail_school_level').text(data.school_level)
                $('#detail_school_pic_occupation').text(data.school_pic_occupation)
                $('#detailUnitModal').modal('show')
            })
        }
    </script>
@endsection
