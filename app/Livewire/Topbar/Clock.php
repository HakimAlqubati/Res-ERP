<?php

namespace App\Livewire\Topbar;

use Livewire\Component;
use Illuminate\Support\Carbon;

/**
 * Livewire clock widget (shows server time with app timezone).
 * Auto-refreshes every second using Livewire polling.
 */
class Clock extends Component
{
    public string $time = '';

    public function mount(): void
    {
        $this->refresh();
    }

    public function refresh(): void
    {
        // Laravel timezone from config/app.php
$this->time = Carbon::now(config('app.timezone'))->format('h:i A');
        // dd($this->time);
    }

    public function render()
    {
        return view('livewire.topbar.clock');
    }
}
