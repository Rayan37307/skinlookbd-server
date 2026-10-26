<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['products', 'categories', 'brands', 'tags']))
            ->columns([
                TextColumn::make('name')
                    ->label('Promotion')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('code')
                    ->searchable()
                    ->placeholder('Automatic'),
                TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'automatic' ? 'Automatic' : 'Code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percent' => 'Percentage',
                        'flat' => 'Fixed amount',
                        'free_delivery' => 'Free delivery',
                        default => $state,
                    }),
                TextColumn::make('discount_value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state, Coupon $record): string => match ($record->discount_type) {
                        'flat' => '৳'.number_format($state),
                        'free_delivery' => 'Free delivery',
                        default => $state.'%',
                    }),
                TextColumn::make('max_discount')
                    ->label('Max discount')
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scope')
                    ->label('Applies to')
                    ->state(fn (Coupon $record): string => self::scopeSummary($record)),
                TextColumn::make('min_order_value')
                    ->label('Min order')
                    ->formatStateUsing(fn (int $state): string => '৳'.number_format($state)),
                TextColumn::make('max_uses')
                    ->label('Max uses')
                    ->placeholder('Unlimited'),
                TextColumn::make('max_uses_per_user')
                    ->label('Max per customer')
                    ->placeholder('Unlimited')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('first_order_only')
                    ->label('First order')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('allow_combination')
                    ->label('Combinable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
                SelectFilter::make('method')
                    ->options([
                        'code' => 'Coupon code',
                        'automatic' => 'Automatic',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function scopeSummary(Coupon $coupon): string
    {
        $scopes = [
            'products_count' => 'product',
            'categories_count' => 'category',
            'brands_count' => 'brand',
            'tags_count' => 'tag',
        ];

        $parts = [];

        foreach ($scopes as $attribute => $label) {
            $count = (int) ($coupon->getAttribute($attribute) ?? 0);

            if ($count > 0) {
                $parts[] = $count.' '.($count === 1 ? $label : $label.'s');
            }
        }

        return $parts === [] ? 'Whole order' : implode(', ', $parts);
    }
}
