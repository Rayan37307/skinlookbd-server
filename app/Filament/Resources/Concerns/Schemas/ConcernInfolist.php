<?php

namespace App\Filament\Resources\Concerns\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ConcernInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('category.name')
                    ->label('Linked category')
                    ->placeholder('-'),
                TextEntry::make('tag.name')
                    ->label('Linked tag')
                    ->placeholder('-'),
                ImageEntry::make('image')
                    ->placeholder('-'),
                TextEntry::make('color_from')
                    ->placeholder('-'),
                TextEntry::make('color_to')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('sort_order'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
