<?php

namespace App\Services;

use App\Models\Cavalier;
use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class SifCsvImportService
{
    /**
     * Column names for the CSV template.
     */
    public const TEMPLATE_COLUMNS = [
        'Numero Concours',
        'Numero Epreuve',
        'Numero Depart',
        'Epreuve',
        'Discipline',
        'Licence',
        'Nom',
        'Prenom',
        'Club',
        'Sire',
        'Cheval',
    ];

    /**
     * Import engagés from an FFE SIF CSV or Excel file.
     *
     * Accepts CSV/TXT (with any separator) or XLSX.
     * Columns (by position): Numero Concours;Numero Epreuve;Numero Depart;Epreuve;Discipline;Licence;Nom;Prenom;Club;Sire;Cheval;...
     */
    public function import(Concours $concours, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rawRows = $extension === 'xlsx'
            ? $this->parseXlsxToRows($file)
            : $this->parseCsvToRows($file);

        if (count($rawRows) < 1) {
            throw new \Exception('Le fichier est vide ou ne contient pas de données.');
        }

        // Detect and skip header row
        if ($this->isHeaderRow($rawRows[0])) {
            array_shift($rawRows);
        }

        if (count($rawRows) < 1) {
            throw new \Exception('Le fichier ne contient pas de données.');
        }

        // Map raw columns to structured rows
        // Columns: 0=NumConcours, 1=NumEpreuve, 2=NumDepart, 3=Epreuve, 4=Discipline, 5=Licence, 6=Nom, 7=Prenom, 8=Club, 9=Sire, 10=Cheval
        $parsedRows = [];
        foreach ($rawRows as $cols) {
            if (count($cols) < 11) {
                continue;
            }

            $nom = $cols[6];
            $chevalNom = $cols[10];
            if (empty($nom) && empty($chevalNom)) {
                continue;
            }

            $discipline    = $cols[4];
            $epreuveNom    = $cols[3];
            $epreuveNumero = trim($cols[1]);
            // If the CSV has no numero, generate a unique fallback key per epreuve name
            if ($epreuveNumero === '') {
                $epreuveNumero = 'auto-' . $epreuveNom;
            }

            $epreuveLabel = $epreuveNom;
            if (! empty($discipline) && ! str_contains(mb_strtolower($epreuveNom), mb_strtolower($discipline))) {
                $epreuveLabel = $discipline . ' - ' . $epreuveNom;
            }

            $parsedRows[] = [
                'epreuveNumero' => $epreuveNumero,
                'epreuveNom'    => $epreuveLabel,
                'numeroDepart'  => $cols[2],
                'licence'       => $cols[5],
                'nom'           => $nom,
                'prenom'        => $cols[7],
                'club'          => $cols[8],
                'sire'          => $cols[9],
                'chevalNom'     => $chevalNom,
            ];
        }

        $counters = [
            'nb_epreuves'    => 0,
            'nb_cavaliers'   => 0,
            'nb_chevaux'     => 0,
            'nb_engagements' => 0,
        ];

        DB::transaction(function () use ($concours, $parsedRows, &$counters) {
            $now = now();

            // === Phase 1: Epreuves ===
            // Cache keyed by numero — two épreuves with the same name but different numero are distinct
            $epreuveCache = $concours->epreuves()->get()->keyBy('numero');

            $newEpreuves = [];
            foreach ($parsedRows as $row) {
                $num = $row['epreuveNumero'];
                if (! $epreuveCache->has($num) && ! isset($newEpreuves[$num])) {
                    $newEpreuves[$num] = [
                        'concours_id' => $concours->id,
                        'nom'         => $row['epreuveNom'],
                        'numero'      => $num,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
            if (! empty($newEpreuves)) {
                Epreuve::insert(array_values($newEpreuves));
                $counters['nb_epreuves'] = count($newEpreuves);
                $epreuveCache = $concours->epreuves()->get()->keyBy('numero');
            }

            // === Phase 2: Cavaliers ===
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
            $newCavaliersByLicence = [];
            $newCavaliersByName = [];

            foreach ($parsedRows as $row) {
                if (! empty($row['licence'])) {
                    if (! $cavaliersByLicence->has($row['licence']) && ! isset($newCavaliersByLicence[$row['licence']])) {
                        $newCavaliersByLicence[$row['licence']] = true;
                        $newCavaliers[] = [
                            'nom'          => $row['nom'],
                            'prenom'       => $row['prenom'],
                            'num_licence'  => $row['licence'],
                            'club'         => $row['club'] ?: null,
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                } else {
                    $nameKey = $row['nom'] . '|' . $row['prenom'];
                    if (! $cavaliersByName->has($nameKey) && ! isset($newCavaliersByName[$nameKey])) {
                        $newCavaliersByName[$nameKey] = true;
                        $newCavaliers[] = [
                            'nom'         => $row['nom'],
                            'prenom'      => $row['prenom'],
                            'num_licence' => null,
                            'club'        => $row['club'] ?: null,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                }
            }
            if (! empty($newCavaliers)) {
                foreach (array_chunk($newCavaliers, 500) as $chunk) {
                    Cavalier::insert($chunk);
                }
                $counters['nb_cavaliers'] = count($newCavaliers);
                $allLicences = array_values(array_unique(array_map(fn ($r) => $r['licence'], array_filter($parsedRows, fn ($r) => ! empty($r['licence'])))));
                if (! empty($allLicences)) {
                    $cavaliersByLicence = Cavalier::whereIn('num_licence', $allLicences)->get()->keyBy('num_licence');
                }
                $allNoms = array_values(array_unique(array_map(fn ($r) => $r['nom'], $parsedRows)));
                $cavaliersByName = Cavalier::whereIn('nom', $allNoms)->get()
                    ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom);
            }

            // === Phase 3: Chevaux ===
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
                            'nom'        => $row['chevalNom'],
                            'num_sire'   => $row['sire'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                } else {
                    if (! $chevauxByName->has($row['chevalNom']) && ! isset($newChevauxByName[$row['chevalNom']])) {
                        $newChevauxByName[$row['chevalNom']] = true;
                        $newChevaux[] = [
                            'nom'        => $row['chevalNom'],
                            'num_sire'   => null,
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
                $epreuve  = $epreuveCache[$row['epreuveNumero']];
                $cavalier = ! empty($row['licence'])
                    ? $cavaliersByLicence[$row['licence']]
                    : $cavaliersByName[$row['nom'] . '|' . $row['prenom']];
                $cheval = ! empty($row['sire'])
                    ? $chevauxBySire[$row['sire']]
                    : $chevauxByName[$row['chevalNom']];

                $engKey = $epreuve->id . '|' . $cavalier->id . '|' . $cheval->id;
                if (! $existingEngagements->has($engKey) && ! isset($newEngagements[$engKey])) {
                    $newEngagements[$engKey] = [
                        'epreuve_id'     => $epreuve->id,
                        'cavalier_id'    => $cavalier->id,
                        'cheval_id'      => $cheval->id,
                        'numero_depart'  => $row['numeroDepart'] ?: null,
                        'created_at'     => $now,
                        'updated_at'     => $now,
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

    /**
     * Parse an XLSX file into a 2D array of trimmed strings.
     */
    private function parseXlsxToRows(UploadedFile $file): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sif_') . '.xlsx';
        copy($file->getRealPath(), $tmpFile);

        $rows = [];
        try {
            $reader = new XlsxReader();
            $reader->open($tmpFile);
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = array_map(
                        fn ($cell) => trim((string) $cell->getValue()),
                        $row->getCells()
                    );
                    if (! empty(array_filter($cells, fn ($c) => $c !== ''))) {
                        $rows[] = $cells;
                    }
                }
                break; // first sheet only
            }
            $reader->close();
        } finally {
            @unlink($tmpFile);
        }

        return $rows;
    }

    /**
     * Parse a CSV/TXT file into a 2D array of trimmed strings.
     */
    private function parseCsvToRows(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $lines = array_values(array_filter(
            explode("\n", $content),
            fn ($line) => trim($line) !== ''
        ));

        if (empty($lines)) {
            return [];
        }

        $separator = $this->detectSeparator($lines[0]);

        return array_map(
            fn ($line) => array_map('trim', explode($separator, $line)),
            $lines
        );
    }

    /**
     * Detect if the first row is a header row by matching known keywords.
     */
    private function isHeaderRow(array $cols): bool
    {
        $headerKeywords = ['discipline', 'epreuve', 'licence', 'nom', 'prenom', 'cheval'];
        $matches = 0;
        foreach ($headerKeywords as $keyword) {
            foreach ($cols as $col) {
                if (str_contains(mb_strtolower($col), $keyword)) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches >= 3;
    }

    private function detectSeparator(string $line): string
    {
        $tabCount       = substr_count($line, "\t");
        $semicolonCount = substr_count($line, ';');
        $commaCount     = substr_count($line, ',');

        if ($tabCount >= $semicolonCount && $tabCount >= $commaCount) {
            return "\t";
        }
        if ($semicolonCount >= $commaCount) {
            return ';';
        }

        return ',';
    }
}
