<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use App\Models\Employee;
use App\Modules\HR\Attendance\Services\AttendanceService;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\IconSize;

class AttendanceTest extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';
    // protected static string | \UnitEnum | null $navigationGroup = 'Inventory Management';

    protected static ?string $navigationLabel = 'Attendance Test';

    protected static ?string $title = 'Attendance Management (V2)';

    protected   string $view = 'filament.pages.attendance-test';

    public ?array $data = [];
    public bool $showTypeField = false;
    public bool $showPeriodField = false;
    public array $periodOptions = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employee')
                    ->options(
                        Employee::query()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($employee) {
                                return [$employee->id => $employee->name . ' - ' . $employee->id];
                            })
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(3)
                    ->required()
                    ->placeholder('Search for employee...')
                    ->native(false),

                Select::make('type')
                    ->label('Type')
                    ->options([
                        'checkin' => 'Check In',
                        'checkout' => 'Check Out',
                    ])
                    ->placeholder('Optional - Auto detected')
                    ->visible(fn() => $this->showTypeField)
                    // ->required(fn() => $this->showTypeField)
                    ->native(false),

                Select::make('period_id')
                    ->label('Select Shift')
                    ->options($this->periodOptions)
                    ->visible(fn() => $this->showPeriodField)
                // ->required(fn() => $this->showPeriodField)
                // ->native(false)
                ,

                DateTimePicker::make('date_time')
                    ->label('Date & Time')
                    ->default(now())
                // ->hidden(fn() => !isSuperAdmin())
                ,
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            // إنشاء instance من service
            $attendanceService = app(AttendanceService::class);

            // معالجة البيانات
            $result = $attendanceService->handle($data);

            // التعامل مع حالة طلب تحديد النوع
            if ($result->typeRequired) {
                $this->showTypeField = true;

                Notification::make()
                    ->title('Type Selection Required')
                    ->body($result->message ?? 'Please select the attendance type (Check-in / Check-out) manually.')
                    ->warning()
                    ->icon('heroicon-o-question-mark-circle')
                    ->iconSize(IconSize::Large)
                    ->duration(10000)
                    ->send();

                return;
            }

             // Dealing with shift selection or conflict
            if ($result->shiftSelectionRequired || ($result->shiftConflictDetected ?? false)) {
                $this->showPeriodField = true;

                // Transform shifts array to Select options
                $periods = $result->conflictOptions ?? $result->availableShifts;
                $this->periodOptions = collect($periods ?? [])
                    ->mapWithKeys(function ($shift) {
                        // Handle array or object
                        $shift = (array) $shift;
                        $label = ($shift['name'] ?? '') . ' (' . ($shift['status'] ?? '') . ')';
                        return [$shift['period_id'] => $label];
                    })
                    ->toArray();

                Notification::make()
                    ->title(($result->shiftConflictDetected ?? false) ? 'Shift Conflict / Selection Required' : 'Shift Selection Required')
                    ->body($result->message ?? 'Multiple shifts found or conflict detected. Please select one.')
                    ->warning()
                    ->icon('heroicon-o-clock')
                    ->iconSize(IconSize::Large)
                    ->duration(10000)
                    ->send();

                return;
            }

            // إرسال رد الخدمة كإشعار
            if ($result->success) {
                // إخفاء الحقول عند النجاح
                $this->showTypeField = false;
                $this->showPeriodField = false;
                $this->periodOptions = [];

                Notification::make()
                    ->title('Attendance Recorded Successfully')
                    ->body($result->message ?? 'Your attendance has been recorded successfully')
                    ->success()
                    ->icon('heroicon-o-check-circle')
                    ->iconSize(IconSize::Large)
                    ->duration(5000)
                    ->send();

                // إعادة تعيين النموذج
                // $this->form->fill();
            } else {
                Notification::make()
                    ->title('Attendance Error')
                    ->body($result->message ?? 'An error occurred while recording attendance')
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconSize(IconSize::Large)
                    ->duration(7000)
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('System Error')
                ->body($e->getMessage())
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->iconSize(IconSize::Large)
                ->duration(10000)
                ->send();
        }
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label('Submit Attendance')
                ->icon('heroicon-o-finger-print')
                ->size('xl')
                ->submit('submit'),
        ];
    }
}
