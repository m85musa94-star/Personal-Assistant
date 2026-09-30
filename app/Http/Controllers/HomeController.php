<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Support\Alerts;

class HomeController extends Controller
{
    /** القائمة الرئيسية (شبكة التطبيقات) مع أهم الأرقام والتنبيهات للشركة المختارة. */
    public function index()
    {
        $onLeave = LeaveRequest::where('status', 'approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())
            ->whereHas('employee', fn ($e) => $e->inCompany())->count();

        $urgent = collect()
            ->merge(EmployeeDocument::with('employee')->expiringWithin()->whereHas('employee', fn ($e) => $e->inCompany()->where('status', 'active'))->get()
                ->map(fn ($d) => ['doc' => $d, 'owner' => $d->employee->displayName(), 'icon' => 'users', 'url' => route('employees.show', ['employee' => $d->employee, 'tab' => 'documents'])]))
            ->merge(VehicleDocument::with('vehicle')->expiringWithin()->whereHas('vehicle', fn ($v) => $v->inCompany()->where('status', '!=', 'sold'))->get()
                ->map(fn ($d) => ['doc' => $d, 'owner' => $d->vehicle->title(), 'icon' => 'car', 'url' => route('vehicles.show', ['vehicle' => $d->vehicle, 'tab' => 'documents'])]))
            ->sortBy(fn ($i) => $i['doc']->expiry_date)->take(8)->values();

        return view('home', [
            'employees' => Employee::inCompany()->where('status', 'active')->count(),
            'vehicles' => Vehicle::inCompany()->where('status', '!=', 'sold')->count(),
            'onLeave' => $onLeave, 'alerts' => Alerts::counts(), 'urgent' => $urgent,
        ]);
    }
}
