<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'carts:send-abandoned-reminders';

    protected $description = 'Email customers whose cart has items but has been inactive, up to a fixed number of reminders';

    /**
     * Hours of inactivity required before each reminder step. Index 0 is
     * measured from the cart's last activity; later steps are measured from
     * the previous reminder, since inactivity alone never changes again once
     * reminders start going out.
     *
     * @var list<int>
     */
    private const REMINDER_STEPS_HOURS = [1, 24, 72];

    public function handle(): int
    {
        // Guest carts have no email on file, so only signed-in users' carts
        // can be reminded.
        Cart::query()
            ->whereNotNull('user_id')
            ->whereHas('items')
            ->where('reminder_count', '<', count(self::REMINDER_STEPS_HOURS))
            ->with('items.productVariant.product', 'user')
            ->chunkById(200, function ($carts) {
                foreach ($carts as $cart) {
                    if ($this->isDueForReminder($cart)) {
                        $cart->user->notify(new AbandonedCartReminder($cart));

                        // Bypass timestamps so updated_at keeps meaning "last customer
                        // activity" rather than "last time we touched this row".
                        $cart->timestamps = false;
                        $cart->forceFill([
                            'reminder_count' => $cart->reminder_count + 1,
                            'last_reminder_sent_at' => now(),
                        ])->save();
                    }
                }
            });

        return self::SUCCESS;
    }

    private function isDueForReminder(Cart $cart): bool
    {
        $hours = self::REMINDER_STEPS_HOURS[$cart->reminder_count];

        $since = $cart->reminder_count === 0 ? $cart->updated_at : $cart->last_reminder_sent_at;

        return $since !== null && $since->lte(now()->subHours($hours));
    }
}
