<?php

namespace App\Services;

use App\Models\Cavalier;
use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SifCsvImportService
{
    /**
     * Import engagés from an FFE SIF CSV file.
     *
     * Expected header: Discipline;Epreuve;Numero Depart;Licence;Nom;Prenom;Club;Sire;Cheval
     */
    public function import(Concours $concours, UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());

        // Handle encoding (FFE files may be ISO-8859-1)
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $lines = explode("\n", $content);
        $lines = array_filter($lines, fn ($line) => trim($line) !== '');

        if (count($lines) < 2) {
            throw new \Exception('Le fichier est vide ou ne contient pas de données.');
        }

        // Validate header
        $header = array_shift($lines);
        $this->validateHeader($header);

        $separator = $this->detectSeparator($header);

        $counters = [
            'nb_epreuves' => 0,
            'nb_cavaliers' => 0,
            'nb_chevaux' => 0,
            'nb_engagements' => 0,
        ];

        DB::transaction(function () use ($concours, $lines, &$counters, $separator) {
            $epreuveNumeros = [];
            $nextNumero = ($concours->epreuves()->max('numero') ?? 0) + 1;

            foreach ($lines as $line) {
                $cols = explode($separator, $line);

                if (count($cols) < 9) {
                    continue;
                }

                $cols = array_map('trim', $cols);

                $discipline = $cols[0];
                $epreuveNom = $cols[1];
                $numeroDepart = $cols[2];
                $licence = $cols[3];
                $nom = $cols[4];
                $prenom = $cols[5];
                $club = $cols[6];
                $sire = $cols[7];
                $cheval = $cols[8];

                // Skip empty lines
                if (empty($nom) && empty($cheval)) {
                    continue;
                }

                // Build epreuve display name: "Discipline - Epreuve" or just "Epreuve"
                $epreuveLabel = $epreuveNom;
                if (! empty($discipline) && ! str_contains(mb_strtolower($epreuveNom), mb_strtolower($discipline))) {
                    $epreuveLabel = $discipline . ' - ' . $epreuveNom;
                }

                // Auto-assign epreuve numero based on first appearance
                if (! isset($epreuveNumeros[$epreuveLabel])) {
                    $epreuveNumeros[$epreuveLabel] = $nextNumero++;
                }

                // Epreuve
                $epreuve = Epreuve::firstOrCreate(
                    [
                        'concours_id' => $concours->id,
                        'nom' => $epreuveLabel,
                    ],
                    [
                        'numero' => $epreuveNumeros[$epreuveLabel],
                    ]
                );
                if ($epreuve->wasRecentlyCreated) {
                    $counters['nb_epreuves']++;
                }

                // Cavalier
                $cavalierData = ['nom' => $nom, 'prenom' => $prenom];
                if (! empty($licence)) {
                    $cavalierData['num_licence'] = $licence;
                }

                $cavalier = Cavalier::firstOrCreate(
                    ! empty($licence)
                        ? ['num_licence' => $licence]
                        : ['nom' => $nom, 'prenom' => $prenom],
                    array_merge($cavalierData, [
                        'club' => $club ?: null,
                    ])
                );
                if ($cavalier->wasRecentlyCreated) {
                    $counters['nb_cavaliers']++;
                }

                // Cheval
                $cheval = Cheval::firstOrCreate(
                    ! empty($sire)
                        ? ['num_sire' => $sire]
                        : ['nom' => $cols[8]],
                    [
                        'nom' => $cols[8],
                        'num_sire' => $sire ?: null,
                    ]
                );
                if ($cheval->wasRecentlyCreated) {
                    $counters['nb_chevaux']++;
                }

                // Engagement
                $engagement = Engagement::firstOrCreate(
                    [
                        'epreuve_id' => $epreuve->id,
                        'cavalier_id' => $cavalier->id,
                        'cheval_id' => $cheval->id,
                    ],
                    [
                        'numero_depart' => $numeroDepart ?: null,
                    ]
                );
                if ($engagement->wasRecentlyCreated) {
                    $counters['nb_engagements']++;
                }
            }
        });

        return $counters;
    }

    private function validateHeader(string $header): void
    {
        $separator = $this->detectSeparator($header);
        $cols = array_map(fn ($c) => mb_strtolower(trim($c)), explode($separator, $header));

        $required = ['discipline', 'epreuve', 'licence', 'nom', 'prenom', 'cheval'];
        $missing = [];

        foreach ($required as $col) {
            $found = false;
            foreach ($cols as $headerCol) {
                if (str_contains($headerCol, $col)) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $missing[] = $col;
            }
        }

        if (! empty($missing)) {
            throw new \Exception('En-tête FFE SIF invalide. Colonnes manquantes : ' . implode(', ', $missing));
        }
    }

    private function detectSeparator(string $line): string
    {
        $tabCount = substr_count($line, "\t");
        $semicolonCount = substr_count($line, ';');
        $commaCount = substr_count($line, ',');

        if ($tabCount >= $semicolonCount && $tabCount >= $commaCount) {
            return "\t";
        }
        if ($semicolonCount >= $commaCount) {
            return ';';
        }

        return ',';
    }
}
