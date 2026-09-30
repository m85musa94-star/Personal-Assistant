<?php

namespace App\Models\Concerns;

use App\Support\CompanyContext;

trait InCompany
{
    /** يقيّد بالشركة المختارة في المبدّل، أو بشركة محددة، أو لا يقيّد عند «كل الشركات». */
    public function scopeInCompany($query, ?int $id = null)
    {
        $id ??= CompanyContext::id();

        return $id ? $query->where($query->getModel()->getTable().'.company_id', $id) : $query;
    }
}
