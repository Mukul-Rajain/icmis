<?php

namespace App\Notifications;

use App\Models\Hearing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HearingScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(public Hearing $hearing) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
        // Add 'nexmo' / SMS channel later if desired
    }

    public function toMail($notifiable): MailMessage
    {
        $case = $this->hearing->case;

        return (new MailMessage)
            ->subject("Hearing Scheduled: {$case->case_number}")
            ->greeting("Dear {$notifiable->name},")
            ->line("A hearing has been scheduled for your case.")
            ->line("**Case Number:** {$case->case_number}")
            ->line("**Title:** {$case->title}")
            ->line("**Hearing Date:** {$this->hearing->scheduled_date->format('d M Y')}")
            ->line("**Time:** " . ($this->hearing->scheduled_time ?? 'To be announced'))
            ->line("**Court:** {$this->hearing->court->name}")
            ->action('View Case Details', route('cases.show', $case))
            ->line('Please be present or instruct your lawyer accordingly.');
    }

    public function toArray($notifiable): array
    {
        return [
            'case_id' => $this->hearing->case_id,
            'case_number' => $this->hearing->case->case_number,
            'hearing_id' => $this->hearing->id,
            'scheduled_date' => $this->hearing->scheduled_date->toDateString(),
            'message' => "Hearing scheduled on {$this->hearing->scheduled_date->format('d M Y')}",
        ];
    }
}
