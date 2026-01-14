@extends('layout.index_admin')
@section('title', 'Tanda Terima Kontribusi Unit')
@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item active">Manajemen Absensi</li>
        <li class="breadcrumb-item active">Tanda Terima Kontribusi</li>
    </ol>
@endsection
@section('content')

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-sm">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="{{ route('receipt.contribution.unit.index') }}">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="unit_id" class="form-label">Unit</label>
                                <select class="form-control" id="unit_id" name="unit_id" required>
                                    <option value="">Pilih Unit</option>
                                    @foreach ($units as $unitItem)
                                        <option value="{{ $unitItem->id }}" {{ request('unit_id') == $unitItem->id ? 'selected' : '' }}>
                                            {{ $unitItem->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="month" class="form-label">Bulan</label>
                                        <select class="form-control" id="month" name="month">
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                                    {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="year" class="form-label">Tahun</label>
                                        <select class="form-control" id="year" name="year">
                                            @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                                    {{ $y }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>

                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="contribution_amount" class="form-label">Uang Kontribusi</label>
                                <input type="number" class="form-control" id="contribution_amount" name="contribution_amount" value="{{ request('contribution_amount') }}">
                            </div>
                            <!-- Filter Buttons -->
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary mr-2">
                                    <i class="fas fa-search"></i> Lihat
                                </button>
                                <a href="{{ route('receipt.contribution.unit.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($unitData)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" id="receiptContainer">
                    <!-- Header -->
                    <div class="mb-4">
                        <h4><strong>Bulan:</strong> {{ \Carbon\Carbon::create()->month($month)->locale('id')->translatedFormat('F') }} {{ $year }}</h4>
                        <h4><strong>Unit:</strong> {{ $unit->name }}</h4>
                    </div>

                    <!-- Attendance Table -->
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>Absensi</strong></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th width="50">No</th>
                                        <th>Nama Pelatih</th>
                                        <th width="80">TS</th>
                                        <th width="150">Week 1</th>
                                        <th width="150">Week 2</th>
                                        <th width="150">Week 3</th>
                                        <th width="150">Week 4</th>
                                        <th width="150">Week 5</th>
                                        <th width="80">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($i = 1)
                                    @php($weekTotals = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0])
                                    @foreach ($coachAttendance as $data)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td>{{ $data['coach']->name }}</td>
                                            <td class="text-center">{{ $data['coach']->ts->alias ?? '-' }}</td>
                                            @foreach ($data['weeks'] as $weekNum => $weekData)
                                                <td class="text-center">
                                                    @if(count($weekData) > 0)
                                                        @foreach ($weekData as $attendanceData)
                                                            @if($attendanceData['status'] != 'training')
                                                                {{ $attendanceData['date'] }}<br>
                                                                <span class="badge {{ App\Models\Attendance::mapAttendanceStatusToClass($attendanceData['status']) }}" style="font-size: 90%;">
                                                                    {{ App\Models\Attendance::mapAttendanceStatus($attendanceData['status']) }}
                                                                </span>
                                                                @if($attendanceData['reason'])
                                                                    <br><small>({{ $attendanceData['reason'] }})</small>
                                                                @endif
                                                            @else
                                                                {{ $attendanceData['date'] }}
                                                                @php($weekTotals[$weekNum]++)
                                                            @endif
                                                            @if (!$loop->last)
                                                                <hr style="margin: 5px 0;">
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-center"><strong>{{ $data['total_attendance'] }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr class="text-center">
                                        <th colspan="3" class="text-right">Total per Week:</th>
                                        @foreach ([1, 2, 3, 4, 5] as $weekNum)
                                            <th>{{ $weekTotals[$weekNum] }}</th>
                                        @endforeach
                                        <th>{{ $totalAttendance }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Contribution Table -->
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>Kontribusi</strong></h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr class="text-center">
                                        <th width="50">No</th>
                                        <th>Nama Pelatih</th>
                                        <th width="80">TS</th>
                                        <th width="100">Kehadiran</th>
                                        <th width="100">Pengali</th>
                                        <th width="80">PJ</th>
                                        <th width="120">Nilai Akhir</th>
                                        <th width="150">Nominal Pertemuan</th>
                                        <th width="150">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php($i = 1)
                                    @foreach ($coachAttendance as $data)
                                        <tr>
                                            <td class="text-center">{{ $i++ }}</td>
                                            <td>{{ $data['coach']->name }}</td>
                                            <td class="text-center">{{ $data['coach']->ts->alias ?? '-' }}</td>
                                            <td class="text-center">{{ $data['total_attendance'] }}</td>
                                            <td class="text-center">{{ $data['multiplier'] }}</td>
                                            <td class="text-center">{{ $data['is_pj']=="0" ? '' : $data['is_pj'] }}</td>
                                            <td class="text-center">{{ number_format($data['final_value'], 0) }}</td>
                                            <td class="text-right">Rp {{ number_format($nominalPerMeeting, 0, ',', '.') }}</td>
                                            <td class="text-right">Rp {{ number_format($data['total_amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="thead-light">
                                    <tr>
                                        <th colspan="3" class="text-right">Total:</th>
                                        <th class="text-center">{{ $totalAttendance }}</th>
                                        <th class="text-center"></th>
                                        <th class="text-center"></th>
                                        <th class="text-center">{{ number_format($totalFinalValue, 0) }}</th>
                                        <th></th>
                                        <th class="text-right">
                                            <strong>Rp {{ number_format($totalContribution, 0, ',', '.') }}</strong>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="row mb-3">
                        <div class="col-12 text-right">
                            <form action="{{ route('receipt.contribution.unit.save') }}" method="POST" id="saveContributionForm">
                                @csrf
                                <input type="hidden" name="unit_id" value="{{ $unit->id }}">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="contribution_amount" value="{{ $contributionAmount }}">
                                <input type="hidden" name="pj_share" value="{{ $pjShare }}">
                                <input type="hidden" name="kas_share" value="{{ $kasShare }}">
                                <input type="hidden" name="saving_share" value="{{ $savingsShare }}">
                                <input type="hidden" name="difference" value="{{ $difference }}">
                                <input type="hidden" name="nominal_per_meeting" value="{{ $nominalPerMeeting }}">
                                <input type="hidden" name="coach_data" value="{{ json_encode($coachAttendance) }}">
                                <button type="submit" class="btn {{ $existingContribution ? 'btn-primary' : 'btn-success' }}">
                                    <i class="fas fa-save"></i> {{ $existingContribution ? 'Update Data Kontribusi' : 'Simpan Data Kontribusi' }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="row">
                        <div class="col-md-6">
                            <p>
                                35% Kas Komwil Dapat Di Transfer Ke Rekeing<br>
                                Bank BNI a.n Mursalat Asyidiq<br>
                                <b>1990539150</b>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Uang Kontribusi:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($contributionAmount, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td>65% PJ:</td>
                                    <td class="text-right">Rp {{ number_format($pjShare, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>20% KAS:</td>
                                    <td class="text-right">Rp {{ number_format($kasShare, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>15% Tabungan:</td>
                                    <td class="text-right">Rp {{ number_format($savingsShare, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Nominal Pertemuan:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($nominalPerMeeting, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Selisih:</strong></td>
                                    <td class="text-right"><strong>Rp {{ number_format($difference, 0, ',', '.') }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i> Silakan pilih unit, bulan, dan tahun untuk melihat tanda terima kontribusi
            </div>
        </div>
    </div>
    @endif

@endsection
@section('script')
    <script>
        // You can add print functionality or export to PDF here if needed
    </script>
@endsection
