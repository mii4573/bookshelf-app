<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーションエラー：13桁以外のISBNが渡された場合は 422 を返す
     */
    public function test_search_isbn_validation_fails_for_invalid_length()
    {
        // 10桁のISBNを送信
        $response = $this->getJson('/api/v1/books/search-isbn/1234567890');

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'ISBNは整数で入力してください。',
                 ]);
    }

    /**
     * 正常系：openBD API で書籍が見つかった場合は 200 と書籍情報を返す
     */
    public function test_search_isbn_returns_data_from_openbd()
    {
        Http::fake([
            'https://api.openbd.jp/*' => Http::response([
                [
                    'summary' => [
                        'title'   => 'テスト書籍名',
                        'author'  => 'テスト著者',
                        'pubdate' => '20260101',
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/books/search-isbn/9784774193915');

        $response->assertStatus(200)
                 ->assertJson([
                     'title'          => 'テスト書籍名',
                     'author'         => 'テスト著者',
                     'published_date' => '2026-01-01',
                     'description'    => '',
                 ]);
    }

    /**
     * 正常系：openBDで見つからず Google Books API で見つかった場合は 200 を返す
     */
    public function test_search_isbn_returns_data_from_google_books_when_openbd_fails()
    {
        Http::fake([
            // openBD は空配列を返す
            'https://api.openbd.jp/*' => Http::response([], 200),
            // Google Books API はヒットした情報を返す
            'https://www.googleapis.com/*' => Http::response([
                'totalItems' => 1,
                'items'      => [
                    [
                        'volumeInfo' => [
                            'title'         => 'Google Books タイトル',
                            'authors'       => ['著者A', '著者B'],
                            'publishedDate' => '2025-10-10',
                            'description'   => '書籍の説明文',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/books/search-isbn/9784774193915');

        $response->assertStatus(200)
                 ->assertJson([
                     'title'          => 'Google Books タイトル',
                     'author'         => '著者A, 著者B',
                     'published_date' => '2025-10-10',
                     'description'    => '書籍の説明文',
                 ]);
    }

    /**
     * 異常系：どちらのAPIにも見つからない場合は 404 を返す
     */
    public function test_search_isbn_returns_404_when_book_not_found()
    {
        Http::fake([
            'https://api.openbd.jp/*'     => Http::response([], 200),
            'https://www.googleapis.com/*' => Http::response(['totalItems' => 0], 200),
        ]);

        $response = $this->getJson('/api/v1/books/search-isbn/9780000000000');

        $response->assertStatus(404)
                 ->assertJson([
                     'message' => '該当する書籍情報が見つかりませんでした。',
                 ]);
    }

    /**
     * 異常系：API通信エラーが発生した場合は 500 を返す
     */
    public function test_search_isbn_returns_500_on_api_failure()
    {
        Http::fake([
            'https://api.openbd.jp/*'     => Http::response([], 200),
            'https://www.googleapis.com/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/api/v1/books/search-isbn/9784774193915');

        $response->assertStatus(500)
                 ->assertJson([
                     'message' => '外部APIとの通信に失敗しました。',
                 ]);
    }
}