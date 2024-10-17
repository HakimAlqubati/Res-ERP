<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Attendance;
use App\Models\Setting;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                                Fieldset::make()->columns(3)->schema([
                                    TextInput::make("hours_count_after_period_before")
                                        ->label('Hours allowed before period')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make("hours_count_after_period_after")
                                        ->label('Hours allowed after period')
                                        ->numeric()
                                        ->required(),
                                    

                                    Fieldset::make()->columns(1)->columnSpan(1)->schema([
                                        Select::make("period_allowed_to_calculate_overtime")
                                            ->label('Overtime calculation period')
                                            ->options([
                                                Attendance::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_QUARTER_HOUR_LABEL,
                                                Attendance::PERIOD_ALLOWED_OVERTIME_HALF_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_HALF_HOUR_LABEL,
                                                Attendance::PERIOD_ALLOWED_OVERTIME_HOUR => Attendance::PERIOD_ALLOWED_OVERTIME_HOUR_LABEL,
                                            ])
                                            ->live()
                                            ->required(),
                                        Toggle::make('calculating_overtime_with_half_hour_after_hour')
                                            ->visible(fn(Get $get): bool => $get('period_allowed_to_calculate_overtime') == Attendance::PERIOD_ALLOWED_OVERTIME_HOUR)
                                        ,

                                    ]),
                                    TextInput::make("early_attendance_minutes")
                                    ->label('Minutes counted as early arrival for attendance')
                                    ->helperText('The number of minutes before the scheduled start time that is considered early attendance.')
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
