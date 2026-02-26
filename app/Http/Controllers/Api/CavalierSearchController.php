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

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $cavaliers = Cavalier::where('nom', 'like', "%{$query}%")
            ->orWhere('prenom', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'nom', 'prenom', 'num_licence', 'club']);

        return response()->json($cavaliers);
    }
}
