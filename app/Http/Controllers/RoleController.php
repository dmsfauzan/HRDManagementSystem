<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('roles.manage'), 403);

        return view('roles.index', [
            'roles' => Role::withCount('users')->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('name')->get()->groupBy(fn (Permission $p) => (string) str($p->name)->before('.')),
        ]);
    }

    public function updatePermissions(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->can('roles.manage'), 403);
        abort_if($role->name === 'super_admin', 403, 'Role super_admin selalu memiliki seluruh permission dan tidak dapat diubah.');

        $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $before = $role->permissions->pluck('name')->all();
        $role->syncPermissions($request->input('permissions', []));
        $after = $role->fresh()->permissions->pluck('name')->all();

        if ($before !== $after) {
            AuditLogger::log('role.permissions_updated', $role, ['permissions' => $before], ['permissions' => $after]);
        }

        return back()->with('success', "Permission role {$role->name} berhasil diperbarui.");
    }
}
