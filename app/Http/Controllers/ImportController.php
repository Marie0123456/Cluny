<?php

namespace App\Http\Controllers;

use App\Models\Concours;
use App\Models\ImportLog;
use App\Services\CsvImportService;
use App\Services\FfeCompetImportService;
use App\Services\FfeCompetService;
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
            'fichier' => 'required|file|max:20480',
        ]);

        $file = $request->file('fichier');

        // FFE Compet : utilise FfeCompetImportService (gère Excel + forfaits)
        if ($concours->type_ffe_compet) {
            $format  = 'ffe_compet';
            $service = new FfeCompetImportService();

            try {
                $result = $service->sync($concours, file_get_contents($file->getRealPath()));

                ImportLog::create([
                    'concours_id'    => $concours->id,
                    'user_id'        => auth()->id(),
                    'nom_fichier'    => $file->getClientOriginalName(),
                    'format'         => $format,
                    'nb_epreuves'    => $result['nb_epreuves'],
                    'nb_engagements' => $result['nb_engagements'],
                    'nb_cavaliers'   => $result['nb_cavaliers'],
                    'nb_chevaux'     => $result['nb_chevaux'],
                    'statut'         => 'succes',
                ]);

                $parts = [];
                if ($result['nb_epreuves'] > 0)    $parts[] = "{$result['nb_epreuves']} épreuves";
                if ($result['nb_cavaliers'] > 0)   $parts[] = "{$result['nb_cavaliers']} cavaliers";
                if ($result['nb_chevaux'] > 0)     $parts[] = "{$result['nb_chevaux']} chevaux";
                if ($result['nb_engagements'] > 0) $parts[] = "{$result['nb_engagements']} engagements";
                if ($result['nb_forfaits'] > 0)    $parts[] = "{$result['nb_forfaits']} forfait(s) détecté(s)";

                $message = ! empty($parts)
                    ? 'Import réussi : ' . implode(', ', $parts) . '.'
                    : 'Import terminé : toutes les données existent déjà, rien de nouveau à importer.';

                return redirect()->route('concours.show', $concours)->with('success', $message);

            } catch (\Throwable $e) {
                ImportLog::create([
                    'concours_id'    => $concours->id,
                    'user_id'        => auth()->id(),
                    'nom_fichier'    => $file->getClientOriginalName(),
                    'format'         => $format,
                    'statut'         => 'erreur',
                    'message_erreur' => $e->getMessage(),
                ]);

                return redirect()->route('concours.show', $concours)
                    ->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
            }
        }

        // FFE SIF : CSV classique
        $format  = 'ffe_sif';
        $service = new SifCsvImportService();

        try {
            $result = $service->import($concours, $file);

            ImportLog::create([
                'concours_id'    => $concours->id,
                'user_id'        => auth()->id(),
                'nom_fichier'    => $file->getClientOriginalName(),
                'format'         => $format,
                'nb_epreuves'    => $result['nb_epreuves'],
                'nb_engagements' => $result['nb_engagements'],
                'nb_cavaliers'   => $result['nb_cavaliers'],
                'nb_chevaux'     => $result['nb_chevaux'],
                'statut'         => 'succes',
            ]);

            $total = $result['nb_epreuves'] + $result['nb_cavaliers'] + $result['nb_chevaux'] + $result['nb_engagements'];
            $message = $total > 0
                ? "Import réussi : {$result['nb_epreuves']} épreuves, {$result['nb_cavaliers']} cavaliers, {$result['nb_chevaux']} chevaux, {$result['nb_engagements']} engagements."
                : "Import terminé : toutes les données du fichier existent déjà, rien de nouveau à importer.";

            return redirect()->route('concours.show', $concours)
                ->with('success', $message);
        } catch (\Exception $e) {
            ImportLog::create([
                'concours_id'    => $concours->id,
                'user_id'        => auth()->id(),
                'nom_fichier'    => $file->getClientOriginalName(),
                'format'         => $format,
                'statut'         => 'erreur',
                'message_erreur' => $e->getMessage(),
            ]);

            return redirect()->route('concours.show', $concours)
                ->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    /**
     * Save FFE Compet credentials for a contest.
     */
    public function saveCredentials(Request $request, Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'ffe_numero_concours' => 'required|string|max:50',
            'ffe_login'           => 'required|string|max:100',
            'ffe_password'        => 'nullable|string|max:255',
        ]);

        // Keep existing password if field left empty
        if (empty($validated['ffe_password'])) {
            unset($validated['ffe_password']);
        }

        $concours->update($validated);

        return redirect()->route('concours.show', $concours)
            ->with('success', 'Identifiants FFE Compet enregistrés.');
    }

    /**
     * Diagnostic : télécharge le fichier FFE Compet et affiche son contenu brut.
     */
    public function diagFfeCompet(Request $request, Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        if (! $concours->ffe_numero_concours || ! $concours->ffe_login || ! $concours->ffe_password) {
            return redirect()->route('concours.show', $concours)
                ->with('error', 'Identifiants FFE Compet non configurés.');
        }

        try {
            $fetchService = new FfeCompetService();
            $content = $fetchService->downloadEngagements(
                $concours->ffe_login,
                $concours->ffe_password,
                $concours->ffe_numero_concours
            );

            $size   = strlen($content);
            $first4 = bin2hex(substr($content, 0, 4));
            $isZip  = str_starts_with($content, "PK\x03\x04");
            $isHtml = str_contains(strtolower(substr($content, 0, 500)), '<html') ||
                      str_contains(strtolower(substr($content, 0, 500)), '<table');

            if ($isZip) {
                $format = 'XLSX (ZIP)';
                $preview = '(fichier binaire XLSX — non affichable)';
            } elseif ($isHtml) {
                $format = 'HTML';
                $preview = htmlspecialchars(substr($content, 0, 2000));
            } else {
                $format = 'Texte / CSV';
                $preview = htmlspecialchars(substr($content, 0, 2000));
            }

            return response("<pre style='font-family:monospace;font-size:13px;padding:20px'>"
                . "<strong>Taille :</strong> {$size} octets\n"
                . "<strong>Magic bytes (hex) :</strong> {$first4}\n"
                . "<strong>Format détecté :</strong> {$format}\n\n"
                . "<strong>Contenu (2000 premiers caractères) :</strong>\n"
                . $preview
                . "</pre>");

        } catch (\Exception $e) {
            return response("<pre style='color:red;padding:20px'>ERREUR : " . htmlspecialchars($e->getMessage()) . "</pre>");
        }
    }


    public function syncFfeCompet(Request $request, Concours $concours)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403);
        }

        if (! $concours->type_ffe_compet) {
            abort(400, 'Ce concours n\'est pas de type FFE Compet.');
        }

        if (! $concours->ffe_numero_concours || ! $concours->ffe_login || ! $concours->ffe_password) {
            return redirect()->route('concours.show', $concours)
                ->with('error', 'Identifiants FFE Compet non configurés. Renseignez le numéro de concours, login et mot de passe.');
        }

        try {
            $fetchService  = new FfeCompetService();
            $importService = new FfeCompetImportService();

            $fileContent = $fetchService->downloadEngagements(
                $concours->ffe_login,
                $concours->ffe_password,
                $concours->ffe_numero_concours
            );

            $result = $importService->sync($concours, $fileContent);

            ImportLog::create([
                'concours_id'    => $concours->id,
                'user_id'        => auth()->id(),
                'nom_fichier'    => 'sync_ffecompet_' . $concours->ffe_numero_concours . '.xls',
                'format'         => 'ffe_compet_sync',
                'nb_epreuves'    => $result['nb_epreuves'],
                'nb_engagements' => $result['nb_engagements'],
                'nb_cavaliers'   => $result['nb_cavaliers'],
                'nb_chevaux'     => $result['nb_chevaux'],
                'statut'         => 'succes',
            ]);

            $parts = [];
            if ($result['nb_epreuves'] > 0) {
                $parts[] = "{$result['nb_epreuves']} épreuves";
            }
            if ($result['nb_cavaliers'] > 0) {
                $parts[] = "{$result['nb_cavaliers']} cavaliers";
            }
            if ($result['nb_chevaux'] > 0) {
                $parts[] = "{$result['nb_chevaux']} chevaux";
            }
            if ($result['nb_engagements'] > 0) {
                $parts[] = "{$result['nb_engagements']} engagements";
            }
            if ($result['nb_forfaits'] > 0) {
                $parts[] = "{$result['nb_forfaits']} forfait(s) détecté(s)";
            }

            $message = ! empty($parts)
                ? 'Synchronisation réussie : ' . implode(', ', $parts) . '.'
                : 'Synchronisation terminée : aucune nouvelle donnée à importer.';

            return redirect()->route('concours.show', $concours)->with('success', $message);

        } catch (\Throwable $e) {
            ImportLog::create([
                'concours_id'    => $concours->id,
                'user_id'        => auth()->id(),
                'nom_fichier'    => 'sync_ffecompet_' . $concours->ffe_numero_concours . '.xls',
                'format'         => 'ffe_compet_sync',
                'statut'         => 'erreur',
                'message_erreur' => $e->getMessage(),
            ]);

            return redirect()->route('concours.show', $concours)
                ->with('error', 'Erreur de synchronisation FFE Compet : ' . $e->getMessage());
        }
    }
}
