<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\AttendnaceResource;
use App\Models\Attendance;
use Filament\Forms\Components\TextInput;
use Filament\Pages\BasePage;
use Filament\Resources\Pages\Page;

class EmployeeAttendance extends BasePage
{
    // protected static string $resource = AttendnaceResource::class;
    // protected static ?string $navigationIcon = 'heroicon-o-clipboard-check';
    // protected static ?string $slug = 'employee-attendance';
    // protected static string $view = 'filament.clusters.h-r-attenance-cluster.resources.attendnace-resource.pages.employee-attendance';

    //  // Disable authentication for this page
    //  public static function shouldRegisterNavigation(): bool
    //  {
    //      return false;
    //  }

    //  public function mount()
    //  {
    //      // No authentication check
    //      $this->middleware(['guest']); // Ensure this page is for guests
    //  }

    //  protected function getFormSchema(): array
    //  {
    //      return [
    //          TextInput::make('nrfid')
    //              ->label('Employee RFID')
    //              ->required()
    //              ->placeholder('Enter RFID')
    //              ->maxLength(255),
    //      ];
    //  }

    //  public function submit()
    //  {
    //      $data = $this->form->getState();

    //      // Store the attendance entry
    //      Attendance::create([
    //          'nrfid' => $data['nrfid'],
    //          'attended_at' => now(),
    //      ]);

    //      $this->notify('success', 'Attendance recorded successfully!');
    //  }
}
