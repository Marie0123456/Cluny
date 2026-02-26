<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained('concours')->cascadeOnDelete();
            $table->string('nom_client');
            $table->date('jour_paiement');
            $table->boolean('paiement_cb')->default(false);
            $table->boolean('paiement_especes')->default(false);
            $table->boolean('paiement_cheque')->default(false);
            $table->string('numero_cheque')->nullable();
            $table->boolean('facture')->default(false);
            $table->foreignId('client_facturation_id')->nullable()->constrained('clients_facturation')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->decimal('total_ttc', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
