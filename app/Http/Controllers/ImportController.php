<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use App\Models\ImportLog;
use App\Services\CsvImportService;
use App\Services\SifCsvImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function templateSif(): StreamedResponse
    {
        $columns = SifCsvImportService::TEMPLATE_COLUMNS;

        return response()->streamDownload(function () use ($columns) {
            echo implode(';', $columns) . "\n";
        }, 'template_import_sif.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
    public function templateCompet(): StreamedResponse
    {
        $columns = CsvImportService::TEMPLATE_COLUMNS;

        return response()->streamDownload(function () use ($columns) {
            echo implode(';', $columns) . "\n";
        }, 'template_import_compet.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request, Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'fichier' => 'required|file|max:10240|mimes:csv,txt',
        ]);

        $file = $request->file('fichier');

        $format = $concours->type_ffe_sif ? 'ffe_sif' : 'ffe_compet';

        $service = $concours->type_ffe_sif
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

            $total = $result['nb_epreuves'] + $result['nb_cavaliers'] + $result['nb_chevaux'] + $result['nb_engagements'];
            $message = $total > 0
                ? "Import réussi : {$result['nb_epreuves']} épreuves, {$result['nb_cavaliers']} cavaliers, {$result['nb_chevaux']} chevaux, {$result['nb_engagements']} engagements."
                : "Import terminé : toutes les données du fichier existent déjà, rien de nouveau à importer.";

            return redirect()->route('concours.show', $concours)
                ->with('success', $message);
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
