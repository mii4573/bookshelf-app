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

        // 【修正】要件定義通り 201 Created が返ってくることをアサート
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

        // 【修正】要件定義通り 204 No Content（レスポンスボディなし）であることをアサート
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