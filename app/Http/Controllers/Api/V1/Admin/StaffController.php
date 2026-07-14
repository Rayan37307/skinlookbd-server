<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin - Staff
 *
 * Requires the `super-admin` role.
 *
 * @authenticated
 */
class StaffController extends Controller
{
    private const STAFF_ROLES = ['super-admin', 'order-manager', 'catalog-manager'];

    /**
     * List staff accounts
     */
    public function index(): JsonResponse
    {
        $staff = User::role(self::STAFF_ROLES)->with('roles')->orderBy('name')->get();

        return response()->json([
            'staff' => StaffResource::collection($staff),
        ]);
    }

    /**
     * Create a staff account
     *
     * Creates a user and assigns exactly one of `super-admin`, `order-manager`, or
     * `catalog-manager`.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = User::create($request->safe()->except(['role', 'password']) + [
            'password' => $request->string('password')->value(),
        ]);
        $staff->assignRole($request->string('role')->value());

        AuditLog::record($request->user(), 'staff.created', $staff, ['role' => $request->string('role')->value()]);

        return response()->json(['staff' => new StaffResource($staff->load('roles'))], 201);
    }

    /**
     * Update a staff account
     *
     * Passing `role` replaces the staff member's current role entirely.
     */
    public function update(UpdateStaffRequest $request, User $staff): JsonResponse
    {
        abort_unless($staff->hasAnyRole(self::STAFF_ROLES), 404);

        $staff->update($request->safe()->except('role'));

        if ($request->has('role')) {
            $staff->syncRoles([$request->string('role')->value()]);
        }

        AuditLog::record($request->user(), 'staff.updated', $staff, $request->validated());

        return response()->json(['staff' => new StaffResource($staff->load('roles'))]);
    }

    /**
     * Delete a staff account
     *
     * You cannot delete your own account.
     */
    public function destroy(Request $request, User $staff): JsonResponse
    {
        abort_unless($staff->hasAnyRole(self::STAFF_ROLES), 404);
        abort_if($staff->id === $request->user()->id, 422, 'You cannot remove your own staff account.');

        AuditLog::record($request->user(), 'staff.deleted', $staff);
        $staff->delete();

        return response()->json(['message' => 'Staff account removed.']);
    }
}
