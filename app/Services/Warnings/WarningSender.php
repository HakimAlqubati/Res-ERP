<?php
// app/Services/Warnings/WarningSender.php

namespace App\Services\Warnings;

use Illuminate\Contracts\Auth\Authenticatable;

interface WarningSender
{
    /**
     * أرسل تحذيرًا لمستخدم واحد أو مجموعة مستخدمين.
     * يطبّق upsert باستخدام ID ثابت لمنع التكرار.
     *
     * @param Authenticatable|iterable<Authenticatable> $users
     * @return void
     */
    public function send(Authenticatable|iterable $users, WarningPayload $payload): void;

    /**
     * نفس send لكن يتجاوز منع التكرار ويُنشئ سجلًا جديدًا دائمًا.
     */
    public function sendAlwaysNew(Authenticatable|iterable $users, WarningPayload $payload): void;
}
