<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

/**
 * A FileUpload paired with a "paste a URL instead" TextInput that both feed the same
 * underlying attribute (e.g. `image`), mirroring the pattern used by SiteMediaSettings. Use
 * split() in a fill hook and combine() in a save hook to translate between the one stored
 * value and these two form fields.
 */
class ImageOrUrlField
{
    /**
     * @return array{0: FileUpload, 1: TextInput}
     */
    public static function make(string $name, string $directory, ?string $label = null, ?string $helperText = null, bool $required = false): array
    {
        $urlName = static::urlFieldName($name);

        $upload = FileUpload::make($name)
            ->label($label)
            ->helperText($helperText)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->live()
            ->required(fn (Get $get): bool => $required && blank($get($urlName)));

        $url = TextInput::make($urlName)
            ->label('...or paste an image URL')
            ->helperText('Use this if you only have a hosted link, not a file to upload.')
            ->url()
            ->maxLength(2048)
            ->live()
            ->required(fn (Get $get): bool => $required && blank($get($name)));

        return [$upload, $url];
    }

    public static function urlFieldName(string $name): string
    {
        return "{$name}_url";
    }

    /**
     * Splits the one stored value back into the upload field (disk path) or url field
     * (external link), so editing a record shows the right one filled in.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function split(array $data, string $name): array
    {
        $value = $data[$name] ?? null;
        $urlName = static::urlFieldName($name);

        if ($value && Str::startsWith($value, ['http://', 'https://'])) {
            $data[$name] = null;
            $data[$urlName] = $value;
        } else {
            $data[$urlName] = null;
        }

        return $data;
    }

    /**
     * Collapses the two form fields back into the one real column before persisting — the
     * upload wins if both were somehow set.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function combine(array $data, string $name): array
    {
        $urlName = static::urlFieldName($name);

        $data[$name] = $data[$name] ?? $data[$urlName] ?? null;
        unset($data[$urlName]);

        return $data;
    }
}
