<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductForm
{
    /**
     * Every native text/textarea input on this form gets this inline style so the field is
     * comfortably bigger to type into — inline styles are used (rather than utility classes)
     * because they reliably win over the Tailwind classes baked into Filament's own input
     * markup, which utility classes of equal specificity are not guaranteed to do.
     */
    private const string INPUT_STYLE = 'font-size: 1rem; padding-top: 0.875rem; padding-bottom: 0.875rem;';

    private const string SELECT_STYLE = 'min-height: 3.25rem; font-size: 1rem;';

    /**
     * Two-column "dashboard" layout, mirroring Shopify's product page: the main content (title,
     * media, pricing) on the left, a narrow sidebar (status, organization) on the right. Nothing
     * on this form is required — admins can save a product with as little as a name (or nothing
     * at all) and fill in the rest later. See applyFallbackDefaults() for the safety net that
     * fills in whatever the database still needs (name, slug, category, price) when a field is
     * left blank.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('General')
                            ->columns(2)
                            ->schema(self::generalFields()),
                        Section::make('Images')
                            ->description('Drop in photos now — no need to save the product first. Drag a thumbnail to reorder. For video or a hosted image URL, use the "Images" tab after saving.')
                            ->schema(self::imageFields()),
                        Section::make('Pricing & Inventory')
                            ->columns(2)
                            ->schema(self::pricingFields()),
                        Section::make('SEO')
                            ->columns(2)
                            ->schema(self::seoFields()),
                    ]),
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->schema(self::statusFields()),
                        Section::make('Organization')
                            ->schema(self::organizationFields()),
                    ]),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected static function generalFields(): array
    {
        return [
            TextInput::make('name')
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state)))
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            TextInput::make('slug')
                ->maxLength(255)
                ->live(onBlur: true)
                ->unique(ignoreRecord: true)
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            TextInput::make('sku')
                ->label('SKU')
                ->helperText('Only used for products without variants.')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->columnSpanFull()
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Textarea::make('short_description')
                ->rows(2)
                ->columnSpanFull()
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Textarea::make('description')
                ->label('Full description')
                ->columnSpanFull()
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Textarea::make('ingredients')
                ->columnSpanFull()
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Repeater::make('additional_information')
                ->label('Additional information')
                ->schema([
                    TextInput::make('label')
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => self::INPUT_STYLE]),
                    TextInput::make('value')
                        ->maxLength(255)
                        ->extraInputAttributes(['style' => self::INPUT_STYLE]),
                ])
                ->columns(2)
                ->addActionLabel('Add row')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected static function statusFields(): array
    {
        return [
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'archived' => 'Archived',
                ])
                ->default('draft')
                ->extraAttributes(['style' => self::SELECT_STYLE]),
        ];
    }

    /**
     * A single multi-file upload rendered as a Shopify-style thumbnail grid: drop in several
     * images at once, drag any thumbnail to reorder, hover to remove. Not tied to the `images`
     * relationship directly (Filament's relationship-repeater needs one row per record, not a
     * shared grid) — CreateProduct/EditProduct sync this field's ordered path list into `image`
     * type ProductImage rows via ProductForm::syncImageGallery(). Video and hosted-URL images
     * stay on the "Images" relation manager tab (post-save only), which this grid leaves alone.
     *
     * @return array<int, Component>
     */
    protected static function imageFields(): array
    {
        return [
            FileUpload::make('image_gallery')
                ->label('')
                ->helperText('The first photo is used as the cover image — drag thumbnails to reorder.')
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->panelLayout('grid')
                ->imagePreviewHeight('160')
                ->disk('public')
                ->directory('products')
                ->extraAttributes(['class' => 'skinlook-image-gallery'])
                ->columnSpanFull(),
            // A pure-CSS "Cover" badge pinned to the gallery's top-left corner, which is always
            // where the first thumbnail sits — safer than resizing it, since the upload widget's
            // grid layout is computed by its JS library and not something CSS can safely resize
            // per item without risking overlapping tiles. :has() hides the badge when empty.
            Placeholder::make('image_gallery_cover_badge_style')
                ->hiddenLabel()
                ->content(new HtmlString(<<<'HTML'
                    <style>
                        .skinlook-image-gallery { position: relative; }
                        .skinlook-image-gallery:has(.filepond--item)::before {
                            content: 'Cover';
                            position: absolute;
                            top: 0.5rem;
                            left: 0.5rem;
                            z-index: 20;
                            padding: 0.125rem 0.625rem;
                            font-size: 0.7rem;
                            font-weight: 600;
                            color: #fff;
                            background: rgba(0, 0, 0, 0.65);
                            border-radius: 9999px;
                            pointer-events: none;
                        }
                    </style>
                    HTML
                ))
                ->extraAttributes(['class' => 'hidden'])
                ->columnSpanFull(),
        ];
    }

    /**
     * Reconciles the image_gallery field's ordered upload paths into `image` type ProductImage
     * rows: existing rows matched by path keep their id (and alt text), new paths get created,
     * and rows whose path dropped out of the list get deleted. Rows of other types (video,
     * hosted URL) are untouched — they belong to the relation manager's advanced editing tab.
     *
     * @param  array<int, string>  $paths
     */
    public static function syncImageGallery(Product $product, array $paths): void
    {
        $paths = array_values(array_filter($paths));

        $existingByPath = $product->images()
            ->where('type', 'image')
            ->where('path', 'not like', 'http://%')
            ->where('path', 'not like', 'https://%')
            ->get()
            ->keyBy('path');

        foreach ($paths as $order => $path) {
            if ($existingByPath->has($path)) {
                $existingByPath->pull($path)->update(['sort_order' => $order]);

                continue;
            }

            $product->images()->create([
                'type' => 'image',
                'path' => $path,
                'sort_order' => $order,
            ]);
        }

        // Whatever's left in $existingByPath wasn't in the new list — the admin removed it.
        foreach ($existingByPath as $removedImage) {
            $removedImage->delete();
        }
    }

    /**
     * @return array<int, Component>
     */
    protected static function pricingFields(): array
    {
        return [
            TextInput::make('base_price')
                ->label('Regular price (BDT)')
                ->numeric()
                ->minValue(0)
                ->prefix('৳')
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            TextInput::make('sale_price')
                ->label('Sale price (BDT)')
                ->numeric()
                ->minValue(0)
                ->lt('base_price')
                ->prefix('৳')
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            TextInput::make('cost_price')
                ->label('Cost price (BDT)')
                ->helperText('Hidden from customers — used for profit calculation only.')
                ->numeric()
                ->minValue(0)
                ->prefix('৳')
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Toggle::make('track_inventory')
                ->label('Track inventory')
                ->default(true)
                ->live(),
            TextInput::make('stock_quantity')
                ->label('Stock quantity')
                ->helperText('Used for products without variants.')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->visible(fn (Get $get) => (bool) $get('track_inventory'))
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Toggle::make('free_shipping')
                ->default(false),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected static function organizationFields(): array
    {
        return [
            Select::make('category_group_id')
                ->label('Category')
                ->options(fn () => Category::whereNull('parent_id')->orderBy('name')->pluck('name', 'id'))
                ->dehydrated(false)
                ->live()
                ->afterStateHydrated(function (Select $component, $record) {
                    if ($record?->category) {
                        $component->state($record->category->parent_id ?? $record->category->id);
                    }
                })
                ->afterStateUpdated(fn (callable $set) => $set('category_id', null))
                ->searchable()
                ->extraAttributes(['style' => self::SELECT_STYLE]),
            Select::make('category_id')
                ->label('Subcategory')
                ->options(function (callable $get) {
                    $parentId = $get('category_group_id');

                    if (! $parentId) {
                        return [];
                    }

                    return Category::where('id', $parentId)
                        ->orWhere('parent_id', $parentId)
                        ->orderByRaw('parent_id is not null')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Category $category) => [
                            $category->id => $category->parent_id ? $category->name : "{$category->name} (General)",
                        ]);
                })
                ->searchable()
                ->extraAttributes(['style' => self::SELECT_STYLE]),
            Select::make('brand_id')
                ->label('Brand')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->extraAttributes(['style' => self::SELECT_STYLE])
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                ]),
            Select::make('skinTypes')
                ->label('Skin types')
                ->relationship('skinTypes', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->extraAttributes(['style' => self::SELECT_STYLE]),
            Select::make('tags')
                ->label('Tags')
                ->relationship('tags', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->extraAttributes(['style' => self::SELECT_STYLE])
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                ]),
            Select::make('labels')
                ->label('Marketing labels')
                ->relationship('labels', 'name')
                ->multiple()
                ->searchable()
                ->preload()
                ->extraAttributes(['style' => self::SELECT_STYLE])
                ->createOptionForm([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('color')
                        ->options([
                            'danger' => 'Red',
                            'warning' => 'Orange',
                            'success' => 'Green',
                            'primary' => 'Purple',
                            'info' => 'Blue',
                            'gray' => 'Gray',
                        ])
                        ->default('gray')
                        ->required(),
                    TextInput::make('icon')
                        ->label('Icon (emoji)')
                        ->maxLength(10),
                ]),
            Select::make('relatedProducts')
                ->label('Related products')
                ->relationship(
                    'relatedProducts',
                    'name',
                    fn ($query, $record) => $record ? $query->whereKeyNot($record->id) : $query,
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->extraAttributes(['style' => self::SELECT_STYLE]),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected static function seoFields(): array
    {
        return [
            TextInput::make('meta_title')
                ->maxLength(255)
                ->live(onBlur: true)
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Textarea::make('meta_description')
                ->rows(2)
                ->maxLength(500)
                ->live(onBlur: true)
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            TextInput::make('focus_keyword')
                ->maxLength(255)
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            TextInput::make('canonical_url')
                ->label('Canonical URL')
                ->url()
                ->maxLength(255)
                ->extraInputAttributes(['style' => self::INPUT_STYLE]),
            Placeholder::make('seo_preview')
                ->label('Search engine listing preview')
                ->live()
                ->content(function (Get $get) {
                    $title = e($get('meta_title') ?: $get('name') ?: 'Product title');
                    $slug = e($get('slug') ?: 'product-slug');
                    $description = e($get('meta_description') ?: 'Add a meta description to see how it will look in search results.');

                    return new HtmlString(<<<HTML
                        <div style="font-family: arial, sans-serif; max-width: 600px;">
                            <div style="color: #1a0dab; font-size: 18px; line-height: 1.3;">{$title}</div>
                            <div style="color: #006621; font-size: 14px;">skinlookbd.com/products/{$slug}</div>
                            <div style="color: #545454; font-size: 13px;">{$description}</div>
                        </div>
                        HTML
                    );
                })
                ->columnSpanFull(),
        ];
    }

    /**
     * Fills in whatever the `products` table still requires (name, slug, category, price) when
     * the form was submitted with those left blank, since nothing on this form is a required
     * field. Mirrors the fallback category/slug logic already used by ProductImporter.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applyFallbackDefaults(array $data): array
    {
        $data['name'] = filled($data['name'] ?? null) ? $data['name'] : 'Untitled product';

        if (blank($data['slug'] ?? null)) {
            $data['slug'] = self::uniqueSlug(Str::slug($data['name']) ?: 'product');
        }

        $data['base_price'] = $data['base_price'] ?? 0;

        if (blank($data['category_id'] ?? null)) {
            $data['category_id'] = Category::firstOrCreate(
                ['slug' => 'uncategorized'],
                ['name' => 'Uncategorized', 'is_active' => true],
            )->id;
        }

        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    private static function uniqueSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
