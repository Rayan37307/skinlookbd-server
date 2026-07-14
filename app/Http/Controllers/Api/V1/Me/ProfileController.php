<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Account
 *
 * @authenticated
 */
class ProfileController extends Controller
{
    /**
     * Get current profile
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('roles')),
        ]);
    }

    /**
     * Update profile
     *
     * Updates name and/or email. Phone number changes go through the OTP
     * verification flow instead and are not editable here.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}
