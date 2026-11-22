<?php

declare(strict_types=1);

namespace App\Services\Warnings\Handlers;

use App\Modules\HR\EmployeeDocuments\Notifications\DocumentExpiryNotifier;
use App\Services\Warnings\Contracts\WarningHandler;

/**
 * Thin adapter integrating DocumentExpiryNotifier with NotificationOrchestrator.
 * Protected by DocumentExpiryDeduplicator so daily digests are never duplicated.
 */
final class ExpiredDocumentHandler implements WarningHandler
{
    /** @var array{user?: string|int|null, force?: bool} */
    private array $options = [];

    public function __construct(
        private readonly DocumentExpiryNotifier $notifier,
    ) {}

    public function key(): string
    {
        return 'expired_documents';
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function handle(): array
    {
        $stats = $this->notifier->notifyForCurrentTenant(
            onlyUser: $this->options['user'] ?? null,
            force: (bool) ($this->options['force'] ?? false),
        );

        return [$stats['sent'], $stats['failed']];
    }
}
