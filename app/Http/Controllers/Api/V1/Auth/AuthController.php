<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @group Auth
 */
class AuthController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    /**
     * Register
     *
     * Create a customer account and receive a Sanctum token. If an `X-Cart-Token`
     * header is sent, the matching guest cart is merged into the new account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        $user->assignRole('customer');

        if ($guestToken = $request->header('X-Cart-Token')) {
            $this->carts->mergeGuestCartIntoUser($guestToken, $user);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Login
     *
     * Authenticate with an email or phone number and password, receiving a Sanctum
     * token. If an `X-Cart-Token` header is sent, the matching guest cart is merged
     * into the account.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->string('login')->value();

        $user = User::where('email', $login)->orWhere('phone', $login)->first();

        if (! $user || ! Auth::validate(['email' => $user->email, 'password' => $request->string('password')->value()])) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($guestToken = $request->header('X-Cart-Token')) {
            $this->carts->mergeGuestCartIntoUser($guestToken, $user);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout
     *
     * Revoke the bearer token used for this request.
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
