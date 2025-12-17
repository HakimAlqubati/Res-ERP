<?php

namespace App\Services\Warnings;

use App\Models\AppLog;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Composite Warning Sender - delegates to multiple channel senders.
 * 
 * This allows sending warnings to multiple channels (database, email, etc.)
 * while preserving backward compatibility.
 */
final class CompositeWarningSender implements WarningSender
{
    /** @var WarningSender[] */
    protected array $senders = [];

    /**
     * @param WarningSender[] $senders
     */
    public function __construct(array $senders = [])
    {
        $this->senders = $senders;
    }

    /**
     * Add a sender to the composite.
     */
    public function addSender(WarningSender $sender): self
    {
        $this->senders[] = $sender;
        return $this;
    }

    /**
     * Send warning to all registered channels.
     */
    public function send(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        foreach ($this->senders as $sender) {
            try {
                $sender->send($users, $payload);
            } catch (\Throwable $e) {
                // Log error but continue with other senders
                AppLog::write(
                    'Warning sender failed',
                    AppLog::LEVEL_ERROR,
                    'CompositeWarningSender',
                    [
                        'sender' => get_class($sender),
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }
    }

    /**
     * Send warning to all registered channels (always creates new record).
     */
    public function sendAlwaysNew(Authenticatable|iterable $users, WarningPayload $payload): void
    {
        foreach ($this->senders as $sender) {
            try {
                $sender->sendAlwaysNew($users, $payload);
            } catch (\Throwable $e) {
                // Log error but continue with other senders
                
            }
        }
    }

    /**
     * Get all registered senders.
     * 
     * @return WarningSender[]
     */
    public function getSenders(): array
    {
        return $this->senders;
    }
}
