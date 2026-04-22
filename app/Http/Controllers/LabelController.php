<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function qr(Tool $tool, Request $request)
    {
        $size = max(100, min((int) $request->query('size', 300), 1200));
        $format = $request->query('format', 'svg');

        $builder = new Builder(
            writer: $format === 'png' ? new PngWriter() : new SvgWriter(),
            data: $tool->code,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 6,
        );

        $result = $builder->build();

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function show(Tool $tool)
    {
        return view('labels.single', compact('tool'));
    }

    public function sheet(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->filter()->map(fn ($v) => (int) $v)->values();

        $tools = $ids->isNotEmpty()
            ? Tool::whereIn('id', $ids)->orderBy('name')->get()
            : Tool::where('is_active', true)->orderBy('name')->get();

        return view('labels.sheet', compact('tools'));
    }
}
