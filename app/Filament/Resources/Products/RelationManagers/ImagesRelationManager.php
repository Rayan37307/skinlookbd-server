<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options([
                        'image' => 'Image',
                        'video' => 'Video',
                    ])
                    ->default('image')
                    ->live()
                    ->required(),
                FileUpload::make('path')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->visible(fn (Get $get) => $get('type') === 'image')
                    ->required(fn (Get $get) => $get('type') === 'image'),
                TextInput::make('path')
                    ->label('Video URL')
                    ->url()
                    ->visible(fn (Get $get) => $get('type') === 'video')
                    ->required(fn (Get $get) => $get('type') === 'video'),
                TextInput::make('alt')
                    ->label('Alt text')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
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
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
