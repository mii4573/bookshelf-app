<?php

namespace Tests\Feature;


use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 要件: レビューいいね登録・解除ができる (DB更新)
     */
    public function test_user_can_toggle_review_like_status(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        // 1. いいね登録 (トグルON)
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseHas('review_like', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 2. いいね解除 (トグルOFF)
        $response = $this->actingAs($user)
            ->post(route('reviews.like', $review));

        $response->assertRedirect();
        $this->assertDatabaseMissing('review_like', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    /**
     * 要件: いいね数が正しく表示される (件数一致)
     */
    public function test_review_like_count_is_correctly_displayed(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);

        // 3人のユーザーがこのレビューに「いいね」を押す
        $likers = User::factory()->count(3)->create();
        foreach ($likers as $liker) {
            $review->likedByUsers()->attach($liker->id);
        }

        // 書籍詳細画面等でレビューといいね数が表示されるか検証
        $response = $this->actingAs($user)
            ->get(route('books.show', $book));

        $response->assertOk();
        
        // 画面上（HTML内）にいいね数の「3」が表示されているか検証
        $response->assertSee('3');
    }

    /**
     * 要件: 未認証（ゲスト）時→ /login にリダイレクト
     */
    public function test_guest_is_redirected_to_login_when_liking_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
    }
}
