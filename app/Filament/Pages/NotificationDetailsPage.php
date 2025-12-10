<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationDetailsPage extends Page
{
    protected static string | BackedEnum | null $navigationIcon = null;
    protected static ?string $slug = 'notification-details/{id}';
    protected static bool $shouldRegisterNavigation = false;

    protected   string $view = 'filament.pages.notifications.notification-details';

    public ?string $notificationId = null;
    public array $notification = [];
    public array $context = [];
    public string $notificationType = 'generic';

    public function mount(string $id): void
    {
        $this->notificationId = $id;

        // Fetch notification from database
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $record = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_id', $user->id)
            ->first();

        if (!$record) {
            abort(404, 'Notification not found');
        }

        // Parse notification data
        $data = json_decode($record->data, true) ?? [];

        $this->notification = [
            'id' => $record->id,
            'title' => $data['title'] ?? 'Notification',
            'detail' => $data['detail'] ?? $data['body'] ?? '',
            'level' => $data['level'] ?? 'info',
            'created_at' => $record->created_at,
            'read_at' => $record->read_at,
        ];

        $this->context = $data['ctx'] ?? $data['context'] ?? [];

        // Detect notification type from scope or context
        $this->notificationType = $this->detectNotificationType($data);

        // Mark as read
        if (!$record->read_at) {
            DB::table('notifications')
                ->where('id', $id)
                ->update(['read_at' => now()]);
        }
    }

    protected function detectNotificationType(array $data): string
    {
        $scope = $data['scope'] ?? '';

        if (str_contains($scope, 'missedcheckin')) {
            return 'missed_checkin';
        }

        if (str_contains($scope, 'lowstock')) {
            return 'low_stock';
        }

        // Check context for type hints
        if (!empty($this->context['employees'])) {
            return 'missed_checkin';
        }

        if (!empty($this->context['products']) || !empty($this->context['store_id'])) {
            return 'low_stock';
        }

        return 'generic';
    }

    public function getTitle(): string|Htmlable
    {
        return $this->notification['title'] ?? __('Notification Details');
    }

    public function getHeading(): string|Htmlable
    {
        return $this->notification['title'] ?? __('Notification Details');
    }

    protected function getViewData(): array
    {
        return [
            'notification' => $this->notification,
            'context' => $this->context,
            'notificationType' => $this->notificationType,
        ];
    }
}
