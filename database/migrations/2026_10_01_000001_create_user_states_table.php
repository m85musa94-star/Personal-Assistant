<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مستند واحد لكل مستخدم يحوي مهامه ومواعيده وسجلات وقته. ts = وقت آخر تعديل بالميلي ثانية (من العميل).
        Schema::create('user_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('data');
            $table->unsignedBigInteger('ts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_states');
    }
};
