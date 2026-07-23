<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReviewPolicy
{
    /**
     * 新規レビュー作成の権限判定（認証ユーザーなら誰でも作成可能）
     */
    public function viewAny(User $user): bool
    {
       return true;
    } 

    /**
     * レビュー更新の権限判定（投稿者本人＋認証ユーザーのみ）
     */
    public function view(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * レビュー削除の権限判定（投稿者本人＋認証ユーザーのみ）
     */
    public function create(User $user): bool
    {
        return $user->id === $review->user_id;
    }

    
}
