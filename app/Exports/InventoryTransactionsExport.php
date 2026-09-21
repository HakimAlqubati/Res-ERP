<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryTransactionsExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, // Product Code
            'B' => 26, // Product
            'C' => 20, // Store
            'D' => 15, // Movement Type
            'E' => 12, // Quantity
            'F' => 16, // Remaining Quantity
            'G' => 12, // Unit
            'H' => 14, // Package Size
            'I' => 12, // Price
            'J' => 14, // Total Price
            'K' => 15, // Movement Date
            'L' => 15, // Transaction Date
            'M' => 25, // Notes
            'N' => 15, // Transaction ID
            'O' => 22, // Transaction Type
            'P' => 20, // Created At
        ];
    }

    public function collection()
    {
        if ($this->records instanceof Builder) {
            return $this->records->with(['product', 'store', 'unit'])->get();
        }

        if ($this->records instanceof Collection) {
            return $this->records->loadMissing(['product', 'store', 'unit']);
        }

        return collect($this->records);
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product',
            'Store',
            'Movement Type',
            'Quantity',
            'Remaining Quantity',
            'Unit',
            'Package Size',
            'Price',
            'Total Price',
            'Movement Date',
            'Transaction Date',
            'Notes',
            'Transaction ID',
            'Transaction Type',
            'Created At',
        ];
    }

    public function map($record): array
    {
        $movementType = $record->movement_type_title ?? ucfirst((string) ($record->movement_type ?? ''));
        $transactionType = $record->formatted_transactionable_type ?? ($record->transactionable_type ? class_basename($record->transactionable_type) : '');

        $movementDate = '';
        if ($record->movement_date) {
            try {
                $movementDate = Carbon::parse($record->movement_date)->format('Y-m-d');
            } catch (\Throwable) {
                $movementDate = (string) $record->movement_date;
            }
        }

        $transactionDate = '';
        if ($record->transaction_date) {
            try {
                $transactionDate = Carbon::parse($record->transaction_date)->format('Y-m-d');
            } catch (\Throwable) {
                $transactionDate = (string) $record->transaction_date;
            }
        }

        return [
            $record->product?->code ?? '',
            $record->product?->name ?? '',
            $record->store?->name ?? '',
            $movementType,
            $record->quantity,
            $record->remaining_quantity,
            $record->unit?->name ?? '',
            $record->package_size,
            $record->price,
            $record->total_price,
            $movementDate,
            $transactionDate,
            $record->notes ?? '',
            $record->transactionable_id ?? '',
            $transactionType,
            $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
