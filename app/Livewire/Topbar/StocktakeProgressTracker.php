<?php

namespace App\Livewire\Topbar;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class StocktakeProgressTracker extends Component
{
    public $progressData = null;

    public function render()
    {
        $this->updateProgress();

        return view('livewire.topbar.stocktake-progress-tracker');
    }

    public function updateProgress()
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            $this->progressData = null;
            return;
        }

        $this->progressData = Cache::get("stocktake_progress_{$userId}");
    }
}
