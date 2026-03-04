<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_retraits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained('concours')->cascadeOnDelete();
            $table->string('numero_commande')->unique();
            $table->date('date_commande');
            $table->string('prenom');
            $table->string('nom');
            $table->string('produit');
            $table->unsignedInteger('quantite')->default(1);
            $table->string('emplacement_boxes')->nullable();
            $table->boolean('retire')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_retraits');
    }
};
