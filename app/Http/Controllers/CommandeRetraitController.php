<?php

namespace App\Http\Controllers;

use App\Models\CommandeRetrait;
use App\Models\Concours;
use Illuminate\Http\Request;

class CommandeRetraitController extends Controller
{
    public function index(Concours $concours)
    {
        $commandes = CommandeRetrait::with('retiredByUser:id,name')
            ->where('concours_id', $concours->id)
            ->orderBy('date_commande', 'desc')
            ->orderBy('numero_commande', 'desc')
            ->get();

        return view('concours.commande-retraits.index', compact('concours', 'commandes'));
    }

    public function import(Request $request, Concours $concours)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv_file');

        // Read file content, strip BOM, and ensure UTF-8
        $content = file_get_contents($file->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $tempFile = tmpfile();
        fwrite($tempFile, $content);
        rewind($tempFile);
        $handle = $tempFile;

        // Read header line
        $header = fgetcsv($handle, 0, ';');

        if (!$header) {
            fclose($handle);
            return back()->with('error', 'Le fichier CSV est vide.');
        }

        // Map header columns
        $header = array_map('trim', $header);
        $columnMap = [
            'numero_commande' => 'Numéro de commande',
            'date_commande' => 'Date de commande',
            'prenom' => 'Prénom (Facturation)',
            'nom' => 'Nom (Facturation)',
            'produit' => 'Produit',
            'quantite' => 'Quantité',
            'emplacement_boxes' => 'emplacement et numeros de boxes',
            'note_client' => 'Note du client',
        ];

        $optionalColumns = ['note_client'];

        $indexes = [];
        foreach ($columnMap as $key => $csvColumn) {
            $index = array_search($csvColumn, $header);
            if ($index === false && !in_array($key, $optionalColumns)) {
                fclose($handle);
                return back()->with('error', "Colonne manquante dans le CSV : {$csvColumn}");
            }
            if ($index !== false) {
                $indexes[$key] = $index;
            }
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $numeroCommande = trim($row[$indexes['numero_commande']] ?? '');

            if (empty($numeroCommande)) {
                continue;
            }

            // Skip if already imported (same numero_commande + produit), including soft-deleted ones
            $produit = trim($row[$indexes['produit']] ?? '');
            $exists = CommandeRetrait::withTrashed()
                ->where('concours_id', $concours->id)
                ->where('numero_commande', $numeroCommande)
                ->where('produit', $produit)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            // Parse date
            $dateStr = trim($row[$indexes['date_commande']] ?? '');
            $date = null;
            if ($dateStr) {
                try {
                    $date = \Carbon\Carbon::parse($dateStr)->toDateString();
                } catch (\Exception $e) {
                    $date = now()->toDateString();
                }
            }

            CommandeRetrait::create([
                'concours_id' => $concours->id,
                'numero_commande' => $numeroCommande,
                'date_commande' => $date ?? now()->toDateString(),
                'prenom' => trim($row[$indexes['prenom']] ?? ''),
                'nom' => trim($row[$indexes['nom']] ?? ''),
                'produit' => $produit,
                'quantite' => (int) ($row[$indexes['quantite']] ?? 1),
                'emplacement_boxes' => trim($row[$indexes['emplacement_boxes']] ?? '') ?: null,
                'note_client' => isset($indexes['note_client']) ? (trim($row[$indexes['note_client']] ?? '') ?: null) : null,
            ]);

            $imported++;
        }

        fclose($handle);

        $message = "{$imported} commande(s) importée(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} commande(s) déjà existante(s) ignorée(s).";
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Concours $concours, CommandeRetrait $commandeRetrait)
    {
        $validated = $request->validate([
            'prenom' => 'nullable|string|max:255',
            'nom' => 'nullable|string|max:255',
            'produit' => 'required|string|max:255',
            'quantite' => 'required|integer|min:1',
            'emplacement_boxes' => 'nullable|string|max:255',
            'note_client' => 'nullable|string|max:1000',
        ]);

        // Clamp quantite_retiree if new quantite is lower
        if ($commandeRetrait->quantite_retiree > $validated['quantite']) {
            $validated['quantite_retiree'] = $validated['quantite'];
        }

        $commandeRetrait->update($validated);

        // Sync retire flag + retired_by/at with quantite_retiree
        $this->syncRetireState($commandeRetrait->fresh());

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Commande modifiée.');
    }

    public function destroy(Request $request, Concours $concours, CommandeRetrait $commandeRetrait)
    {
        $commandeRetrait->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Commande supprimée.');
    }

    public function setQuantiteRetiree(Request $request, Concours $concours, CommandeRetrait $commandeRetrait)
    {
        $validated = $request->validate([
            'quantite_retiree' => 'required|integer|min:0|max:' . $commandeRetrait->quantite,
        ]);

        $qty = (int) $validated['quantite_retiree'];
        $isComplete = $qty >= $commandeRetrait->quantite;

        $commandeRetrait->update([
            'quantite_retiree' => $qty,
            'retire' => $isComplete,
            'retired_by' => $qty > 0 ? auth()->id() : null,
            'retired_at' => $qty > 0 ? now() : null,
        ]);

        if ($request->expectsJson()) {
            $commandeRetrait->load('retiredByUser:id,name');
            return response()->json([
                'success' => true,
                'quantite_retiree' => $commandeRetrait->quantite_retiree,
                'statut' => $commandeRetrait->statut_retrait,
                'retired_by_name' => $commandeRetrait->retiredByUser?->name,
                'retired_at' => $commandeRetrait->retired_at?->format('d/m/Y à H:i'),
            ]);
        }

        return back()->with('success', 'Quantité retirée mise à jour.');
    }

    private function syncRetireState(CommandeRetrait $commande): void
    {
        $isComplete = $commande->quantite_retiree >= $commande->quantite && $commande->quantite_retiree > 0;
        $commande->update([
            'retire' => $isComplete,
        ]);
    }
}
