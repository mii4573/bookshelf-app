<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 公開API一覧取得_書籍一覧APIを取得できる()
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'title', 'author']
                     ]
                 ]);
    }

    /** @test */
    public function 公開API詳細取得_書籍詳細APIを取得できる()
    {
        $book = Book::factory()->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk()
                 ->assertJsonPath('data.id', $book->id);
    }

    /** @test */
    public function 公開API詳細取得_存在しないIDは404を返す()
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function 未認証ユーザーは登録・更新・削除できない()
    {
        $book = Book::factory()->create();

        // 未認証での登録試行 -> 401
        $this->postJson('/api/v1/books', [
            'title' => '未認証テスト',
        ])->assertUnauthorized();

        // 未認証での更新試行 -> 401
        $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '未認証更新',
        ])->assertUnauthorized();

        // 未認証での削除試行 -> 401
        $this->deleteJson("/api/v1/books/{$book->id}")
             ->assertUnauthorized();
    }

    /** @test */
    public function 他人の書籍は更新できない()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();

        // バリデーションを通すため、必要そうなパラメータをすべて網羅して送信
        $response = $this->actingAs($otherUser, 'sanctum')
             ->putJson("/api/v1/books/{$book->id}", [
                 'user_id' => $owner->id,
                 'title' => '勝手に更新',
                 'author' => '勝手な著者',
                 'isbn' => '9784123456789',
                 'published_date' => '2026-01-01',
                 'description' => 'テスト説明',
                 'genres' => [$genre->id],
             ]);

        $response->assertForbidden();
    }

    /** @test */
    public function 他人の書籍は削除できない()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        // 削除はパラメータ不要のため、純粋に 403 Forbidden が返るか検証
        $response = $this->actingAs($otherUser, 'sanctum')
             ->deleteJson("/api/v1/books/{$book->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function 公開API登録_書籍を登録できる()
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'API新規書籍',
            'author' => 'テスト著者',
            'isbn' => '9784123456789',
            'published_date' => '2026-01-01',
            'description' => 'APIからの登録テストです。',
            'genres' => [$genre->id],
        ]);

        $response->assertCreated(); 
        $this->assertDatabaseHas('books', ['title' => 'API新規書籍']);
    }

    /** @test */
    public function 公開API更新_書籍を更新できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9784987654321',
            'published_date' => '2026-01-01',
            'description' => '更新後の説明文です。',
            'genres' => [$genre->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
        ]);
    }

    /** @test */
    public function 公開API削除_書籍を削除できる()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent(); 
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /** @test */
    public function 公開API削除_存在しないIDは404を返す()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/books/99999');

        $response->assertNotFound();
    }
}