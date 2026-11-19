<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required' => 'Jméno je povinné.',
            'email.required' => 'E-mail je povinný.',
            'email.email' => 'Zadejte platnou e-mailovou adresu.',
            'email.unique' => 'Tento e-mail již používá jiný účet.',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Osobní údaje byly uloženy.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Zadejte současné heslo.',
            'current_password.current_password' => 'Současné heslo není správné.',
            'password.required' => 'Zadejte nové heslo.',
            'password.min' => 'Nové heslo musí mít alespoň :min znaků.',
            'password.confirmed' => 'Potvrzení nového hesla se neshoduje.',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        $request->session()->regenerate();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Heslo bylo úspěšně změněno.');
    }
}
