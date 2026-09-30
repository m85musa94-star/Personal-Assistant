<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /** التعديل على بيانات الموظفين والمركبات لمدير النظام أو مسؤول الموظفين والمركبات فقط. */
    protected function manage(Request $request): void
    {
        abort_unless($request->user()->canManageData(), 403, __('هذه الصفحة لمسؤول الموظفين والمركبات فقط'));
    }
}
