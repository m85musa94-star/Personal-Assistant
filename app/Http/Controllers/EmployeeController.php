<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\CompanyContext;
use App\Support\Nationalities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $this->permit($request, 'employees.edit');
        if (Company::allowed()->doesntExist()) {
            return redirect()->route('companies.create')->with('warn', __('أضف شركة أولًا، ثم أضف موظفيها.'));
        }

        return view('employees.form', ['employee' => new Employee(['status' => 'active', 'company_id' => CompanyContext::id()]), 'companies' => Company::allowed()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->permit($request, 'employees.edit');
        $employee = Employee::create($this->validated($request, new Employee));
        $employee->load('company');
        EmployeeRecord::log($employee, 'created', ['company' => $employee->company->name, 'company_en' => $employee->company->name_en]);

        return redirect()->route('employees.show', $employee)->with('ok', __('تمت إضافة الموظف.'));
    }

    public function show(Request $request, Employee $employee)
    {
        $this->guardCompany($employee->company_id);
        $employee->load(['company', 'vehicles']);
        $documents = $employee->documents()->orderByRaw('expiry_date is null')->orderBy('expiry_date')->get();
        $canLeaves = $request->user()->can('leaves.view');
        $leaves = $canLeaves ? $employee->leaves()->with('type')->latest('start_date')->get() : collect();
        $types = LeaveType::where('is_active', true)->orderBy('id')->get();
        $balances = $canLeaves ? $types->map(fn ($t) => ['type' => $t, 'left' => $employee->leaveBalance($t)]) : collect();
        $tabs = ['documents', 'notes', 'record'];
        $records = $employee->records()->with('user')->orderByDesc('event_date')->orderByDesc('id')->get();
        if ($canLeaves) {
            $tabs[] = 'leaves';
        }
        $tab = in_array($request->query('tab'), $tabs, true) ? $request->query('tab') : 'documents';

        return view('employees.show', [
            'employee' => $employee, 'documents' => $documents, 'leaves' => $leaves, 'balances' => $balances, 'types' => $types, 'tab' => $tab,
            'companies' => Company::allowed()->orderBy('name')->get(), 'onLeave' => $employee->onLeaveToday(), 'canLeaves' => $canLeaves, 'records' => $records,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $this->permit($request, 'employees.edit');
        $this->guardCompany($employee->company_id);
        $old = $employee->load('company')->only(['company_id', 'status', 'job_title']);
        $oldCompany = $employee->company;
        $employee->update($this->validated($request, $employee));
        $employee->load('company');
        if ((int) $old['company_id'] !== (int) $employee->company_id) {
            EmployeeRecord::log($employee, 'company', ['from' => $oldCompany?->name, 'from_en' => $oldCompany?->name_en, 'to' => $employee->company->name, 'to_en' => $employee->company->name_en]);
        }
        if ($old['status'] !== $employee->status) {
            EmployeeRecord::log($employee, 'status', ['status' => $employee->status]);
        }
        if (($old['job_title'] ?? null) !== $employee->job_title) {
            EmployeeRecord::log($employee, 'job', ['from' => $old['job_title'] ?: '—', 'to' => $employee->job_title ?: '—']);
        }

        return redirect()->route('employees.show', ['employee' => $employee, 'tab' => $request->input('tab', 'documents')])->with('ok', __('تم حفظ التعديلات.'));
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403, __('هذه الصفحة للمدير فقط'));
        $employee->delete();

        return redirect()->route('employees.index')->with('ok', __('تم حذف الموظف وكل سجلاته.'));
    }

    /** سجل الموظفين: جدول شامل قابل للطباعة. */
    public function register(Request $request)
    {
        return view('employees.register', ['rows' => $this->registerRows($request), 'status' => $this->registerStatus($request), 'company' => CompanyContext::current()]);
    }

    /** تصدير سجل الموظفين CSV (UTF-8 مع BOM ليفتح بالعربية في إكسل). */
    public function export(Request $request)
    {
        $rows = $this->registerRows($request);
        $head = [__('رقم الموظف'), __('الاسم'), __('الاسم (إنجليزي)'), __('الشركة'), __('المسمى الوظيفي'), __('الجنسية'), __('تاريخ التعيين'), __('الجوال'), __('البريد الإلكتروني'), __('الحالة')];
        foreach (['iqama', 'insurance', 'passport', 'contract', 'work_permit', 'driving_license'] as $t) {
            $head[] = __('types.employee_doc.'.$t).' — '.__('الرقم');
            $head[] = __('types.employee_doc.'.$t).' — '.__('الانتهاء');
        }
        $clean = fn ($v) => is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$v : $v;

        return response()->streamDownload(function () use ($rows, $head, $clean) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $head);
            foreach ($rows as $r) {
                fputcsv($out, array_map($clean, $r['csv']));
            }
            fclose($out);
        }, 'employees-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** ملف الموظف الكامل للطباعة. */
    public function print(Request $request, Employee $employee)
    {
        $this->guardCompany($employee->company_id);
        $employee->load(['company', 'documents', 'vehicles']);
        $canLeaves = $request->user()->can('leaves.view');

        return view('employees.print', [
            'employee' => $employee, 'documents' => $employee->documents()->orderByRaw('expiry_date is null')->orderBy('expiry_date')->get(),
            'leaves' => $canLeaves ? $employee->leaves()->with('type')->orderByDesc('start_date')->get() : collect(),
            'records' => $employee->records()->with('user')->orderByDesc('event_date')->orderByDesc('id')->get(), 'canLeaves' => $canLeaves,
        ]);
    }

    private function registerStatus(Request $request): string
    {
        return in_array($request->query('status'), ['active', 'inactive', 'all'], true) ? $request->query('status') : 'active';
    }

    /** @return Collection<int, array{employee: Employee, docs: array, csv: array}> */
    private function registerRows(Request $request)
    {
        $status = $this->registerStatus($request);
        $employees = Employee::with(['company', 'documents'])->inCompany()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy('company_id')->orderBy('name')->get();

        return $employees->map(function (Employee $e) {
            $docs = [];
            foreach (['iqama', 'insurance', 'passport', 'contract', 'work_permit', 'driving_license'] as $t) {
                $docs[$t] = $e->documents->where('type', $t)->sortByDesc('expiry_date')->first();
            }
            $csv = [$e->code, $e->name, $e->name_en, $e->company?->displayName(), $e->job_title, Nationalities::label($e->nationality), $e->hire_date?->format('Y-m-d'), $e->phone, $e->email, __('types.employee_status.'.$e->status)];
            foreach ($docs as $d) {
                $csv[] = $d?->number;
                $csv[] = $d?->expiry_date?->format('Y-m-d');
            }

            return ['employee' => $e, 'docs' => $docs, 'csv' => $csv];
        });
    }

    private function validated(Request $request, Employee $employee): array
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id', function ($attr, $value, $fail) use ($request) {
                if (! $request->user()->canAccessCompany((int) $value)) {
                    $fail(__('لا تملك صلاحية على هذه الشركة.'));
                }
            }],
            'name' => ['required', 'string', 'max:160'],
            'name_en' => ['nullable', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:40', Rule::unique('employees', 'code')->ignore($employee->id)],
            'nationality' => ['required', 'string', 'max:60'],
            'job_title' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'hire_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ], ['code.unique' => __('رقم الموظف مستخدم مسبقًا.')]);

        return array_map(fn ($v) => $v === '' ? null : $v, $data);
    }
}
