<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'employee_id' => fake()->unique()->bothify('???-###'),
            'full_name' => fake('id_ID')->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'manager_id' => null,
            'work_schedule_id' => WorkSchedule::factory(),
            'join_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'employment_type' => fake()->randomElement(['permanent', 'contract', 'intern', 'probation']),
            'employment_status' => 'active',
            'photo_path' => null,
        ];
    }
}
