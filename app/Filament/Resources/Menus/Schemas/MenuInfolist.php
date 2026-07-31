<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MenuInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('parent.label')
                    ->label('Parent item')
                    ->placeholder('-'),
                TextEntry::make('label'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('target')
                    ->placeholder('-'),
                TextEntry::make('style')
                    ->placeholder('-'),
                ColorEntry::make('highlight_color')
                    ->label('Pill background color')
                    ->placeholder('-'),
                TextEntry::make('sort_order'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
