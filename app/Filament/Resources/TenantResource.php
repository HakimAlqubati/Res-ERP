<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Models\CustomTenantModel;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
                    TextInput::make('name')->required()->unique(ignoreRecord: true),
                    TextInput::make('domain')->required()->unique(ignoreRecord: true)
                        ->suffix('.' . config('app.domain'))
                        ->prefix(function () {
                            return (isLocal()) ? 'http://' : 'https://';
                        }),
                    TextInput::make('database')->required()->unique(ignoreRecord: true),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable()->searchable()->toggleable(),
                TextColumn::make('name')->sortable()->searchable()->toggleable(),
                TextColumn::make('domain')->sortable()->searchable()->toggleable()
                    ->url(fn($record) => (isLocal()) ? 'http://' . $record->domain : 'http://'  . $record->domain)

                    ->openUrlInNewTab(),
                TextColumn::make('database')->sortable()->searchable()->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function createDatabase($dbName)
    {
        DB::beginTransaction();
        try {
            // Check if the database exists
            $databaseExists = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);

            if ($databaseExists) {
                return "Database {$dbName} already exists.";
            }

            // Create the database
            DB::statement("CREATE DATABASE {$dbName}");

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
