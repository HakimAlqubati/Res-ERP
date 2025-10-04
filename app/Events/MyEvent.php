<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MyEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public array $payload = [];

    /**
     * $payload مثال:
     * [
     *   'count' => 7,
     *   'items' => [
     *      ['level' => 'critical', 'title' => 'Stock below min', 'detail' => '...', 'time' => '2025-10-04 03:12', 'link' => '...'],
     *      ...
     *   ]
     * ]
     */


    public function __construct($message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return ['my-channel'];
    }

    public function broadcastAs()
    {
        return 'my-event';
    }
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
