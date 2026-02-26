<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained('concours')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nom_fichier');
            $table->integer('nb_epreuves')->default(0);
            $table->integer('nb_engagements')->default(0);
            $table->integer('nb_cavaliers')->default(0);
            $table->integer('nb_chevaux')->default(0);
            $table->string('statut')->default('succes'); // succes, erreur
            $table->text('message_erreur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
