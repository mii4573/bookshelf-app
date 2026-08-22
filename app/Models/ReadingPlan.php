<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'status' => ReadingPlanStatus::class,
    ];

    // --- リレーション ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // --- 状態判定メソッド ---

    public function isCompleted(): bool
    {
        return $this->status === ReadingPlanStatus::Completed;
    }

    public function isExpired(): bool
    {
        // 読了していなくて、期日が過ぎていたら期限切れ
        return ! $this->isCompleted() && $this->target_date->isPast();
    }

    // --- クエリスコープ (Scopes) ---

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReadingPlanStatus::Pending);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', ReadingPlanStatus::InProgress);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ReadingPlanStatus::Completed);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', ReadingPlanStatus::Expired);
    }

    // --- アクセサ ---

    protected function targetDate(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value) : null,
        );
    }

    protected function completedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Carbon::parse($value) : null,
        );
    }
}
