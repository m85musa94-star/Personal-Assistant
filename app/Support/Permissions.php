<?php

namespace App\Support;

/**
 * صلاحيات النظام: لكل قسم صلاحيتا «عرض» و«تعديل». مدير النظام (is_admin) يملك كل شيء ولا يُقيَّد.
 * التعديل يستلزم العرض. الحذف النهائي وإدارة المستخدمين لمدير النظام فقط.
 */
class Permissions
{
    /** صفوف مصفوفة الصلاحيات: [التسمية العربية (مفتاح ترجمة), مفتاح العرض, مفتاح التعديل] */
    public const MATRIX = [
        ['المهام والجدولة', 'tasks.use', null],
        ['الموظفون', 'employees.view', 'employees.edit'],
        ['سجل الموظف (ملاحظات وإنذارات وتقديرات)', null, 'records.edit'],
        ['الإجازات', 'leaves.view', 'leaves.edit'],
        ['السيارات وسجلاتها', 'vehicles.view', 'vehicles.edit'],
        ['التنبيهات', 'alerts.view', null],
        ['الشركات', null, 'companies.edit'],
        ['أنواع الإجازات', null, 'leave_types.edit'],
    ];

    /** قوالب جاهزة للتعبئة السريعة. */
    public const PRESETS = [
        'viewer' => ['tasks.use', 'employees.view', 'leaves.view', 'vehicles.view', 'alerts.view'],
        'manager' => ['tasks.use', 'employees.view', 'employees.edit', 'records.edit', 'leaves.view', 'leaves.edit', 'vehicles.view', 'vehicles.edit', 'alerts.view', 'companies.edit', 'leave_types.edit'],
        'hr' => ['tasks.use', 'employees.view', 'employees.edit', 'records.edit', 'leaves.view', 'leaves.edit', 'alerts.view'],
        'fleet' => ['tasks.use', 'vehicles.view', 'vehicles.edit', 'alerts.view'],
        'tasks' => ['tasks.use'],
    ];

    /** @return array<int, string> */
    public static function all(): array
    {
        $out = [];
        foreach (self::MATRIX as [, $view, $edit]) {
            foreach ([$view, $edit] as $k) {
                if ($k) {
                    $out[] = $k;
                }
            }
        }

        return $out;
    }

    /** يُصفّي المفاتيح غير المعروفة ويضيف «العرض» لكل «تعديل». */
    public static function normalize(array $perms): array
    {
        $perms = array_values(array_intersect($perms, self::all()));
        foreach (self::MATRIX as [, $view, $edit]) {
            if ($edit && $view && in_array($edit, $perms, true)) {
                $perms[] = $view;
            }
        }
        if (in_array('records.edit', $perms, true)) {
            $perms[] = 'employees.view';
        }
        if (in_array('leaves.edit', $perms, true)) {
            $perms[] = 'employees.view';
        }

        return array_values(array_unique($perms));
    }

    /** اسم القالب المطابق تمامًا لصلاحيات المستخدم، أو 'custom'. */
    public static function presetOf(array $perms): string
    {
        $perms = self::normalize($perms);
        sort($perms);
        foreach (self::PRESETS as $name => $list) {
            $l = self::normalize($list);
            sort($l);
            if ($l === $perms) {
                return $name;
            }
        }

        return 'custom';
    }
}
