<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証（ゲスト）ユーザーがお気に入りトグルを実行するとログイン画面にリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_toggling_favorite(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));
    }

    /**
     * 未認証（ゲスト）ユーザーがお気に入り一覧にアクセスするとログイン画面にリダイレクトされる
     */
    public function test_guest_is_redirected_to_login_when_accessing_favorites_index(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }
    
    /**
     * 要件: お気に入り登録・解除ができる (DB更新)
     */
    public function test_user_can_toggle_favorite_status(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 1. お気に入り登録 (トグルON)
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 2. お気に入り解除 (トグルOFF)
        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect();
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * 要件: お気に入り一覧が表示される / お気に入り書籍のみ表示
     */
    public function test_user_can_view_favorite_books_index(): void
    {
        $user = User::factory()->create();
        
        // ユーザーがお気に入りに登録した本
        $favoritedBook = Book::factory()->create(['title' => 'お気に入りの本']);
        $user->favorites()->attach($favoritedBook->id);

        // ユーザーがお気に入り登録していない本
        $otherBook = Book::factory()->create(['title' => '登録していない本']);

        // 一覧画面を開く
        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        
        // お気に入り登録した本が表示され、登録していない本が表示されていないことを検証
        $response->assertSee('お気に入りの本');
        $response->assertDontSee('登録していない本');
    }
}
