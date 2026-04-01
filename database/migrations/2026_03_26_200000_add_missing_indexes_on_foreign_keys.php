<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championnat_exclusions', function (Blueprint $table) {
            $table->index('cavalier_id');
            $table->index('cheval_id');
        });

        Schema::table('championnat_resultats', function (Blueprint $table) {
            $table->index('cavalier_id');
            $table->index('cheval_id');
        });
    }

    public function down(): void
    {
        Schema::table('championnat_exclusions', function (Blueprint $table) {
            $table->dropIndex(['cavalier_id']);
            $table->dropIndex(['cheval_id']);
        });

        Schema::table('championnat_resultats', function (Blueprint $table) {
            $table->dropIndex(['cavalier_id']);
            $table->dropIndex(['cheval_id']);
        });
    }
};
