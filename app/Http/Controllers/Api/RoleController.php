<?php

namespace App\Http\Controllers\Api;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->with('permissions')->get();
        return $this->respondSuccess('Roles retrieved', $roles);
    }

    public function store(Request $request): JsonResponse
    {
        $error = $this->validate($request->all(), [
            'name'         => 'required|string|max:50|unique:roles,name',
            'display_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
        ]);
        if ($error) return $error;

        $role = Role::create([
            'name'         => $request->name,
            'display_name' => $request->display_name,
            'description'  => $request->description,
            'is_system'    => false,
        ]);

        return $this->respondCreated($role);
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::with('permissions', 'users')->find($id);
        if (!$role) return $this->respondNotFound();

        return $this->respondSuccess('Role retrieved', $role);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) return $this->respondNotFound();

        if ($role->is_system) {
            return $this->respondError('System roles cannot be modified.', 403);
        }

        $error = $this->validate($request->all(), [
            'display_name' => 'sometimes|string|max:100',
            'description'  => 'nullable|string|max:500',
        ]);
        if ($error) return $error;

        $role->update($request->only('display_name', 'description'));
        return $this->respondSuccess('Role updated', $role);
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) return $this->respondNotFound();

        if ($role->is_system) {
            return $this->respondError('System roles cannot be deleted.', 403);
        }

        $role->delete();
        return $this->respondSuccess('Role deleted');
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        return $this->respondSuccess('Permissions retrieved', $permissions);
    }

    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'permission_ids'   => 'required|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);
        if ($error) return $error;

        $role->permissions()->sync($request->permission_ids);
        $this->flushUsersPermissionCache($role);

        return $this->respondSuccess('Role permissions updated', $role->load('permissions'));
    }

    public function assignUser(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'user_id'    => 'required|integer|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
        ]);
        if ($error) return $error;

        $user = User::find($request->user_id);

        $role->users()->syncWithoutDetaching([$user->id => [
            'assigned_at' => now(),
            'assigned_by' => $request->user()->id,
            'expires_at'  => $request->expires_at,
        ]]);

        $user->flushPermissionCache();

        return $this->respondSuccess('Role assigned to user');
    }

    public function revokeUser(Request $request, int $id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) return $this->respondNotFound();

        $error = $this->validate($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);
        if ($error) return $error;

        $user = User::find($request->user_id);
        $role->users()->detach($user->id);
        $user->flushPermissionCache();

        return $this->respondSuccess('Role revoked from user');
    }

    private function flushUsersPermissionCache(Role $role): void
    {
        $role->users()->each(fn ($user) => $user->flushPermissionCache());
    }
}
