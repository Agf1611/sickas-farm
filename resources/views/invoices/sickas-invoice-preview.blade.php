<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview {{ $title }} - {{ $number }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f4f6;
            --paper: #ffffff;
            --border: #d1d5db;
            --text: #111827;
            --muted: #4b5563;
            --primary: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
        }

        .toolbar {
            align-items: center;
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 10px;
            justify-content: space-between;
            padding: 14px 18px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .toolbar-title {
            font-size: 14px;
            font-weight: 700;
        }

        .toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .button {
            align-items: center;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            min-height: 38px;
            padding: 9px 13px;
            text-decoration: none;
        }

        .button-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: #111827;
        }

        .page-shell {
            margin: 24px auto;
            max-width: 980px;
            padding: 0 14px 32px;
        }

        .paper {
            background: var(--paper);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
            padding: 34px;
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
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title-line {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .small-line {
            font-size: 11px;
        }

        .rule {
            border-top: 2px solid #111827;
            border-bottom: 1px solid #111827;
            height: 3px;
            margin: 14px 0 18px;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 14px;
            text-align: center;
            text-transform: uppercase;
        }

        .meta {
            border-collapse: collapse;
            margin-bottom: 16px;
            width: 100%;
        }

        .meta td {
            border: 1px solid var(--border);
            padding: 9px;
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
            font-size: 11px;
            padding: 10px 8px;
            text-align: left;
        }

        .data td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 8px;
            vertical-align: top;
        }

        .detail-summary {
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 14px;
            padding: 12px 14px;
        }

        .detail-summary-ok {
            background: #ecfdf5;
            border-color: #86efac;
            color: #166534;
        }

        .detail-summary-warning {
            background: #fffbeb;
            border-color: #fbbf24;
            color: #92400e;
        }

        .warning-row td {
            background: #fffbeb;
        }

        .row-detail {
            color: var(--muted);
            font-size: 11px;
            margin-top: 3px;
        }

        .right {
            text-align: right;
        }

        .total {
            margin-top: 14px;
            text-align: right;
        }

        .total strong {
            font-size: 18px;
        }

        .notes {
            border-top: 1px solid var(--border);
            color: var(--muted);
            margin-top: 18px;
            padding-top: 10px;
        }

        @media (max-width: 720px) {
            .toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .toolbar-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .button {
                justify-content: center;
            }

            .paper {
                border-radius: 12px;
                overflow-x: auto;
                padding: 18px;
            }

            .data {
                min-width: 920px;
            }
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .page-shell {
                margin: 0;
                max-width: none;
                padding: 0;
            }

            .paper {
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Preview {{ $title }}</div>
            <div style="color: var(--muted); font-size: 12px;">Periksa detail invoice sebelum dicetak.</div>
        </div>
        <div class="toolbar-actions">
            <a class="button" href="{{ url()->previous() }}">Kembali</a>
            <a class="button" href="{{ $pdf_url }}" target="_blank" rel="noopener">Download PDF</a>
            <button class="button button-primary" type="button" onclick="window.print()">Print Invoice</button>
        </div>
    </div>

    <main class="page-shell">
        <section class="paper">
            @include('invoices.partials.sickas-invoice-content')
        </section>
    </main>
</body>
</html>
