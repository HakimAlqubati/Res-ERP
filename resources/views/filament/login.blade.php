<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            display: flex;
            flex-direction: row;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
        }

        .illustration {
            background-color: #f3ebff;
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .illustration img {
            max-width: 100%;
            height: auto;
        }

        .illustration h2 {
            font-size: 24px;
            color: #5d3fd3;
            margin-top: 20px;
        }

        .illustration p {
            font-size: 16px;
            color: #6c757d;
        }

        .login-form {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form h1 {
            /* font-size: 24px; */
            color: 'rgb(0, 100, 46)';
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: 'rgb(0, 100, 46)';
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid 'rgb(0, 100, 46)';
            border-radius: 8px;
            font-size: 14px;
            color: 'rgb(0, 100, 46)';
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .form-actions a {
            font-size: 14px;
            color: 'rgb(0, 100, 46)';
            text-decoration: none;
        }

        .btn {
            background-color: 'rgb(0, 100, 46)';
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        .btn:hover {
            background-color: 'rgb(0, 100, 46)';
        }
 
 
 
    </style>
</head>

<body>
    <div class="container">
        <div class="illustration">
            <img src="{{ asset('storage/logo/default.png') }}" alt="Illustration">
            {{-- <h1 style="color: rgb(0, 100, 46); font-size: 25px;">{{ 'Empowering Growth with' }}</h1> --}}
            {{-- <br> --}}
            {{-- <h4>{{ 'Enterprise-Level Solutions for All' }}</h4> --}}

        </div>
        <div class="login-form">
            <section class="grid auto-cols-fr gap-y-6">
                <x-filament-panels::header.simple :heading="$heading ??= $this->getHeading()" :logo="$this->hasLogo()" :subheading="$subheading ??= $this->getSubHeading()" />
            </section>
            <x-filament-panels::form id="form" wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
            </x-filament-panels::form>
        </div>
    </div>
</body>

</html>
