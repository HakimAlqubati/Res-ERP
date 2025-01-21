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
        $branchTree = BranchTreeService::getBranchTreeV2();
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
    public function getData()
    {
        $data = [
            [
                'name' => 'Parent',
                'children' => [
                    [
                        'name' => 'Child',
                        'children' => [
                            [
                                'name' => 'Grand Child',
                                'children' => [
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child Example'],
                                    ['name' => 'Great Grand Child'],
                                ],
                            ],
                            [
                                'name' => 'Grand Child',
                                'children' => [
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                ],
                            ],
                            [
                                'name' => 'Grand Child',
                                'children' => [
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Child2',
                        'children' => [
                            [
                                'name' => 'Grand Child',
                                'children' => [
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child Example'],
                                    ['name' => 'Great Grand Child'],
                                ],
                            ],
                            [
                                'name' => 'Grand Child',
                                'children' => [
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                ],
                            ],
                            [
                                'name' => 'Grand Child',
                                'children' => [
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                    ['name' => 'Great Grand Child'],
                                ],
                            ],
                        ],
                    ],
                    ['name' => 'Child2'],
                ],
            ],
        ];
        return $data;
    }
    function generateTree($nodes)
    {
        $html = '<ul>';

        foreach ($nodes as $node) {
            $html .= '<li>';
            $html .= '<a href="#">' . htmlspecialchars($node['name']) . '</a>';

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
