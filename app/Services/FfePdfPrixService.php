<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class FfePdfPrixService
{
    /**
     * Parse a PDF file and return detected épreuve number → price mappings.
     *
     * Handles the standard FFE Compet programme layout where each épreuve block
     * looks like:
     *   "Épreuve 7 (à la suite)"
     *   ...
     *   "Engagement : 30,00 €"     ← total price (may be followed by sub-breakdown)
     *
     * Falls back to generic strategies for other layouts.
     *
     * @return array<string, float>  e.g. ['1' => 38.00, '7' => 30.00]
     */
    public function parse(string $pdfContent): array
    {
        $parser = new Parser();
        $text   = $parser->parseContent($pdfContent)->getText();

        // Primary strategy: "Épreuve N ... Engagement : XX,XX €"
        $results = $this->strategyFfeCompet($text);

        // Generic fallback strategies for other PDF layouts
        if (empty($results)) {
            $results = $this->strategyGeneric($text);
        }

        return $results;
    }

    /** Returns the raw extracted text (useful for debugging). */
    public function rawText(string $pdfContent): string
    {
        $parser = new Parser();
        return $parser->parseContent($pdfContent)->getText();
    }

    // -------------------------------------------------------------------------
    // Strategy A – FFE Compet "Épreuve N / Engagement : XX,XX €" format
    // -------------------------------------------------------------------------

    private function strategyFfeCompet(string $text): array
    {
        $results = [];
        $lines   = preg_split('/\r?\n/', $text);
        $total   = count($lines);

        for ($i = 0; $i < $total; $i++) {
            $line = trim($lines[$i]);

            // Detect "Épreuve N" header (with optional suffix like "(à la suite)")
            if (!preg_match('/^É?preuve\s+0*(\d{1,3})\b/ui', $line, $m)) {
                continue;
            }

            $numero = (string) (int) $m[1];
            if ((int) $numero < 1 || (int) $numero > 200) {
                continue;
            }

            if (isset($results[$numero])) {
                continue;
            }

            // Look for "Engagement : XX,XX" within the next 20 lines
            for ($j = $i + 1; $j < min($i + 20, $total); $j++) {
                $nl = trim($lines[$j]);

                // Stop if we hit the next épreuve block
                if ($j > $i + 2 && preg_match('/^É?preuve\s+0*\d{1,3}\b/ui', $nl)) {
                    break;
                }

                // Match "Engagement : 30,00 €" or "Droit d'engagement : 30,00 €"
                // Take only the first price (ignores "dont X,XX € de part fixe" suffix)
                if (preg_match('/(?:Droits?\s+d\'engagement|Engagement)\s*:\s*(\d{1,4}[,\.]\d{2})/ui', $nl, $pm)) {
                    $price = (float) str_replace(',', '.', $pm[1]);
                    if ($price >= 0.01 && $price <= 2000.0) {
                        $results[$numero] = $price;
                        break;
                    }
                }
            }
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // Strategy B – Generic: number at line start + price on same / nearby line
    // -------------------------------------------------------------------------

    private function strategyGeneric(string $text): array
    {
        $results = [];
        $lines   = preg_split('/\r?\n/', $text);
        $total   = count($lines);

        // Pass 1: line-start number + price on the same or next few lines
        for ($i = 0; $i < $total; $i++) {
            $line = trim($lines[$i]);
            if (strlen($line) < 2) {
                continue;
            }

            // Line starts with a 1–3 digit number (optional "E", "N°" prefix)
            if (!preg_match('/^(?:E(?:p\.?)?\s*|N\s*°\s*)?0*(\d{1,3})\b/ui', $line, $m)) {
                continue;
            }

            $numero = (string) (int) $m[1];
            if ((int) $numero < 1 || (int) $numero > 200 || isset($results[$numero])) {
                continue;
            }

            // Try current line first, then the next 4 lines
            $searchLines = [$line];
            for ($j = $i + 1; $j < min($i + 5, $total); $j++) {
                $nl = trim($lines[$j]);
                if ($j > $i + 1 && preg_match('/^(?:E(?:p\.?)?\s*|N\s*°\s*)?0*\d{1,3}\b/ui', $nl)) {
                    break;
                }
                $searchLines[] = $nl;
            }

            foreach ($searchLines as $sl) {
                $price = $this->findPriceInLine($sl);
                if ($price !== null) {
                    $results[$numero] = $price;
                    break;
                }
            }
        }

        // Pass 2: explicit "Prix : XX,XX" labels
        for ($i = 0; $i < $total; $i++) {
            $line = trim($lines[$i]);
            if (!preg_match('/prix\s*:?\s*(\d{1,4}[,\.]\d{2})/ui', $line, $pm)) {
                continue;
            }

            $priceVal = (float) str_replace(',', '.', $pm[1]);
            if ($priceVal < 5 || $priceVal > 2000) {
                continue;
            }

            // Walk back to find the nearest épreuve number header
            for ($j = $i - 1; $j >= max(0, $i - 12); $j--) {
                $pl = trim($lines[$j]);
                if (preg_match('/^(?:E(?:p\.?)?\s*|N\s*°\s*)?0*(\d{1,3})\b/ui', $pl, $nm)) {
                    $numero = (string) (int) $nm[1];
                    if ((int) $numero >= 1 && (int) $numero <= 200 && !isset($results[$numero])) {
                        $results[$numero] = $priceVal;
                    }
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Find the most likely price in a single line.
     * Accepts French (45,00) and English (45.00) decimal formats.
     * Filters values outside 5–2000 €.
     */
    private function findPriceInLine(string $line): ?float
    {
        preg_match_all('/(?<!\d)(\d{1,4})[,\.](\d{2})(?!\d)/', $line, $matches, PREG_SET_ORDER);

        $candidates = [];
        foreach ($matches as $m) {
            $val = (float) ($m[1] . '.' . $m[2]);
            if ($val >= 5.0 && $val <= 2000.0) {
                $candidates[] = $val;
            }
        }

        return empty($candidates) ? null : end($candidates);
    }
}
