<?php

namespace App\Filament\Resources\Orders;

use App\Models\AuditLog;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class OrderActions
{
    public const array TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
        'returned' => [],
    ];

    public static function updateStatus(): Action
    {
        return Action::make('updateStatus')
            ->label('Update status')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (Order $record) => filled(self::TRANSITIONS[$record->status] ?? []))
            ->schema(fn (Order $record) => [
                Select::make('status')
                    ->options(collect(self::TRANSITIONS[$record->status] ?? [])
                        ->mapWithKeys(fn (string $status) => [$status => ucfirst($status)]))
                    ->required(),
            ])
            ->action(function (Order $record, array $data) {
                $newStatus = $data['status'];
                $previousStatus = $record->status;
                $orders = app(OrderService::class);

                match ($newStatus) {
                    'cancelled' => $orders->cancel($record, auth()->user()),
                    'delivered' => $orders->markDelivered($record),
                    default => $record->update(['status' => $newStatus]),
                };

                AuditLog::record(auth()->user(), 'order.status_updated', $record, [
                    'from' => $previousStatus,
                    'to' => $newStatus,
                ]);

                Notification::make()->success()->title('Order status updated')->send();
            });
    }

    public static function refund(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->color('danger')
            ->icon('heroicon-o-receipt-refund')
            ->requiresConfirmation()
            ->visible(fn (Order $record) => ! in_array($record->status, ['cancelled', 'returned'], true)
                && $record->payment
                && in_array($record->payment->status, ['confirmed', 'paid'], true))
            ->schema([
                Textarea::make('reason')
                    ->label('Reason (optional)'),
            ])
            ->action(function (Order $record, array $data) {
                app(OrderService::class)->refund($record, auth()->user(), $data['reason'] ?? null);

                AuditLog::record(auth()->user(), 'order.refunded', $record, [
                    'reason' => $data['reason'] ?? null,
                ]);

                Notification::make()->success()->title('Order refunded')->send();
            });
    }
}
