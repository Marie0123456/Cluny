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
        $lines = array_values($lines);

        if (count($lines) < 2) {
            throw new \Exception('Le fichier est vide ou ne contient pas de données.');
        }

        // Auto-detect header row (may not be line 1 if file has junk lines at top)
        $headerIndex = $this->detectHeaderRow($lines);
        $lines = array_slice($lines, $headerIndex + 1);

        $separator = $this->detectSeparator(reset($lines));

        // Parse all lines upfront
        $parsedLines = [];
        foreach ($lines as $line) {
            $cols = array_map('trim', explode($separator, $line));
            if (count($cols) >= 19) {
                $parsedLines[] = $cols;
            }
        }

        $counters = [
            'nb_epreuves' => 0,
            'nb_cavaliers' => 0,
            'nb_chevaux' => 0,
            'nb_engagements' => 0,
        ];

        DB::transaction(function () use ($concours, $parsedLines, &$counters) {
            $now = now();

            // === Phase 1: Epreuves ===
            $epreuveCache = $concours->epreuves()->get()->keyBy('numero');
            $newEpreuves = [];
            foreach ($parsedLines as $cols) {
                $numero = $cols[0];
                if (! $epreuveCache->has($numero) && ! isset($newEpreuves[$numero])) {
                    $newEpreuves[$numero] = [
                        'concours_id' => $concours->id,
                        'numero' => $numero,
                        'nom' => $cols[1],
                        'date' => $this->parseDate($cols[2]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (! empty($newEpreuves)) {
                Epreuve::insert(array_values($newEpreuves));
                $counters['nb_epreuves'] = count($newEpreuves);
                $epreuveCache = $concours->epreuves()->get()->keyBy('numero');
            }

            // === Phase 2: Cavaliers ===
            $csvCavalierNoms = array_values(array_unique(array_map(fn ($c) => $c[4], $parsedLines)));
            $cavalierCache = Cavalier::whereIn('nom', $csvCavalierNoms)->get()
                ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom . '|' . ($c->num_licence ?? ''));

            $newCavaliers = [];
            foreach ($parsedLines as $cols) {
                $key = $cols[4] . '|' . $cols[5] . '|' . ($cols[7] ?: '');
                if (! $cavalierCache->has($key) && ! isset($newCavaliers[$key])) {
                    $newCavaliers[$key] = [
                        'nom' => $cols[4],
                        'prenom' => $cols[5],
                        'num_licence' => $cols[7] ?: null,
                        'club' => $cols[8] ?: null,
                        'cre' => $cols[9] ?: null,
                        'departement' => $cols[10] ?: null,
                        'num_departement' => $cols[11] ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (! empty($newCavaliers)) {
                foreach (array_chunk(array_values($newCavaliers), 500) as $chunk) {
                    Cavalier::insert($chunk);
                }
                $counters['nb_cavaliers'] = count($newCavaliers);
                $cavalierCache = Cavalier::whereIn('nom', $csvCavalierNoms)->get()
                    ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom . '|' . ($c->num_licence ?? ''));
            }

            // === Phase 3: Chevaux ===
            $csvChevalNoms = array_values(array_unique(array_map(fn ($c) => $c[13], $parsedLines)));
            $chevalCache = Cheval::whereIn('nom', $csvChevalNoms)->get()
                ->keyBy(fn ($c) => $c->nom . '|' . ($c->num_sire ?? ''));

            $newChevaux = [];
            foreach ($parsedLines as $cols) {
                $key = $cols[13] . '|' . ($cols[15] ?: '');
                if (! $chevalCache->has($key) && ! isset($newChevaux[$key])) {
                    $newChevaux[$key] = [
                        'nom' => $cols[13],
                        'num_sire' => $cols[15] ?: null,
                        'age' => $this->parseAge($cols[16] ?? ''),
                        'sexe' => $cols[17] ?? null,
                        'robe' => $cols[18] ?? null,
                        'race' => $cols[19] ?? null,
                        'etat' => $cols[20] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (! empty($newChevaux)) {
                foreach (array_chunk(array_values($newChevaux), 500) as $chunk) {
                    Cheval::insert($chunk);
                }
                $counters['nb_chevaux'] = count($newChevaux);
                $chevalCache = Cheval::whereIn('nom', $csvChevalNoms)->get()
                    ->keyBy(fn ($c) => $c->nom . '|' . ($c->num_sire ?? ''));
            }

            // === Phase 4: Engagements ===
            $existingEngagements = Engagement::whereIn('epreuve_id', $epreuveCache->pluck('id'))
                ->get()
                ->keyBy(fn ($e) => $e->epreuve_id . '|' . $e->cavalier_id . '|' . $e->cheval_id);

            $newEngagements = [];
            foreach ($parsedLines as $cols) {
                $epreuve = $epreuveCache[$cols[0]];
                $cavalierKey = $cols[4] . '|' . $cols[5] . '|' . ($cols[7] ?: '');
                $chevalKey = $cols[13] . '|' . ($cols[15] ?: '');
                $cavalier = $cavalierCache[$cavalierKey];
                $cheval = $chevalCache[$chevalKey];

                $engKey = $epreuve->id . '|' . $cavalier->id . '|' . $cheval->id;
                if (! $existingEngagements->has($engKey) && ! isset($newEngagements[$engKey])) {
                    $newEngagements[$engKey] = [
                        'epreuve_id' => $epreuve->id,
                        'cavalier_id' => $cavalier->id,
                        'cheval_id' => $cheval->id,
                        'numero_depart' => $cols[3] ?: null,
                        'role_cavalier' => $cols[6] ?: null,
                        'dept_groom' => $cols[12] ?: null,
                        'role_cheval' => $cols[14] ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (! empty($newEngagements)) {
                foreach (array_chunk(array_values($newEngagements), 500) as $chunk) {
                    Engagement::insert($chunk);
                }
                $counters['nb_engagements'] = count($newEngagements);
            }
        });

        return $counters;
    }

    private function detectHeaderRow(array $lines): int
    {
        $knownHeaders = ['epreuve', 'cavalier', 'cheval', 'licence', 'sire', 'race'];

        foreach ($lines as $index => $line) {
            $lower = mb_strtolower($line);
            $matches = 0;
            foreach ($knownHeaders as $header) {
                if (str_contains($lower, $header)) {
                    $matches++;
                }
            }
            // If at least 3 known headers found, this is the header row
            if ($matches >= 3) {
                return $index;
            }
        }

        // Fallback: assume first line is header
        return 0;
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
