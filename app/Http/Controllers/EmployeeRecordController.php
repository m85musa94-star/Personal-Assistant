<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeRecordController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $this->permit($request, 'records.edit');
        $this->guardCompany($employee->company_id);
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', EmployeeRecord::TYPES)],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:4000'],
            'event_date' => ['required', 'date'],
        ], ['title.required' => __('اكتب عنوان الإدخال.')]);
        $employee->records()->create($data + ['user_id' => $request->user()->id]);

        return $this->back($employee, __('تمت إضافة الإدخال إلى السجل.'));
    }

    public function destroy(Request $request, EmployeeRecord $record): RedirectResponse
    {
        $this->permit($request, 'records.edit');
        $employee = $record->employee;
        $this->guardCompany($employee->company_id);
        // الأحداث التلقائية لا تُحذف (أثر تدقيق). الإدخال اليدوي يحذفه كاتبه أو مدير النظام.
        abort_if($record->isSystem(), 403, __('الأحداث التلقائية لا تُحذف.'));
        abort_unless($request->user()->is_admin || $record->user_id === $request->user()->id, 403, __('ليست لديك صلاحية لهذا الإجراء.'));
        $record->delete();

        return $this->back($employee, __('تم حذف الإدخال.'));
    }

    private function back(Employee $employee, string $msg): RedirectResponse
    {
        return redirect()->route('employees.show', ['employee' => $employee, 'tab' => 'record'])->with('ok', $msg);
    }
}
