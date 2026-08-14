<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        
        // 日付オブジェクトか文字列かに対応できる安全な書き方
        $targetDate = $this->readingPlan->target_date;
        if ($targetDate instanceof \DateTimeInterface) {
            $targetDate = $targetDate->format('Y-m-d');
        }

        $message = match ($this->type) {
            'three_days_before' => "『{$bookTitle}』の読書目標日（{$targetDate}）まであと3日です。",
            'on_due_date'       => "本日（{$targetDate}）は『{$bookTitle}』の読書目標日です！",
            'three_days_after'  => "『{$bookTitle}』の読书目標日（{$targetDate}）から3日が経過しました。",
            default             => "『{$bookTitle}』の読書計画に関するお知らせです。",
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id'         => $this->readingPlan->book_id,
            'book_title'      => $bookTitle,
            'type'            => $this->type,
            'message'         => $message,
        ];
    }
}
