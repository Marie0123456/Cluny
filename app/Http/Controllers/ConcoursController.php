<?php

namespace App\Http\Controllers;

use App\Enums\Discipline;
use App\Models\Concours;
use Illuminate\Http\Request;

class ConcoursController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $concours = Concours::orderBy('date_debut', 'desc')->get();
        } else {
            $concours = $user->concours()->orderBy('date_debut', 'desc')->get();
        }

        return view('dashboard', compact('concours'));
    }

    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function create()
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $disciplines = Discipline::cases();
        return view('concours.create', compact('disciplines'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'discipline' => 'required|in:CSO,Dressage,Open',
            'type_ffe_sif' => 'boolean',
            'type_ffe_compet' => 'boolean',
            'grand_national' => 'boolean',
        ]);

        $validated['type_ffe_sif'] = $request->boolean('type_ffe_sif');
        $validated['type_ffe_compet'] = $request->boolean('type_ffe_compet');
        $validated['grand_national'] = $request->boolean('grand_national');

        $concours = Concours::create($validated);

        return redirect()->route('concours.show', $concours)
            ->with('success', 'Concours créé avec succès.');
    }

    public function show(Concours $concours)
    {
        $user = auth()->user();

        if (! $user->isAdmin() && ! $user->concours()->where('concours.id', $concours->id)->exists()) {
            abort(403);
        }

        $concours->loadCount(['epreuves', 'engagements', 'modifications', 'ventes']);

        return view('concours.show', compact('concours'));
    }

    public function edit(Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $disciplines = Discipline::cases();
        return view('concours.edit', compact('concours', 'disciplines'));
    }

    public function update(Request $request, Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'discipline' => 'required|in:CSO,Dressage,Open',
            'type_ffe_sif' => 'boolean',
            'type_ffe_compet' => 'boolean',
            'grand_national' => 'boolean',
        ]);

        $validated['type_ffe_sif'] = $request->boolean('type_ffe_sif');
        $validated['type_ffe_compet'] = $request->boolean('type_ffe_compet');
        $validated['grand_national'] = $request->boolean('grand_national');

        $concours->update($validated);

        return redirect()->route('concours.show', $concours)
            ->with('success', 'Concours mis à jour.');
    }

    public function destroy(Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $concours->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Concours supprimé.');
    }
}
