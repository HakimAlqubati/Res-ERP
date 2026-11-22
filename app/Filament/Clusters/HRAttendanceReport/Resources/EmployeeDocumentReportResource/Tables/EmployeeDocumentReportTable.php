<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeDocumentReportResource\Tables;

use App\Filament\Resources\EmployeeResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeFile;
use App\Modules\HR\EmployeeDocuments\Builders\EmployeeDocumentQueryBuilder;
use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use App\Modules\HR\EmployeeDocuments\Services\DocumentRenewalService;
use App\Modules\HR\EmployeeDocuments\Support\DocumentStatusCalculator;
use App\Modules\HR\EmployeeDocuments\Support\EmployeeFileTypeCatalog;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

final class EmployeeDocumentReportTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->deferLoading()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->defaultKeySort(false)
            ->defaultSort(fn (Builder $query): Builder => EmployeeDocumentQueryBuilder::applyUrgencyOrder($query))
            ->emptyStateHeading(__('lang.no_data'))
            ->columns([
                TextColumn::make('employee_no')
                    ->label(__('lang.employee_no'))
                    ->alignCenter()
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('emp.employee_no', $direction))
                    ->searchable(query: fn (Builder $q, string $search): Builder => $q->where('emp.employee_no', 'like', "%{$search}%"))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('employee_name')
                    ->label(__('lang.employee'))
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('emp.name', $direction))
                    ->searchable(query: fn (Builder $q, string $search): Builder => $q->where('emp.name', 'like', "%{$search}%"))
                    ->limit(28)
                    ->tooltip(fn (EmployeeFile $record): ?string => $record->employee_name),

                TextColumn::make('branch_name')
                    ->label(__('lang.branch'))
                    ->placeholder('-')
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('br.name', $direction))
                    ->searchable(query: fn (Builder $q, string $search): Builder => $q->where('br.name', 'like', "%{$search}%"))
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('employee_nationality')
                    ->label(__('lang.nationality'))
                    ->placeholder('-')
                    ->alignCenter()
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('emp.nationality', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('file_type_name')
                    ->label(__('lang.file_type'))
                    ->badge()
                    ->color('info')
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('ft.name', $direction))
                    ->searchable(query: fn (Builder $q, string $search): Builder => $q->where('ft.name', 'like', "%{$search}%")),

                TextColumn::make('issue_date')
                    ->label(__('lang.issue_date'))
                    ->date('Y-m-d')
                    ->placeholder('-')
                    ->alignCenter()
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('hr_employee_files.issue_date', $direction))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expiry_date')
                    ->label(__('lang.expiry_date'))
                    ->date('Y-m-d')
                    ->placeholder('-')
                    ->alignCenter()
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q->orderBy('hr_employee_files.expiry_date', $direction)),

                TextColumn::make('remaining_days')
                    ->label(__('lang.remaining_days'))
                    ->alignCenter()
                    ->placeholder('-')
                    ->formatStateUsing(function (EmployeeFile $record): string {
                        $days = app(DocumentStatusCalculator::class)->remainingDays(
                            (bool) $record->file_type_has_expiry,
                            $record->expiry_date,
                        );

                        if ($days === null) {
                            return '-';
                        }

                        if ($days < 0) {
                            return __('lang.days_overdue', ['days' => abs($days)]);
                        }

                        if ($days === 0) {
                            return __('lang.expires_today');
                        }

                        return __('lang.days_left', ['days' => $days]);
                    })
                    ->color(fn (EmployeeFile $record): string => self::resolveStatus($record)->color())
                    ->sortable(query: fn (Builder $q, string $direction): Builder => $q
                        ->orderByRaw('hr_employee_files.expiry_date IS NULL ASC')
                        ->orderBy('hr_employee_files.expiry_date', $direction)),

                TextColumn::make('expiry_status')
                    ->label(__('lang.status'))
                    ->badge()
                    ->alignCenter()
                    ->getStateUsing(fn (EmployeeFile $record): string => self::resolveStatus($record)->label())
                    ->color(fn (EmployeeFile $record): string => self::resolveStatus($record)->color())
                    ->icon(fn (EmployeeFile $record): string => self::resolveStatus($record)->icon())
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('active')
                    ->label(__('lang.active'))
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferFilters(true)
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('lang.branch'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(function (): array {
                        $query = Branch::query()->active()->orderBy('name');

                        if (! (isSuperAdmin() || isSystemManager() || isHR())) {
                            $branchIds = auth()->user()?->all_branch_ids ?? [];
                            $query->whereIn('id', $branchIds === [] ? [-1] : $branchIds);
                        }

                        return $query->pluck('name', 'id')->all();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $values = array_filter((array) ($data['values'] ?? []));

                        return $values !== []
                            ? $query->whereIn('emp.branch_id', $values)
                            : $query;
                    })
                    ->columnSpan(2),

                SelectFilter::make('file_type_id')
                    ->label(__('lang.file_type'))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => app(EmployeeFileTypeCatalog::class)->allOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $values = array_filter((array) ($data['values'] ?? []));

                        return $values !== []
                            ? $query->whereIn('hr_employee_files.file_type_id', $values)
                            : $query;
                    })
                    ->columnSpan(2),

                SelectFilter::make('employee_id')
                    ->label(__('lang.employee'))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        $query = Employee::query();

                        if (! (isSuperAdmin() || isSystemManager() || isHR())) {
                            $branchIds = auth()->user()?->all_branch_ids ?? [];
                            $query->whereIn('branch_id', $branchIds === [] ? [-1] : $branchIds);
                        }

                        return $query
                            ->where(function (Builder $q) use ($search): void {
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('employee_no', 'like', "%{$search}%");
                            })
                            ->limit(25)
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->getOptionLabelUsing(fn ($value): ?string => Employee::query()->whereKey($value)->value('name'))
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('hr_employee_files.employee_id', $data['value'])
                        : $query)
                    ->columnSpan(2),

                SelectFilter::make('nationality')
                    ->label(__('lang.nationality'))
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => getNationalities())
                    ->query(function (Builder $query, array $data): Builder {
                        $values = array_filter((array) ($data['values'] ?? []));

                        return $values !== []
                            ? $query->whereIn('emp.nationality', $values)
                            : $query;
                    })
                    ->columnSpan(2),

                SelectFilter::make('expiry_status_filter')
                    ->label(__('lang.document_status'))
                    ->options(DocumentExpiryStatus::options())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = DocumentExpiryStatus::tryFrom((string) ($data['value'] ?? ''));

                        return $status !== null
                            ? EmployeeDocumentQueryBuilder::applyStatusCondition($query, $status)
                            : $query;
                    })
                    ->columnSpan(2),

                SelectFilter::make('employee_active')
                    ->label(__('lang.employee_status'))
                    ->options([
                        '1' => __('lang.active'),
                        '0' => __('lang.terminated'),
                        'all' => __('lang.all'),
                    ])
                    ->default('1')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? '1') {
                            '1' => $query->where('emp.active', 1),
                            '0' => $query->where('emp.active', 0),
                            default => $query,
                        };
                    })
                    ->columnSpan(1),

                SelectFilter::make('file_active')
                    ->label(__('lang.file_details'))
                    ->options([
                        '1' => __('lang.active_documents'),
                        '0' => __('lang.archived_documents'),
                        'all' => __('lang.all'),
                    ])
                    ->default('1')
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? '1') {
                            '1' => $query->where('hr_employee_files.active', 1),
                            '0' => $query->where('hr_employee_files.active', 0),
                            default => $query,
                        };
                    })
                    ->columnSpan(1),

                Filter::make('expiry_range')
                    ->schema([
                        DatePicker::make('expiry_from')->label(__('lang.expiry_date_from')),
                        DatePicker::make('expiry_to')->label(__('lang.expiry_date_to')),
                    ])
                    ->columns(2)
                    ->columnSpan(4)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['expiry_from'] ?? null),
                                fn (Builder $q): Builder => $q->where('hr_employee_files.expiry_date', '>=', $data['expiry_from'])
                            )
                            ->when(
                                filled($data['expiry_to'] ?? null),
                                fn (Builder $q): Builder => $q->where('hr_employee_files.expiry_date', '<=', $data['expiry_to'])
                            );
                    }),
            ], FiltersLayout::Modal)
            ->filtersFormColumns(6)
            ->filtersFormWidth(Width::FiveExtraLarge)
            ->recordActions([
                ActionGroup::make([
                    Action::make('view_attachment')
                        ->label(__('lang.view_attachment'))
                        ->icon('heroicon-o-paper-clip')
                        ->color('info')
                        ->url(fn (EmployeeFile $record): ?string => self::resolveAttachmentUrl($record->attachment))
                        ->openUrlInNewTab()
                        ->visible(fn (EmployeeFile $record): bool => filled($record->attachment)),

                    Action::make('view_details')
                        ->label(__('lang.view_details'))
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn (EmployeeFile $record): string => ($record->file_type_name ?? '') . ' — ' . ($record->employee_name ?? ''))
                        ->modalSubmitAction(false)
                        ->infolist([
                            KeyValueEntry::make('dynamic_field_values')
                                ->label(__('lang.additional_fields'))
                                ->state(function (EmployeeFile $record): array {
                                    $values = is_array($record->dynamic_field_values) ? $record->dynamic_field_values : [];
                                    $formatted = [];

                                    foreach ($values as $key => $value) {
                                        $label = ucfirst(str_replace('_', ' ', (string) $key));
                                        $formatted[$label] = is_scalar($value) ? (string) $value : json_encode($value);
                                    }

                                    return $formatted;
                                }),
                        ])
                        ->visible(fn (EmployeeFile $record): bool => is_array($record->dynamic_field_values) && $record->dynamic_field_values !== []),

                    Action::make('renew_document')
                        ->label(__('lang.renew_document'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->modalHeading(__('lang.renew_document'))
                        ->schema([
                            DatePicker::make('issue_date')
                                ->label(__('lang.issue_date'))
                                ->live()
                                ->nullable(),
                            DatePicker::make('expiry_date')
                                ->label(__('lang.expiry_date'))
                                ->required()
                                ->minDate(fn (Get $get) => $get('issue_date') ?: null)
                                ->rules([
                                    fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                                        $issueDate = $get('issue_date');
                                        if (! empty($issueDate) && ! empty($value) && $value < $issueDate) {
                                            $fail(__('lang.expiry_date_must_be_after_issue_date'));
                                        }
                                    },
                                ]),
                            FileUpload::make('attachment')
                                ->label(__('lang.attach_file'))
                                ->downloadable()
                                ->previewable()
                                ->maxSize(20000),
                            Textarea::make('description')
                                ->label(__('lang.description'))
                                ->rows(2),
                        ])
                        ->action(function (EmployeeFile $record, array $data): void {
                            app(DocumentRenewalService::class)->renew($record, $data);

                            Notification::make()
                                ->title(__('lang.success'))
                                ->body(__('lang.document_renewed_successfully'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (EmployeeFile $record): bool => (bool) $record->active
                            && (bool) $record->file_type_has_expiry
                            && (isSuperAdmin() || isSystemManager() || isHR())),

                    Action::make('view_employee')
                        ->label(__('lang.view_employee'))
                        ->icon('heroicon-o-user')
                        ->color('primary')
                        ->url(fn (EmployeeFile $record): string => EmployeeResource::getUrl('view', ['record' => $record->employee_id]))
                        ->openUrlInNewTab(),
                ]),
            ]);
    }

    private static function resolveStatus(EmployeeFile $record): DocumentExpiryStatus
    {
        return app(DocumentStatusCalculator::class)->resolveStatus(
            hasExpiry: (bool) $record->file_type_has_expiry,
            expiryDate: $record->expiry_date,
            alertDays: isset($record->file_type_alert_days) ? (int) $record->file_type_alert_days : null,
        );
    }

    private static function resolveAttachmentUrl(?string $attachment): ?string
    {
        if (blank($attachment)) {
            return null;
        }

        $disk = (string) config('filament.default_filesystem_disk', 'public');

        try {
            return Storage::disk($disk)->url($attachment);
        } catch (\Throwable) {
            return Storage::disk('public')->url($attachment);
        }
    }
}
