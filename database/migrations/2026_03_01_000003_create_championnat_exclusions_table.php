<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championnat_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('championnat_id')->constrained('championnats')->cascadeOnDelete();
            $table->unsignedBigInteger('cavalier_id');
            $table->unsignedBigInteger('cheval_id');
            $table->timestamps();

            $table->foreign('cavalier_id')->references('id')->on('cavaliers')->cascadeOnDelete();
            $table->foreign('cheval_id')->references('id')->on('chevaux')->cascadeOnDelete();
            $table->unique(['championnat_id', 'cavalier_id', 'cheval_id'], 'excl_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championnat_exclusions');
    }
};
