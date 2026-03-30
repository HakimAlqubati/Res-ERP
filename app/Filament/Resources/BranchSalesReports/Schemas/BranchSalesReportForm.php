<?php

namespace App\Filament\Resources\BranchSalesReports\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;
use Filament\Forms\Components\Placeholder;

class BranchSalesReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('reports')
                    ->label(__('Sales Reports'))
                    ->schema([
                        Grid::make(12)
                        ->columnSpanFull()
                        ->schema([
                            Grid::make(1)->columnSpan(['default' => 12, 'lg' => 8])->schema([
                                Fieldset::make('Report Details')->schema([
                                    Grid::make()->columnSpanFull()->columns(3)->schema([
                                        Select::make('branch_id')
                                            ->label('Branch')
                                            ->options(Branch::active()->pluck('name', 'id'))
                                            ->searchable()
                                            ->required(),
                                        DatePicker::make('date')
                                            ->label('Date')
                                            ->required()
                                            ->default(now())
                                            ->rule(function (callable $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $branchId = $get('branch_id');
                                                    if (!$branchId || !$value) {
                                                        return;
                                                    }

                                                    $date = \Carbon\Carbon::parse($value)->startOfDay();

                                                    $existing = \App\Models\BranchSalesReport::query()
                                                        ->withTrashed()
                                                        ->where('branch_id', $branchId)
                                                        ->whereDate('date', $date)
                                                        ->first();

                                                    if ($existing) {
                                                        if ($existing->deleted_at) {
                                                            $fail(__("A report already exists in the trash for this branch and date. Please restore or permanently delete it first."));
                                                        } else {
                                                            $fail(__("A report already exists for this branch and date."));
                                                        }
                                                    }
                                                };
                                            }),
                                        TextInput::make('status')
                                            ->label('Status')
                                            ->default(\App\Models\BranchSalesReport::STATUS_PENDING)
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),
                                    
                                    Grid::make()->columnSpanFull()->columns(3)->schema([
                                        TextInput::make('net_sale')
                                            ->label('Net Sale')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($set, $state, callable $get) {
                                                self::recalculateTotalAmount($set, $get);
                                            })
                                            ->required(),
                                        TextInput::make('service_charge')
                                            ->label('Service Charge')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($set, $state, callable $get) {
                                                self::recalculateTotalAmount($set, $get);
                                            })
                                            ->required(),
                                        TextInput::make('total_amount')
                                            ->label('Total Amount')
                                            ->numeric()
                                            ->default(0)
                                            ->readOnly()
                                            ->dehydrated()
                                            ->required(),
                                    ]),

                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->columnSpanFull(),
                                ]),
                                
                                Fieldset::make('Attachment')->schema([
                                    FileUpload::make('attachment')
                                        ->label('Attachment')
                                        ->directory('branch-sales-reports')
                                        ->columnSpanFull()
                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file): string {
                                            return (string) str($file->getClientOriginalName())->prepend('branch-sales-');
                                        })
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                            if (!$state) return;

                                            $attempt = null;
                                            $repo = app(\App\Repositories\Contracts\DocumentAnalysisAttemptRepositoryInterface::class);

                                            try {
                                                if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                    $attempt = $repo->createAttempt(
                                                        \App\Models\BranchSalesReport::class,
                                                        $record?->id,
                                                        auth()->id(),
                                                        $state->getClientOriginalName()
                                                    );
                                                    $set('document_analysis_attempt_id', $attempt->id);
                                                }

                                                $service = new \App\Services\AWS\Textract\ExtractReportSummaryService();

                                                if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                    $file = new \Illuminate\Http\UploadedFile(
                                                        $state->getRealPath(),
                                                        $state->getClientOriginalName(),
                                                        $state->getMimeType(),
                                                        $state->getError(),
                                                        true
                                                    );

                                                    $result = $service->extract($file);

                                                    if ($attempt) {
                                                        $repo->updatePayload($attempt, $result);
                                                    }

                                                    $hasData = false;
                                                    if (!empty($result['branch_name'])) {
                                                        $branchName = trim($result['branch_name']);
                                                        $branchId = \App\Models\Branch::query()
                                                            ->where('name', 'like', "%{$branchName}%")
                                                            ->orWhere('name', 'like', "{$branchName}%")
                                                            ->value('id');
                                                        if ($branchId) {
                                                            $set('branch_id', $branchId);
                                                            $hasData = true;
                                                        }
                                                    }
                                                    if (!empty($result['date'])) {
                                                        $date = date('Y-m-d', strtotime(str_replace('/', '-', $result['date'])));
                                                        $set('date', $date);
                                                        $hasData = true;
                                                    }
                                                    if (isset($result['service_charge'])) {
                                                        $set('service_charge', $result['service_charge']);
                                                        $hasData = true;
                                                    }
                                                    if (isset($result['net_sale'])) {
                                                        $set('net_sale', $result['net_sale']);
                                                        $hasData = true;
                                                    }

                                                    self::recalculateTotalAmount($set, $get);

                                                    if ($hasData) {
                                                        if ($attempt) {
                                                            $repo->markAsSuccess($attempt, $result);
                                                        }
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('✅ Report parsed successfully')
                                                            ->body('Data imported automatically from the attachment.')
                                                            ->success()
                                                            ->send();
                                                    } else {
                                                        if ($attempt) {
                                                            $repo->markAsFailed($attempt, 'No useful data found in the document.');
                                                        }
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('⚠️ No data found')
                                                            ->body('System could not extract fields from the attachment.')
                                                            ->warning()
                                                            ->send();
                                                    }
                                                }
                                            } catch (\Throwable $e) {
                                                if (isset($attempt) && $attempt) {
                                                    $repo->markAsFailed($attempt, $e->getMessage());
                                                }
                                                Log::error('failed_file', [$e->getMessage()]);
                                                \Filament\Notifications\Notification::make()
                                                    ->title('❌ Failed to parse file')
                                                    ->body($e->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                ]),

                                \Filament\Forms\Components\Hidden::make('document_analysis_attempt_id')
                                    ->dehydrated(),
                            ]),

                            Grid::make(1)->columnSpan(['default' => 12, 'lg' => 4])->schema([
                                \Filament\Schemas\Components\Section::make('Preview')
                                    ->schema([
                                        Placeholder::make('file_preview')
                                            ->hiddenLabel()
                                            ->content(function (callable $get) {
                                                $fieldPath = 'attachment';
                                                $file = $get($fieldPath);
                                                
                                                if (!$file) {
                                                    return new \Illuminate\Support\HtmlString('<div class="text-gray-400 p-8 text-center border-2 border-dashed rounded-lg">No file uploaded</div>');
                                                }

                                                if (is_array($file)) {
                                                    $file = array_values($file)[0] ?? null;
                                                }

                                                $url = null;
                                                $isPdf = false;

                                                if (is_string($file)) {
                                                    $url = \Illuminate\Support\Facades\Storage::url($file);
                                                    $isPdf = str_ends_with(strtolower($file), '.pdf');
                                                } elseif ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                    $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf' || $file->getMimeType() === 'application/pdf';
                                                    if ($isPdf) {
                                                        $path = 'tmp-previews/' . basename($file->getRealPath()) . '.pdf';
                                                        \Illuminate\Support\Facades\Storage::disk('public')->put(
                                                            $path,
                                                            fopen($file->getRealPath(), 'r')
                                                        );
                                                        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                                                    } else {
                                                        try {
                                                            $url = $file->temporaryUrl();
                                                        } catch (\Exception $e) {}
                                                    }
                                                }

                                                if (!$url) {
                                                    if ($isPdf) {
                                                        return new \Illuminate\Support\HtmlString('<div class="text-gray-400 p-8 text-center border-2 border-dashed rounded-lg">PDF file is too large for live preview. Please save to view.</div>');
                                                    }
                                                    return new \Illuminate\Support\HtmlString('<div class="text-gray-400 p-8 text-center border-2 border-dashed rounded-lg">Preview not available</div>');
                                                }

                                                if ($isPdf) {
                                                    return new \Illuminate\Support\HtmlString('<iframe src="' . $url . '" style="width: 100%; height: 50vh;" class="rounded-lg shadow border-0"></iframe>');
                                                }

                                                return new \Illuminate\Support\HtmlString('<img src="' . $url . '" style="width: 100%; max-height: 50vh; object-fit: contain;" class="rounded-lg shadow border" />');
                                            })
                                    ])->collapsible()
                            ]),
                        ]),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel(__('Add another report'))
                    ->columnSpanFull()
                    ->hiddenOn('edit'),

                Grid::make(12)
                    ->schema([
                        Grid::make(1)->columnSpan(['default' => 12, 'lg' => 8])->schema([
                            Fieldset::make('Report Details')->schema([
                                Grid::make()->columnSpanFull()->columns(3)->schema([
                                    Select::make('branch_id')
                                        ->label('Branch')
                                        ->options(Branch::active()->pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->disabledOn('edit'),
                                    DatePicker::make('date')
                                        ->label('Date')
                                        ->required()
                                        ->default(now())
                                        ->disabledOn('edit'),
                                    TextInput::make('status')
                                        ->label('Status')
                                        ->default(\App\Models\BranchSalesReport::STATUS_PENDING)
                                        ->disabled()
                                        ->dehydrated(),
                                ]),
                                
                                Grid::make()->columnSpanFull()->columns(3)->schema([
                                    TextInput::make('net_sale')
                                        ->label('Net Sale')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($set, $state, callable $get) {
                                            self::recalculateTotalAmount($set, $get);
                                        })
                                        ->required(),
                                    TextInput::make('service_charge')
                                        ->label('Service Charge')
                                        ->numeric()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($set, $state, callable $get) {
                                            self::recalculateTotalAmount($set, $get);
                                        })
                                        ->required(),
                                    TextInput::make('total_amount')
                                        ->label('Total Amount')
                                        ->numeric()
                                        ->default(0)
                                        ->readOnly()
                                        ->dehydrated()
                                        ->required(),
                                ]),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->columnSpanFull(),
                            ]),
                        ]),

                        Grid::make(1)->columnSpan(['default' => 12, 'lg' => 4])->schema([
                            \Filament\Schemas\Components\Section::make('Preview')
                                ->schema([
                                    Placeholder::make('file_preview_edit')
                                        ->hiddenLabel()
                                        ->content(function (callable $get) {
                                            $file = $get('attachment');
                                            if (!$file) return new \Illuminate\Support\HtmlString('<div class="text-gray-400 p-8 text-center border-2 border-dashed rounded-lg">No file</div>');
                                            $url = \Illuminate\Support\Facades\Storage::url($file);
                                            $isPdf = str_ends_with(strtolower($file), '.pdf');
                                            if ($isPdf) {
                                                return new \Illuminate\Support\HtmlString('<iframe src="' . $url . '" style="width: 100%; height: 50vh;" class="rounded-lg shadow border-0"></iframe>');
                                            }
                                            return new \Illuminate\Support\HtmlString('<img src="' . $url . '" style="width: 100%; max-height: 50vh; object-fit: contain;" class="rounded-lg shadow border" />');
                                        })
                                ])
                        ])
                    ])
                    ->visibleOn('edit'),
            ]);
    }

    protected static function recalculateTotalAmount(callable $set, callable $get): void
    {
        $netSale = (float)($get('net_sale') ?? 0);
        $serviceCharge = (float)($get('service_charge') ?? 0);
        
        $total = round($netSale + $serviceCharge, 2);
        $set('total_amount', $total);
    }
}
