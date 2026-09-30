<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'name_en', 'is_paid', 'annual_days', 'is_active'])]
class LeaveType extends Model
{
    use HasLocalizedName;

    protected function casts(): array
    {
        return ['is_paid' => 'boolean', 'is_active' => 'boolean', 'annual_days' => 'float'];
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
