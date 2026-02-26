<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    public function index()
    {
        $produits = Produit::orderBy('nom')->get();
        return view('admin.produits.index', compact('produits'));
    }

    public function create()
    {
        return view('admin.produits.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prix_ttc' => 'required|numeric|min:0',
            'tva' => 'required|numeric|min:0|max:100',
        ]);

        Produit::create($validated);

        return redirect()->route('admin.produits.index')
            ->with('success', 'Produit créé.');
    }

    public function edit(Produit $produit)
    {
        return view('admin.produits.edit', compact('produit'));
    }

    public function update(Request $request, Produit $produit)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prix_ttc' => 'required|numeric|min:0',
            'tva' => 'required|numeric|min:0|max:100',
        ]);

        $produit->update($validated);

        return redirect()->route('admin.produits.index')
            ->with('success', 'Produit mis à jour.');
    }

    public function toggleActif(Produit $produit)
    {
        $produit->update(['actif' => !$produit->actif]);

        return redirect()->route('admin.produits.index')
            ->with('success', $produit->actif ? 'Produit activé.' : 'Produit désactivé.');
    }
}
