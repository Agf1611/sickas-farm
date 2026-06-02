<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} - {{ $number }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        h1, h2, h3, p {
            margin: 0;
        }

        .letterhead {
            border-collapse: collapse;
            width: 100%;
        }

        .letterhead td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 118px;
        }

        .logo {
            height: 58px;
            object-fit: contain;
            width: 92px;
        }

        .center {
            text-align: center;
        }

        .kicker {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-line {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .small-line {
            font-size: 8.5px;
        }

        .rule {
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 2px;
            margin: 10px 0 14px;
        }

        .invoice-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .meta {
            border-collapse: collapse;
            margin-bottom: 12px;
            width: 100%;
        }

        .meta td {
            border: 1px solid #d1d5db;
            padding: 6px;
        }

        .meta .label {
            background: #f3f4f6;
            font-weight: bold;
            width: 20%;
        }

        .data {
            border-collapse: collapse;
            width: 100%;
        }

        .data th {
            background: #111827;
            color: #ffffff;
            font-size: 9px;
            padding: 7px 6px;
            text-align: left;
        }

        .data td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 6px;
        }

        .right {
            text-align: right;
        }

        .total {
            margin-top: 10px;
            text-align: right;
        }

        .total strong {
            font-size: 13px;
        }

        .notes {
            border-top: 1px solid #d1d5db;
            color: #4b5563;
            margin-top: 16px;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <table class="letterhead">
        <tr>
            <td class="logo-cell">
                @if (! empty($identity['logo_path']))
                    <img class="logo" src="{{ $identity['logo_path'] }}" alt="Logo">
                @endif
            </td>
            <td class="center">
                <p class="kicker">Badan Usaha Milik Desa</p>
                <p class="kicker">{{ $identity['bumdes_name'] ?? 'BUMDes Ketapang Ternak Domba' }}</p>
                <p class="title-line">{{ $identity['unit_name'] ?? 'Unit Ternak' }}</p>
                @if (! empty($identity['address']))
                    <p class="small-line"><strong>Alamat:</strong> {{ $identity['address'] }}</p>
                @endif
                @if (! empty($identity['phone']) || ! empty($identity['email']))
                    <p class="small-line">
                        @if (! empty($identity['phone']))Telepon/WhatsApp: {{ $identity['phone'] }}@endif
                        @if (! empty($identity['phone']) && ! empty($identity['email'])) | @endif
                        @if (! empty($identity['email']))Email: {{ $identity['email'] }}@endif
                    </p>
                @endif
                @if (! empty($identity['legal_number']))
                    <p class="small-line">No. Legalitas: {{ $identity['legal_number'] }}</p>
                @endif
            </td>
            <td class="logo-cell"></td>
        </tr>
    </table>
    <div class="rule"></div>

    <h1 class="invoice-title">{{ $title }}</h1>

    <table class="meta">
        <tr>
            <td class="label">Nomor</td>
            <td>{{ $number }}</td>
            <td class="label">Tanggal</td>
            <td>{{ $date }}</td>
        </tr>
        <tr>
            <td class="label">{{ $party_label }}</td>
            <td>{{ $party_name }}</td>
            <td class="label">Tanggal Cetak</td>
            <td>{{ \App\Support\SickasFormatter::dateTime(now()) }}</td>
        </tr>
        @foreach ($meta as $label => $value)
            <tr>
                <td class="label">{{ $label }}</td>
                <td colspan="3">{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>Uraian</th>
                <th>Jumlah</th>
                <th>Berat</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['qty'] }}</td>
                    <td>{{ $row['weight'] }}</td>
                    <td class="right">{{ $row['amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="total">{{ $total_label }}: <strong>{{ $total }}</strong></p>

    @if ($notes)
        <p class="notes"><strong>Catatan:</strong> {{ $notes }}</p>
    @endif

    @if (! empty($identity['report_footer']))
        <p class="notes">{{ $identity['report_footer'] }}</p>
    @endif
</body>
</html>
