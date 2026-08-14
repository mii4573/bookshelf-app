<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 主要ユーザー（山田太郎）の取得（UserSeederで作られた既存ユーザーを使う）
        $mainUser = User::where('name', '山田太郎')->first() ?? User::first();
        
        // 2. 他ユーザーの取得（認可・分離テスト用）
        $otherUsers = User::where('id', '!=', $mainUser->id)->get();

        // 3. BookSeeder で投入済みの本を取得
        $books = Book::all();

        // -------------------------------------------------------------
        // シナリオ A: 山田太郎に主要な通知・計画パターンを集約
        // -------------------------------------------------------------
        
        // パターン1: 3日前 (リマインド通知対象)
        $plan3DaysBefore = ReadingPlan::factory()->threeDaysBefore()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books->get(0)?->id ?? Book::factory()->create()->id,
        ]);
        $mainUser->notify(new ReadingPlanNotification($plan3DaysBefore, 'three_days_before'));

        // パターン2: 当日 (期日通知対象)
        $planOnDue = ReadingPlan::factory()->onDueDate()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books->get(1)?->id ?? Book::factory()->create()->id,
        ]);
        $mainUser->notify(new ReadingPlanNotification($planOnDue, 'on_due_date'));

        // パターン3: 3日後/超過 (超過通知対象)
        $plan3DaysAfter = ReadingPlan::factory()->threeDaysAfter()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books->get(2)?->id ?? Book::factory()->create()->id,
        ]);
        $mainUser->notify(new ReadingPlanNotification($plan3DaysAfter, 'three_days_after'));

        // パターン4: 完了済み (通知発火しないパターンの検証用)
        ReadingPlan::factory()->completed()->create([
            'user_id' => $mainUser->id,
            'book_id' => $books->get(3)?->id ?? Book::factory()->create()->id,
        ]);

        // -------------------------------------------------------------
        // シナリオ B: 複数ユーザーデータの配置（他人の計画が見えないか認可検証用）
        // -------------------------------------------------------------
        foreach ($otherUsers as $otherUser) {
            $otherPlan = ReadingPlan::factory()->onDueDate()->create([
                'user_id' => $otherUser->id,
                'book_id' => $books->random()->id,
            ]);
            // 他ユーザーにも通知を作成（ログインユーザーごとの通知分離をテストするため）
            $otherUser->notify(new ReadingPlanNotification($otherPlan, 'on_due_date'));
        }
    }
}