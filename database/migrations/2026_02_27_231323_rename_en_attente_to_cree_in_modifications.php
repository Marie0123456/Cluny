<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('modifications')
            ->where('statut', 'en_attente')
            ->update(['statut' => 'cree']);
    }

    public function down(): void
    {
        DB::table('modifications')
            ->where('statut', 'cree')
            ->update(['statut' => 'en_attente']);
    }
};
