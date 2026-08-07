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
     * $requireMediaSource controls the image upload / image URL / video URL fields only. The
     * relation manager's standalone "add image" modal always creates a record immediately, so
     * it keeps requiring one of the two (there's no such thing as a blank saved image there).
     * The repeater embedded in the product form instead drops rows left without a media source
     * before saving (see ProductForm::combineImageRowOrSkip()), so it passes false here to keep
     * an added-but-unfinished row from blocking the whole product save.
     *
     * @return array<int, Component>
     */
    public static function make(bool $requireMediaSource = true): array
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
                ->required(fn (Get $get) => $requireMediaSource && $get('type') === 'image' && blank($get('path_url'))),
            $imageUrl
                ->visible(fn (Get $get) => $get('type') === 'image')
                ->required(fn (Get $get) => $requireMediaSource && $get('type') === 'image' && blank($get('path'))),
            TextInput::make('path')
                ->label('Video URL')
                ->url()
                ->visible(fn (Get $get) => $get('type') === 'video')
                ->required(fn (Get $get) => $requireMediaSource && $get('type') === 'video'),
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
