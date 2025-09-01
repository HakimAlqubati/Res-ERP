<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskAttachmentResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Clusters\HRTasksSystem\Resources\TaskAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaskAttachment extends EditRecord
{
    protected static string $resource = TaskAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
