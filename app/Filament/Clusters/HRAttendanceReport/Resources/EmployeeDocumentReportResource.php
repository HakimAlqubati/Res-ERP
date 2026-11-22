<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttendanceReport\Resources;

use App\Filament\Clusters\HRAttendanceReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeDocumentReportResource\Pages\ListEmployeeDocumentReport;
use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeDocumentReportResource\Tables\EmployeeDocumentReportTable;
use App\Models\EmployeeFile;
use App\Modules\HR\EmployeeDocuments\Contracts\DocumentReportServiceInterface;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocumentReportResource extends Resource
{
    protected static ?string $model = EmployeeFile::class;

    protected static ?string $slug = 'employee-documents-report';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = HRAttendanceReport::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('lang.employee_documents_report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.employee_documents_report');
    }

    public static function getModelLabel(): string
    {
        return __('lang.employee_document');
    }

    public static function getLabel(): ?string
    {
        return __('lang.employee_documents_report');
    }

    public static function table(Table $table): Table
    {
        return EmployeeDocumentReportTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return app(DocumentReportServiceInterface::class)->buildQuery(
            filter: new DocumentReportFilterDTO(
                onlyActiveEmployees: null,
                onlyActiveFiles: null,
                applyAccessScope: true,
            ),
            applyDefaultOrder: false,
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeDocumentReport::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return isSuperAdmin() || isSystemManager() || isHR() || isBranchManager();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
