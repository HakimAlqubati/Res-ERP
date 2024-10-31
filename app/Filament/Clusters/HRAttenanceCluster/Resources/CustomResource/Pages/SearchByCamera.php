<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\CustomResource\Pages;
 
use App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource;
use Filament\Resources\Pages\Page;

class SearchByCamera extends Page
{

    
    protected static string $resource = TestSearchByImageResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-camera';

     protected static string $view = 'filament.clusters.h-r-attenance-cluster.resources.custom-resource.pages.search-by-camera';

    // Add page title
    protected static ?string $title = 'Search by Camera';
}
