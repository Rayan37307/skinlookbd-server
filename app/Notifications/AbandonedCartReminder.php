<?php

namespace App\Notifications;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbandonedCartReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Cart $cart) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $items = $this->cart->items;
        $total = $items->sum(fn ($item) => $item->productVariant->price * $item->quantity);

        $mail = (new MailMessage)
            ->subject('You left something in your cart')
            ->greeting("Hi {$notifiable->name},")
            ->line('You still have items waiting in your cart:');

        foreach ($items as $item) {
            $mail->line("- {$item->productVariant->product->name} ({$item->productVariant->size_label}) x{$item->quantity}");
        }

        return $mail
            ->line("Cart total: {$total}")
            ->line('Open the app to complete your purchase before items sell out.');
    }
}
