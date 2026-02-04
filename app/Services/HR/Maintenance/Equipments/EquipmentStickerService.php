<?php

namespace App\Services\HR\Maintenance\Equipments;

use App\Models\Equipment;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class EquipmentStickerService
{
    public function generate($equipmentId)
    {
        $equipment = Equipment::findOrFail($equipmentId);
        $url = url('/') . '/admin/h-r-service-request/equipment/' . $equipment->id;
        $code = $equipment->asset_tag;

        $pdf = LaravelMpdf::loadView('qr-code.sticker', compact('url', 'code'), [], [
            'format' => [50, 50],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'QR-' . $equipment->asset_tag . '.pdf');
    }
}
