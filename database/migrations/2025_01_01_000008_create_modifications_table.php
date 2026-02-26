<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('concours_id')->constrained('concours')->cascadeOnDelete();
            $table->string('type'); // changement_cheval, changement_cavalier, ajout_engagement, changement_epreuve
            $table->string('description')->nullable();
            $table->foreignId('ancien_cheval_id')->nullable()->constrained('chevaux')->nullOnDelete();
            $table->foreignId('nouveau_cheval_id')->nullable()->constrained('chevaux')->nullOnDelete();
            $table->string('statut')->default('en_attente'); // en_attente, fait, supprime
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifications');
    }
};
