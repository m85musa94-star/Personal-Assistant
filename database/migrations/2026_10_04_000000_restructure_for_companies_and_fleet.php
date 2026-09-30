<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('name_en')->nullable();
            $t->string('cr_number', 40)->nullable();   // السجل التجاري
            $t->string('tax_number', 40)->nullable();  // الرقم الضريبي
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->boolean('is_active')->default(true);
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);            // iqama | passport | insurance | contract | work_permit | driving_license | other
            $t->string('title')->nullable();   // لنوع "other"
            $t->string('number', 60)->nullable();
            $t->string('provider')->nullable(); // شركة التأمين / جهة الإصدار
            $t->date('issue_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('expiry_date');
        });

        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('plate', 40);
            $t->string('make', 60)->nullable();
            $t->string('model', 60)->nullable();
            $t->unsignedSmallInteger('year')->nullable();
            $t->string('color', 40)->nullable();
            $t->string('vin', 40)->nullable();
            $t->string('type', 20)->nullable();   // sedan | suv | pickup | van | truck | bus | other
            $t->string('fuel', 20)->nullable();   // petrol | diesel | hybrid | electric
            $t->string('status', 20)->default('active'); // active | maintenance | out_of_service | sold
            $t->foreignId('driver_id')->nullable()->constrained('employees')->nullOnDelete();
            $t->unsignedInteger('odometer')->nullable();
            $t->date('purchase_date')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('status');
        });

        Schema::create('vehicle_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);            // registration | insurance | inspection | operating_card | authorization | other
            $t->string('title')->nullable();
            $t->string('number', 60)->nullable();
            $t->string('provider')->nullable();
            $t->date('issue_date')->nullable();
            $t->date('expiry_date')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('expiry_date');
        });

        Schema::create('vehicle_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->string('type', 20);            // service | repair | fuel | fine | accident | other
            $t->date('record_date');
            $t->unsignedInteger('odometer')->nullable();
            $t->decimal('amount', 12, 2)->nullable();
            $t->string('vendor')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
            $t->index(['vehicle_id', 'record_date']);
        });

        Schema::table('employees', function (Blueprint $t) {
            $t->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('nationality', 60)->nullable();
        });

        // نقل بيانات النسخة السابقة (إن وُجدت): شركة افتراضية قابلة للتسمية، ووثائق الموظفين من الأعمدة القديمة.
        if (DB::table('employees')->exists()) {
            $now = now();
            $companyId = DB::table('companies')->insertGetId(['name' => 'الشركة الرئيسية', 'name_en' => 'Main company', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('employees')->update(['company_id' => $companyId]);
            foreach (DB::table('employees')->get() as $e) {
                $docs = [
                    ['iqama', $e->id_number, $e->id_expiry, null],
                    ['passport', $e->passport_number, $e->passport_expiry, null],
                    ['contract', null, $e->contract_end, $e->contract_start],
                ];
                foreach ($docs as [$type, $number, $expiry, $issue]) {
                    if ($number || $expiry || $issue) {
                        DB::table('employee_documents')->insert(['employee_id' => $e->id, 'type' => $type, 'number' => $number, 'expiry_date' => $expiry, 'issue_date' => $issue, 'created_at' => $now, 'updated_at' => $now]);
                    }
                }
            }
        }

        // لا اعتماد بعد الآن: الإجازات سجلات يدخلها المسؤول.
        DB::table('leave_requests')->where('status', 'pending')->update(['status' => 'approved']);
        DB::table('leave_requests')->where('status', 'rejected')->update(['status' => 'cancelled']);

        Schema::dropIfExists('attendance_records');

        Schema::table('leave_requests', function (Blueprint $t) {
            $t->dropConstrainedForeignId('decided_by');
            $t->dropColumn(['decided_at', 'decision_note']);
        });

        Schema::table('employees', function (Blueprint $t) {
            $t->dropUnique(['user_id']);
        });
        Schema::table('employees', function (Blueprint $t) {
            $t->dropConstrainedForeignId('user_id');
            $t->dropConstrainedForeignId('department_id');
            $t->dropConstrainedForeignId('manager_id');
        });
        Schema::table('employees', function (Blueprint $t) {
            $t->dropIndex(['status']);
        });
        Schema::table('employees', function (Blueprint $t) {
            $t->dropColumn(['id_number', 'id_expiry', 'passport_number', 'passport_expiry', 'contract_type', 'contract_start', 'contract_end', 'salary']);
            $t->index('status');
        });

        Schema::dropIfExists('departments');
    }

    public function down(): void
    {
        // تغيير هيكلي أحادي الاتجاه.
    }
};
