<?php

namespace App\Services;

use App\Filament\Resources\Sheep\SheepResource;
use App\Filament\Resources\SheepBatches\SheepBatchResource;
use App\Models\FatteningBatch;
use App\Models\Sheep;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\HtmlString;

class QrCodeService
{
    public function batchDetailUrl(FatteningBatch $batch): string
    {
        return SheepBatchResource::getUrl('view', ['record' => $batch]);
    }

    public function sheepDetailUrl(Sheep $sheep): string
    {
        return SheepResource::getUrl('view', ['record' => $sheep]);
    }

    public function batchPrintUrl(FatteningBatch $batch): string
    {
        return route('sickas-farm.qr.batch.print', $batch);
    }

    public function sheepPrintUrl(Sheep $sheep): string
    {
        return route('sickas-farm.qr.sheep.print', $sheep);
    }

    public function svgForUrl(string $url, int $scale = 10): string
    {
        $options = new QROptions([
            'eccLevel' => EccLevel::M,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'scale' => $scale,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
        ]);

        return (new QRCode($options))->render($url);
    }

    public function previewHtml(string $code, string $detailUrl): HtmlString
    {
        $svg = $this->svgForUrl($detailUrl, 6);
        $escapedCode = e($code);
        $escapedUrl = e($detailUrl);

        return new HtmlString(<<<HTML
            <div style="display:grid; gap:0.75rem; justify-items:start;">
                <div style="width:160px; max-width:100%; padding:0.5rem; background:#fff; border:1px solid #e5e7eb; border-radius:0.5rem;">
                    {$svg}
                </div>
                <div style="font-size:0.875rem; line-height:1.4;">
                    <strong>{$escapedCode}</strong><br>
                    <span style="color:#6b7280;">{$escapedUrl}</span>
                </div>
            </div>
        HTML);
    }
}
