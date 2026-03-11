<?php

namespace App\Repositories\Eloquent;

use App\Models\DocumentAnalysisAttempt;
use App\Repositories\Contracts\DocumentAnalysisAttemptRepositoryInterface;

class DocumentAnalysisAttemptRepository implements DocumentAnalysisAttemptRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function createAttempt(
        string $type,
        ?int $id,
        ?int $userId,
        ?string $fileName,
        string $provider = 'amazon_textract'
    ): DocumentAnalysisAttempt {
        return DocumentAnalysisAttempt::create([
            'documentable_type' => $type,
            'documentable_id' => $id,
            'user_id' => $userId,
            'file_name' => $fileName,
            'provider' => $provider,
            'status' => 'pending',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function updatePayload(DocumentAnalysisAttempt $attempt, array $payload): bool
    {
        return $attempt->update(['payload' => $payload]);
    }

    /**
     * @inheritDoc
     */
    public function markAsSuccess(DocumentAnalysisAttempt $attempt, array $mappedData): bool
    {
        return $attempt->update([
            'status' => 'success',
            'mapped_data' => $mappedData,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function markAsFailed(DocumentAnalysisAttempt $attempt, string $errorMessage): bool
    {
        return $attempt->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
