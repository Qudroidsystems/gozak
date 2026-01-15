<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Services\BarcodeService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $barcodePng;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, ?BarcodeService $barcodeService = null)
    {
        $this->order = $order;
        if ($barcodeService) {
            $this->barcodePng = $barcodeService->getBarcodeForEmail($order);
        }
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $orderIdShort = substr($this->order->id, -8);

        return $this->subject('🎉 Order Confirmation - ' . config('app.name'))
                    ->view('emails.order-confirmation')
                    ->with([
                        'order' => $this->order,
                        'barcodePng' => $this->barcodePng,
                        'orderIdShort' => $orderIdShort,
                    ]);
    }
}
