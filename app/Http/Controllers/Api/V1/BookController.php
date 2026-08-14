<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\BookStoreRequest;
use App\Http\Requests\Api\V1\BookUpdateRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧取得 API
     */
    public function index(BookIndexRequest $request)
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // キーワード検索 (タイトルまたは著者)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // ジャンル絞り込み
        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        // ページネーション
        $perPage = $request->get('per_page', 20);
        $books = $query->latest('id')->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * 書籍詳細取得 API
     */
    public function show(string $id)
    {
        $book = Book::with(['genres', 'reviews.user'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->find($id);

        if (! $book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        return new BookResource($book);
    }

    /**
     * 書籍登録 API
     */
    public function store(BookStoreRequest $request)
    {
        // 1. Policy 認可チェック（認証済みか）
        $this->authorize('create', Book::class);

        $validated = $request->validated();

        // ログイン中ユーザーの ID を設定
        $validated['user_id'] = $request->user()->id;

        // 【画像対策】image_url が空の場合はデフォルト画像URLを補完
        if (empty($validated['image_url'])) {
            $validated['image_url'] = 'https://placehold.co/400x600?text=No+Image';
        }

        // トランザクション処理（DB登録＆ジャンル紐付け）
        $book = DB::transaction(function () use ($request, $validated) {
            $book = Book::create($validated);
            if ($request->has('genres')) {
                $book->genres()->sync($request->genres);
            }

            return $book;
        });

        // リレーション＆追加情報のロード（トランザクション外で実行）
        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        // 要件: 201ステータスと登録情報を返し、成功メッセージは含めない
        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍更新 API
     */
    public function update(BookUpdateRequest $request, string $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        // 2. Policy 認可チェック（本人以外の更新をブロック）
        $this->authorize('update', $book);
        
        $validated = $request->validated();

        DB::transaction(function () use ($request, $book, $validated) {
            $book->update($validated);
            if ($request->has('genres')) {
                $book->genres()->sync($request->genres);
            }
        });

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍削除 API
     */
    public function destroy(string $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        // 3. Policy 認可チェック（本人以外の削除をブロック）
        $this->authorize('delete', $book);

        $book->delete();

        // 要件: 204 No Content を返し、レスポンスボディは返さない
        return response()->noContent();
    }
}
