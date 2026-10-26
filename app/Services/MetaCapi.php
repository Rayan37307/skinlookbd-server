<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapi
{
    /**
     * Report a Purchase to Meta's Conversions API.
     *
     * Runs synchronously and swallows failures — same rationale as FrontendDeployTrigger:
     * the queue on this host isn't reliably drained (see DEPLOY.md), so a queued job could
     * sit unprocessed indefinitely. This is a single fast HTTP call and a missed report
     * should never fail a checkout. The browser pixel fires the same event with
     * event_id = order_number, so Meta dedupes — this call only backfills purchases the
     * browser missed (ad blockers, closed tabs, slow connections).
     */
    public function sendPurchase(Order $order): void
    {
        $pixelId = config('services.meta.pixel_id');
        $token = config('services.meta.capi_token');

        if (! $pixelId || ! $token) {
            Log::info('Skipping Meta CAPI purchase: META_PIXEL_ID or META_CAPI_TOKEN not configured.');

            return;
        }

        $payload = [
            'data' => [
                [
                    'event_name' => 'Purchase',
                    'event_time' => $order->created_at->timestamp,
                    'event_id' => $order->order_number,
                    'action_source' => 'ecommerce',
                    'user_data' => [
                        'ph' => [hash('sha256', $this->normalizePhone($order->recipient_phone))],
                    ],
                    'custom_data' => [
                        'currency' => 'BDT',
                        'value' => (float) $order->total,
                        'content_type' => 'product',
                        'contents' => $order->items->map(fn ($item) => [
                            'id' => (string) $item->product_id,
                            'quantity' => (int) $item->quantity,
                            'item_price' => (float) $item->unit_price,
                        ])->values()->all(),
                        'num_items' => (int) $order->items->sum('quantity'),
                    ],
                ],
            ],
        ];

        try {
            $response = Http::asJson()
                ->timeout(5)
                ->post("https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$token}", $payload);

            if ($response->failed()) {
                Log::warning('Meta CAPI purchase failed', [
                    'order_number' => $order->order_number,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI purchase threw an exception', [
                'order_number' => $order->order_number,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Meta expects phone numbers as E.164 digits without a leading '+'. Bangladeshi
     * numbers are commonly stored as 01XXXXXXXXX, so drop the leading 0 and prepend 880.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        return '880'.ltrim($digits, '0');
    }
}
