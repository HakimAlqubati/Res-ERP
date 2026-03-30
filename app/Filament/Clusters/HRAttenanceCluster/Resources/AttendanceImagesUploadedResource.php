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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceImagesUploadedResource extends Resource
{
    protected static ?string $model = AttendanceImagesUploaded::class;

    protected static string | \BackedEnum | null $navigationIcon =  Heroicon::Photo;

    protected static ?string $cluster = HRAttenanceCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('lang.attendance_image');
    }

    public static function getPluralLabel(): string
    {
        return __('lang.attendance_images');
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.attendance_images');
    }

    protected static bool $shouldRegisterNavigation = true;

    public static function table(Table $table): Table
    {
        return $table->striped()
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                Stack::make([
                    ImageColumn::make('full_image_url')->circular(false)
                        ->tooltip(
                            fn($record) =>
                            "ID: (" . ($record->employee?->id ?? '--') . "), Employee No: (" . ($record->employee?->employee_no ?? '--') . "), Nationality: (" . ($record->employee?->nationality ?? '--') . "), Branch: (" . ($record->employee?->branch?->name ?? '--') . "), Job Title: (" . ($record->employee?->job_title ?? '--') . ")"
                        )

                        ->label(__('lang.image'))
                        ->size(200)->wrap()
                        ->extraImgAttributes(
                            fn($record) => $record->attendances()->doesntExist()
                                ? ['style' => 'border: 3px solid red; border-radius: 8px;']
                                : ['style' => 'border: 3px solid green; border-radius: 8px;']
                        ),
                    TextColumn::make('employee.name')->label(__('lang.employee'))->default('--')->searchable()
                        ->color('primary')
                        ->weight(FontWeight::Bold),
                    TextColumn::make('datetime')->label(__('lang.date'))
                        ->date('Y-m-d')

                    // ->hidden(fn($state) => blank($state))
                    ,
                    TextColumn::make('datetime')->label(__('lang.date'))
                        ->time('H:i:s')
                    // ->hidden(fn($state) => blank($state))
                    ,

                    TextColumn::make('attendances.check_date')->label(__('lang.check_date'))->placeholder('--')
                        ->date('Y-m-d')
                        ->hidden(fn($state) => blank($state)),
                    TextColumn::make('attendances.check_time')->label(__('lang.check_time'))
                        ->placeholder('--')
                        ->hidden(fn($state) => blank($state)),
                    TextColumn::make('attendances.check_type')
                        ->label(__('lang.check_type'))
                        ->badge()
                        ->formatStateUsing(fn($state) => $state === 'checkin' ? __('lang.checkin') : ($state === 'checkout' ? __('lang.checkout') : $state))
                        ->color(fn($state) => $state === 'checkin' ? 'success' : ($state === 'checkout' ? 'danger' : 'gray'))
                        ->hidden(fn($state) => blank($state)),
                    TextColumn::make('attendances.status')
                        ->label(__('lang.status'))
                        ->badge()
                        ->hidden(fn($state) => blank($state))
                        ->formatStateUsing(fn($state) => \App\Models\Attendance::getStatusLabel($state))
                        ->color(fn($state) => \App\Models\Attendance::getStatusColor($state))
                        ->hidden(),
                ]),

            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 4,
            ])
            ->filters([

                Filter::make('has_accepted_attendance')
                    ->label(__('lang.has_accepted_attendance'))
                    ->toggle()
                    ->default(true)
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('attendances', function (Builder $q) {
                            $q->where('accepted', 1);
                        });
                    }),

                Filter::make('datetime')
                    ->label(__('lang.created_at'))
                    ->schema([
                        DatePicker::make('created_from')->label(__('lang.from'))->default(now()->subMonth(1))->columnSpan(2),
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
                    }),
                SelectFilter::make('employee_id')->label(__('lang.employee'))
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->options(function () {
                        return \App\Models\Employee::orderBy('name')
                            ->active()
                            // ->forBranch('branch_id')
                            ->forBranchManager()
                            ->pluck('name', 'id')->toArray();
                    }),
                
                SelectFilter::make('employee_gender')
                    ->label(__('lang.gender'))
                    ->options([
                        '1' => __('lang.male'),
                        '0' => __('lang.female'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['value']) && $data['value'] !== '') {
                            $query->whereHas('employee', function (Builder $q) use ($data) {
                                $q->where('gender', $data['value']);
                            });
                        }
                        return $query;
                    })
                    ->visible(fn () => auth()->user()?->email === 'hakimahmed123321@gmail.com'),
            ], FiltersLayout::Modal)->filtersFormColumns(3)
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

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isHR()) {
            return true;
        }
        return false;
    }
}
