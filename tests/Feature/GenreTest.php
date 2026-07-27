<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // ログイン用ユーザーの準備
        $this->user = User::factory()->create();
    }

    /** @test */
    public function ログインユーザーはジャンル一覧を表示できる()
    {
        $response = $this->actingAs($this->user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
    }

    /** @test */
    public function 未認証ユーザーはログイン画面へリダイレクトされる()
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function ログインユーザーはジャンルを新規登録できる()
    {
        $response = $this->actingAs($this->user)->post(route('genres.store'), [
            'name' => 'SF・ファンタジー',
        ]);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを作成しました。');
        $this->assertDatabaseHas('genres', ['name' => 'SF・ファンタジー']);
    }

    /** @test */
    public function ジャンル名の未入力および重複登録はバリデーションエラーになる()
    {
        Genre::factory()->create(['name' => '既存ジャンル']);

        // 未入力チェック
        $responseEmpty = $this->actingAs($this->user)->post(route('genres.store'), [
            'name' => '',
        ]);
        $responseEmpty->assertSessionHasErrors(['name' => 'ジャンル名は必須です']);

        // 重複チェック
        $responseUnique = $this->actingAs($this->user)->post(route('genres.store'), [
            'name' => '既存ジャンル',
        ]);
        $responseUnique->assertSessionHasErrors(['name' => 'このジャンル名は既に使用されています']);
    }

    /** @test */
    public function ログインユーザーはジャンル名を更新できる()
    {
        $genre = Genre::factory()->create(['name' => '旧ジャンル名']);

        $response = $this->actingAs($this->user)->put(route('genres.update', $genre), [
            'name' => '新ジャンル名',
        ]);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '新ジャンル名']);
    }

    /** @test */
    public function ジャンル編集時に自分のジャンル名のまま更新してもバリデーションエラーにならない()
    {
        $genre = Genre::factory()->create(['name' => '既存ジャンル']);

        $response = $this->actingAs($this->user)->put(route('genres.update', $genre), [
            'name' => '既存ジャンル',
        ]);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');
    }

    /** @test */
    public function 書籍が紐付いていないジャンルは削除できる()
    {
        $genre = Genre::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    /** @test */
    public function 書籍が紐付いているジャンルは削除が拒否される()
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        // ジャンルと書籍を紐付け
        $genre->books()->attach($book->id);

        $response = $this->actingAs($this->user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('error', 'このジャンルは紐づく書籍があるため削除できません。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    /** @test */
    public function ジャンル詳細画面で紐づく書籍一覧が表示される()
    {
        $genre = Genre::factory()->create(['name' => '技術書']);
        $book = Book::factory()->create(['title' => 'Laravel入門']);
        $genre->books()->attach($book->id);

        $response = $this->actingAs($this->user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('Laravel入門');
    }
}
