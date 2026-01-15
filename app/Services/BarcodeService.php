<?php

namespace App\Services;

use DNS2D;
use Exception;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BarcodeService
{
    /**
     * Generate barcode for order
     */
    public function generateOrderBarcode(Order $order): string
    {
        try {
            // Create barcode data string
            $barcodeData = $this->createBarcodeData($order);

            // Generate QR code as PNG string
            $barcodePng = DNS2D::getBarcodePNG($barcodeData, 'QRCODE', 8, 8);

            return $barcodePng;

        } catch (Exception $e) {
            Log::error('Barcode generation failed: ' . $e->getMessage());
            throw new Exception('Failed to generate barcode: ' . $e->getMessage());
        }
    }

    /**
     * Create structured data for barcode - LIMIT THE SIZE
     */
    protected function createBarcodeData(Order $order): string
    {
        // Limit the data to prevent database issues
        $data = [
            'order_id' => $order->id,
            'customer' => substr($order->user->full_name ?? 'Customer', 0, 100), // Limit name length
            'email' => substr($order->user->email ?? '', 0, 100), // Limit email length
            'total' => number_format($order->total_amount, 2),
            'currency' => 'NGN',
            'date' => $order->created_at->toISOString(),
            'items_count' => $order->items->count(),
            'type' => 'order',
            'app' => config('app.name'),
            // Remove items from barcode data to reduce size
            // 'items' => $order->items->map(function ($item) {
            //     return [
            //         'title' => substr($item->title, 0, 50), // Limit title length
            //         'quantity' => $item->quantity,
            //         'price' => number_format($item->price, 2),
            //     ];
            // })->toArray(),
        ];

        $jsonData = json_encode($data);

        // If still too large, remove more fields
        if (strlen($jsonData) > 10000) { // 10KB max
            unset($data['customer'], $data['email']);
            $jsonData = json_encode($data);
        }

        return $jsonData;
    }

    /**
     * Save barcode to storage and return file path
     */
    public function saveBarcodeToStorage(Order $order): string
    {
        try {
            $barcodePng = $this->generateOrderBarcode($order);
            $filename = "barcodes/order_{$order->id}_" . time() . '.png';

            Storage::disk('public')->put($filename, base64_decode($barcodePng));

            return $filename;

        } catch (Exception $e) {
            Log::error('Failed to save barcode to storage: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get barcode as base64 for embedding in emails
     */
    public function getBarcodeForEmail(Order $order): string
    {
        return $this->generateOrderBarcode($order);
    }

    /**
     * Get barcode download URL
     */
    public function getBarcodeDownloadUrl(Order $order): string
    {
        try {
            $filename = $this->saveBarcodeToStorage($order);
            return Storage::disk('public')->url($filename);

        } catch (Exception $e) {
            Log::error('Failed to get barcode download URL: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Parse barcode data (for scanning)
     */
    public function parseBarcodeData(string $barcodeData): array
    {
        try {
            $data = json_decode($barcodeData, true);

            return [
                'order_id' => $data['order_id'] ?? null,
                'customer' => $data['customer'] ?? null,
                'email' => $data['email'] ?? null,
                'total' => $data['total'] ?? null,
                'date' => $data['date'] ?? null,
                'items_count' => $data['items_count'] ?? null,
                'items' => $data['items'] ?? [],
                'valid' => isset($data['order_id']) && isset($data['type']) && $data['type'] === 'order',
            ];

        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Invalid barcode data'];
        }
    }

    /**
     * Generate barcode for order and return all data - FIXED VERSION
     */
    public function generateBarcodeForOrder(Order $order): array
    {
        try {
            $barcodePng = $this->generateOrderBarcode($order);
            $filename = $this->saveBarcodeToStorage($order);
            $barcodeData = $this->createBarcodeData($order);

            // Update the order directly - DON'T use non-existent method
            $order->update([
                'barcode_path' => $filename,
                'barcode_data' => json_decode($barcodeData, true), // Store as array, not JSON string
            ]);

            Log::info('Barcode generated and saved for order', [
                'order_id' => $order->id,
                'barcode_path' => $filename,
                'barcode_data_length' => strlen($barcodeData),
            ]);

            return [
                'success' => true,
                'barcode_url' => Storage::disk('public')->url($filename),
                'barcode_data_url' => 'data:image/png;base64,' . $barcodePng,
                'barcode_data' => $barcodeData,
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate barcode for order: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get barcode data as array (for display)
     */
    public function getBarcodeDataArray(Order $order): array
    {
        try {
            $barcodeData = $order->barcode_data;

            if (is_string($barcodeData)) {
                return json_decode($barcodeData, true) ?? [];
            }

            return is_array($barcodeData) ? $barcodeData : [];
        } catch (Exception $e) {
            Log::error('Failed to get barcode data array: ' . $e->getMessage());
            return [];
        }
    }
}
