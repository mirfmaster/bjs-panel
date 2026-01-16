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
    private const SERVICES_KEY = 'bjs.services';

    public function index(): View|RedirectResponse
    {
        if (!Auth::check() || !Auth::user()->is_superadmin) {
            return redirect()->route('dashboard')
                ->with('error', 'Only superadmin can access settings.');
        }

        $services = Cache::get(self::SERVICES_KEY, []);
        $servicesFormatted = implode("\n", array_map('strval', $services));

        $settings = [
            'username' => Cache::get(self::USERNAME_KEY, ''),
            'password' => Cache::get(self::PASSWORD_KEY, ''),
            'login_toggle' => Cache::get(self::LOGIN_TOGGLE_KEY, false),
            'services' => $servicesFormatted,
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
            'services' => ['nullable', 'string'],
        ]);

        Cache::put(self::USERNAME_KEY, $validated['username']);
        Cache::put(self::PASSWORD_KEY, $validated['password']);
        Cache::put(self::LOGIN_TOGGLE_KEY, $request->boolean('login_toggle'));

        $servicesInput = $validated['services'] ?? '';
        $servicesLines = array_filter(array_map('trim', explode("\n", $servicesInput)));
        $services = array_map('intval', $servicesLines);
        $services = array_filter($services, fn ($id) => $id > 0);
        $services = array_values($services);

        Cache::put(self::SERVICES_KEY, $services);

        return redirect()->route('settings')->with('status', 'Settings saved successfully.');
    }
}
