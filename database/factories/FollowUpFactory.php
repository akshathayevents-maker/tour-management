<?php

namespace Database\Factories;

use App\Enums\FollowUpStatus;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by' => User::factory(),
            'due_at' => now()->addDay(),
            'status' => FollowUpStatus::Pending,
        ];
    }
}
