<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;

trait MakesData
{
    /** @param string $role admin | hr (= مسؤول الموظفين والمركبات) | user (= مشاهد) */
    protected function user(string $email = 'u@example.com', string $role = 'user', ?array $perms = null): User
    {
        $u = new User(['name' => $email, 'email' => $email, 'password' => 'correct-horse-1']);
        $u->is_admin = $role === 'admin';
        $u->permissions = $role === 'admin' ? null : ($perms ?? ($role === 'hr' ? Permissions::PRESETS['manager'] : Permissions::PRESETS['viewer']));
        $u->save();

        return $u;
    }

    protected function company(string $name = 'شركة أ', array $extra = []): Company
    {
        return Company::create(array_merge(['name' => $name], $extra));
    }

    protected function emp(Company $c, string $name = 'موظف', array $extra = []): Employee
    {
        return Employee::create(array_merge(['company_id' => $c->id, 'name' => $name, 'status' => 'active'], $extra));
    }

    protected function car(Company $c, string $plate = 'ABC 1234', array $extra = []): Vehicle
    {
        return Vehicle::create(array_merge(['company_id' => $c->id, 'plate' => $plate, 'status' => 'active'], $extra));
    }

    protected function annual(): LeaveType
    {
        return LeaveType::where('name_en', 'Annual leave')->firstOrFail();
    }
}
