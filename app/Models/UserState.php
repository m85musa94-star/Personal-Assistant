<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'data', 'ts'])]
class UserState extends Model
{
    protected function casts(): array
    {
        return ['data' => 'array', 'ts' => 'integer'];
    }
}
