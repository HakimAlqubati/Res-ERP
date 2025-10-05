<?php

namespace App\Livewire\Filament;

use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

class ChangePassword extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('password')
                ->label(__('New password'))
                ->password()
                ->revealable()
                ->required()
                ->rule(PasswordRule::defaults())
                ->same('password_confirmation'),

            Forms\Components\TextInput::make('password_confirmation')
                ->label(__('Confirm password'))
                ->password()
                ->revealable()
                ->required(),
        ];
    }

    public function change(): void
    {
        $data = $this->form->getState();

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        Notification::make()
            ->title(__('Password updated successfully'))
            ->success()
            ->send();

        $this->dispatch('close-modal', id: 'change-password-modal');
    }

    public function render()
    {
        return view('livewire.filament.change-password');
    }
}
