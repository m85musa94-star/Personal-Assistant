<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status', 'active');
        $view = $request->query('view', 'cards') === 'list' ? 'list' : 'cards';

        $employees = Employee::with(['department', 'manager'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('name_en', 'like', $like)
                    ->orWhere('code', 'like', $like)->orWhere('email', 'like', $like)->orWhere('job_title', 'like', $like));
            })
            ->when($request->query('department'), fn ($query, $d) => $query->where('department_id', $d))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')->paginate(48)->withQueryString();

        return view('hr.employees.index', [
            'employees' => $employees, 'departments' => Department::orderBy('name')->get(),
            'q' => $q, 'status' => $status, 'view' => $view, 'dept' => $request->query('department'),
        ]);
    }

    public function show(Request $request, Employee $employee)
    {
        $user = $request->user();
        $isSelf = $employee->user_id === $user->id;
        $sensitive = $user->isHrManager() || $isSelf;

        $employee->load(['department', 'manager', 'reports', 'user']);
        $types = LeaveType::where('is_active', true)->orderBy('id')->get();
        $balances = $types->map(fn ($t) => ['type' => $t, 'left' => $employee->leaveBalance($t)]);
        $leaves = $employee->leaveRequests()->with('type')->latest('start_date')->limit(20)->get();
        $attendance = $employee->attendance()->latest('check_in')->limit(20)->get();

        return view('hr.employees.show', compact('employee', 'sensitive', 'balances', 'leaves', 'attendance', 'isSelf'));
    }

    public function create(Request $request)
    {
        $this->authorizeHr($request);

        return view('hr.employees.form', $this->formData(new Employee(['status' => 'active'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeHr($request);
        $employee = new Employee;
        $employee->fill($this->validated($request, $employee));
        $employee->save();

        return redirect()->route('hr.employees.show', $employee)->with('ok', __('تمت إضافة الموظف.'));
    }

    public function edit(Request $request, Employee $employee)
    {
        $this->authorizeHr($request);

        return view('hr.employees.form', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->authorizeHr($request);
        $employee->fill($this->validated($request, $employee))->save();

        return redirect()->route('hr.employees.show', $employee)->with('ok', __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403, __('هذه الصفحة للمدير فقط'));
        $employee->delete();

        return redirect()->route('hr.employees.index')->with('ok', __('تم حذف الموظف وكل سجلاته.'));
    }

    private function authorizeHr(Request $request): void
    {
        abort_unless($request->user()->isHrManager(), 403, __('هذه الصفحة لمسؤول الموارد البشرية فقط'));
    }

    private function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'departments' => Department::orderBy('name')->get(),
            'managers' => Employee::where('status', 'active')->when($employee->exists, fn ($q) => $q->where('id', '!=', $employee->id))->orderBy('name')->get(),
            'users' => User::where(fn ($q) => $q->whereDoesntHave('employee')->orWhere('id', $employee->user_id ?? 0))->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, Employee $employee): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'name_en' => ['nullable', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('employees', 'code')->ignore($employee->id)],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'user_id' => ['nullable', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee->id)],
            'hire_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'id_number' => ['nullable', 'string', 'max:40'],
            'id_expiry' => ['nullable', 'date'],
            'passport_number' => ['nullable', 'string', 'max:40'],
            'passport_expiry' => ['nullable', 'date'],
            'contract_type' => ['nullable', 'in:'.implode(',', Employee::CONTRACT_TYPES)],
            'contract_start' => ['nullable', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ], [
            'code.unique' => __('رقم الموظف مستخدم مسبقًا.'),
            'user_id.unique' => __('هذا الحساب مرتبط بموظف آخر.'),
            'contract_end.after_or_equal' => __('نهاية العقد قبل بدايته.'),
        ]);

        $manager = $data['manager_id'] ?? null;
        if ($manager && $employee->exists && $this->wouldCycle($employee->id, (int) $manager)) {
            throw ValidationException::withMessages(['manager_id' => __('لا يمكن أن يكون المدير المباشر من مرؤوسيه (حلقة في الهيكل).')]);
        }

        // تحويل النصوص الفارغة إلى null
        return array_map(fn ($v) => $v === '' ? null : $v, $data);
    }

    /** هل يؤدي تعيين $managerId مديرًا لـ $employeeId إلى حلقة؟ */
    private function wouldCycle(int $employeeId, int $managerId): bool
    {
        $seen = [];
        while ($managerId && ! isset($seen[$managerId])) {
            if ($managerId === $employeeId) {
                return true;
            }
            $seen[$managerId] = true;
            $managerId = (int) Employee::whereKey($managerId)->value('manager_id');
        }

        return false;
    }
}
