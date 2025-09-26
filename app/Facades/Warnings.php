<?php
// app/Facades/Warnings.php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

final class Warnings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Warnings\WarningSender::class;
    }
}
