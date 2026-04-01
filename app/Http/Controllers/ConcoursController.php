<?php

namespace App\Http\Controllers;

use App\Enums\Discipline;
use App\Models\Concours;
use App\Models\ConcoursAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $now = now()->startOfDay();
        $concoursFuturs = $concours->where('date_fin', '>=', $now)->values();
        $concoursPasses = $concours->where('date_fin', '<', $now)->values();

        $availableConcours = collect();
        $pendingRequestIds = [];

        if (! $user->isAdmin()) {
            $assignedIds = $user->concours()->pluck('concours.id')->toArray();
            $availableConcours = Concours::whereNotIn('id', $assignedIds)
                ->where('date_fin', '>=', $now)
                ->orderBy('date_debut', 'desc')
                ->get();

            $pendingRequestIds = ConcoursAccessRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->pluck('concours_id')
                ->toArray();
        }

        return view('dashboard', compact('concoursFuturs', 'concoursPasses', 'availableConcours', 'pendingRequestIds'));
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
        return redirect()->route('concours.epreuves.index', $concours);
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

    public function purge(Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        DB::transaction(function () use ($concours) {
            $concours->modifications()->delete();
            $concours->engagements()->delete();
            $concours->epreuves()->delete();
        });

        return redirect()->route('concours.epreuves.index', $concours)
            ->with('success', 'Toutes les donnees importees ont ete supprimees.');
    }
}
