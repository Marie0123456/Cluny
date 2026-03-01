<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use App\Models\ImportLog;
use App\Services\CsvImportService;
use App\Services\SifCsvImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(Request $request, Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'fichier' => 'required|file|max:10240',
            'format' => 'required|in:ffe_compet,ffe_sif',
        ]);

        $file = $request->file('fichier');
        $format = $request->input('format');

        $service = $format === 'ffe_sif'
            ? new SifCsvImportService()
            : new CsvImportService();

        try {
            $result = $service->import($concours, $file);

            ImportLog::create([
                'concours_id' => $concours->id,
                'user_id' => auth()->id(),
                'nom_fichier' => $file->getClientOriginalName(),
                'format' => $format,
                'nb_epreuves' => $result['nb_epreuves'],
                'nb_engagements' => $result['nb_engagements'],
                'nb_cavaliers' => $result['nb_cavaliers'],
                'nb_chevaux' => $result['nb_chevaux'],
                'statut' => 'succes',
            ]);

            return redirect()->route('concours.engages.index', $concours)
                ->with('success', "Import réussi : {$result['nb_epreuves']} épreuves, {$result['nb_cavaliers']} cavaliers, {$result['nb_chevaux']} chevaux, {$result['nb_engagements']} engagements.");
        } catch (\Exception $e) {
            ImportLog::create([
                'concours_id' => $concours->id,
                'user_id' => auth()->id(),
                'nom_fichier' => $file->getClientOriginalName(),
                'format' => $format,
                'statut' => 'erreur',
                'message_erreur' => $e->getMessage(),
            ]);

            return redirect()->route('concours.show', $concours)
                ->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
}
