<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'code', 'name', 'name_en', 'email', 'phone', 'job_title', 'department_id', 'manager_id', 'hire_date', 'status', 'id_number', 'id_expiry', 'passport_number', 'passport_expiry', 'contract_type', 'contract_start', 'contract_end', 'salary', 'notes'])]
class Employee extends Model
{
    use HasLocalizedName;

    public const CONTRACT_TYPES = ['full_time', 'part_time', 'contractor', 'temporary'];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date', 'id_expiry' => 'date', 'passport_expiry' => 'date',
            'contract_start' => 'date', 'contract_end' => 'date', 'salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function initial(): string
    {
        return mb_substr($this->displayName(), 0, 1);
    }

    /** @return array<int, array{label: string, date: Carbon, days: int}> وثائق منتهية أو قاربت الانتهاء خلال $within يومًا */
    public function expiringDocuments(int $within = 60): array
    {
        $out = [];
        foreach (['id_expiry' => 'الهوية/الإقامة', 'passport_expiry' => 'جواز السفر', 'contract_end' => 'العقد'] as $field => $label) {
            $d = $this->{$field};
            if ($d && today()->diffInDays($d, false) <= $within) {
                $out[] = ['label' => $label, 'date' => $d, 'days' => (int) today()->diffInDays($d, false)];
            }
        }

        return $out;
    }

    /** رصيد نوع إجازة لسنة ميلادية: null إن لم يُحدَّد الاستحقاق. */
    public function leaveBalance(LeaveType $type, ?int $year = null): ?float
    {
        if ($type->annual_days === null) {
            return null;
        }
        $year ??= (int) today()->format('Y');
        $used = (float) $this->leaveRequests()
            ->where('leave_type_id', $type->id)->where('status', 'approved')
            ->whereYear('start_date', $year)->sum('days');

        return round($type->annual_days - $used, 1);
    }
}
