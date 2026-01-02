<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Order $order)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database']; // Admin gets email + dashboard notification
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Order Received #' . $this->order->invoice_number)
            ->greeting('Hello Admin!')
            ->line('A new order has been placed.')
            ->line('Order ID: #' . ($this->order->invoice_number ?? $this->order->id))
            ->line('Customer: ' . $this->order->user->name)
            ->line('Total: $' . number_format($this->order->total_amount, 2))
            ->action('View Order', route('adminorders.show', $this->order))
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'invoice' => $this->order->invoice_number ?? substr($this->order->id, 0, 8),
            'customer' => $this->order->user->name,
            'amount' => $this->order->total_amount,
            'message' => 'New order placed',
            'icon' => 'bi-cart-plus',
            'color' => 'success',
        ];
    }
}
