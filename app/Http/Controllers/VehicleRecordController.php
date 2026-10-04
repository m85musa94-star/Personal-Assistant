<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VehicleRecordController extends Controller
{
    public function index(Request $request)
    {
        $type = in_array($request->query('type'), VehicleRecord::TYPES, true) ? $request->query('type') : null;
        $vehicleId = $request->query('vehicle');
        $year = preg_match('/^\d{4}$/', (string) $request->query('year')) ? (int) $request->query('year') : null;

        $base = VehicleRecord::with('vehicle.company')
            ->whereHas('vehicle', fn ($v) => $v->inCompany())
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($year, fn ($q) => $q->whereYear('record_date', $year));

        $total = (clone $base)->sum('amount');
        $records = $base->orderByDesc('record_date')->orderByDesc('id')->paginate(50)->withQueryString();

        return view('vehicle-records.index', [
            'records' => $records, 'total' => $total, 'type' => $type, 'vehicleId' => $vehicleId, 'year' => $year,
            'vehicles' => Vehicle::inCompany()->orderBy('plate')->get(),
        ]);
    }

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->permit($request, 'vehicles.edit');
        $this->guardCompany($vehicle->company_id);
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', VehicleRecord::TYPES)],
            'record_date' => ['required', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'vendor' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        $vehicle->records()->create($data);

        // عداد المسافة: نرفع قراءة المركبة الحالية إن كانت قراءة السجل أحدث وأكبر.
        if (! empty($data['odometer']) && (int) $data['odometer'] > (int) $vehicle->odometer) {
            $vehicle->update(['odometer' => $data['odometer']]);
        }

        return redirect()->route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'records'])->with('ok', __('تمت إضافة السجل.'));
    }

    public function destroy(Request $request, VehicleRecord $record): RedirectResponse
    {
        $this->permit($request, 'vehicles.edit');
        $vehicle = $record->vehicle;
        $this->guardCompany($vehicle->company_id);
        $record->delete();

        return redirect()->route('vehicles.show', ['vehicle' => $vehicle, 'tab' => 'records'])->with('ok', __('تم حذف السجل.'));
    }
}
