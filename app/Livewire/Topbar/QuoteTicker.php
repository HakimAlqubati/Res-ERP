<?php

namespace App\Livewire\Topbar;

use Livewire\Component;

class QuoteTicker extends Component
{
    /** @var array<string> */
    public array $quotes = [];

    /** ثابت العرض حتى ما يتمدّد */
    public int $width = 260;

    /** بالثواني: 5 دقائق = 300 ثانية */
    public int $rotateEvery = 300;

    /** بدء عشوائي حتى ما يطلع نفس الاقتباس لكل المستخدمين */
    public int $startIndex = 0;

    public function mount(int $width = 260, int $rotateEvery = 300): void
    {
        $this->width = $width;
        $this->rotateEvery = $rotateEvery;

        // مصدر العبارات من config، ولو فاضي نحط افتراضيات
        $this->quotes = config('workbench.quotes');

        // فلترة الفراغات
        $this->quotes = array_values(array_filter(array_map('trim', $this->quotes)));

        // بدء عشوائي
        $this->startIndex = count($this->quotes) ? random_int(0, count($this->quotes) - 1) : 0;
    }

    public function render()
    {
        return view('livewire.topbar.quote-ticker');
    }
}
