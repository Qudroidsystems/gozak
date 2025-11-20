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
     * Create structured data for barcode
     */
    protected function createBarcodeData(Order $order): string
    {
        $data = [
            'order_id' => $order->id,
            'customer' => $order->user->full_name,
            'email' => $order->user->email,
            'total' => $order->total_amount,
            'currency' => 'NGN',
            'date' => $order->created_at->toISOString(),
            'items_count' => $order->items->count(),
            'type' => 'order',
            'app' => config('app.name'),
            'items' => $order->items->map(function ($item) {
                return [
                    'title' => $item->title,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            })->toArray(),
        ];

        return json_encode($data);
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
     * Generate barcode for order and return all data
     */
    public function generateBarcodeForOrder(Order $order): array
    {
        try {
            $barcodePng = $this->generateOrderBarcode($order);
            $filename = $this->saveBarcodeToStorage($order);
            
            // Update order with barcode info
            $barcodeData = $this->createBarcodeData($order);
            $order->updateBarcodeInfo($filename, json_decode($barcodeData, true));
            
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
}