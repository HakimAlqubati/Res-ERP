<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources\TenantInvoices\Schemas;

use App\Models\TenantInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TenantInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Invoice Information'))
                    ->description(__('Select the subscriber, billing amount, and schedule dates.'))
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            TextInput::make('invoice_number')
                                ->label(__('Invoice Number'))
                                ->prefixIcon(Heroicon::OutlinedHashtag)
                                ->trim()
                                ->required()
                                ->maxLength(255)
                                ->unique(table: TenantInvoice::class, column: 'invoice_number', ignoreRecord: true),

                            Select::make('tenant_id')
                                ->label(__('Tenant'))
                                ->relationship('tenant', 'name')
                                ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required(),

                            TextInput::make('amount')
                                ->label(__('Amount'))
                                ->prefixIcon(Heroicon::OutlinedBanknotes)
                                ->placeholder('0.00')
                                ->numeric()
                                ->minValue(0.01)
                                ->step('0.01')
                                ->rule('gt:0')
                                ->required(),

                            DatePicker::make('invoice_date')
                                ->label(__('Invoice Date'))
                                ->prefixIcon(Heroicon::OutlinedCalendar)
                                ->default(today())
                                ->required(),

                            DatePicker::make('due_date')
                                ->label(__('Due Date'))
                                ->prefixIcon(Heroicon::OutlinedCalendarDays)
                                ->afterOrEqual('invoice_date')
                                ->required(),

                            TextInput::make('warning_days')
                                ->label(__('Warning Days'))
                                ->prefixIcon(Heroicon::OutlinedBellAlert)
                                ->suffix(__('days'))
                                ->integer()
                                ->minValue(0)
                                ->maxValue(365)
                                ->default(fn (): int => TenantInvoice::getDefaultWarningDays())
                                ->required(),
                        ]),

                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            ToggleButtons::make('status')
                                ->label(__('Status'))
                                ->inline()
                                ->options(TenantInvoice::getStatusOptions())
                                ->colors([
                                    TenantInvoice::STATUS_UNPAID => 'warning',
                                    TenantInvoice::STATUS_PAID => 'success',
                                    TenantInvoice::STATUS_CANCELLED => 'danger',
                                ])
                                ->icons([
                                    TenantInvoice::STATUS_UNPAID => Heroicon::OutlinedClock,
                                    TenantInvoice::STATUS_PAID => Heroicon::OutlinedCheckCircle,
                                    TenantInvoice::STATUS_CANCELLED => Heroicon::OutlinedXCircle,
                                ])
                                ->default(TenantInvoice::STATUS_UNPAID)
                                ->live()
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    if ($state === TenantInvoice::STATUS_PAID) {
                                        if (blank($get('paid_at'))) {
                                            $set('paid_at', today()->toDateString());
                                        }
                                    } else {
                                        $set('paid_at', null);
                                    }
                                })
                                ->required(),

                            DatePicker::make('paid_at')
                                ->label(__('Payment Date'))
                                ->prefixIcon(Heroicon::OutlinedCalendar)
                                ->visible(fn (Get $get): bool => $get('status') === TenantInvoice::STATUS_PAID)
                                ->required(fn (Get $get): bool => $get('status') === TenantInvoice::STATUS_PAID)
                                ->nullable(),

                            Textarea::make('notes')
                                ->label(__('Notes'))
                                ->placeholder(__('Optional payment reference, bank transfer details, or internal remarks...'))
                                ->rows(2)
                                ->nullable()
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
