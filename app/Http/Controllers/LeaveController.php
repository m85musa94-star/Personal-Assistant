<?php

namespace App\Http\Controllers;

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
        $status = in_array($request->query('status'), ['approved', 'cancelled', 'all'], true) ? $request->query('status') : 'approved';
        $typeId = $request->query('type');
        $employeeId = $request->query('employee');
        $year = preg_match('/^\d{4}$/', (string) $request->query('year')) ? (int) $request->query('year') : null;

        $leaves = LeaveRequest::with(['employee.company', 'type'])
            ->whereHas('employee', fn ($e) => $e->inCompany())
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($typeId, fn ($q) => $q->where('leave_type_id', $typeId))
            ->when($employeeId, fn ($q) => $q->where('employee_id', $employeeId))
            ->when($year, fn ($q) => $q->whereYear('start_date', $year))
            ->orderByDesc('start_date')->paginate(50)->withQueryString();

        $onLeave = LeaveRequest::with('employee')->where('status', 'approved')->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())->whereHas('employee', fn ($e) => $e->inCompany())->get();

        return view('leaves.index', [
            'leaves' => $leaves, 'onLeave' => $onLeave, 'status' => $status, 'typeId' => $typeId, 'employeeId' => $employeeId, 'year' => $year,
            'types' => LeaveType::orderBy('id')->get(), 'employees' => Employee::inCompany()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->manage($request);

        return view('leaves.form', [
            'types' => LeaveType::where('is_active', true)->orderBy('id')->get(),
            'employees' => Employee::with('company')->inCompany()->where('status', 'active')->orderBy('name')->get(),
            'employeeId' => $request->query('employee'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->manage($request);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'numeric', 'min:0.5', 'max:365'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ], ['end_date.after_or_equal' => __('تاريخ النهاية قبل البداية.')]);

        $employee = Employee::findOrFail($data['employee_id']);
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $max = LeaveRequest::calendarDays($data['start_date'], $data['end_date']);
        $days = (float) ($data['days'] ?? $max);
        if ($days > $max) {
            throw ValidationException::withMessages(['days' => __('عدد الأيام أكبر من المدة المختارة.')]);
        }

        $overlap = $employee->leaves()->where('status', 'approved')
            ->whereDate('start_date', '<=', $data['end_date'])->whereDate('end_date', '>=', $data['start_date'])->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => __('يتقاطع مع إجازة أخرى مسجّلة لنفس الموظف.')]);
        }

        // الرصيد للتنبيه فقط: السجل يعكس الواقع ولا يُمنع.
        $warn = null;
        if ($type->annual_days !== null) {
            $left = $employee->leaveBalance($type, (int) substr($data['start_date'], 0, 4));
            if ($left !== null && $days > $left) {
                $warn = __('تنبيه: الإجازة تتجاوز الرصيد المتبقي (:n يوم).', ['n' => max(0, $left)]);
            }
        }

        $employee->leaves()->create(['leave_type_id' => $type->id, 'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'days' => $days, 'reason' => $data['reason'] ?? null, 'status' => 'approved']);

        $target = $request->input('return') === 'employee' ? route('employees.show', ['employee' => $employee, 'tab' => 'leaves']) : route('leaves.index');

        return redirect($target)->with('ok', __('تم تسجيل الإجازة.'))->with('warn', $warn);
    }

    /** إلغاء سجل (يبقى محفوظًا بحالة ملغاة) أو استعادته. */
    public function toggle(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->manage($request);
        if ($leave->status === 'cancelled') {
            $overlap = $leave->employee->leaves()->where('status', 'approved')->where('id', '!=', $leave->id)
                ->whereDate('start_date', '<=', $leave->end_date)->whereDate('end_date', '>=', $leave->start_date)->exists();
            if ($overlap) {
                return back()->with('warn', __('لا يمكن استعادة الإجازة لأنها تتقاطع مع أخرى.'));
            }
        }
        $leave->update(['status' => $leave->status === 'cancelled' ? 'approved' : 'cancelled']);

        return back()->with('ok', $leave->status === 'cancelled' ? __('تم إلغاء الإجازة.') : __('تمت استعادة الإجازة.'));
    }
}
