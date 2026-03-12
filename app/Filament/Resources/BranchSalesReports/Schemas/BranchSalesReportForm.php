<?php

namespace App\Filament\Resources\BranchSalesReports\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Log;
use App\Models\Branch;

class BranchSalesReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make()->columnSpanFull()->schema([
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
                            ->afterStateUpdated(function (Set $set, $state, callable $get) {
                                self::recalculateTotalAmount($set, $get);
                            })
                            ->required(),
                        TextInput::make('service_charge')
                            ->label('Service Charge')
                            ->numeric()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state, callable $get) {
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
                        })
                        ->hiddenOn('view'),

                    \Filament\Forms\Components\Hidden::make('document_analysis_attempt_id')
                        ->dehydrated(false),
                ])
            ]);
    }

    protected static function recalculateTotalAmount(Set $set, callable $get): void
    {
        $netSale = (float)($get('net_sale') ?? 0);
        $serviceCharge = (float)($get('service_charge') ?? 0);
        
        $total = round($netSale + $serviceCharge, 2);
        $set('total_amount', $total);
    }
}
