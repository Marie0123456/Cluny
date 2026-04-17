<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championnat_resultats', function (Blueprint $table) {
            $table->decimal('points', 8, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('championnat_resultats', function (Blueprint $table) {
            $table->decimal('points', 8, 2)->default(0)->change();
        });
    }
};
