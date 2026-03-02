<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epreuves', function (Blueprint $table) {
            $table->index(['concours_id', 'numero']);
        });

        Schema::table('cavaliers', function (Blueprint $table) {
            $table->index('num_licence');
        });

        Schema::table('engagements', function (Blueprint $table) {
            $table->index(['epreuve_id', 'cavalier_id', 'cheval_id']);
        });
    }

    public function down(): void
    {
        Schema::table('epreuves', function (Blueprint $table) {
            $table->dropIndex(['concours_id', 'numero']);
        });

        Schema::table('cavaliers', function (Blueprint $table) {
            $table->dropIndex(['num_licence']);
        });

        Schema::table('engagements', function (Blueprint $table) {
            $table->dropIndex(['epreuve_id', 'cavalier_id', 'cheval_id']);
        });
    }
};
