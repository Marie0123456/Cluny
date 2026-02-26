<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cavaliers', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('num_licence')->nullable();
            $table->string('club')->nullable();
            $table->string('cre')->nullable();
            $table->string('departement')->nullable();
            $table->string('num_departement')->nullable();
            $table->timestamps();

            $table->unique(['nom', 'prenom', 'num_licence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cavaliers');
    }
};
