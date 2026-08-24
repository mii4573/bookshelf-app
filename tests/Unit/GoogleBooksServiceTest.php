<?php

namespace Tests\Unit;

use App\Services\GoogleBooksService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksServiceTest extends TestCase
{
    public function test_it_returns_book_data_from_google_books_when_successful()
    {
        // Google Books APIのレスポンスをモック
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Google本タイトル',
                            'authors' => ['Google著者'],
                            'publisher' => 'テスト出版社',
                            'publishedDate' => '2023',
                            'description' => '説明文',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new GoogleBooksService;
        $result = $service->searchByIsbn('9784297123456');

        $this->assertNotNull($result);
        $this->assertEquals('Google本タイトル', $result['title']);
        $this->assertEquals('Google著者', $result['author']);
    }

    public function test_it_falls_back_to_openbd_when_google_books_fails()
    {
        // Google Booksはヒットせず、openBDがヒットするケース
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
            ], 200),
            'api.openbd.jp/v1/get*' => Http::response([[
                'summary' => [
                    'title' => 'openBD本タイトル',
                    'author' => 'openBD著者',
                    'publisher' => 'openBD出版社',
                    'pubdate' => '20230101',
                    'cover' => 'https://example.com/cover.jpg',
                ],
            ]], 200),
        ]);

        $service = new GoogleBooksService;
        $result = $service->searchByIsbn('9784297123456');

        $this->assertNotNull($result);
        $this->assertEquals('openBD本タイトル', $result['title']);
        $this->assertEquals('openBD著者', $result['author']);
    }

    public function test_it_returns_null_when_book_not_found_in_any_api()
    {
        // どちらのAPIでも見つからないケース
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response(['totalItems' => 0], 200),
            'api.openbd.jp/v1/get*' => Http::response([null], 200),
        ]);

        $service = new GoogleBooksService;
        $result = $service->searchByIsbn('0000000000000');

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_api_throws_exception()
    {
        // API通信中に例外（エラー）が発生するケースを模擬
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        $service = new GoogleBooksService;
        $result = $service->searchByIsbn('9784297123456');

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_isbn_is_empty()
    {
        $service = new GoogleBooksService;
        // 記号や空文字を渡してクリーニング後に空になるケース
        $result = $service->searchByIsbn('---');

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_google_books_fails_with_error_status()
    {
        // Google Books APIが 500 などのエラーを返すケース
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response(null, 500),
            'api.openbd.jp/v1/get*' => Http::response(null, 500),
        ]);

        $service = new GoogleBooksService;
        $result = $service->searchByIsbn('9784297123456');

        $this->assertNull($result);
    }

    public function test_it_handles_openbd_exception()
    {
        // openBD API通信中に例外が発生するケース
        Http::fake([
            'www.googleapis.com/books/v1/volumes*' => Http::response(['totalItems' => 0], 200),
            'api.openbd.jp/v1/get*' => function () {
                throw new \Exception('OpenBD Network error');
            },
        ]);

        $service = new GoogleBooksService;
        $result = $service->searchByIsbn('9784297123456');

        $this->assertNull($result);
    }
}
