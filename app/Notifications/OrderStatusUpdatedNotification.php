<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Order $order, protected string $oldStatus)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Status Updated #' . $this->order->invoice_number)
            ->line('Order status has been changed.')
            ->line('Order: #' . ($this->order->invoice_number ?? $this->order->id))
            ->line('Customer: ' . $this->order->user->name)
            ->line('Status: ' . ucfirst($this->oldStatus) . ' → ' . ucfirst($this->order->status))
            ->action('View Order', route('adminorders.show', $this->order));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'invoice' => $this->order->invoice_number ?? substr($this->order->id, 0, 8),
            'customer' => $this->order->user->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->order->status,
            'message' => 'Order status changed to ' . ucfirst($this->order->status),
            'icon' => 'bi-arrow-repeat',
            'color' => 'info',
        ];
    }
}
