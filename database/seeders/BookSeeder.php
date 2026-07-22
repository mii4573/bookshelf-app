<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\User;
use App\Models\Genre;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        $booksData = [
            [
                'title' => '吾輩は猫である', 'author' => '夏目漱石', 'isbn' => '9784101010014',
                'published_at' => '1905-01-01', 'genres' => ['小説'], 'num' => 1
            ],
            [
                'title' => '人を動かす', 'author' => 'D・カーネギー', 'isbn' => '9784422100524',
                'published_at' => '1936-10-01', 'genres' => ['ビジネス', '自己啓発'], 'num' => 2
            ],
            [
                'title' => 'リーダブルコード', 'author' => 'Dustin Boswell', 'isbn' => '9784873115658',
                'published_at' => '2012-06-23', 'genres' => ['技術書'], 'num' => 3
            ],
            [
                'title' => '7つの習慣', 'author' => 'スティーブン・R・コヴィー', 'isbn' => '9784863940246',
                'published_at' => '2013-08-30', 'genres' => ['ビジネス', '自己啓発'], 'num' => 4
            ],
            [
                'title' => '坊っちゃん', 'author' => '夏目漱石', 'isbn' => '9784101010021',
                'published_at' => '1906-04-01', 'genres' => ['小説'], 'num' => 5
            ],
            [
                'title' => 'サピエンス全史', 'author' => 'ユヴァル・ノア・ハラリ', 'isbn' => '9784309226712',
                'published_at' => '2016-09-08', 'genres' => ['歴史', '科学'], 'num' => 6
            ],
            [
                'title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '9784048930598',
                'published_at' => '2017-12-18', 'genres' => ['技術書'], 'num' => 7
            ],
            [
                'title' => '嫌われる勇気', 'author' => '岸見一郎・古賀史健', 'isbn' => '9784478025819',
                'published_at' => '2013-12-13', 'genres' => ['自己啓発'], 'num' => 8
            ],
            [
                'title' => '火花', 'author' => '又吉直樹', 'isbn' => '9784163902302',
                'published_at' => '2015-03-11', 'genres' => ['小説'], 'num' => 9
            ],
            [
                'title' => 'FACTFULNESS', 'author' => 'ハンス・ロスリング', 'isbn' => '9784822289607',
                'published_at' => '2019-01-11', 'genres' => ['ビジネス', '科学'], 'num' => 10
            ],
            [
                'title' => 'コンテナ物語', 'author' => 'マルク・レビンソン', 'isbn' => '9784822251468',
                'published_at' => '2007-01-18', 'genres' => ['ビジネス', '歴史'], 'num' => 11
            ],
        ];

        foreach ($booksData as $data) {
            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'user_id' => $user->id,
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'published_at' => $data['published_at'],
                    'description' => "『{$data['title']}』の概要説明文です。",
                    'image_url' => "https://placehold.co/200x300/e2e8f0/475569?text={$data['num']}",
                ]
            );

            // ジャンルの同期
            $genreIds = Genre::whereIn('name', $data['genres'])->pluck('id');
            $book->genres()->sync($genreIds);
        }
    }
}
