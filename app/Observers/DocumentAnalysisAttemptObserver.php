<?php

namespace App\Observers;

use App\Models\DocumentAnalysisAttempt;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DocumentAnalysisAttemptObserver
{
    /**
     * Handle the DocumentAnalysisAttempt "created" event.
     */
    public function created(DocumentAnalysisAttempt $attempt): void
    {
        Log::info("Document Analysis Attempt created for file: {$attempt->file_name}");
    }

    /**
     * Handle the DocumentAnalysisAttempt "updated" event.
     */
    public function updated(DocumentAnalysisAttempt $attempt): void
    {
        if ($attempt->wasChanged('status')) {
            Log::info("Document Analysis Attempt status changed to: {$attempt->status} for file: {$attempt->file_name}");
        }
    }

    /**
     * Handle the DocumentAnalysisAttempt "deleted" event.
     */
    public function deleted(DocumentAnalysisAttempt $attempt): void
    {
        //
    }

    /**
     * Handle the DocumentAnalysisAttempt "restored" event.
     */
    public function restored(DocumentAnalysisAttempt $attempt): void
    {
        //
    }

    /**
     * Handle the DocumentAnalysisAttempt "force deleted" event.
     */
    public function forceDeleted(DocumentAnalysisAttempt $attempt): void
    {
        //
    }
}
