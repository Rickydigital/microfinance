<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

#[Signature('user:create
    {--first-name= : Employee first name}
    {--middle-name= : Employee middle name}
    {--last-name= : Employee last name}
    {--email= : Unique login email}
    {--phone= : Employee phone number}
    {--staff-number= : Unique staff number}
    {--gender= : Male, Female, or Other}
    {--date-of-birth= : Date in YYYY-MM-DD format}
    {--branch= : Branch code}
    {--department= : Department code}
    {--role= : Existing role name}
    {--password= : Password; omit to enter it securely}')]
#[Description('Create an employee account and assign a configured role')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $branchCode = $this->option('branch') ?: $this->choice('Branch', Branch::query()->orderBy('name')->pluck('code')->all());
        $roleName = $this->option('role') ?: $this->choice('Role', Role::query()->orderBy('name')->pluck('name')->all());
        $departmentCode = $this->option('department');

        $data = [
            'first_name' => $this->option('first-name') ?: $this->ask('First name'),
            'middle_name' => $this->option('middle-name'),
            'last_name' => $this->option('last-name') ?: $this->ask('Last name'),
            'email' => $this->option('email') ?: $this->ask('Email address'),
            'phone' => $this->option('phone'),
            'staff_number' => $this->option('staff-number') ?: $this->ask('Staff number'),
            'gender' => $this->option('gender'),
            'date_of_birth' => $this->option('date-of-birth'),
            'password' => $this->option('password') ?: $this->secret('Password'),
            'branch_id' => Branch::query()->where('code', $branchCode)->value('id'),
            'department_id' => $departmentCode ? Department::query()->where('code', $departmentCode)->value('id') : null,
            'role' => $roleName,
        ];

        $validator = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'staff_number' => ['required', 'string', 'max:50', 'unique:users,staff_number'],
            'gender' => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8'],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $validated = $validator->validated();
        unset($validated['role']);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::query()->create($validated);
        $user->assignRole($roleName);

        $this->components->info("User {$user->full_name} created with role {$roleName}.");

        return self::SUCCESS;
    }
}
