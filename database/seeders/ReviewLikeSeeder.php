<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = Review::all();

        foreach ($reviews as $review) {
            // 自分のレビュー投稿者を除外したユーザーリスト
            $otherUsers = User::where('id', '!=', $review->user_id)->get();

            // 0〜3人ランダムで選出
            $likeCount = rand(0, min(3, $otherUsers->count()));
            if ($likeCount > 0) {
                $likedUserIds = $otherUsers->random($likeCount)->pluck('id');
                $review->likedByUsers()->syncWithoutDetaching($likedUserIds);
            }
        }
    }
}
