<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'is_active', 'permissions', 'all_companies', 'locale', 'theme', 'last_company_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['is_admin' => false, 'is_active' => true, 'all_companies' => true, 'locale' => 'ar', 'theme' => 'auto'];

    public function hasPermission(string $permission): bool
    {
        return $this->is_admin || in_array($permission, $this->permissions ?? [], true);
    }

    /** أي صلاحية تتيح رؤية قائمة الشركات (تحتاجها شاشات الموظفين والمركبات والمبدّل). */
    public function canViewCompanies(): bool
    {
        return $this->is_admin || array_intersect(['employees.view', 'vehicles.view', 'leaves.view', 'companies.edit'], $this->permissions ?? []) !== [];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    /** null = كل الشركات؛ وإلا قائمة معرّفات الشركات المسموح بها. مدير النظام غير مقيَّد. */
    public function allowedCompanyIds(): ?array
    {
        if ($this->is_admin || $this->all_companies) {
            return null;
        }

        return $this->companies()->pluck('companies.id')->map(fn ($i) => (int) $i)->all();
    }

    public function canAccessCompany(?int $companyId): bool
    {
        $allowed = $this->allowedCompanyIds();

        return $allowed === null || ($companyId !== null && in_array($companyId, $allowed, true));
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
            'all_companies' => 'boolean',
            'permissions' => 'array',
        ];
    }
}
