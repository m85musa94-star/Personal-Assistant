<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Vehicle;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $status = in_array($request->query('status'), [...Vehicle::STATUSES, 'all', 'current'], true) ? $request->query('status') : 'current';
        $view = $request->query('view', 'cards') === 'list' ? 'list' : 'cards';

        $vehicles = Vehicle::with(['company', 'driver', 'documents'])->inCompany()
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.$q.'%';
                $w->where(fn ($x) => $x->where('plate', 'like', $like)->orWhere('make', 'like', $like)->orWhere('model', 'like', $like)
                    ->orWhere('vin', 'like', $like)->orWhere('color', 'like', $like));
            })
            // الافتراضي «الحالية»: كل ما عدا المباعة
            ->when($status === 'current', fn ($w) => $w->where('status', '!=', 'sold'))
            ->when(in_array($status, Vehicle::STATUSES, true), fn ($w) => $w->where('status', $status))
            ->when($request->boolean('alerts'), fn ($w) => $w->whereHas('documents', fn ($d) => $d->expiringWithin()))
            ->orderBy('plate')->paginate(48)->withQueryString();

        return view('vehicles.index', ['vehicles' => $vehicles, 'q' => $q, 'status' => $status, 'view' => $view, 'onlyAlerts' => $request->boolean('alerts')]);
    }

    public function create(Request $request)
    {
        $this->manage($request);
        if (Company::count() === 0) {
            return redirect()->route('companies.create')->with('warn', __('أضف شركة أولًا، ثم أضف مركباتها.'));
        }

        return view('vehicles.form', $this->formData(new Vehicle(['status' => 'active', 'company_id' => CompanyContext::id()])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->manage($request);
        $vehicle = Vehicle::create($this->validated($request));

        return redirect()->route('vehicles.show', $vehicle)->with('ok', __('تمت إضافة المركبة.'));
    }

    public function show(Request $request, Vehicle $vehicle)
    {
        $vehicle->load(['company', 'driver']);
        $documents = $vehicle->documents()->orderByRaw('expiry_date is null')->orderBy('expiry_date')->get();
        $records = $vehicle->records()->orderByDesc('record_date')->orderByDesc('id')->get();
        $tab = in_array($request->query('tab'), ['documents', 'records', 'notes'], true) ? $request->query('tab') : 'documents';

        return view('vehicles.show', ['documents' => $documents, 'records' => $records, 'tab' => $tab, 'total' => $records->sum('amount')] + $this->formData($vehicle));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->manage($request);
        $vehicle->update($this->validated($request));

        return redirect()->route('vehicles.show', ['vehicle' => $vehicle, 'tab' => $request->input('tab', 'documents')])->with('ok', __('تم حفظ التعديلات.'));
    }

    /** تغيير الحالة من شريط الحالة (كما في أودو). */
    public function status(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->manage($request);
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', Vehicle::STATUSES)]]);
        $vehicle->update($data);

        return back()->with('ok', __('تم تحديث الحالة.'));
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403, __('هذه الصفحة للمدير فقط'));
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('ok', __('تم حذف المركبة وكل سجلاتها.'));
    }

    private function formData(Vehicle $vehicle): array
    {
        return ['vehicle' => $vehicle, 'companies' => Company::orderBy('name')->get(), 'drivers' => Employee::where('status', 'active')->orderBy('name')->get()];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plate' => ['required', 'string', 'max:40'],
            'make' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:60'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'color' => ['nullable', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:40'],
            'type' => ['nullable', 'in:'.implode(',', Vehicle::TYPES)],
            'fuel' => ['nullable', 'in:'.implode(',', Vehicle::FUELS)],
            'status' => ['required', 'in:'.implode(',', Vehicle::STATUSES)],
            'driver_id' => ['nullable', 'exists:employees,id'],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'purchase_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        return array_map(fn ($v) => $v === '' ? null : $v, $data);
    }
}
