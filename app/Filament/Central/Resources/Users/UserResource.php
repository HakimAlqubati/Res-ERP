<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\Users;

use App\Filament\Central\Resources\Users\Pages\CreateUser;
use App\Filament\Central\Resources\Users\Pages\EditUser;
use App\Filament\Central\Resources\Users\Pages\ListUsers;
use App\Filament\Central\Resources\Users\Schemas\UserForm;
use App\Filament\Central\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string | UnitEnum | null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Users');
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    public static function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->canAccessCentralPanel()) {
            return Response::deny();
        }

        if (
            in_array($action, ['delete', 'forceDelete'], true)
            && $record instanceof User
            && (int) $record->getKey() === (int) $user->getKey()
        ) {
            return Response::deny(__('You cannot delete your own account.'));
        }

        return Response::allow();
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }
}
