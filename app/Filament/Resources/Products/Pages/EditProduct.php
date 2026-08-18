<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @var array<int, string>
     */
    private array $pendingImageGallery = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * Seeds the image_gallery field from the product's existing `image` type images, ordered the
     * same way they're stored, so the grid shows what's already there.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['image_gallery'] = $this->record->images()
            ->where('type', 'image')
            ->where('path', 'not like', 'http://%')
            ->where('path', 'not like', 'https://%')
            ->orderBy('sort_order')
            ->pluck('path')
            ->all();

        return $data;
    }

    /**
     * image_gallery isn't a column on `products` — it's pulled out here and synced into `images`
     * rows in afterSave().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingImageGallery = $data['image_gallery'] ?? [];
        unset($data['image_gallery']);

        return ProductForm::applyFallbackDefaults($data);
    }

    protected function afterSave(): void
    {
        ProductForm::syncImageGallery($this->record, $this->pendingImageGallery);
        ProductForm::assignFallbackCategoryIfNone($this->record);
    }
}
