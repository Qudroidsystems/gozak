<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Refund;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Refunds for orders.
 *
 *  gateway → money goes back automatically to the card / bank account the
 *            customer paid with. Supported for Paystack payments (Paystack
 *            Refund API). Paystack settles refunds asynchronously, so the
 *            refund starts as pending/processing and is completed by the
 *            refund.processed webhook or the "Check status" button.
 *  manual  → you already paid the customer back yourself (cash, bank
 *            transfer, or from the OPay merchant dashboard); we just record it.
 *
 * Pending refunds reserve their amount, so you can't refund the same money twice.
 */
class RefundService
{
    /** Map Paystack refund states to ours. */
    protected const PAYSTACK_STATUS = [
        'pending'         => 'pending',
        'processing'      => 'processing',
        'needs-attention' => 'processing',
        'processed'       => 'processed',
        'failed'          => 'failed',
        'reversed'        => 'failed',
    ];

    /** What the admin page needs to show the refund form. */
    public function summary(Order $order): array
    {
        $tx = $order->latestSuccessfulTransaction();
        $gateway = $tx?->payment_method;
        return [
            'paid'            => ($order->payment_status ?? '') === 'paid' || ($order->payment_status ?? '') === 'refunded' || (bool) $tx,
            'transaction'     => $tx,
            'gateway'         => $gateway,
            'can_auto_refund' => $tx && in_array($gateway, ['paystack', 'gozak_credit'], true),
            'refunded'        => (float) $order->totalRefunded(),
            'pending'         => (float) $order->pendingRefunds(),
            'refundable'      => (float) $order->refundableAmount(),
        ];
    }

    /**
     * @param  string $method   gateway | manual
     * @param  string $channel  for manual: cash | bank_transfer | opay | paystack
     */
    public function refund(Order $order, float $amount, string $reason, string $method, int $adminId, ?string $channel = null, ?string $manualReference = null): Refund
    {
        $amount = round($amount, 2);

        // Lock the order row so two admins can't refund the same money at once.
        return DB::transaction(function () use ($order, $amount, $reason, $method, $adminId, $channel, $manualReference) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($amount <= 0) {
                throw new \InvalidArgumentException('Enter an amount greater than zero.');
            }
            if ($amount > $order->refundableAmount() + 0.001) {
                throw new \InvalidArgumentException('You can refund at most ₦' . number_format($order->refundableAmount(), 2) . '.');
            }

            if ($method === 'gateway') {
                $tx = $order->latestSuccessfulTransaction();
                if ($tx && $tx->payment_method === 'gozak_credit') {
                    // Paid with Gozak Credit → give the money back to the credit balance (instant).
                    $refund = Refund::create([
                        'order_id'              => $order->id,
                        'user_id'               => $adminId,
                        'amount'                => $amount,
                        'reason'                => $reason,
                        'method'                => 'gateway',
                        'gateway'               => 'gozak_credit',
                        'transaction_reference' => $tx->reference,
                        'status'                => 'processed',
                        'processed_at'          => now(),
                    ]);
                    app(\App\Services\Credit\CreditService::class)->refundOrder($order, $amount, $adminId, 'Refund: ' . $reason);
                    $this->syncOrderPaymentStatus($order);
                    return $refund->fresh();
                }
                if (!$tx || $tx->payment_method !== 'paystack') {
                    throw new \InvalidArgumentException('Automatic refunds are only available for orders paid with Paystack. Refund the customer yourself, then record it as a manual refund.');
                }

                $refund = Refund::create([
                    'order_id'              => $order->id,
                    'user_id'               => $adminId,
                    'amount'                => $amount,
                    'reason'                => $reason,
                    'method'                => 'gateway',
                    'gateway'               => 'paystack',
                    'transaction_reference' => $tx->reference,
                    'status'                => 'pending',
                ]);

                try {
                    $res  = app(PaystackService::class)->createRefund(
                        $tx->reference,
                        // Full refund of the whole payment → let Paystack refund everything.
                        abs($amount - (float) $tx->amount) < 0.01 ? null : $amount,
                        'Order ' . substr($order->id, 0, 8) . ': ' . $reason,
                        'Refund for your GozakMart order #' . strtoupper(substr(str_replace('-', '', $order->id), 0, 8))
                    );
                    $data = $res['data'] ?? [];
                    $refund->update([
                        'gateway_refund_id' => isset($data['id']) ? (string) $data['id'] : null,
                        'gateway_response'  => $data,
                        'status'            => self::PAYSTACK_STATUS[$data['status'] ?? 'pending'] ?? 'pending',
                        'processed_at'      => ($data['status'] ?? '') === 'processed' ? now() : null,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Paystack refund failed', ['order' => $order->id, 'error' => $e->getMessage()]);
                    $refund->update(['status' => 'failed', 'failure_reason' => mb_substr($e->getMessage(), 0, 250)]);
                    throw new \RuntimeException('Paystack could not refund this payment: ' . $e->getMessage());
                }
            } else {
                $refund = Refund::create([
                    'order_id'         => $order->id,
                    'user_id'          => $adminId,
                    'amount'           => $amount,
                    'reason'           => $reason,
                    'method'           => 'manual',
                    'gateway'          => $channel ?: 'bank_transfer',
                    'manual_reference' => $manualReference,
                    'status'           => 'processed',
                    'processed_at'     => now(),
                ]);
            }

            $this->syncOrderPaymentStatus($order);
            return $refund->fresh();
        });
    }

    /** Ask Paystack for the latest state of a refund. */
    public function refreshStatus(Refund $refund): Refund
    {
        if ($refund->method !== 'gateway' || $refund->gateway !== 'paystack' || !$refund->gateway_refund_id) {
            return $refund;
        }
        $res  = app(PaystackService::class)->fetchRefund($refund->gateway_refund_id);
        $this->applyGatewayState($refund, $res['data'] ?? []);
        return $refund->fresh();
    }

    /** Paystack webhook: refund.processed / refund.failed / refund.pending … */
    public function handlePaystackWebhook(array $data): void
    {
        $id  = isset($data['id']) ? (string) $data['id'] : null;
        $ref = $data['transaction_reference'] ?? ($data['transaction']['reference'] ?? null);

        $refund = null;
        if ($id) {
            $refund = Refund::where('gateway_refund_id', $id)->first();
        }
        if (!$refund && $ref) {
            $refund = Refund::where('transaction_reference', $ref)
                ->whereIn('status', Refund::OPEN_STATUSES)
                ->latest()->first();
        }
        if ($refund) {
            $this->applyGatewayState($refund, $data);
        } else {
            Log::info('Paystack refund webhook for unknown refund', ['id' => $id, 'reference' => $ref]);
        }
    }

    protected function applyGatewayState(Refund $refund, array $data): void
    {
        $status = self::PAYSTACK_STATUS[$data['status'] ?? ''] ?? $refund->status;
        $refund->update([
            'status'            => $status,
            'gateway_refund_id' => $refund->gateway_refund_id ?: (isset($data['id']) ? (string) $data['id'] : null),
            'gateway_response'  => $data,
            'processed_at'      => $status === 'processed' ? ($refund->processed_at ?? now()) : $refund->processed_at,
            'failure_reason'    => $status === 'failed' ? ($data['merchant_note'] ?? $data['message'] ?? 'Refund failed at Paystack') : null,
        ]);
        if ($refund->order) {
            $this->syncOrderPaymentStatus($refund->order);
        }
    }

    /** Fully refunded orders become payment_status = refunded (partial stays "paid"). */
    public function syncOrderPaymentStatus(Order $order): void
    {
        $order->refresh();
        $refunded = (float) $order->totalRefunded();
        if ($refunded > 0 && $refunded + 0.01 >= (float) $order->total_amount) {
            if ($order->payment_status !== 'refunded') {
                $order->forceFill(['payment_status' => 'refunded'])->save();
            }
        } elseif ($order->payment_status === 'refunded') {
            $order->forceFill(['payment_status' => 'paid'])->save();
        }
    }
}
