<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanNotification extends Notification
{
    use Queueable;

    protected $readingPlan;

    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(ReadingPlan $readingPlan, string $type)
    {
        $this->readingPlan = $readingPlan;
        $this->type = $type; // 'three_days_before', 'on_due_date', 'three_days_after' など
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $bookTitle = $this->readingPlan->book?->title ?? '本';

        $targetDate = $this->readingPlan->target_date;
        if ($targetDate instanceof \DateTimeInterface) {
            $targetDate = $targetDate->format('Y-m-d');
        }

        // Blade側の match ($timing) に合わせてキーを整理
        [$title, $body] = match ($this->type) {
            'three_days_before' => [
                '読書計画の期日が近づいています',
                "『{$bookTitle}』の読書目標日（{$targetDate}）まであと3日です。",
            ],
            'on_due_date' => [
                '本日は読書計画の期日です',
                "本日（{$targetDate}）は『{$bookTitle}』の読書目標日です！",
            ],
            'three_days_after' => [
                '読書計画の期日を過ぎています',
                "『{$bookTitle}』の読書目標日（{$targetDate}）から3日が経過しました。",
            ],
            default => [
                '読書計画のお知らせ',
                "『{$bookTitle}』の読書計画に関するお知らせです。",
            ],
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->type, // Blade側が $timing で受けるため
            'title' => $title,       // Blade側が $notification->data['title'] で受けるため
            'body' => $body,         // Blade側が $notification->data['body'] で受けるため
        ];
    }
}
