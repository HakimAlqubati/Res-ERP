<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use App\Models\Employee;
use App\Services\HR\v2\Attendance\AttendanceServiceV2;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Filament\Support\Enums\IconSize;

class AttendanceTest extends BasePage implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Attendance Test';

    protected static ?string $title = 'Attendance Management (V2)';

    protected string $view = 'filament.pages.attendance-test';

    public ?array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_id')
                    ->label('Employee ID')
                    ->placeholder('Enter Employee ID')
                    ->numeric()
                    ->required()
                    ->autofocus()
                    ->extraInputAttributes(['autocomplete' => 'off']),

                TextInput::make('rfid')
                    ->label('RFID Code')
                    ->placeholder('Enter RFID Code')
                    ->required()
                    ->extraInputAttributes(['autocomplete' => 'off']),

                Select::make('type')
                    ->label('Type')
                    ->options([
                        'checkin' => 'Check In',
                        'checkout' => 'Check Out',
                    ])
                    ->placeholder('Optional - Auto detected')
                    ->native(false),

                DateTimePicker::make('date_time')
                    ->label('Date & Time')
                    ->default(now())
                    ->seconds(false)
                    ->native(false)
                    ->displayFormat('Y-m-d H:i')
                    ->hidden(fn() => !isSuperAdmin()),

                Select::make('attendance_type')
                    ->label('Attendance Type')
                    ->options([
                        'rfid' => 'RFID',
                        'manual' => 'Manual',
                        'biometric' => 'Biometric',
                    ])
                    ->default('rfid')
                    ->native(false)
                    ->hidden(fn() => !isSuperAdmin()),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            // إنشاء instance من service
            $attendanceService = app(AttendanceServiceV2::class);

            // معالجة البيانات
            $result = $attendanceService->handle($data);

            // إرسال رد الخدمة كإشعار
            if ($result['success']) {
                Notification::make()
                    ->title('Attendance Recorded Successfully')
                    ->body($result['message'] ?? 'Your attendance has been recorded successfully')
                    ->success()
                    ->icon('heroicon-o-check-circle')
                    ->iconSize(IconSize::Large)
                    ->duration(5000)
                    ->send();

                // إعادة تعيين النموذج
                $this->form->fill();
            } else {
                Notification::make()
                    ->title('Attendance Error')
                    ->body($result['message'] ?? 'An error occurred while recording attendance')
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
