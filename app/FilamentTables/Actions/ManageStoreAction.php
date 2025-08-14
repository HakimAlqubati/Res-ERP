<?php
namespace App\FilamentTables\Actions;

use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ManageStoreAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('manageStore');
        $this->label(__('Manage Store'));
        $this->icon('heroicon-o-pencil-square');
        $this->color('gray');
        $this->modalHeading(__('Edit Store Info'));

        // الشرط الافتراضي: يظهر فقط إذا كان للفرع مخزن أصلاً
        $this->hidden(fn (Model $record) => ! method_exists($record, 'store') || ! $record->store);

        // نموذج التعديل
        $this->form(function (Model $record) {
            $store = $record->store;

            return [
                TextInput::make('name')
                    ->label(__('Store Name'))
                    ->default($store?->name)
                    ->required(),
                Toggle::make('active')
                    ->label(__('Active'))
                    ->default((bool) $store?->active),
            ];
        });

        // التنفيذ
        $this->action(function (Model $record, array $data) {
            if (! $record->store) {
                Notification::make()
                    ->title(__('No store linked to this branch.'))
                    ->danger()
                    ->send();
                return;
            }

            $record->store->update([
                'name'   => $data['name'],
                'active' => (bool) $data['active'],
            ]);

            Notification::make()
                ->title(__('Store Updated'))
                ->body('✅ ' . __('Store updated successfully.'))
                ->success()
                ->send();
        });
    }

    /**
     * صانع مبسّط للاستخدام في الموارد.
     * يمكنك تمرير خيارات لتعديل السلوك الافتراضي.
     */
    public static function makeForResource(
        ?string $label = null,
        ?bool $hideWhenNoStore = true,
        ?callable $extraVisibility = null
    ): static {
        $action = static::make('manageStore');

        if ($label) {
            $action->label($label);
        }

        if ($hideWhenNoStore) {
            $action->hidden(fn (Model $record) => ! $record->store);
        }

        if ($extraVisibility) {
            // دمج شرط ظهور إضافي من الريسورس (مثلاً صلاحيات معينة)
            $action->visible($extraVisibility);
        }

        return $action;
    }
}
