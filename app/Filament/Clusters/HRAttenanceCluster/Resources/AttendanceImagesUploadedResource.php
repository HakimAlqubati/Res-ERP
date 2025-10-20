<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendanceImagesUploadedResource\Pages\ListAttendanceImagesUploadeds;
use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendanceImagesUploadedResource\Pages;
use App\Models\AttendanceImagesUploaded;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceImagesUploadedResource extends Resource
{
    protected static ?string $model = AttendanceImagesUploaded::class;

    protected static string | \BackedEnum | null $navigationIcon =  Heroicon::Photo;

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;
    protected static ?string $pluralLabel = 'Attendance Images';
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $pluralModelLabel = 'Attendance Images';

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->columns([
                Stack::make([
                    ImageColumn::make('full_image_url')->circular(false)->tooltip(fn($record) => $record->employee_name)
                        ->label('Image')
                        ->size(200)->wrap(),
                    TextColumn::make('employee.name')->label('Employee')->default('--')->searchable()
                        ->color('primary')
                        ->weight(FontWeight::Bold),
                    TextColumn::make('datetime')->label('Date')->default('--')
                        ->date('Y-m-d'),
                    TextColumn::make('datetime')->label('Date')->default('--')
                        ->time('H:i:s'),
                ]),

            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 4,
            ])
            ->filters([

                Filter::make('datetime')
                    ->label(__('lang.created_at'))
                    ->schema([
                        DatePicker::make('created_from')->label(__('lang.from'))->default(now())->columnSpan(2),
                        DatePicker::make('created_until')->label(__('lang.to'))->default(now())->columnSpan(2),
                    ])->columns(4)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('datetime', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('datetime', '<=', $date),
                            );
                    })
            ], FiltersLayout::AboveContent)
            ->deferFilters(false)
        ;
    }


    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceImagesUploadeds::route('/'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
