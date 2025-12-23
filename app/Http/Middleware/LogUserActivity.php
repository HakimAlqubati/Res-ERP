<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\Activity;

class LogUserActivity
{
    /**
     * المسار الحرج: نمرر الطلب بأسرع ما يمكن
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * المعالجة الخلفية: التسجيل يتم بعد إغلاق الاتصال مع المستخدم
     */
    public function terminate(Request $request, Response $response): void
    {
        // 1. التحقق السريع
        if (!Auth::check()) {
            return;
        }

        // 2. تجاهل تحديثات Livewire الفارغة (Logic Optimized)
        if ($this->shouldIgnoreLivewire($request)) {
            return;
        }

        // 3. تجهيز البيانات (بدون تعطيل المستخدم)
        $user = Auth::user();

        // تنظيف البيانات الحساسة والثقيلة
        $requestData = $this->prepareRequestData($request);

        // 4. تسجيل النشاط (كتابة فقط - بدون قراءة أو حذف)
        Activity::causedBy($user)
            ->withProperties([
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $requestData,
            ])
            ->tap(function (\Spatie\Activitylog\Models\Activity $activity) use ($user) {
                $activity->user_name = $user->name; // Null safe operator not needed if Auth::check passed
            })
            ->log("User visited: {$request->path()}");
    }

    private function shouldIgnoreLivewire(Request $request): bool
    {
        if (!$request->is('livewire/update')) {
            return false;
        }

        $components = $request->input('components', []);

        // Early return for performance
        if (empty($components)) return true;

        return count($components) === 1 &&
            empty($components[0]['updates']) &&
            empty($components[0]['calls']);
    }

    private function prepareRequestData(Request $request): array
    {
        return collect($request->except(['password', 'password_confirmation', 'token', '_token']))
            ->map(function ($value, $key) {
                // معالجة الـ Snapshots فقط إذا كانت ضرورية جداً
                // ملاحظة: فك تشفير الـ Snapshot مكلف، تأكد من حاجتك له في اللوج
                if ($key === 'components' && is_array($value)) {
                    return collect($value)->map(function ($component) {
                        if (isset($component['snapshot']) && is_string($component['snapshot'])) {
                            $decoded = json_decode($component['snapshot'], true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $component['snapshot'] = $decoded;
                            }
                        }
                        return $component;
                    })->toArray();
                }
                return $value;
            })->toArray();
    }
}
