<?php

namespace App\Filament\Central\Resources\Tenants;

use App\Filament\Central\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Central\Resources\Tenants\Pages\EditTenant;
use App\Filament\Central\Resources\Tenants\Pages\ListTenants;
use App\Models\CustomTenantModel;
use App\Observers\TenantObserver;
use App\Services\Tenancy\TenantBackupService;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = CustomTenantModel::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::BuildingOffice2;

    protected static string | UnitEnum | null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $label = 'Tenant';

    public static function getNavigationLabel(): string
    {
        return 'Tenants';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Tenants';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('')
                    ->columnSpanFull()
                    ->label('')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    $slug = Str::slug($state);
                                    $set('domain', $slug);
                                    $set('database', config('app.db_prefix') . $slug);
                                }
                            })
                            ->disabledOn('edit'),

                        TextInput::make('domain')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->suffix(fn (string $operation) => $operation === 'create' ? '.' . config('app.domain') : null)
                            ->prefix(fn () => app()->isLocal() ? 'http://' : 'https://')
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('database')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(),

                        Select::make('modules')
                            ->label('Modules')
                            ->columnSpanFull()
                            ->options(CustomTenantModel::getModules())
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Toggle::make('active')
                            ->label('Active')
                            ->default(true),

                        Toggle::make('daily_backup_enabled')
                            ->label('Daily Backup')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(),
                TextColumn::make('name')->sortable()->searchable()->toggleable(),
                TextColumn::make('domain')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->url(fn (CustomTenantModel $record) => (app()->isLocal() ? 'http://' : 'https://') . $record->domain)
                    ->openUrlInNewTab(),
                TextColumn::make('database')->sortable()->searchable()->toggleable(),
                TextColumn::make('modules_titles')
                    ->label('Modules')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('modules', 'like', "%{$search}%"))
                    ->toggleable(),
                IconColumn::make('database_created')
                    ->label('Database Created')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean()
                    ->alignCenter(),
                ToggleColumn::make('active')
                    ->authorize('update')
                    ->sortable()
                    ->toggleable(),
                ToggleColumn::make('daily_backup_enabled')
                    ->label('Daily Backup')
                    ->authorize('update')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')->label('Active Status'),
                TernaryFilter::make('daily_backup_enabled')->label('Daily Backup'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('create_database')
                    ->hidden()
                    ->label('Create Database')
                    ->button()
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->action(function (CustomTenantModel $record) {
                        try {
                            TenantObserver::createDatabase($record);
                            showSuccessNotifiMessage('Done');
                        } catch (Exception $th) {
                            showWarningNotifiMessage($th->getMessage());
                            throw $th;
                        }
                    })
                    ->color('success')
                    ->visible(fn (CustomTenantModel $record) => ! $record->database_created),

                Action::make('setModules')
                    ->label('Set Modules')
                    ->button()
                    ->authorize('update')
                    ->schema([
                        Select::make('modules')
                            ->default(fn (CustomTenantModel $record) => $record->modules)
                            ->label('Modules')
                            ->columnSpanFull()
                            ->options(CustomTenantModel::getModules())
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ])
                    ->action(function (CustomTenantModel $record, array $data) {
                        try {
                            $record->update(['modules' => $data['modules']]);
                            showSuccessNotifiMessage('Done');
                        } catch (Exception $th) {
                            showWarningNotifiMessage('Error', $th->getMessage());
                            throw $th;
                        }
                    })
                    ->color(Color::Green),

                Action::make('importDatabase')
                    ->button()
                    ->authorize('update')
                    ->action(function (CustomTenantModel $record) {
                        try {
                            (new CustomTenantModel())->importDatabase($record);
                            showSuccessNotifiMessage('Done');
                        } catch (Throwable $th) {
                            showWarningNotifiMessage($th->getMessage());
                            throw $th;
                        }
                    })
                    ->hidden(),

                Action::make('download_backup')
                    ->label('Backup to Drive')
                    ->requiresConfirmation()
                    ->modalHeading(fn (CustomTenantModel $record) => "Backup {$record->name}")
                    ->button()
                    ->authorize('update')
                    ->action(function (CustomTenantModel $record, TenantBackupService $backupService) {
                        try {
                            $backupService->backupToGoogleDrive($record);
                            showSuccessNotifiMessage('Backup uploaded to Google Drive successfully');
                        } catch (Throwable $th) {
                            showWarningNotifiMessage($th->getMessage());
                            sendBackupFailureEmail($record->name, $th->getMessage());
                            throw $th;
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
