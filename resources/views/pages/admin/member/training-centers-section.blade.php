<!-- Training Centers Section -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-dumbbell"></i> Training Centers
        </h3>
    </div>
    <div class="card-body">
        <!-- Training Centers List -->
        @if($member->trainingCenters->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Pesilat belum terdaftar di training center manapun.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Nama Training Center</th>
                            <th class="text-center">Hari Training</th>
                            <th class="text-center">Jam Training</th>
                            <th class="text-center">Tanggal Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $i = 1;
                            $dayLabels = [
                                'Monday' => 'Senin',
                                'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu',
                                'Thursday' => 'Kamis',
                                'Friday' => 'Jumat',
                                'Saturday' => 'Sabtu',
                                'Sunday' => 'Minggu'
                            ];
                        @endphp
                        @foreach($member->trainingCenters as $center)
                        <tr>
                            <td class="text-center">{{ $i++ }}</td>
                            <td>
                                <a href="{{ route('training-center.show', $center->id) }}" target="_blank">
                                    {{ $center->name }}
                                </a>
                            </td>
                            <td class="text-center">
                                @if($center->training_days && count($center->training_days) > 0)
                                    
                                    <small>
                                        @foreach($center->training_days as $day)
                                            <span class="badge bg-secondary">{{ $dayLabels[$day] ?? $day }}</span>
                                        @endforeach
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($center->training_time)
                                    <span class="badge bg-primary">{{ $center->training_time }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($center->pivot->joined_date)->format('d-m-Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
