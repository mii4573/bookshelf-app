<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * 書籍一覧画面
     */
    public function index()
    {
        // N+1問題を防止するために with('genres') を指定
        $books = Book::with('genres')
            ->latest()
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録画面
     */
    public function create()
    {
        $genres = Genre::all();
        return view('books.create', compact('genres'));
    }

    /**
     * 書籍登録処理
     */
    public function store(StoreBookRequest $request)
    {
        // ログインユーザーのIDをセットして書籍作成
        $book = $request->user()->books()->create($request->validated());

        // ジャンルの紐付け (中間テーブル)
        $book->genres()->sync($request->validated('genres'));

        return redirect()
            ->route('books.show', $book)
            ->with('status', '書籍を登録しました');
    }

    /**
     * 書籍詳細画面.
     */
    public function show(Book $book)
    {
        // レビュー（投稿日の新しい順）とジャンルを Eager Load
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->latest();
            },
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面
     */
    public function edit(Book $book)
    {
        // Policy による認可チェック
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍更新処理
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        // 認可チェックは UpdateBookRequest 内の authorize() で自動実行されるため省略可
        $book->update($request->validated());
        $book->genres()->sync($request->validated('genres'));

        return redirect()
            ->route('books.show', $book)
            ->with('status', '書籍を更新しました');
    }

    /**
     * 書籍削除処理
     */
    public function destroy(Book $book)
    {
        // Policy による認可チェック
        $this->authorize('delete', $book);

        // 関連データ（ジャンル紐付け・レビュー・お気に入り）はDB制約やリレーションにより適切に処理
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('status', '書籍を削除しました');
    }
}
