<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concours', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('discipline'); // CSO, Dressage, Open
            $table->boolean('type_ffe_sif')->default(false);
            $table->boolean('type_ffe_compet')->default(false);
            $table->boolean('grand_national')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concours');
    }
};
