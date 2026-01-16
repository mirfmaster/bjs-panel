<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const USERNAME_KEY = 'bjs.credentials.username';
    private const PASSWORD_KEY = 'bjs.credentials.password';
    private const LOGIN_TOGGLE_KEY = 'bjs.session.login_toggle';

    public function index(): View|RedirectResponse
    {
        if (!Auth::check() || !Auth::user()->is_superadmin) {
            return redirect()->route('dashboard')
                ->with('error', 'Only superadmin can access settings.');
        }

        $settings = [
            'username' => Cache::get(self::USERNAME_KEY, ''),
            'password' => Cache::get(self::PASSWORD_KEY, ''),
            'login_toggle' => Cache::get(self::LOGIN_TOGGLE_KEY, false),
        ];

        return view('settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        if (!Auth::check() || !Auth::user()->is_superadmin) {
            return redirect()->route('dashboard')
                ->with('error', 'Only superadmin can modify settings.');
        }

        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'login_toggle' => ['nullable', 'boolean'],
        ]);

        Cache::put(self::USERNAME_KEY, $validated['username']);
        Cache::put(self::PASSWORD_KEY, $validated['password']);
        Cache::put(self::LOGIN_TOGGLE_KEY, $request->boolean('login_toggle'));

        return redirect()->route('settings')->with('status', 'Settings saved successfully.');
    }
}
