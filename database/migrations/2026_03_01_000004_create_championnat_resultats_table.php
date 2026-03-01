<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championnat_resultats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championnat_id')->constrained('championnats')->cascadeOnDelete();
            $table->foreignId('epreuve_id')->constrained('epreuves')->cascadeOnDelete();
            $table->unsignedBigInteger('cavalier_id');
            $table->unsignedBigInteger('cheval_id');
            $table->decimal('points', 8, 2)->default(0);
            $table->decimal('temps', 8, 2)->nullable();
            $table->string('statut', 20)->default('normal'); // normal, elimine, non_partant, abandon
            $table->timestamps();

            $table->foreign('cavalier_id')->references('id')->on('cavaliers')->cascadeOnDelete();
            $table->foreign('cheval_id')->references('id')->on('chevaux')->cascadeOnDelete();
            $table->unique(['championnat_id', 'epreuve_id', 'cavalier_id', 'cheval_id'], 'resultat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championnat_resultats');
    }
};
