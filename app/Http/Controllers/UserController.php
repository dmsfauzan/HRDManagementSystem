<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($sub) => $sub->where('name', $request->string('role'))))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'is_active' => $request->boolean('is_active', true),
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$request->string('role')->toString()]);

        AuditLogger::log('user.created', $user, [], ['name' => $user->name, 'email' => $user->email, 'role' => $request->string('role')->toString()]);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $oldRole = $user->getRoleNames()->first();

        $user->update([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $newRole = $request->string('role')->toString();
        if ($oldRole !== $newRole) {
            $user->syncRoles([$newRole]);
            AuditLogger::log('user.role_changed', $user, ['role' => $oldRole], ['role' => $newRole]);
        }

        AuditLogger::log('user.updated', $user, [], ['name' => $user->name, 'email' => $user->email]);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        AuditLogger::log($user->is_active ? 'user.activated' : 'user.deactivated', $user, [], ['is_active' => $user->is_active]);

        return back()->with('success', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make($request->string('password'))]);

        AuditLogger::log('user.password_reset', $user);

        return redirect()->route('users.edit', $user)->with('success', 'Password pengguna berhasil direset.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $name = $user->name;
        AuditLogger::log('user.deleted', $user, ['name' => $name, 'email' => $user->email], []);
        $user->delete();

        return redirect()->route('users.index')->with('success', "Pengguna {$name} berhasil dihapus.");
    }
}
