<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendOtpSmsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $code,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // No SMS gateway (bKash/SSLCommerz-adjacent SMS provider) is wired up yet;
        // swap this for the real gateway call when one is chosen.
        Log::info("OTP for {$this->phone}: {$this->code}");
    }
}
