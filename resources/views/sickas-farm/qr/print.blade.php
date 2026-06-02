<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ $code }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
        }

        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .card {
            width: 92mm;
            min-height: 118mm;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12mm;
            text-align: center;
        }

        .brand {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .unit {
            margin-top: 3px;
            font-size: 12px;
            color: #4b5563;
        }

        .qr {
            width: 56mm;
            margin: 10mm auto 7mm;
        }

        .qr svg {
            width: 100%;
            height: auto;
            display: block;
        }

        .label {
            font-size: 12px;
            color: #6b7280;
        }

        .code {
            margin-top: 4px;
            font-size: 24px;
            font-weight: 800;
            word-break: break-word;
        }

        .subtitle {
            margin-top: 6px;
            font-size: 13px;
            color: #374151;
        }

        .url {
            margin-top: 9mm;
            font-size: 9px;
            color: #6b7280;
            word-break: break-all;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 18px;
        }

        .button {
            border: 0;
            border-radius: 6px;
            background: #111827;
            color: #ffffff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
        }

        .button.secondary {
            background: #e5e7eb;
            color: #111827;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page {
                min-height: auto;
                padding: 0;
            }

            .card {
                border: 1px solid #000000;
                border-radius: 0;
                box-shadow: none;
            }

            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div>
            <section class="card" aria-label="{{ $title }}">
                <div class="brand">BUMDes SICKAS FARM</div>
                <div class="unit">{{ $unitName }}</div>

                <div class="qr">{!! $qrSvg !!}</div>

                <div class="label">{{ $label }}</div>
                <div class="code">{{ $code }}</div>
                <div class="subtitle">{{ $subtitle }}</div>

                <div class="url">{{ $detailUrl }}</div>
            </section>

            <div class="actions">
                <button class="button" type="button" onclick="window.print()">Cetak QR</button>
                <a class="button secondary" href="{{ $detailUrl }}">Buka Detail</a>
            </div>
        </div>
    </main>
</body>
</html>
