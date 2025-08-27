<?php 
// app/Filament/Pages/StockInventoryReactPage.php
namespace App\Filament\Pages;

use Filament\Pages\Page;

class StockInventoryReactPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-table';
    protected string $view = 'filament.pages.stock-inventory-react-page';
    protected static ?string $navigationLabel = 'Stocktake (React)';
    protected static ?string $title = 'Stocktake (React)';
}