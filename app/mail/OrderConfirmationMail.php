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
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 120, 300];

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, BarcodeService $barcodeService)
    {
        $this->order = $order;
        $this->barcodePng = $barcodeService->getBarcodeForEmail($order);
        
        // Set queue properties
        $this->onQueue('emails');
        $this->afterCommit = true;
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

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        // Log the failure
        \Log::error('Order confirmation email failed to send', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);

        // You can also notify admins here if needed
    }
}