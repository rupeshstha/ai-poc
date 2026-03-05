<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Website>
 */
class WebsiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "id" => Str::uuid()->toString(),
            "name" => $this->faker->company(),
            "has_custom_host" => $this->faker->boolean(),
            "host_name" => $this->faker->url(),
            "default_channel_id" => null,
            "navigate_structure" => Arr::random(["domain", "path"]),
            "created_at" => now(),
            "updated_at" => now(),
        ];
    }
}
