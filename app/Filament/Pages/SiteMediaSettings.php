<?php

namespace App\Filament\Pages;

use App\Models\SiteMedia;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

class SiteMediaSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Media';

    protected static ?string $title = 'Appearance / Media';

    protected string $view = 'filament.pages.site-media-settings';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $records = SiteMedia::whereIn('key', array_keys(config('site_media.slots')))->get()->keyBy('key');

        [$logoImage, $logoUrl] = $this->splitImagePath($records->get('logo')?->image_path);
        [$footerLogoImage, $footerLogoUrl] = $this->splitImagePath($records->get('footer_logo')?->image_path);
        [$footerImage, $footerUrl] = $this->splitImagePath($records->get('footer_ribbon')?->image_path);

        $this->form->fill([
            'logo_image' => $logoImage,
            'logo_image_url' => $logoUrl,
            'footer_logo_image' => $footerLogoImage,
            'footer_logo_image_url' => $footerLogoUrl,
            'footer_ribbon_image' => $footerImage,
            'footer_ribbon_image_url' => $footerUrl,
            'footer_ribbon_is_active' => $records->get('footer_ribbon')?->is_active ?? true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $imageField = fn (string $name, string $label) => FileUpload::make($name)
            ->label($label)
            ->image()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->maxSize(4096)
            ->disk('public')
            ->directory('site-media');

        $urlField = fn (string $name) => TextInput::make($name)
            ->label('...or paste an image URL')
            ->helperText('Use this if you only have a hosted link, not a file to upload.')
            ->url()
            ->maxLength(2048);

        return $schema
            ->components([
                Section::make('Logo')
                    ->schema([
                        $imageField('logo_image', 'Logo'),
                        $urlField('logo_image_url'),
                    ]),

                Section::make('Footer Logo')
                    ->description('Used in the site footer. Falls back to the main logo above if left empty.')
                    ->schema([
                        $imageField('footer_logo_image', 'Footer logo'),
                        $urlField('footer_logo_image_url'),
                    ]),

                Section::make('Footer Ribbon')
                    ->schema([
                        $imageField('footer_ribbon_image', 'Footer ribbon'),
                        $urlField('footer_ribbon_image_url'),
                        Toggle::make('footer_ribbon_is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->persistSlot(
            key: 'logo',
            uploadedPath: $state['logo_image'] ?? null,
            externalUrl: $state['logo_image_url'] ?? null,
            isActive: true,
        );

        $this->persistSlot(
            key: 'footer_logo',
            uploadedPath: $state['footer_logo_image'] ?? null,
            externalUrl: $state['footer_logo_image_url'] ?? null,
            isActive: true,
        );

        $this->persistSlot(
            key: 'footer_ribbon',
            uploadedPath: $state['footer_ribbon_image'] ?? null,
            externalUrl: $state['footer_ribbon_image_url'] ?? null,
            isActive: (bool) ($state['footer_ribbon_is_active'] ?? true),
        );

        Notification::make()->title('Media updated')->success()->send();

        $this->mount();
    }

    private function persistSlot(
        string $key,
        ?string $uploadedPath,
        ?string $externalUrl,
        bool $isActive,
        ?string $linkUrl = null,
        bool $updateLinkUrl = false,
    ): void {
        $record = SiteMedia::firstOrCreate(['key' => $key]);

        $newImagePath = $uploadedPath ?: $externalUrl;

        if ($newImagePath !== $record->image_path) {
            $record->deleteStoredImage();
            $record->image_path = $newImagePath;
        }

        if ($updateLinkUrl) {
            $record->link_url = $linkUrl;
        }

        $record->is_active = $isActive;
        $record->save();
    }

    /**
     * @return array{0: ?string, 1: ?string} [uploadedDiskPath, externalUrl]
     */
    private function splitImagePath(?string $path): array
    {
        if (! $path) {
            return [null, null];
        }

        return Str::startsWith($path, ['http://', 'https://']) ? [null, $path] : [$path, null];
    }
}
