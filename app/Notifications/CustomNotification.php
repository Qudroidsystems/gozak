<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Store in database only
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject($this->data['title'] ?? 'Notification')
                    ->line($this->data['body'] ?? '')
                    ->action('View Details', url('/notifications'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'Notification',
            'body' => $this->data['body'] ?? '',
            'data' => $this->data,
            'type' => $this->data['type'] ?? 'general',
            'delivery_status' => $this->data['delivery_status'] ?? 'sent',
            'sent_via' => $this->data['sent_via'] ?? 'system',
            'fcm_response' => $this->data['fcm_response'] ?? null,
            'timestamp' => now()->toISOString(),
        ];
    }
}
