<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ユーザーが登録した書籍（1対多）
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    // ユーザーが投稿したレビュー（1対多）
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // ユーザーがお気に入り登録した書籍（多対多）
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')->withTimestamps();
    }

    // ユーザーが「いいね」したレビュー（多対多）
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_like')->withTimestamps();
    }
}
