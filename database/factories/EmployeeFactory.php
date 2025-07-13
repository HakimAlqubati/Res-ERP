<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'name'         => $this->faker->name(),
            'position_id'  => rand(1, 5),
            // 'email'        => $this->faker->unique()->safeEmail(),
            'phone_number' => $this->faker->phoneNumber(),
            'job_title'    => $this->faker->jobTitle(),
            // 'employee_no'  => $this->faker->unique()->numberBetween(1015484, 3958451),
            'active'       => 1,
            'join_date'    => $this->faker->date(),
            'address'      => $this->faker->address(),
            'salary'       => rand(1000, 5000),
            'rfid'         => $this->faker->unique()->numberBetween(10000, 99999),
            
            'gender'       => $this->faker->randomElement([1, 0]),
            'nationality'  => $this->faker->country(),
            // أضف أي حقول أخرى حسب جدولك
        ];
    }
}