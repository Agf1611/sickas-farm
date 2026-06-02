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
            font-size: 8px;
            padding: 6px 5px;
            text-align: left;
        }

        .data td {
            border-bottom: 1px solid #e5e7eb;
            font-size: 8.5px;
            padding: 6px 5px;
        }

        .detail-summary {
            border: 1px solid #d1d5db;
            font-size: 9px;
            margin-bottom: 10px;
            padding: 7px;
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
            color: #4b5563;
            font-size: 8px;
            margin-top: 2px;
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
    @include('invoices.partials.sickas-invoice-content')
</body>
</html>
