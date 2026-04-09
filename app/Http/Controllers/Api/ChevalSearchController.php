<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cheval;
use Illuminate\Http\Request;

class ChevalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $concoursId = $request->get('concours_id');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $normalizedQuery = $this->removeAccents(mb_strtolower($query));

        $baseQuery = Cheval::query()
            ->when($concoursId, function ($q) use ($concoursId) {
                $q->whereHas('engagements.epreuve', function ($sub) use ($concoursId) {
                    $sub->where('concours_id', $concoursId);
                });
            });

        // Try SQL LIKE first (fast, case-insensitive via LOWER)
        $chevaux = (clone $baseQuery)
            ->whereRaw('LOWER(nom) LIKE ?', ['%' . mb_strtolower($query) . '%'])
            ->limit(20)
            ->get(['id', 'nom', 'num_sire', 'race', 'sexe']);

        if ($chevaux->isNotEmpty()) {
            return response()->json($chevaux);
        }

        // Fallback: accent-insensitive search in PHP
        $chevaux = $baseQuery
            ->get(['id', 'nom', 'num_sire', 'race', 'sexe'])
            ->filter(function ($cheval) use ($normalizedQuery) {
                return str_contains(
                    $this->removeAccents(mb_strtolower($cheval->nom)),
                    $normalizedQuery
                );
            })
            ->values()
            ->take(20);

        return response()->json($chevaux);
    }

    private function removeAccents(string $str): string
    {
        return strtr($str, [
            'À' => 'A', 'Â' => 'A', 'Ä' => 'A', 'Á' => 'A',
            'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'É' => 'E',
            'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Í' => 'I',
            'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O', 'Ó' => 'O',
            'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ú' => 'U',
            'Ÿ' => 'Y', 'Ý' => 'Y',
            'Ç' => 'C', 'Ñ' => 'N',
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
            'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'í' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ÿ' => 'y', 'ý' => 'y',
            'ç' => 'c', 'ñ' => 'n',
        ]);
    }
}
