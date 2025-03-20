<?php

namespace App\Filament\Pages;

use App\Models\LoginAttempt;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class CustomLogin extends SimplePage
{
    use InteractsWithFormActions;
    use WithRateLimiting;

    protected static string $view = 'filament.login';
    // protected static string $view = 'filament-panels::pages.auth.login';
    protected static ?string $title = null;

    protected ?string $heading = null;

    protected ?string $subheading = null;

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return 'filament-panels::components.layout.base';
    }

    public function hasLogo(): bool
    {
        return false;
    }
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];


    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function authenticate()
    {
        $ipAddress = request()?->ip();
        $email = $this->form->getState()['email'];

        // Check if the user is blocked
        if ($this->isBlocked($email, $ipAddress)) {
            throw ValidationException::withMessages([
                'data.email' => 'User Is Blocked, Multiple Attempts Login',
            ]);
        }

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'email' => __('filament::login.messages.throttled', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();

        if (!Filament::auth()->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'])) {
            // Log failed attempt
            LoginAttempt::create([
                'email' => $email,
                'ip_address' => $ipAddress,
                'attempted_at' => now(),
                'successful' => false,
            ]);
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'attempted_at' => now(),
            'successful' => true,
        ]);

        session()->regenerate();

        if (setting('disallow_multi_session') === 1) {
            Auth::logoutOtherDevices($data['password']);
        } 
        return app(LoginResponse::class);
    }


   

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form;
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::pages/auth/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl());
    }

    // public function getTitle(): string | Htmlable
    // {
    //     return __('filament-panels::pages/auth/login.title');
    // }

    // public function getHeading(): string | Htmlable
    // {
    //     return __('filament-panels::pages/auth/login.heading');
    // }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    protected function isBlocked($email, $ipAddress)
    {
        $threshold = setting('threshold'); // Maximum allowed attempts
        $hoursToAllow = 1;
        if (setting('hours_to_allow_login_again') !== null) {
            $hoursToAllow = setting('hours_to_allow_login_again');
        }
        $blockTime = now()->subHours($hoursToAllow); // Block duration

        // Check if blocking is based on a specific time
        if (setting('type_reactive_blocked_users') === 'based_on_specific_time') {
            // Count recent failed attempts based only on attempted_at
            $failedAttempts = LoginAttempt::where('attempted_at', '>=', $blockTime)
                ->where('ip_address', $ipAddress)
                ->where('successful', false)
                ->count();
        } else {
            // Default condition with additional filters
            $failedAttempts = LoginAttempt::where('email', $email)
                ->where('ip_address', $ipAddress)
                ->where('successful', false)
                ->count();
        }

        return $failedAttempts >= $threshold;
    }
}
