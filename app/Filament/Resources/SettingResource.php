<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str;
class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('settings')->columnSpanFull()
                    ->tabs([
                        Tab::make('Site Settings')
                            ->schema([
                                Grid::make()->schema([                                     
                                    TextInput::make("site_name")
                                        ->label('Site Name')
                                        ->columnSpan(2)
                                        ->required(), 

                                    FileUpload::make('site_logo')
                                        ->label('Site Logo')
                                        ->image()
                                        ->columnSpan(2)
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            return "images/" . Str::random(15) . "." . $file->getClientOriginalExtension();
                                        }),
                                ]),
                            ]),

                        Tab::make('HR Settings')
                            ->schema([
                                Grid::make()->schema([
                                    TextInput::make("hours_count_after_period_before")
                                        ->label('Hours before period')
                                        ->columnSpan(2)
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("hours_count_after_period_after")
                                        ->label('Hours after period')
                                        ->columnSpan(2)
                                        ->numeric()
                                        ->required(),
                                ]),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            // 'index' => Pages\ListSettings::route('/'),
            // 'create' => Pages\CreateSetting::route('/create'),
            'index' => Pages\CreateSetting::route('/'),
            // 'edit' => Pages\EditSetting::route('/'),
        ];
    }

}
