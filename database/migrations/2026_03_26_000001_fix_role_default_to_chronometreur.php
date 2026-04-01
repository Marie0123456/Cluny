<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix the column default from 'user' (invalid enum value) to 'chronometreur'
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('chronometreur')->change();
        });

        // Also fix any existing users that still have the invalid 'user' role
        DB::table('users')->where('role', 'user')->update(['role' => 'chronometreur']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }
};
