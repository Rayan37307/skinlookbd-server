<?php

namespace App\Http\Controllers\Api\V1\Me;

use App\Http\Controllers\Controller;
use App\Http\Requests\Me\StoreAddressRequest;
use App\Http\Requests\Me\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Account
 *
 * @authenticated
 */
class AddressController extends Controller
{
    /**
     * List addresses
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'addresses' => AddressResource::collection($request->user()->addresses()->get()),
        ]);
    }

    /**
     * Add an address
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $request->user()->addresses()->create($request->validated());

        return response()->json([
            'address' => new AddressResource($address),
        ], 201);
    }

    /**
     * Update an address
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address->update($request->validated());

        return response()->json([
            'address' => new AddressResource($address),
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);

        $address->delete();

        return response()->json(['message' => 'Address deleted.']);
    }
}
