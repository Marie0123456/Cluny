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

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $chevaux = Cheval::where('nom', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'nom', 'num_sire', 'race', 'sexe']);

        return response()->json($chevaux);
    }
}
