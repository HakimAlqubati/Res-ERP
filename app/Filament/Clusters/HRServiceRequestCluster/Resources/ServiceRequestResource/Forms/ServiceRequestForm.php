<?php

namespace App\Filament\Clusters\HRServiceRequestCluster\Resources\ServiceRequestResource\Forms;

use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\ServiceRequest;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ServiceRequestForm
{
    /**
     * Configure the form schema
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    static::getBasicDataStep(),
                    static::getImagesStep(),
                ])->skippable()->columnSpanFull(),
            ]);
    }

    /**
     * Step 1: Basic Data - البيانات الأساسية
     */
    public static function getBasicDataStep(): Step
    {
        return Step::make('Basic data')
            ->icon('heroicon-o-bars-3-center-left')
            ->schema([
                Fieldset::make()->columnSpanFull()->schema([
                    // Branch, Area, Equipment Grid
                    Fieldset::make()->columnSpanFull()->columns(3)->schema([
                        static::getBranchField(),
                        static::getBranchAreaField(),
                        static::getEquipmentField(),
                        static::getDescriptionField(),
                    ]),

                    // Assignment & Status Fields
                    Fieldset::make()->columnSpanFull()->columns(4)->schema([
                        static::getAssignedToField(),
                        static::getUrgencyField(),
                        static::getImpactField(),
                        static::getStatusField(),
                    ]),
                ]),
            ]);
    }

    /**
     * Step 2: Images - الصور
     */
    public static function getImagesStep(): Step
    {
        return Step::make('Images')
            ->icon('heroicon-o-photo')
            ->schema([
                Fieldset::make()->columnSpanFull()->columns(1)->schema([
                    static::getMediaField(),
                ]),
            ]);
    }

    /**
     * Branch select field
     */
    public static function getBranchField(): Select
    {
        return Select::make('branch_id')->label('Branch')
            ->disabled(function ($record) {
                if (isset($record)) {
                    if ($record->created_by == auth()->user()->id) {
                        return false;
                    }
                    return true;
                }
            })
            ->options(Branch::active()
                ->whereIn('type', [
                    Branch::TYPE_BRANCH,
                    Branch::TYPE_CENTRAL_KITCHEN,
                    Branch::TYPE_POPUP,
                    Branch::TYPE_HQ
                ])
                ->select('name', 'id')->pluck('name', 'id'))
            ->default(function () {
                if (isStuff()) {
                    return auth()->user()->branch_id;
                }
            })
            ->live()
            ->required();
    }

    /**
     * Branch area select field
     */
    public static function getBranchAreaField(): Select
    {
        return Select::make('branch_area_id')->label('Branch area')->required()
            ->options(function (Get $get) {
                return BranchArea::query()
                    ->where('branch_id', $get('branch_id'))
                    ->pluck('name', 'id');
            })
            ->disabled(function ($record) {
                if (isset($record)) {
                    if ($record->created_by == auth()->user()->id) {
                        return false;
                    }
                    return true;
                }
            })->rule(function (Get $get) {
                $branchId = $get('branch_id');

                // تحقق من وجود مناطق للفرع المحدد
                $hasAreas = BranchArea::where('branch_id', $branchId)->exists();

                return $hasAreas
                    ? 'required'
                    : function () {
                        return fn() => false;
                    };
            })
            ->validationMessages([
                'required' => 'The branch area field is required. ⚠ Go to Branch to add Areas first.',
                '*.0'      => '',
            ]);
    }

    /**
     * Equipment select field
     */
    public static function getEquipmentField(): Select
    {
        return Select::make('equipment_id')->label('Equipment')
            ->options(function (Get $get) {
                return Equipment::query()
                    ->where('branch_id', $get('branch_id'))
                    ->where('branch_area_id', $get('branch_area_id'))
                    ->pluck('name', 'id');
            })->required()
            ->searchable();
    }

    /**
     * Description textarea field
     */
    public static function getDescriptionField(): Textarea
    {
        return Textarea::make('description')->label('')->required()
            ->helperText('Description of service request')
            ->columnSpanFull()
            ->maxLength(500);
    }

    /**
     * Assigned to select field
     */
    public static function getAssignedToField(): Select
    {
        return Select::make('assigned_to')
            ->options(fn(Get $get): Collection => Employee::query()
                ->where('active', 1)
                ->where('branch_id', $get('branch_id'))
                ->pluck('name', 'id'))
            ->searchable()
            ->hidden(fn() => request()->has('equipment_id'))
            ->helperText(function (Model $record = null) {
                if ($record) {
                    return 'To reassign, go to table page ';
                }
            })
            ->nullable();
    }

    /**
     * Urgency select field
     */
    public static function getUrgencyField(): Select
    {
        return Select::make('urgency')
            ->options([
                ServiceRequest::URGENCY_HIGH   => 'High',
                ServiceRequest::URGENCY_MEDIUM => 'Medium',
                ServiceRequest::URGENCY_LOW    => 'Low',
            ])
            ->disabled(function () {
                if (isset($record)) {
                    if ($record->created_by == auth()->user()->id) {
                        return false;
                    }
                    return true;
                }
            })
            ->required();
    }

    /**
     * Impact select field
     */
    public static function getImpactField(): Select
    {
        return Select::make('impact')
            ->options([
                ServiceRequest::IMPACT_HIGH   => 'High',
                ServiceRequest::IMPACT_MEDIUM => 'Medium',
                ServiceRequest::IMPACT_LOW    => 'Low',
            ])
            ->disabled(function () {
                if (isset($record)) {
                    if ($record->created_by == auth()->user()->id) {
                        return false;
                    }
                    return true;
                }
            })
            ->required();
    }

    /**
     * Status select field
     */
    public static function getStatusField(): Select
    {
        return Select::make('status')
            ->default(ServiceRequest::STATUS_NEW)
            ->options([
                ServiceRequest::STATUS_NEW         => 'New',
                ServiceRequest::STATUS_PENDING     => 'Pending',
                ServiceRequest::STATUS_IN_PROGRESS => 'In progress',
                ServiceRequest::STATUS_CLOSED      => 'Closed',
            ])->disabled()
            ->helperText(function (Model $record = null) {
                if ($record) {
                    return 'To change status, go to table page ';
                }
            })
            ->required();
    }

    /**
     * Media upload field - الصور
     */
    public static function getMediaField(): FileUpload
    {
        return FileUpload::make('images')
            ->disk('public')
            ->label('')
            ->directory('service-requests')
            ->columnSpanFull()
            ->image()
            ->multiple()
            ->downloadable()
            ->appendFiles()
            ->previewable()
            ->imagePreviewHeight('250')
            ->loadingIndicatorPosition('right')
            ->panelLayout('integrated')
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('right')
            ->uploadProgressIndicatorPosition('right')
            ->panelLayout('grid')
            ->reorderable()
            ->openable()
            ->downloadable(true)
            ->previewable(true)
            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                return (string) str($file->getClientOriginalName())->prepend('service-');
            })
            ->imageEditor()
            ->imageEditorAspectRatios([
                '16:9',
                '4:3',
                '1:1',
            ])->maxSize(800)
            ->imageEditorMode(2)
            ->imageEditorEmptyFillColor('#fff000')
            ->circleCropper();
    }
}
