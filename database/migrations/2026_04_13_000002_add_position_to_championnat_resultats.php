<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championnat_resultats', function (Blueprint $table) {
            // Manual position for disciplines with manual ranking (Equifeel/Equifun/Endurance).
            // 0 or null = non classe ; 1+ = rang.
            $table->unsignedInteger('position')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('championnat_resultats', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
