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

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, ?BarcodeService $barcodeService = null)
    {
        $this->order = $order;
        // Add barcode logic if needed
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('📦 Order Status Update - ' . config('app.name'))
                    ->view('emails.order-status-update')
                    ->with([
                        'order' => $this->order,
                    ]);
    }
}
