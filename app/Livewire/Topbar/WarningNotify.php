<?php

namespace App\Livewire\Topbar;

use Livewire\Component;

class WarningNotify extends Component
{
    /** @var array<int, array{id:int,title:string,detail:string,time:string,level:string}> */
    public array $warnings = [];

    /** Fixed width to avoid stretching */
    public int $width = 260;

    /** Rotation interval in seconds (default: 60s) */
    public int $rotateEvery = 60;

    /** Random start index */
    public int $startIndex = 0;

    public function mount(int $width = 260, int $rotateEvery = 60): void
    {
        $this->width = $width;
        $this->rotateEvery = $rotateEvery;

        // Load from config or default demo warnings
        $this->warnings = config('workbench.warnings', [
            ['id'=>101,'title'=>'Speed bump ahead','detail'=>'Suppliers road: today’s delivery is delayed.','time'=>'10 minutes ago','level'=>'warning'],
            ['id'=>102,'title'=>'Low stock','detail'=>'Ginger stock is below minimum level.','time'=>'1 hour ago','level'=>'critical'],
            ['id'=>103,'title'=>'Expiry alert','detail'=>'Product: Fresh milk, expires in 3 days.','time'=>'Today','level'=>'info'],
        ]);

        $this->warnings = array_values(array_filter($this->warnings));
        $this->startIndex = count($this->warnings) ? random_int(0, count($this->warnings) - 1) : 0;
    }

    public function render()
    {
        return view('livewire.topbar.warning-notify');
    }
}
