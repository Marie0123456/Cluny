<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epreuve_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cavalier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cheval_id')->constrained('chevaux')->cascadeOnDelete();
            $table->string('numero_depart')->nullable();
            $table->string('role_cavalier')->nullable();
            $table->string('dept_groom')->nullable();
            $table->string('role_cheval')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};
