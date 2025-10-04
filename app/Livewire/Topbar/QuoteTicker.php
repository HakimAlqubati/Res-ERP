<?php

namespace App\Livewire\Topbar;

use Livewire\Component;
use Illuminate\Support\Arr;

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

    public function mount(int $width = 260, int $rotateEvery = 20): void
    {
        $this->width       = $width;
        $this->rotateEvery = max(1, $rotateEvery);

        // نقرأ من config(workbench.php) المفتاح: 'quotes'
        $raw = config('workbench.quotes', []);

        // 1) نطبّع الشكلين: إما string قديم، أو array جديدة فيها date/content
        $normalized = array_values(array_filter(array_map(function ($item) {
            // الشكل الجديد: ['date' => int(1..31), 'content' => '...']
            if (is_array($item)) {
                $day     = (int) Arr::get($item, 'date', 0);
                $content = trim((string) Arr::get($item, 'content', ''));
                if ($content !== '') {
                    return ['date' => $day ?: null, 'content' => $content];
                }
                return null;
            }

            // الشكل القديم: 'نص الاقتباس'
            if (is_string($item)) {
                $content = trim($item);
                return $content === '' ? null : ['date' => null, 'content' => $content];
            }

            return null;
        }, $raw)));

        // 2) ترشيح حسب يوم الشهر الحالي (1..31)
        $today = (int) now()->day;

        $todayQuotes = array_values(array_map(
            fn ($row) => $row['content'],
            array_filter($normalized, fn ($row) => isset($row['date']) && (int) $row['date'] === $today)
        ));

        // لو ما فيه اقتباسات لليوم الحالي، نعرض الكل كـ fallback
        $this->quotes = count($todayQuotes) ? $todayQuotes : array_values(array_map(fn ($row) => $row['content'], $normalized));

        // فلترة الفراغات لو بقي شيء
        $this->quotes = array_values(array_filter(array_map('trim', $this->quotes)));

        // بدء عشوائي آمن
        $this->startIndex = count($this->quotes) ? random_int(0, count($this->quotes) - 1) : 0;
    }

    public function render()
    {
        return view('livewire.topbar.quote-ticker');
    }
}
