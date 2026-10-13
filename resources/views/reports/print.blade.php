<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name', 'HRIS') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #0f172a; margin: 24px; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #059669; padding-bottom: 10px; margin-bottom: 16px; }
        .brand { display: flex; align-items: center; gap: 8px; }
        .logo { width: 32px; height: 32px; border-radius: 6px; background: #059669; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        h1 { font-size: 16px; margin: 0; }
        .meta { font-size: 11px; color: #64748b; text-align: right; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; }
        th { background: #f1f5f9; font-weight: 600; }
        tr:nth-child(even) td { background: #f8fafc; }
        .toolbar { margin-bottom: 12px; }
        .btn { display: inline-block; padding: 6px 12px; border-radius: 6px; background: #059669; color: #fff; text-decoration: none; font-size: 12px; border: 0; cursor: pointer; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="toolbar">
        <button class="btn" onclick="window.print()">Cetak / Simpan PDF</button>
        <a class="btn btn-secondary" href="{{ url()->previous() }}">Kembali</a>
    </div>

    <header>
        <div class="brand">
            <div class="logo">HR</div>
            <div>
                <h1>{{ $title }}</h1>
                <div class="meta">HRIS — HRD Management System</div>
            </div>
        </div>
        <div class="meta">
            Dicetak: {{ now()->timezone(tz())->translatedFormat('d F Y H:i') }} WIB
        </div>
    </header>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach((array) $row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
