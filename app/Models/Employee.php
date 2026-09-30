<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use App\Models\Concerns\InCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'code', 'name', 'name_en', 'nationality', 'email', 'phone', 'job_title', 'hire_date', 'status', 'notes'])]
class Employee extends Model
{
    use HasLocalizedName, InCompany;

    protected function casts(): array
    {
        return ['hire_date' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function initial(): string
    {
        return mb_substr($this->displayName(), 0, 1);
    }

    /** في إجازة معتمدة اليوم؟ */
    public function onLeaveToday(): bool
    {
        return $this->leaves()->where('status', 'approved')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->exists();
    }

    /** رصيد نوع إجازة لسنة ميلادية: null إن لم يُحدَّد الاستحقاق (لا نفترض أرقامًا). */
    public function leaveBalance(LeaveType $type, ?int $year = null): ?float
    {
        if ($type->annual_days === null) {
            return null;
        }
        $year ??= (int) today()->format('Y');
        $used = (float) $this->leaves()->where('leave_type_id', $type->id)->where('status', 'approved')
            ->whereYear('start_date', $year)->sum('days');

        return round($type->annual_days - $used, 1);
    }
}
