<?php

namespace App\Filament\Clusters\HRTasksSystem\Resources\TaskAttachmentResource\Pages;

use App\Filament\Clusters\HRTasksSystem\Resources\TaskAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaskAttachment extends CreateRecord
{
    protected static string $resource = TaskAttachmentResource::class;
}
