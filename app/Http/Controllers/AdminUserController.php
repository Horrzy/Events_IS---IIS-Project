<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public const ROLES = [
        'user' => 'Registrovaný uživatel',
        'moderator' => 'Moderátor',
        'admin' => 'Administrátor',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($role, self::ROLES), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'search' => $search,
            'selectedRole' => $role,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => null,
            'roles' => self::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', 'unique:users,username'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'username.required' => 'Uživatelské jméno je povinné.',
            'username.alpha_dash' => 'Uživatelské jméno může obsahovat jen písmena, čísla, pomlčku a podtržítko.',
            'username.min' => 'Uživatelské jméno musí mít alespoň :min znaky.',
            'username.unique' => 'Toto uživatelské jméno je již obsazené.',
            'name.required' => 'Jméno je povinné.',
            'email.required' => 'E-mail je povinný.',
            'email.email' => 'Zadejte platnou e-mailovou adresu.',
            'email.unique' => 'Tento e-mail již používá jiný účet.',
            'role.required' => 'Vyberte roli uživatele.',
            'role.in' => 'Neplatná role.',
            'password.required' => 'Heslo je povinné.',
            'password.min' => 'Heslo musí mít alespoň :min znaků.',
            'password.confirmed' => 'Potvrzení hesla se neshoduje.',
        ]);

        $user = new User([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'password' => $validated['password'],
        ]);
        $user->role = $validated['role'];
        $user->is_active = $request->boolean('is_active', true);
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Uživatel {$user->username} byl vytvořen.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => self::ROLES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
        ], [
            'name.required' => 'Jméno je povinné.',
            'email.required' => 'E-mail je povinný.',
            'email.email' => 'Zadejte platnou e-mailovou adresu.',
            'email.unique' => 'Tento e-mail již používá jiný účet.',
            'role.required' => 'Vyberte roli uživatele.',
            'role.in' => 'Neplatná role.',
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
            return back()
                ->withErrors(['role' => 'Nemůžete sami sobě odebrat roli administrátora.'])
                ->withInput();
        }

        if ($user->role === 'admin' && $validated['role'] !== 'admin' && $this->isLastActiveAdmin($user)) {
            return back()
                ->withErrors(['role' => 'Nelze odebrat roli poslednímu aktivnímu administrátorovi.'])
                ->withInput();
        }

        $user->name = $validated['name'];
        $user->email = mb_strtolower($validated['email']);
        $user->role = $validated['role'];
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Údaje uživatele {$user->username} byly uloženy.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordReset', [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Zadejte nové heslo.',
            'password.min' => 'Heslo musí mít alespoň :min znaků.',
            'password.confirmed' => 'Potvrzení hesla se neshoduje.',
        ]);

        $user->update(['password' => $validated['password']]);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', "Heslo uživatele {$user->username} bylo změněno.");
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Nemůžete deaktivovat vlastní účet.');
        }

        if ($user->is_active && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Nelze deaktivovat posledního aktivního administrátora.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'aktivován' : 'deaktivován';

        return back()->with('success', "Účet uživatele {$user->username} byl {$statusText}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Nemůžete smazat vlastní účet.');
        }

        if ($this->isLastActiveAdmin($user)) {
            return back()->with('error', 'Nelze smazat posledního aktivního administrátora.');
        }

        try {
            $username = $user->username;
            $user->delete();
        } catch (QueryException) {
            return back()->with('error', 'Uživatele nelze smazat, protože má v systému navázané záznamy (události, návrhy nebo registrace). Místo toho účet deaktivujte.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Uživatel {$username} byl smazán.");
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if ($user->role !== 'admin' || !$user->is_active) {
            return false;
        }

        return User::where('role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
