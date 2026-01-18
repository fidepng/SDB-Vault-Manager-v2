<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\AuditService;
use App\Services\SdbLogService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            // ✅ LOG FAILED LOGIN
            AuditService::logFailedLogin(
                $request->input('email'),
                'Invalid credentials'
            );

            throw $e;
        }

        $request->session()->regenerate();

        // Log successful login (sudah ada)
        SdbLogService::record(
            'LOGIN',
            'User berhasil masuk ke sistem.'
        );

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // [BARU] Catat Log Logout (Sebelum session dihapus agar ID user masih terbaca)
        if (Auth::check()) {
            SdbLogService::record(
                'LOGOUT',
                'User keluar dari sistem.'
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
