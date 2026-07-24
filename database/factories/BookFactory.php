<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // ユーザーも自動生成して紐付け
            'title' => fake()->realText(20), // 20文字のダミーテキスト
            'author' => fake()->name(),       // ダミーの氏名
            'isbn' => fake()->isbn13(),     // 13桁のISBN
            'published_date' => fake()->date(),       // ダミーの日付
            'description' => fake()->realText(50),
            'image_url' => fake()->imageUrl(),
        ];
    }
}
