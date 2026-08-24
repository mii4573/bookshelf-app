<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReadingPlanNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バッチコマンドが3日前の読書計画に対して正しく通知を送信するかテスト
     */
    public function test_reminder_command_sends_notification_for_three_days_before()
    {
        // ユーザーと本を作成
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 3日後の読書計画を作成
        $plan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(3)->toDateString(),
            'status' => 'in_progress',
        ]);

        // 通知がまだ送信されていないことを確認
        $this->assertCount(0, $user->unreadNotifications);

        // バッチコマンドを実行
        Artisan::call('notifications:send-reading-reminders');

        // 未読通知が1件増えていることを確認
        $user->refresh();
        $this->assertCount(1, $user->unreadNotifications);
    }

    /**
     * ログインユーザーが通知一覧画面にアクセスできるかテスト
     */
    public function test_user_can_view_notifications_page()
    {
        $user = User::factory()->create();

        // ログインして通知一覧ページにアクセス
        $response = $this->actingAs($user)->get(route('notifications.index'));

        // ステータスコード 200 (成功) が返ることを確認
        $response->assertStatus(200);
    }

    /**
     * 読書計画通知のデータ構造が正しいかテスト
     */
    public function test_reading_plan_notification_contains_correct_data()
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => 'テストの本']);
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $notification = new ReadingPlanNotification($plan, 'three_days_before');

        // 実際のキー構造（reading_plan_id, title, body, timing）に合わせてテスト
        $arrayData = $notification->toArray($user);

        $this->assertArrayHasKey('reading_plan_id', $arrayData);
        $this->assertArrayHasKey('title', $arrayData);
        $this->assertArrayHasKey('body', $arrayData);
        $this->assertArrayHasKey('timing', $arrayData);
        $this->assertEquals($plan->id, $arrayData['reading_plan_id']);
        $this->assertEquals('three_days_before', $arrayData['timing']);
    }
}
