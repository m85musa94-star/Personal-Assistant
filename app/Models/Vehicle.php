<?php

namespace App\Models;

use App\Models\Concerns\InCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'plate', 'make', 'model', 'year', 'color', 'vin', 'type', 'fuel', 'status', 'driver_id', 'odometer', 'purchase_date', 'notes'])]
class Vehicle extends Model
{
    use InCompany;

    public const TYPES = ['sedan', 'suv', 'pickup', 'van', 'truck', 'bus', 'other'];

    public const FUELS = ['petrol', 'diesel', 'hybrid', 'electric'];

    public const STATUSES = ['active', 'maintenance', 'out_of_service', 'sold'];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'year' => 'integer', 'odometer' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(VehicleRecord::class);
    }

    public function title(): string
    {
        return trim($this->plate.' — '.trim(($this->make ?? '').' '.($this->model ?? '')), ' —');
    }
}
