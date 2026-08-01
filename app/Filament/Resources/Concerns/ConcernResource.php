<?php

namespace App\Filament\Resources\Concerns;

use App\Filament\Resources\Concerns\Pages\CreateConcern;
use App\Filament\Resources\Concerns\Pages\EditConcern;
use App\Filament\Resources\Concerns\Pages\ListConcerns;
use App\Filament\Resources\Concerns\Pages\ViewConcern;
use App\Filament\Resources\Concerns\Schemas\ConcernForm;
use App\Filament\Resources\Concerns\Schemas\ConcernInfolist;
use App\Filament\Resources\Concerns\Tables\ConcernsTable;
use App\Models\Concern;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConcernResource extends Resource
{
    protected static ?string $model = Concern::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ConcernForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConcernInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConcernsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConcerns::route('/'),
            'create' => CreateConcern::route('/create'),
            'view' => ViewConcern::route('/{record}'),
            'edit' => EditConcern::route('/{record}/edit'),
        ];
    }
}
