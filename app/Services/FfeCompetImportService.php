<?php

namespace App\Services;

use App\Enums\ModificationStatut;
use App\Enums\ModificationType;
use App\Models\Cavalier;
use App\Models\Cheval;
use App\Models\Concours;
use App\Models\Engagement;
use App\Models\Epreuve;
use App\Models\Modification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class FfeCompetImportService
{
    /**
     * Import or sync engagements from a raw Excel/HTML file content.
     */
    public function sync(Concours $concours, string $fileContent): array
    {
        $rows = $this->parseFile($fileContent);

        if (empty($rows)) {
            throw new \RuntimeException('Le fichier FFE Compet ne contient aucune donnée.');
        }

        $counters = [
            'nb_epreuves'    => 0,
            'nb_cavaliers'   => 0,
            'nb_chevaux'     => 0,
            'nb_engagements' => 0,
            'nb_forfaits'    => 0,
        ];

        DB::transaction(function () use ($concours, $rows, &$counters) {
            $now = now();

            // === Phase 1 : Épreuves ===
            $epreuveCache = $concours->epreuves()->get()->keyBy('numero');
            $newEpreuves = [];
            foreach ($rows as $row) {
                $numero = $row['epreuve_numero'] ?? null;
                if (! $numero) {
                    continue;
                }
                if (! $epreuveCache->has($numero) && ! isset($newEpreuves[$numero])) {
                    $newEpreuves[$numero] = [
                        'concours_id' => $concours->id,
                        'numero'      => $numero,
                        'nom'         => $row['epreuve_nom'] ?? $numero,
                        'date'        => $row['epreuve_date'] ?? null,
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

            // === Phase 2 : Cavaliers ===
            $csvNoms = array_values(array_unique(array_filter(array_column($rows, 'nom'))));
            $cavalierCache = Cavalier::whereIn('nom', $csvNoms)->get()
                ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom . '|' . ($c->num_licence ?? ''));

            $newCavaliers = [];
            foreach ($rows as $row) {
                $key = ($row['nom'] ?? '') . '|' . ($row['prenom'] ?? '') . '|' . ($row['licence'] ?? '');
                if (! $cavalierCache->has($key) && ! isset($newCavaliers[$key])) {
                    $newCavaliers[$key] = [
                        'nom'             => $row['nom'] ?? '',
                        'prenom'          => $row['prenom'] ?? '',
                        'num_licence'     => $row['licence'] ?: null,
                        'club'            => $row['club'] ?: null,
                        'cre'             => $row['cre'] ?: null,
                        'departement'     => $row['departement'] ?: null,
                        'num_departement' => $row['num_dept'] ?: null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
            if (! empty($newCavaliers)) {
                foreach (array_chunk(array_values($newCavaliers), 500) as $chunk) {
                    Cavalier::insert($chunk);
                }
                $counters['nb_cavaliers'] = count($newCavaliers);
                $cavalierCache = Cavalier::whereIn('nom', $csvNoms)->get()
                    ->keyBy(fn ($c) => $c->nom . '|' . $c->prenom . '|' . ($c->num_licence ?? ''));
            }

            // === Phase 3 : Chevaux ===
            $csvChevalNoms = array_values(array_unique(array_filter(array_column($rows, 'cheval'))));
            $chevalCache = Cheval::whereIn('nom', $csvChevalNoms)->get()
                ->keyBy(fn ($c) => $c->nom . '|' . ($c->num_sire ?? ''));

            $newChevaux = [];
            foreach ($rows as $row) {
                $key = ($row['cheval'] ?? '') . '|' . ($row['sire'] ?? '');
                if (! $chevalCache->has($key) && ! isset($newChevaux[$key])) {
                    $newChevaux[$key] = [
                        'nom'        => $row['cheval'] ?? '',
                        'num_sire'   => $row['sire'] ?: null,
                        'age'        => $this->parseAge($row['age'] ?? ''),
                        'sexe'       => $row['sexe'] ?? null,
                        'robe'       => $row['robe'] ?? null,
                        'race'       => $row['race'] ?? null,
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

            // === Phase 4 : Engagements ===
            $existingEngagements = Engagement::whereIn('epreuve_id', $epreuveCache->pluck('id'))
                ->get()
                ->keyBy(fn ($e) => $e->epreuve_id . '|' . $e->cavalier_id . '|' . $e->cheval_id);

            $newEngagements = [];
            foreach ($rows as $row) {
                $numero = $row['epreuve_numero'] ?? null;
                if (! $numero || ! $epreuveCache->has($numero)) {
                    continue;
                }
                $epreuve     = $epreuveCache[$numero];
                $cavalierKey = ($row['nom'] ?? '') . '|' . ($row['prenom'] ?? '') . '|' . ($row['licence'] ?? '');
                $chevalKey   = ($row['cheval'] ?? '') . '|' . ($row['sire'] ?? '');

                if (! $cavalierCache->has($cavalierKey) || ! $chevalCache->has($chevalKey)) {
                    continue;
                }

                $cavalier = $cavalierCache[$cavalierKey];
                $cheval   = $chevalCache[$chevalKey];
                $engKey   = $epreuve->id . '|' . $cavalier->id . '|' . $cheval->id;

                if (! $existingEngagements->has($engKey) && ! isset($newEngagements[$engKey])) {
                    $newEngagements[$engKey] = [
                        'epreuve_id'    => $epreuve->id,
                        'cavalier_id'   => $cavalier->id,
                        'cheval_id'     => $cheval->id,
                        'numero_depart' => $row['num_depart'] ?: null,
                        'role_cavalier' => $row['role_cavalier'] ?: null,
                        'dept_groom'    => $row['dept_groom'] ?: null,
                        'role_cheval'   => $row['role_cheval'] ?: null,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
            if (! empty($newEngagements)) {
                foreach (array_chunk(array_values($newEngagements), 500) as $chunk) {
                    Engagement::insert($chunk);
                }
                $counters['nb_engagements'] = count($newEngagements);
                $existingEngagements = Engagement::whereIn('epreuve_id', $epreuveCache->pluck('id'))
                    ->get()
                    ->keyBy(fn ($e) => $e->epreuve_id . '|' . $e->cavalier_id . '|' . $e->cheval_id);
            }

            // === Phase 5 : Forfaits → NON_PARTANT ===
            $existingNp = Modification::where('concours_id', $concours->id)
                ->where('type', ModificationType::NON_PARTANT)
                ->pluck('engagement_id')
                ->flip();

            foreach ($rows as $row) {
                if (strtolower(trim($row['statut'] ?? '')) !== 'forfait') {
                    continue;
                }

                $numero = $row['epreuve_numero'] ?? null;
                if (! $numero || ! $epreuveCache->has($numero)) {
                    continue;
                }
                $epreuve     = $epreuveCache[$numero];
                $cavalierKey = ($row['nom'] ?? '') . '|' . ($row['prenom'] ?? '') . '|' . ($row['licence'] ?? '');
                $chevalKey   = ($row['cheval'] ?? '') . '|' . ($row['sire'] ?? '');

                if (! $cavalierCache->has($cavalierKey) || ! $chevalCache->has($chevalKey)) {
                    continue;
                }

                $cavalier = $cavalierCache[$cavalierKey];
                $cheval   = $chevalCache[$chevalKey];
                $engKey   = $epreuve->id . '|' . $cavalier->id . '|' . $cheval->id;

                if (! $existingEngagements->has($engKey)) {
                    continue;
                }

                $engagement = $existingEngagements[$engKey];

                if ($existingNp->has($engagement->id)) {
                    continue;
                }

                Modification::create([
                    'engagement_id' => $engagement->id,
                    'concours_id'   => $concours->id,
                    'type'          => ModificationType::NON_PARTANT,
                    'statut'        => ModificationStatut::FAIT,
                    'description'   => 'Forfait importé depuis FFE Compet',
                    'prix'          => 0,
                    'pf'            => 0,
                ]);

                $counters['nb_forfaits']++;
                $existingNp[$engagement->id] = true;
            }
        });

        return $counters;
    }

    private function parseFile(string $content): array
    {
        // Vieux format binaire XLS (OLE2 : D0 CF 11 E0)
        if (str_starts_with($content, "\xD0\xCF\x11\xE0")) {
            throw new \RuntimeException(
                'Le fichier est au format Excel 97-2003 (.xls binaire). ' .
                'Ouvrez-le dans Excel puis enregistrez-le au format .xlsx, et réimportez-le.'
            );
        }

        // XLSX (fichier ZIP)
        if (str_starts_with($content, "PK\x03\x04")) {
            return $this->parseXlsx($content);
        }

        // Ignorer un éventuel BOM UTF-8 avant de tester HTML
        $sample = ltrim($content, "\xEF\xBB\xBF \t\r\n");

        // HTML déguisé en Excel (courant sur les sites FFE)
        if (str_starts_with(strtolower($sample), '<html') ||
            str_starts_with(strtolower($sample), '<!doctype') ||
            str_contains(strtolower(substr($sample, 0, 500)), '<table')) {
            return $this->parseHtml($content);
        }

        // Fallback : CSV / TSV
        return $this->parseCsv($content);
    }

    private function parseXlsx(string $content): array
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'ffe_') . '.xlsx';
        file_put_contents($tmpFile, $content);

        try {
            $reader = new XlsxReader();
            $reader->open($tmpFile);

            $rawRows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rawRows[] = array_map(
                        fn ($cell) => $this->cellValueToString($cell->getValue()),
                        $row->getCells()
                    );
                }
                break; // first sheet only
            }
            $reader->close();
        } finally {
            @unlink($tmpFile);
        }

        return $this->mapRows($rawRows);
    }

    private function parseHtml(string $content): array
    {
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($content);
        libxml_clear_errors();

        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                $cells[] = trim($td->textContent);
            }
            if (! empty(array_filter($cells))) {
                $rows[] = $cells;
            }
        }

        return $this->mapRows($rows);
    }

    private function parseCsv(string $content): array
    {
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        $lines = array_filter(explode("\n", $content), fn ($l) => trim($l) !== '');
        $lines = array_values($lines);

        $separator = $this->detectSeparator(reset($lines));
        $rawRows = array_map(
            fn ($line) => array_map('trim', explode($separator, $line)),
            $lines
        );

        return $this->mapRows($rawRows);
    }

    private function mapRows(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [];
        }

        $headerIndex = $this->findHeaderRow($rawRows);
        if ($headerIndex === null) {
            return [];
        }

        $headers   = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rawRows[$headerIndex]);
        $columnMap = $this->buildColumnMap($headers);
        $rows      = [];

        for ($i = $headerIndex + 1; $i < count($rawRows); $i++) {
            $raw = $rawRows[$i];
            if (empty(array_filter($raw, fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $row = [];
            foreach ($columnMap as $field => $colIndex) {
                $row[$field] = isset($raw[$colIndex]) ? trim((string) $raw[$colIndex]) : '';
            }

            $row['epreuve_date'] = $this->parseDate($row['epreuve_date'] ?? '');
            $rows[]              = $row;
        }

        return $rows;
    }

    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $line = mb_strtolower(implode(' ', array_map(fn ($v) => (string) $v, $row)));
            // La ligne d'en-tête FFE Compet contient toujours 'licence' et 'sire'
            if (str_contains($line, 'licence') && str_contains($line, 'sire')) {
                return $index;
            }
            // Fallback : au moins 3 mots-clés génériques
            $matches = 0;
            foreach (['epreuve', 'cavalier', 'cheval', 'licence', 'sire', 'nom', 'prenom', 'prénom'] as $h) {
                if (str_contains($line, $h)) {
                    $matches++;
                }
            }
            if ($matches >= 3) {
                return $index;
            }
        }
        return null;
    }

    private function buildColumnMap(array $headers): array
    {
        $joined = implode(' ', $headers);

        // Format FFE Compet réel : colonnes toujours dans le même ordre
        // N°Epr | Nom(épr) | Date | Dép. | Nom(cav) | Prénom | Rôle | Num Licence |
        // Club | CRE | Dept. | N°Dept | Dept.Groom | Nom(cheval) | Rôle | Num Sire |
        // Âge | Sexe | Robe | Race | Statut
        if (str_contains($joined, 'licence') && str_contains($joined, 'sire') && count($headers) >= 15) {
            $result = [
                'epreuve_numero' => 0,
                'epreuve_nom'    => 1,
                'epreuve_date'   => 2,
                'num_depart'     => 3,
                'nom'            => 4,
                'prenom'         => 5,
                'role_cavalier'  => 6,
                'licence'        => 7,
                'club'           => 8,
                'cre'            => 9,
                'departement'    => 10,
                'num_dept'       => 11,
                'dept_groom'     => 12,
                'cheval'         => 13,
                'role_cheval'    => 14,
                'sire'           => 15,
                'age'            => 16,
                'sexe'           => 17,
                'robe'           => 18,
            ];
            // Statut : chercher par nom (position variable selon les exports)
            foreach ($headers as $i => $h) {
                if (str_contains($h, 'statut')) {
                    $result['statut'] = $i;
                    break;
                }
            }
            // Si pas trouvé par nom, prendre la dernière colonne non vide
            if (! isset($result['statut']) && count($headers) > 19) {
                $result['statut'] = count($headers) - 1;
            }
            return $result;
        }

        // Fallback : correspondance par nom de colonne
        $map = [
            'epreuve_numero' => ['epreuve_numero', 'num_epreuve', 'epreuve'],
            'epreuve_nom'    => ['epreuve_nom', 'libelle'],
            'epreuve_date'   => ['epreuve_date', 'date'],
            'num_depart'     => ['num_depart', 'depart', 'dép'],
            'nom'            => ['nom'],
            'prenom'         => ['prenom', 'prénom'],
            'role_cavalier'  => ['role_cavalier', 'rôle'],
            'licence'        => ['licence'],
            'club'           => ['club'],
            'cre'            => ['cre'],
            'departement'    => ['departement', 'département'],
            'num_dept'       => ['num_dept', 'dept'],
            'dept_groom'     => ['groom'],
            'cheval'         => ['cheval'],
            'role_cheval'    => ['role_cheval'],
            'sire'           => ['sire'],
            'age'            => ['age', 'âge'],
            'sexe'           => ['sexe'],
            'robe'           => ['robe'],
            'statut'         => ['statut'],
        ];

        $result = [];
        foreach ($map as $field => $candidates) {
            foreach ($headers as $i => $header) {
                foreach ($candidates as $candidate) {
                    if (str_contains($header, $candidate)) {
                        $result[$field] = $i;
                        break 2;
                    }
                }
            }
        }
        return $result;
    }

    private function cellValueToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if ($value instanceof \DateTimeImmutable || $value instanceof \DateTime) {
            return $value->format('d/m/Y');
        }
        return trim((string) $value);
    }

    private function parseDate(string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        // Excel serial date (numeric)
        if (is_numeric($value)) {
            try {
                return Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    private function detectSeparator(string $line): string
    {
        $counts = [
            "\t" => substr_count($line, "\t"),
            ';'  => substr_count($line, ';'),
            ','  => substr_count($line, ','),
        ];
        return array_search(max($counts), $counts);
    }

    private function parseAge(string $ageStr): ?int
    {
        if (preg_match('/(\d+)/', $ageStr, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
