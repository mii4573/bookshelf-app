<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;
use App\Models\Book;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        // 32件になるよう配分パターン（11冊に2〜4件ずつ配分）
        $comments = [
            5 => '非常に素晴らしい内容でした！何度も読み返したい一冊です。',
            4 => 'とても読みやすく、大変勉強になりました。おすすめです。',
            3 => '内容は良かったですが、もう少し具体例があると助かります。',
        ];

        $reviewCount = 0;
        foreach ($books as $index => $book) {
            // 書籍ごとに2〜4件割り当て（合計32件にする調整）
            $targetCount = ($index < 10) ? 3 : 2; // 10冊×3件 + 1冊×2件 = 32件

            foreach ($users->take($targetCount) as $user) {
                if ($reviewCount >= 32) break;

                $rating = rand(3, 5);
                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $comments[$rating],
                ]);

                $reviewCount++;
            }
        }
    }
}
