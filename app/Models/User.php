<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'is_active', 'is_hr', 'locale', 'theme', 'last_company_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['is_admin' => false, 'is_active' => true, 'is_hr' => false, 'locale' => 'ar', 'theme' => 'auto'];

    /** مدير النظام أو مسؤول الموظفين والمركبات: يملك التعديل على بياناتهم. */
    public function canManageData(): bool
    {
        return $this->is_admin || $this->is_hr;
    }

    public function state(): HasOne
    {
        return $this->hasOne(UserState::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_hr' => 'boolean',
        ];
    }
}
