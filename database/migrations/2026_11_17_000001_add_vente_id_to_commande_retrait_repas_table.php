<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande_retrait_repas', function (Blueprint $table) {
            $table->foreignId('vente_id')->nullable()->after('concours_id')->nullOnDelete()->constrained('ventes');
        });
    }

    public function down(): void
    {
        Schema::table('commande_retrait_repas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vente_id');
        });
    }
};
