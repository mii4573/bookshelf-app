<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 一覧表示の権限判定（ゲスト閲覧可）
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 詳細表示の権限判定（ゲスト閲覧可）
     */
    public function view(User $user, Book $book): bool
    {
        return true;
    }

    /**
     * 新規登録の権限判定（認証ユーザーのみ）
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 更新の権限判定（作成者本人＋認証ユーザーのみ
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 削除の権限判定（作成者本人＋認証ユーザーのみ）
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
