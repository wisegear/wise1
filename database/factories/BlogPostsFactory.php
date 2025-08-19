<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class BlogPostsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->words(5, true);
        $slug = Str::slug($title, '-');

        return [ 
            'title' => $title,
            'slug' => $slug,
            'summary' => $this->faker->paragraph(5, false),
            'body' => $this->faker->paragraph(20, false),
            'categories_id' => $this->faker->numberBetween(1, 5),
            'user_id' => $this->faker->numberBetween(1, 4),
            'created_at' => $this->faker->dateTimeThisYear(),
            'date' => $this->faker->dateTimeThisYear(),
        ];
    }
}
