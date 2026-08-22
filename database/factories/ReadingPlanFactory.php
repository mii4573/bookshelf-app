<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    protected $model = ReadingPlan::class;

    /**
     * デフォルトの定義
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'target_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'status' => 'in_progress',
        ];
    }

    // --- 相対日付状態（State） ---

    /** 3日前 (通知対象) */
    public function threeDaysBefore(): static
    {
        return $this->state(fn () => [
            'target_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
        ]);
    }

    /** 当日 (通知対象) */
    public function onDueDate(): static
    {
        return $this->state(fn () => [
            'target_date' => Carbon::today()->format('Y-m-d'),
            'status' => 'in_progress',
        ]);
    }

    /** 3日後/超過 (通知対象) */
    public function threeDaysAfter(): static
    {
        return $this->state(fn () => [
            'target_date' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'status' => 'in_progress',
        ]);
    }

    /** 完了済み (発火しないパターン確認用) */
    public function completed(): static
    {
        return $this->state(fn () => [
            'target_date' => Carbon::today()->subDays(1)->format('Y-m-d'),
            'status' => 'completed',
        ]);
    }
}
