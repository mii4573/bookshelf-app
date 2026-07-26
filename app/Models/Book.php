<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     *  キャストの設定
     */
    protected $casts = [
        'published_date' => 'date:Y-m-d',
    ];

    // 登録したユーザー（多対1）
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 書籍に紐づくジャンル（多対多）
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre');
    }

    // 書籍に寄せられたレビュー（1対多）
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // この書籍をお気に入り登録しているユーザー（多対多）
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * 指定したユーザーがこの書籍をお気に入り登録しているか判定
     */
    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // 既に Eager Loading されている場合はメモリ上で判定（N+1対策）、なければDB問い合わせ
        return $this->relationLoaded('favoritedByUsers')
            ? $this->favoritedByUsers->contains('id', $user->id)
            : $this->favoritedByUsers()->where('user_id', $user->id)->exists();
    }
}
