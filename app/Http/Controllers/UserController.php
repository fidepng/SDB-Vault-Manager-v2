<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SdbLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Menampilkan daftar user.
     */
    public function index()
    {
        // Ambil semua user, urutkan dari yang terbaru
        $users = User::latest()->get();
        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan form tambah user.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Menyimpan user baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,super_admin'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // LOG AUDIT
        SdbLogService::record(
            'USER_MANAGEMENT',
            "Menambahkan user baru: {$user->name} ({$user->role})",
            null,
            Auth::id()
        );

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit user.
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update data user (termasuk reset password jika diisi).
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,super_admin'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()], // Password opsional saat edit
        ]);

        $changes = [];

        // Cek perubahan data dasar untuk log
        if ($user->name !== $request->name) $changes[] = "Nama berubah";
        if ($user->role !== $request->role) $changes[] = "Role berubah ke {$request->role}";

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

        // Logic Reset Password (hanya jika diisi)
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $changes[] = "Password di-reset";
        }

        $user->save();

        // LOG AUDIT (Hanya jika ada perubahan)
        if (!empty($changes)) {
            SdbLogService::record(
                'USER_MANAGEMENT',
                "Update user {$user->email}: " . implode(', ', $changes),
                null,
                Auth::id()
            );
        }

        return redirect()->route('users.index')->with('success', 'Data user diperbarui.');
    }

    /**
     * Menghapus user.
     */
    public function destroy(User $user)
    {
        // Cegah hapus diri sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun sendiri saat sedang login.');
        }

        $email = $user->email;
        $user->delete();

        // LOG AUDIT
        SdbLogService::record(
            'USER_MANAGEMENT',
            "Menghapus user: {$email}",
            null,
            Auth::id()
        );

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
