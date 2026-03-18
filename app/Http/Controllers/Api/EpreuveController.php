<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Epreuve;
use Illuminate\Http\Request;

class EpreuveController extends Controller
{
    public function updatePrix(Request $request, Epreuve $epreuve)
    {
        $validated = $request->validate([
            'prix' => 'nullable|numeric|min:0',
        ]);

        $epreuve->update(['prix' => $validated['prix']]);

        return response()->json(['success' => true, 'prix' => $epreuve->prix]);
    }
}
