<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\Page;

class OrgChart extends Page
{
    protected static string $resource = EmployeeResource::class;

    protected static string $view = 'filament.clusters.h-r-cluster.resources.employee-resource.pages.org-chart';

    // If you need to pass data to the view, you can define a method here
    public function getEmployees()
    {
        return [
            [
                'name' => 'CEO',
                'children' => [
                    [
                        'name' => 'CTO',
                        'children' => [
                            ['name' => 'Senior Developer'],
                            ['name' => 'Junior Developer'],
                        ],
                    ],
                    [
                        'name' => 'CFO',
                        'children' => [
                            ['name' => 'Accountant'],
                            ['name' => 'Financial Analyst'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
