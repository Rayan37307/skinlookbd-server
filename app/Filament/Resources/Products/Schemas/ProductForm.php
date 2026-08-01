<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Product')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('General')
                            ->components(self::generalFields()),
                        Tab::make('Pricing & Inventory')
                            ->components(self::pricingFields()),
                        Tab::make('Organization')
                            ->components(self::organizationFields()),
                        Tab::make('SEO')
                            ->components(self::seoFields()),
                    ]),
            ]);
    }

    /**
     * @return array<int, Component>
     */
    protected static function generalFields(): array
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
                ->required(),
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
                ->required(),
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->unique(ignoreRecord: true),
            TextInput::make('sku')
                ->label('SKU')
                ->helperText('Only used for products without variants.')
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('brand_id')
                ->label('Brand')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
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
            Textarea::make('short_description')
                ->rows(2)
                ->columnSpanFull(),
            Textarea::make('description')
                ->label('Full description')
                ->columnSpanFull(),
            Textarea::make('ingredients')
                ->columnSpanFull(),
            Repeater::make('additional_information')
                ->label('Additional information')
                ->schema([
                    TextInput::make('label')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('value')
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2)
                ->addActionLabel('Add row')
                ->columnSpanFull(),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'archived' => 'Archived',
                ])
                ->required()
                ->default('draft'),
        ];
    }

    /**
     * @return array<int, Component>
     */
    protected static function pricingFields(): array
    {
        return [
            TextInput::make('base_price')
                ->label('Regular price (BDT)')
                ->required()
                ->numeric()
                ->minValue(0)
                ->prefix('৳'),
            TextInput::make('sale_price')
                ->label('Sale price (BDT)')
                ->numeric()
                ->minValue(0)
                ->lt('base_price')
                ->prefix('৳'),
            TextInput::make('cost_price')
                ->label('Cost price (BDT)')
                ->helperText('Hidden from customers — used for profit calculation only.')
                ->numeric()
                ->minValue(0)
                ->prefix('৳'),
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
                ->visible(fn (Get $get) => (bool) $get('track_inventory')),
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
            Select::make('skinTypes')
                ->label('Skin types')
                ->relationship('skinTypes', 'name')
                ->multiple()
                ->searchable()
                ->preload(),
            Select::make('tags')
                ->label('Tags')
                ->relationship('tags', 'name')
                ->multiple()
                ->searchable()
                ->preload()
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
            Select::make('concerns')
                ->label('Skin concerns')
                ->relationship('concerns', 'name')
                ->multiple()
                ->searchable()
                ->preload()
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
                ->preload(),
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
                ->live(onBlur: true),
            Textarea::make('meta_description')
                ->rows(2)
                ->maxLength(500)
                ->live(onBlur: true),
            TextInput::make('focus_keyword')
                ->maxLength(255),
            TextInput::make('canonical_url')
                ->label('Canonical URL')
                ->url()
                ->maxLength(255),
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
}
