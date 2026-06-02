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

@if (! empty($detail_summary))
    <div class="detail-summary {{ $detail_summary['is_complete'] ? 'detail-summary-ok' : 'detail-summary-warning' }}">
        <strong>Status Detail Penjualan:</strong>
        @if ($detail_summary['is_complete'])
            Detail ternak sudah lengkap dan sesuai total penjualan.
        @else
            Detail ternak belum lengkap. Tercatat {{ $detail_summary['detailed_head_count'] }} dari {{ $detail_summary['head_count'] }} ekor.
            Sisa {{ $detail_summary['undetailed_head_count'] }} ekor / {{ $detail_summary['difference'] }} ditampilkan sebagai baris "Belum dirinci".
        @endif
    </div>
@endif

@if (($type ?? null) === 'sale')
    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Ternak</th>
                <th>Jenis</th>
                <th>Batch</th>
                <th>Kandang</th>
                <th>Jumlah</th>
                <th>Bobot</th>
                <th class="right">Harga</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr @class(['warning-row' => ! empty($row['is_warning'])])>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $row['livestock_code'] ?? $row['description'] }}</strong>
                        @if (! empty($row['detail']))
                            <div class="row-detail">{{ $row['detail'] }}</div>
                        @endif
                    </td>
                    <td>{{ $row['livestock_type'] ?? '-' }}</td>
                    <td>{{ $row['batch_code'] ?? '-' }}</td>
                    <td>{{ $row['pen_name'] ?? '-' }}</td>
                    <td>{{ $row['qty'] }}</td>
                    <td>{{ $row['weight'] }}</td>
                    <td class="right">{{ $row['unit_price'] ?? '-' }}</td>
                    <td class="right">{{ $row['amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Uraian</th>
                <th>Jumlah</th>
                <th>Berat</th>
                <th class="right">Harga</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['description'] }}</strong>
                        @if (! empty($row['detail']))
                            <div class="row-detail">{{ $row['detail'] }}</div>
                        @endif
                    </td>
                    <td>{{ $row['qty'] }}</td>
                    <td>{{ $row['weight'] }}</td>
                    <td class="right">{{ $row['unit_price'] ?? '-' }}</td>
                    <td class="right">{{ $row['amount'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<p class="total">{{ $total_label }}: <strong>{{ $total }}</strong></p>

@if ($notes)
    <p class="notes"><strong>Catatan:</strong> {{ $notes }}</p>
@endif

@if (! empty($identity['report_footer']))
    <p class="notes">{{ $identity['report_footer'] }}</p>
@endif
