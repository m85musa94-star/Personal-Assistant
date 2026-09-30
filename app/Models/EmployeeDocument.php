<?php

namespace App\Models;

use App\Models\Concerns\TracksExpiry;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'type', 'title', 'number', 'provider', 'issue_date', 'expiry_date', 'notes'])]
class EmployeeDocument extends Model
{
    use TracksExpiry;

    public const TYPES = ['iqama', 'passport', 'insurance', 'contract', 'work_permit', 'driving_license', 'other'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function label(): string
    {
        return $this->type === 'other' && filled($this->title) ? $this->title : __('types.employee_doc.'.$this->type);
    }
}
