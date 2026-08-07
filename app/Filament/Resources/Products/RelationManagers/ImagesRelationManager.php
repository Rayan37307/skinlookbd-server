<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Support\ImageOrUrlField;
use App\Filament\Support\ProductImageFields;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Schema $schema): Schema
    {
        return $schema->components(ProductImageFields::make(requireMediaSource: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('path')
                    ->label('Media')
                    ->formatStateUsing(fn ($record) => $record->type === 'video' ? $record->path : basename($record->path))
                    ->url(fn ($record) => $record->type === 'video' ? $record->path : $record->url())
                    ->openUrlInNewTab()
                    ->limit(40),
                TextColumn::make('alt')
                    ->label('Alt text')
                    ->searchable(),
                TextColumn::make('sort_order'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => ImageOrUrlField::combine($data, 'path'))
                    ->using(function (array $data, string $model, CreateAction $action) {
                        if (blank($data['path'] ?? null)) {
                            throw new Halt;
                        }

                        $record = new $model($data);
                        $action->getRelationship()->save($record);

                        return $record;
                    }),
                Action::make('bulkUpload')
                    ->label('Bulk upload')
                    ->modalHeading('Bulk upload images')
                    ->modalSubmitActionLabel('Add images')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Images')
                            ->image()
                            ->multiple()
                            ->disk('public')
                            ->directory('products')
                            ->reorderable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $relationship = $this->getOwnerRecord()->images();
                        $nextOrder = ((int) $relationship->max('sort_order')) + 1;

                        foreach ($data['files'] as $path) {
                            $relationship->create([
                                'type' => 'image',
                                'path' => $path,
                                'sort_order' => $nextOrder++,
                            ]);
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data): array => ImageOrUrlField::split($data, 'path'))
                    ->mutateFormDataUsing(fn (array $data): array => ImageOrUrlField::combine($data, 'path'))
                    ->using(function (array $data, Model $record) {
                        if (blank($data['path'] ?? null)) {
                            throw new Halt;
                        }

                        $record->update($data);
                    }),
                DeleteAction::make(),
            ]);
    }
}
