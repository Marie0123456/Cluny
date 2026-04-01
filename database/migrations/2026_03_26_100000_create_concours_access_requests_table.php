<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concours_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('concours_id')->constrained('concours')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'concours_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concours_access_requests');
    }
};
