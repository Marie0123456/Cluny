<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cavalier;
use Illuminate\Http\Request;

class CavalierSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $concoursId = $request->get('concours_id');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $search = '%' . $query . '%';

        $cavaliers = Cavalier::query()
            ->whereRaw('LOWER(nom) LIKE ?', [mb_strtolower($search)])
            ->orWhereRaw('LOWER(prenom) LIKE ?', [mb_strtolower($search)])
            ->orWhereRaw("LOWER(nom || ' ' || prenom) LIKE ?", [mb_strtolower($search)])
            ->when($concoursId, function ($q) use ($concoursId) {
                $q->orderByRaw('CASE WHEN id IN (SELECT cavalier_id FROM engagements WHERE epreuve_id IN (SELECT id FROM epreuves WHERE concours_id = ?)) THEN 0 ELSE 1 END', [$concoursId]);
            })
            ->orderBy('nom')
            ->limit(15)
            ->get(['id', 'nom', 'prenom', 'num_licence', 'club']);

        return response()->json($cavaliers);
    }
}
