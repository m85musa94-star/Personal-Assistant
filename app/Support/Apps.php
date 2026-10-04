<?php

namespace App\Support;

use App\Models\User;

/** تعريف تطبيقات النظام (شبكة القائمة الرئيسية وشريط التنقل العلوي على طريقة أودو). */
class Apps
{
    /** @return array<string, array{icon: string, color: string, label: string, url: string, prefixes: array<int, string>, menus: array<int, array{label: string, url: string, match?: string, view?: string}>}> */
    public static function all(User $user): array
    {
        $apps = [
            'tasks' => [
                'can' => 'tasks.use', 'icon' => 'check', 'color' => '#0d9488', 'label' => __('المهام والجدولة'), 'url' => url('/tasks'), 'prefixes' => ['tasks'],
                'menus' => [
                    ['label' => __('لوحة القيادة'), 'url' => url('/tasks#dash'), 'view' => 'dash'],
                    ['label' => __('يومي'), 'url' => url('/tasks#myday'), 'view' => 'myday'],
                    ['label' => __('كل المهام'), 'url' => url('/tasks#tasks'), 'view' => 'tasks'],
                    ['label' => __('المميّزة'), 'url' => url('/tasks#important'), 'view' => 'important'],
                    ['label' => __('كانبان'), 'url' => url('/tasks#board'), 'view' => 'board'],
                    ['label' => __('الجدولة'), 'url' => url('/tasks#calendar'), 'view' => 'calendar'],
                    ['label' => __('الوقت والدوام'), 'url' => url('/tasks#time'), 'view' => 'time'],
                    ['label' => __('قوالب دورية'), 'url' => url('/tasks#templates'), 'view' => 'templates'],
                    ['label' => __('التقارير'), 'url' => url('/tasks#reports'), 'view' => 'reports'],
                    ['label' => __('الإعدادات'), 'url' => url('/tasks#settings'), 'view' => 'settings'],
                ],
            ],
            'employees' => [
                'can' => 'employees.view', 'icon' => 'users', 'color' => '#714b67', 'label' => __('الموظفون'), 'url' => route('employees.index'), 'prefixes' => ['employees', 'leaves'],
                'menus' => [
                    ['label' => __('الموظفون'), 'url' => route('employees.index'), 'match' => 'employees', 'exact' => true],
                    ['label' => __('سجل الموظفين'), 'url' => route('employees.register'), 'match' => 'employees/register'],
                    ['label' => __('الإجازات'), 'url' => route('leaves.index'), 'match' => 'leaves', 'can' => 'leaves.view'],
                ],
            ],
            'vehicles' => [
                'can' => 'vehicles.view', 'icon' => 'car', 'color' => '#2563eb', 'label' => __('السيارات'), 'url' => route('vehicles.index'), 'prefixes' => ['vehicles', 'vehicle-records'],
                'menus' => [
                    ['label' => __('المركبات'), 'url' => route('vehicles.index'), 'match' => 'vehicles'],
                    ['label' => __('السجلات'), 'url' => route('vehicle-records.index'), 'match' => 'vehicle-records'],
                ],
            ],
            'alerts' => [
                'can' => 'alerts.view', 'icon' => 'bell', 'color' => '#d97706', 'label' => __('التنبيهات'), 'url' => route('alerts.index'), 'prefixes' => ['alerts'],
                'menus' => [
                    ['label' => __('الكل'), 'url' => route('alerts.index'), 'match' => 'alerts'],
                    ['label' => __('الموظفون'), 'url' => route('alerts.index', ['scope' => 'employees'])],
                    ['label' => __('المركبات'), 'url' => route('alerts.index', ['scope' => 'vehicles'])],
                ],
            ],
            'settings' => [
                'can' => 'companies.view', 'icon' => 'settings', 'color' => '#64748b', 'label' => __('الإعدادات'), 'url' => route('companies.index'), 'prefixes' => ['settings', 'users'],
                'menus' => array_values(array_filter([
                    ['label' => __('الشركات'), 'url' => route('companies.index'), 'match' => 'settings/companies'],
                    ['label' => __('أنواع الإجازات'), 'url' => route('leave-types.index'), 'match' => 'settings/leave-types'],
                    $user->is_admin ? ['label' => __('حسابات الدخول'), 'url' => route('users.index'), 'match' => 'users'] : null,
                ])),
            ],
        ];

        // نُخفي ما لا يملك المستخدم صلاحيته (التطبيقات وقوائمها).
        $out = [];
        foreach ($apps as $key => $app) {
            if (! $user->can($app['can'])) {
                continue;
            }
            $app['menus'] = array_values(array_filter($app['menus'], fn ($m) => ! isset($m['can']) || $user->can($m['can'])));
            $out[$key] = $app;
        }

        return $out;
    }

    public static function currentKey(string $path): ?string
    {
        foreach (self::all(request()->user()) as $key => $app) {
            foreach ($app['prefixes'] as $p) {
                if ($path === $p || str_starts_with($path, $p.'/')) {
                    return $key;
                }
            }
        }

        return null;
    }
}
