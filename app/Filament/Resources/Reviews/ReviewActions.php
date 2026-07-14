<?php

namespace App\Filament\Resources\Reviews;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (Review $record) => $record->status !== 'approved')
            ->requiresConfirmation()
            ->action(function (Review $record) {
                $record->update(['status' => 'approved']);
                Notification::make()->success()->title('Review approved')->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (Review $record) => $record->status !== 'rejected')
            ->requiresConfirmation()
            ->action(function (Review $record) {
                $record->update(['status' => 'rejected']);
                Notification::make()->success()->title('Review rejected')->send();
            });
    }
}
