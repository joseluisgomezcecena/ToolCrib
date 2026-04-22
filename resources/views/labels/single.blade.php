<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiqueta · {{ $tool->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; }
        .bar { max-width: 720px; margin: 0 auto 16px; display: flex; justify-content: space-between; gap: 12px; }
        .bar a, .bar button { padding: 8px 14px; border-radius: 6px; text-decoration: none; border: 0; cursor: pointer; font-size: 14px; }
        .primary { background: #4f46e5; color: #fff; }
        .ghost { background: #fff; color: #374151; border: 1px solid #d1d5db; }
        .sheet { background: #fff; max-width: 720px; margin: 0 auto; padding: 28px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .label { text-align: center; padding: 24px; border: 2px dashed #9ca3af; border-radius: 8px; }
        .label h1 { margin: 12px 0 4px; font-size: 22px; }
        .label .code { font-family: monospace; font-size: 26px; letter-spacing: 2px; margin: 4px 0 12px; }
        .label .meta { color: #6b7280; font-size: 13px; }
        .label img, .label svg { width: 260px; height: 260px; display: block; margin: 0 auto; }
        @media print {
            body { background: #fff; padding: 0; }
            .bar { display: none; }
            .sheet { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <a href="{{ route('tools.show', $tool) }}" class="ghost">← Volver a la herramienta</a>
        <div>
            <a href="{{ route('tools.qr', [$tool, 'format' => 'png', 'size' => 600]) }}" download="qr-{{ $tool->code }}.png" class="ghost">Descargar PNG</a>
            <button onclick="window.print()" class="primary">Imprimir</button>
        </div>
    </div>

    <div class="sheet">
        <div class="label">
            <div class="meta">{{ config('app.name') }}</div>
            <img src="{{ route('tools.qr', [$tool, 'format' => 'svg', 'size' => 400]) }}" alt="QR {{ $tool->code }}">
            <h1>{{ $tool->name }}</h1>
            <div class="code">{{ $tool->code }}</div>
            <div class="meta">
                {{ optional($tool->category)->name }}
                @if($tool->location) · {{ $tool->location->name }} @endif
            </div>
        </div>
    </div>
</body>
</html>
