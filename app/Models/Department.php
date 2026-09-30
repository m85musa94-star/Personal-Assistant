<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'name_en'])]
class Department extends Model
{
    use HasLocalizedName;

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
