<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * ユーザーの読書データを集計し、レポート画面を表示する
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Eager Loading による N+1 問題の回避
        $reviews = $user->reviews()->with(['book.genres'])->get();

        // 1〜5の評価のみを抽出
        $validReviews = $reviews->filter(fn ($review) => $review->rating >= 1 && $review->rating <= 5);

        // 2. 基本サマリーの集計
        $stats = [
            'summary' => [
                'total_reviews' => $validReviews->count(),
                'books_read' => $validReviews->unique('book_id')->count(),
                'average_rating' => $validReviews->count() > 0 ? round($validReviews->avg('rating'), 1) : 0,
            ],
            'rating_distribution' => $this->getRatingDistribution($validReviews),
            'top_rated_books' => $this->getTopRatedBooks($validReviews),
            'genre_ratings' => $this->getGenreRatings($validReviews),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * 評価分布 (1〜5星) を集計する
     */
    private function getRatingDistribution(Collection $reviews): Collection
    {
        return collect(range(1, 5))->mapWithKeys(function ($rating) use ($reviews) {
            return [$rating => $reviews->where('rating', $rating)->count()];
        });
    }

    /**
     * 高評価書籍 TOP5 を取得する
     */
    private function getTopRatedBooks(Collection $reviews): Collection
    {
        return $reviews->where('rating', '>=', 4)
            ->sortByDesc('rating')
            ->take(5)
            ->map(fn ($review) => [
                'id' => $review->book->id ?? null,
                'title' => $review->book->title ?? '',
                'author' => $review->book->author ?? '',
                'rating' => $review->rating,
            ])
            ->values();
    }

    /**
     * ジャンル別の評価傾向を集計する
     */
    private function getGenreRatings(Collection $reviews): Collection
    {
        return $reviews->flatMap(fn ($review) => $review->book->genres->map(fn ($genre) => [
            'id' => $genre->id,
            'name' => $genre->name,
            'rating' => $review->rating,
        ]))
            ->groupBy('id')
            ->map(fn ($group) => [
                'id' => $group->first()['id'],
                'name' => $group->first()['name'],
                'count' => $group->count(),
                'average_rating' => round($group->avg('rating'), 1),
            ])
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();
    }
}
