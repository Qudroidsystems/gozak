<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
// use App\Models\Notification;
use Illuminate\Support\Facades\Notification;
use App\Models\UserNotification;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Mail\Mailable;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdateMail;
use App\Mail\GenericNotificationMail;

class NotificationService
{
    protected $fcmService;
    protected $barcodeService;

    public function __construct(FcmService $fcmService, BarcodeService $barcodeService)
    {
        $this->fcmService = $fcmService;
        $this->barcodeService = $barcodeService;
    }

    /**
     * Send order confirmation with both push notification and email
     */
    public function sendOrderConfirmation(Order $order): array
    {
        $results = [
            'push_notification' => null,
            'email' => null,
            'barcode' => null,
        ];

        try {
            $user = $order->user;

            // Send push notification
            if ($user->canReceivePushNotifications('order_update')) {
                $results['push_notification'] = $this->fcmService->sendOrderStatusUpdate($order, 'pending');
            }

            // Send email with barcode
            if ($user->canReceiveEmailNotifications('order_update')) {
                $results['email'] = $this->sendOrderConfirmationEmail($order);
            }

            // Generate barcode for download
            $results['barcode'] = $this->barcodeService->generateBarcodeForOrder($order);

            $this->createNotificationRecord(
                $user,
                'Order Confirmation',
                "Your order #{$order->id} has been confirmed",
                [
                    'order_id' => $order->id,
                    'type' => 'order_created',
                    'total_amount' => $order->total_amount,
                    'items_count' => $order->items->count()
                ],
                'order_created',
                $results
            );

            $user->recordNotificationSent();

            Log::info('Order confirmation sent successfully', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'push_sent' => !isset($results['push_notification']['error']),
                'email_sent' => $results['email']['success'] ?? false,
                'barcode_generated' => $results['barcode']['success'] ?? false,
            ]);

        } catch (\Throwable $e) {
            // \Throwable (not just \Exception) so a fatal \Error -- e.g. a
            // missing class -- gets logged and absorbed here instead of
            // escaping into the DB::transaction() in PaymentController and
            // rolling back the order's paid status.
            Log::error('Failed to send order confirmation: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);

            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Send order status update with both notification and email
     */
    public function sendOrderStatusUpdate(Order $order, string $newStatus): array
    {
        $results = [
            'push_notification' => null,
            'email' => null,
        ];

        try {
            $user = $order->user;

            // Send push notification
            if ($user->canReceivePushNotifications('order_update')) {
                $results['push_notification'] = $this->fcmService->sendOrderStatusUpdate($order, $newStatus);
            }

            // Send status update email (only for important status changes)
            if ($user->canReceiveEmailNotifications('order_update') && in_array($newStatus, ['shipped', 'delivered', 'cancelled'])) {
                $results['email'] = $this->sendOrderStatusUpdateEmail($order);
            }

            $this->createNotificationRecord(
                $user,
                'Order Status Update',
                "Your order #{$order->id} status has been updated to " . ucfirst($newStatus),
                [
                    'order_id' => $order->id,
                    'type' => 'order_updated',
                    'status' => $newStatus,
                    'old_status' => $order->getOriginal('status')
                ],
                'order_updated',
                $results
            );

            Log::info('Order status update sent', [
                'order_id' => $order->id,
                'new_status' => $newStatus,
                'push_sent' => !isset($results['push_notification']['error']),
                'email_sent' => $results['email']['success'] ?? false,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to send order status update: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Send promotional notification with email fallback
     */
    public function sendPromotionalNotification(User $user, string $title, string $body, array $data = []): array
    {
        $results = [
            'push_notification' => null,
            'email' => null,
        ];

        try {
            // Try push notification first
            if ($user->canReceivePushNotifications('promotional')) {
                $results['push_notification'] = $this->fcmService->sendPromotionalNotification($user, $title, $body, $data);
            }

            // If push fails and user has email, send email
            if (($results['push_notification']['error'] ?? false) && $user->canReceiveEmailNotifications('promotional')) {
                $results['email'] = $this->sendPromotionalEmail($user, $title, $body, $data);
            }

            $this->createNotificationRecord($user, $title, $body, $data, 'promotional', $results);

        } catch (\Throwable $e) {
            Log::error('Failed to send promotional notification: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Send security alert (always sent via both channels if possible)
     */
    public function sendSecurityAlert(User $user, string $title, string $body, array $data = []): array
    {
        $results = [
            'push_notification' => null,
            'email' => null,
        ];

        try {
            // Always try to send security alerts via both channels
            if ($user->canReceivePushNotifications('security')) {
                $results['push_notification'] = $this->fcmService->sendToUser($user, $title, $body, $data, 'security');
            }

            if ($user->canReceiveEmailNotifications('security')) {
                $results['email'] = $this->sendSecurityEmail($user, $title, $body, $data);
            }

            $this->createNotificationRecord($user, $title, $body, $data, 'security', $results);

        } catch (\Throwable $e) {
            Log::error('Failed to send security alert: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Bulk send notifications to multiple users
     */
    public function sendBulkNotification(array $userIds, string $title, string $body, array $data = [], string $type = 'system'): array
    {
        $results = [];
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $results[$user->id] = $this->sendToUser($user, $title, $body, $data, $type);
        }

        return $results;
    }

    /**
     * Generic method to send both push and email notifications
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        $results = [
            'push_notification' => null,
            'email' => null,
        ];

        try {
            // Send push notification
            if ($user->canReceivePushNotifications($type)) {
                $results['push_notification'] = $this->fcmService->sendToUser($user, $title, $body, $data, $type);
            }

            // Always send email for important notifications
            if ($user->canReceiveEmailNotifications($type) && in_array($type, ['order_created', 'order_updated', 'security'])) {
                $results['email'] = $this->sendGenericEmail($user, $title, $body, $data);
            }

            $this->createNotificationRecord($user, $title, $body, $data, $type, $results);

        } catch (\Throwable $e) {
            Log::error('Failed to send notification to user: ' . $e->getMessage());
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Send order confirmation email with barcode
     */
    protected function sendOrderConfirmationEmail(Order $order): array
    {
        try {
            Mail::to($order->user->email)
                ->send(new OrderConfirmationMail($order, $this->barcodeService));

            return [
                'success' => true,
                'message' => 'Order confirmation email sent successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to send order confirmation email: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send order status update email
     */
    protected function sendOrderStatusUpdateEmail(Order $order): array
    {
        try {
            Mail::to($order->user->email)
                ->send(new OrderStatusUpdateMail($order, $this->barcodeService));

            return [
                'success' => true,
                'message' => 'Order status update email sent successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to send order status update email: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send promotional email
     */
    protected function sendPromotionalEmail(User $user, string $title, string $body, array $data = []): array
    {
        try {
            // You can create a PromotionalMail mailable class for this
            // For now, we'll use a generic email
            Mail::to($user->email)
                ->send(new \App\Mail\GenericNotificationMail($title, $body, $data, 'promotional'));

            return [
                'success' => true,
                'message' => 'Promotional email sent successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to send promotional email: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send security email
     */
    protected function sendSecurityEmail(User $user, string $title, string $body, array $data = []): array
    {
        try {
            // You can create a SecurityAlertMail mailable class for this
            Mail::to($user->email)
                ->send(new \App\Mail\GenericNotificationMail($title, $body, $data, 'security'));

            return [
                'success' => true,
                'message' => 'Security email sent successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to send security email: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send generic notification email
     */
    protected function sendGenericEmail(User $user, string $title, string $body, array $data = []): array
    {
        try {
            Mail::to($user->email)
                ->send(new \App\Mail\GenericNotificationMail($title, $body, $data));

            return [
                'success' => true,
                'message' => 'Notification email sent successfully',
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to send generic email: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create notification record in database
     */

    // Update the createNotificationRecord method
    protected function createNotificationRecord(User $user, string $title, string $body, array $data, string $type, array $sendResults): void
    {
        try {
            $successCount = 0;
            if (isset($sendResults['push_notification']) && !isset($sendResults['push_notification']['error'])) {
                $successCount++;
            }
            if (isset($sendResults['email']) && ($sendResults['email']['success'] ?? false)) {
                $successCount++;
            }

            $deliveryStatus = $successCount > 0 ? UserNotification::STATUS_SENT : UserNotification::STATUS_FAILED;

            UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sent_via' => $this->getSentVia($sendResults),
                'delivery_status' => $deliveryStatus,
                'fcm_response' => $sendResults,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to create notification record: ' . $e->getMessage());
        }
    }

    /**
     * Determine which channels were used for sending
     */
    protected function getSentVia(array $sendResults): string
    {
        $channels = [];

        if (isset($sendResults['push_notification']) && !isset($sendResults['push_notification']['error'])) {
            $channels[] = 'fcm';
        }

        if (isset($sendResults['email']) && ($sendResults['email']['success'] ?? false)) {
            $channels[] = 'email';
        }

        return empty($channels) ? 'none' : implode(',', $channels);
    }

    /**
     * Get notification statistics for a user
     */
    public function getUserNotificationStats(User $user): array
    {
        return [
            'total_notifications' => $user->notifications()->count(),
            'unread_count' => $user->unreadNotifications()->count(),
            'read_count' => $user->notifications()->read()->count(),
            'recent_notifications' => $user->notifications()->recent(7)->count(),
            'preferences' => $user->getNotificationPreferences(),
        ];
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $days = 90): int
    {
        $cutoffDate = now()->subDays($days);
        $deletedCount = Notification::where('created_at', '<', $cutoffDate)->delete();

        Log::info("Cleaned up {$deletedCount} notifications older than {$days} days");

        return $deletedCount;
    }
}
