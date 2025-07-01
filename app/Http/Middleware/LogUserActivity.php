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

        // تجاهل طلبات livewire التلقائية التي لا تحتوي على أي calls أو updates
        if ($request->is('livewire/update')) {
            $components = $request->input('components', []);
            if (
                count($components) === 1 &&
                empty($components[0]['updates']) &&
                empty($components[0]['calls'])
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
            ->tap(function (\Spatie\Activitylog\Models\Activity $activity) use ($user) {
                $activity->user_name = $user?->name;
            })
            ->log("User visited/requested: {$request->method()} {$request->path()}");

        return $response;
    }
}