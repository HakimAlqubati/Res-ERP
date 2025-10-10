<?php

namespace App\Filament\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema;  
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser; 
use App\Filament\Resources\UserResource\Pages\UserTable; 
use App\Filament\Traits\Forms\HasAttendanceForm;
use App\Filament\Traits\Forms\HasEmployeeExistingForm;
use App\Filament\Traits\Forms\HasNewUserForm; 
use App\Models\User; 
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource; 
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope; 

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class UserResource extends Resource
{
    use HasAttendanceForm, HasNewUserForm, HasEmployeeExistingForm;
    protected static ?string $model           = User::class;
    protected static string | \BackedEnum | null $navigationIcon  = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'User & Roles';
    // protected static ?string $cluster = UserCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    public static function getNavigationLabel(): string
    {
        return __('lang.users');
    }
    public static function form(Schema $schema): Schema
    {

        return $schema
            ->components([

                ToggleButtons::make('account_mode')
                    ->label('')
                    ->inline()
                    ->options([
                        // 'existing_employee' => 'Existing Employee',
                        'attendance_user'   => 'Attendance User',
                        'new_user'          => 'New User',
                    ])
                    ->icons([
                        // 'existing_employee' => 'heroicon-o-identification',
                        'attendance_user'   => 'heroicon-o-camera',
                        'new_user'          => 'heroicon-o-user-plus',
                    ])->visibleOn('create')
                    ->default('new_user')
                    ->live()
                    ->required(), 
                self::newUserForm(),
                self::attendanceForm(),
            ]);
    }

    public static function table(Table $table): Table
    {
       return UserTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListUsers::class,
            CreateUser::class,
            EditUser::class,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::forBranchManager()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->forBranchManager()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])->withMax('loginHistories as last_login_at', 'created_at');
    }

    public static function canViewAny(): bool
    {
        return true;
    }

      public static function getGlobalSearchResultsLimit(): int
    {
        return 15;
    }
}
