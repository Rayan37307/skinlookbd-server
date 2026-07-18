<?php

namespace App\Filament\Resources\Labels\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LabelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->badge()
                    ->color(fn ($record) => $record->color)
                    ->formatStateUsing(fn ($record) => trim(($record->icon ?? '').' '.$record->name)),
                TextEntry::make('slug'),
                TextEntry::make('color')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
