<?php

namespace App\Http\Controllers;

use App\Models\FatteningBatch;
use App\Models\Sheep;
use App\Models\BusinessProfile;
use App\Services\QrCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class QrCodePrintController extends Controller
{
    public function batch(FatteningBatch $batch, QrCodeService $qrCode): View
    {
        Gate::authorize('view', $batch);

        $detailUrl = $qrCode->batchDetailUrl($batch);

        return view('sickas-farm.qr.print', [
            'title' => 'QR Code Batch Penggemukan',
            'unitName' => BusinessProfile::reportIdentity()['unit_name'] ?? 'Ketapang Ternak',
            'label' => 'Kode Batch',
            'code' => $batch->batch_code,
            'subtitle' => $batch->pen?->name ? 'Kandang: '.$batch->pen->name : 'Batch Penggemukan',
            'detailUrl' => $detailUrl,
            'qrSvg' => $qrCode->svgForUrl($detailUrl),
        ]);
    }

    public function sheep(Sheep $sheep, QrCodeService $qrCode): View
    {
        Gate::authorize('view', $sheep);

        $detailUrl = $qrCode->sheepDetailUrl($sheep);

        return view('sickas-farm.qr.print', [
            'title' => 'QR Code Ternak',
            'unitName' => BusinessProfile::reportIdentity()['unit_name'] ?? 'Ketapang Ternak',
            'label' => 'Kode Ternak',
            'code' => $sheep->tag_number,
            'subtitle' => $sheep->fatteningBatch?->batch_code ? 'Batch: '.$sheep->fatteningBatch->batch_code : 'Data Ternak',
            'detailUrl' => $detailUrl,
            'qrSvg' => $qrCode->svgForUrl($detailUrl),
        ]);
    }
}
