<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧の表示
     */
    public function index(Request $request)
    {
        $books = $request->user()
            ->favorites()
            ->with('user')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入り登録・解除のトグル処理
     */
    public function store(Request $request, Book $book): RedirectResponse
    {
        // toggle を使うと、登録済みなら削除、未登録なら追加を自動で行ってくれます
        $request->user()->favorites()->toggle($book->id);

        return back();
    }
}
