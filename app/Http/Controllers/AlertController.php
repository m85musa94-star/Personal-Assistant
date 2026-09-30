<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Models\VehicleDocument;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $scope = in_array($request->query('scope'), ['employees', 'vehicles'], true) ? $request->query('scope') : 'all';
        $days = in_array((int) $request->query('days'), [0, 30, 60, 90, 180], true) && $request->has('days') ? (int) $request->query('days') : 60;

        $items = collect();
        if ($scope !== 'vehicles') {
            $items = $items->merge(EmployeeDocument::with('employee.company')->expiringWithin($days)
                ->whereHas('employee', fn ($e) => $e->inCompany()->where('status', 'active'))->get()
                ->map(fn ($d) => ['kind' => 'employee', 'doc' => $d, 'owner' => $d->employee->displayName(), 'company' => $d->employee->company, 'url' => route('employees.show', ['employee' => $d->employee, 'tab' => 'documents'])]));
        }
        if ($scope !== 'employees') {
            $items = $items->merge(VehicleDocument::with('vehicle.company')->expiringWithin($days)
                ->whereHas('vehicle', fn ($v) => $v->inCompany()->where('status', '!=', 'sold'))->get()
                ->map(fn ($d) => ['kind' => 'vehicle', 'doc' => $d, 'owner' => $d->vehicle->title(), 'company' => $d->vehicle->company, 'url' => route('vehicles.show', ['vehicle' => $d->vehicle, 'tab' => 'documents'])]));
        }
        $items = $items->sortBy(fn ($i) => $i['doc']->expiry_date)->values();

        return view('alerts.index', compact('items', 'scope', 'days'));
    }
}
