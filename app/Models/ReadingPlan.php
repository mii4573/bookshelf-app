<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingPlan extends Model
{
    use HasFactory; // Factoryを使うために追加

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date', // マイグレーションのカラム名（due_date等）に合わせて調整してください
        'status',
    ];

    // ★ここを追加！ target_date を Carbon (日付オブジェクト) にキャストします
    protected $casts = [
        'target_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}