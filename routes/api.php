<?php

use App\Http\Controllers\Api\V1\AuthController; // ※タスク2で作成します
use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Routes (認証不要: 閲覧のみ)
    |--------------------------------------------------------------------------
    */
    // トークン発行 API（ログイン）
    Route::post('/login', [AuthController::class, 'login']);

    // 書籍一覧・詳細取得
    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{book}', [BookController::class, 'show']);


    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Sanctum トークン認証必須)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        // ログアウト（トークン削除）
        Route::post('/logout', [AuthController::class, 'logout']);

        // ログイン中ユーザー情報の取得
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // 書籍の作成・更新・削除
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
    });

});