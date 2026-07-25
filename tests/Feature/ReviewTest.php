<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 【Feature】レビュー投稿: レビューを投稿できる
     */
    public function test_user_can_post_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 5,
                'comment' => 'とても素晴らしい本でした！',
            ]);

        // 投稿後に直前の画面へリダイレクトされ、データベースに保存されていること
        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => 'とても素晴らしい本でした！',
        ]);
    }

    /**
     * 【Feature】レビュー投稿: 評価未選択では投稿できない
     */
    public function test_cannot_post_review_without_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => null, // 評価未選択
                'comment' => 'コメントのみ記述',
            ]);

        // セッションエラー（rating）が発生してデータベースに保存されないこと
        $response->assertSessionHasErrors(['rating']);
        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * 【Feature】レビュー投稿: コメント未入力では投稿できない
     * （※ StoreReviewRequest で comment が required の場合を想定）
     */
    public function test_cannot_post_review_without_comment(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'rating' => 4,
                'comment' => '', // コメント未入力
            ]);

        // セッションエラー（comment）が発生してデータベースに保存されないこと
        $response->assertSessionHasErrors(['comment']);
        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * 【Feature】レビュー編集: 投稿者本人は編集できる
     */
    public function test_author_can_update_own_review(): void
    {
        $author = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $author->id,
            'rating' => 3,
            'comment' => '編集前のコメント',
        ]);

        // 編集画面にアクセスできること
        $this->actingAs($author)
            ->get(route('reviews.edit', $review))
            ->assertStatus(200);

        // レビュー更新リクエスト
        $response = $this->actingAs($author)
            ->put(route('reviews.update', $review), [
                'rating' => 5,
                'comment' => '編集後のコメント',
            ]);

        // 詳細画面へリダイレクトされ、DBが更新されていること
        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '編集後のコメント',
        ]);
    }

    /**
     * 【Feature】レビュー編集: 投稿者以外は編集できない
     */
    public function test_non_author_cannot_update_other_review(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $author->id,
            'rating' => 3,
            'comment' => '元コメント',
        ]);

        // 他人が編集画面にアクセスした場合 403 Forbidden になること
        $this->actingAs($otherUser)
            ->get(route('reviews.edit', $review))
            ->assertStatus(403);

        // 他人が更新処理を試みた場合 403 Forbidden になり、DBが更新されないこと
        $response = $this->actingAs($otherUser)
            ->put(route('reviews.update', $review), [
                'rating' => 1,
                'comment' => '勝手に変更',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元コメント',
        ]);
    }

    /**
     * 【Feature】レビュー削除: 投稿者本人は削除できる
     */
    public function test_author_can_delete_own_review(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $response = $this->actingAs($author)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect();
        // DBから削除されていること
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    /**
     * 【Feature】レビュー削除: 投稿者以外は削除できない
     */
    public function test_non_author_cannot_delete_other_review(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $author->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        // 403 Forbidden になり、DBに残り続けること
        $response->assertStatus(403);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}
