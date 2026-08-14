<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        // 評価別（1〜5）の簡潔な日本語コメントテンプレート
        $comments = [
            1 => '期待していた内容とは少し異なっていました。',
            2 => '少し内容が難しく、自分にはあまり合いませんでした。',
            3 => '内容は良かったですが、もう少し具体例があると助かります。',
            4 => 'とても読みやすく、大変勉強になりました。おすすめです。',
            5 => '非常に素晴らしい内容でした！何度も読み返したい一冊です。',
        ];

        foreach ($books as $book) {
            // 各書籍に2〜4件のレビューをランダムに割り当て
            $targetCount = rand(2, 4);

            // 投稿者を重複しないようにランダム選出
            $selectedUsers = $users->random(min($targetCount, $users->count()));

            foreach ($selectedUsers as $user) {
                // 評価を1〜5の全範囲でランダム化
                $rating = rand(1, 5);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating'  => $rating,
                    'comment' => $comments[$rating],
                ]);
            }
        }
    }
}