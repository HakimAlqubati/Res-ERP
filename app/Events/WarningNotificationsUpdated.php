<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class WarningNotificationsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    /**
     * @param array $payload
     * مثال على الشكل:
     * [
     *   'count' => 5,
     *   'items' => [
     *      ['level' => 'critical', 'title' => 'Stock < Min', 'detail' => '...', 'time' => '2025-10-04 03:12', 'link' => '...'],
     *      ['level' => 'warning', 'title' => 'Task overdue', 'detail' => '...', 'time' => '2025-10-04 04:15'],
     *   ]
     * ]
     */
    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        // قناة عامة بسيطة
        return [new Channel('warnings')];
    }

    public function broadcastAs(): string
    {
        return 'warnings.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
