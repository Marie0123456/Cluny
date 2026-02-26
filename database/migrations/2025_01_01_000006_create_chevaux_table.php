<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chevaux', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('num_sire')->nullable();
            $table->integer('age')->nullable();
            $table->string('sexe')->nullable();
            $table->string('robe')->nullable();
            $table->string('race')->nullable();
            $table->timestamps();

            $table->unique(['nom', 'num_sire']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chevaux');
    }
};
