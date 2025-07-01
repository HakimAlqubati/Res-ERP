<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Facades\Activity;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // لا تتبع إذا لم يكن المستخدم مسجلاً
        if (!Auth::check()) {
            return $response;
        }

        // تجاهل طلبات livewire إذا كانت غير جوهرية
        if ($request->is('livewire/update')) {
            $components = $request->input('components', []);

            // تجاهل Livewire التلقائي بدون calls أو updates
            if (
                count($components) === 1 &&
                empty($components[0]['updates']) &&
                empty($components[0]['calls'])
            ) {
                return $response;
            }

            // تجاهل Livewire إذا كانت updates عبارة عن tableFilters فقط
            if (
                isset($components[0]['updates']) &&
                $this->isOnlyFilterUpdate($components[0]['updates'])
            ) {
                return $response;
            }
        }

        $user = Auth::user();

        // معالجة بيانات الطلب وفك snapshot إن وُجد
        $requestData = collect($request->except(['password', 'token', '_token']))
            ->map(function ($value, $key) {
                if ($key === 'components' && is_array($value)) {
                    return collect($value)->map(function ($component) {
                        if (isset($component['snapshot'])) {
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

        // تسجيل النشاط
        Activity::causedBy($user)
            ->withProperties([
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $requestData,
            ])
            ->log("User visited/requested: {$request->method()} {$request->path()}");

        return $response;
    }

    protected function isOnlyFilterUpdate(array $updates): bool
    {
        return collect($updates)
            ->keys()
            ->every(fn($key) => str_starts_with($key, 'tableFilters.'));
    }
}