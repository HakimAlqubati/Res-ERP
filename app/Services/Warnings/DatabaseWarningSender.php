<?php
// app/Services/Warnings/DatabaseWarningSender.php

namespace App\Services\Warnings;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DatabaseWarningSender implements WarningSender
{
    public function send(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $users = is_iterable($users) ? $users : [$users];
        // $nowIso = now()->toISOString();
        // $data   = $payload->toDatabaseArray();
        $data = json_encode($payload->toDatabaseArray()); // 👈 هذا الفرق

        foreach ($users as $u) {
            // dd($payload->deterministicId());
            DB::table('notifications')->updateOrInsert(
                [
                    'id'              => $payload->deterministicId(),
                    'notifiable_type' => get_class($u),
                    'notifiable_id'   => $u->getAuthIdentifier(),
                ],
                [
                    'type'       => \App\Notifications\WarningNotification::class,
                    'data'       => $data,
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function sendAlwaysNew(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        $users = is_iterable($users) ? $users : [$users];
        $nowIso = now()->toISOString();
        // $data   = $payload->toDatabaseArray();
        $data = json_encode($payload->toDatabaseArray()); // 👈 هذا الفرق

        foreach ($users as $u) {
            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(), // جديد دائمًا
                'type'            => \App\Notifications\WarningNotification::class,
                'notifiable_type' => get_class($u),
                'notifiable_id'   => $u->getAuthIdentifier(),
                'data'            => $data,
                'read_at'         => null,
                'created_at'      => $nowIso,
                'updated_at'      => $nowIso,
            ]);
        }
    }
}
