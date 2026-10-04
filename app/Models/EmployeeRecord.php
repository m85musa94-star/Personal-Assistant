<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** سجل الموظف: أحداث تلقائية (type=system) وإدخالات يدوية (ملاحظة، إنذار، تقدير…). */
#[Fillable(['employee_id', 'user_id', 'type', 'event', 'data', 'title', 'body', 'event_date'])]
class EmployeeRecord extends Model
{
    public const TYPES = ['note', 'warning', 'commendation', 'evaluation', 'training', 'incident', 'other'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'data' => 'array'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /** حدث تلقائي. القيم التي لها نسخة «_en» تُعرض بلغة الواجهة. */
    public static function log(Employee $employee, string $event, array $data = [], ?int $userId = null): self
    {
        return self::create([
            'employee_id' => $employee->id, 'user_id' => $userId ?? request()->user()?->id, 'type' => 'system',
            'event' => $event, 'data' => $data, 'event_date' => today(),
        ]);
    }

    public function text(): string
    {
        if (! $this->isSystem()) {
            return (string) $this->title;
        }
        $data = $this->data ?? [];
        $params = [];
        foreach ($data as $k => $v) {
            if (str_ends_with($k, '_en')) {
                continue;
            }
            $en = $data[$k.'_en'] ?? null;
            $params[$k] = (app()->getLocale() === 'en' && filled($en)) ? $en : $v;
        }
        if (isset($params['status'])) {
            $params['status'] = __('types.employee_status.'.$params['status']);
        }

        return __('record.'.$this->event, $params);
    }
}
