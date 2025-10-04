<?php

namespace App\Livewire\Topbar;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

final class WarningNotify extends Component
{
    /** @var array<int, array{id:string,title:string,detail:string,time:string,level:string}> */
    public array $warnings = [];
    protected $listeners = ['warnings:refresh' => 'refreshWarnings'];

    /** عرض ثابت لمنع التمدد */
    public int $width = 260;


    /** تدوير كل N ثوانٍ */
    public int $rotateEvery = 60;

    /** فهرس بداية عشوائي */
    public int $startIndex = 0;

    /** اجلب غير المقروء فقط؟ */
    public bool $onlyUnread = true;

    /** حد أقصى للنتائج */
    public int $limit = 30;

    public function mount(int $width = 260, int $rotateEvery = 60, bool $onlyUnread = true, int $limit = 30): void
    {
        $this->width       = $width;
        $this->rotateEvery = $rotateEvery;
        $this->onlyUnread  = $onlyUnread;
        $this->limit       = $limit;

        $this->refreshWarnings();
    }

    /** إعادة الجلب من قاعدة البيانات */
    #[On('warnings:refresh')]
    public function refreshWarnings(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->warnings = [];
            $this->startIndex = 0;
            return;
        }

        // فلترة بحسب نوع الإشعار (WarningNotification)
        $query = $user->notifications()
            ->where('type', \App\Notifications\WarningNotification::class)
            ->latest();

        if ($this->onlyUnread) {
            $query->whereNull('read_at');
        }

        $rows = $query->limit($this->limit)->get();

        // تحويل الشكل لهيئة الواجهة
        $this->warnings = $rows->map(function ($n) {
            $data  = (array) $n->data;
            $level = $data['level'] ?? 'warning';


            return [
                'id'     => (string) $n->id,
                'title'  => $data['title']  ?? 'Warning',
                'detail' => $data['detail'] ?? '',
                'time'   => $this->humanTime($n->created_at),
                'link' => $data['link'] ?? ($data['url'] ?? null),

                'level'  => in_array($level, ['critical', 'warning', 'info'], true) ? $level : 'warning',
                // لو تبغى الرابط أو سياق إضافي فيما بعد:
                // 'link'   => $data['link']   ?? null,
                // 'context'=> $data['context']?? null,
            ];
        })->all();

        $this->warnings = array_values(array_filter($this->warnings));
        $this->startIndex = count($this->warnings) ? random_int(0, count($this->warnings) - 1) : 0;
    }

    private function humanTime($dt): string
    {
        try {
            return Carbon::parse($dt)->diffForHumans();
        } catch (\Throwable $e) {
            return (string) $dt;
        }
    }

    public function render()
    {
        return view('livewire.topbar.warning-notify');
    }
}
