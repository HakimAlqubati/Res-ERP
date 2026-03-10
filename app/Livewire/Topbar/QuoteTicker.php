<?php

namespace App\Livewire\Topbar;

use App\Services\QuoteService;
use Livewire\Component;

class QuoteTicker extends Component
{
    /** @var array<int, string> قائمة المحتوى النهائي بعد التطبيع والترشيح */
    public array $quotes = [];

    /** ثابت العرض حتى ما يتمدّد */
    public int $width = 260;

    /** بالثواني */
    public int $rotateEvery = 20;

    /** بدء عشوائي حتى ما يطلع نفس الاقتباس لكل المستخدمين */
    public int $startIndex = 0;

    public function mount(QuoteService $quoteService, int $width = 260, int $rotateEvery = 20): void
    {
        $this->width       = $width;
        $this->rotateEvery = max(1, $rotateEvery);

        $this->quotes = $quoteService->getTodayQuotes();

        // بدء عشوائي آمن
        $this->startIndex = count($this->quotes) ? random_int(0, count($this->quotes) - 1) : 0;
    }

    public function render()
    {
        return view('livewire.topbar.quote-ticker');
    }
}
