<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientFacturation;
use Illuminate\Http\Request;

class ClientFacturationController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = ClientFacturation::whereRaw('LOWER(nom) LIKE ?', ['%' . mb_strtolower($query) . '%'])
            ->limit(10)
            ->get(['id', 'nom', 'telephone', 'email', 'adresse']);

        return response()->json($clients);
    }
}
