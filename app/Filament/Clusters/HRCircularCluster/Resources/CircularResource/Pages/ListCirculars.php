<?php

namespace App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages;

use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource;
use App\Models\Branch;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListCirculars extends ListRecords
{
    protected static string $resource = CircularResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(function(){
            if(isStuff()){
                 return 'Share insight';
             }
                return 'New memo';
            }),
          ];
    }
}
