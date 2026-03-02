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

        // Parse all lines upfront
        $parsedRows = [];
        foreach ($lines as $line) {
            $cols = array_map('trim', explode($separator, $line));
            if (count($cols) < 9) {
                continue;
            }

            $nom = $cols[4];
            $chevalNom = $cols[8];
            if (empty($nom) && empty($chevalNom)) {
                continue;
            }

            // Build epreuve display name
            $discipline = $cols[0];
            $epreuveNom = $cols[1];
            $epreuveLabel = $epreuveNom;
            if (! empty($discipline) && ! str_contains(mb_strtolower($epreuveNom), mb_strtolower($discipline))) {
                $epreuveLabel = $discipline . ' - ' . $epreuveNom;
            }

            $parsedRows[] = [
                'epreuveLabel' => $epreuveLabel,
                'numeroDepart' => $cols[2],
                'licence' => $cols[3],
                'nom' => $nom,
                'prenom' => $cols[5],
                'club' => $cols[6],
                'sire' => $cols[7],
                'chevalNom' => $chevalNom,
            ];
        }

        $counters = [
            'nb_epreuves' => 0,
            'nb_cavaliers' => 0,
            'nb_chevaux' => 0,
            'nb_engagements' => 0,
        ];

        DB::transaction(function () use ($concours, $parsedRows, &$counters) {
            $now = now();

            // === Phase 1: Epreuves ===
            $epreuveCache = $concours->epreuves()->get()->keyBy('nom');
            $nextNumero = (int) ($concours->epreuves()->max('numero') ?? 0) + 1;

            $newEpreuves = [];
            $epreuveNumeros = [];
            foreach ($parsedRows as $row) {
                $label = $row['epreuveLabel'];
                if (! $epreuveCache->has($label) && ! isset($newEpreuves[$label])) {
                    $epreuveNumeros[$label] = $nextNumero++;
                    $newEpreuves[$label] = [
                        'concours_id' => $concours->id,
                        'nom' => $label,
                        'numero' => $epreuveNumeros[$label],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if (! empty($newEpreuves)) {
                Epreuve::insert(array_values($newEpreuves));
                $counters['nb_epreuves'] = count($newEpreuves);
                $epreuveCache = $concours->epreuves()->get()->keyBy('nom');
            }

            // === Phase 2: Cavaliers ===
            // SIF uses dual-key lookup: by num_licence if available, otherwise by (nom, prenom)
            $csvLicences = [];
            $csvCavalierNoms = [];
            foreach ($parsedRows as $row) {
                if (! empty($row['licence'])) {
                    $csvLicences[] = $row['licence'];
                } else {
                    $csvCavalierNoms[] = $row['nom'];
                }
            }

            $cavaliersByLicence = collect();
            if (! empty($csvLicences)) {
                $cavaliersByLicence = Cavalier::whereIn('num_licence', array_unique($csvLicences))
                    ->get()->keyBy('num_licence');
            }
            $cavaliersByName = collect();
            $csvCavalierNoms = array_values(array_unique($csvCavalierNoms));
            if (! empty($csvCavalierNoms)) {
                $cavaliersByName = Cavalier::whereIn('nom', $csvCavalierNoms)->get()
                    ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom);
            }

            $newCavaliers = [];
            // Track which keys we've already queued for creation
            $newCavaliersByLicence = [];
            $newCavaliersByName = [];

            foreach ($parsedRows as $row) {
                if (! empty($row['licence'])) {
                    if (! $cavaliersByLicence->has($row['licence']) && ! isset($newCavaliersByLicence[$row['licence']])) {
                        $newCavaliersByLicence[$row['licence']] = true;
                        $newCavaliers[] = [
                            'nom' => $row['nom'],
                            'prenom' => $row['prenom'],
                            'num_licence' => $row['licence'],
                            'club' => $row['club'] ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    $nameKey = $row['nom'] . '|' . $row['prenom'];
                    if (! $cavaliersByName->has($nameKey) && ! isset($newCavaliersByName[$nameKey])) {
                        $newCavaliersByName[$nameKey] = true;
                        $newCavaliers[] = [
                            'nom' => $row['nom'],
                            'prenom' => $row['prenom'],
                            'num_licence' => null,
                            'club' => $row['club'] ?: null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
            if (! empty($newCavaliers)) {
                foreach (array_chunk($newCavaliers, 500) as $chunk) {
                    Cavalier::insert($chunk);
                }
                $counters['nb_cavaliers'] = count($newCavaliers);
                // Reload caches
                $allLicences = array_values(array_unique(array_map(fn ($r) => $r['licence'], array_filter($parsedRows, fn ($r) => ! empty($r['licence'])))));
                if (! empty($allLicences)) {
                    $cavaliersByLicence = Cavalier::whereIn('num_licence', $allLicences)->get()->keyBy('num_licence');
                }
                $allNoms = array_values(array_unique(array_map(fn ($r) => $r['nom'], $parsedRows)));
                $cavaliersByName = Cavalier::whereIn('nom', $allNoms)->get()
                    ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom);
            }

            // === Phase 3: Chevaux ===
            // SIF uses dual-key: by num_sire if available, otherwise by nom
            $csvSires = [];
            $csvChevalNoms = [];
            foreach ($parsedRows as $row) {
                if (! empty($row['sire'])) {
                    $csvSires[] = $row['sire'];
                } else {
                    $csvChevalNoms[] = $row['chevalNom'];
                }
            }

            $chevauxBySire = collect();
            if (! empty($csvSires)) {
                $chevauxBySire = Cheval::whereIn('num_sire', array_unique($csvSires))
                    ->get()->keyBy('num_sire');
            }
            $chevauxByName = collect();
            $csvChevalNoms = array_values(array_unique($csvChevalNoms));
            if (! empty($csvChevalNoms)) {
                $chevauxByName = Cheval::whereIn('nom', $csvChevalNoms)->get()->keyBy('nom');
            }

            $newChevaux = [];
            $newChevauxBySire = [];
            $newChevauxByName = [];

            foreach ($parsedRows as $row) {
                if (! empty($row['sire'])) {
                    if (! $chevauxBySire->has($row['sire']) && ! isset($newChevauxBySire[$row['sire']])) {
                        $newChevauxBySire[$row['sire']] = true;
                        $newChevaux[] = [
                            'nom' => $row['chevalNom'],
                            'num_sire' => $row['sire'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    if (! $chevauxByName->has($row['chevalNom']) && ! isset($newChevauxByName[$row['chevalNom']])) {
                        $newChevauxByName[$row['chevalNom']] = true;
                        $newChevaux[] = [
                            'nom' => $row['chevalNom'],
                            'num_sire' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
            if (! empty($newChevaux)) {
                foreach (array_chunk($newChevaux, 500) as $chunk) {
                    Cheval::insert($chunk);
                }
                $counters['nb_chevaux'] = count($newChevaux);
                // Reload caches
                $allSires = array_values(array_unique(array_map(fn ($r) => $r['sire'], array_filter($parsedRows, fn ($r) => ! empty($r['sire'])))));
                if (! empty($allSires)) {
                    $chevauxBySire = Cheval::whereIn('num_sire', $allSires)->get()->keyBy('num_sire');
                }
                $allChevalNoms = array_values(array_unique(array_map(fn ($r) => $r['chevalNom'], $parsedRows)));
                $chevauxByName = Cheval::whereIn('nom', $allChevalNoms)->get()->keyBy('nom');
            }

            // === Phase 4: Engagements ===
            $existingEngagements = Engagement::whereIn('epreuve_id', $epreuveCache->pluck('id'))
                ->get()
                ->keyBy(fn ($e) => $e->epreuve_id . '|' . $e->cavalier_id . '|' . $e->cheval_id);

            $newEngagements = [];
            foreach ($parsedRows as $row) {
                $epreuve = $epreuveCache[$row['epreuveLabel']];

                $cavalier = ! empty($row['licence'])
                    ? $cavaliersByLicence[$row['licence']]
                    : $cavaliersByName[$row['nom'] . '|' . $row['prenom']];

                $cheval = ! empty($row['sire'])
                    ? $chevauxBySire[$row['sire']]
                    : $chevauxByName[$row['chevalNom']];

                $engKey = $epreuve->id . '|' . $cavalier->id . '|' . $cheval->id;
                if (! $existingEngagements->has($engKey) && ! isset($newEngagements[$engKey])) {
                    $newEngagements[$engKey] = [
                        'epreuve_id' => $epreuve->id,
                        'cavalier_id' => $cavalier->id,
                        'cheval_id' => $cheval->id,
                        'numero_depart' => $row['numeroDepart'] ?: null,
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
