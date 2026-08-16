<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\BookStoreRequest;
use App\Http\Requests\Api\V1\BookUpdateRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

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

        if (!$book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        return new BookResource($book);
    }

    /**
     * ISBN指定による書籍検索 API
     */
    public function searchIsbn(string $isbn)
    {
        if (!ctype_digit($isbn) || strlen($isbn) !== 13) {
            return response()->json([
                'message' => 'ISBNは整数で入力してください。',
            ], 422);
        }

        try {
            // 1. OpenBD API の呼び出し
            $openBdResponse = Http::get("https://api.openbd.jp/v1/get?isbn={$isbn}");

            if ($openBdResponse->failed()) {
                return response()->json([
                    'message' => '外部APIとの通信に失敗しました。',
                ], 500);
            }

            $openBdData = $openBdResponse->json();

            if (!empty($openBdData) && isset($openBdData[0]) && $openBdData[0] !== null) {
                $summary = $openBdData[0]['summary'] ?? [];

                // YYYYMMDD -> YYYY-MM-DD に整形
                $pubdate = $summary['pubdate'] ?? '';
                if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $pubdate, $m)) {
                    $publishedDate = "{$m[1]}-{$m[2]}-{$m[3]}";
                } else {
                    $publishedDate = $pubdate;
                }

                return response()->json([
                    'title'          => $summary['title'] ?? '',
                    'author'         => $summary['author'] ?? '',
                    'published_date' => $publishedDate,
                    'description'    => $summary['description'] ?? '',
                ], 200);
            }

            // 2. Google Books API の呼び出し
            $googleResponse = Http::get("https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}");

            if ($googleResponse->failed()) {
                return response()->json([
                    'message' => '外部APIとの通信に失敗しました。',
                ], 500);
            }

            $googleData = $googleResponse->json();

            if (!empty($googleData['items'])) {
                $volumeInfo = $googleData['items'][0]['volumeInfo'] ?? [];
                $authors = isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '';

                return response()->json([
                    'title'          => $volumeInfo['title'] ?? '',
                    'author'         => $authors,
                    'published_date' => $volumeInfo['publishedDate'] ?? '',
                    'description'    => $volumeInfo['description'] ?? '',
                ], 200);
            }

            return response()->json([
                'message' => '該当する書籍情報が見つかりませんでした。',
            ], 404);

        } catch (\Exception $e) {
            Log::error('ISBN Search Error: ' . $e->getMessage());
            return response()->json([
                'message' => '外部APIとの通信に失敗しました。',
            ], 500);
        }
    }

    /**
     * 書籍登録 API
     */
    public function store(BookStoreRequest $request)
    {
        $this->authorize('create', Book::class);

        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        if (empty($validated['image_url'])) {
            $validated['image_url'] = 'https://placehold.co/400x600?text=No+Image';
        }

        $book = DB::transaction(function () use ($request, $validated) {
            $book = Book::create($validated);
            if ($request->has('genres')) {
                $book->genres()->sync($request->genres);
            }

            return $book;
        });

        $book->load('genres')
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

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

        if (!$book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

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

        if (!$book) {
            return response()->json([
                'message' => '書籍が見つかりません',
            ], 404);
        }

        $this->authorize('delete', $book);
        $book->delete();

        return response()->noContent();
    }
}