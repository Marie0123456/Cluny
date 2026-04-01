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
        ];

        $indexes = [];
        foreach ($columnMap as $key => $csvColumn) {
            $index = array_search($csvColumn, $header);
            if ($index === false) {
                fclose($handle);
                return back()->with('error', "Colonne manquante dans le CSV : {$csvColumn}");
            }
            $indexes[$key] = $index;
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $numeroCommande = trim($row[$indexes['numero_commande']] ?? '');

            if (empty($numeroCommande)) {
                continue;
            }

            // Skip if already imported
            $exists = CommandeRetrait::where('numero_commande', $numeroCommande)->exists();
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
                'produit' => trim($row[$indexes['produit']] ?? ''),
                'quantite' => (int) ($row[$indexes['quantite']] ?? 1),
                'emplacement_boxes' => trim($row[$indexes['emplacement_boxes']] ?? '') ?: null,
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

    public function toggleRetire(Concours $concours, CommandeRetrait $commandeRetrait)
    {
        $newState = !$commandeRetrait->retire;

        $commandeRetrait->update([
            'retire' => $newState,
            'retired_by' => $newState ? auth()->id() : null,
            'retired_at' => $newState ? now() : null,
        ]);

        return back()->with('success', $newState ? 'Commande marquée comme retirée.' : 'Commande marquée comme non retirée.');
    }
}
