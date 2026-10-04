<?php

namespace App\Models\Concerns;

use App\Support\CompanyContext;

trait InCompany
{
    /** يقيّد بالشركة المختارة في المبدّل، أو بشركة محددة، أو لا يقيّد عند «كل الشركات». */
    public function scopeInCompany($query, ?int $id = null)
    {
        $id ??= CompanyContext::id();
        $col = $query->getModel()->getTable().'.company_id';
        if ($id) {
            return $query->where($col, $id);
        }
        $allowed = CompanyContext::allowedIds();

        return $allowed === null ? $query : $query->whereIn($col, $allowed);
    }
}
