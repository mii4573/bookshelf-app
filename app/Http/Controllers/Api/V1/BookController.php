<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\BookStoreRequest;
use App\Http\Requests\Api\V1\BookUpdateRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $perPage = $request->get('per_page', 10);
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

        if (!$book) {
            return response()->json([
                'message' => '書籍が見つかりません'
            ], 404);
        }

        return new BookResource($book);
    }

    /**
     * 書籍登録 API
     */
    public function store(BookStoreRequest $request)
    {
        $book = DB::transaction(function () use ($request) {
            $book = Book::create($request->validated());
            if ($request->has('genres')) {
                $book->genres()->sync($request->genres);
            }
            return $book;
        });

        $book->load('genres')
             ->loadAvg('reviews', 'rating')
             ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍更新 API
     */
    public function update(BookUpdateRequest $request, string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => '書籍が見つかりません'
            ], 404);
        }

        DB::transaction(function () use ($request, $book) {
            $book->update($request->validated());
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

        if (!$book) {
            return response()->json([
                'message' => '書籍が見つかりません'
            ], 404);
        }

        $book->delete();

        return response()->json([
            'message' => '書籍を削除しました'
        ], 204);
    }
}
