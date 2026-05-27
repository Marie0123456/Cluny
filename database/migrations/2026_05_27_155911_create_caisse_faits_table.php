<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_faits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concours_id')->constrained()->cascadeOnDelete();
            $table->string('section'); // 'vente' or 'modification'
            $table->text('groupe_key');
            $table->boolean('fait')->default(false);
            $table->foreignId('fait_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fait_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_faits');
    }
};
