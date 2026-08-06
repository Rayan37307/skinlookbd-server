<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;

/**
 * The image/video fields shared by the product images relation manager (the post-save table on
 * the edit page) and the repeater embedded directly in the product create/edit form, so images
 * can be added while creating a product instead of only after it's been saved once.
 */
class ProductImageFields
{
    /**
     * @return array<int, Component>
     */
    public static function make(): array
    {
        [$imageUpload, $imageUrl] = ImageOrUrlField::make('path', 'products', label: 'Image');

        return [
            Select::make('type')
                ->options([
                    'image' => 'Image',
                    'video' => 'Video',
                ])
                ->default('image')
                ->live()
                ->required(),
            $imageUpload
                ->visible(fn (Get $get) => $get('type') === 'image')
                ->required(fn (Get $get) => $get('type') === 'image' && blank($get('path_url'))),
            $imageUrl
                ->visible(fn (Get $get) => $get('type') === 'image')
                ->required(fn (Get $get) => $get('type') === 'image' && blank($get('path'))),
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
        ];
    }
}
