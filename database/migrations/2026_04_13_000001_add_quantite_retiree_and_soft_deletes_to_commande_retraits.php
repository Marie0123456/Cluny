<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commande_retraits', function (Blueprint $table) {
            $table->unsignedInteger('quantite_retiree')->default(0)->after('quantite');
            $table->softDeletes();
        });

        // Migrate existing data: if retire=true, set quantite_retiree = quantite
        \DB::statement('UPDATE commande_retraits SET quantite_retiree = quantite WHERE retire = true');
    }

    public function down(): void
    {
        Schema::table('commande_retraits', function (Blueprint $table) {
            $table->dropColumn('quantite_retiree');
            $table->dropSoftDeletes();
        });
    }
};
