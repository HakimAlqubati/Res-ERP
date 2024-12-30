<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\CustomTenantModel;
use App\Observers\TenantObserver;
use Dompdf\FrameDecorator\Text;
use Filament\Actions\Action;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = CustomTenantModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return 'Tenants';
    }

    public static function getPluralLabel(): ?string
    {
        return 'Tenants';
    }
    protected static ?string $label = 'Tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('')->label('')->columns(3)->schema([
                    TextInput::make('name')->required()->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            if ($state) {
                                // Update the 'domain' field dynamically based on 'name'
                                $set('domain', Str::slug($state));

                                // Update the 'database' field dynamically based on 'name'
                                $set('database', config('app.db_prefix') . Str::slug($state));
                            }
                        }),
                    TextInput::make('domain')->required()->unique(ignoreRecord: true)->disabled()

                        ->suffix('.' . config('app.domain'))
                        ->prefix(function () {
                            return (isLocal()) ? 'http://' : 'https://';
                        })->disabled()->dehydrated(),
                    TextInput::make('database')->required()->unique(ignoreRecord: true)->disabled()->dehydrated(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        // $dbName = $_GET['dbName'];
        // dd(DB::statement('CREATE DATABASE IF NOT EXISTS ' . $dbName));
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(),
                TextColumn::make('name')->sortable()->searchable()->toggleable(),
                TextColumn::make('domain')->sortable()->searchable()->toggleable()
                    // ->url(fn($record) => (isLocal()) ? 'https://' . $record->domain : 'https://' . $record->domain)
                    ->url(fn($record) => ('http://' . $record->domain))

                    ->openUrlInNewTab(),
                TextColumn::make('database')->sortable()->searchable()->toggleable(),
                IconColumn::make('database_created')->label('Database Created')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true)->boolean()->alignCenter(true),
                ToggleColumn::make('active')->sortable()->searchable()->toggleable(),
                TextColumn::make('updated_at')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('create_database')->hidden()
                    ->label('Create Database')->button()
                    ->requiresConfirmation()

                    ->action(function ($record) {
                        try {
                            TenantObserver::createDatabase($record);
                            showSuccessNotifiMessage('Done');
                        } catch (\Exception $th) {
                            throw $th;
                            showWarningNotifiMessage($th->getMessage());
                        }
                    })

                    ->color('success')
                    ->visible(fn($record) => !$record->database_created),

                Tables\Actions\Action::make('importDatabase')
                    // ->form([
                    //     FileUpload::make('sqlfile')->label('SQL File')
                    //         ->visibility('public')
                    //         ->required(),
                    // ])
                    ->button()
                    ->action(function ($record, $data) {
                        try {
                            // $sql = 'public/' . $data['sqlfile'];
                            // $sql = Storage::path($sql);
                            // $sql = file_get_contents($sql);
                            // dd($sql);

                            // (new CustomTenantModel)->importDatabaseByForm($record->database, $sql);
                            (new CustomTenantModel)->importDatabase($record->database);
                            showSuccessNotifiMessage('done');
                        } catch (\Throwable $th) {
                            showWarningNotifiMessage($th->getMessage());
                            throw $th;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function createDatabase_($dbName)
    {
        DB::beginTransaction();
        try {
            // Check if the database exists
            $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

            if ($databaseExists) {
                return "Database {$dbName} already exists.";
            }

            // Safely create the database
            DB::statement("CREATE DATABASE `" . addslashes($dbName) . "`");

            // Set the tenant database connection dynamically
            config(['database.connections.tenant.database' => $dbName]);
            DB::purge('tenant'); // Reset the tenant connection
            DB::reconnect('tenant');

            // Set the database connection to the new database
            // config(['database.connections.tenant.database' => $dbName]);
            Artisan::call('tenants:artisan', [
                'artisanCommand' => 'migrate --database=tenant',
            ]);

            DB::commit();
            return "Database {$dbName} created and migrations applied successfully.";
        } catch (\Throwable $th) {
            DB::rollBack();
            return $th->getMessage();
        }
        // add logic to create database
    }
}
