<?php

namespace App\Exports;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAdvanceReportResource;
use App\Filament\Clusters\HRAttendanceReport\Resources\ListEmployeeAdvanceReport;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeAdvanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected mixed $records;

    /**
     * @var array<string, Column>
     */
    protected array $columns = [];

    public function __construct(mixed $records, ?array $columns = null)
    {
        $this->records = $records;
        $this->columns = $columns ?? static::resolveTableColumns();
    }

    /**
     * Dynamically resolve all columns directly from the Resource table definition.
     *
     * @return array<string, Column>
     */
    public static function resolveTableColumns(?object $livewire = null): array
    {
        $currentLocale = app()->getLocale();
        // Ensure headings are in English as per requirements
        app()->setLocale('en');

        try {
            $page = $livewire ?? app(ListEmployeeAdvanceReport::class);
            $table = EmployeeAdvanceReportResource::table(new Table($page));
            $columns = $table->getColumns();

            // Disable UI truncation for Excel export so full values are exported
            foreach ($columns as $column) {
                if (method_exists($column, 'limit')) {
                    $column->limit(null);
                }
                if (method_exists($column, 'words')) {
                    $column->words(null);
                }
            }

            return $columns;
        } finally {
            app()->setLocale($currentLocale);
        }
    }

    public function collection(): Collection
    {
        if ($this->records instanceof Builder) {
            return $this->records->get();
        }

        if ($this->records instanceof Collection) {
            return $this->records;
        }

        return collect($this->records);
    }

    public function headings(): array
    {
        $headings = [];

        foreach ($this->columns as $name => $column) {
            $label = $column->getLabel();

            if (blank($label)) {
                $label = (string) str($name)->afterLast('.')->headline();
            }

            $headings[] = (string) $label;
        }

        return $headings;
    }

    public function map($record): array
    {
        $row = [];

        foreach ($this->columns as $column) {
            $column->record($record);
            $state = $column->getState();

            if (method_exists($column, 'formatState')) {
                $value = $column->formatState($state);
            } else {
                $value = $state;
            }

            if ($value instanceof Htmlable) {
                $value = trim(strip_tags($value->toHtml()));
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i');
            } elseif (is_array($value)) {
                $value = implode(', ', $value);
            }

            $row[] = $value ?? '';
        }

        return $row;
    }
}
