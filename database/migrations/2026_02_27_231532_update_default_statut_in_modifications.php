<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->string('statut')->default('cree')->change();
        });
    }

    public function down(): void
    {
        Schema::table('modifications', function (Blueprint $table) {
            $table->string('statut')->default('en_attente')->change();
        });
    }
};
