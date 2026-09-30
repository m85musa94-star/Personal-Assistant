<?php

namespace App\Models;

use App\Models\Concerns\TracksExpiry;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'type', 'title', 'number', 'provider', 'issue_date', 'expiry_date', 'notes'])]
class VehicleDocument extends Model
{
    use TracksExpiry;

    public const TYPES = ['registration', 'insurance', 'inspection', 'operating_card', 'authorization', 'other'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'expiry_date' => 'date'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function label(): string
    {
        return $this->type === 'other' && filled($this->title) ? $this->title : __('types.vehicle_doc.'.$this->type);
    }
}
