<?php

namespace App\Filament\Clusters\SettingsCluster\Resources\Tables;

use App\Models\User;
use App\Notifications\AttendanceNotification;
use App\Notifications\SyncDatabaseNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification  as LaraNotification;

use Filament\Schemas\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Log;

class NotificationSettingTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('frequency')
                    ->label('Frequency')
                    ->sortable(),

                TextColumn::make('daily_time')
                    ->label('Daily Time')
                    ->sortable(),

                ToggleColumn::make('active')
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('testNotify')
                    ->schema([Fieldset::make()->columnSpanFull()->schema([
                        TextInput::make('message')->columnSpanFull()->helperText('Type Message'),
                    ])])
                    ->action(function ($data) {
                        $recipient = auth()->user();

                        $recipient = User::where('email', 'wm555213@gmailcom')->first();;

                        Notification::make()
                            ->title('Saved successfully')
                            ->body($data['message'])
                            ->send()
                            ->broadcast($recipient)
                            ->sendToDatabase($recipient, isEventDispatched: true)
                        ;
                        // event(new \App\Events\MyEvent($data['message']));

                        Log::info('notifycation', [$recipient]);
                    })
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
