<?php

namespace App\Filament\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    /**
     * @return array<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('title')
                ->label('Title')
                ->guess(['post_title'])
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),

            ImportColumn::make('sku_source')
                ->label('Source ID (used to generate the SKU)')
                ->guess(['ID']),

            ImportColumn::make('brand')
                ->label('Brand')
                ->guess(['tax:product_brand']),

            ImportColumn::make('description')
                ->label('Description')
                ->guess(['post_content']),

            ImportColumn::make('short_description')
                ->label('Short description')
                ->guess(['post_excerpt']),

            ImportColumn::make('status')
                ->label('Status')
                ->guess(['post_status']),

            ImportColumn::make('regular_price')
                ->label('Regular price')
                ->guess(['regular_price'])
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric', 'min:0']),

            ImportColumn::make('sale_price')
                ->label('Sale price')
                ->guess(['sale_price'])
                ->numeric()
                ->ignoreBlankState(),

            ImportColumn::make('stock_status')
                ->label('Stock status')
                ->guess(['stock_status']),

            ImportColumn::make('category_path')
                ->label('Category')
                ->guess(['tax:product_cat']),

            ImportColumn::make('images')
                ->label('Images')
                ->guess(['images'])
                ->ignoreBlankState(),
        ];
    }

    public function resolveRecord(): Product
    {
        $title = $this->data['title'] ?? null;

        if (filled($title)) {
            $slug = Str::slug($title);
            $existing = Product::where('slug', $slug)->first();

            if ($existing) {
                return $existing;
            }
        }

        return new Product();
    }

    /**
     * Deliberately does not call parent::fillRecord() — several logical column names
     * here (title, sku_source, regular_price, category_path) don't correspond to real
     * Product attributes, so the base per-column data_set() behavior would set bogus
     * dynamic attributes and blow up on save(). Every field is mapped by hand instead.
     */
    public function fillRecord(): void
    {
        $record = $this->getRecord();

        if (! $record->exists) {
            $title = (string) ($this->data['title'] ?? '');
            $record->name = $title;
            $record->slug = $this->uniqueSlug(Str::slug($title));
        }

        $record->brand_id = $this->resolveBrandId($this->data['brand'] ?? null);

        $record->description = $this->cleanHtml((string) ($this->data['description'] ?? ''));
        $record->short_description = $this->cleanHtml((string) ($this->data['short_description'] ?? ''));

        $record->status = strtolower((string) ($this->data['status'] ?? '')) === 'published' ? 'active' : 'draft';

        $regularPrice = (int) ($this->data['regular_price'] ?? 0);
        $record->base_price = $regularPrice;

        $salePrice = $this->data['sale_price'] ?? null;
        $salePrice = $salePrice !== null ? (int) $salePrice : null;
        $record->sale_price = ($salePrice !== null && $salePrice < $regularPrice) ? $salePrice : null;

        if (! $record->sku) {
            $sourceId = $this->data['sku_source'] ?? null;
            $record->sku = 'SLB-'.str_pad((string) ($sourceId ?: random_int(10000, 99999)), 5, '0', STR_PAD_LEFT);
        }

        $record->track_inventory = true;
        $record->meta_title = $record->name;
        $record->meta_description = $record->short_description ?: null;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        $record->categories()->sync($this->resolveCategoryIds((string) ($this->data['category_path'] ?? '')));

        $record->images()->delete();

        foreach ($this->parseImageUrls((string) ($this->data['images'] ?? '')) as $index => $url) {
            $record->images()->create([
                'type' => 'image',
                'path' => $url,
                'alt' => $record->name,
                'sort_order' => $index,
            ]);
        }

        $stockStatus = strtolower((string) ($this->data['stock_status'] ?? 'instock'));
        $quantity = $stockStatus === 'outofstock' ? 0 : 100;

        $variant = $record->variants()->first();

        if ($variant) {
            $variant->update(['stock_quantity' => $quantity]);
        } else {
            $record->variants()->create([
                'sku' => $record->sku.'-STD',
                'size_label' => 'Standard',
                'price_override' => null,
                'stock_quantity' => $quantity,
            ]);
        }
    }

    /**
     * Resolves (creating if needed) a 2-level category from each raw, often messy,
     * pipe-separated list of ">"-delimited category paths (WooCommerce export format), and
     * returns every path's resolved category id — a product can end up in several categories
     * from a single import row. Flattens anything deeper than 2 levels per path by using its
     * first segment as the top-level category and its last as the subcategory.
     *
     * @return array<int, int>
     */
    private function resolveCategoryIds(string $raw): array
    {
        $raw = trim($raw);
        $paths = array_filter(array_map('trim', explode('|', $raw)));

        $ids = [];

        foreach ($paths ?: ['Uncategorized'] as $path) {
            $segments = array_values(array_filter(array_map('trim', explode('>', $path))));

            if ($segments === []) {
                continue;
            }

            $topName = $segments[0];
            $subName = count($segments) > 1 ? end($segments) : null;

            $top = Category::firstOrCreate(
                ['slug' => Str::slug($topName)],
                ['name' => $topName, 'is_active' => true],
            );

            if ($subName === null || $subName === $topName) {
                $ids[] = $top->id;

                continue;
            }

            $sub = Category::firstOrCreate(
                ['slug' => Str::slug("{$topName} {$subName}")],
                ['name' => $subName, 'parent_id' => $top->id, 'is_active' => true],
            );

            $ids[] = $sub->id;
        }

        if ($ids === []) {
            $ids[] = Category::firstOrCreate(
                ['slug' => 'uncategorized'],
                ['name' => 'Uncategorized', 'is_active' => true],
            )->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Resolves (creating if needed) a Brand by name, deduped by slug. Returns null for a
     * blank/missing brand rather than inventing one.
     */
    private function resolveBrandId(?string $rawName): ?int
    {
        $rawName = trim((string) $rawName);

        if ($rawName === '') {
            return null;
        }

        return Brand::firstOrCreate(
            ['slug' => Str::slug($rawName)],
            ['name' => $rawName, 'is_active' => true],
        )->id;
    }

    /**
     * @return array<int, string>
     */
    private function parseImageUrls(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return [];
        }

        $urls = [];

        foreach (explode('|', $raw) as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            $url = trim(explode('!', $segment)[0]);

            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function cleanHtml(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '.number_format($import->successful_rows).' '.
            str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
