<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * ステータスの日本語ラベルを取得する
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '未着手',
            self::InProgress => '読書中',
            self::Completed => '読了',
            self::Expired => '期限切れ',
        };
    }

    /**
     * ステータスに応じたTailwind CSSのバッジ用クラスを取得する
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-100 text-yellow-800',
            self::InProgress => 'bg-blue-100 text-blue-800',
            self::Completed => 'bg-green-100 text-green-800',
            self::Expired => 'bg-red-100 text-red-800',
        };
    }
}
