<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * الشركة المختارة في مبدّل الشركات (على طريقة أودو). null = كل الشركات المسموح بها.
 * تُحفظ في الجلسة وفي حساب المستخدم ليتذكرها بين الجلسات.
 * المستخدم المقيَّد بشركات لا يرى ولا يختار غيرها.
 */
class CompanyContext
{
    /** null = بلا قيد؛ وإلا معرّفات الشركات المسموح بها للمستخدم الحالي. */
    public static function allowedIds(): ?array
    {
        $attrs = request()->attributes;
        if (! $attrs->has('company_allowed')) {
            $attrs->set('company_allowed', request()->user()?->allowedCompanyIds());
        }

        return $attrs->get('company_allowed');
    }

    public static function id(): ?int
    {
        $attrs = request()->attributes;
        if ($attrs->has('company_ctx')) {
            return $attrs->get('company_ctx');
        }
        $id = session('company_id', request()->user()?->last_company_id);
        $allowed = self::allowedIds();
        if ($id && $allowed !== null && ! in_array((int) $id, $allowed, true)) {
            $id = null;
        }
        $id = $id && Company::whereKey($id)->exists() ? (int) $id : null;
        $attrs->set('company_ctx', $id);

        return $id;
    }

    public static function current(): ?Company
    {
        $id = self::id();

        return $id ? Company::find($id) : null;
    }

    /** @return Collection<int, Company> */
    public static function all(): Collection
    {
        return Company::allowed()->orderBy('name')->get();
    }

    public static function set(?int $id): void
    {
        session(['company_id' => $id]);
        request()->attributes->set('company_ctx', $id);
        request()->user()?->forceFill(['last_company_id' => $id])->save();
    }
}
