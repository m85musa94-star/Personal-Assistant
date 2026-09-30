<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $me = $user->employee;
        $isHr = $user->isHrManager();
        $hasReports = $me && Employee::where('manager_id', $me->id)->exists();

        $tabs = [];
        if ($me) {
            $tabs['mine'] = __('طلباتي');
        }
        if ($isHr || $hasReports) {
            $tabs['approvals'] = __('بانتظار الاعتماد');
        }
        if ($isHr) {
            $tabs['all'] = __('كل الطلبات');
        }
        $tab = $request->query('tab');
        $tab = array_key_exists((string) $tab, $tabs) ? $tab : (array_key_first($tabs) ?? 'mine');
        $layout = $request->query('layout') === 'board' && $isHr ? 'board' : 'table';

        $q = LeaveRequest::with(['employee', 'type', 'decider'])->latest('start_date');
        if ($tab === 'mine') {
            $q->where('employee_id', $me?->id ?? 0);
        } elseif ($tab === 'approvals') {
            $q->where('status', 'pending');
            if (! $isHr) {
                $q->whereHas('employee', fn ($e) => $e->where('manager_id', $me?->id ?? 0));
            }
        }
        $requests = $q->limit(300)->get();

        $types = LeaveType::where('is_active', true)->orderBy('id')->get();
        $balances = $me ? $types->map(fn ($t) => ['type' => $t, 'left' => $me->leaveBalance($t)]) : collect();

        return view('hr.leave.index', compact('tabs', 'tab', 'layout', 'requests', 'balances', 'me', 'isHr'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        abort_unless($user->employee || $user->isHrManager(), 403, __('حسابك غير مرتبط بموظف. اطلب من مسؤول الموارد البشرية ربطه.'));

        return view('hr.leave.create', [
            'types' => LeaveType::where('is_active', true)->orderBy('id')->get(),
            'employees' => $user->isHrManager() ? Employee::where('status', 'active')->orderBy('name')->get() : collect(),
            'me' => $user->employee,
            'isHr' => $user->isHrManager(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isHr = $user->isHrManager();
        $data = $request->validate([
            'employee_id' => [$isHr ? 'required' : 'nullable', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'numeric', 'min:0.5', 'max:365'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], ['end_date.after_or_equal' => __('تاريخ النهاية قبل البداية.')]);

        $employee = $isHr ? Employee::findOrFail($data['employee_id']) : $user->employee;
        abort_if($employee === null, 403, __('حسابك غير مرتبط بموظف. اطلب من مسؤول الموارد البشرية ربطه.'));

        $type = LeaveType::where('is_active', true)->findOrFail($data['leave_type_id']);
        $maxDays = LeaveRequest::calendarDays($data['start_date'], $data['end_date']);
        $days = (float) ($data['days'] ?? $maxDays);
        if ($days > $maxDays) {
            throw ValidationException::withMessages(['days' => __('عدد الأيام أكبر من المدة المختارة.')]);
        }

        $overlap = LeaveRequest::where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $data['end_date'])->whereDate('end_date', '>=', $data['start_date'])->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => __('يتقاطع مع طلب إجازة آخر لنفس الموظف.')]);
        }

        // الرصيد يُفحص فقط إن حُدِّد استحقاق النوع؛ مسؤول الموارد البشرية يستطيع التجاوز.
        if ($type->annual_days !== null && ! $isHr) {
            $year = (int) substr($data['start_date'], 0, 4);
            $used = (float) LeaveRequest::where('employee_id', $employee->id)->where('leave_type_id', $type->id)
                ->whereIn('status', ['pending', 'approved'])->whereYear('start_date', $year)->sum('days');
            if ($used + $days > $type->annual_days) {
                throw ValidationException::withMessages(['days' => __('الرصيد غير كافٍ: المتبقي :n يوم.', ['n' => max(0, $type->annual_days - $used)])]);
            }
        }

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $type->id,
            'start_date' => $data['start_date'], 'end_date' => $data['end_date'],
            'days' => $days, 'reason' => $data['reason'] ?? null, 'status' => 'pending',
        ]);

        // اعتماد مباشر اختياري من مسؤول الموارد البشرية لموظف آخر (فصل المهام: لا يعتمد طلب نفسه).
        if ($isHr && $request->boolean('approve_now') && $this->mayDecide($user, $leave)) {
            $this->decide($leave, $user->id, 'approved', null);

            return redirect()->route('hr.leave.index', ['tab' => 'all'])->with('ok', __('تم تسجيل الإجازة واعتمادها.'));
        }

        return redirect()->route('hr.leave.index')->with('ok', __('تم إرسال الطلب.'));
    }

    public function approve(Request $request, LeaveRequest $leave): RedirectResponse
    {
        return $this->handleDecision($request, $leave, 'approved');
    }

    public function reject(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $request->validate(['decision_note' => ['required', 'string', 'max:1000']], ['decision_note.required' => __('اكتب سبب الرفض.')]);

        return $this->handleDecision($request, $leave, 'rejected');
    }

    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $user = $request->user();
        $own = $user->employee && $leave->employee_id === $user->employee->id;
        abort_unless($own || $user->isHrManager(), 403);
        $cancellable = $leave->status === 'pending' || ($leave->status === 'approved' && $leave->start_date->gte(today()));
        if (! $cancellable) {
            return back()->with('warn', __('لا يمكن إلغاء هذا الطلب.'));
        }
        $leave->update(['status' => 'cancelled', 'decided_by' => $user->id, 'decided_at' => now()]);

        return back()->with('ok', __('تم إلغاء الطلب.'));
    }

    private function handleDecision(Request $request, LeaveRequest $leave, string $status): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->mayDecide($user, $leave), 403, __('لا تملك صلاحية اعتماد هذا الطلب.'));
        if ($leave->status !== 'pending') {
            return back()->with('warn', __('تم البت في هذا الطلب مسبقًا.'));
        }
        $this->decide($leave, $user->id, $status, $request->input('decision_note'));

        return back()->with('ok', $status === 'approved' ? __('تم اعتماد الطلب.') : __('تم رفض الطلب.'));
    }

    /** مسؤول HR أو المدير المباشر؛ ولا يعتمد أحد طلب نفسه إلا مدير النظام (لا أحد فوقه). */
    private function mayDecide($user, LeaveRequest $leave): bool
    {
        $employee = $leave->employee;
        if ($user->employee && $leave->employee_id === $user->employee->id && ! $user->is_admin) {
            return false;
        }

        return $user->canDecideLeaveFor($employee);
    }

    private function decide(LeaveRequest $leave, int $userId, string $status, ?string $note): void
    {
        $leave->update(['status' => $status, 'decided_by' => $userId, 'decided_at' => now(), 'decision_note' => $note]);
    }
}
