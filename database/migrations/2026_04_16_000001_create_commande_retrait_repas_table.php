<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_retrait_repas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained()->cascadeOnDelete();
            $table->string('numero_commande');
            $table->date('date_commande');
            $table->string('prenom')->nullable();
            $table->string('nom')->nullable();
            $table->string('produit');
            $table->unsignedInteger('quantite')->default(1);
            $table->unsignedInteger('quantite_retiree')->default(0);
            $table->string('emplacement_boxes')->nullable();
            $table->text('note_client')->nullable();
            $table->boolean('retire')->default(false);
            $table->foreignId('retired_by')->nullable()->nullOnDelete()->constrained('users');
            $table->timestamp('retired_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['concours_id', 'numero_commande', 'produit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commande_retrait_repas');
    }
};
