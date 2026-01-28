<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeesWithoutUserExport implements FromCollection, WithHeadings
{
    protected $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function collection()
    {
        return $this->employees->map(function ($employee) {
            return [
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => '123456',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Password',
        ];
    }
}
