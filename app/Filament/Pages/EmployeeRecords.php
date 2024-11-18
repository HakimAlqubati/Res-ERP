<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource;
use App\Filament\Widgets\CircularWidget;
use App\Filament\Widgets\TaskWidget;
use Filament\Pages\Page;
class EmployeeRecords extends Page

{

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.employee-records';
    
    protected static ?string $slug = "employee-records";

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    
    public static function getNavigationLabel(): string
    {
       return 'My records';
    }

    public function getTitle(): string
    {
        return 'My records';
    }
     
    public function getColumns(): int | string | array
    {
        return 2;
    }
    public function getHeaderWidgets(): array
    {
        
        return [
            // EmployeeAttednaceReportResource::class,
            CircularWidget::class,
        ];
    }
}
