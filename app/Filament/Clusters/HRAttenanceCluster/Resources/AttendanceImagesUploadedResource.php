<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources;

use App\Filament\Clusters\HRAttenanceCluster;
use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendanceImagesUploadedResource\Pages;
use App\Models\AttendanceImagesUploaded;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceImagesUploadedResource extends Resource
{
    protected static ?string $model = AttendanceImagesUploaded::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 20;


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
                    ->form([
                        DatePicker::make('created_from')->label(__('lang.from'))->default(now()),
                        DatePicker::make('created_until')->label(__('lang.to'))->default(now()),
                    ])
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
            ])
        ;
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceImagesUploadeds::route('/'),
        ];
    }
}
