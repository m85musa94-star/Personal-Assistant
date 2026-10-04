<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use App\Support\CompanyContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'name_en', 'cr_number', 'tax_number', 'phone', 'email', 'address', 'is_active', 'notes'])]
class Company extends Model
{
    use HasLocalizedName;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** الشركات المسموح بها للمستخدم الحالي. */
    public function scopeAllowed($query)
    {
        $allowed = CompanyContext::allowedIds();

        return $allowed === null ? $query : $query->whereIn('companies.id', $allowed);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
