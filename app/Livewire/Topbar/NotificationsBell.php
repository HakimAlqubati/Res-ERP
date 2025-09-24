<?php

namespace App\Livewire\Topbar;

use Livewire\Component;

class NotificationsBell extends Component
{
    public $notifications;
    public $unreadCount = 0;
    public $showDropdown = false; // ← أضف هذه الخاصية

    protected $listeners = [
        'refreshNotifications' => 'loadNotifications',
    ];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $user = auth()->user();
        if (!$user) return;

        $this->notifications = $user->unreadNotifications()->latest()->get();
        $this->unreadCount = $this->notifications->count();
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->unreadNotifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }
    public function render()
    {
        return view('livewire.topbar.notifications-bell');
    }
}
