<?php

namespace App\Http\Controllers;

use App\Models\Book;

class RankingController extends Controller
{
    public function index()
    {
        $rankedBooks = Book::query()
            // 1. レビューが存在する書籍のみに絞り込み（レビューなしを除外）
            ->has('reviews')
            // 2. レビューの rating の平均値を 'reviews_avg_rating' として自動算出
            ->withAvg('reviews', 'rating')
            // 3. レビュー件数も取得（Bladeで $book->reviews_count を使用しているため必須）
            ->withCount('reviews')
            // 4. N+1対策: ビューで参照するリレーションを事前読み込み
            ->with(['genres'])
            // 5. 平均評価の降順（高い順）でソート
            ->orderByDesc('reviews_avg_rating')
            // 6. TOP 10件を取得
            ->take(10)
            ->get();

        // Blade側の変数名 $rankedBooks に合わせて渡す
        return view('ranking.index', compact('rankedBooks'));
    }
}
