<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $me = $user->employee;
        $isHr = $user->isHrManager();

        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : today()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();

        $visible = $this->visibleEmployees($user);
        $employeeId = (int) $request->query('employee');
        $q = AttendanceRecord::with('employee')->whereBetween('check_in', [$start, $end])->orderByDesc('check_in');
        if ($employeeId && $visible->contains('id', $employeeId)) {
            $q->where('employee_id', $employeeId);
        } else {
            $employeeId = 0;
            $q->whereIn('employee_id', $visible->pluck('id'));
        }
        $records = $q->limit(500)->get();

        $totals = $records->groupBy('employee_id')->map(fn ($rows) => [
            'employee' => $rows->first()->employee, 'hours' => round($rows->sum(fn ($r) => $r->hours()), 1), 'days' => $rows->groupBy(fn ($r) => $r->check_in->toDateString())->count(),
        ])->sortByDesc('hours')->values();

        $open = $me ? AttendanceRecord::where('employee_id', $me->id)->whereNull('check_out')->latest('check_in')->first() : null;

        return view('hr.attendance', compact('records', 'totals', 'open', 'me', 'isHr', 'month', 'visible', 'employeeId') + [
            'prev' => $start->copy()->subMonth()->format('Y-m'), 'next' => $start->copy()->addMonth()->format('Y-m'),
            'monthLabel' => $start->copy()->locale(app()->getLocale())->translatedFormat('F Y'),
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $me = $request->user()->employee;
        abort_if($me === null, 403, __('حسابك غير مرتبط بموظف. اطلب من مسؤول الموارد البشرية ربطه.'));
        if (AttendanceRecord::where('employee_id', $me->id)->whereNull('check_out')->exists()) {
            return back()->with('warn', __('لديك تسجيل حضور مفتوح. سجّل الانصراف أولًا.'));
        }
        AttendanceRecord::create(['employee_id' => $me->id, 'check_in' => now(), 'source' => 'web']);

        return back()->with('ok', __('تم تسجيل الحضور.'));
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $me = $request->user()->employee;
        abort_if($me === null, 403, __('حسابك غير مرتبط بموظف. اطلب من مسؤول الموارد البشرية ربطه.'));
        $open = AttendanceRecord::where('employee_id', $me->id)->whereNull('check_out')->latest('check_in')->first();
        if (! $open) {
            return back()->with('warn', __('لا يوجد تسجيل حضور مفتوح.'));
        }
        $open->update(['check_out' => now()]);

        return back()->with('ok', __('تم تسجيل الانصراف.'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isHrManager(), 403, __('هذه الصفحة لمسؤول الموارد البشرية فقط'));
        $d = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'check_in' => ['required', 'date'],
            'check_out' => ['nullable', 'date', 'after_or_equal:check_in'],
            'note' => ['nullable', 'string', 'max:200'],
        ], ['check_out.after_or_equal' => __('الانصراف قبل الحضور.')]);
        AttendanceRecord::create($d + ['source' => 'manual']);

        return back()->with('ok', __('تمت إضافة السجل.'));
    }

    public function destroy(Request $request, AttendanceRecord $record): RedirectResponse
    {
        abort_unless($request->user()->isHrManager(), 403, __('هذه الصفحة لمسؤول الموارد البشرية فقط'));
        $record->delete();

        return back()->with('ok', __('تم حذف السجل.'));
    }

    /** مسؤول HR: الجميع. غيره: نفسه ومرؤوسوه المباشرون. */
    private function visibleEmployees($user)
    {
        if ($user->isHrManager()) {
            return Employee::orderBy('name')->get(['id', 'name', 'name_en']);
        }
        $me = $user->employee;

        return $me ? Employee::where('id', $me->id)->orWhere('manager_id', $me->id)->orderBy('name')->get(['id', 'name', 'name_en']) : collect();
    }
}
