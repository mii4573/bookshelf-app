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

    // レビュー管理
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::match(['put', 'patch'], '/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // ジャンル管理
    Route::get('/genres', class_exists(GenreController::class) ? [GenreController::class, 'index'] : fn () => '')->name('genres.index');
    Route::get('/genres/create', class_exists(GenreController::class) ? [GenreController::class, 'create'] : fn () => '')->name('genres.create');
    Route::post('/genres', class_exists(GenreController::class) ? [GenreController::class, 'store'] : fn () => '')->name('genres.store');
    Route::get('/genres/{genre}/edit', class_exists(GenreController::class) ? [GenreController::class, 'edit'] : fn () => '')->name('genres.edit');
    Route::match(['put', 'patch'], '/genres/{genre}', class_exists(GenreController::class) ? [GenreController::class, 'update'] : fn () => '')->name('genres.update');
    Route::delete('/genres/{genre}', class_exists(GenreController::class) ? [GenreController::class, 'destroy'] : fn () => '')->name('genres.destroy');

    // お気に入り関連
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorite', [FavoriteController::class, 'store'])->name('favorites.toggle');

    // レビューのいいね関連
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'store'])->name('reviews.like');
});

// --- 詳細画面（/books/create などとのURL衝突を防ぐため一番下に配置） ---
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
