<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_accurate_reading_report(): void
    {
        // 1. 準備: ユーザー、書籍、レビューを作成
        $user = User::factory()->create();
        $genre = Genre::create(['name' => '小説']);

        $book1 = Book::factory()->create(['title' => '高評価の本']);
        $book1->genres()->attach($genre);

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5, // ★5
        ]);

        $book2 = Book::factory()->create(['title' => '低評価の本']);
        Review::create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3, // ★3
        ]);

        // 2. 実行: 認証してレポート画面へアクセス
        $response = $this->actingAs($user)->get(route('reports.index'));

        // 3. 検証: 正しい値が計算されているか
        $response->assertStatus(200);

        // 基本統計の検証
        $response->assertSee('2'); // 総レビュー数
        $response->assertSee('4.0'); // 平均評価 (5+3)/2 = 4.0

        // 表示データの構造を検証（Viewに渡されたデータを確認）
        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 2 &&
                   $stats['summary']['average_rating'] === 4.0 &&
                   $stats['top_rated_books']->first()['title'] === '高評価の本';
        });
    }
}
