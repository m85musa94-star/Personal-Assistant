<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;

class OrgController extends Controller
{
    public function index()
    {
        $all = Employee::with('department')->where('status', 'active')->orderBy('name')->get();
        $byManager = $all->groupBy('manager_id');
        $ids = $all->pluck('id')->flip();
        // الجذور: بلا مدير، أو مديرهم غير نشط/غير موجود
        $roots = $all->filter(fn (Employee $e) => ! $e->manager_id || ! isset($ids[$e->manager_id]))->values();

        return view('hr.org', compact('roots', 'byManager'));
    }
}
