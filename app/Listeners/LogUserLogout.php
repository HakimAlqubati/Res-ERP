<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Facades\Request;
use App\Models\UserLoginHistory;

class LogUserLogout
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        // UserLoginHistory::create([
        //     'user_id'    => $event->user->id,
        //     'username' => $event?->user?->name,
        //     'email' => $event->user?->email,
        //     'phonenumber' => $event?->user?->phone_number,
        //     'ip_address' => Request::ip(),
        //     'user_agent' => Request::userAgent(),
        //     'platform'   => Request::is('api/*') ? 'API' : 'Web',
        // ]);
    }
}
