<?php

namespace App\Filament\Resources\AppLogs\Tables;

use App\Models\AppLog;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AppLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->striped()->deferFilters(false)
            ->paginated([10, 25, 50, 100, 150])

            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('level')
                    ->label('Level')
                    ->sortable()
                    ->colors([
                        'gray' => fn(string $state): bool =>
                        strtolower($state) === AppLog::LEVEL_DEBUG,

                        'success' => fn(string $state): bool =>
                        in_array(strtolower($state), [
                            AppLog::LEVEL_INFO,
                            AppLog::LEVEL_NOTICE,
                        ], true),

                        'warning' => fn(string $state): bool =>
                        strtolower($state) === AppLog::LEVEL_WARNING,

                        'danger' => fn(string $state): bool =>
                        in_array(strtolower($state), [
                            AppLog::LEVEL_ERROR,
                            AppLog::LEVEL_CRITICAL,
                            AppLog::LEVEL_ALERT,
                            AppLog::LEVEL_EMERGENCY,
                        ], true),
                    ])
                    ->formatStateUsing(fn(string $state) => strtoupper($state)),
                TextColumn::make('context')
                    ->label('Context')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(40)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn($record) => $record->message),

                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->copyable()
                    ->copyMessage('IP copied')
                    ->copyMessageDuration(1500)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                static::contextFilter(),

            ], FiltersLayout::Modal)
            ->recordActions([
                // ViewAction::make(),
                static::viewExtraAction(), // استدعاء الزر الستاتيك
            ])

            ->toolbarActions([]);
    }

    /**
     * زر عرض بيانات extra في مودال بشكل منظم.
     */
    public static function viewExtraAction(): Action
    {
        return Action::make('viewExtra')
            ->label('Extra')
            ->icon('heroicon-o-code-bracket-square')
            ->color('gray')
            ->modalHeading('Extra Data')
            ->modalWidth('2xl')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(function (AppLog $record) {
                if (empty($record->extra)) {
                    return 'No extra data.';
                }

                // 1. فك تشفير بيانات extra
                $extraData = $record->extra;

                $employeeName = null;

                // 2. التحقق مما إذا كانت البيانات تحتوي على employee_id
                if (isset($extraData['employee_id'])) {
                    // 3. البحث عن الموظف باستخدام employee_id
                    // تأكد من استبدال 'App\Models\Employee' بالمسار الصحيح لموديل الموظفين لديك إذا كان مختلفًا
                    $employee = \App\Models\Employee::find($extraData['employee_id']);

                    if ($employee) {
                        // 4. الحصول على اسم الموظف
                        $employeeName = $employee->name;
                    }
                }

                // 5. تهيئة بيانات JSON للعرض
                $json = json_encode(
                    $extraData,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                // 6. بناء محتوى المودال، بما في ذلك اسم الموظف إذا وجد
                $content = '';

                if ($employeeName) {
                    $content .= '<div class="mb-4 p-3 bg-green-100 dark:bg-green-900 rounded-lg">';
                    $content .= '<p class="text-sm font-semibold text-green-800 dark:text-green-200">';
                    $content .= 'Employee ID: ' . $extraData['employee_id'] . ' &bull; ';
                    $content .= 'Employee Name: ' . e($employeeName);
                    $content .= '</p>';
                    $content .= '</div>';
                }

                $content .= '<pre class="text-xs whitespace-pre-wrap bg-gray-50 dark:bg-gray-900 p-4 rounded-lg overflow-auto max-h-[500px]">';
                $content .= e($json);
                $content .= '</pre>';

                return new HtmlString($content);
            });
    }   
    public static function contextFilter(): SelectFilter
    {
        return SelectFilter::make('context')
            ->label('Context')
            ->options(function () {

                // جلب جميع السياقات غير الفارغة
                $options = AppLog::query()
                    ->whereNotNull('context')
                    ->where('context', '!=', '')
                    ->distinct()
                    ->orderBy('context')
                    ->pluck('context', 'context')
                    ->toArray();

                // إضافة خيار "بدون سياق"
                $options['__empty__'] = 'Other';

                return $options;
            })
            ->placeholder('All Contexts')
            ->query(function ($query, $value) {

                // إذا اختار "بدون سياق"
                if ($value === '__empty__') {
                    return $query->where(function ($q) {
                        $q->whereNull('context')->orWhere('context', '');
                    });
                }

                // فلترة عادية
                if ($value) {
                    return $query->where('context', $value);
                }

                return $query;
            });
    }
}
