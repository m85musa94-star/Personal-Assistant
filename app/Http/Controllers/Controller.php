<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** يتحقق من صلاحية المستخدم لإجراء تعديل؛ وإلا 403. */
    protected function permit(Request $request, string $ability): void
    {
        abort_unless($request->user()->can($ability), 403, __('ليست لديك صلاحية لهذا الإجراء.'));
    }

    /** يمنع الوصول المباشر (برابط) إلى سجل في شركة غير مسموح بها للمستخدم. */
    protected function guardCompany(?int $companyId): void
    {
        abort_unless(request()->user()->canAccessCompany($companyId), 404);
    }
}
