<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سجل الموظف: أحداث تلقائية (event + data) وإدخالات يدوية (type + title + body).
        Schema::create('employee_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type', 20);              // system | note | warning | commendation | evaluation | training | incident | other
            $t->string('event', 40)->nullable(); // مفتاح الحدث التلقائي
            $t->json('data')->nullable();        // معاملات رسالة الحدث
            $t->string('title')->nullable();
            $t->text('body')->nullable();
            $t->date('event_date');
            $t->timestamps();
            $t->index(['employee_id', 'event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_records');
    }
};
