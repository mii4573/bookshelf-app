<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Support\Arr;

class BookController extends Controller
{
    /**
     * 書籍一覧画面
     */
    public function index()
    {
        // with('genres') に加え、reviews の平均評価を取得する withAvg を追加
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
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
        // validated() から genres だけを除外して書籍を作成
        $validated = $request->validated();
        $book = $request->user()->books()->create(Arr::except($validated, ['genres']));

        // ジャンルの紐付け (中間テーブル)
        $book->genres()->sync($request->validated('genres'));

        return redirect()
            ->route('books.show', $book)
            ->with('status', '書籍を登録しました');
    }

    /**
     * 書籍詳細画面
     */
    public function show(Book $book)
    {
        // レビューを新しい順（latest）にしつつ、関連データ（user, likedByUsers）も一緒にEager Load
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->latest()->with(['user', 'likedByUsers']);
            },
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
        $validated = $request->validated();
        $book->update(Arr::except($validated, ['genres']));
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
