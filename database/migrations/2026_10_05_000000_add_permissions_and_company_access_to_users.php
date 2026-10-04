<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->json('permissions')->nullable();           // null لمدير النظام (كل الصلاحيات)
            $t->boolean('all_companies')->default(true);   // false = مقيَّد بشركات محددة (جدول company_user)
        });

        Schema::create('company_user', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->primary(['user_id', 'company_id']);
        });

        // ترحيل الأدوار القديمة إلى صلاحيات تحفظ ما كان لكل مستخدم.
        $viewer = ['tasks.use', 'employees.view', 'leaves.view', 'vehicles.view', 'alerts.view'];
        $manager = ['tasks.use', 'employees.view', 'employees.edit', 'records.edit', 'leaves.view', 'leaves.edit', 'vehicles.view', 'vehicles.edit', 'alerts.view', 'companies.edit', 'leave_types.edit'];
        foreach (DB::table('users')->get(['id', 'is_admin', 'is_hr']) as $u) {
            $perms = $u->is_admin ? null : json_encode($u->is_hr ? $manager : $viewer);
            DB::table('users')->where('id', $u->id)->update(['permissions' => $perms]);
        }

        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('is_hr');
        });
    }

    public function down(): void
    {
        // تغيير أحادي الاتجاه.
    }
};
