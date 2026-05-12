<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->string('ffe_numero_concours')->nullable()->after('type_ffe_compet');
            $table->string('ffe_login')->nullable()->after('ffe_numero_concours');
            $table->text('ffe_password')->nullable()->after('ffe_login');
        });
    }

    public function down(): void
    {
        Schema::table('concours', function (Blueprint $table) {
            $table->dropColumn(['ffe_numero_concours', 'ffe_login', 'ffe_password']);
        });
    }
};
