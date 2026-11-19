<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.required' => 'Uživatelské jméno je povinné.',
            'username.alpha_dash' => 'Uživatelské jméno může obsahovat jen písmena, čísla, pomlčku a podtržítko.',
            'username.min' => 'Uživatelské jméno musí mít alespoň :min znaky.',
            'username.unique' => 'Toto uživatelské jméno je již obsazené.',
            'name.required' => 'Jméno je povinné.',
            'email.required' => 'E-mail je povinný.',
            'email.email' => 'Zadejte platnou e-mailovou adresu.',
            'email.unique' => 'Účet s tímto e-mailem již existuje.',
            'password.required' => 'Heslo je povinné.',
            'password.min' => 'Heslo musí mít alespoň :min znaků.',
            'password.confirmed' => 'Potvrzení hesla se neshoduje.',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
        ]);

        $user->forceFill(['last_login_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->timestamp);

        return redirect()
            ->route('home')
            ->with('success', 'Registrace proběhla úspěšně. Jste přihlášeni.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Zadejte uživatelské jméno nebo e-mail.',
            'password.required' => 'Zadejte heslo.',
        ]);

        $loginValue = trim($validated['login']);

        $user = User::where('email', mb_strtolower($loginValue))
            ->orWhere('username', $loginValue)
            ->first();

        if ($user === null || !Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Neplatné přihlašovací údaje.'])
                ->onlyInput('login');
        }

        if (!$user->is_active) {
            return back()
                ->withErrors(['login' => 'Tento účet je deaktivovaný.'])
                ->onlyInput('login');
        }

        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->timestamp);

        return redirect()
            ->intended(route('home'))
            ->with('success', 'Byli jste úspěšně přihlášeni.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Byli jste odhlášeni.');
    }
}
