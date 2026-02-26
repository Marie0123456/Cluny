<?php

namespace App\Services;

use App\Models\Cavalier;
use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CsvImportService
{
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

        // Skip header line
        array_shift($lines);

        $counters = [
            'nb_epreuves' => 0,
            'nb_cavaliers' => 0,
            'nb_chevaux' => 0,
            'nb_engagements' => 0,
        ];

        $separator = $this->detectSeparator($lines[0] ?? '');

        DB::transaction(function () use ($concours, $lines, &$counters, $separator) {
            foreach ($lines as $line) {
                $cols = explode($separator, $line);

                if (count($cols) < 19) {
                    continue;
                }

                // Clean columns
                $cols = array_map('trim', $cols);

                // Epreuve (columns 0-2)
                $epreuve = Epreuve::firstOrCreate(
                    [
                        'concours_id' => $concours->id,
                        'numero' => $cols[0],
                    ],
                    [
                        'nom' => $cols[1],
                        'date' => $this->parseDate($cols[2]),
                    ]
                );
                if ($epreuve->wasRecentlyCreated) {
                    $counters['nb_epreuves']++;
                }

                // Cavalier (columns 4-11)
                $cavalier = Cavalier::firstOrCreate(
                    [
                        'nom' => $cols[4],
                        'prenom' => $cols[5],
                        'num_licence' => $cols[7] ?: null,
                    ],
                    [
                        'club' => $cols[8] ?: null,
                        'cre' => $cols[9] ?: null,
                        'departement' => $cols[10] ?: null,
                        'num_departement' => $cols[11] ?: null,
                    ]
                );
                if ($cavalier->wasRecentlyCreated) {
                    $counters['nb_cavaliers']++;
                }

                // Cheval (columns 13-19)
                $cheval = Cheval::firstOrCreate(
                    [
                        'nom' => $cols[13],
                        'num_sire' => $cols[15] ?: null,
                    ],
                    [
                        'age' => $this->parseAge($cols[16] ?? ''),
                        'sexe' => $cols[17] ?? null,
                        'robe' => $cols[18] ?? null,
                        'race' => $cols[19] ?? null,
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
                        'numero_depart' => $cols[3] ?: null,
                        'role_cavalier' => $cols[6] ?: null,
                        'dept_groom' => $cols[12] ?: null,
                        'role_cheval' => $cols[14] ?: null,
                    ]
                );
                if ($engagement->wasRecentlyCreated) {
                    $counters['nb_engagements']++;
                }
            }
        });

        return $counters;
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

    private function parseDate(string $dateStr): ?string
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            // Format dd/mm/yyyy
            return Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAge(string $ageStr): ?int
    {
        // "7 ans" → 7
        if (preg_match('/(\d+)/', $ageStr, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
