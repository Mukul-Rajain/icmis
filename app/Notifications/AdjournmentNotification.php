<?php

namespace App\Notifications;

use App\Models\Adjournment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdjournmentNotification extends Notification
{
    use Queueable;

    public function __construct(public Adjournment $adjournment) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $case = $this->adjournment->case;

        return (new MailMessage)
            ->subject("Hearing Adjourned: {$case->case_number}")
            ->greeting("Dear {$notifiable->name},")
            ->line("The hearing in your case has been adjourned.")
            ->line("**Case Number:** {$case->case_number}")
            ->line("**Reason:** " . str_replace('_', ' ', ucfirst($this->adjournment->reason_category)))
            ->line("**Next Hearing Date:** {$this->adjournment->new_date->format('d M Y')}")
            ->action('View Case Details', route('cases.show', $case));
    }

    public function toArray($notifiable): array
    {
        return [
            'case_id' => $this->adjournment->case_id,
            'case_number' => $this->adjournment->case->case_number,
            'new_date' => $this->adjournment->new_date->toDateString(),
            'message' => "Hearing adjourned to {$this->adjournment->new_date->format('d M Y')}",
        ];
    }
}
