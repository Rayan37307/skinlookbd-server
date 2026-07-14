<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Jobs\SendOtpSmsJob;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * @group Auth
 */
class OtpController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    /**
     * Send phone OTP
     *
     * Generates a 6-digit code (valid 5 minutes) and dispatches it by SMS.
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->value();

        Otp::where('phone', $phone)
            ->where('purpose', 'phone_verification')
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        Otp::create([
            'phone' => $phone,
            'code' => $code,
            'purpose' => 'phone_verification',
            'expires_at' => now()->addMinutes(5),
        ]);

        SendOtpSmsJob::dispatch($phone, $code);

        return response()->json(['message' => 'OTP sent.']);
    }

    /**
     * Verify phone OTP
     *
     * Confirms the code sent to a phone number and marks it verified. Locks after
     * 5 incorrect attempts.
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $phone = $request->string('phone')->value();

        $otp = Otp::where('phone', $phone)
            ->where('purpose', 'phone_verification')
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return response()->json(['message' => 'OTP is invalid or has expired.'], 422);
        }

        if (! Str::is($otp->code, $request->string('code')->value())) {
            $otp->increment('attempts');

            return response()->json(['message' => 'OTP is invalid or has expired.'], 422);
        }

        $otp->update(['consumed_at' => now()]);

        User::where('phone', $phone)->update(['phone_verified_at' => now()]);

        return response()->json(['message' => 'Phone number verified.']);
    }
}
