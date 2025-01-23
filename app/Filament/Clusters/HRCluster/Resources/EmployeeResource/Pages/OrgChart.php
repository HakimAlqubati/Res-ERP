<?php

namespace App\Filament\Clusters\HRCluster\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Services\BranchTreeService;
use Filament\Resources\Pages\Page;

class OrgChart extends Page
{
    protected static string $resource = EmployeeResource::class;
    protected $branchTreeService;

    protected static string $view = 'filament.clusters.h-r-cluster.resources.employee-resource.pages.org-chart';

    // If you need to pass data to the view, you can define a method here
    public function getEmployees()
    {
        $branchTree = BranchTreeService::getEmployeeHierarchy();
        return $branchTree;
    }

    public function getData2()
    {
        return $this->getEmployees();
        $data = [
            [
                'name' => 'Parent',
                'children' => [
                    [
                        'name' => 'Grand Child',
                        'children' => [
                            [
                                'name' => 'Hakim',
                                'children' => [
                                    [
                                        'name' => 'Oday',
                                        'children' => [
                                            ['name' => 'Jamal'],
                                            ['name' => 'Jamal2'],
                                        ]
                                    ],
                                ]
                            ],
                            ['name' => 'Hakim2',]
                        ]

                    ],
                ]
            ]
        ];

        dd($this->getEmployees(), $data);

        return $data;
    }

    function generateTree($nodes)
    {
        $html = '<ul>';

        foreach ($nodes as $node) {
            $html .= '<li>';
            $html .= '<a href="#">';
            $html .= '<img src="https://romansiahdev.nltworkbench.com/storage/employees/default/avatar.png"></img>';
            $html .= '<p>' .  htmlspecialchars($node['name']) . '</p>';
            $html .= '<p>' .  htmlspecialchars($node['emp_no']) . '</p>';

            // Check if the node has children
            if (!empty($node['children'])) {
                $html .= $this->generateTree($node['children']); // Recursive call for children
            }

            $html .= '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    function generateTreeCard($nodes)
    {
        $html = '<ul>';

        foreach ($nodes as $node) {
            $html .= '<li>';
            $html .= '<div class="employee-card">';
            $html .= '<img src="' . htmlspecialchars($node['image'] ?? 'default-avatar.png') . '" alt="Employee Image" class="employee-image">';
            $html .= '<div class="employee-info">';
            $html .= '<h4>' . htmlspecialchars($node['name']) . '</h4>';
            $html .= '<p>' . htmlspecialchars($node['job_title'] ?? 'Unknown Job Title') . '</p>';
            $html .= '<p>Emp No: ' . htmlspecialchars($node['emp_no'] ?? 'N/A') . '</p>';
            $html .= '</div>';
            $html .= '</div>';

            // Check if the node has children
            if (!empty($node['children'])) {
                $html .= $this->generateTree($node['children']); // Recursive call for children
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    public function generate()
    {
        // Get the tree data
        $data = $this->getData2();

        // Generate the tree HTML
        $treeHtml = $this->generateTree($data);
        return $treeHtml;
    }
}
