<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['department', 'manager'])
            ->orderBy('name')
            ->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $managers    = User::where('is_active', true)->orderBy('name')->get();
        $roles       = UserRole::cases();

        return view('admin.users.create', compact('departments', 'managers', 'roles'));
    }

    public function store(StoreUserRequest $request)
    {
        User::create($request->validated());
        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $managers    = User::where('is_active', true)->where('id', '!=', $user->id)->orderBy('name')->get();
        $roles       = UserRole::cases();

        return view('admin.users.edit', compact('user', 'departments', 'managers', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // Jangan nonaktifkan diri sendiri
        if ($user->id === auth()->id() && isset($data['is_active']) && !$data['is_active']) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }
}
