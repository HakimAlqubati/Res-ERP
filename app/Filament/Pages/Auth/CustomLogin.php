<?php

namespace App\Filament\Pages\Auth;
use Filament\Auth\Pages\Login;

class CustomLogin extends Login
{
    protected static string $view = 'filament.login';
}