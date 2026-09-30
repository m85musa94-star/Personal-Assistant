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
        $expired = $scopeEmp(EmployeeDocument::whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()))->count()
            + $scopeVeh(VehicleDocument::whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()))->count();
        $total = $scopeEmp(EmployeeDocument::expiringWithin())->count() + $scopeVeh(VehicleDocument::expiringWithin())->count();
        $out = ['expired' => $expired, 'soon' => $total - $expired, 'total' => $total];
        $attrs->set('alert_counts', $out);

        return $out;
    }
}
