<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande_retraits', function (Blueprint $table) {
            $table->dropUnique('commande_retraits_concours_numero_unique');
            $table->unique(['concours_id', 'numero_commande', 'produit'], 'commande_retraits_concours_numero_produit_unique');
        });
    }

    public function down(): void
    {
        Schema::table('commande_retraits', function (Blueprint $table) {
            $table->dropUnique('commande_retraits_concours_numero_produit_unique');
            $table->unique(['concours_id', 'numero_commande'], 'commande_retraits_concours_numero_unique');
        });
    }
};
