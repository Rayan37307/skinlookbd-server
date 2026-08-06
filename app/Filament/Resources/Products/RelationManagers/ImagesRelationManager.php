<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Support\ImageOrUrlField;
use App\Filament\Support\ProductImageFields;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        return $schema->components(ProductImageFields::make());
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('path')
                    ->label('Media')
                    ->formatStateUsing(fn ($record) => $record->type === 'video' ? $record->path : basename($record->path))
                    ->url(fn ($record) => $record->type === 'video' ? $record->path : $record->url())
                    ->openUrlInNewTab()
                    ->limit(40),
                TextColumn::make('alt')
                    ->label('Alt text')
                    ->searchable(),
                TextColumn::make('sort_order'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => ImageOrUrlField::combine($data, 'path')),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => ImageOrUrlField::split($data, 'path'))
                    ->mutateFormDataUsing(fn (array $data): array => ImageOrUrlField::combine($data, 'path')),
                DeleteAction::make(),
            ]);
    }
}
