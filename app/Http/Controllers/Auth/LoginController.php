<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    public function showLoginForm()
    {
       if (session()->has('admin')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request, FirestoreService $service)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:8'
        ]);

        $key = 'login:' . strtolower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again later.'
            ]);
        }

        // try {
            $admin = $service->findActiveAdminByEmail($request->email);

            if (
                !$admin ||
                !Hash::check($request->password, $admin['password'])
            ) {
                RateLimiter::hit($key, 60);

                return back()->withErrors([
                    'email' => 'Invalid email or password.'
                ]);
            }

            RateLimiter::clear($key);

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $request->session()->put('admin', [
                'id'    => $admin['id'],
                'email' => $admin['email'],
                'name'  => $admin['name'],
            ]);

            return redirect()->route('dashboard');
        // } catch (\Throwable $e) {
        //     return back()
        //         ->withErrors(['email' => 'Unable to login at the moment.'])
        //         ->withInput();
        // }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out successfully.');
    }
}
