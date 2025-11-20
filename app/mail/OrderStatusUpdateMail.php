<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Services\BarcodeService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderStatusUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $barcodePng;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, BarcodeService $barcodeService)
    {
        $this->order = $order;
        $this->barcodePng = $barcodeService->getBarcodeForEmail($order);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $statusTitles = [
            'shipped' => '📦 Your Order Has Been Shipped!',
            'delivered' => '✅ Your Order Has Been Delivered!',
            'cancelled' => '❌ Your Order Has Been Cancelled',
        ];

        $title = $statusTitles[$this->order->status] ?? 'Order Status Updated';
        $orderIdShort = substr($this->order->id, -8);

        return $this->subject($title . ' - Order #' . $orderIdShort)
                    ->view('emails.order-status-update')
                    ->with([
                        'order' => $this->order,
                        'barcodePng' => $this->barcodePng,
                        'orderIdShort' => $orderIdShort,
                        'statusTitle' => $title,
                    ]);
    }
}