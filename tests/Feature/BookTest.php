<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 書籍一覧アクセス（ログイン済み）
     */
    public function test_user_can_access_books_index(): void
    {
        $user = User::factory()->create();

        // 画面のルーティング未定義エラー(Blade描画エラー)を避けるため
        // ビュー描画処理をスキップしてルートのレスポンスだけチェックする指定も可能です
        $this->withoutExceptionHandling();

        $response = $this->actingAs($user)->get(route('books.index'));

        $response->assertOk();
    }

    /**
     * 2. 書籍の新規登録（正常系）
     */
    public function test_user_can_store_book(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $data = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'description' => 'テストの概要です',
            'image_url' => 'https://example.com/test.jpg',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)->post(route('books.store'), $data);

        // 1. DBに書籍が登録されたか
        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
        ]);

        $book = Book::where('isbn', '9784123456789')->first();

        // 2. 中間テーブルにジャンルが紐付いたか
        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }

        // 3. 詳細画面へリダイレクトされたか
        $response->assertRedirect(route('books.show', $book));
    }

    /**
     * 3. 書籍の新規登録（異常系：バリデーションエラー）
     */
    public function test_store_book_validation_fails(): void
    {
        $user = User::factory()->create();

        // 空のデータ（必須項目なし）
        $data = [];

        $response = $this->actingAs($user)->post(route('books.store'), $data);

        // セッションエラーが存在するか
        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);

        // DBに書籍が登録されていないか
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 4. 書籍詳細画面へのアクセス
     */
    public function test_user_can_view_book_show(): void
    {
        $this->withoutExceptionHandling(); // ← エラー確認のため1行追加！
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertOk();
    }

    /**
     * 5. 書籍の更新（正常系：所有者本人）
     */
    public function test_owner_can_update_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $newGenres = Genre::factory()->count(2)->create();

        $updateData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => $book->isbn, // 自身のISBNはRule::uniqueで許可される
            'published_date' => '2026-05-01',
            'description' => '更新された概要',
            'genres' => $newGenres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)->put(route('books.update', $book), $updateData);

        // DBが更新されたか
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'published_date' => '2026-05-01',
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    /**
     * 6. 書籍の更新（認可エラー：他人の書籍）
     */
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $updateData = [
            'title' => '勝手に更新タイトル',
            'author' => '著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-01-01',
            'genres' => [Genre::factory()->create()->id],
        ];

        // 他のユーザーでリクエストを実行
        $response = $this->actingAs($otherUser)->put(route('books.update', $book), $updateData);

        // Policyにより 403 Forbidden が返されるか
        $response->assertStatus(403);
    }

    /**
     * 7. 書籍の削除（正常系：所有者本人）
     */
    public function test_owner_can_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('books.destroy', $book));

        // DBから削除されたか
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $response->assertRedirect(route('books.index'));
    }
}
