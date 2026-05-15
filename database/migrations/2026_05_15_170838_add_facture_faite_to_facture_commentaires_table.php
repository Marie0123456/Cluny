<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facture_commentaires', function (Blueprint $table) {
            $table->boolean('facture_faite')->default(false)->after('commentaire');
        });
    }

    public function down(): void
    {
        Schema::table('facture_commentaires', function (Blueprint $table) {
            $table->dropColumn('facture_faite');
        });
    }
};
