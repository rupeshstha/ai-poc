<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
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
            "name" => $this->faker->countryCode(),
            "host" => $this->faker->url(),
            "currency" => $this->faker->currencyCode(),
            "default_locale" => $this->faker->locale(),
        ];
    }
}
