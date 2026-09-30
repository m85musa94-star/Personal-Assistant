<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->default('ar');
            $table->string('theme', 10)->default('auto');
            $table->boolean('is_hr')->default(false); // مسؤول الموارد البشرية (صلاحيات HR دون صلاحيات المدير الكاملة)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'theme', 'is_hr']);
        });
    }
};
