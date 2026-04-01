<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Concours;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('concours')->orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::cases();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
            'role' => 'required|in:admin,chronometreur,vendeur',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé.');
    }

    public function edit(User $user)
    {
        $roles = Role::cases();
        $concours = Concours::orderBy('date_debut', 'desc')->get();
        $assignedConcoursIds = $user->concours()->pluck('concours.id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'concours', 'assignedConcoursIds'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,chronometreur,vendeur',
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé.');
    }

    public function assignConcours(Request $request, User $user)
    {
        $validated = $request->validate([
            'concours_ids' => 'array',
            'concours_ids.*' => 'exists:concours,id',
        ]);

        $user->concours()->sync($validated['concours_ids'] ?? []);

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Concours assignés avec succès.');
    }

    public function resetPassword(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.edit', $user)
                ->with('error', 'Vous ne pouvez pas réinitialiser votre propre mot de passe ici.');
        }

        $newPassword = Str::random(12);
        $user->update(['password' => $newPassword]);

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Mot de passe réinitialisé : ' . $newPassword);
    }
}
