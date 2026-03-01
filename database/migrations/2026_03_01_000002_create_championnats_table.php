<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championnats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained('concours')->cascadeOnDelete();
            $table->string('nom');
            $table->foreignId('epreuve1_id')->constrained('epreuves')->cascadeOnDelete();
            $table->foreignId('epreuve2_id')->constrained('epreuves')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championnats');
    }
};
