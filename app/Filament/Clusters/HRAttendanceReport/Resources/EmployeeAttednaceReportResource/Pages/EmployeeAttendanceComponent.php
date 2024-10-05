<?php 
namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource\Pages;
use Livewire\Component;

class EmployeeAttendanceComponent extends Component
{
    public $showModal = false;
    public $attendanceDetails = [];

     

    public function render()
    {
        return view('filament.pages.hr-reports.attendance.pages.attendance-details-modal');
    }
}
