<?php

namespace App\Support;

use App\Models\EmployeeDocument;
use App\Models\VehicleDocument;

/** عدّادات التنبيهات للشركة المختارة (لشارة الجرس والصفحة الرئيسية). */
class Alerts
{
    /** @return array{expired: int, soon: int, total: int} */
    public static function counts(): array
    {
        $attrs = request()->attributes;
        if ($attrs->has('alert_counts')) {
            return $attrs->get('alert_counts');
        }
        $scopeEmp = fn ($q) => $q->whereHas('employee', fn ($e) => $e->inCompany()->where('status', 'active'));
        $scopeVeh = fn ($q) => $q->whereHas('vehicle', fn ($v) => $v->inCompany()->where('status', '!=', 'sold'));
        $u = request()->user();
        $emp = $u?->can('employees.view');
        $veh = $u?->can('vehicles.view');
        $expired = ($emp ? $scopeEmp(EmployeeDocument::whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()))->count() : 0)
            + ($veh ? $scopeVeh(VehicleDocument::whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()))->count() : 0);
        $total = ($emp ? $scopeEmp(EmployeeDocument::expiringWithin())->count() : 0) + ($veh ? $scopeVeh(VehicleDocument::expiringWithin())->count() : 0);
        $out = ['expired' => $expired, 'soon' => $total - $expired, 'total' => $total];
        $attrs->set('alert_counts', $out);

        return $out;
    }
}
