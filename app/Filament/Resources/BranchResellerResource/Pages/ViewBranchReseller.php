<?php

namespace App\Filament\Resources\BranchResellerResource\Pages;

use App\Filament\Resources\BranchResellerResource;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewBranchReseller extends ViewRecord
{
    protected static string $resource = BranchResellerResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->record->name;
    }

    // public function form(Form $form): Form
    // {
    //     return $form
    //         ->schema([
    //             // We use a Section component for better visual organization.
    //             Section::make('Reseller Details')
    //                 ->description('')
    //                 ->schema([
    //                     // Display the 'name' field.
    //                     TextInput::make('name')
    //                         ->label('Reseller Name')
    //                         ->disabled(), // Disable the field to make it read-only.

    //                     // Example: Display the name of the related Branch Manager.
    //                     // This assumes you have a relationship called 'manager' on your 'BranchReseller' model
    //                     // that connects to a User (or another model) which has a 'name' attribute.
    //                     TextInput::make('manager')
    //                         ->formatStateUsing(function ($record) {
    //                             return $record->user->name ?? '';
    //                         })
    //                         ->label(__('lang.manager')),
 
    //                 ])
    //                 ->columns(2), // Arrange the fields into 2 columns.
    //         ]);
    // }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }
}