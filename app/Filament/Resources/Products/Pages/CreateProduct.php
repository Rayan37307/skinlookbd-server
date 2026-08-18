<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Services\FrontendDeployTrigger;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @var array<int, string>
     */
    private array $pendingImageGallery = [];

    /**
     * `image_gallery` isn't a column on `products` — it's pulled out here and synced into
     * `images` rows in afterCreate() once the record (and its id) exists.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingImageGallery = $data['image_gallery'] ?? [];
        unset($data['image_gallery']);

        return ProductForm::applyFallbackDefaults($data);
    }

    protected function afterCreate(): void
    {
        ProductForm::syncImageGallery($this->record, $this->pendingImageGallery);
        ProductForm::assignFallbackCategoryIfNone($this->record);

        app(FrontendDeployTrigger::class)->triggerNewProductDeploy();
    }
}
