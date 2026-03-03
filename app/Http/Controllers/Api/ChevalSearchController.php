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

        $chevaux = Cheval::query()
            ->when($concoursId, function ($q) use ($concoursId) {
                $q->whereHas('engagements.epreuve', function ($sub) use ($concoursId) {
                    $sub->where('concours_id', $concoursId);
                });
            })
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
        $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');

        if ($transliterator) {
            return $transliterator->transliterate($str);
        }

        // Fallback if intl extension not available
        return strtr($str, [
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
