<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\HRCluster;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\CheckInstallments;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages\OrgChart;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\BranchLogRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\EmployeeFaceDataRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\LeaveBalancesRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodHistoriesRelationManager;
use App\Filament\Clusters\HRCluster\Resources\EmployeeResource\RelationManagers\PeriodRelationManager;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\Pages\CreateEmployee;
use App\Filament\Resources\EmployeeResource\Pages\EditEmployee;
use App\Filament\Resources\EmployeeResource\Pages\ListEmployees;
use App\Filament\Resources\EmployeeResource\Pages\ViewEmployee;
use App\Filament\Resources\EmployeeResource\Schemas\EmployeeForm;
use App\Filament\Resources\EmployeeResource\Tables\EmployeeTable;
use App\Models\Employee;
use App\Models\EmployeeFaceData;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

// use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $cluster = HRCluster::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = true;

    public static function getNavigationLabel(): string
    {
        return __('lang.employees');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.employees');
    }

    public static function getModelLabel(): string
    {
        return __('lang.employee');
    }

    public static function getLabel(): ?string
    {
        return __('lang.employees');
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PeriodRelationManager::class,
            PeriodHistoriesRelationManager::class,
            BranchLogRelationManager::class,
            LeaveBalancesRelationManager::class,
            // AdvanceWagesRelationM        anager::class,
            // EmployeeFaceDataRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
            'view' => ViewEmployee::route('/{record}'),
            'org_chart' => OrgChart::route('/org_chart'),
            // 'view' => Pages\ViewEmployee::route('/{record}'),
            'checkInstallments' => CheckInstallments::route('/{employeeId}/check-installments'), // Pass employee ID here

        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListEmployees::class,
            CreateEmployee::class,
            EditEmployee::class,
            // Pages\ViewEmployee::class,
        ]);
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::forBranchManager()->count();
    // }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // ->where('role_id',8)
            ->forBranchManager()
            ->with(['branch:id,name', 'pendingTerminationRequest',
                'serviceTermination'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    // public function canCreate(){
    //     return false;
    // }

    public static function canCreate(): bool
    {

        if (isSystemManager() || isSuperAdmin() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (isSystemManager() || isBranchManager() || isSuperAdmin() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canDeleteAny(): bool
    {
        if (isSystemManager() || isBranchManager() || isSuperAdmin() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (isSuperAdmin() || isBranchManager() || isSystemManager() || isStuff() || isFinanceManager() || isHR()) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isFinanceManager() || isHR()) {
            return true;
        }

        return false;
    }

    public static function avatarUploadField(): FileUpload
    {
        return FileUpload::make('avatar')
            ->image()
            ->label('')->columnSpanFull()
            // ->avatar()
            ->imageEditor()

            ->circleCropper()
            // ->disk('public')
            // ->directory('employees')
            ->visibility('public')
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ])
            ->disk('s3') // Change disk to S3
            ->directory('employees')
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                try {
                    $manager = new ImageManager(new Driver);
                    $img = $manager->read($file->get());
                    $img->scaleDown(width: 1200);
                    $encodedImage = $img->toJpeg(70);
                    $filename = 'employees/'.Str::random(15).'.jpeg';
                    Storage::disk('s3')->put($filename, (string) $encodedImage, 'public');

                    return $filename;
                } catch (\Exception $e) {
                    Log::error('Avatar Upload Error: '.$e->getMessage());
                    throw $e;
                }
            })
            // ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
            //     return Str::random(15) . "." . $file->getClientOriginalExtension();
            // })
            // ->imagePreviewHeight('250')
            // ->resize(5)
            ->maxSize(5000)
            ->columnSpan(2)
            ->reactive();
    }

    public static function storeFaceImages($employee, array $images)
    {
        DB::beginTransaction();

        try {
            foreach ($images as $image) {

                EmployeeFaceData::create([
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'employee_email' => $employee->email,
                    'employee_branch_id' => $employee->branch_id,
                    'image_path' => $image,
                    'embedding' => [],
                    'active' => true,
                ]);
            }

            DB::commit();

            Notification::make()
                ->title(__('lang.success'))
                ->body(__('lang.face_images_uploaded_successfully'))
                ->success()
                ->send();
        } catch (Throwable $th) {
            DB::rollBack();

            Log::error('Failed to store face images', [
                'employee_id' => $employee->id,
                'error' => $th->getMessage(),
            ]);

            Notification::make()
                ->title(__('lang.error'))
                ->body(__('lang.error_uploading_face_images'))
                ->danger()
                ->send();
        }
    }

    /**
     * Temporary function to simulate face embedding generation.
     */
    protected static function generateFakeEmbedding(): array
    {
        return array_map(function () {
            return round(mt_rand() / mt_getrandmax(), 6);
        }, range(1, 128));
    }

    public static function createUserForm($record = null): array
    {
        return [
            Fieldset::make()->columnSpanFull()->columns(2)->schema([

                TextInput::make('name')
                    ->default($record?->name)
                    ->required(),

                TextInput::make('email')
                    ->email()
                    ->default($record?->email)->readOnly()
                    ->unique(ignoreRecord: true) // يشيك داخل hr_employees
                    ->required(),

            ]),

        ];
    }

    public static function canForceDelete(Model $record): bool
    {
        if (isHakimOrAdel()) {
            return true;
        }

        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        if (isHakimOrAdel()) {
            return true;
        }

        return false;
    }

    public static function getGlobalSearchResultsLimit(): int
    {
        return 15;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'employee_no', 'id'];
    }
}
