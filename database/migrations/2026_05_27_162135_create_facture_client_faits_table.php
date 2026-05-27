<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facture_client_faits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_facturation_id')->constrained()->cascadeOnDelete();
            $table->string('section'); // 'vente' or 'modification'
            $table->unsignedBigInteger('item_id'); // VenteLigne.id or Modification.id
            $table->boolean('fait')->default(false);
            $table->foreignId('fait_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fait_le')->nullable();
            $table->timestamps();
            $table->unique(['concours_id', 'client_facturation_id', 'section', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_client_faits');
    }
};
