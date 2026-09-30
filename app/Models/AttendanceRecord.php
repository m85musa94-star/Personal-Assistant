<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'check_in', 'check_out', 'source', 'note'])]
class AttendanceRecord extends Model
{
    protected function casts(): array
    {
        return ['check_in' => 'datetime', 'check_out' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** الساعات العشرية، وللمفتوح حتى الآن. */
    public function hours(): float
    {
        $end = $this->check_out ?? now();

        return round(max(0, $this->check_in->diffInSeconds($end)) / 3600, 2);
    }
}
