<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
      public function show(Request $request)
    {
        // يمكنك إضافة وسيطات platform/version في المستقبل
        return response()->json([
            // مفاتيح أخرى إن أردت (countdown, screensaver, ovalRxPct, ...)
            'showSwitchCameraButton' => (bool) Setting::getSetting('show_switch_camera_button', false),
            'updatedAt' => now()->toIso8601String(),
        ]);
    }
}
