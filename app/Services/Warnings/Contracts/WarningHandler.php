<?php

namespace App\Services\Warnings\Contracts;

interface WarningHandler
{
    /** اسم فريد للنوع (للتتبّع) */
    public function key(): string;

    /** تمرير خيارات من الأمر (user, limit …) */
    public function setOptions(array $options): void;

    /**
     * تنفيذ النوع كاملاً على الاتصال الحالي (سنترال/تينانت).
     * يجب أن يُرجع [sent, failed]
     */
    public function handle(): array;
}
