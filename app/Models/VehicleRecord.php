<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'type', 'record_date', 'odometer', 'amount', 'vendor', 'description'])]
class VehicleRecord extends Model
{
    public const TYPES = ['service', 'repair', 'fuel', 'fine', 'accident', 'other'];

    protected function casts(): array
    {
        return ['record_date' => 'date', 'odometer' => 'integer', 'amount' => 'decimal:2'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
