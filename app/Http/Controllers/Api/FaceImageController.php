<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FaceImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FaceImageController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'image'    => 'required|file|image|max:4096', // 4 MB max
                'score'    => 'nullable|numeric',
            ]);

            // حفظ الصورة
            $image = $request->file('image');
            $path  = $image->store('face_images', 'public');

            // حفظ في قاعدة البيانات
            $faceImage = FaceImage::create([
                'user_id'  => auth()->id(), // يمكن حذفها إذا لم تستخدم المصادقة
                'path'     => $path,
                'score'    => $request->input('score'), 
                'meta'     => null,
            ]);

            Log::info('done',['done']);
            return response()->json([
                'success' => true,
                'id'      => $faceImage->id,
                'url'     => Storage::url($path),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('catchhhhh: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            // خطأ تحقق البيانات
            return response()->json([
                'success' => false,
                'error'   => 'Validation error.',
                'details' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // أي خطأ آخر (حفظ الصورة، الاتصال بقاعدة البيانات...)
            Log::error('FaceImage store error: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'error'   => 'Server error. Please try again later.',
            ], 500);
        }
    }
}