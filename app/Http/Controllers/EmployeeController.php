<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = $request->query('status', 'active');
        $view = $request->query('view', 'cards') === 'list' ? 'list' : 'cards';
        $onlyAlerts = $request->boolean('alerts');

        $query = Employee::with(['company', 'documents'])->inCompany()
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.$q.'%';
                $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('name_en', 'like', $like)
                    ->orWhere('code', 'like', $like)->orWhere('job_title', 'like', $like)
                    ->orWhere('phone', 'like', $like)->orWhere('nationality', 'like', $like));
            })
            ->when(in_array($status, ['active', 'inactive'], true), fn ($w) => $w->where('status', $status))
            ->when($onlyAlerts, fn ($w) => $w->whereHas('documents', fn ($d) => $d->expiringWithin()))
            ->orderBy('name');

        $employees = $query->paginate(48)->withQueryString();
        $onLeave = LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())->whereIn('employee_id', $employees->pluck('id'))->pluck('employee_id')->flip();

        return view('employees.index', compact('employees', 'q', 'status', 'view', 'onlyAlerts', 'onLeave'));
    }

    public function create(Request $request)
    {
        $this->manage($request);
        if (Company::count() === 0) {
            return redirect()->route('companies.create')->with('warn', __('أضف شركة أولًا، ثم أضف موظفيها.'));
        }

        return view('employees.form', ['employee' => new Employee(['status' => 'active', 'company_id' => CompanyContext::id()]), 'companies' => Company::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->manage($request);
        $employee = Employee::create($this->validated($request, new Employee));

        return redirect()->route('employees.show', $employee)->with('ok', __('تمت إضافة الموظف.'));
    }

    public function show(Request $request, Employee $employee)
    {
        $employee->load(['company', 'vehicles']);
        $documents = $employee->documents()->orderByRaw('expiry_date is null')->orderBy('expiry_date')->get();
        $leaves = $employee->leaves()->with('type')->latest('start_date')->get();
        $types = LeaveType::where('is_active', true)->orderBy('id')->get();
        $balances = $types->map(fn ($t) => ['type' => $t, 'left' => $employee->leaveBalance($t)]);
        $tab = in_array($request->query('tab'), ['documents', 'leaves', 'notes'], true) ? $request->query('tab') : 'documents';

        return view('employees.show', [
            'employee' => $employee, 'documents' => $documents, 'leaves' => $leaves, 'balances' => $balances, 'types' => $types, 'tab' => $tab,
            'companies' => Company::orderBy('name')->get(), 'onLeave' => $employee->onLeaveToday(),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->manage($request);
        $employee->update($this->validated($request, $employee));

        return redirect()->route('employees.show', ['employee' => $employee, 'tab' => $request->input('tab', 'documents')])->with('ok', __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403, __('هذه الصفحة للمدير فقط'));
        $employee->delete();

        return redirect()->route('employees.index')->with('ok', __('تم حذف الموظف وكل سجلاته.'));
    }

    private function validated(Request $request, Employee $employee): array
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:160'],
            'name_en' => ['nullable', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('employees', 'code')->ignore($employee->id)],
            'nationality' => ['nullable', 'string', 'max:60'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'hire_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ], ['code.unique' => __('رقم الموظف مستخدم مسبقًا.')]);

        return array_map(fn ($v) => $v === '' ? null : $v, $data);
    }
}
