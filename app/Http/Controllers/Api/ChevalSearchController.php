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

        $chevaux = Cheval::where('nom', 'like', "%{$query}%")
            ->when($concoursId, function ($q) use ($concoursId) {
                $q->whereHas('engagements.epreuve', function ($sub) use ($concoursId) {
                    $sub->where('concours_id', $concoursId);
                });
            })
            ->limit(20)
            ->get(['id', 'nom', 'num_sire', 'race', 'sexe']);

        return response()->json($chevaux);
    }
}
