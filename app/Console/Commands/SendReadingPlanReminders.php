<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-reading-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期日が近づいたユーザーに通知を送信する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 例として「3日後」が目標日の読書計画をピックアップする場合
        // ※ 本日の日付（時間なし）を取得
        $targetDate = Carbon::today()->addDays(3)->toDateString();

        // 3日後が目標日で、まだ完了していない読書計画を取得
        $plans = ReadingPlan::where('target_date', $targetDate)
            ->where('status', 'in_progress') // または未着手のステータスに合わせて調整
            ->get();

        foreach ($plans as $plan) {
            // 読書計画の所有者（ユーザー）に対して通知を送信
            $user = $plan->user;
            if ($user) {
                $user->notify(new ReadingPlanNotification($plan, 'three_days_before'));
            }
        }

        $this->info('読書計画のリマインダー通知を送信しました。対象件数: '.$plans->count());
    }
}
