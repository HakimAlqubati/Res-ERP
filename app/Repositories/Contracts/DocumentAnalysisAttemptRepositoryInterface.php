<?php

namespace App\Repositories\Contracts;

use App\Models\DocumentAnalysisAttempt;

interface DocumentAnalysisAttemptRepositoryInterface
{
    /**
     * Create a new document analysis attempt.
     *
     * @param string $type The morph type (e.g., GoodsReceivedNote::class)
     * @param int|null $id The morph id, nullable if created before the entity.
     * @param int|null $userId The user who initiated the attempt.
     * @param string|null $fileName The uploaded file name.
     * @param string $provider The provider used (e.g., amazon_textract).
     * @return DocumentAnalysisAttempt
     */
    public function createAttempt(
        string $type,
        ?int $id,
        ?int $userId,
        ?string $fileName,
        string $provider = 'amazon_textract'
    ): DocumentAnalysisAttempt;

    /**
     * Update the payload of an attempt.
     *
     * @param DocumentAnalysisAttempt $attempt
     * @param array $payload
     * @return bool
     */
    public function updatePayload(DocumentAnalysisAttempt $attempt, array $payload): bool;

    /**
     * Mark the attempt as successful with mapped data.
     *
     * @param DocumentAnalysisAttempt $attempt
     * @param array $mappedData
     * @return bool
     */
    public function markAsSuccess(DocumentAnalysisAttempt $attempt, array $mappedData): bool;

    /**
     * Mark the attempt as failed with an error message.
     *
     * @param DocumentAnalysisAttempt $attempt
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed(DocumentAnalysisAttempt $attempt, string $errorMessage): bool;
}
