<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class SoftDeleteColumn
{
    public static function make(string $name = 'deleted_at'): TextColumn
    {
        return TextColumn::make($name)
            ->label('')
            ->formatStateUsing(fn($state) => $state ? 'Deleted' : '')
            ->badge()
            ->colors([
                'danger' => fn($state) => filled($state),
                'success' => fn($state) => blank($state),
            ]);
    }
}
