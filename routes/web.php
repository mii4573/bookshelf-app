<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- ゲスト（未ログイン）アクセス可能 ---
Route::get('/', fn () => redirect()->route('books.index'));
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

// --- ログイン必須エリア ---
Route::middleware(['auth'])->group(function () {

    // 書籍管理
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::match(['put', 'patch'], '/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // レビュー管理 (※未実装のコントローラーは仮定義)
    Route::post('/books/{book}/reviews', class_exists(ReviewController::class) ? [ReviewController::class, 'store'] : fn () => '')->name('reviews.store');
    Route::get('/reviews/{review}/edit', class_exists(ReviewController::class) ? [ReviewController::class, 'edit'] : fn () => '')->name('reviews.edit');
    Route::match(['put', 'patch'], '/reviews/{review}', class_exists(ReviewController::class) ? [ReviewController::class, 'update'] : fn () => '')->name('reviews.update');
    Route::delete('/reviews/{review}', class_exists(ReviewController::class) ? [ReviewController::class, 'destroy'] : fn () => '')->name('reviews.destroy');

    // ジャンル管理
    Route::get('/genres', class_exists(GenreController::class) ? [GenreController::class, 'index'] : fn () => '')->name('genres.index');
    Route::get('/genres/create', class_exists(GenreController::class) ? [GenreController::class, 'create'] : fn () => '')->name('genres.create');
    Route::post('/genres', class_exists(GenreController::class) ? [GenreController::class, 'store'] : fn () => '')->name('genres.store');
    Route::get('/genres/{genre}/edit', class_exists(GenreController::class) ? [GenreController::class, 'edit'] : fn () => '')->name('genres.edit');
    Route::match(['put', 'patch'], '/genres/{genre}', class_exists(GenreController::class) ? [GenreController::class, 'update'] : fn () => '')->name('genres.update');
    Route::delete('/genres/{genre}', class_exists(GenreController::class) ? [GenreController::class, 'destroy'] : fn () => '')->name('genres.destroy');

    // お気に入り・いいね機能
    Route::get('/favorites', class_exists(FavoriteController::class) ? [FavoriteController::class, 'index'] : fn () => '')->name('favorites.index');
    Route::post('/books/{book}/favorites', class_exists(FavoriteController::class) ? [FavoriteController::class, 'toggle'] : fn () => '')->name('favorites.toggle');
    Route::post('/reviews/{review}/like', class_exists(ReviewLikeController::class) ? [ReviewLikeController::class, 'toggle'] : fn () => '')->name('reviews.like');
});

// --- 詳細画面（/books/create などとのURL衝突を防ぐため一番下に配置） ---
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
