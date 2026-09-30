<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeHr($request);

        return view('hr.departments', ['departments' => Department::withCount('employees')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHr($request);
        Department::create($request->validate(['name' => ['required', 'string', 'max:160'], 'name_en' => ['nullable', 'string', 'max:160']]));

        return back()->with('ok', __('تمت إضافة القسم.'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeHr($request);
        $department->update($request->validate(['name' => ['required', 'string', 'max:160'], 'name_en' => ['nullable', 'string', 'max:160']]));

        return back()->with('ok', __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeHr($request);
        if ($department->employees()->exists()) {
            return back()->with('warn', __('لا يمكن حذف قسم فيه موظفون. انقلهم أولًا.'));
        }
        $department->delete();

        return back()->with('ok', __('تم حذف القسم.'));
    }

    private function authorizeHr(Request $request): void
    {
        abort_unless($request->user()->isHrManager(), 403, __('هذه الصفحة لمسؤول الموارد البشرية فقط'));
    }
}
