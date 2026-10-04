<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Support\Alerts;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /** القائمة الرئيسية (شبكة التطبيقات) مع أهم الأرقام والتنبيهات للشركة المختارة. */
    public function index(Request $request)
    {
        $u = $request->user();
        $onLeave = ! $u->can('leaves.view') ? null : LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
            ->whereHas('employee', fn ($e) => $e->inCompany())->count();

        $canAlerts = $u->can('alerts.view');
        $urgent = collect()
            ->merge(! $canAlerts || ! $u->can('employees.view') ? [] : EmployeeDocument::with('employee')->expiringWithin()->whereHas('employee', fn ($e) => $e->inCompany()->where('status', 'active'))->get()
                ->map(fn ($d) => ['doc' => $d, 'owner' => $d->employee->displayName(), 'icon' => 'users', 'url' => route('employees.show', ['employee' => $d->employee, 'tab' => 'documents'])]))
            ->merge(! $canAlerts || ! $u->can('vehicles.view') ? [] : VehicleDocument::with('vehicle')->expiringWithin()->whereHas('vehicle', fn ($v) => $v->inCompany()->where('status', '!=', 'sold'))->get()
                ->map(fn ($d) => ['doc' => $d, 'owner' => $d->vehicle->title(), 'icon' => 'car', 'url' => route('vehicles.show', ['vehicle' => $d->vehicle, 'tab' => 'documents'])]))
            ->sortBy(fn ($i) => $i['doc']->expiry_date)->take(8)->values();

        return view('home', [
            'employees' => $u->can('employees.view') ? Employee::inCompany()->where('status', 'active')->count() : null,
            'vehicles' => $u->can('vehicles.view') ? Vehicle::inCompany()->where('status', '!=', 'sold')->count() : null,
            'onLeave' => $onLeave, 'alerts' => $canAlerts ? Alerts::counts() : null, 'urgent' => $urgent,
        ]);
    }
}
