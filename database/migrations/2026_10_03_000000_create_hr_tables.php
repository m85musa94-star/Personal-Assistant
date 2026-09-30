<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('name_en')->nullable();
            $t->timestamps();
        });

        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $t->string('code', 40)->nullable()->unique();
            $t->string('name');
            $t->string('name_en')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('job_title')->nullable();
            $t->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->date('hire_date')->nullable();
            $t->string('status', 20)->default('active'); // active | inactive
            $t->string('id_number', 40)->nullable();
            $t->date('id_expiry')->nullable();
            $t->string('passport_number', 40)->nullable();
            $t->date('passport_expiry')->nullable();
            $t->string('contract_type', 30)->nullable();
            $t->date('contract_start')->nullable();
            $t->date('contract_end')->nullable();
            $t->decimal('salary', 12, 2)->nullable(); // حقل حساس: يراه مسؤول الموارد البشرية فقط
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('status');
        });

        Schema::create('leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('name_en')->nullable();
            $t->boolean('is_paid')->default(true);
            // الاستحقاق السنوي بالأيام. NULL = لم يُحدَّد بعد (لا نفترض أرقامًا نظامية)؛ يدخلها المسؤول.
            $t->decimal('annual_days', 5, 1)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $t->date('start_date');
            $t->date('end_date');
            $t->decimal('days', 5, 1);
            $t->text('reason')->nullable();
            $t->string('status', 20)->default('pending'); // pending | approved | rejected | cancelled
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('decided_at')->nullable();
            $t->text('decision_note')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'start_date']);
            $t->index('status');
        });

        Schema::create('attendance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->timestamp('check_in');
            $t->timestamp('check_out')->nullable();
            $t->string('source', 10)->default('web'); // web | manual
            $t->string('note')->nullable();
            $t->timestamps();
            $t->index(['employee_id', 'check_in']);
        });

        // أسماء أنواع الإجازات فقط — بلا أي أعداد أيام (تُدخل من شاشة «أنواع الإجازات»).
        $now = now();
        DB::table('leave_types')->insert([
            ['name' => 'إجازة سنوية', 'name_en' => 'Annual leave', 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'إجازة مرضية', 'name_en' => 'Sick leave', 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'إجازة بدون راتب', 'name_en' => 'Unpaid leave', 'is_paid' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'إجازة أخرى', 'name_en' => 'Other leave', 'is_paid' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
    }
};
