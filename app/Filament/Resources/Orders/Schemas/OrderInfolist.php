<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order')
                    ->columns(3)
                    ->components([
                        TextEntry::make('order_number')->label('Order #'),
                        TextEntry::make('user.name')->label('Customer'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'pending' => 'gray',
                                'confirmed', 'processing' => 'info',
                                'shipped' => 'warning',
                                'delivered' => 'success',
                                'cancelled', 'returned' => 'danger',
                            }),
                        TextEntry::make('payment_method')->label('Payment method')->badge(),
                        TextEntry::make('payment.status')->label('Payment status')->placeholder('-')->badge(),
                        TextEntry::make('coupon.code')->label('Coupon')->placeholder('-'),
                        TextEntry::make('subtotal')->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                        TextEntry::make('discount_total')->label('Discount')->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                        TextEntry::make('shipping_charge')->label('Shipping')->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                        TextEntry::make('total')->weight('bold')->formatStateUsing(fn (int $state) => '৳'.number_format($state)),
                        TextEntry::make('created_at')->label('Placed at')->dateTime(),
                        TextEntry::make('notes')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Shipping address')
                    ->columns(2)
                    ->components([
                        TextEntry::make('recipient_name')->label('Recipient'),
                        TextEntry::make('recipient_phone')->label('Phone'),
                        TextEntry::make('shipping_address_line1')->label('Address line 1')->columnSpanFull(),
                        TextEntry::make('shipping_address_line2')->label('Address line 2')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('shipping_city')->label('City'),
                        TextEntry::make('shipping_area')->label('Area')->placeholder('-'),
                        TextEntry::make('shipping_postal_code')->label('Postal code')->placeholder('-'),
                    ]),
            ]);
    }
}
