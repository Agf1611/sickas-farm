<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.35;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .header {
            margin-bottom: 12px;
        }

        .letterhead {
            border-collapse: collapse;
            width: 100%;
        }

        .letterhead td {
            vertical-align: middle;
        }

        .logo-cell {
            text-align: left;
            width: 118px;
        }

        .logo {
            height: 58px;
            object-fit: contain;
            width: 92px;
        }

        .letterhead-text {
            text-align: center;
        }

        .letterhead-kicker {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: .2px;
            text-transform: uppercase;
        }

        .letterhead-business {
            font-size: 11px;
            font-weight: bold;
            margin-top: 1px;
            text-transform: uppercase;
        }

        .letterhead-title {
            font-size: 15px;
            font-weight: bold;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .letterhead-line {
            font-size: 8.5px;
            margin-top: 2px;
        }

        .letterhead-line strong {
            font-weight: bold;
        }

        .letterhead-spacer {
            width: 118px;
        }

        .letterhead-rule {
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 2px;
            margin-top: 10px;
        }

        .report-heading {
            margin: 10px 0 12px;
            text-align: center;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta {
            color: #4b5563;
            font-size: 9px;
            margin-top: 3px;
        }

        .profile-line {
            color: #4b5563;
            font-size: 9px;
            margin-top: 4px;
        }

        .summary {
            margin-bottom: 12px;
            width: 100%;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 6px;
        }

        .summary .label {
            background: #f3f4f6;
            color: #374151;
            font-weight: bold;
            width: 25%;
        }

        .data {
            border-collapse: collapse;
            width: 100%;
        }

        .data th {
            background: #111827;
            color: #ffffff;
            font-size: 8px;
            padding: 6px 5px;
            text-align: left;
        }

        .data td {
            border-bottom: 1px solid #e5e7eb;
            font-size: 8px;
            padding: 5px;
            vertical-align: top;
        }

        .empty {
            border: 1px solid #d1d5db;
            color: #6b7280;
            padding: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $bumdesName = $report['bumdes_name'] ?? 'BUMDes Ketapang Ternak Domba';
        $businessTitle = $report['business_title'] ?? ($report['app_name'] ?? 'SICKAS FARM');
        $unitName = $report['unit_name'] ?? 'Unit Ternak';
        $address = $report['address'] ?? null;
        $phone = $report['phone'] ?? null;
        $email = $report['email'] ?? null;
        $legalNumber = $report['legal_number'] ?? null;
    @endphp

    <div class="header">
        <table class="letterhead">
            <tr>
                <td class="logo-cell">
                    @if (! empty($report['logo_path']))
                        <img class="logo" src="{{ $report['logo_path'] }}" alt="Logo">
                    @endif
                </td>
                <td class="letterhead-text">
                    <p class="letterhead-kicker">Badan Usaha Milik Desa</p>
                    <p class="letterhead-business">{{ $bumdesName }}</p>
                    <p class="letterhead-title">{{ $unitName ?: $businessTitle }}</p>
                    @if ($businessTitle && $businessTitle !== $unitName)
                        <p class="letterhead-line"><strong>{{ $businessTitle }}</strong></p>
                    @endif
                    @if ($address)
                        <p class="letterhead-line"><strong>Alamat:</strong> {{ $address }}</p>
                    @endif
                    @if ($phone || $email)
                        <p class="letterhead-line">
                            @if ($phone)<strong>Telepon/WhatsApp:</strong> {{ $phone }}@endif
                            @if ($phone && $email) | @endif
                            @if ($email)<strong>Email:</strong> {{ $email }}@endif
                        </p>
                    @endif
                    @if ($legalNumber)
                        <p class="letterhead-line">
                            <strong>No. Legalitas:</strong> {{ $legalNumber }}
                        </p>
                    @endif
                </td>
                <td class="letterhead-spacer"></td>
            </tr>
        </table>
        <div class="letterhead-rule"></div>
    </div>

    <div class="report-heading">
        <h1 class="report-title">{{ $report['title'] }}</h1>
        <p class="meta">Periode: {{ $report['period'] }} | Tanggal cetak: {{ $report['printed_at'] }}</p>
    </div>

    <table class="summary">
        <tbody>
            @foreach ($report['summary'] as $label => $value)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if (count($report['rows']))
        <table class="data">
            <thead>
                <tr>
                    @foreach ($report['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($report['columns'] as $column)
                            <td>{{ $row[$column] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">Belum ada data untuk filter laporan ini.</div>
    @endif

    @if (! empty($report['report_footer']))
        <p class="meta" style="border-top: 1px solid #d1d5db; margin-top: 14px; padding-top: 8px;">{{ $report['report_footer'] }}</p>
    @endif
</body>
</html>
