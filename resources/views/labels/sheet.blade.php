<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Hoja de etiquetas</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #e5e7eb; margin: 0; padding: 24px; }
        .bar { max-width: 900px; margin: 0 auto 16px; display: flex; justify-content: space-between; }
        .bar a, .bar button { padding: 8px 14px; border-radius: 6px; text-decoration: none; border: 0; cursor: pointer; font-size: 14px; }
        .primary { background: #4f46e5; color: #fff; }
        .ghost { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .page { background: #fff; max-width: 900px; margin: 0 auto; padding: 24px;
                display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .cell { border: 1px dashed #9ca3af; border-radius: 6px; padding: 10px;
                display: flex; align-items: center; gap: 10px; page-break-inside: avoid; }
        .cell img, .cell svg { width: 96px; height: 96px; flex-shrink: 0; }
        .cell .info { flex: 1; min-width: 0; }
        .cell h3 { margin: 0 0 2px; font-size: 14px; line-height: 1.2; }
        .cell .code { font-family: monospace; font-size: 13px; color: #374151; }
        .cell .meta { font-size: 11px; color: #6b7280; margin-top: 4px; }
        @media print {
            body { background: #fff; padding: 0; }
            .bar { display: none; }
            .page { box-shadow: none; padding: 0; gap: 6px; }
            .cell { border-color: #d1d5db; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <a href="{{ route('tools.index') }}" class="ghost">← Volver al listado</a>
        <button onclick="window.print()" class="primary">Imprimir {{ count($tools) }} etiquetas</button>
    </div>

    <div class="page">
        @foreach($tools as $t)
            <div class="cell">
                <img src="{{ route('tools.qr', [$t, 'format' => 'svg', 'size' => 200]) }}" alt="QR">
                <div class="info">
                    <h3>{{ $t->name }}</h3>
                    <div class="code">{{ $t->code }}</div>
                    <div class="meta">
                        {{ optional($t->category)->name }}
                        @if($t->location) · {{ $t->location->name }} @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
