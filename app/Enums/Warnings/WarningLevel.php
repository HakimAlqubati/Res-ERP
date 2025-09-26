<?php
namespace App\Enums\Warnings;

enum WarningLevel: string
{
    case Info     = 'info';
    case Warning  = 'warning';
    case Critical = 'critical';
}
