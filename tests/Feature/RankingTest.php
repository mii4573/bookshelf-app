<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 要件: ゲスト（未ログイン）でもランキング画面にアクセスできる
     */
    public function test_guest_can_access_ranking_page(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
    }

    /**
     * 要件: レビュー平均評価順（降順）に表示され、レビューのない書籍は一覧に含まれない
     */
    public function test_ranking_displays_books_in_descending_order_of_average_rating_and_excludes_books_without_reviews(): void
    {
        $user = User::factory()->create();

        // 1. 平均評価 3.0 の書籍
        $bookMid = Book::factory()->create(['title' => '中評価の書籍']);
        Review::factory()->create(['book_id' => $bookMid->id, 'user_id' => $user->id, 'rating' => 3]);

        // 2. 平均評価 5.0 の書籍
        $bookHigh = Book::factory()->create(['title' => '高評価の書籍']);
        Review::factory()->create(['book_id' => $bookHigh->id, 'user_id' => $user->id, 'rating' => 5]);

        // 3. レビューが 0 件の書籍
        $bookNoReview = Book::factory()->create(['title' => 'レビューなし書籍']);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        // 降順表示の検証（高評価の書籍 -> 中評価の書籍 の順でHTML内に登場するか）
        $response->assertSeeInOrder([
            '高評価の書籍',
            '中評価の書籍',
        ]);

        // レビューのない書籍が画面に含まれていないことの検証
        $response->assertDontSee('レビューなし書籍');
    }

    /**
     * 要件: TOP 10件までのみ表示される
     */
    public function test_ranking_displays_maximum_ten_books(): void
    {
        $user = User::factory()->create();

        // レビュー付きの書籍を11件作成
        $books = Book::factory()->count(11)->create();
        foreach ($books as $index => $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'user_id' => $user->id,
                'rating' => 5,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        // ビューに渡された $rankedBooks が 10件 であることを検証
        $response->assertViewHas('rankedBooks', function ($rankedBooks) {
            return $rankedBooks->count() === 10;
        });
    }
}
