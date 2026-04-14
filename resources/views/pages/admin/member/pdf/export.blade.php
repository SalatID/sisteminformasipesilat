<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Pesilat</title>
    <style>
        @page {
            margin: 20px;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 16px;
        }

        .header h3 {
            margin: 3px 0;
            font-size: 12px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }

        .footer {
            text-align: right;
            font-size: 9px;
            margin-top: 20px;
        }

        .summary-table th {
            font-size: 9px;
        }

        .summary-table td {
            text-align: center;
            font-size: 9px;
        }

        .unit-header {
            background-color: #e0e0e0;
            padding: 8px;
            margin-top: 15px;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 12px;
        }
    </style>
</head>
<body>
    @if($showSummary)
        {{-- Summary Page --}}
        <div class="header">
            <h2>RINGKASAN DATA PESILAT</h2>
            <h3>Berdasarkan Tingkat Sabuk (TS) dan Unit</h3>
            <h3>Tanggal: {{ date('d-m-Y') }}</h3>
        </div>

        <table class="summary-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">No</th>
                    <th rowspan="2">Unit</th>
                    <th colspan="{{ count($ts_list) }}">Tingkat Sabuk (TS)</th>
                    <th rowspan="2">Total</th>
                </tr>
                <tr>
                    @foreach($ts_list as $ts)
                        <th>{{ $ts->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php($total_by_ts = array_fill(0, count($ts_list), 0))
                @foreach($units as $index => $unit)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ strtoupper(strtolower($unit->name)) }}</td>
                        @php($row_total = 0)
                        @foreach($ts_list as $ts_index => $ts)
                            @php($count = $summary[$ts->id][$unit->id] ?? 0)
                            @php($total_by_ts[$ts_index] += $count)
                            @php($row_total += $count)
                            <td class="text-center">{{ $count > 0 ? $count : '-' }}</td>
                        @endforeach
                        <td class="text-center"><strong>{{ $row_total }}</strong></td>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="2" class="text-center">TOTAL</th>
                    @foreach($total_by_ts as $total)
                        <th class="text-center">{{ $total }}</th>
                    @endforeach
                    <th class="text-center">{{ array_sum($total_by_ts) }}</th>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Dicetak pada: {{ date('d-m-Y H:i:s') }}</p>
        </div>

        <div class="page-break"></div>
    @endif

    {{-- Member List Pages (Grouped by Unit) --}}
    @foreach($membersByUnit as $unit_name => $unit_members)
        <div class="header">
            <h2>DAFTAR PESILAT</h2>
            <h3>Unit: {{ strtoupper($unit_name) }}</h3>
            <h3>Tanggal: {{ date('d-m-Y') }}</h3>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Pesilat</th>
                    <th style="width: 100px;">ID Member</th>
                    <th style="width: 80px;">TS</th>
                    <th style="width: 80px;">Tanggal Bergabung</th>
                    <th style="width: 60px;">Jenis Kelamin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unit_members as $index => $member)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $member->name }}</td>
                        <td class="text-center">{{ $member->member_id }}</td>
                        <td class="text-center">{{ $member->ts->name ?? '-' }}</td>
                        <td class="text-center">{{ $member->joined_date->format('d-m-Y') }}</td>
                        <td class="text-center">
                            @if($member->gender === 'male')
                                L
                            @elseif($member->gender === 'female')
                                P
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <th colspan="5" class="text-right">Total Pesilat:</th>
                    <th class="text-center">{{ count($unit_members) }}</th>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Dicetak pada: {{ date('d-m-Y H:i:s') }}</p>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
