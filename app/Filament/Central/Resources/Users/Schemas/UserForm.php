<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('User Information'))
                    ->description(__('Manage the user name, email address, and password.'))
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('name')
                                ->label(__('Name'))
                                ->prefixIcon(Heroicon::OutlinedUser)
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label(__('Email'))
                                ->prefixIcon(Heroicon::OutlinedEnvelope)
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(table: User::class, column: 'email', ignoreRecord: true),
                        ]),

                        TextInput::make('password')
                            ->label(__('Password'))
                            ->prefixIcon(Heroicon::OutlinedLockClosed)
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('Leave blank to keep the current password.') : null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
