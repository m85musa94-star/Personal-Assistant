<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * الشركة المختارة في مبدّل الشركات (على طريقة أودو). null = كل الشركات.
 * تُحفظ في الجلسة وفي حساب المستخدم ليتذكرها بين الجلسات.
 */
class CompanyContext
{
    public static function id(): ?int
    {
        $attrs = request()->attributes;
        if ($attrs->has('company_ctx')) {
            return $attrs->get('company_ctx');
        }
        $id = session('company_id', request()->user()?->last_company_id);
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
        return Company::orderBy('name')->get();
    }

    public static function set(?int $id): void
    {
        session(['company_id' => $id]);
        request()->attributes->set('company_ctx', $id);
        request()->user()?->forceFill(['last_company_id' => $id])->save();
    }
}
