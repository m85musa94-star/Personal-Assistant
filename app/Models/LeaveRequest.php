<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'reason', 'status', 'decided_by', 'decided_at', 'decision_note'])]
class LeaveRequest extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'days' => 'float', 'decided_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** عدد الأيام التقويمية شاملًا اليومين الطرفين. */
    public static function calendarDays(string $start, string $end): int
    {
        return Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
    }
}
