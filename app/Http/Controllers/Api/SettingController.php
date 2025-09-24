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
            'faceRawMin' => (float) Setting::getSetting('face_raw_min', 0.20),
            'faceRawIdeal' => (float) Setting::getSetting('face_raw_ideal', 0.22),
            'faceRawMax' => (float) Setting::getSetting('face_raw_max', 0.50),
            'cropScale' => (float) Setting::getSetting('crop_scale', 0.7),  
            'showKeypadScreen' => (bool) Setting::getSetting('show_keypad_screen', true),
            'showCameraScreen' => (bool) Setting::getSetting('show_camera_screen', true),
            'updatedAt' => now()->toIso8601String(),
        ]);
    }
}
