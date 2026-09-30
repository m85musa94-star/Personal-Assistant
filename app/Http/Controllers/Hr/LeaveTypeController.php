<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeHr($request);

        return view('hr.leave-types', ['types' => LeaveType::withCount('requests')->orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHr($request);
        LeaveType::create($this->validated($request));

        return back()->with('ok', __('تمت إضافة نوع الإجازة.'));
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorizeHr($request);
        $leaveType->update($this->validated($request));

        return back()->with('ok', __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $this->authorizeHr($request);
        if ($leaveType->requests()->exists()) {
            return back()->with('warn', __('عليه طلبات إجازة؛ عطّله بدل حذفه.'));
        }
        $leaveType->delete();

        return back()->with('ok', __('تم حذف النوع.'));
    }

    private function validated(Request $request): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'annual_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
        ]);
        $d['is_paid'] = $request->boolean('is_paid');
        $d['is_active'] = $request->boolean('is_active');
        $d['annual_days'] = ($d['annual_days'] ?? '') === '' ? null : $d['annual_days'];

        return $d;
    }

    private function authorizeHr(Request $request): void
    {
        abort_unless($request->user()->isHrManager(), 403, __('هذه الصفحة لمسؤول الموارد البشرية فقط'));
    }
}
